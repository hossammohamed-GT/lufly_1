"""Append a block of SQL to lufly-database.sql without ever truncating it.

The file is read completely before anything is written, and the write is
atomic, so a failed or mis-ordered write can never leave an empty file.
"""
import os
import tempfile

TAIL = "-- End of lufly-database.sql\n"
PATH = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "lufly-database.sql")


def append_patch(block, path=PATH):
    with open(path, encoding="utf-8") as fh:
        sql = fh.read()
    if not sql.rstrip().endswith("-- End of lufly-database.sql"):
        raise RuntimeError("lufly-database.sql has no end marker; refusing to patch")
    if not block.endswith("\n"):
        block += "\n"
    sql = sql.replace(TAIL, block + TAIL, 1)
    fd, tmp = tempfile.mkstemp(dir=os.path.dirname(path))
    with os.fdopen(fd, "w", encoding="utf-8") as fh:
        fh.write(sql)
    os.replace(tmp, path)
    return len(sql)
