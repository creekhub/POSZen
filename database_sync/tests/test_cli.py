import unittest

from database_sync.cli import _compatible_tables, _rename_row
from database_sync.db import TableSchema


class FakeStore:
    def __init__(self, table_name: str, schema: TableSchema):
        self.table_name = table_name
        self.table_schema = schema

    def tables(self) -> set[str]:
        return {self.table_name}

    def schema(self, table: str) -> TableSchema:
        self.assert_table(table)
        return self.table_schema

    def assert_table(self, table: str) -> None:
        if table != self.table_name:
            raise AssertionError(f"expected {self.table_name}, got {table}")


class CompatibleTablesTests(unittest.TestCase):
    def test_matching_ignores_case_for_tables_columns_and_primary_keys(self):
        local = FakeStore(
            "Document",
            TableSchema("Document", ("Id", "Total"), ("Id",)),
        )
        remote = FakeStore(
            "document",
            TableSchema("document", ("id", "total"), ("id",)),
        )

        plans, problems = _compatible_tables(local, remote, {"DOCUMENT"})

        self.assertEqual(problems, [])
        self.assertEqual(len(plans), 1)
        self.assertEqual(plans[0].local_table, "Document")
        self.assertEqual(plans[0].remote_table, "document")
        self.assertEqual(plans[0].local_columns, ("Id", "Total"))
        self.assertEqual(plans[0].remote_columns, ("id", "total"))

    def test_excluded_tables_remain_excluded_case_insensitively(self):
        local = FakeStore("Users", TableSchema("Users", ("Id",), ("Id",)))
        remote = FakeStore("users", TableSchema("users", ("id",), ("id",)))

        plans, problems = _compatible_tables(local, remote, None)

        self.assertEqual(plans, [])
        self.assertEqual(problems, [])

    def test_rows_can_be_renamed_to_the_remote_physical_columns(self):
        row = _rename_row({"Id": 7, "Total": 12.5}, ("Id", "Total"), ("id", "total"))

        self.assertEqual(row, {"id": 7, "total": 12.5})


if __name__ == "__main__":
    unittest.main()
