# Catalog Data

How products, categories, images, translations and SEO fit together — and how to edit them safely.

## Current content (as shipped)

| Content | en | tr | cs |
|---|---|---|---|
| Products | 280 | 280 | 280 |
| Categories | 8 | 8 | 8 |
| Product images (DB-linked) | — | 560 in `media` + `product_media` (284 photos, 276 drawings) | — |
| SEO rows (`seo_meta`) | 280 | 280 | 280 |

Pages / blogs / announcements tables exist but are **empty by design** right now.

## Entity map

```
products (id, sku/code, category_id, price?, is_active, …)
 ├─ product_translations (product_id, locale) → name, short_description, description
 │     UNIQUE(product_id, locale) — exactly one row per product per locale
 ├─ product_media (product_id, media_id, type: main|gallery|drawing|situ, …)
 │     main/gallery = photos, drawing = technical drawing, situ = installed shot
 │     see docs/Media-Taxonomy.md
 │     └─ media (path → /images/products/prod_…*.jpg) — every row maps to a real file on disk
 ├─ seo_meta (product_id, locale) → meta_title = "{name} | LUFLY", meta_description = short_description
 ├─ product_variants / product_specifications / product_dimensions / product_relations (optional extras)
 └─ categories → products.category_id
       └─ category_translations (category_id, locale) → name
```

The site **never hard-codes text**: every frontend string comes from
`product_translations` for the requested locale, falling back to English.

## A note on legacy text (honesty in data)

The seed data came from a legacy WordPress export. For products with `id ≤ 27`, the **English
rows still hold the original (Czech) legacy text** as a deliberate fallback — they were kept
untouched until a translator reviews them. Turkish + Czech rows are complete for all 280 products.

## Editing content

### Through the admin panel (recommended)

`/admin` → **Products** → create/edit. A product edit writes:

- base row in `products`
- one `product_translations` row per enabled locale
- `seo_meta` rows per locale (title auto-composed as `{name} | LUFLY` if empty)
- image assignments in `product_media`, organised in the three sections of the product
  page (photos / technical drawings / installed) — upload into a section, move an image
  between sections, `★` the main photo; every upload gets a `media` row automatically

### Bulk / from data files

`database/seeders/data/products.json` + `categories.json` hold the seed catalog.
`ProductSeeder` / `CatalogSeeder` read them in an idempotent way, so you can fix data in JSON
and re-seed safely:

```bash
php cli seed                              # updates dev sqlite
php cli db:export-mysql                   # refresh the MySQL export afterwards
```

## Images

- Product images: 560 files in `public/images/products/`, **all registered in the `media` table**
  (path column) — no orphan files, no missing files.
- NEVER just copy files into the folder — every image must have a `media` row, and a
  `product_media` row linking it to the product (`main` = cover photo, `gallery` = extra
  photo, `drawing` = technical drawing, `situ` = installed shot).
- Which tab an image fills, and how the legacy import was sorted into photos vs drawings:
  `docs/Media-Taxonomy.md` (+ audit tool `tools/media_audit/`).
- Cards (catalogue grid, home *featured* row, live-search results) rotate through **every**
  image of the product on hover — photos, then drawings, then installed shots — via
  `Product::cardSlides()` / the `card_slides` API key; the card labels the section for
  non-photo slides.
- The product-card fallback image is `images/favicon.png` (used by the live-search UI when a
  product has no image — keep that file).

## SEO

- One `seo_meta` row per `(product, locale)` — all 840 exist.
- `meta_title` follows `{Product name} | LUFLY`; description = short description of the same locale.
- Edit per-product from **Admin → Products → edit** or globally from **Admin → SEO**.

## Search

`product_search_keywords` + the `/api/products/search` endpoint power the header live-search
(`frontend/js/live-search.js`). Product names/codes in all three locales are searchable.
