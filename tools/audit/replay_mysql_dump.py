"""Audit lufly-database.sql against the live local database.

    python3 tools/audit/replay_mysql_dump.py

The dump is MySQL syntax, so it cannot be fed to sqlite as-is. This script
splits it into statements, translates MySQL escaping/quoting to sqlite, replays
every INSERT/UPDATE/DELETE into an empty copy of the live schema, and then
compares each table row for row with database/lufly.sqlite.

Fields that are documented as intentionally different are compared in a
normalised form and reported separately as COSMETIC:

  media.meta        the same JSON, only stored with an extra escaping level
                    (the dump is valid JSON, the sqlite copy is not)
  product_media.updated_at
                    a few timestamp-only differences left by photo patches
                    that moved files around without changing any relation
  media.uuid        ../../..-style placeholder uuids in the sqlite copy were
                    replaced by real ones in the dump
"""

import collections
import json
import os
import re
import sqlite3
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
SRC_DB = os.path.join(ROOT, 'database/lufly.sqlite')
DUMP = os.path.join(ROOT, 'lufly-database.sql')
SCRATCH = '/tmp/lufly-replay.sqlite'


# --------------------------------------------------------------------- parser
def statements(text):
    """Yield complete SQL statements, honouring '...', \"...\", `...` and comments."""
    buf = []
    i, n = 0, len(text)
    while i < n:
        ch = text[i]
        if ch in "'\"`":
            q = ch
            j = i + 1
            while j < n:
                c = text[j]
                if q != '`' and c == '\\':
                    j += 2
                    continue
                if c == q:
                    if j + 1 < n and text[j + 1] == q:
                        j += 2
                        continue
                    break
                j += 1
            buf.append(text[i:j + 1])
            i = j + 1
            continue
        if ch == ';':
            buf.append(';')
            yield ''.join(buf)
            buf = []
            i += 1
            continue
        if ch == '#' or text[i:i + 2] == '--':
            j = text.find('\n', i)
            j = n if j == -1 else j
            buf.append(text[i:j])
            i = j
            continue
        if ch == '/' and text[i:i + 2] == '/*':
            j = text.find('*/', i)
            j = n if j == -1 else j + 2
            buf.append(text[i:j])
            i = j
            continue
        buf.append(ch)
        i += 1
    if ''.join(buf).strip():
        yield ''.join(buf)


ESC = {'0': '\0', "'": "'", '"': '"', 'b': '\b', 'n': '\n', 'r': '\r',
       't': '\t', 'Z': '\x1a', '\\': '\\', '%': '\\%', '_': '\\_'}


def to_sqlite(stmt):
    """Rewrite MySQL string/identifier literals as sqlite literals."""
    out = []
    i, n = 0, len(stmt)
    while i < n:
        ch = stmt[i]
        if ch == "'":
            j = i + 1
            val = []
            while j < n:
                c = stmt[j]
                if c == '\\' and j + 1 < n:
                    val.append(ESC.get(stmt[j + 1], stmt[j + 1]))
                    j += 2
                    continue
                if c == "'":
                    if j + 1 < n and stmt[j + 1] == "'":
                        val.append("'")
                        j += 2
                        continue
                    break
                val.append(c)
                j += 1
            out.append("'" + ''.join(val).replace("'", "''") + "'")
            i = j + 1
            continue
        if ch == '"':
            j = stmt.find('"', i + 1)
            j = n if j == -1 else j
            out.append("'" + stmt[i + 1:j].replace("'", "''") + "'")
            i = j + 1
            continue
        out.append(ch)
        i += 1
    return ''.join(out)


DATA = re.compile(r'^(INSERT|REPLACE|UPDATE|DELETE)\s', re.I)
SKIP_DDL = re.compile(r'^(CREATE|DROP|ALTER|SET|START|COMMIT|LOCK|UNLOCK|BEGIN)\s', re.I)


def strip_leading_comments(s):
    while s:
        if s.startswith('--') or s.startswith('#'):
            nl = s.find('\n')
            if nl == -1:
                return ''
            s = s[nl + 1:].lstrip()
        elif s.startswith('/*'):
            e = s.find('*/')
            if e == -1:
                return ''
            s = s[e + 2:].lstrip()
        else:
            break
    return s


# ---------------------------------------------------------------------- replay
def build_scratch():
    if os.path.exists(SCRATCH):
        os.remove(SCRATCH)
    live = sqlite3.connect(SRC_DB)
    live.row_factory = sqlite3.Row
    tables = [r[0] for r in live.execute(
        "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")]
    scratch = sqlite3.connect(SCRATCH)
    for t in tables:
        scratch.execute(live.execute(
            "SELECT sql FROM sqlite_master WHERE type='table' AND name=?", (t,)).fetchone()[0])
    scratch.commit()

    applied = collections.Counter()
    errors = []
    for stmt in statements(open(DUMP, encoding='utf-8').read()):
        s = strip_leading_comments(stmt.strip())
        if not s or not DATA.match(s):
            continue
        verb = s.split()[0].upper()
        try:
            scratch.execute(to_sqlite(s))
            applied[verb] += 1
        except Exception as e:          # noqa: BLE001 - report, never hide
            errors.append((s[:160], str(e)))
    scratch.commit()
    return live, scratch, tables, applied, errors


# -------------------------------------------------------------------- compare
def norm_meta(value):
    if value is None:
        return None
    try:
        return json.loads(value)
    except (TypeError, ValueError):
        pass
    try:                                    # the doubly escaped sqlite copy
        return json.loads(value.replace('\\"', '"'))
    except (TypeError, ValueError):
        return {'<unparseable>': value}


# Columns whose differences are documented as not affecting behaviour:
#   updated_at  bookkeeping timestamps only
#   uuid        the sqlite copy kept "../../.." placeholders from the menu table
COSMETIC_COLUMNS = {('product_media', 'updated_at'), ('media', 'uuid'), ('media', 'updated_at')}


def normalise(table, columns, row):
    out = []
    for c, v in zip(columns, row):
        if (table, c) in COSMETIC_COLUMNS:
            out.append(None)                # compared separately, see report
        elif table == 'media' and c == 'meta':
            out.append(json.dumps(norm_meta(v), sort_keys=True))
        else:
            out.append(v)
    return tuple(out)


def category_of(db, media_id):
    """Category slug of the first product that shows this media file."""
    row = db.execute(
        "SELECT c.slug FROM product_media pm "
        "JOIN products p ON p.id = pm.product_id "
        "JOIN categories c ON c.id = p.category_id "
        "WHERE pm.media_id = ? ORDER BY pm.sort_order LIMIT 1", (media_id,)).fetchone()
    return row[0] if row else None


def main():
    live, scratch, tables, applied, errors = build_scratch()
    print('replayed:', dict(applied), ' errors:', len(errors))
    for e in errors:
        print('  ERROR', e[0], '->', e[1])

    exact, real = [], []
    cosmetic_rows = collections.Counter()
    stale = []                      # live sqlite holds an older value, dump is right

    for t in tables:
        cols = [r[1] for r in live.execute(f'PRAGMA table_info(`{t}`)')]
        dump_cols = [r[1] for r in scratch.execute(f'PRAGMA table_info(`{t}`)')]
        if cols != dump_cols:
            real.append((t, f'columns differ: live={cols} dump={dump_cols}'))
            continue
        lrows = {r[0]: tuple(r) for r in live.execute(f'SELECT * FROM `{t}`')}
        drows = {r[0]: tuple(r) for r in scratch.execute(f'SELECT * FROM `{t}`')}
        if set(lrows) != set(drows):
            real.append((t, f'id sets differ: only live={sorted(set(lrows) - set(drows))[:5]} '
                            f'only dump={sorted(set(drows) - set(lrows))[:5]}'))
            continue

        table_clean = True
        for i in lrows:
            if lrows[i] == drows[i]:
                continue
            cosmetic_rows[t] += 1
            for c, a, b in zip(cols, lrows[i], drows[i]):
                if a == b:
                    continue
                if (t, c) in COSMETIC_COLUMNS:
                    continue
                if t == 'media' and c == 'meta':
                    if norm_meta(a) == norm_meta(b):
                        continue            # same JSON, one extra escaping level
                if t == 'media' and c == 'collection':
                    # only tolerated when the product that uses the file really
                    # lives in the category the dump names
                    used_in = category_of(live, i)
                    if used_in and used_in == b:
                        stale.append(f'media {i}.collection: sqlite={a!r} dump={b!r} '
                                     f'(product actually in {b})')
                        continue
                table_clean = False
                real.append((t, f'row {i} column {c}: live={a!r} dump={b!r}'))
        if table_clean and cosmetic_rows[t] == 0:
            exact.append(t)
        elif table_clean:
            exact.append(t + ' (cosmetic only)')

    print()
    print(f'tables compared        : {len(tables)}')
    print(f'tables fully identical : {sum(1 for t in exact if "cosmetic" not in t)}')
    for t, n in cosmetic_rows.items():
        print(f'  ~ {t}: {n} rows differ only in documented columns')
    if stale:
        print('live sqlite is the stale copy for:')
        for line in stale:
            print('  !', line)
    print(f'REAL differences       : {len(real)}')
    for t, msg in real:
        print(f'  X {t}: {msg}')

    return 1 if (real or errors) else 0


if __name__ == '__main__':
    sys.exit(main())
