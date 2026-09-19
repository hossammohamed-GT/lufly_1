# Legacy data import (real LUFLY catalog)

The placeholder/demo catalog that used to ship with this repository has been
removed. The catalog now comes exclusively from the old LUFLY WordPress /
WooCommerce system exported into `delete_files/`.

## Sources (verified, not invented)

| File | Contents |
| --- | --- |
| `delete_files/lufly_new.sql` | `chso_posts` (283 products, 734 attachments), `chso_postmeta` (10,625 rows), `chso_term_relationships`, `chso_term_taxonomy` |
| `delete_files/chso_terms.sql` | `chso_terms` (75 terms, incl. 14 `product_cat` terms) |
| `images/products/` | 280 product photos exported from the old media library, named `prod_{legacyPostId}_{originalFile}` |

## Pipeline

```bash
# 1. parse the dumps and write the clean catalog JSON
python3 tools/import/extract_legacy.py
#    -> database/seeders/data/products.json
#    -> database/seeders/data/categories.json

# 2a. normal path: seed through the framework
php cli/lufly db:seed

# 2b. sandbox path (no PHP runtime): identical rebuild straight into SQLite
python3 tools/import/rebuild_sqlite.py
```

`rebuild_sqlite.py` is a one-to-one twin of `CategorySeeder`, `CatalogSeeder`
and `ProductSeeder`; both produce the same rows.

## What the extractor does

- Keeps only `post_type = product` rows with `publish`/`draft` status and real
  content. Auto-drafts and empty placeholder rows are dropped (283 -> 280).
- Strips WordPress HTML, fixes the stray spaces the old editor injected into
  Czech words (`zav ěšená` -> `zavěšená`).
- Takes the model code printed in the product title/body (`1620-111`,
  `1654-2220GG`, `V-1911L`, `GT-82221`...). Demo-shop SKUs inherited from the
  purchased theme (`EWO-SDH-128`, `WER-JHK-254`) are discarded.
- Rebuilds slugs from the real product name plus model code, because most
  legacy slugs were leftovers from the theme demo
  (`kata-bolla-gold-ceramic-compote-vase`).
- Detects the language of each record (`cs` or `en`) from its own text.
- Maps the legacy `product_cat` taxonomy to the 8 real categories and drops
  the purely organisational terms (`our-store`, `uncategorized`, `glass`,
  `kitchen`, `non-oxide`, `silicates`).
- Parses `Key: value` lines out of the product copy into real specifications
  (`Finish: chrome`, `Material: stainless steel`, `Size (mm): 500x145x68`).
- Attaches the images that actually exist on disk for that legacy post id.

## Known gaps (intentionally left empty)

These were previously filled with invented content and are now blank until
real data arrives:

- **Turkish translations** - the legacy system had none. Each product is
  stored in its own language plus an `en` fallback row carrying the same
  original text.
- **Czech/English descriptions** for a handful of records the legacy system
  left empty.
- **Gallery images** - only one photo per product was exported; the legacy
  `_product_image_gallery` ids point to files that were not exported.
- **Collections, attributes, dimensions, documents, prices, announcements** -
  no such data exists in the legacy dump, so the tables are empty and are
  filled from Admin.
