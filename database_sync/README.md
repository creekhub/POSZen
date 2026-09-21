# POSZen SQLite/MariaDB Sync

This project reconciles the local SQLite database configured by POSZen with an online MariaDB database in both directions. It is designed for the Aronium database currently configured in the repository.

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

The script reads `.env` from the repository root for the local SQLite path:

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

The sync account needs SELECT, INSERT, UPDATE, and (only when using `--allow-deletes`) DELETE on the synchronized tables. It must not have permission to drop tables or alter schemas.

## Install and run

From the repository root:

```powershell
python -m pip install -e .\database_sync
python -m database_sync.cli --tables Document,DocumentItem,Payment,ZReport
python -m database_sync.cli --apply --confirm-target your_database
```

The first run should normally be a dry run. Use `--tables` to test a small dependency-ordered subset first. Run `python -m database_sync.cli --help` for polling and retry options.

For automatic operation, schedule the one-shot command every few minutes, or use `--poll 30` on a dedicated process. Do not run overlapping instances against the same state file.

SQLite and MariaDB do not support one distributed transaction. If a connection fails after one side commits, the state file is not advanced; the next run compares both databases again and safely retries idempotent changes. This gives eventual consistency and recovery, not a false guarantee of instantaneous atomicity.

## Limitations

The source and target schemas must expose compatible tables with primary keys. The tool discovers common tables but does not create or alter schemas. The current repository does not define POS tables in Laravel migrations, so the Aronium SQLite schema and the existing AWS MariaDB schema must be compatible. Timestamp clocks are not used for conflict decisions; the state baseline and complete row values are used instead.
