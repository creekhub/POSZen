# POSZen SQLite/MariaDB Tools

This directory contains two command-line tools:

- `export_mariadb_schema.py` creates MariaDB table definitions from a SQLite database.
- `database_sync.cli` reconciles rows between the local SQLite database and an existing MariaDB database.

The tools are designed for the Aronium database currently configured in the repository. The schema exporter creates structure only. The synchronizer does not create or alter tables.

## Safety model

- The default command is a dry run. Use `--apply` to write.
- `--apply` also requires `--confirm-target <database-name>`.
- New records are copied in either direction.
- If one side changed since the last successful sync, the changed row is copied to the other side.
- If both sides changed the same row, the row is reported as a conflict and neither value is overwritten.
- Deletes are reported but are not propagated unless `--allow-deletes` is explicitly supplied. A delete is never inferred from a missing row on the first run.
- The state file is written only after a successful apply with no conflicts. Keep it backed up with the sync host.

This avoids silent data loss. Resolve reported conflicts by choosing the authoritative row, applying that decision, and rerunning the sync. The tool does not guess how a sale, payment, or inventory update should be merged.

## Configuration

Both tools can be run from the repository root. The synchronizer reads `.env` from the repository root for the local SQLite path:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=C:/Users/User/AppData/Local/Aronium/Data/pos.db
```

Configure the online database with separate variables so local Laravel settings cannot accidentally point the sync at the wrong server:

```dotenv
SYNC_MARIADB_HOST=your-aws-mariadb-host
SYNC_MARIADB_PORT=3306
SYNC_MARIADB_DATABASE=your_database
SYNC_MARIADB_USER=sync_user
SYNC_MARIADB_PASSWORD=do-not-commit-this
SYNC_STATE_FILE=database_sync/state.json
```

`SYNC_SQLITE_PATH` can be used instead of `DB_DATABASE` when the synchronization source should differ from the Laravel application database. Environment variables set in PowerShell take precedence over values in `.env`.

Do not commit `.env`, database passwords, generated state files, or connection URLs containing credentials.

## Export MariaDB schema

Generate the schema-only MariaDB DDL from the configured SQLite database:

```powershell
python .\database_sync\export_mariadb_schema.py "C:\path\to\pos.db" .\database_sync\schema_mariadb.sql
```

The generated file creates tables, primary keys, unique indexes, and foreign keys. It does not copy application data or drop existing tables. Review the type mappings and test against a disposable MariaDB database before applying it to a live target.

The exporter arguments are positional:

```text
python .\database_sync\export_mariadb_schema.py <sqlite-source> <sql-output>
```

Example using the configured Aronium path:

```powershell
python .\database_sync\export_mariadb_schema.py `
"C:\Users\User\AppData\Local\Aronium\Data\pos.db" `
 .\database_sync\schema_mariadb.sql
```

Review the generated type conversions before applying them. SQLite has flexible typing, so numeric values are mapped to `DECIMAL(20, 6)` or `DOUBLE`, text values to `LONGTEXT`, and integer flags such as `INTEGER (1)` to `TINYINT`.

Apply the script only after taking a target backup and confirming the target database:

```powershell
mariadb --host $env:SYNC_MARIADB_HOST `
--port $env:SYNC_MARIADB_PORT `
--user $env:SYNC_MARIADB_USER `
--password `
$env:SYNC_MARIADB_DATABASE < .\database_sync\schema_mariadb.sql
```

The command prompts for the password. Do not put the password directly in the command or in the SQL file.

The sync account needs SELECT, INSERT, UPDATE, and (only when using `--allow-deletes`) DELETE on the synchronized tables. It must not have permission to drop tables or alter schemas.

## Install the tools

From the repository root:

```powershell
python -m pip install -e .\database_sync
```

This installs the MariaDB Python driver declared in `database_sync/pyproject.toml`. Verify the available commands with:

```powershell
poszen-db-sync --help
python .\database_sync\export_mariadb_schema.py --help
```

## Run a synchronization

The synchronizer requires the SQLite file and these MariaDB settings:

```dotenv
SYNC_MARIADB_HOST=your-mariadb-host
SYNC_MARIADB_PORT=3306
SYNC_MARIADB_DATABASE=your_database
SYNC_MARIADB_USER=sync_user
SYNC_MARIADB_PASSWORD=do-not-commit-this
```

Run a dry run first. A dry run reads both databases, reports planned changes and conflicts, and performs no writes:

```powershell
poszen-db-sync --tables Document,DocumentItem,Payment,ZReport
```

If the dry run is correct, apply the changes explicitly. The confirmation value must exactly match `SYNC_MARIADB_DATABASE`:

```powershell
poszen-db-sync `
--tables Document,DocumentItem,Payment,ZReport `
--apply `
--confirm-target $env:SYNC_MARIADB_DATABASE
```

For all compatible non-system tables, omit `--tables`:

```powershell
poszen-db-sync --apply --confirm-target $env:SYNC_MARIADB_DATABASE
```

Useful options:

| Option | Purpose |
| --- | --- |
| `--tables A,B` | Restrict the run to named tables. |
| `--batch-size 500` | Limit rows read per database cursor batch. |
| `--state-file path` | Use a specific synchronization baseline file. |
| `--connect-timeout 10` | Set the MariaDB connection timeout in seconds. |
| `--log-level DEBUG` | Increase diagnostic logging. |
| `--poll 30` | Repeat failed runs after 30 seconds. |
| `--allow-deletes` | Propagate detected deletes. Use only with explicit approval. |

The synchronizer matches table names, columns, and primary keys case-insensitively. It preserves each database's physical spelling when reading and writing, so names such as `Document` and `document` can synchronize. It excludes Laravel framework tables such as `users`, `sessions`, `jobs`, and `migrations`.

## Understand the result

Exit codes are useful in scheduled jobs:

- `0`: dry run or apply completed successfully.
- `1`: configuration, connectivity, or runtime failure.
- `2`: requested tables are incompatible or conflicts were found.

Conflict messages identify the table, key, and conflict kind. No writes are performed while conflicts remain. Resolve the conflicting row, then rerun the dry run.

The state file stores the last successful row baseline. It is written only after an apply succeeds. Back it up with the host running the synchronizer, and do not share it if row contents are considered sensitive.

## Scheduled or polling operation

For a scheduler, run the one-shot command at a controlled interval. Do not run overlapping processes against the same state file:

```powershell
poszen-db-sync --apply --confirm-target $env:SYNC_MARIADB_DATABASE
```

For a dedicated process that retries failures, use polling:

```powershell
poszen-db-sync --poll 30
```

Polling is not a real-time guarantee. It retries failed runs and does not replace backups, monitoring, or a process supervisor.

For automatic operation, schedule the one-shot command every few minutes, or use `--poll 30` on a dedicated process. Do not run overlapping instances against the same state file.

SQLite and MariaDB do not support one distributed transaction. If a connection fails after one side commits, the state file is not advanced; the next run compares both databases again and safely retries idempotent changes. This gives eventual consistency and recovery, not a false guarantee of instantaneous atomicity.

## Limitations

The source and target schemas must expose compatible tables with primary keys. The tool discovers common tables but does not create or alter schemas. The current repository does not define POS tables in Laravel migrations, so the Aronium SQLite schema and the existing AWS MariaDB schema must be compatible. Timestamp clocks are not used for conflict decisions; the state baseline and complete row values are used instead.
