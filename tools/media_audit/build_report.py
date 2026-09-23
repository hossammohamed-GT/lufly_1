#!/usr/bin/env python3
"""Audit (and optionally fix) which tab every product image belongs to.

Run from the repository root:

    python3 tools/media_audit/build_report.py                       # report only
    python3 tools/media_audit/build_report.py --apply               # write to database/lufly.sqlite
    python3 tools/media_audit/build_report.py --apply --patch-sql   # ... and refresh lufly-database.sql

What it does
------------
For every row of `product_media` it looks at the file on disk, decides
photo-vs-drawing (see classify.py) and writes the type that the storefront
tabs expect:

    photos            -> type "main"    (exactly one, is_primary = 1)
                         type "gallery" (the rest)
    technical drawing -> type "drawing"
    installed / site  -> type "situ"    (left untouched: never auto-detected)

Requires `pillow` and `numpy` (tooling only - the application itself has no
Python dependency).
"""
from __future__ import annotations

import argparse
import csv
import json
import os
import re
import sqlite3
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from classify import PHOTO_MIN_SURVIVAL, classify, stroke_survival  # noqa: E402

ROOT = Path(__file__).resolve().parents[2]
DB_PATH = ROOT / "database/lufly.sqlite"
PUBLIC = ROOT / "public"
EXPORT_PATH = ROOT / "lufly-database.sql"
SEED_PATH = ROOT / "database/seeders/data/products.json"
REPORT_PATH = ROOT / "storage/reports/media-classification.csv"

PHOTO_TYPES = ("main", "gallery")


def load_rows(connection: sqlite3.Connection) -> list[dict]:
    connection.row_factory = sqlite3.Row
    rows = connection.execute(
        """
        SELECT pm.id            AS product_media_id,
               pm.product_id    AS product_id,
               pm.variant_id    AS variant_id,
               pm.media_id      AS media_id,
               pm.type          AS current_type,
               pm.sort_order    AS current_sort,
               pm.is_primary    AS current_primary,
               m.path           AS path,
               m.status         AS status
        FROM product_media pm
        INNER JOIN media m ON m.id = pm.media_id
        ORDER BY pm.product_id ASC, pm.sort_order ASC, pm.id ASC
        """
    ).fetchall()
    return [dict(r) for r in rows]


def analyse(rows: list[dict]) -> list[dict]:
    for row in rows:
        file = PUBLIC / str(row["path"]).lstrip("/")
        area, survival = stroke_survival(file)
        kind, reason = classify(file, survival)
        row["area"] = round(area, 4)
        row["survival"] = round(survival, 4)
        row["detected"] = kind
        row["reason"] = reason
        row["new_type"] = row["current_type"]
        row["new_sort"] = row["current_sort"]
        row["new_primary"] = int(row["current_primary"])
    return rows


def plan(rows: list[dict]) -> list[dict]:
    """Assign the final type / order / primary flag for every attachment."""
    by_product: dict[int, list[dict]] = {}
    for row in rows:
        by_product.setdefault(int(row["product_id"]), []).append(row)

    for product_id, items in by_product.items():
        # "situ" (installed shots) is a manual section: never re-typed here.
        manual = [r for r in items if r["current_type"] == "situ"]
        for index, row in enumerate(manual, start=1):
            row["new_type"] = "situ"
            row["new_sort"] = index
            row["new_primary"] = int(row["current_primary"])

        # keep the existing on-page order of every section
        rest = sorted(
            (r for r in items if r["current_type"] != "situ"),
            key=lambda r: (int(r["current_sort"]), int(r["product_media_id"])),
        )
        photos = [r for r in rest if r["detected"] == "photo"]
        drawings = [r for r in rest if r["detected"] != "photo"]

        if photos:
            # the flagged primary photo keeps first place, the rest follow
            photos.sort(key=lambda r: (0 if int(r["current_primary"]) else 1, int(r["current_sort"])))
            for index, row in enumerate(photos):
                row["new_type"] = "main" if index == 0 else "gallery"
                row["new_sort"] = index + 1
                row["new_primary"] = 1 if index == 0 else 0

        for index, row in enumerate(drawings, start=1):
            row["new_type"] = "drawing"
            row["new_sort"] = index
            row["new_primary"] = 0

    return rows


def summary(rows: list[dict]) -> dict:
    changed = [r for r in rows if (r["new_type"], r["new_sort"], r["new_primary"])
               != (r["current_type"], int(r["current_sort"]), int(r["current_primary"]))]
    by_product: dict[int, list[dict]] = {}
    for row in rows:
        by_product.setdefault(int(row["product_id"]), []).append(row)

    without_photo = sorted(
        pid for pid, items in by_product.items()
        if not any(r["new_type"] in PHOTO_TYPES for r in items)
    )

    return {
        "attachments": len(rows),
        "photos": sum(1 for r in rows if r["new_type"] in PHOTO_TYPES),
        "drawings": sum(1 for r in rows if r["new_type"] == "drawing"),
        "situ": sum(1 for r in rows if r["new_type"] == "situ"),
        "changed_rows": len(changed),
        "gallery_to_drawing": sum(1 for r in changed
                                  if r["current_type"] == "gallery" and r["new_type"] == "drawing"),
        "main_to_drawing": sum(1 for r in changed
                               if r["current_type"] == "main" and r["new_type"] == "drawing"),
        "photo_to_drawing": sum(1 for r in changed
                                if r["current_type"] == "drawing" and r["new_type"] in PHOTO_TYPES),
        "products": len(by_product),
        "products_without_photo": without_photo,
    }


def apply_to_sqlite(rows: list[dict]) -> int:
    connection = sqlite3.connect(DB_PATH)
    try:
        with connection:
            cursor = connection.cursor()
            for row in rows:
                cursor.execute(
                    "UPDATE product_media SET type = ?, sort_order = ?, is_primary = ? WHERE id = ?",
                    (row["new_type"], int(row["new_sort"]), int(row["new_primary"]),
                     int(row["product_media_id"])),
                )
        return connection.total_changes
    finally:
        connection.close()


def quote(value: object) -> str:
    if value is None:
        return "NULL"
    if isinstance(value, bool):
        return "1" if value else "0"
    if isinstance(value, (int, float)):
        return str(value)
    return "'" + str(value).replace("\\", "\\\\").replace("'", "''") + "'"


def write_csv(rows: list[dict], path: Path) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    columns = [
        "product_id", "product_media_id", "media_id", "path", "current_type", "current_sort",
        "current_primary", "new_type", "new_sort", "new_primary", "detected", "survival", "area", "reason",
    ]
    with path.open("w", newline="", encoding="utf-8") as handle:
        writer = csv.DictWriter(handle, fieldnames=columns, extrasaction="ignore")
        writer.writeheader()
        writer.writerows(rows)


def patch_mysql_export() -> int:
    """Rewrite the product_media data block of lufly-database.sql from sqlite.

    The MySQL export shipped for XAMPP/phpMyAdmin holds the same rows as
    database/lufly.sqlite; only this block changes, so it is regenerated with
    exactly the same statement format the exporter uses.
    """
    connection = sqlite3.connect(DB_PATH)
    connection.row_factory = sqlite3.Row
    rows = connection.execute("SELECT * FROM product_media ORDER BY id ASC").fetchall()
    connection.close()

    columns = [key for key in rows[0].keys()] if rows else []
    statements = [
        "INSERT INTO `product_media` ({}) VALUES ({});".format(
            ", ".join(f"`{c}`" for c in columns),
            ", ".join(quote(row[c]) for c in columns),
        )
        for row in rows
    ]

    lines = EXPORT_PATH.read_text(encoding="utf-8").split("\n")
    start = next(i for i, line in enumerate(lines) if line.startswith("-- data: product_media"))
    end = start + 1
    while end < len(lines) and lines[end].startswith("INSERT INTO `product_media`"):
        end += 1

    replacement = [f"-- data: product_media ({len(statements)} rows)"] + statements
    EXPORT_PATH.write_text("\n".join(lines[:start] + replacement + lines[end:]), encoding="utf-8")

    return len(statements)


def patch_seed_file(rows: list[dict]) -> tuple[int, int]:
    """Keep the seeder honest.

    `database/seeders/data/products.json` is the source `php cli seed` rebuilds
    the catalog from, so it has to produce the same photo/drawing split (and the
    same availability flags) as the database. Returns (type changes, file flags
    corrected). Types and paths are patched textually to leave the hand-written
    JSON formatting untouched.
    """
    text = SEED_PATH.read_text(encoding="utf-8")
    type_changes = 0
    flag_changes = 0

    for row in rows:
        path = str(row["path"])
        new_type = row["new_type"]
        if new_type not in PHOTO_TYPES + ("drawing",):
            continue  # "situ" is a manual decision, never seeded

        pattern = re.compile(
            r'("type":\s*")(?P<old>gallery|main|drawing)(",\s*\n\s*"path":\s*")' + re.escape(path) + r'(")'
        )
        if not pattern.search(text):
            continue

        existing = os.path.exists(PUBLIC / path.lstrip("/"))
        available = "true" if existing else "false"

        def replace(match: re.Match[str]) -> str:
            nonlocal type_changes
            if match.group("old") != new_type:
                type_changes += 1
            return match.group(1) + new_type + match.group(3) + path + match.group(4)

        text = pattern.sub(replace, text, count=1)

        flag_pattern = re.compile(
            r'("path":\s*")' + re.escape(path) + r'(",\s*\n\s*"available":\s*)(true|false)'
        )
        flag_match = flag_pattern.search(text)
        if flag_match is not None and flag_match.group(3) != available:
            text = flag_pattern.sub(
                lambda m: m.group(1) + path + m.group(2) + available, text, count=1
            )
            flag_changes += 1

    json.loads(text)  # refuse to write a broken seed file
    SEED_PATH.write_text(text, encoding="utf-8")

    return type_changes, flag_changes


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--apply", action="store_true", help="write the plan to database/lufly.sqlite")
    parser.add_argument("--patch-sql", action="store_true",
                        help="also refresh the product_media block of lufly-database.sql")
    parser.add_argument("--patch-seed", action="store_true",
                        help="also refresh image types + availability in database/seeders/data/products.json")
    parser.add_argument("--report", default=str(REPORT_PATH), help="CSV output path")
    parser.add_argument("--json", dest="json_path", help="optional JSON dump of the plan")
    parser.add_argument("--quiet", action="store_true")
    args = parser.parse_args()

    connection = sqlite3.connect(DB_PATH)
    rows = load_rows(connection)
    connection.close()

    rows = plan(analyse(rows))
    stats = summary(rows)

    write_csv(rows, Path(args.report))
    if args.json_path:
        Path(args.json_path).write_text(json.dumps(rows, indent=2), encoding="utf-8")

    if not args.quiet:
        print(f"attachments            : {stats['attachments']}")
        print(f"  photos (main+gallery): {stats['photos']}")
        print(f"  drawings             : {stats['drawings']}")
        print(f"  installed (situ)     : {stats['situ']}")
        print(f"products               : {stats['products']}")
        print(f"products with no photo : {len(stats['products_without_photo'])} "
              f"{stats['products_without_photo']}")
        print()
        print(f"rows that change       : {stats['changed_rows']}")
        print(f"  gallery -> drawing   : {stats['gallery_to_drawing']}")
        print(f"  main    -> drawing   : {stats['main_to_drawing']}")
        print(f"  drawing -> photo     : {stats['photo_to_drawing']}")
        print(f"drawing threshold      : survival < {PHOTO_MIN_SURVIVAL}")
        print(f"report                 : {args.report}")

    if args.apply:
        apply_to_sqlite(rows)
        print("\napplied to database/lufly.sqlite")
    if args.patch_sql:
        count = patch_mysql_export()
        print(f"refreshed product_media block in lufly-database.sql ({count} rows)")
    if args.patch_seed:
        types, flags = patch_seed_file(rows)
        print(f"seed file updated: {types} image type(s), {flags} availability flag(s)")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
