"""Why is a product missing from the storefront?

Runs the exact SQL gate the catalog uses (ProductVisibility::mediaReadySql())
against database/lufly.sqlite and lists every active product that is hidden,
with the reason.

    python3 tools/audit/why_hidden.py
"""

import os
import sqlite3
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
DB = os.path.join(ROOT, 'database/lufly.sqlite')
PUBLIC = os.path.join(ROOT, 'public')

QUERY = """
SELECT p.id, p.model_code, p.status, cat.slug AS category,
  EXISTS (SELECT 1 FROM product_media pm INNER JOIN media m ON m.id = pm.media_id
          WHERE pm.product_id = p.id AND pm.variant_id IS NULL
            AND pm.type NOT IN ('drawing','situ') AND m.status NOT IN ('missing','hidden')) AS has_photo_row,
  EXISTS (SELECT 1 FROM product_media pm INNER JOIN media m ON m.id = pm.media_id
          WHERE pm.product_id = p.id AND pm.variant_id IS NULL
            AND pm.type = 'drawing' AND m.status NOT IN ('missing','hidden')) AS has_drawing_row,
  (SELECT m.path FROM product_media pm INNER JOIN media m ON m.id = pm.media_id
     WHERE pm.product_id = p.id AND pm.variant_id IS NULL
       AND pm.type NOT IN ('drawing','situ')
     ORDER BY pm.is_primary DESC, pm.sort_order ASC, pm.id ASC LIMIT 1) AS first_photo_path,
  EXISTS (SELECT 1 FROM product_relations pr WHERE pr.related_product_id = p.id
            AND pr.relation_type IN ('seat_cover','seat_cover_only')) AS is_seat_cover
FROM products p
LEFT JOIN categories cat ON cat.id = p.category_id
WHERE p.deleted_at IS NULL AND p.status IN ('active','coming_soon')
"""


def file_exists(path):
    return bool(path) and os.path.exists(os.path.join(PUBLIC, path.lstrip('/')))


def main():
    db = sqlite3.connect(DB)
    db.row_factory = sqlite3.Row
    rows = [dict(r) for r in db.execute(QUERY)]

    shown, hidden = [], []
    for r in rows:
        r['photo_file'] = file_exists(r['first_photo_path'])
        public = r['has_photo_row'] and r['has_drawing_row'] and not r['is_seat_cover']
        (shown if public else hidden).append(r)

    print(f"active products            : {len(rows)}")
    print(f"shown by the catalog today : {len(shown)}")
    print(f"hidden                     : {len(hidden)}\n")

    buckets = {
        'seat cover (hidden on purpose)': lambda r: r['is_seat_cover'],
        'no media at all': lambda r: not r['has_photo_row'] and not r['has_drawing_row'],
        'photo only, NO technical drawing (file is on disk)':
            lambda r: r['has_photo_row'] and r['photo_file'] and not r['has_drawing_row'],
        'photo row points at a file that is not on disk':
            lambda r: not r['has_photo_row'] and not r['is_seat_cover'],
        'other': lambda r: True,
    }
    remaining = list(hidden)
    for name, test in buckets.items():
        group = [r for r in remaining if test(r)]
        remaining = [r for r in remaining if r not in group]
        if not group:
            continue
        print(f"{name}: {len(group)}")
        for r in group[:8]:
            print(f"    {str(r['model_code']):<18} {r['category']}")
        if len(group) > 8:
            print(f"    ... and {len(group) - 8} more")
        print()

    # What a "usable photo on disk" rule would show instead.
    by_file = [r for r in rows if r['photo_file'] and not r['is_seat_cover']]
    print(f"rule = a photo file exists on disk -> {len(by_file)} shown "
          f"({len(by_file) - len(shown):+d} vs today)")
    by_row = [r for r in rows if r['has_photo_row'] and not r['is_seat_cover']]
    print(f"rule = a photo row exists          -> {len(by_row)} shown "
          f"({len(by_row) - len(shown):+d} vs today)")
    return 0


if __name__ == '__main__':
    sys.exit(main())
