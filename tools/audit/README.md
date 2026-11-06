# lufly-database.sql — built from, and checked against, the local database

The dump is now **the local data itself**: `database/lufly.sqlite`, table for table,
row for row, column for column — including the JSON columns exactly as sqlite stores
them (the doubly-escaped `media.meta` values included). Nothing is "cleaned up" and
there are no patch blocks at the end of the file.

## Scripts

| script | what it does |
|---|---|
| `rebuild_dump_from_sqlite.py` | rewrites the `-- table:` / `-- data:` part of `lufly-database.sql` from `database/lufly.sqlite` (one `INSERT` per row, file order = id order), keeping the header and the indexes/AUTO_INCREMENT/FK block |
| `verify_exact.py` | replays the dump into an empty schema and fails unless the result is **byte-identical** to the sqlite file, all 50 tables |
| `replay_mysql_dump.py` | the original, more chatty audit: replay + report, plus a check for DDL/import traps |
| `REPORT-ar.md` | the written audit (Arabic) |

```bash
python3 tools/audit/rebuild_dump_from_sqlite.py   # regenerate the data part
python3 tools/audit/verify_exact.py               # exit 0 == identical to sqlite
python3 tools/audit/replay_mysql_dump.py          # replay report + trap scan
```

## What stays "fixed" in the DDL

The data is untouched, but three things in the schema would break a phpMyAdmin
import, so they are not the raw sqlite form anymore:

1. `AUTO_INCREMENT` = `max(id)+1` for every table (they used to point below the
   highest id, so the first admin insert would hit `Duplicate entry`).
2. No `CHECK (json_valid(...))` on the JSON columns — an invalid-JSON row would
   abort the import. The local data does contain such rows (18 `media.meta`
   values), and the application reads them fine (`json_decode(...) ?? []`).
3. No per-column `CHARACTER SET ... COLLATE utf8mb4_bin` — that collation is not
   available on every MySQL 8 / MariaDB build and aborts the import.

If you ever want the dump to be literal sqlite DDL as well, say so — the data part
already is.
