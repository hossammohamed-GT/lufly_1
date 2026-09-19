#!/usr/bin/env python3
"""Extract the REAL LUFLY catalog out of the legacy WordPress/WooCommerce dump.

Input : delete_files/lufly_new.sql  (posts, postmeta, term relationships)
        delete_files/chso_terms.sql (term names/slugs)
Output: database/seeders/data/categories.json
        database/seeders/data/products.json

No invented marketing copy: every field comes from the legacy database.
Missing pieces (tr translations, extra galleries) are simply left empty.
"""
import json, os, re, sys, unicodedata, collections
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from wp_parse import parse_dump

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
DUMP = os.path.join(ROOT, 'delete_files', 'lufly_new.sql')
TERMS = os.path.join(ROOT, 'delete_files', 'chso_terms.sql')
OUT = os.path.join(ROOT, 'database', 'seeders', 'data')
IMG_DIR = os.path.join(ROOT, 'images', 'products')

# legacy WooCommerce categories -> catalog slug + names
CATEGORY_MAP = {
    'bathroom':        ('bathroom-ceramics',  {'en': 'Bathroom Ceramics', 'cs': 'Koupelnová keramika'}),
    'kids':            ('kids',               {'en': 'Kids', 'cs': 'Dětský program'}),
    'washbasin-mixer': ('washbasin-mixers',   {'en': 'Washbasin Mixers', 'cs': 'Umyvadlové baterie'}),
    'sink-mixer':      ('sink-mixers',        {'en': 'Sink Mixers', 'cs': 'Dřezové baterie'}),
    'shower-set':      ('shower-sets',        {'en': 'Shower Sets', 'cs': 'Sprchové sety'}),
    'sensors':         ('sensor-products',    {'en': 'Sensor Products', 'cs': 'Senzorové produkty'}),
    'disabled':        ('accessible-range',   {'en': 'Accessible Range', 'cs': 'Bezbariérový program'}),
    'soft-porcelain':  ('soft-porcelain',     {'en': 'Soft Porcelain', 'cs': 'Jemný porcelán'}),
}
# purely organisational legacy terms we do not surface as catalog categories
IGNORED_TERMS = {'our-store', 'uncategorized', 'glass', 'kitchen', 'non-oxide', 'silicates'}

# real LUFLY model codes look like 1620-111, 1654-2220GG, 570-022C, V-1911L,
# GT-82221, HL-ST110, LXD-7012, SS-9762366, GTB-18-S-B
MODEL_RE = re.compile(
    r'\b\d{3,4}-[A-Z]{0,3}[0-9]{2,5}[A-Z]{0,3}\b'
    r'|\b(?:V|GT|GTB|HL|LXD|SS|BBW)-[0-9A-Z]+(?:-[0-9A-Z]+)*\b'
)
# demo-shop SKUs inherited from the purchased WordPress theme, never real
FAKE_SKU_RE = re.compile(r'^(EWO|WER|QAZ|XSW|ABC)-', re.I)
CZECH_CHARS = set('ěščřžýáíéúůďťňó')


def strip_html(value: str) -> str:
    value = re.sub(r'<br\s*/?>', '\n', value or '', flags=re.I)
    value = re.sub(r'</p>', '\n', value, flags=re.I)
    value = re.sub(r'<[^>]+>', '', value)
    value = value.replace('&nbsp;', ' ').replace('&#8217;', "'").replace('&amp;', '&')
    value = value.replace('\r\n', '\n').replace('\r', '\n')
    lines = [re.sub(r'[ \t]+', ' ', l).strip() for l in value.split('\n')]
    return '\n'.join(l for l in lines if l).strip()


def fix_spacing(text: str) -> str:
    """The legacy editor injected stray spaces inside Czech words (zav ěšená)."""
    text = re.sub(r'(?<=[a-zá-ž]) (?=[ěščřžýáíéúůďťň][a-zá-ž])', '', text)
    text = re.sub(r'(?<=[A-ZÁ-Ž]{2}) (?=[ĚŠČŘŽÝÁÍÉÚŮŇ][A-ZÁ-Ž])', '', text)
    return re.sub(r'\s{2,}', ' ', text).strip()


def detect_locale(text: str) -> str:
    low = (text or '').lower()
    if any(ch in CZECH_CHARS for ch in low):
        return 'cs'
    return 'en'


def resolve_file(post_id: str, legacy_file: str, on_disk: set, claimed: set) -> str:
    """Map a legacy upload path to its exported `prod_{id}_{file}` name.

    The exporter dropped WordPress' "-scaled" suffix on some files and
    re-encoded a few others, so try those variants before giving up and
    returning the canonical name (which then reports available=False).
    """
    basename = os.path.basename(legacy_file)
    root, ext = os.path.splitext(basename)
    roots = [root]
    if root.endswith('-scaled'):
        roots.append(root[: -len('-scaled')])

    for candidate_root in roots:
        for candidate_ext in dict.fromkeys([ext, '.jpg', '.jpeg', '.png', '.webp']):
            candidate = f'prod_{post_id}_{candidate_root}{candidate_ext}'
            # never let two different attachments resolve onto the same export
            if candidate in on_disk and candidate not in claimed:
                return candidate

    return f'prod_{post_id}_{basename}'


def slugify(value: str) -> str:
    value = unicodedata.normalize('NFKD', value).encode('ascii', 'ignore').decode()
    value = re.sub(r'[^a-zA-Z0-9]+', '-', value).strip('-').lower()
    return re.sub(r'-{2,}', '-', value)


def main() -> int:
    dump = parse_dump(DUMP)
    terms = {r['term_id']: r for r in parse_dump(TERMS)['chso_terms']}
    taxonomy = {r['term_taxonomy_id']: r for r in dump['chso_term_taxonomy']}

    rel = collections.defaultdict(list)
    for r in dump['chso_term_relationships']:
        rel[r['object_id']].append(r['term_taxonomy_id'])

    meta = collections.defaultdict(dict)
    for m in dump['chso_postmeta']:
        meta[m['post_id']][m['meta_key']] = m['meta_value']

    attachments = {p['ID']: meta[p['ID']].get('_wp_attached_file')
                   for p in dump['chso_posts'] if p['post_type'] == 'attachment'}

    on_disk = set(os.listdir(IMG_DIR)) if os.path.isdir(IMG_DIR) else set()
    by_post_id = collections.defaultdict(list)
    for f in sorted(on_disk):
        parts = f.split('_', 2)
        if len(parts) == 3 and parts[0] == 'prod':
            by_post_id[parts[1]].append(f)

    products, seen_slug = [], {}
    used_categories = {}

    for post in dump['chso_posts']:
        if post['post_type'] != 'product':
            continue
        if post['post_status'] not in ('publish', 'draft'):
            continue  # auto-draft / trash = not real catalog data

        pid = post['ID']
        pmeta = meta[pid]
        title = fix_spacing(strip_html(post['post_title']))
        excerpt = fix_spacing(strip_html(post['post_excerpt']))
        content = fix_spacing(strip_html(post['post_content']))

        # -- model code: prefer the code printed in the title/body (that is the
        # code LUFLY actually prints in its catalog), fall back to the SKU meta
        sku = (pmeta.get('_sku') or '').strip()
        if FAKE_SKU_RE.match(sku):
            sku = ''
        model = ''
        for candidate in (title, excerpt, content, sku):
            if not candidate:
                continue
            found = MODEL_RE.findall(candidate.replace('Kod:', ' '))
            if found:
                model = found[0].strip()
                break
        if not model and sku:
            model = sku

        # -- name: drop the trailing "| Kod: xxxx" marketing tail
        name = re.sub(r'\s*\|\s*(Kod:)?\s*[\w./-]+\s*$', '', title).strip(' |')
        name = re.sub(r'^\s*Kod:\s*[\w-]+\s*', '', name).strip()
        if not name or name.upper() == name and len(name) > 45:
            # legacy ALL-CAPS section headline: use the descriptive body line
            body_first = (content.split('\n') or [''])[-1] if content else ''
            name = fix_spacing(body_first) or title
        if not name:
            name = model or ('LUFLY ' + pid)
        if name[:1].islower():
            name = name[0].upper() + name[1:]
        if model and model not in name:
            name = f'{name} {model}'.strip()

        locale = detect_locale(name + ' ' + excerpt + ' ' + content)

        def clean_body(text: str) -> str:
            lines = []
            for line in (text or '').split('\n'):
                line = re.sub(r'^\s*(<strong>)?\s*Kod:\s*[\w./-]+\s*$', '', line).strip()
                if not line:
                    continue
                # drop the legacy ALL-CAPS section banner
                letters = [c for c in line if c.isalpha()]
                if len(letters) > 25 and all(c.isupper() for c in letters):
                    continue
                lines.append(line)
            return '\n'.join(lines).strip()

        description = clean_body(content) or clean_body(excerpt)
        short = clean_body(excerpt)
        if short == description:
            short = description.split('\n')[0]
        if not short and description:
            short = description.split('\n')[0]

        # -- categories
        cats = []
        for ttid in rel[pid]:
            tt = taxonomy.get(ttid)
            if not tt or tt['taxonomy'] != 'product_cat':
                continue
            term = terms.get(tt['term_id'])
            if not term or term['slug'] in IGNORED_TERMS:
                continue
            mapped = CATEGORY_MAP.get(term['slug'])
            if not mapped:
                mapped = (slugify(term['name']), {'en': term['name']})
            used_categories[mapped[0]] = mapped[1]
            cats.append(mapped[0])
        cats = list(dict.fromkeys(cats))

        # -- images: the full set the legacy product references, in order.
        # [0] = featured thumbnail, then the _product_image_gallery ids.
        # Files whose name contains "drawing" are the technical drawings.
        images = []
        seen_attachment = set()
        matched_files = set()
        thumb = (pmeta.get('_thumbnail_id') or '').strip()
        gallery = [g.strip() for g in (pmeta.get('_product_image_gallery') or '').split(',') if g.strip()]

        for attachment_id in ([thumb] if thumb else []) + gallery:
            if attachment_id in seen_attachment:
                continue
            seen_attachment.add(attachment_id)
            legacy_file = attachments.get(attachment_id)
            if not legacy_file:
                continue

            filename = resolve_file(pid, legacy_file, on_disk, matched_files)
            basename = os.path.basename(legacy_file).lower()
            kind = 'drawing' if 'drawing' in basename else ('main' if not images else 'gallery')

            matched_files.add(filename)
            images.append({
                'type': kind,
                'path': '/images/products/' + filename,
                'available': filename in on_disk,
                'legacy_file': legacy_file,
                'legacy_attachment_id': int(attachment_id) if attachment_id.isdigit() else None,
            })

        # a drawing must never be the primary shot
        if images and images[0]['type'] == 'drawing':
            photo = next((i for i, im in enumerate(images) if im['type'] != 'drawing'), None)
            if photo is not None:
                images.insert(0, images.pop(photo))
            images[0]['type'] = 'main' if images[0]['type'] != 'drawing' else images[0]['type']

        # any exported file we did not reach through the metadata still belongs
        # to this product (the export is named prod_{legacyId}_{originalFile})
        for extra in by_post_id.get(pid, []):
            if extra in matched_files:
                continue
            images.append({
                'type': 'drawing' if 'drawing' in extra.lower() else ('main' if not images else 'gallery'),
                'path': '/images/products/' + extra,
                'available': True,
                'legacy_file': None,
                'legacy_attachment_id': None,
            })

        if not images and not description:
            continue  # empty placeholder / abandoned draft row

        # legacy slugs are mostly leftovers from the purchased demo shop
        # (kata-bolla-gold-ceramic-compote-vase ...). Build a truthful slug
        # from the real product name + model code instead.
        slug_base = slugify(name) or slugify(model) or ('lufly-' + pid)
        legacy_slug = post['post_name'] or ''
        if model and model.lower() in legacy_slug.lower():
            slug_base = legacy_slug
        slug = slug_base
        if slug in seen_slug:
            slug = f'{slug_base}-{pid}'
        seen_slug[slug] = pid

        # -- real specifications: "Key: value" lines the catalog itself carries
        specs = []
        seen_spec = set()
        for chunk in re.split(r'\n|,\s(?=[A-Z][A-Za-z ()\[\]/+.#-]{2,28}\s*:)', description):
            m = re.match(r'^([A-Za-z][A-Za-z0-9 /()\[\]+.#-]{2,28})\s*:\s*(.+)$', chunk.strip())
            if not m:
                continue
            key = re.sub(r'\s+', ' ', m.group(1)).strip()
            value = m.group(2).strip().rstrip('.').strip()
            if not value or key.lower() in ('kod', 'code') or key.lower() in seen_spec:
                continue
            seen_spec.add(key.lower())
            specs.append({'key': key.title() if key.islower() else key, 'value': value})

        products.append({
            'legacy_id': int(pid),
            'slug': slug,
            'model_code': model,
            'sku': sku or model,
            'locale': locale,
            'name': name,
            'short_description': short,
            'description': description,
            'specifications': specs,
            'categories': cats,
            'category': cats[0] if cats else None,
            'status': 'active' if post['post_status'] == 'publish' else 'draft',
            'images': images,
            'created_at': post['post_date'],
            'updated_at': post['post_modified'],
        })

    # primary category = the most specific one the product belongs to
    # (legacy products sit in a broad term plus a precise one)
    breadth = collections.Counter()
    for p in products:
        breadth.update(p['categories'])
    for p in products:
        if p['categories']:
            p['categories'].sort(key=lambda s: breadth[s])
            p['category'] = p['categories'][0]

    categories = []
    order = list(CATEGORY_MAP.values())
    ordered_slugs = [s for s, _ in order if s in used_categories]
    ordered_slugs += [s for s in used_categories if s not in ordered_slugs]
    for i, slug in enumerate(ordered_slugs, start=1):
        names = used_categories[slug]
        first = next((im['path'] for p in products if p['category'] == slug
                      for im in p['images']
                      if im['type'] != 'drawing' and im['available']), None)
        categories.append({
            'slug': slug,
            'sort_order': i,
            'image': first,
            'names': names,
            'product_count': sum(1 for p in products if p['category'] == slug),
        })
    categories = [c for c in categories if c['product_count'] > 0]
    for i, category in enumerate(categories, start=1):
        category['sort_order'] = i

    # manifest of images the legacy product references but that were never
    # exported, so they can be pulled off the old server in one pass
    missing_rows = ['legacy_post_id,type,legacy_upload_path,expected_filename']
    for p in products:
        for im in p['images']:
            if im['available'] or not im['legacy_file']:
                continue
            missing_rows.append('%s,%s,%s,%s' % (
                p['legacy_id'], im['type'], im['legacy_file'],
                os.path.basename(im['path'])))

    with open(os.path.join(ROOT, 'tools', 'import', 'missing-images.csv'), 'w',
              encoding='utf-8') as fh:
        fh.write('\n'.join(missing_rows) + '\n')
    print('missing-images.csv rows:', len(missing_rows) - 1)

    os.makedirs(OUT, exist_ok=True)
    with open(os.path.join(OUT, 'products.json'), 'w', encoding='utf-8') as fh:
        json.dump(products, fh, ensure_ascii=False, indent=2)
    with open(os.path.join(OUT, 'categories.json'), 'w', encoding='utf-8') as fh:
        json.dump(categories, fh, ensure_ascii=False, indent=2)

    print('products:', len(products))
    total_images = sum(len(p['images']) for p in products)
    on_disk_images = sum(1 for p in products for im in p['images'] if im['available'])
    drawings = sum(1 for p in products for im in p['images'] if im['type'] == 'drawing')
    print('with image:', sum(1 for p in products if p['images']))
    print('image refs:', total_images, '| on disk:', on_disk_images,
          '| missing files:', total_images - on_disk_images)
    print('drawings:', drawings,
          '| products with >1 image:', sum(1 for p in products if len(p['images']) > 1))
    print('with model code:', sum(1 for p in products if p['model_code']))
    print('with specs:', sum(1 for p in products if p['specifications']))
    print('with description:', sum(1 for p in products if p['description']))
    print('categories:', [(c['slug'], c['product_count']) for c in categories])
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
