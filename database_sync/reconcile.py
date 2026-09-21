from __future__ import annotations

from dataclasses import dataclass
import base64
import hashlib
import json
from typing import Any


@dataclass(frozen=True)
class Action:
    direction: str
    key: str
    row: dict[str, Any] | None


@dataclass(frozen=True)
class Conflict:
    key: str
    kind: str


def _json_value(value: Any) -> Any:
    if isinstance(value, bytes):
        return {"__bytes__": base64.b64encode(value).decode("ascii")}
    if hasattr(value, "isoformat"):
        return value.isoformat()
    if isinstance(value, dict):
        return {str(key): _json_value(item) for key, item in value.items()}
    if isinstance(value, (list, tuple)):
        return [_json_value(item) for item in value]
    return value


def row_hash(row: dict[str, Any]) -> str:
    payload = json.dumps(_json_value(row), sort_keys=True, separators=(",", ":"), default=str)
    return hashlib.sha256(payload.encode("utf-8")).hexdigest()


def _changed(row: dict[str, Any] | None, baseline: str | None) -> bool:
    return row is not None and (baseline is None or row_hash(row) != baseline)


def reconcile_table(
    snapshots: dict[str, dict[str, dict[str, Any]]],
    baseline: dict[str, str],
    *,
    allow_deletes: bool = False,
) -> tuple[list[Action], list[Conflict]]:
    """Plan lossless bidirectional changes for one table.

    A baseline hash lets the planner distinguish a one-sided update from a
    concurrent update. Without a baseline, differing existing rows are treated
    as conflicts rather than guessed merges.
    """
    local = snapshots.get("local", {})
    remote = snapshots.get("remote", {})
    actions: list[Action] = []
    conflicts: list[Conflict] = []

    for key in sorted(set(local) | set(remote)):
        local_row = local.get(key)
        remote_row = remote.get(key)
        previous = baseline.get(key)

        if local_row is None or remote_row is None:
            present_row = local_row or remote_row
            if previous is None:
                direction = "to_remote" if local_row is not None else "to_local"
                actions.append(Action(direction, key, present_row))
                continue

            present_hash = row_hash(present_row) if present_row is not None else None
            if present_hash != previous:
                conflicts.append(Conflict(key, "delete_vs_update"))
            elif allow_deletes:
                direction = "delete_remote" if local_row is None else "delete_local"
                actions.append(Action(direction, key, present_row))
            else:
                conflicts.append(Conflict(key, "delete_requires_approval"))
            continue

        if row_hash(local_row) == row_hash(remote_row):
            continue

        local_changed = _changed(local_row, previous)
        remote_changed = _changed(remote_row, previous)
        if local_changed and not remote_changed:
            actions.append(Action("to_remote", key, local_row))
        elif remote_changed and not local_changed:
            actions.append(Action("to_local", key, remote_row))
        else:
            conflicts.append(Conflict(key, "both_sides_changed"))

    return actions, conflicts
