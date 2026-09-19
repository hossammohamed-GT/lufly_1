#!/usr/bin/env python3
"""Rebuild the catalog tables of database/lufly.sqlite from the REAL legacy data.

This is the Python twin of database/seeders/{CategorySeeder,CatalogSeeder,
ProductSeeder}.php. It exists because the sandbox has no PHP runtime; running
`php cli/lufly db:seed` on a fresh database produces the same rows.

It wipes every placeholder catalog row (products, categories, collections,
attributes, media, announcements) and reloads the extracted legacy data.
Users, roles, permissions, languages and settings are left untouched.
"""
import json, os, sqlite3, uuid, datetime, re, sys

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
DB = os.path.join(ROOT, 'database', 'lufly.sqlite')
DATA = os.path.join(ROOT, 'database', 'seeders', 'data')
NOW = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')

WIPE = [
    'product_search_keywords', 'product_import_logs', 'product_attribute_values',
    'product_specifications', 'product_dimensions', 'product_documents',
    'product_relations', 'product_media', 'product_variants', 'product_translations',
    'seo_meta', 'products', 'category_translations', 'categories',
    'collection_translations', 'collections', 'attribute_options', 'attributes',
    'media', 'announcement_translations', 'announcements', 'blog_translations',
    'blogs', 'page_translations', 'pages',
]


def stamp(value):
    if not value or str(value).startswith('0000'):
        return NOW
    return value


def main():
    products = json.load(open(os.path.join(DATA, 'products.json'), encoding='utf-8'))
    categories = json.load(open(os.path.join(DATA, 'categories.json'), encoding='utf-8'))

    db = sqlite3.connect(DB)
    db.execute('PRAGMA foreign_keys = OFF')
    cur = db.cursor()

    for table in WIPE:
        cur.execute(f'DELETE FROM "{table}"')
        cur.execute('DELETE FROM sqlite_sequence WHERE name = ?', (table,))

    # brand (CatalogSeeder)
    cur.execute('DELETE FROM brands')
    cur.execute('DELETE FROM sqlite_sequence WHERE name = ?', ('brands',))
    cur.execute(
        'INSERT INTO brands (name, slug, logo, country, website, status, created_at, updated_at)'
        ' VALUES (?,?,?,?,?,?,?,?)',
        ('LUFLY', 'lufly', '/images/logo.png', 'Turkey', 'https://www.lufly.tr', 'active', NOW, NOW),
    )
    brand_id = cur.lastrowid

    # categories (CategorySeeder)
    cat_ids = {}
    for category in categories:
        cur.execute(
            'INSERT INTO categories (parent_id, slug, image, icon, is_featured, sort_order, status, seo, created_at, updated_at)'
            ' VALUES (NULL,?,?,NULL,?,?,?,?,?,?)',
            (category['slug'], category.get('image'),
             1 if category['sort_order'] <= 4 else 0, category['sort_order'], 'active',
             json.dumps({'title': category['names'].get('en', category['slug'])}, ensure_ascii=False),
             NOW, NOW),
        )
        cat_ids[category['slug']] = cur.lastrowid
        for locale, name in category['names'].items():
            cur.execute(
                'INSERT INTO category_translations (category_id, locale, name, description) VALUES (?,?,?,NULL)',
                (cat_ids[category['slug']], locale, name),
            )

    # products (ProductSeeder)
    used_skus, sort_order = set(), 0
    for item in products:
        sort_order += 1
        model = (item.get('model_code') or '').strip()
        cur.execute(
            'INSERT INTO products (model_code, slug, category_id, collection_id, brand_id, status, is_featured, sort_order, created_at, updated_at)'
            ' VALUES (?,?,?,NULL,?,?,0,?,?,?)',
            (model or None, item['slug'], cat_ids.get(item.get('category')), brand_id,
             item.get('status', 'active'), sort_order,
             stamp(item.get('created_at')), stamp(item.get('updated_at'))),
        )
        pid = cur.lastrowid
        locale = item.get('locale', 'en')

        for translation_locale in dict.fromkeys([locale, 'en']):
            cur.execute(
                'INSERT INTO product_translations (product_id, locale, name, short_description, description) VALUES (?,?,?,?,?)',
                (pid, translation_locale, item['name'],
                 (item.get('short_description') or '').strip() or None,
                 (item.get('description') or '').strip() or None),
            )

        sku = (item.get('sku') or model).strip()
        if sku:
            unique, suffix = sku, 2
            while unique in used_skus:
                unique = f'{sku}-{suffix}'
                suffix += 1
            used_skus.add(unique)
            cur.execute(
                'INSERT INTO product_variants (product_id, sku, variant_name, price, stock_status, sort_order, status, created_at, updated_at)'
                ' VALUES (?,?,NULL,0,?,1,?,?,?)',
                (pid, unique, 'in_stock', 'active', NOW, NOW),
            )

        for index, spec in enumerate(item.get('specifications') or [], start=1):
            key, value = spec.get('key', '').strip(), spec.get('value', '').strip()
            if not key or not value:
                continue
            cur.execute(
                'INSERT INTO product_specifications (product_id, variant_id, spec_key, spec_value, unit, sort_order)'
                ' VALUES (?,NULL,?,?,NULL,?)',
                (pid, key, value[:255], index),
            )

        for index, image in enumerate(item.get('images') or []):
            image = '/' + image.lstrip('/')
            filename = os.path.basename(image)
            ext = (os.path.splitext(filename)[1][1:] or 'jpg').lower()
            cur.execute(
                'INSERT INTO media (uuid, collection, filename, original_name, path, mime_type, extension, size, meta, status, created_at, updated_at)'
                ' VALUES (?,?,?,?,?,?,?,0,?,?,?,?)',
                (str(uuid.uuid4()), 'products', filename, filename, image,
                 'image/png' if ext == 'png' else 'image/jpeg', ext,
                 json.dumps({'source': 'legacy-wordpress', 'legacy_post_id': item['legacy_id']}, ensure_ascii=False),
                 'active', NOW, NOW),
            )
            media_id = cur.lastrowid
            cur.execute(
                'INSERT INTO product_media (product_id, variant_id, media_id, type, sort_order, is_primary, created_at, updated_at)'
                ' VALUES (?,NULL,?,?,?,?,?,?)',
                (pid, media_id, 'main' if index == 0 else 'gallery', index + 1,
                 1 if index == 0 else 0, NOW, NOW),
            )

        meta_desc = (item.get('short_description') or item.get('description') or '').replace('\n', ' ')[:500]
        cur.execute(
            'INSERT INTO seo_meta (product_id, locale, meta_title, meta_description, og_title, og_description, canonical_url, created_at, updated_at)'
            ' VALUES (?,?,?,?,NULL,NULL,NULL,?,?)',
            (pid, locale, (item['name'] + ' | LUFLY')[:255], meta_desc or None, NOW, NOW),
        )

        keywords = ['lufly']
        if model:
            keywords.append(model.lower())
        keywords += [w.lower() for w in re.split(r'[^\w-]+', item['name'], flags=re.UNICODE) if len(w) >= 3]
        if item.get('category'):
            keywords.append(item['category'].replace('-', ' '))
        for keyword in dict.fromkeys(keywords):
            cur.execute('INSERT INTO product_search_keywords (product_id, keyword) VALUES (?,?)', (pid, keyword[:150]))

        cur.execute(
            'INSERT INTO product_import_logs (product_id, source_file, source_page, detected_sku, status, raw_data, created_at, updated_at)'
            ' VALUES (?,?,?,?,?,?,?,?)',
            (pid, 'delete_files/lufly_new.sql', item['legacy_id'], model or None, 'imported',
             json.dumps(item, ensure_ascii=False), NOW, NOW),
        )

    db.commit()

    report = {t: cur.execute(f'SELECT COUNT(*) FROM "{t}"').fetchone()[0]
              for t in ('products', 'product_translations', 'product_variants',
                        'product_specifications', 'product_media', 'media',
                        'categories', 'category_translations', 'seo_meta',
                        'product_search_keywords', 'collections', 'attributes',
                        'announcements')}
    for k, v in report.items():
        print(f'{k:28} {v}')
    db.close()
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
