# Product image audit

Which section (photo / technical drawing / installed) every file attached to a
product belongs to. Background and results: `docs/Media-Taxonomy.md`.

The application itself has **no** Python dependency — these scripts only exist to
audit and (re)sort the catalogue data.

## Setup

```bash
pip install pillow numpy
```

## Run

```bash
# 1. look first: writes storage/reports/media-classification.csv, changes nothing
python3 tools/media_audit/build_report.py

# 2. apply the plan to database/lufly.sqlite
python3 tools/media_audit/build_report.py --apply

# 3. also refresh the product_media block of lufly-database.sql (MySQL export)
python3 tools/media_audit/build_report.py --apply --patch-sql
```

Useful flags: `--report path.csv`, `--json plan.json`, `--quiet`.

## Files

| file | purpose |
|---|---|
| `features.py` | content box, tone statistics, duplicate signature helpers |
| `classify.py` | photo-vs-drawing decision (**stroke survival** + the two pinned files) |
| `build_report.py` | CLI: analyse → plan → CSV → optional `--apply` / `--patch-sql` |
| `contact_sheet.py` | render a labelled PNG grid of images so a human can check a whole score band |

## Checking a doubtful band by eye

```bash
python3 tools/media_audit/contact_sheet.py --out /tmp/band.png --cols 7 \
  public/images/products/prod_3030_*.png public/images/products/prod_3046_*.png
```

Add anything that turns out to be mis-sorted to `FORCE_DRAWING` / `FORCE_PHOTO`
in `classify.py` (with a one-line reason), then re-run `--apply`.
