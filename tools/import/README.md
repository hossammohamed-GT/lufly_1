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
- Records **every** image the legacy product references, in order: the
  featured thumbnail (`_thumbnail_id`) followed by the gallery
  (`_product_image_gallery`). Files whose name contains `drawing` are typed as
  technical drawings and are never used as the primary shot.
- Resolves each legacy upload to its exported `prod_{legacyId}_{file}` name,
  tolerating the `-scaled` suffix and re-encoded extensions, and never lets two
  attachments claim the same exported file.

## Known gaps (intentionally left empty)

These were previously filled with invented content and are now blank until
real data arrives:

- **Turkish translations** - the legacy system had none. Each product is
  stored in its own language plus an `en` fallback row carrying the same
  original text.
- **Czech/English descriptions** for a handful of records the legacy system
  left empty.
- **Gallery images and technical drawings** - the catalog references **560**
  images (280 main + 259 gallery + 21 drawings) but only the **280 main**
  shots were ever exported off the old server.

  All 560 references are now imported so nothing is lost: the 280 files that
  are present are stored with `media.status = 'active'`, the 280 that are not
  are stored with `media.status = 'missing'`. The storefront filters missing
  rows out (`Product::media()`), so no broken image can render, and the
  product page falls back to its generated technical sheet when the real
  drawing is absent.

  **`tools/import/missing-images.csv` lists all 280 files to copy** from the
  old server, with the legacy upload path and the exact target filename:

  ```
  legacy_post_id,type,legacy_upload_path,expected_filename
  146,gallery,2023/10/1620-111-b.jpg,prod_146_1620-111-b.jpg
  2050,drawing,2024/11/1690-000-Drawing.jpg,prod_2050_1690-000-Drawing.jpg
  ```

  Drop the files into `images/products/` under `expected_filename` and rerun
  the pipeline; they flip to `active` and appear automatically.
- **Collections, attributes, dimensions, documents, prices, announcements** -
  no such data exists in the legacy dump, so the tables are empty and are
  filled from Admin.
