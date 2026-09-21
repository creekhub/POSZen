from __future__ import annotations

import argparse
from dataclasses import dataclass
import re
import sqlite3
from pathlib import Path


IDENTIFIER = re.compile(r"^[A-Za-z_][A-Za-z0-9_]*$")


@dataclass(frozen=True)
class ForeignKey:
    column: str
    target_table: str
    target_column: str
    on_delete: str
    on_update: str


def quote_identifier(value: str) -> str:
    if not IDENTIFIER.fullmatch(value):
        raise ValueError(f"Unsupported SQLite identifier: {value!r}")
    return f"`{value}`"


def quote_literal(value: str) -> str:
    return "'" + value.replace("'", "''") + "'"


def normalize_type(declared_type: str, primary_key: bool = False) -> str:
    value = declared_type.upper().strip()
    if primary_key and value == "INTEGER":
        return "BIGINT"
    if value.startswith("INTEGER") or value.startswith("INT"):
        if "(1)" in value:
            return "TINYINT"
        return "BIGINT"
    if value.startswith("NUMERIC"):
        return "DECIMAL(20, 6)"
    if value.startswith("REAL") or value.startswith("DOUBLE") or value.startswith("FLOAT"):
        return "DOUBLE"
    if value.startswith("BLOB"):
        return "LONGBLOB"
    if value.startswith("DATETIME") or value.startswith("TIMESTAMP"):
        return "DATETIME"
    if value.startswith("DATE"):
        return "DATE"
    if value.startswith("TEXT") or not value:
        return "LONGTEXT"
    return value


def normalize_default(default: str | None, column_type: str) -> str | None:
    if default is None:
        return None
    value = default.strip()
    if "DATETIME" in value.upper() and "NOW" in value.upper():
        return "CURRENT_TIMESTAMP"
    if column_type == "DATETIME" and value.upper() == "CURRENT_TIMESTAMP":
        return value.upper()
    return value


def indexed_columns(connection: sqlite3.Connection, table: str) -> set[str]:
    columns: set[str] = set()
    for index in connection.execute(f"PRAGMA index_list({quote_literal(table)})"):
        index_name = index[1]
        for index_column in connection.execute(f"PRAGMA index_info({quote_literal(index_name)})"):
            if index_column[2] is not None:
                columns.add(index_column[2])
    return columns


def render_schema(source: Path) -> str:
    connection = sqlite3.connect(source)
    connection.row_factory = sqlite3.Row
    try:
        tables = [
            row[0]
            for row in connection.execute(
                "SELECT name FROM sqlite_master "
                "WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
            )
        ]
        output = [
            "-- Generated from SQLite: " + str(source),
            "-- Schema only: no application data is included.",
            "-- Review type mappings and target naming before applying to production.",
            "",
            "SET FOREIGN_KEY_CHECKS = 0;",
            "",
        ]
        foreign_keys: list[tuple[str, ForeignKey]] = []

        for table in tables:
            columns = connection.execute(f"PRAGMA table_info({quote_literal(table)})").fetchall()
            indexed = indexed_columns(connection, table)
            definitions: list[str] = []
            primary_key: list[str] = []
            for column in columns:
                name = column[1]
                is_primary_key = bool(column[5])
                data_type = normalize_type(column[2], is_primary_key)
                if data_type == "LONGTEXT" and name in indexed:
                    data_type = "VARCHAR(255)"
                definition = f"  {quote_identifier(name)} {data_type}"
                if column[3]:
                    definition += " NOT NULL"
                default = normalize_default(column[4], data_type)
                if default is not None:
                    definition += f" DEFAULT {default}"
                definitions.append(definition)
                if is_primary_key:
                    primary_key.append(name)

            if primary_key:
                definitions.append(
                    "  PRIMARY KEY (" + ", ".join(quote_identifier(name) for name in primary_key) + ")"
                )

            for index in connection.execute(f"PRAGMA index_list({quote_literal(table)})"):
                index_name, unique, origin = index[1], index[2], index[3]
                if not unique or origin == "pk":
                    continue
                index_columns = [row[2] for row in connection.execute(f"PRAGMA index_info({quote_literal(index_name)})")]
                if index_columns:
                    definitions.append(
                        "  UNIQUE KEY "
                        + quote_identifier(index_name.removeprefix("sqlite_autoindex_"))
                        + " ("
                        + ", ".join(quote_identifier(name) for name in index_columns)
                        + ")"
                    )

            output.extend(
                [
                    f"CREATE TABLE IF NOT EXISTS {quote_identifier(table)} (",
                    ",\n".join(definitions),
                    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
                    "",
                ]
            )

            for foreign_key in connection.execute(f"PRAGMA foreign_key_list({quote_literal(table)})"):
                foreign_keys.append(
                    (
                        table,
                        ForeignKey(
                            column=foreign_key[3],
                            target_table=foreign_key[2],
                            target_column=foreign_key[4],
                            on_delete=foreign_key[6],
                            on_update=foreign_key[5],
                        ),
                    )
                )

        output.append("-- Foreign keys are added after all tables so creation order does not matter.")
        for sequence, (table, foreign_key) in enumerate(foreign_keys, start=1):
            constraint_name = f"fk_{table}_{sequence}_{foreign_key.column}_{foreign_key.target_table}_{foreign_key.target_column}"
            output.extend(
                [
                    f"ALTER TABLE {quote_identifier(table)} ADD CONSTRAINT {quote_identifier(constraint_name)}",
                    f"  FOREIGN KEY ({quote_identifier(foreign_key.column)})",
                    f"  REFERENCES {quote_identifier(foreign_key.target_table)} ({quote_identifier(foreign_key.target_column)})",
                    f"  ON DELETE {foreign_key.on_delete} ON UPDATE {foreign_key.on_update};",
                    "",
                ]
            )
        output.extend(["SET FOREIGN_KEY_CHECKS = 1;", ""])
        return "\n".join(output)
    finally:
        connection.close()


def main() -> int:
    parser = argparse.ArgumentParser(description="Export SQLite table structure as MariaDB DDL")
    parser.add_argument("source", type=Path, help="Path to the SQLite database")
    parser.add_argument("output", type=Path, help="Output .sql file")
    args = parser.parse_args()
    if not args.source.is_file():
        parser.error(f"SQLite database does not exist: {args.source}")
    args.output.write_text(render_schema(args.source) + "\n", encoding="utf-8")
    print(f"Wrote MariaDB schema to {args.output}.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
