"""Strict verification: replay lufly-database.sql and require the result to be
identical to database/lufly.sqlite - every row, every column, every byte.

    python3 tools/audit/verify_exact.py      # exit 0 == identical

No tolerance list: JSON columns, uuids and timestamps are compared too.
"""

import os
import sqlite3
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from replay_mysql_dump import build_scratch  # noqa: E402

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
LIVE = os.path.join(ROOT, 'database/lufly.sqlite')


def main():
    live, scratch, tables, applied, errors = build_scratch()
    print('statements replayed:', dict(applied), '| SQL errors:', len(errors))
    for e in errors:
        print('  ERROR', e[0], '->', e[1])

    problems = []
    for t in tables:
        lcols = [r[1] for r in live.execute(f'PRAGMA table_info(`{t}`)')]
        dcols = [r[1] for r in scratch.execute(f'PRAGMA table_info(`{t}`)')]
        if lcols != dcols:
            problems.append(f'{t}: columns differ')
            continue
        lrows = {r[0]: tuple(r) for r in live.execute(f'SELECT * FROM `{t}`')}
        drows = {r[0]: tuple(r) for r in scratch.execute(f'SELECT * FROM `{t}`')}
        if set(lrows) != set(drows):
            problems.append(f'{t}: id sets differ '
                            f'(only live {sorted(set(lrows) - set(drows))[:5]}, '
                            f'only dump {sorted(set(drows) - set(lrows))[:5]})')
            continue
        for i in lrows:
            if lrows[i] != drows[i]:
                for c, a, b in zip(lcols, lrows[i], drows[i]):
                    if a != b:
                        problems.append(f'{t} id={i} column {c}: sqlite={a!r} dump={b!r}')
                break

    print()
    if problems:
        print(f'NOT identical - {len(problems)} problem(s):')
        for p in problems[:40]:
            print('  X', p)
        return 1
    print(f'IDENTICAL: all {len(tables)} tables, every row and column, match database/lufly.sqlite')
    return 0


if __name__ == '__main__':
    sys.exit(main())
