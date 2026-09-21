---
name: sqlite-mariadb-sync
description: "Create, review, or troubleshoot a Python synchronization script that copies a SQLite database into an online MariaDB database. Use for SQLite-to-MariaDB migration, POS data replication, schema mapping, incremental sync, dry runs, validation, and production-safe database transfers."
argument-hint: "Describe the SQLite source, MariaDB target, tables to sync, and whether this is a full or incremental sync."
user-invocable: true
---

# SQLite to MariaDB Synchronization

Create a maintainable Python utility that synchronizes data from the local SQLite database to an online MariaDB database. Treat the transfer as a controlled data migration: inspect first, make mappings explicit, run a dry run, write transactionally, and verify the result.

## When to Use

Use this skill when the user needs to:

- create or update a Python SQLite-to-MariaDB synchronization script;
- migrate POS users, products, inventory, sales, payments, or related tables;
- add full or incremental synchronization;
- review a sync script for correctness, safety, idempotency, or data loss risks;
- diagnose schema, type, connection, duplicate-key, or foreign-key failures during synchronization.

## Required Inputs

Before writing code, identify these inputs from the repository or ask for only the values that cannot be determined safely:

- SQLite database path and MariaDB connection settings;
- source and target table names, including any schema differences;
- tables included in the sync and their dependency order;
- primary keys and conflict/upsert policy;
- whether the run is a full replacement, append, upsert, or incremental sync;
- the incremental watermark, if applicable, such as `updated_at` or a durable change-log table;
- expected handling for deletes, soft deletes, nulls, malformed rows, and missing foreign keys;
- batch size, timeout, retry, and backup requirements.

Never invent column mappings, credentials, delete behavior, or conflict rules. If a decision affects data loss or production writes, stop and ask for it.

## Procedure

### 1. Inspect the local system

1. Read the repository instructions and existing database configuration.
2. Confirm the Python interpreter and available packages.
3. Locate the SQLite database without printing secrets.
4. Inspect SQLite tables, columns, primary keys, indexes, foreign keys, row counts, and timestamp ranges using SQLite metadata queries.
5. Inspect Laravel migrations and models when they clarify business relationships or target naming.
6. Determine whether MariaDB uses compatible schemas or requires explicit transformations.

Use parameterized SQL for values. Never build SQL from untrusted table or column names without validating identifiers against inspected metadata.

### 2. Design the sync contract

Write down the sync plan before implementation:

- source table to target table mappings;
- column transformations and type conversions;
- parent-before-child dependency order;
- stable conflict keys and upsert behavior;
- treatment of records removed from SQLite;
- checkpoint or watermark semantics;
- validation queries and acceptance thresholds.

Prefer a configuration-driven mapping in the script or a checked-in config file over scattered table-specific conditionals. Keep credentials in environment variables or the existing local secret mechanism; never hard-code them or commit `.env` contents.

### 3. Implement the Python script

Create the script in the repository's established scripts location. If none exists, use `scripts/sync_sqlite_to_mariadb.py`.

The script should:

- expose a clear CLI with source path, target settings, table selection, batch size, `--dry-run`, and logging options;
- default to a dry run or require an explicit `--apply` flag for writes;
- use context managers for both database connections and cursors;
- use a MariaDB-compatible Python driver already present in the project, or document the minimal dependency if one must be added;
- use parameterized statements for row values and validated identifiers for dynamic SQL;
- read SQLite rows in bounded batches rather than loading an entire table into memory;
- preserve source primary keys when the target depends on them, unless the mapping explicitly says otherwise;
- perform parent tables before dependent tables and honor foreign-key integrity;
- use idempotent inserts or updates with a documented unique/conflict key;
- commit each controlled batch or transaction unit and roll back failed units;
- make retries bounded and avoid duplicating rows after a retry;
- record per-table counts for read, inserted, updated, skipped, failed, and deleted rows;
- avoid logging passwords, connection URLs containing passwords, or sensitive row contents;
- return a nonzero exit code when validation or a required table fails.

For incremental sync, persist and advance the checkpoint only after the corresponding target transaction succeeds. Use a deterministic tie-breaker when timestamps are not unique, such as `(updated_at, primary_key)`. Do not claim that an `updated_at` filter captures deletes unless deletion tracking exists.

### 4. Make production safety explicit

Before enabling writes:

1. Confirm the target host, database, and account from environment variables.
2. Run a dry run and show the planned tables, mappings, row counts, and estimated changes.
3. Require an explicit apply action for live writes.
4. Recommend a target backup or snapshot and least-privilege credentials.
5. Use a target transaction strategy that limits lock duration and makes partial failure recoverable.
6. Make destructive operations, including target truncation or delete propagation, opt-in and separately guarded.
7. Ensure rerunning the same source produces the same target state under the chosen sync contract.

Never test against production with guessed credentials. Never include credentials in command output, examples, fixtures, or committed files.

### 5. Validate the result

Add focused tests or a validation command for:

- schema and mapping compatibility;
- type conversions, null handling, and timestamps;
- duplicate or repeated runs being idempotent;
- parent/child ordering and foreign-key failures;
- rollback when a batch fails;
- incremental checkpoint behavior at equal timestamps;
- dry-run mode making no target changes;
- counts and checksums or key-set comparisons for synchronized tables.

After a dry run, validate against a disposable MariaDB database or a small explicitly approved subset. Compare source and target counts, primary-key sets, representative records, and relevant aggregate totals. Report any skipped, failed, orphaned, or ambiguous rows.

Run the repository's focused Python tests and static checks, then run the Laravel test suite if the synchronized data feeds application behavior. Do not call the work complete if only the script parses; the data checks must pass too.

## Deliverables

Provide:

- the Python synchronization script;
- a short configuration or environment-variable reference;
- explicit table mappings and assumptions;
- dependency installation instructions if needed;
- focused tests or reproducible validation commands;
- a concise runbook covering dry run, apply, retry, rollback, and recovery from a failed checkpoint.

## Completion Checklist

Before finishing, confirm:

- [ ] The source and target schemas were inspected rather than assumed.
- [ ] Every synchronized table has an explicit mapping and dependency position.
- [ ] Writes require an explicit opt-in and dry run is available.
- [ ] Values use parameterized SQL and secrets stay outside source control.
- [ ] Batching, transactions, rollback, retries, and logging are implemented.
- [ ] Full versus incremental semantics and delete handling are documented.
- [ ] Repeated runs are idempotent under the chosen conflict policy.
- [ ] Counts, key sets, and representative data were verified.
- [ ] Focused tests or checks were run and their results reported.
