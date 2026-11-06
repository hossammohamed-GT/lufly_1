"""Write the DDL part of lufly-database.sql from database/lufly.sqlite.

Rebuilds only the ``-- table: x`` / ``-- data: x`` region (schema + every row),
keeps the header and the indexes/AUTO_INCREMENT/foreign-key block verbatim, and
folds the history of photo/name patches at the end into the rows themselves so
the file has no patch blocks left - one INSERT per row, straight from sqlite.

    python3 tools/audit/rebuild_dump_from_sqlite.py

The result is byte-comparable with the live database: replaying the file gives
back exactly the same rows, including the JSON columns exactly as sqlite stores
them.
"""

import os
import re
import sqlite3
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
DUMP = os.path.join(ROOT, 'lufly-database.sql')
DB = os.path.join(ROOT, 'database/lufly.sqlite')

# Parents first, the order the import needs (foreign keys are created at the end).
TABLE_ORDER = [
    'migrations', 'languages', 'users', 'roles', 'permissions', 'role_permissions',
    'user_roles', 'settings', 'media', 'activity_logs', 'audits', 'api_logs',
    'notifications', 'pages', 'page_translations', 'blogs', 'blog_translations',
    'categories', 'category_translations', 'brands', 'collections',
    'collection_translations', 'products', 'product_translations', 'product_variants',
    'attributes', 'attribute_options', 'product_attribute_values',
    'product_specifications', 'product_specification_translations',
    'product_feature_presets', 'product_feature_preset_translations',
    'product_card_features', 'product_card_feature_translations', 'product_dimensions',
    'product_media', 'product_documents', 'product_relations', 'seo_meta',
    'product_search_keywords', 'product_import_logs', 'announcements',
    'announcement_translations', 'ai_cache', 'ai_usage', 'assistant_leads',
    'favorites', 'favorite_items', 'boxes', 'box_items',
]

MYSQL_ESCAPE = {
    '\0': '\\0', "'": "\\'", '"': '\\"', '\b': '\\b', '\n': '\\n', '\r': '\\r',
    '\\': '\\\\', '\x1a': '\\Z',
}


def mysql_literal(value):
    if value is None:
        return 'NULL'
    if isinstance(value, bool):
        return '1' if value else '0'
    if isinstance(value, int):
        return str(value)
    if isinstance(value, float):
        return repr(value)
    text = str(value)
    return "'" + ''.join(MYSQL_ESCAPE.get(ch, ch) for ch in text) + "'"


def main():
    db = sqlite3.connect(DB)
    db.text_factory = str
    dump = open(DUMP, encoding='utf-8').read()

    head, rest = dump.split('-- table: migrations', 1)
    ddl_start = '-- table: migrations'
    ddl, tail = ('-- table: migrations' + rest).split('-- indexes, AUTO_INCREMENT and foreign keys', 1)
    tail = '-- indexes, AUTO_INCREMENT and foreign keys' + tail

    # the indexes block ends with the COMMIT; everything after it is history
    end_of_indexes = tail.index('SET FOREIGN_KEY_CHECKS = 1;') + len('SET FOREIGN_KEY_CHECKS = 1;')
    indexes = tail[:end_of_indexes].rstrip() + '\n'

    # --- schema: reuse the CREATE TABLE statements already in the file
    blocks = {}
    for m in re.finditer(r'-- table: (\w+)\n(CREATE TABLE `\1` \(.*?\n\) ENGINE=[^;]+;\n)', ddl, re.S):
        blocks[m.group(1)] = m.group(2)
    missing = [t for t in TABLE_ORDER if t not in blocks]
    if missing:
        sys.exit(f'no CREATE TABLE found for: {missing}')

    # --- data: straight from sqlite, one INSERT per row, file order == id order
    out = [head.rstrip('\n'), '']
    total = 0
    for table in TABLE_ORDER:
        out.append('-- table: ' + table)
        out.append(blocks[table].rstrip('\n'))
        cols = [r[1] for r in db.execute(f'PRAGMA table_info(`{table}`)')]
        rows = db.execute(f'SELECT * FROM `{table}` ORDER BY id ASC').fetchall()
        out.append('')
        out.append(f'-- data: {table} ({len(rows)} rows)')
        total += len(rows)
        if rows:
            collist = ', '.join(f'`{c}`' for c in cols)
            for row in rows:
                values = ', '.join(mysql_literal(v) for v in row)
                out.append(f'INSERT INTO `{table}` ({collist}) VALUES ({values});')
        out.append('')

    out.append(indexes)
    out.append('')
    out.append('-- End of lufly-database.sql')

    text = '\n'.join(out)
    text = re.sub(r'\n{3,}', '\n\n', text)
    open(DUMP, 'w', encoding='utf-8').write(text)
    print(f'rewrote {DUMP}: {total} rows across {len(TABLE_ORDER)} tables')


if __name__ == '__main__':
    main()
