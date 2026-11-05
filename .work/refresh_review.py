"""Rebuild review/ so it shows the ORIGINAL photos (reverted state), still numbered."""
import json, sqlite3, shutil
from pathlib import Path
from PIL import Image

REV = Path('review'); IMG = REV / 'img'
DB = sqlite3.connect('database/lufly.sqlite')

# authoritative gallery order (same as the live /en/products?category=kitchen grid)
order = [l.strip() for l in open('/tmp/kitchen_media.txt') if l.strip()]
# map slug -> primary path for the 24 card products, in the recorded site order
site = """1653-8033c 1654-0760 4000-270a 4000-277a 4000-359c lxd-6005 4000-288
1644-8002gg 1644-8033mb 4000-7644a ga-00403 3001-1600 1644-8003c 1653-7005gg
lxd-6010 3001-1630 1644-8004rg 1644-7004g 1644-932cg 1644-7005gg 1644-8037
1644-7062 1644-7069 1644-8035c""".split()

items = []
for n, slug in enumerate(site, 1):
    row = DB.execute("""
        select m.path from products p
        join product_media pm on pm.product_id=p.id and pm.is_primary=1
        join media m on m.id=pm.media_id
        where p.slug=? limit 1""", (slug,)).fetchone()
    if row is None:
        raise SystemExit(f'no primary media for {slug}')
    src = Path('public') / row[0].lstrip('/')
    items.append({'n': n, 'slug': slug, 'file': f'img/{n:02d}.webp',
                  'src': str(src), 'w': 0, 'h': 0})

for it in items:
    with Image.open(it['src']) as im:
        it['w'], it['h'] = im.width, im.height
        im.convert('RGB').save(IMG / f"{it['n']:02d}.webp", 'WEBP', quality=90, method=6)

(REV / 'items.json').write_text(json.dumps(
    [{k: v for k, v in i.items() if k != 'src'} for i in items], ensure_ascii=False, indent=1))
print('items:', len(items), '| sizes:', {i['slug']: (i['w'], i['h']) for i in items[:4]})
