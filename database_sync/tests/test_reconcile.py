import unittest

from database_sync.reconcile import reconcile_table, row_hash


class ReconcileTests(unittest.TestCase):
    def test_new_rows_are_copied_in_both_directions(self):
        actions, conflicts = reconcile_table(
            {"local": {"1": {"id": 1, "total": 10}}, "remote": {"2": {"id": 2, "total": 20}}},
            {},
        )

        self.assertEqual(conflicts, [])
        self.assertEqual({(action.direction, action.key) for action in actions}, {
            ("to_remote", "1"),
            ("to_local", "2"),
        })


    def test_one_sided_change_is_propagated(self):
        actions, conflicts = reconcile_table(
            {"local": {"1": {"id": 1, "total": 15}}, "remote": {"1": {"id": 1, "total": 10}}},
            {"1": row_hash({"id": 1, "total": 10})},
        )

        self.assertEqual(conflicts, [])
        self.assertEqual(len(actions), 1)
        self.assertEqual(actions[0].direction, "to_remote")


    def test_two_sided_change_is_a_conflict(self):
        actions, conflicts = reconcile_table(
            {"local": {"1": {"id": 1, "total": 15}}, "remote": {"1": {"id": 1, "total": 12}}},
            {"1": "old"},
        )

        self.assertEqual(actions, [])
        self.assertEqual(len(conflicts), 1)
        self.assertEqual(conflicts[0].key, "1")


    def test_delete_is_not_propagated_by_default(self):
        actions, conflicts = reconcile_table(
            {"local": {}, "remote": {"1": {"id": 1, "total": 10}}},
            {"1": row_hash({"id": 1, "total": 10})},
        )

        self.assertEqual(actions, [])
        self.assertEqual(len(conflicts), 1)
        self.assertEqual(conflicts[0].kind, "delete_requires_approval")


if __name__ == "__main__":
    unittest.main()
