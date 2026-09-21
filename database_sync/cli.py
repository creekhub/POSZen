from __future__ import annotations

import argparse
import logging
import os
from pathlib import Path
import time
from typing import Any

from .db import MariaDbStore, SqliteStore, TableSchema, key_for
from .reconcile import Action, reconcile_table, row_hash
from .state import load, save

LOGGER = logging.getLogger("poszen-db-sync")
EXCLUDED_TABLES = {"migrations", "cache", "cache_locks", "jobs", "job_batches", "failed_jobs", "sessions", "password_reset_tokens", "users"}


def env_file(path: Path) -> dict[str, str]:
    values: dict[str, str] = {}
    if not path.exists():
        return values
    for line in path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        value = value.strip()
        if len(value) >= 2 and value[0] == value[-1] and value[0] in "\"'":
            value = value[1:-1]
        values[key.strip()] = value
    return values


def setting(name: str, values: dict[str, str], default: str | None = None) -> str | None:
    return os.environ.get(name, values.get(name, default))


def parser() -> argparse.ArgumentParser:
    result = argparse.ArgumentParser(description="Bidirectional, conflict-safe POSZen SQLite/MariaDB synchronization")
    result.add_argument("--repo-root", default=".")
    result.add_argument("--tables", help="Comma-separated table names; default is all compatible non-system tables")
    result.add_argument("--batch-size", type=int, default=500)
    result.add_argument("--apply", action="store_true", help="Write planned changes; otherwise perform a dry run")
    result.add_argument("--confirm-target", help="Required with --apply; must equal SYNC_MARIADB_DATABASE")
    result.add_argument("--allow-deletes", action="store_true", help="Propagate deletions after explicit approval")
    result.add_argument("--state-file", default=None)
    result.add_argument("--poll", type=int, default=0, help="Repeat every N seconds; useful when local or online connectivity is intermittent")
    result.add_argument("--connect-timeout", type=int, default=10)
    result.add_argument("--log-level", default="INFO", choices=("DEBUG", "INFO", "WARNING", "ERROR"))
    return result


def _compatible_tables(local: SqliteStore, remote: MariaDbStore, requested: set[str] | None) -> tuple[list[tuple[str, TableSchema, tuple[str, ...]]], list[str]]:
    names = (local.tables() & remote.tables()) - EXCLUDED_TABLES
    if requested is not None:
        names &= requested
    plans: list[tuple[str, TableSchema, tuple[str, ...]]] = []
    problems: list[str] = []
    for name in sorted(names):
        local_schema = local.schema(name)
        remote_schema = remote.schema(name)
        if not local_schema.primary_key or local_schema.primary_key != remote_schema.primary_key:
            problems.append(f"{name}: primary keys differ or are missing")
            continue
        if set(local_schema.columns) != set(remote_schema.columns):
            problems.append(f"{name}: columns differ between local and online schemas")
            continue
        columns = tuple(column for column in local_schema.columns if column in remote_schema.columns)
        if any(column not in columns for column in local_schema.primary_key):
            problems.append(f"{name}: primary-key columns are not common")
            continue
        if not columns:
            problems.append(f"{name}: no common columns")
            continue
        plans.append((name, local_schema, columns))
    missing = requested - names if requested is not None else set()
    problems.extend(f"{name}: not present in both databases" for name in sorted(missing))
    return plans, problems


def _apply_actions(local: SqliteStore, remote: MariaDbStore, actions_by_table: dict[str, list[Action]], schemas: dict[str, tuple[TableSchema, tuple[str, ...]]]) -> None:
    for table, actions in actions_by_table.items():
        local_schema, columns = schemas[table]
        local.begin()
        remote.begin()
        try:
            for action in actions:
                if action.direction == "to_remote":
                    remote.upsert(table, columns, local_schema.primary_key, action.row or {})
                elif action.direction == "to_local":
                    local.upsert(table, columns, local_schema.primary_key, action.row or {})
                elif action.direction == "delete_remote":
                    remote.delete(table, local_schema.primary_key, action.row or {})
                elif action.direction == "delete_local":
                    local.delete(table, local_schema.primary_key, action.row or {})
            local.commit()
            remote.commit()
        except Exception:
            local.rollback()
            remote.rollback()
            raise


def run_once(args: argparse.Namespace) -> int:
    root = Path(args.repo_root).resolve()
    values = env_file(root / ".env")
    sqlite_path = setting("SYNC_SQLITE_PATH", values, setting("DB_DATABASE", values))
    target_database = setting("SYNC_MARIADB_DATABASE", values)
    required = {
        "SYNC_MARIADB_HOST": setting("SYNC_MARIADB_HOST", values),
        "SYNC_MARIADB_DATABASE": target_database,
        "SYNC_MARIADB_USER": setting("SYNC_MARIADB_USER", values),
        "SYNC_MARIADB_PASSWORD": setting("SYNC_MARIADB_PASSWORD", values),
    }
    if not sqlite_path or not Path(sqlite_path).exists():
        raise RuntimeError(f"SQLite database does not exist: {sqlite_path!r}")
    missing = [name for name, value in required.items() if not value]
    if missing:
        raise RuntimeError("Missing online database settings: " + ", ".join(missing))
    if args.apply and args.confirm_target != target_database:
        raise RuntimeError("--apply requires --confirm-target matching SYNC_MARIADB_DATABASE")
    if args.batch_size < 1:
        raise RuntimeError("--batch-size must be positive")

    state_path = args.state_file or setting("SYNC_STATE_FILE", values, str(root / "database_sync" / "state.json"))
    state = load(state_path)
    requested = {name.strip() for name in args.tables.split(",") if name.strip()} if args.tables else None
    local = SqliteStore(sqlite_path)
    remote = MariaDbStore(
        host=required["SYNC_MARIADB_HOST"],
        port=int(setting("SYNC_MARIADB_PORT", values, "3306") or "3306"),
        database=target_database or "",
        user=required["SYNC_MARIADB_USER"],
        password=required["SYNC_MARIADB_PASSWORD"],
        connect_timeout=args.connect_timeout,
    )
    try:
        plans, problems = _compatible_tables(local, remote, requested)
        for problem in problems:
            LOGGER.error(problem)
        if problems and requested is not None:
            return 2
        actions_by_table: dict[str, list[Action]] = {}
        schemas: dict[str, tuple[TableSchema, tuple[str, ...]]] = {}
        conflicts = 0
        for table, local_schema, columns in plans:
            local_rows = local.snapshot(table, columns, local_schema.primary_key, args.batch_size)
            remote_rows = remote.snapshot(table, columns, local_schema.primary_key, args.batch_size)
            baseline = state.get("tables", {}).get(table, {}).get("baseline", {})
            actions, table_conflicts = reconcile_table(
                {"local": local_rows, "remote": remote_rows},
                baseline,
                allow_deletes=args.allow_deletes,
            )
            actions_by_table[table] = actions
            schemas[table] = (local_schema, columns)
            conflicts += len(table_conflicts)
            LOGGER.info("%s: local=%d remote=%d planned=%d conflicts=%d", table, len(local_rows), len(remote_rows), len(actions), len(table_conflicts))
            for conflict in table_conflicts:
                LOGGER.error("%s key=%s kind=%s", table, conflict.key, conflict.kind)

        total_actions = sum(len(actions) for actions in actions_by_table.values())
        if conflicts:
            LOGGER.error("No writes performed: %d conflicts require review", conflicts)
            return 2
        if not args.apply:
            LOGGER.info("Dry run complete: %d changes would be applied", total_actions)
            return 0

        _apply_actions(local, remote, actions_by_table, schemas)
        new_tables: dict[str, Any] = {}
        for table, local_schema, columns in plans:
            rows = local.snapshot(table, columns, local_schema.primary_key, args.batch_size)
            new_tables[table] = {"baseline": {key: row_hash(row) for key, row in rows.items()}}
        save(state_path, {"version": 1, "tables": new_tables})
        LOGGER.info("Apply complete: %d changes committed; state saved to %s", total_actions, state_path)
        return 0
    finally:
        local.close()
        remote.close()


def main(argv: list[str] | None = None) -> int:
    args = parser().parse_args(argv)
    logging.basicConfig(level=getattr(logging, args.log_level), format="%(asctime)s %(levelname)s %(message)s")
    while True:
        try:
            result = run_once(args)
        except Exception as error:
            LOGGER.error("Synchronization unavailable or failed: %s", error)
            result = 1
        if not args.poll or result == 0:
            return result
        LOGGER.warning("Retrying in %d seconds", args.poll)
        time.sleep(args.poll)
