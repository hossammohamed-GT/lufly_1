#!/usr/bin/env python3
"""Locale data integrity test - the "language mixing" regression guard.

Guards the fix for products showing the wrong language per locale (29 EN rows
were verbatim Czech copies). Verifies, directly against the source of truth
(database/lufly.sqlite) and the hosting copy (lufly-database.sql at the repo root, the hosting copy):

  1. every product has exactly one translation row per supported locale
     (en / tr / cs) in product_translations and seo_meta;
  2. category_translations covers every category in every locale;
  3. no row carries another language's markers:
       - en rows: no Czech diacritics, no Turkish letters, no "Boyut"/"Kod";
       - tr rows: no Czech diacritics;
       - cs rows: no Turkish letters, no "Boyut"/"Kod";
  4. seo titles follow "<translated name> | LUFLY" in every locale.

Run:  python3 tools/frontend_audit/locale_data_test.py
Exit: 0 = all clean, 1 = any check failed (a summary line lists the failures).
"""
from __future__ import annotations

import re
import sqlite3
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SQLITE = ROOT / "database" / "lufly.sqlite"
DUMP = ROOT / "lufly-database.sql"

LOCALES = ("en", "tr", "cs")
# Letters unique to Czech (Turkish shares none of these, English has none).
CZECH = re.compile(r"[ěščřžůúťďňáíéýĚŠČŘŽŮÚŤĎŇÁÍÉÝ]")
# Czech markers for Turkish rows: "á" is excluded because the Turkish data
# legitimately uses it in loanwords such as "perlátor" (verified: every Czech
# letter except á flags zero Turkish rows, so this stays a strict check).
CZECH_IN_TR = re.compile(r"[ěščřžůúťďňíéýĚŠČŘŽŮÚŤĎŇÍÉÝ]")
# Letters unique to Turkish (dotless/dotted i, g-breve, s-cedilla).
TURKISH = re.compile(r"[ğışİŞ]")
# Turkish words that leaked into non-Turkish rows during the original import.
TURKISH_WORDS = re.compile(r"\bBoyut\b|\bKod\b")

failures: list[str] = []


def check(ok: bool, message: str) -> None:
    print(f"{'ok  ' if ok else 'FAIL'}  {message}")
    if not ok:
        failures.append(message)


def wrong_language(text: str | None, locale: str) -> str | None:
    """Return a description if `text` carries markers of the wrong language."""
    if text is None or text == "":
        return None
    if locale == "en" and (CZECH.search(text) or TURKISH.search(text) or TURKISH_WORDS.search(text)):
        return "Czech/Turkish text in an English row"
    if locale == "tr" and CZECH_IN_TR.search(text):
        return "Czech text in a Turkish row"
    if locale == "cs" and (TURKISH.search(text) or TURKISH_WORDS.search(text)):
        return "Turkish text in a Czech row"
    return None


def test_sqlite() -> None:
    con = sqlite3.connect(f"file:{SQLITE}?mode=ro", uri=True)
    cur = con.cursor()

    products = cur.execute("SELECT COUNT(*) FROM products").fetchone()[0]

    # 1. one translation row per product per locale, no extras
    rows = cur.execute(
        "SELECT locale, COUNT(*) FROM product_translations GROUP BY locale"
    ).fetchall()
    counts = dict(rows)
    for loc in LOCALES:
        check(counts.get(loc) == products,
              f"product_translations[{loc}] covers all {products} products (found {counts.get(loc, 0)})")
    check(len(rows) == len(LOCALES), f"product_translations has exactly the locales {LOCALES}")

    seo_rows = cur.execute(
        "SELECT locale, COUNT(*) FROM seo_meta WHERE product_id IS NOT NULL GROUP BY locale"
    ).fetchall()
    seo_counts = dict(seo_rows)
    for loc in LOCALES:
        check(seo_counts.get(loc) == products,
              f"seo_meta[{loc}] covers all {products} products (found {seo_counts.get(loc, 0)})")

    cats = cur.execute("SELECT COUNT(*) FROM categories").fetchone()[0]
    cat_rows = cur.execute(
        "SELECT locale, COUNT(*) FROM category_translations GROUP BY locale"
    ).fetchall()
    cat_counts = dict(cat_rows)
    for loc in LOCALES:
        check(cat_counts.get(loc) == cats,
              f"category_translations[{loc}] covers all {cats} categories (found {cat_counts.get(loc, 0)})")

    # 2. no row carries another language's markers
    bad = 0
    for product_id, locale, name, sd, desc in cur.execute(
        "SELECT product_id, locale, name, short_description, description FROM product_translations"
    ):
        for field, value in (("name", name), ("short_description", sd), ("description", desc)):
            why = wrong_language(value, locale)
            if why:
                bad += 1
                if bad <= 5:
                    print(f"      -> p{product_id}.{locale}.{field}: {why}: {str(value)[:60]!r}")
    check(bad == 0, f"no wrong-language product translations (bad rows: {bad})")

    # 3. seo titles match the translated name per locale
    bad = 0
    for product_id, locale, title, name in cur.execute(
        "SELECT s.product_id, s.locale, s.meta_title, t.name FROM seo_meta s "
        "JOIN product_translations t ON t.product_id = s.product_id AND t.locale = s.locale "
        "WHERE s.product_id IS NOT NULL"
    ):
        if title != f"{name} | LUFLY" or wrong_language(title, locale):
            bad += 1
            if bad <= 5:
                print(f"      -> p{product_id}.{locale}: title={title!r} name={name!r}")
    check(bad == 0, f"seo titles follow '<translated name> | LUFLY' per locale (bad rows: {bad})")

    con.close()


def dump_statements(text: str) -> list[str]:
    """Split the dump into INSERT statements.

    Values can contain raw newlines, ''-escaped quotes and backslash-escaped
    quotes (\\'), so statements cannot be read line-by-line: track
    single-quoted strings and end each statement at a ");" outside a string.
    A quote is escaped - and stays inside the string - when preceded by an
    odd number of backslashes or immediately followed by another quote.
    """
    statements: list[str] = []
    start = None
    in_string = False
    i = 0
    while i < len(text):
        ch = text[i]
        if ch == "\\":
            i += 2  # skip any escaped character, in or out of a string
            continue
        if in_string:
            if ch == "'":
                if i + 1 < len(text) and text[i + 1] == "'":
                    i += 2  # ''-escaped quote, still inside the string
                    continue
                in_string = False
        elif ch == "'":
            in_string = True
        elif start is None and text.startswith("INSERT INTO", i):
            start = i
        elif start is not None and ch == ")" and text.startswith(");\n", i):
            statements.append(text[start:i + 3])
            start = None
        i += 1
    return statements


def test_dump() -> None:
    """The hosting copy must be just as clean - it is what gets restored."""
    table_re = re.compile(
        r"^INSERT INTO `(product_translations|seo_meta|category_translations)` "
    )
    locale_re = re.compile(r"', '(en|tr|cs)', '")
    bad = 0
    seen: dict[str, int] = {}
    for stmt in dump_statements(DUMP.read_text(encoding="utf-8")):
        m = table_re.match(stmt)
        if not m:
            continue
        table = m.group(1)
        seen[table] = seen.get(table, 0) + 1
        lm = locale_re.search(stmt)
        if not lm:
            continue
        locale = lm.group(1)
        why = wrong_language(stmt, locale)
        if why:
            bad += 1
            if bad <= 5:
                print(f"      -> {table}[{locale}]: {stmt[:80]!r}")
    check(bad == 0, f"hosting dump has no wrong-language rows (bad rows: {bad})")
    check(seen.get("product_translations", 0) == 840,
          f"hosting dump carries 840 product_translations rows (found {seen.get('product_translations', 0)})")


def main() -> int:
    print(f"locale data test - {SQLITE.relative_to(ROOT)} + {DUMP.relative_to(ROOT)}")
    test_sqlite()
    test_dump()
    print()
    if failures:
        print(f"FAILED: {len(failures)} check(s) did not pass")
        for f in failures:
            print(f"  - {f}")
        return 1
    print("PASSED: all locales carry their own language everywhere")
    return 0


if __name__ == "__main__":
    sys.exit(main())
