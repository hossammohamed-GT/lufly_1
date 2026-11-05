#!/usr/bin/env python3
"""Complete the V-0726C hand wash dispenser (product 714).

The piece reached the shop as a bare admin record — a code, a name and four
uploads whose files never landed on the server — so the storefront had nothing
to render. This fills in the whole dossier the catalogue expects, the way every
other product in the shop is built:

* three photographs (hero, detail, in-situ) and the technical sheet
* name, short description and full description in en / tr / cs
* twelve key specifications, including the verified drawing dimensions
* six card feature badges
* physical dimensions used by the key-specs panel and the planner
* search keywords

Product 715 is the second upload of the same dispenser — same code, same
photos, same drawing — so it stays a draft instead of duplicating the piece.

Run once from the repository root:

    python3 tools/lufly_v0726c_seed.py

The SQLite runtime file and both server dumps are patched in the same run, from
one table of rows, and the three stores are re-read afterwards to prove they
agree.
"""

from __future__ import annotations

import re
import sqlite3
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DB = ROOT / "database" / "lufly.sqlite"
DUMPS = [ROOT / "lufly-database.sql", ROOT / "lufly_db_new.sql"]

NOW = "2026-11-05 13:00:00"
PRODUCT_ID = 714
DUPLICATE_ID = 715
CATEGORY_ID = 110
CATEGORY_SLUG = "soap-dispenser"

HERO = "/images/products/soap-dispenser/v-0726c-hero.jpg"
DETAIL = "/images/products/soap-dispenser/v-0726c-detail.jpg"
LIFESTYLE = "/images/products/soap-dispenser/v-0726c-lifestyle.jpg"
DRAWING = "/images/products/catalog/4f7df108c783d9ba2757a88f.webp"

NAMES = {
    "en": "Hand Wash Dispenser",
    "tr": "El Yıkama Sabunluk Bataryası",
    "cs": "Dávkovač mýdla",
}

SHORT = {
    "en": "Hand Wash Dispenser — deck mounted, push type, 48 mm body, 185 mm high, brass body "
          "with AISI 304 stainless steel, Chrome finish",
    "tr": "El Yıkama Sabunluk Bataryası — tezgâh üstü, basmalı, 48 mm gövde, 185 mm yükseklik, "
          "pirinç gövde ve AISI 304 paslanmaz çelik, Krom kaplama",
    "cs": "Dávkovač mýdla — na desku, tlačný, tělo 48 mm, výška 185 mm, mosazné tělo a nerez "
          "AISI 304, povrch Chrom",
}

DESCRIPTION = {
    "en": "Hand Wash Dispenser — Deck Mounted, Soap Dispenser, Push Type, Brass raw copper material "
          "faucet body with Stainless Steel 304, Finish: Chrome, Diameter: 48 mm [4.8 cm], Weight: 2 kg, "
          "Max 43 mm [4.3 cm] Mounting Thickness, 60 mm base flange, 185 mm overall height, 150 mm body "
          "height above the base, body inclined at 60° so the soap falls naturally into the hand over the "
          "basin. Push-type pump with a slim chrome actuator and a machined soap outlet; recommended "
          "maximum of 40,000 cP soap viscosity, suitable for liquid soap and hand wash gels. The polished "
          "cap carries the LUFLY mark engraved tone-on-tone.\n\n"
          "Verified drawing dimensions:\n"
          "Body diameter: 48 mm\n"
          "Base flange diameter: 60 mm\n"
          "Overall height: 185 mm\n"
          "Body height above base: 150 mm\n"
          "Maximum mounting thickness: 43 mm\n"
          "Weight: 2 kg",
    "tr": "El Yıkama Sabunluk Bataryası — Tezgâh Üstü, Sabunluk, Basmalı, pirinç ham bakır gövde ve "
          "AISI 304 paslanmaz çelik, Yüzey: Krom, Çap: 48 mm [4,8 cm], Ağırlık: 2 kg, Maksimum 43 mm "
          "[4,3 cm] montaj kalınlığı, 60 mm taban flanşı, 185 mm toplam yükseklik, tabandan itibaren "
          "150 mm gövde yüksekliği, gövde lavaboya doğal el pozisyonu için 60° eğimli. İnce krom butonlu "
          "basmalı pompa ve işlenmiş sabun çıkışı; tavsiye edilen maksimum 40.000 cP sabun viskozitesi, "
          "sıvı sabun ve el yıkama jelleri için uygundur. Parlak başlıkta LUFLY markası ton üzerine ton "
          "kazınmıştır.\n\n"
          "Doğrulanmış çizim ölçüleri:\n"
          "Gövde çapı: 48 mm\n"
          "Taban flanş çapı: 60 mm\n"
          "Toplam yükseklik: 185 mm\n"
          "Tabandan gövde yüksekliği: 150 mm\n"
          "Maksimum montaj kalınlığı: 43 mm\n"
          "Ağırlık: 2 kg",
    "cs": "Dávkovač mýdla — na desku, dávkovač mýdla, tlačný, mosazné tělo ze surové mědi a nerez "
          "AISI 304, povrch: Chrom, průměr: 48 mm [4,8 cm], hmotnost: 2 kg, maximální tloušťka montáže "
          "43 mm [4,3 cm], základní příruba 60 mm, celková výška 185 mm, výška těla nad základnou 150 mm, "
          "tělo skloněné o 60° pro přirozenou polohu ruky nad umyvadlem. Tlačná pumpa se štíhlým chromovým "
          "ovladačem a opracovaným vývodem mýdla; doporučená maximální viskozita mýdla 40 000 cP, vhodné "
          "pro tekuté mýdlo a gely na mytí rukou. Na leštěném víku je značka LUFLY vyrytá tón v tónu.\n\n"
          "Ověřené rozměry z výkresu:\n"
          "Průměr těla: 48 mm\n"
          "Průměr základní příruby: 60 mm\n"
          "Celková výška: 185 mm\n"
          "Výška těla nad základnou: 150 mm\n"
          "Maximální tloušťka montáže: 43 mm\n"
          "Hmotnost: 2 kg",
}

# media id, filename, original name, path, mime, extension, width, height
PHOTOS = [
    (1501, "v-0726c-hero.jpg", "V-0726C hero.jpg", HERO, "image/jpeg", "jpeg", 1600, 2867),
    (1502, "v-0726c-detail.jpg", "V-0726C detail.jpg", DETAIL, "image/jpeg", "jpeg", 1600, 893),
    (1503, "v-0726c-lifestyle.jpg", "V-0726C in situ.jpg", LIFESTYLE, "image/jpeg", "jpeg", 1600, 1477),
    (1504, "4f7df108c783d9ba2757a88f.webp", "V-0726C technical sheet.webp", DRAWING, "image/webp", "webp", 1720, 1440),
]
# attachment id, media id, type, sort order, is_primary
ATTACHMENTS = [
    (1506, 1501, "gallery", 1, 1),
    (1507, 1502, "gallery", 2, 0),
    (1508, 1503, "situ", 3, 0),
    (1509, 1504, "drawing", 1, 0),
]
# the four uploads whose files never landed on the server: the rows stay as
# the record, only their status turns to "missing"
RETIRED_UPLOADS = (1497, 1498, 1499, 1500)
OLD_MEDIA_IDS = RETIRED_UPLOADS
OLD_ATTACHMENTS = (1499, 1500, 1501, 1502)
DUPLICATE_ATTACHMENTS = (1503, 1504, 1505)

# key, en, tr, cs
SPECS = [
    ("Finish", "Chrome", "Krom", "Chrom"),
    ("Drawing: Body diameter", "48 mm", "48 mm", "48 mm"),
    ("Drawing: Base diameter", "60 mm", "60 mm", "60 mm"),
    ("Drawing: Overall height", "185 mm", "185 mm", "185 mm"),
    ("Drawing: Body height", "150 mm", "150 mm", "150 mm"),
    ("Drawing: Mounting thickness", "Max 43 mm", "Maks. 43 mm", "Max. 43 mm"),
    ("Material", "Brass & AISI 304 Stainless Steel", "Pirinç ve AISI 304 Paslanmaz Çelik",
     "Mosaz a nerez AISI 304"),
    ("Installation", "Deck mounted", "Tezgâh üstü", "Na desku"),
    ("Dispenser type", "Push type", "Basmalı", "Tlačný"),
    ("Soap viscosity", "Max 40,000 cP", "Maks. 40.000 cP", "Max 40 000 cP"),
    ("Body angle", "60°", "60°", "60°"),
    ("Weight", "2 kg", "2 kg", "2 kg"),
]

SPEC_KEY_TR = {
    "Finish": "Yüzey Kaplaması",
    "Drawing: Body diameter": "Teknik Çizim: Gövde çapı",
    "Drawing: Base diameter": "Teknik Çizim: Taban çapı",
    "Drawing: Overall height": "Teknik Çizim: Toplam yükseklik",
    "Drawing: Body height": "Teknik Çizim: Gövde yüksekliği",
    "Drawing: Mounting thickness": "Teknik Çizim: Montaj kalınlığı",
    "Material": "Malzeme",
    "Installation": "Montaj",
    "Dispenser type": "Sabunluk tipi",
    "Soap viscosity": "Sabun viskozitesi",
    "Body angle": "Gövde açısı",
    "Weight": "Ağırlık",
}
SPEC_KEY_CS = {
    "Finish": "Povrchová úprava",
    "Drawing: Body diameter": "Výkres: Průměr těla",
    "Drawing: Base diameter": "Výkres: Průměr základny",
    "Drawing: Overall height": "Výkres: Celková výška",
    "Drawing: Body height": "Výkres: Výška těla",
    "Drawing: Mounting thickness": "Výkres: Tloušťka montáže",
    "Material": "Materiál",
    "Installation": "Montáž",
    "Dispenser type": "Typ dávkovače",
    "Soap viscosity": "Viskozita mýdla",
    "Body angle": "Úhel těla",
    "Weight": "Hmotnost",
}

# preset id, icon key, (en, tr, cs) label, (en, tr, cs) value
FEATURES = [
    (1, "dimensions", ("Dimensions", "Ölçüler", "Rozměry"), ("\u00d848 \u00d7 185 mm",) * 3),
    (2, "material", ("Material", "Malzeme", "Materiál"),
     ("Brass & AISI 304", "Pirinç ve AISI 304", "Mosaz a nerez AISI 304")),
    (3, "finish", ("Finish", "Yüzey", "Povrch"), ("Chrome", "Krom", "Chrom")),
    (4, "installation", ("Mounting", "Montaj", "Montáž"), ("Deck Mounted", "Tezgâh Üstü", "Na desku")),
    (20, "warranty", ("Operation", "Çalışma", "Provoz"), ("Push Type", "Basmalı", "Tlačný")),
    (19, "certification", ("Soap Viscosity", "Sabun Viskozitesi", "Viskozita mýdla"),
     ("Max 40,000 cP", "Maks. 40.000 cP", "Max 40 000 cP")),
]

KEYWORDS = [
    "V-0726C", "lufly", "soap dispenser", "hand wash dispenser", "sabunluk",
    "sabunluk bataryası", "dávkovač mýdla", "deck mounted dispenser",
    "push type soap dispenser", "chrome soap dispenser", "bathroom accessories",
    "bathroom", "accessories", "soap-dispenser",
]

DIMENSIONS = (48, 185, 48, 2)  # width, height, depth, weight


# --------------------------------------------------------------------------- #
# One table of rows; both stores are written from it
#
#   table -> (columns, rows)
# where each row is a list of Python values in column order.
# --------------------------------------------------------------------------- #
def media_size(url: str) -> int:
    return (ROOT / "public" / url.lstrip("/")).stat().st_size


def rows() -> dict[str, tuple[list[str], list[list[object]]]]:
    media = (
        ["id", "uuid", "collection", "filename", "original_name", "path", "mime_type", "extension",
         "size", "width", "height", "meta", "owner_id", "status", "created_at", "updated_at", "deleted_at"],
        [[media_id, None, CATEGORY_SLUG, filename, original, url, mime, ext, media_size(url), width,
          height, "[]", 2, "active", NOW, NOW, None]
         for media_id, filename, original, url, mime, ext, width, height in PHOTOS],
    )

    attachments = (
        ["id", "product_id", "variant_id", "media_id", "type", "sort_order", "is_primary",
         "created_at", "updated_at"],
        [[link_id, PRODUCT_ID, None, media_id, kind, order, is_primary, NOW, NOW]
         for link_id, media_id, kind, order, is_primary in ATTACHMENTS],
    )

    translations = (
        ["id", "product_id", "locale", "name", "short_description", "description"],
        [[2201, PRODUCT_ID, "tr", NAMES["tr"], SHORT["tr"], DESCRIPTION["tr"]],
         [2202, PRODUCT_ID, "cs", NAMES["cs"], SHORT["cs"], DESCRIPTION["cs"]]],
    )

    specs, spec_translations = [], []
    for offset, (key, en, tr, cs) in enumerate(SPECS):
        spec_id = 2500 + offset
        specs.append([spec_id, PRODUCT_ID, None, key, en, None, offset + 1, NOW, NOW])
        for lang, label, value in (("en", key, en), ("tr", SPEC_KEY_TR[key], tr), ("cs", SPEC_KEY_CS[key], cs)):
            spec_translations.append([7400 + offset * 3 + ("en", "tr", "cs").index(lang), spec_id, lang, label, value])

    features, feature_translations = [], []
    for offset, (preset_id, icon_key, labels, values) in enumerate(FEATURES):
        feature_id = 3800 + offset
        features.append([feature_id, PRODUCT_ID, preset_id, icon_key, offset + 1, NOW, NOW])
        for lang_index, lang in enumerate(("en", "tr", "cs")):
            feature_translations.append(
                [11300 + offset * 3 + lang_index, feature_id, lang, labels[lang_index], values[lang_index]]
            )

    keywords = (
        ["id", "product_id", "keyword"],
        [[9000 + offset, PRODUCT_ID, keyword] for offset, keyword in enumerate(KEYWORDS)],
    )

    categories = (
        ["id", "parent_id", "slug", "image", "icon", "is_featured", "sort_order", "status", "seo",
         "created_at", "updated_at", "deleted_at"],
        [[CATEGORY_ID, None, CATEGORY_SLUG, "/images/categories/110-soap-dispenser.jpg", None, 1, 10,
          "active", '{"title": "Soap Dispensers"}', "2026-10-30 16:16:50", NOW, None]],
    )

    category_translations = (
        ["id", "category_id", "locale", "name", "description"],
        [[328, CATEGORY_ID, "en", "Soap Dispensers", None],
         [332, CATEGORY_ID, "tr", "Sabunluklar", None],
         [333, CATEGORY_ID, "cs", "Dávkovače mýdla", None]],
    )

    dimensions = (
        ["id", "product_id", "variant_id", "width_mm", "height_mm", "depth_mm", "weight_kg",
         "created_at", "updated_at"],
        [[1, PRODUCT_ID, None, *DIMENSIONS, NOW, NOW]],
    )

    return {
        "media": media,
        "product_media": attachments,
        "product_translations": translations,
        "product_specifications": (
            ["id", "product_id", "variant_id", "spec_key", "spec_value", "unit", "sort_order",
             "created_at", "updated_at"],
            specs,
        ),
        "product_specification_translations": (
            ["id", "specification_id", "locale", "spec_key", "spec_value"],
            spec_translations,
        ),
        "product_card_features": (
            ["id", "product_id", "preset_id", "icon_key", "sort_order", "created_at", "updated_at"],
            features,
        ),
        "product_card_feature_translations": (
            ["id", "feature_id", "locale", "label", "feature_value"],
            feature_translations,
        ),
        "product_search_keywords": keywords,
        "product_dimensions": dimensions,
        "categories": categories,
        "category_translations": category_translations,
    }


# --------------------------------------------------------------------------- #
# SQLite runtime file
# --------------------------------------------------------------------------- #
def patch_sqlite() -> None:
    db = sqlite3.connect(DB)

    db.execute("UPDATE media SET status = 'missing', updated_at = ? WHERE id IN (?, ?, ?, ?)",
               (NOW, *OLD_MEDIA_IDS))
    db.execute("DELETE FROM product_media WHERE product_id = ?", (PRODUCT_ID,))
    for table, key, ids in (
        ("product_specification_translations", "specification_id",
         "SELECT id FROM product_specifications WHERE product_id = {pid}".format(pid=PRODUCT_ID)),
        ("product_specifications", "product_id", PRODUCT_ID),
        ("product_card_feature_translations", "feature_id",
         "SELECT id FROM product_card_features WHERE product_id = {pid}".format(pid=PRODUCT_ID)),
        ("product_card_features", "product_id", PRODUCT_ID),
        ("product_search_keywords", "product_id", PRODUCT_ID),
        ("product_dimensions", "product_id", PRODUCT_ID),
        ("category_translations", "category_id", CATEGORY_ID),
    ):
        if isinstance(ids, str):
            db.execute(f"DELETE FROM {table} WHERE {key} IN ({ids})")
        else:
            db.execute(f"DELETE FROM {table} WHERE {key} = ?", (ids,))

    for table, (columns, table_rows) in rows().items():
        placeholders = ", ".join("?" for _ in columns)
        column_sql = ", ".join(columns)
        for row in table_rows:
            db.execute(f"INSERT OR REPLACE INTO {table} ({column_sql}) VALUES ({placeholders})", row)

    db.execute(
        "UPDATE product_translations SET name = ?, short_description = ?, description = ? "
        "WHERE product_id = ? AND locale = 'en'",
        (NAMES["en"], SHORT["en"], DESCRIPTION["en"], PRODUCT_ID),
    )
    db.execute(
        "UPDATE products SET slug = 'v-0726c', status = 'active', brand_id = 1, sort_order = 714, "
        "is_featured = 1, is_new = 1, updated_at = ? WHERE id = ?", (NOW, PRODUCT_ID))
    db.execute("UPDATE product_variants SET sku = 'V-0726C', status = 'active', updated_at = ? "
               "WHERE product_id = ?", (NOW, PRODUCT_ID))
    db.execute("UPDATE products SET status = 'draft', updated_at = ? WHERE id = ?", (NOW, DUPLICATE_ID))

    db.execute("UPDATE categories SET is_featured = 1, updated_at = ? WHERE id = ?", (NOW, CATEGORY_ID))

    db.commit()
    db.close()


# --------------------------------------------------------------------------- #
# SQL dumps
# --------------------------------------------------------------------------- #
def literal(value: object) -> str:
    if value is None:
        return "NULL"
    if isinstance(value, (int, float)):
        return str(value)
    text = str(value).replace("\\", "\\\\").replace("'", "\\'").replace("\n", "\\n")
    return f"'{text}'"


def drop_rows(sql: str, table: str, ids: tuple[int, ...], product_id: int | None = None) -> str:
    """Remove rows from either dump shape.

    Compact dumps keep one complete INSERT per line; pretty dumps keep one
    tuple per line inside multi-row INSERTs. Rows are matched inside the
    table's own blocks only, because ids repeat across unrelated tables.
    """
    lines = sql.split("\n")
    header = f"INSERT INTO `{table}`"

    for row_id in ids:
        lookahead = f"({row_id}, {product_id}," if product_id is not None else f"({row_id},"
        in_block = False
        removed = False

        for index, line in enumerate(lines):
            if line is None:
                continue
            if in_block:
                matched = line.startswith(lookahead)
                if line.rstrip().endswith(";"):
                    in_block = False
            elif line.startswith(header):
                matched = f"VALUES {lookahead}" in line
                in_block = not line.rstrip().endswith(";")
            else:
                continue

            if matched:
                lines[index] = None
                removed = True
                break

        if not removed:
            raise SystemExit(f"{table}: cannot drop row {row_id}")

    return "\n".join(line for line in lines if line is not None)


def add_rows(sql: str, table: str, columns: list[str], table_rows: list[list[object]]) -> str:
    """Append rows to the table's last INSERT block, in either dump shape.

    Tables that had no rows yet have no block at all, so one is created right
    after the table's own `-- data:` marker - never at the end of the file.
    """
    header = f"INSERT INTO `{table}` ({', '.join('`' + c + '`' for c in columns)}) VALUES"
    rendered = [", ".join(literal(value) for value in row) for row in table_rows]
    # the compact dump keeps one complete INSERT per line
    single = [f"{header} ({row});" for row in rendered]
    lines = sql.split("\n")
    indexes = [i for i, line in enumerate(lines) if line.startswith(header)]

    if not indexes:
        marker = f"-- data: {table} ("
        marker_index = next((i for i, line in enumerate(lines) if line.startswith(marker)), None)
        if marker_index is None:
            # this dump never emitted a data section for the table: write the
            # block just above its index definitions instead
            marker_index = next((i for i, line in enumerate(lines)
                                 if line.strip() == f"-- Indexes for table `{table}`"), None)
            if marker_index is None:
                raise SystemExit(f"{table}: no data section to write into")
            marker_index -= 2  # step back over the "--" rule above the comment
        lines[marker_index + 1:marker_index + 1] = [""] + single
        return collapse_blanks("\n".join(lines))

    start, end = indexes[-1], indexes[-1]
    while not lines[end].rstrip().endswith(";"):
        end += 1

    if end == start:
        lines[end + 1:end + 1] = single
    else:
        lines[end] = lines[end].rstrip()[:-1] + ","
        block = [f"({row})" + ("," if offset < len(rendered) - 1 else ";")
                 for offset, row in enumerate(rendered)]
        lines[end + 1:end + 1] = block

    return collapse_blanks("\n".join(lines))


def collapse_blanks(sql: str) -> str:
    """A written block leaves one blank line, the way the dumps space things."""
    return re.sub(r"\n{3,}", "\n\n", sql)


def patch_dump(path: Path, stale: dict[str, list[int]]) -> None:
    sql = path.read_text(encoding="utf-8")
    if "'v-0726c-hero.jpg'" in sql:
        return

    sql = _retire_uploads(sql)
    # every child row this product already had, then the fresh set
    for table, ids in stale.items():
        ids = tuple(sorted(set(ids)))
        if ids:
            sql = drop_rows(sql, table, ids)
    sql = drop_rows(sql, "categories", (CATEGORY_ID,))
    sql = drop_rows(sql, "category_translations", (328,))

    for table, (columns, table_rows) in rows().items():
        sql = add_rows(sql, table, columns, table_rows)

    # the existing English translation arrived empty; fill it in place
    sql = _refresh_english_translation(sql)

    # the live product row: real brand, live status, showcase flag
    sql = _refresh_product_row(sql)

    path.write_text(_tidy(sql), encoding="utf-8")


def _retire_uploads(sql: str) -> str:
    """Mark the four lost uploads `missing` in place, keeping the record intact."""
    lines = sql.split("\n")
    for media_id in RETIRED_UPLOADS:
        for index, line in enumerate(lines):
            if line.startswith(f"({media_id}, NULL, '{CATEGORY_SLUG}',") or (
                    line.startswith("INSERT INTO `media`") and f"VALUES ({media_id}, NULL, '{CATEGORY_SLUG}'," in line):
                lines[index] = line.replace(", 'active', '", ", 'missing', '")
                break
        else:
            raise SystemExit(f"media: cannot find row {media_id}")

    return "\n".join(lines)


def _tidy(sql: str) -> str:
    """Keep the `-- data: <table> (N rows)` counters matching the file itself."""
    for table in set(re.findall(r"(?m)^-- data: (\w+) \(\d+ rows\)$", sql)):
        if f"INSERT INTO `{table}`" not in sql:
            count = 0
        elif re.search(rf"(?m)^INSERT INTO `{table}` \([^)]*\) VALUES \(\d+,", sql):
            count = len(re.findall(rf"(?m)^INSERT INTO `{table}` \([^)]*\) VALUES \(", sql))
        else:
            count = _count_pretty_rows(sql, table)
        sql = re.sub(rf"(?m)^-- data: {table} \(\d+ rows\)$", f"-- data: {table} ({count} rows)", sql)

    return sql


def _count_pretty_rows(sql: str, table: str) -> int:
    """Count one-tuple-per-line rows inside every INSERT block of `table`."""
    header = f"INSERT INTO `{table}`"
    total, in_block = 0, False
    for line in sql.split("\n"):
        if in_block:
            total += 1
            if line.rstrip().endswith(";"):
                in_block = False
        elif line.startswith(header):
            in_block = not line.rstrip().endswith(";")

    return total


def _refresh_product_row(sql: str) -> str:
    """Rewrite product 714's own row: brand, live status, showcase flag."""
    columns = ["id", "model_code", "slug", "category_id", "collection_id", "brand_id", "status",
               "is_featured", "is_new", "sort_order", "created_at", "updated_at", "deleted_at"]
    # kept from the original row so the record still looks created when it was
    row = [PRODUCT_ID, "V-0726C", "v-0726c", CATEGORY_ID, None, 1, "active", 1, 1, PRODUCT_ID,
           "2026-10-31 06:47:48", NOW, None]
    values = "(" + ", ".join(literal(value) for value in row) + ")"

    header = f"INSERT INTO `products` ({', '.join('`' + c + '`' for c in columns)}) VALUES "
    lines = sql.split("\n")
    for index, line in enumerate(lines):
        if line.startswith(header + f"({PRODUCT_ID},"):
            lines[index] = header + values + ";"
            return "\n".join(lines)
        if line.startswith(f"({PRODUCT_ID}, 'V-0726C', 'v-0726c',"):
            lines[index] = values + ("," if line.rstrip().endswith(",") else ";")
            return "\n".join(lines)

    raise SystemExit("products: cannot find the row for 714")


def _refresh_english_translation(sql: str) -> str:
    """Fill the English name/short description/description already on file."""
    replacement = ", ".join(literal(value) for value in
                            (2140, PRODUCT_ID, "en", NAMES["en"], SHORT["en"], DESCRIPTION["en"]))
    lines = sql.split("\n")
    header = f"INSERT INTO `product_translations`"
    lookahead = f"(2140, {PRODUCT_ID}, 'en'"

    for index, line in enumerate(lines):
        if line.startswith(lookahead):
            lines[index] = f"({replacement})," if line.rstrip().endswith(",") else f"({replacement});"
            return "\n".join(lines)
        if line.startswith(header) and f"VALUES {lookahead}" in line:
            prefix = line.split(f"VALUES {lookahead}")[0] + "VALUES "
            lines[index] = f"{prefix}({replacement});"
            return "\n".join(lines)

    raise SystemExit("product_translations: cannot find the English row for 714")


def stale_ids() -> dict[str, list[int]]:
    """Ids the current database already holds for 714, so the dumps can drop
    exactly those rows before the fresh set is written. 715 keeps its own."""
    db = sqlite3.connect(DB)
    out = {table: [row[0] for row in db.execute(
        f"SELECT id FROM {table} WHERE product_id = ?", (PRODUCT_ID,))]
        for table in ("product_media", "product_specifications", "product_card_features",
                      "product_search_keywords", "product_dimensions")}
    # the English translation is filled in place, only the localized rows are replaced
    out["product_translations"] = [row[0] for row in db.execute(
        "SELECT id FROM product_translations WHERE product_id = ? AND locale <> 'en'", (PRODUCT_ID,))]

    spec_ids = ",".join(str(i) for i in out["product_specifications"]) or "0"
    feature_ids = ",".join(str(i) for i in out["product_card_features"]) or "0"
    out["product_specification_translations"] = [row[0] for row in db.execute(
        f"SELECT id FROM product_specification_translations WHERE specification_id IN ({spec_ids})")]
    out["product_card_feature_translations"] = [row[0] for row in db.execute(
        f"SELECT id FROM product_card_feature_translations WHERE feature_id IN ({feature_ids})")]
    db.close()

    return out


def main() -> None:
    stale = stale_ids()
    patch_sqlite()
    for dump in DUMPS:
        patch_dump(dump, stale)
    print("patched sqlite and both dumps")


if __name__ == "__main__":
    main()
