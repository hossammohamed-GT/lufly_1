#!/usr/bin/env python3
"""Reconcile the catalog taxonomy and the public visibility rules.

Run once from the repository root:

    python3 tools/catalog_visibility_fix.py

What it does (idempotent — re-running changes nothing):

1. Shower Enclosures holds glass enclosures only. The four shower trays that
   were parked in ``shower-enclosures.other-enclosures`` move to a new Bathroom
   > Sanitary Ware > Shower Trays branch and ``other-enclosures`` is retired, so
   the emptied node disappears from the atlas instead of dangling.
2. Uploads that never reached the disk are flagged ``missing`` on their media
   row and their products drop to ``draft``: nobody could ever open such a page
   (the card image 404s), yet the category badge still counted it — the
   "soap-dispenser says 1 product but the grid is empty" defect.
3. Product 714 trades the admin placeholder slug ``new-product`` for the real
   model slug.

The SQLite runtime file and both server dumps are patched in the same run, and
the three stores are re-read afterwards to prove they agree.
"""

from __future__ import annotations

import re
import sqlite3
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DB = ROOT / "database" / "lufly.sqlite"
DUMPS = [ROOT / "lufly-database.sql", ROOT / "lufly_db_new.sql"]

NOW = "2026-11-05 12:00:00"

TRAY_CATEGORY = {
    "id": 111,
    "parent_id": 2,
    "slug": "bathroom.sanitary-ware.shower-trays",
    "image": "/images/categories/111-bathroom-sanitary-ware-shower-trays.jpg",
    "sort_order": 8,
    "seo": '{"title": "Shower Trays"}',
}
TRAY_NAMES = {"en": "Shower Trays", "tr": "Duş Tekneleri", "cs": "Sprchové vaničky"}
TRAY_PRODUCT_IDS = (125, 126, 127, 128)
RETIRED_CATEGORY_ID = 79  # shower-enclosures.other-enclosures

SOAP_PRODUCT_IDS = (714, 715)
SOAP_MEDIA_IDS = (1497, 1498, 1499, 1500)
PRODUCT_SLUG_FIX = {714: "v-0726c"}

CATEGORY_COLUMNS = [
    "id", "parent_id", "slug", "image", "icon", "is_featured",
    "sort_order", "status", "seo", "created_at", "updated_at", "deleted_at",
]


# --------------------------------------------------------------------------- #
# SQLite runtime file
# --------------------------------------------------------------------------- #
def patch_sqlite() -> None:
    connection = sqlite3.connect(DB)

    if connection.execute("SELECT id FROM categories WHERE id = ?", (TRAY_CATEGORY["id"],)).fetchone() is None:
        connection.execute(
            "INSERT INTO categories (id, parent_id, slug, image, icon, is_featured, sort_order, status, seo, "
            "created_at, updated_at, deleted_at) VALUES (?, ?, ?, ?, NULL, 0, ?, 'active', ?, ?, ?, NULL)",
            (
                TRAY_CATEGORY["id"], TRAY_CATEGORY["parent_id"], TRAY_CATEGORY["slug"],
                TRAY_CATEGORY["image"], TRAY_CATEGORY["sort_order"], TRAY_CATEGORY["seo"], NOW, NOW,
            ),
        )
        for offset, (locale, name) in enumerate(TRAY_NAMES.items(), start=329):
            connection.execute(
                "INSERT INTO category_translations (id, category_id, locale, name, description) "
                "VALUES (?, ?, ?, ?, NULL)",
                (offset, TRAY_CATEGORY["id"], locale, name),
            )

    connection.execute(
        "UPDATE products SET category_id = ?, updated_at = ? WHERE id IN (?, ?, ?, ?)",
        (TRAY_CATEGORY["id"], NOW, *TRAY_PRODUCT_IDS),
    )
    connection.execute(
        "UPDATE categories SET status = 'inactive', updated_at = ? WHERE id = ?",
        (NOW, RETIRED_CATEGORY_ID),
    )
    for product_id, slug in PRODUCT_SLUG_FIX.items():
        connection.execute(
            "UPDATE products SET slug = ?, updated_at = ? WHERE id = ?", (slug, NOW, product_id)
        )
    connection.execute(
        "UPDATE media SET status = 'missing', updated_at = ? WHERE id IN (?, ?, ?, ?)",
        (NOW, *SOAP_MEDIA_IDS),
    )
    connection.execute(
        "UPDATE products SET status = 'draft', updated_at = ? WHERE id IN (?, ?)",
        (NOW, *SOAP_PRODUCT_IDS),
    )
    connection.commit()
    connection.close()


# --------------------------------------------------------------------------- #
# SQL dumps — surgical line rewrites, table by table
# --------------------------------------------------------------------------- #
def sub_or_fail(sql: str, pattern: str, replacement, label: str) -> str:
    new_sql, changes = re.subn(pattern, replacement, sql)
    if changes == 0:
        raise SystemExit(f"{label}: expected row was not found, aborting")
    return new_sql


def patch_compact_dump(path: Path) -> None:
    """lufly-database.sql: one INSERT per line."""
    sql = path.read_text(encoding="utf-8")

    if "'bathroom.sanitary-ware.shower-trays'" not in sql:
        translation_rows = "".join(
            "\nINSERT INTO `category_translations` (`id`, `category_id`, `locale`, `name`, `description`) "
            f"VALUES ({offset}, {TRAY_CATEGORY['id']}, '{locale}', '{name}', NULL);"
            for offset, (locale, name) in enumerate(TRAY_NAMES.items(), start=329)
        )
        category_row = (
            "\nINSERT INTO `categories` (`id`, `parent_id`, `slug`, `image`, `icon`, `is_featured`, "
            "`sort_order`, `status`, `seo`, `created_at`, `updated_at`, `deleted_at`) VALUES "
            f"({TRAY_CATEGORY['id']}, {TRAY_CATEGORY['parent_id']}, '{TRAY_CATEGORY['slug']}', "
            f"'{TRAY_CATEGORY['image']}', NULL, 0, {TRAY_CATEGORY['sort_order']}, 'active', "
            f"'{{\\\"title\\\": \\\"Shower Trays\\\"}}', '{NOW}', '{NOW}', NULL);"
        )
        sql = replace_last_line(sql, "INSERT INTO `categories`", category_row)
        sql = replace_last_line(sql, "INSERT INTO `category_translations`", translation_rows)

    # the four trays leave Other Enclosures
    sql = sub_or_fail(
        sql,
        r"(INSERT INTO `products` \([^)]*\) VALUES \((?:125|126|127|128), '[^']*', '[^']*', )79(,)",
        lambda m: m.group(1) + str(TRAY_CATEGORY["id"]) + m.group(2),
        "trays -> shower-trays",
    )
    # the emptied node is retired
    sql = sub_or_fail(
        sql,
        r"(INSERT INTO `categories` \([^)]*\) VALUES \(79, 71, 'shower-enclosures\.other-enclosures',[^;]*?, )'active'(,)",
        r"\1'inactive'\2",
        "other-enclosures -> inactive",
    )
    # soap-dispenser products: real slug, out of the public grid
    sql = sub_or_fail(
        sql,
        r"(INSERT INTO `products` \([^)]*\) VALUES \(714, 'V-0726C', )'new-product'(, 110, NULL, NULL, )'active'",
        r"\1'v-0726c'\2'draft'",
        "product 714 slug/status",
    )
    sql = sub_or_fail(
        sql,
        r"(INSERT INTO `products` \([^)]*\) VALUES \(715, 'V-0726C-2', '[^']*', 110, NULL, NULL, )'draft'",
        r"\1'draft'",
        "product 715 status",
    )
    # their uploads never existed on disk
    sql = sub_or_fail(
        sql,
        r"(INSERT INTO `media` \([^)]*\) VALUES \((?:1497|1498|1499|1500),.*?, )'active'(, '2026-10-31)",
        r"\1'missing'\2",
        "soap media -> missing",
    )

    path.write_text(sql, encoding="utf-8")


def patch_pretty_dump(path: Path) -> None:
    """lufly_db_new.sql: one multi-row INSERT per table."""
    sql = path.read_text(encoding="utf-8")

    if "'bathroom.sanitary-ware.shower-trays'" not in sql:
        category_tuple = (
            f"\n({TRAY_CATEGORY['id']}, {TRAY_CATEGORY['parent_id']}, '{TRAY_CATEGORY['slug']}', "
            f"'{TRAY_CATEGORY['image']}', NULL, 0, {TRAY_CATEGORY['sort_order']}, 'active', "
            f"'{{\\\\\"title\\\\\": \\\\\"Shower Trays\\\\\"}}', '{NOW}', '{NOW}', NULL),"
        )
        sql = sql.replace(
            "--\n-- Dumping data for table `categories`\n--\n\n"
            "INSERT INTO `categories` (`id`, `parent_id`, `slug`, `image`, `icon`, `is_featured`, "
            "`sort_order`, `status`, `seo`, `created_at`, `updated_at`, `deleted_at`) VALUES\n",
            "--\n-- Dumping data for table `categories`\n--\n\n"
            "INSERT INTO `categories` (`id`, `parent_id`, `slug`, `image`, `icon`, `is_featured`, "
            "`sort_order`, `status`, `seo`, `created_at`, `updated_at`, `deleted_at`) VALUES"
            + category_tuple + "\n",
            1,
        )
        translation_tuples = "".join(
            f"\n({offset}, {TRAY_CATEGORY['id']}, '{locale}', '{name}', NULL),"
            for offset, (locale, name) in enumerate(TRAY_NAMES.items(), start=329)
        )
        sql = sql.replace(
            "--\n-- Dumping data for table `category_translations`\n--\n\n"
            "INSERT INTO `category_translations` (`id`, `category_id`, `locale`, `name`, `description`) VALUES\n",
            "--\n-- Dumping data for table `category_translations`\n--\n\n"
            "INSERT INTO `category_translations` (`id`, `category_id`, `locale`, `name`, `description`) VALUES"
            + translation_tuples + "\n",
            1,
        )

    sql = sub_or_fail(
        sql,
        r"(\((?:125|126|127|128), '[^']*', '[^']*', )79(,)",
        lambda m: m.group(1) + str(TRAY_CATEGORY["id"]) + m.group(2),
        "trays -> shower-trays",
    )
    sql = sub_or_fail(
        sql,
        r"(\(79, 71, 'shower-enclosures\.other-enclosures',[^\n]*?, )'active'(,)",
        r"\1'inactive'\2",
        "other-enclosures -> inactive",
    )
    sql = sub_or_fail(
        sql,
        r"(\(714, 'V-0726C', )'new-product'(, 110, NULL, NULL, )'active'",
        r"\1'v-0726c'\2'draft'",
        "product 714 slug/status",
    )
    sql = sub_or_fail(
        sql,
        r"(\((?:1497|1498|1499|1500),.*?, )'active'(, '2026-10-31)",
        r"\1'missing'\2",
        "soap media -> missing",
    )

    path.write_text(sql, encoding="utf-8")


def replace_last_line(sql: str, prefix: str, addition: str) -> str:
    lines = sql.split("\n")
    index = max(i for i, line in enumerate(lines) if line.startswith(prefix))
    lines.insert(index + 1, addition.lstrip("\n"))
    return "\n".join(lines)


# --------------------------------------------------------------------------- #
# Verification
# --------------------------------------------------------------------------- #
def sqlite_state() -> dict[str, object]:
    connection = sqlite3.connect(DB)
    connection.row_factory = sqlite3.Row
    state = {
        "tray_category": connection.execute(
            "SELECT COUNT(*) AS n FROM categories WHERE slug = ?", (TRAY_CATEGORY["slug"],)
        ).fetchone()["n"] == 1,
        "tray_names": connection.execute(
            "SELECT COUNT(*) AS n FROM category_translations WHERE category_id = ?", (TRAY_CATEGORY["id"],)
        ).fetchone()["n"] == 3,
        "trays_moved": connection.execute(
            "SELECT COUNT(*) AS n FROM products WHERE id IN (125, 126, 127, 128) AND category_id = ?",
            (TRAY_CATEGORY["id"],),
        ).fetchone()["n"] == 4,
        "other_enclosures_inactive": connection.execute(
            "SELECT status FROM categories WHERE id = ?", (RETIRED_CATEGORY_ID,)
        ).fetchone()["status"] == "inactive",
        "soap_products_draft": connection.execute(
            "SELECT COUNT(*) AS n FROM products WHERE id IN (714, 715) AND status = 'draft'"
        ).fetchone()["n"] == 2,
        "soap_media_missing": connection.execute(
            "SELECT COUNT(*) AS n FROM media WHERE id IN (?, ?, ?, ?) AND status = 'missing'", SOAP_MEDIA_IDS
        ).fetchone()["n"] == 4,
        "product_714_slug": connection.execute(
            "SELECT slug FROM products WHERE id = 714"
        ).fetchone()["slug"] == "v-0726c",
    }
    connection.close()

    return state


def dump_state(path: Path) -> dict[str, object]:
    sql = path.read_text(encoding="utf-8")
    trays = re.findall(
        r"\((?:125|126|127|128), '[^']*', '[^']*', " + str(TRAY_CATEGORY["id"]) + ",",
        sql,
    )

    return {
        "tray_category": "'" + TRAY_CATEGORY["slug"] + "'" in sql,
        "tray_names": all("'" + name + "'" in sql for name in TRAY_NAMES.values()),
        "trays_moved": len(trays) == 4,
        "other_enclosures_inactive": bool(
            re.search(r"\(79, 71, 'shower-enclosures\.other-enclosures',[^\n]*?, 'inactive'", sql)
        ),
        "soap_products_draft": len(re.findall(r"\(71[45], 'V-0726C[^']*', '[^']*', 110, NULL, NULL, 'draft'", sql)) == 2,
        "soap_media_missing": len(
            re.findall(r"\((?:1497|1498|1499|1500),.*?, 'missing', '2026-10-31", sql)
        ) == 4,
        "product_714_slug": bool(re.search(r"\(714, 'V-0726C', 'v-0726c'", sql)),
    }


def main() -> None:
    patch_sqlite()
    patch_compact_dump(DUMPS[0])
    patch_pretty_dump(DUMPS[1])

    states = {"sqlite": sqlite_state()}
    for dump in DUMPS:
        states[dump.name] = dump_state(dump)

    for name, state in states.items():
        print(f"{name:20} {state}")
    first = states["sqlite"]
    print("3 stores identical:", all(state == first for state in states.values()))


if __name__ == "__main__":
    main()
