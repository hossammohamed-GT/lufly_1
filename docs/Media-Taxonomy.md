# Product images — which tab an image belongs to

The catalogue keeps **one** image library (`media`) and attaches files to products
through `product_media`. The `type` column of that pivot is the only thing that
decides where an image shows up:

| `product_media.type` | Section in Admin | Where it appears |
|---|---|---|
| `main` | Product photos | cover of the product card, search / share image, first image of the **Photo** tab |
| `gallery` | Product photos | the other images of the **Photo** tab (and hover cycle on cards) |
| `drawing` | Technical drawings | **Drawing** tab of the product page |
| `situ` | Installed / on site | **Installed** tab, and hover cycle on cards |

Invariants enforced by `Modules\Products\Services\ProductMediaService`:

* at most one `is_primary = 1` row per product, and it is always a photo
  (`main`); when a product has photos, the first photo is `main`;
* `sort_order` is dense (1..n) **inside each section**, so re-ordering one
  section never disturbs another;
* a product that only ever received drawings keeps no primary row — its cover
  image falls back to the first drawing (`Product::primaryImageUrl()`), so cards
  and share-cards never render blank.

## What the legacy import looked like

The WordPress/WooCommerce export behind `database/seeders/data/products.json`
put every attachment of a product into two buckets only: `main` (the featured
shot) and `gallery` (everything else — **photos *and* technical drawings**).

Measured on the shipped catalog (280 products, 560 attachments):

| | before | after |
|---|---|---|
| `main` | 280 | 277 |
| `gallery` | 259 | 7 |
| `drawing` | 21 | 276 |
| photos per product | 1–5 (mixed with drawings) | 1 (270 products), 2 (7 products) |
| products without any photo | — | 3 (`114`, `117`, `218` — drawings only) |

255 rows moved: 252 `gallery → drawing` and 3 `main → drawing`. No drawing was
mis-filed as a photo, so nothing moved into the photo section.

## How the split is detected (tooling, not runtime)

`tools/media_audit/` re-runs the analysis at any time (needs `pillow` + `numpy`,
never needed by the application itself):

```bash
python3 tools/media_audit/build_report.py                        # report only
python3 tools/media_audit/build_report.py --apply --patch-sql    # write sqlite + MySQL export
```

The classifier measures **stroke survival** of the file attached to the row:

1. crop to the content box (drops the surrounding white margin),
2. rescale to 300×300, mask everything darker than 245,
3. erode the mask twice — measure how much of it survives.

* photo of a ceramic/brass product → solid body, **60–100 %** survives;
* dimensioned drawing → hairlines on white paper, **0–50 %** survives.

Two files sit between the two populations and are pinned by name in
`classify.py` (`FORCE_DRAWING` / `FORCE_PHOTO`); both were confirmed by eye on a
contact sheet rendered with `tools/media_audit/contact_sheet.py`.

Every decision, with its score, is written to
`storage/reports/media-classification.csv` (one row per attachment, including
the previous and the new type) so the split stays auditable.

## Adding images from now on

**Admin → Products → edit → Product images.** Each section has its own grid and
its own upload field:

* **Product photos** — `★` makes an image the main one, `↑`/`↓` re-order;
* **Technical drawings** — upload the factory drawing/PDF-page export here;
* **Installed / on site** — photos of the product in place;
* the arrow + dropdown on each card moves an image between sections, `×` only
  detaches it from the product (the file stays in **Admin → Media**).

Product pages read the sections directly, so a file uploaded into *Technical
drawings* shows up under the Drawing tab on the next request — no re-import.
