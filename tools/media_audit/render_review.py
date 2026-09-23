#!/usr/bin/env python3
"""Render a self-contained HTML review sheet of the image sections.

    python3 tools/media_audit/render_review.py --out storage/reports/media-sections-preview.html

The page mirrors what the admin panel shows per product (photos / technical
drawings / installed) with every thumbnail embedded as a data URI, so it can be
opened straight from the file system - no PHP, no web server, no network.
"""
from __future__ import annotations

import argparse
import base64
import csv
import html as html_module
import io
import sqlite3
from collections import defaultdict
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
DB_PATH = ROOT / "database/lufly.sqlite"
PUBLIC = ROOT / "public"
REPORT_PATH = ROOT / "storage/reports/media-classification.csv"

THUMB = 130
QUALITY = 72


def esc(value: object) -> str:
    """Escape text coming out of the database for direct HTML embedding."""
    return html_module.escape(str(value if value is not None else ""), quote=True)


SECTIONS = [
    ("photos", "Photos", "الصور العادية"),
    ("drawings", "Technical drawings", "الدروينج"),
    ("situ", "Installed", "الصورة على الموقع"),
]


def thumbnail(path: Path) -> str:
    with Image.open(path) as image:
        image = image.convert("RGB")
        image.thumbnail((THUMB, THUMB), Image.LANCZOS)
        buffer = io.BytesIO()
        image.save(buffer, format="JPEG", quality=QUALITY, optimize=True)

    return "data:image/jpeg;base64," + base64.b64encode(buffer.getvalue()).decode()


def section_of(media_type: str) -> str:
    return {"drawing": "drawings", "situ": "situ"}.get(media_type, "photos")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--out", default=str(ROOT / "storage/reports/media-sections-preview.html"))
    parser.add_argument("--report", default=str(REPORT_PATH))
    parser.add_argument("--filter", default="", help="only products whose slug contains this text")
    args = parser.parse_args()

    connection = sqlite3.connect(DB_PATH)
    connection.row_factory = sqlite3.Row
    products = connection.execute(
        """
        SELECT p.id, p.slug, p.model_code, p.status,
               (SELECT name FROM product_translations t WHERE t.product_id = p.id AND t.locale = 'en') AS name
        FROM products p
        WHERE p.deleted_at IS NULL
        ORDER BY p.id ASC
        """
    ).fetchall()

    plan = list(csv.DictReader(Path(args.report).open(encoding="utf-8")))

    grouped: dict[int, list[dict]] = defaultdict(list)
    for row in plan:
        grouped[int(row["product_id"])].append(row)
    for rows in grouped.values():
        rows.sort(key=lambda r: (r["new_type"] not in ("main", "gallery"), int(r["new_sort"])))

    moved = [row for row in plan if row["current_type"] != row["new_type"]]

    cards: list[str] = []
    for product in products:
        rows = grouped.get(int(product["id"]), [])
        if args.filter and args.filter not in str(product["slug"]):
            continue

        counts = {key: sum(1 for r in rows if section_of(r["new_type"]) == key) for key, _, _ in SECTIONS}
        by_section: dict[str, list[str]] = {key: [] for key, _, _ in SECTIONS}

        for row in rows:
            file = PUBLIC / str(row["path"]).lstrip("/")
            if not file.is_file():
                continue
            badge = "main" if row["new_type"] == "main" else row["new_type"]
            by_section[section_of(row["new_type"])].append(
                f'<figure class="thumb is-{badge}">'
                f'<img src="{thumbnail(file)}" alt="" loading="lazy">'
                f"<figcaption>{badge} · {float(row['survival']):.2f}</figcaption>"
                f"</figure>"
            )

        columns = []
        for key, label, arabic in SECTIONS:
            thumbs = "".join(by_section[key]) or '<p class="empty">—</p>'
            columns.append(
                f'<div class="section section-{key}">'
                f'<h4>{label} <span class="count">{counts[key]}</span>'
                f'<span class="ar" dir="rtl">{arabic}</span></h4>'
                f'<div class="thumbs">{thumbs}</div></div>'
            )

        warning = ""
        if counts["photos"] == 0:
            warning = '<span class="warn">no photo — needs one</span>'
        elif counts["photos"] > 1:
            warning = f'<span class="ok">{counts["photos"]} photos</span>'

        cards.append(
            f'<article class="product">'
            f'<header><b>#{int(product["id"])}</b> '
            f'<code>{esc(product["model_code"])}</code> '
            f'<span class="slug">{esc(product["slug"])}</span> '
            f'<span class="name">{esc(product["name"])}</span> {warning}</header>'
            f'<div class="sections">{"".join(columns)}</div>'
            f"</article>"
        )

    total_photos = sum(1 for r in plan if r["new_type"] in ("main", "gallery"))
    total_drawings = sum(1 for r in plan if r["new_type"] == "drawing")
    without_photo = [p for p in products if not any(
        section_of(r["new_type"]) == "photos" for r in grouped.get(int(p["id"]), [])
    )]

    html = f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>LUFLY product images — sections audit</title>
<style>
  :root {{ color-scheme: light; }}
  * {{ box-sizing: border-box; }}
  body {{ margin: 0; padding: 24px; font: 14px/1.5 -apple-system, "Segoe UI", Roboto, sans-serif; background: #f6f7f9; color: #1f2933; }}
  h1 {{ font-size: 22px; margin: 0 0 4px; }}
  h2 {{ font-size: 16px; margin: 28px 0 8px; }}
  .lede {{ color: #52606d; max-width: 900px; }}
  .stats {{ display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0 8px; }}
  .stat {{ background: #fff; border: 1px solid #d9dee5; border-radius: 8px; padding: 8px 12px; }}
  .stat b {{ display: block; font-size: 20px; }}
  .stat span {{ color: #52606d; font-size: 12px; }}
  .alerts {{ background: #fff5e6; border: 1px solid #f0b429; border-radius: 8px; padding: 10px 14px; margin: 12px 0; }}
  .toolbar {{ display: flex; gap: 8px; flex-wrap: wrap; margin: 16px 0; position: sticky; top: 0; background: #f6f7f9; padding: 8px 0; z-index: 2; }}
  button {{ font: inherit; padding: 5px 12px; border-radius: 999px; border: 1px solid #c3ccd5; background: #fff; cursor: pointer; }}
  button.is-on {{ background: #0f766e; border-color: #0f766e; color: #fff; }}
  .product {{ background: #fff; border: 1px solid #d9dee5; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; }}
  .product header {{ display: flex; gap: 10px; align-items: baseline; flex-wrap: wrap; margin-bottom: 10px; }}
  .product code {{ background: #eef1f5; border-radius: 4px; padding: 1px 6px; }}
  .slug, .name {{ color: #52606d; font-size: 13px; }}
  .warn {{ background: #fde8e8; color: #9b1c1c; border-radius: 4px; padding: 1px 6px; font-size: 12px; }}
  .ok {{ background: #def7ec; color: #03543f; border-radius: 4px; padding: 1px 6px; font-size: 12px; }}
  .sections {{ display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }}
  .section {{ border: 1px dashed #d9dee5; border-radius: 8px; padding: 8px; min-height: 90px; }}
  .section h4 {{ margin: 0 0 8px; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #52606d; display: flex; gap: 6px; align-items: center; }}
  .section h4 .count {{ background: #eef1f5; border-radius: 999px; padding: 0 7px; color: #1f2933; }}
  .section h4 .ar {{ font-size: 12px; color: #7b8794; text-transform: none; letter-spacing: 0; }}
  .thumbs {{ display: flex; flex-wrap: wrap; gap: 6px; }}
  .thumb {{ margin: 0; border: 1px solid #e4e7eb; border-radius: 6px; overflow: hidden; background: #fff; }}
  .thumb img {{ display: block; max-width: {THUMB}px; max-height: {THUMB}px; }}
  .thumb figcaption {{ font-size: 10px; color: #7b8794; text-align: center; padding: 2px 0; background: #f8fafc; }}
  .thumb.is-main figcaption {{ background: #0f766e; color: #fff; }}
  .thumb.is-drawing figcaption {{ background: #eef1f5; color: #3e4c59; }}
  .empty {{ color: #9aa5b1; margin: 0; font-size: 12px; }}
  body.hide-drawings .section-drawings {{ display: none; }}
  body.only-photos .section:not(.section-photos) {{ display: none; }}
  body.only-problems .product:not(.has-problem) {{ display: none; }}
</style>
</head>
<body>
<h1>LUFLY product images — which section every file now fills</h1>
<p class="lede" dir="rtl">كل منتج: صورته فى قسم «الصور العادية»، وكل باقى الملفات اتنقلت لقسم «الدروينج». الصفحة دى عرض للنتيجة النهائية بعد إعادة التصنيف.</p>
<p class="lede">Regenerated by <code>tools/media_audit/render_review.py</code> from
<code>storage/reports/media-classification.csv</code>. Thumbnails are embedded, so this file opens anywhere.</p>

<div class="stats">
  <div class="stat"><b>{len(products)}</b><span>products</span></div>
  <div class="stat"><b>{len(plan)}</b><span>attached images</span></div>
  <div class="stat"><b>{total_photos}</b><span>photos (main + gallery)</span></div>
  <div class="stat"><b>{total_drawings}</b><span>technical drawings</span></div>
  <div class="stat"><b>{len(moved)}</b><span>files re-filed by this audit</span></div>
</div>

<div class="alerts">
  <b>{len(without_photo)} products still have no photo at all</b> — only drawings are on file:
  {", ".join(f"#{int(p['id'])} {esc(p['model_code'])}" for p in without_photo)}.
  They show a single "Drawing" tab until a real photo is uploaded from Admin → Products → edit.
</div>

<div class="toolbar">
  <button type="button" class="is-on" data-view="all">All sections</button>
  <button type="button" data-view="hide-drawings">Hide drawings</button>
  <button type="button" data-view="only-photos">Photos only</button>
  <button type="button" data-view="only-problems">Only products needing a photo ({len(without_photo)})</button>
</div>

{"".join(cards)}
<script>
  const buttons = document.querySelectorAll('.toolbar button');
  const bodies = {{ all: '', 'hide-drawings': 'hide-drawings', 'only-photos': 'only-photos' }};
  for (const product of document.querySelectorAll('.product')) {{
    if (product.querySelector('.section-photos .empty') === null) continue;
    product.classList.add('has-problem');
  }}
  buttons.forEach((button) => button.addEventListener('click', () => {{
    buttons.forEach((other) => other.classList.remove('is-on'));
    button.classList.add('is-on');
    const view = button.dataset.view;
    document.body.className = view === 'hide-drawings' ? 'hide-drawings'
      : view === 'only-photos' ? 'only-photos'
      : view === 'only-problems' ? 'only-problems' : '';
  }}));
</script>
</body>
</html>
"""

    out = Path(args.out)
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(html, encoding="utf-8")
    print(f"wrote {out} ({out.stat().st_size / 1024:.0f} KB, {len(cards)} products)")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
