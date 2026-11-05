#!/usr/bin/env python3
"""Keep the `media` rows in step with the files on disk.

Every finished photo run ends here: the pictures are replaced on disk and the
three stores - the SQLite runtime file and both server dumps - have to record
the same width, height and byte size, otherwise the storefront reserves the
wrong box for the image and the PDP lightbox opens at the wrong ratio.

Run from the repository root, after the files are in place, naming only the
photos this run replaced:

    python3 tools/lufly_media_sync.py /images/products/catalog/7850dc1a.webp ...

Nothing else is touched: the rows for every other photo keep exactly the
numbers they already had, and only the named tuples are rewritten in the dumps
(one line per row in the compact dump, one tuple per line in the pretty one).
Re-running changes nothing once the numbers agree.
"""

from __future__ import annotations

import re
import sqlite3
import sys
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
DB = ROOT / "database" / "lufly.sqlite"
DUMPS = [ROOT / "lufly-database.sql", ROOT / "lufly_db_new.sql"]
PUBLIC = ROOT / "public"

NOW = "2026-11-05 14:00:00"

MEDIA_COLUMNS = ["id", "uuid", "collection", "filename", "original_name", "path", "mime_type", "extension",
                 "size", "width", "height", "meta", "owner_id", "status", "created_at", "updated_at",
                 "deleted_at"]


def literal(value: object) -> str:
    if value is None:
        return "NULL"
    if isinstance(value, (int, float)):
        return str(value)
    text = str(value).replace("\\", "\\\\").replace("'", "\\'").replace("\n", "\\n")
    return f"'{text}'"


def file_facts(path: str) -> tuple[int, int, int] | None:
    """(byte size, width, height) of a stored file, or None when it is gone."""
    full = PUBLIC / path.lstrip("/")
    if not full.is_file():
        return None
    with Image.open(full) as image:
        return full.stat().st_size, image.width, image.height


def patch_sqlite(paths: list[str]) -> dict[str, tuple[int, int, int]]:
    db = sqlite3.connect(DB)
    db.row_factory = sqlite3.Row
    changed: dict[str, tuple[int, int, int]] = {}

    for path in paths:
        rows = db.execute("SELECT id, path, size, width, height FROM media WHERE path = ?",
                          (path,)).fetchall()
        if not rows:
            raise SystemExit(f"media: no row for {path}")
        facts = file_facts(path)
        if facts is None:
            raise SystemExit(f"media: file missing for {path}")
    for row in db.execute("SELECT id, path, size, width, height FROM media ORDER BY id").fetchall():
        if str(row["path"]) not in paths:
            continue
        facts = file_facts(str(row["path"]))
        if facts is None:
            continue
        size, width, height = facts
        if (row["size"], row["width"], row["height"]) == facts:
            continue
        db.execute("UPDATE media SET size = ?, width = ?, height = ?, updated_at = ? WHERE id = ?",
                   (size, width, height, NOW, row["id"]))
        changed[str(row["path"])] = facts

    db.commit()
    db.close()

    return changed


def patch_dump(path: Path, changed: dict[str, tuple[int, int, int]]) -> None:
    if not changed:
        return

    sql = path.read_text(encoding="utf-8")
    lines = sql.split("\n")
    header = f"INSERT INTO `media` ({', '.join('`' + c + '`' for c in MEDIA_COLUMNS)}) VALUES"
    pending = dict(changed)
    in_block = False

    for index, line in enumerate(lines):
        if in_block:
            match = re.match(r"\((\d+), (NULL|'[^']*'), '[^']*', '[^']*', '[^']*', '([^']*)',", line)
            closed = line.rstrip().endswith(";")
            in_block = not closed
            if match is None:
                continue
            media_path = match.group(3)
            if media_path in pending:
                lines[index] = _rewrite_tuple(line, pending.pop(media_path))
        elif line.startswith(header):
            match = re.search(r"VALUES \((\d+), (?:NULL|'[^']*'), '[^']*', '[^']*', '[^']*', '([^']*)',", line)
            in_block = not line.rstrip().endswith(";")
            if match is None:
                continue
            media_path = match.group(2)
            if media_path in pending:
                lines[index] = _rewrite_tuple(line, pending.pop(media_path))

    if pending:
        raise SystemExit(f"media paths not found in {path.name}: {sorted(pending)}")

    path.write_text("\n".join(lines), encoding="utf-8")


TUPLE_RE = re.compile(r"^(?P<lead>.*?VALUES \()?(?P<tuple>\((?:[^()']|'(?:[^'\\]|\\.)*')*\))(?P<tail>.*)$")


def _rewrite_tuple(line: str, facts: tuple[int, int, int]) -> str:
    """Replace size/width/height inside one `media` tuple, keeping the rest byte-identical."""
    size, width, height = facts
    match = re.match(
        r"^(?P<before>.*?VALUES \((?:\d+), (?:NULL|'(?:[^'\\]|\\.)*'), '(?:[^'\\]|\\.)*', "
        r"'(?:[^'\\]|\\.)*', '(?:[^'\\]|\\.)*', '(?:[^'\\]|\\.)*', "
        r"'(?:[^'\\]|\\.)*', '(?:[^'\\]|\\.)*', )(?P<size>-?\d+|NULL), (?P<width>-?\d+|NULL), "
        r"(?P<height>-?\d+|NULL)(?P<after>,.*)$",
        line,
    ) or re.match(
        r"^(?P<before>\(\d+, (?:NULL|'(?:[^'\\]|\\.)*'), '(?:[^'\\]|\\.)*', "
        r"'(?:[^'\\]|\\.)*', '(?:[^'\\]|\\.)*', '(?:[^'\\]|\\.)*', "
        r"'(?:[^'\\]|\\.)*', '(?:[^'\\]|\\.)*', )(?P<size>-?\d+|NULL), (?P<width>-?\d+|NULL), "
        r"(?P<height>-?\d+|NULL)(?P<after>,.*)$",
        line,
    )
    if match is None:
        raise SystemExit(f"cannot rewrite media tuple: {line[:120]}")

    return f"{match.group('before')}{size}, {width}, {height}{match.group('after')}"


def main() -> None:
    paths = sys.argv[1:]
    if not paths:
        raise SystemExit("usage: lufly_media_sync.py <media path> [<media path> ...]")
    changed = patch_sqlite(paths)
    for dump in DUMPS:
        patch_dump(dump, changed)
    print(f"media synced: {len(changed)} file(s)")


if __name__ == "__main__":
    main()
