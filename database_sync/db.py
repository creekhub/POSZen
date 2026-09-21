from __future__ import annotations

from dataclasses import dataclass
from importlib import import_module
import re
import sqlite3
from typing import Any, Iterator

_IDENTIFIER = re.compile(r"^[A-Za-z_][A-Za-z0-9_]*$")


@dataclass(frozen=True)
class TableSchema:
    name: str
    columns: tuple[str, ...]
    primary_key: tuple[str, ...]


def validate_identifier(identifier: str) -> str:
    if not _IDENTIFIER.fullmatch(identifier):
        raise ValueError(f"Unsupported SQL identifier: {identifier!r}")
    return identifier


def key_for(row: dict[str, Any], primary_key: tuple[str, ...]) -> str:
    import json

    return json.dumps([row[column] for column in primary_key], default=str, separators=(",", ":"))


class SqliteStore:
    placeholder = "?"

    def __init__(self, path: str):
        self.connection = sqlite3.connect(path, timeout=30)
        self.connection.row_factory = sqlite3.Row
        self.connection.execute("PRAGMA busy_timeout = 30000")

    def close(self) -> None:
        self.connection.close()

    def begin(self) -> None:
        self.connection.execute("BEGIN")

    def commit(self) -> None:
        self.connection.commit()

    def rollback(self) -> None:
        self.connection.rollback()

    def tables(self) -> set[str]:
        rows = self.connection.execute(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
        )
        return {row[0] for row in rows}

    def schema(self, table: str) -> TableSchema:
        validate_identifier(table)
        rows = self.connection.execute(f'PRAGMA table_info("{table}")').fetchall()
        columns = sorted(rows, key=lambda row: row[0])
        primary_key = tuple(row[1] for row in columns if row[5])
        return TableSchema(table, tuple(row[1] for row in columns), primary_key)

    def snapshot(self, table: str, columns: tuple[str, ...], primary_key: tuple[str, ...], batch_size: int) -> dict[str, dict[str, Any]]:
        quoted = ", ".join(f'"{validate_identifier(column)}"' for column in columns)
        cursor = self.connection.execute(f'SELECT {quoted} FROM "{validate_identifier(table)}"')
        result: dict[str, dict[str, Any]] = {}
        while rows := cursor.fetchmany(batch_size):
            for values in rows:
                row = {column: values[column] for column in columns}
                result[key_for(row, primary_key)] = row
        return result

    def upsert(self, table: str, columns: tuple[str, ...], primary_key: tuple[str, ...], row: dict[str, Any]) -> None:
        table_name = validate_identifier(table)
        quoted_columns = ", ".join(f'"{validate_identifier(column)}"' for column in columns)
        placeholders = ", ".join(self.placeholder for _ in columns)
        updates = tuple(column for column in columns if column not in primary_key)
        if updates:
            assignments = ", ".join(f'"{validate_identifier(column)}" = excluded."{validate_identifier(column)}"' for column in updates)
            conflict = ", ".join(f'"{validate_identifier(column)}"' for column in primary_key)
            sql = f'INSERT INTO "{table_name}" ({quoted_columns}) VALUES ({placeholders}) ON CONFLICT ({conflict}) DO UPDATE SET {assignments}'
        else:
            sql = f'INSERT OR IGNORE INTO "{table_name}" ({quoted_columns}) VALUES ({placeholders})'
        self.connection.execute(sql, [row[column] for column in columns])

    def delete(self, table: str, primary_key: tuple[str, ...], row: dict[str, Any]) -> None:
        predicates = " AND ".join(f'"{validate_identifier(column)}" = {self.placeholder}' for column in primary_key)
        self.connection.execute(f'DELETE FROM "{validate_identifier(table)}" WHERE {predicates}', [row[column] for column in primary_key])


class MariaDbStore:
    placeholder = "%s"

    def __init__(self, host: str, port: int, database: str, user: str, password: str, connect_timeout: int = 10):
        try:
            mariadb = import_module("mariadb")
        except ImportError as error:
            raise RuntimeError("Install the MariaDB driver with: python -m pip install mariadb") from error
        self.connection = mariadb.connect(
            host=host,
            port=port,
            database=database,
            user=user,
            password=password,
            connect_timeout=connect_timeout,
        )

    def close(self) -> None:
        self.connection.close()

    def begin(self) -> None:
        self.connection.begin()

    def commit(self) -> None:
        self.connection.commit()

    def rollback(self) -> None:
        self.connection.rollback()

    def tables(self) -> set[str]:
        cursor = self.connection.cursor()
        cursor.execute("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")
        return {row[0] for row in cursor.fetchall()}

    def schema(self, table: str) -> TableSchema:
        cursor = self.connection.cursor()
        cursor.execute(f'SHOW COLUMNS FROM `{validate_identifier(table)}`')
        rows = cursor.fetchall()
        columns = tuple(row[0] for row in rows)
        primary_key = tuple(row[0] for row in rows if row[3] == "PRI")
        return TableSchema(table, columns, primary_key)

    def snapshot(self, table: str, columns: tuple[str, ...], primary_key: tuple[str, ...], batch_size: int) -> dict[str, dict[str, Any]]:
        quoted = ", ".join(f'`{validate_identifier(column)}`' for column in columns)
        cursor = self.connection.cursor()
        cursor.execute(f'SELECT {quoted} FROM `{validate_identifier(table)}`')
        result: dict[str, dict[str, Any]] = {}
        while rows := cursor.fetchmany(batch_size):
            for values in rows:
                row = dict(zip(columns, values))
                result[key_for(row, primary_key)] = row
        return result

    def upsert(self, table: str, columns: tuple[str, ...], primary_key: tuple[str, ...], row: dict[str, Any]) -> None:
        table_name = validate_identifier(table)
        quoted_columns = ", ".join(f'`{validate_identifier(column)}`' for column in columns)
        placeholders = ", ".join(self.placeholder for _ in columns)
        updates = tuple(column for column in columns if column not in primary_key)
        if updates:
            assignments = ", ".join(f'`{validate_identifier(column)}` = VALUES(`{validate_identifier(column)}`)' for column in updates)
            sql = f'INSERT INTO `{table_name}` ({quoted_columns}) VALUES ({placeholders}) ON DUPLICATE KEY UPDATE {assignments}'
        else:
            sql = f'INSERT IGNORE INTO `{table_name}` ({quoted_columns}) VALUES ({placeholders})'
        cursor = self.connection.cursor()
        cursor.execute(sql, [row[column] for column in columns])

    def delete(self, table: str, primary_key: tuple[str, ...], row: dict[str, Any]) -> None:
        predicates = " AND ".join(f'`{validate_identifier(column)}` = {self.placeholder}' for column in primary_key)
        cursor = self.connection.cursor()
        cursor.execute(f'DELETE FROM `{validate_identifier(table)}` WHERE {predicates}', [row[column] for column in primary_key])
