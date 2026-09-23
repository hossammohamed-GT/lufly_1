#!/usr/bin/env python3
"""Decide whether a catalogue image is a PHOTO or a TECHNICAL DRAWING.

The legacy WordPress export put photos and blueprints in the same
`product_media.type` bucket, so the storefront cannot tell them apart. This
module re-classifies every attached file so the three tabs on the product page
(Product / Drawing / Installed) can be filled from the data.

Signal used - "stroke survival":

    crop to the content box, rescale to 300x300, mask everything darker than
    245, erode the mask twice and measure how much of it survives.

* A photo of a ceramic or brass product is a solid body: 60-100% survives.
* A technical drawing is hairline strokes on white paper: 0-50% survives.

Measured on all 560 attachments of the legacy catalog the two populations are
cleanly separated (see docs/Media-Taxonomy.md); three files needed a manual
pin, and each one was confirmed by eye on a rendered contact sheet
(`tools/media_audit/contact_sheet.py`).
"""
from __future__ import annotations

from pathlib import Path

import numpy as np
from PIL import Image

from features import content_box, load_gray

NORMALISED = 300
DARK_THRESHOLD = 245
PHOTO_MIN_SURVIVAL = 0.60

# Grey-zone files pinned after visual inspection on a contact sheet.
FORCE_DRAWING: dict[str, str] = {
    "/images/products/prod_3046_Screenshot-2026-07-22-175440.png": "bracket detail with 60/70/90/40 mm dimensions",
    "/images/products/prod_3030_Screenshot-2026-07-22-174536.png": "seat drawing with 330/630 mm dimensions",
}
FORCE_PHOTO: dict[str, str] = {
    "/images/products/prod_2534_DL-08120-LSJ.jpg": "gold shower column photographed on white",
}


def _erode(mask: np.ndarray) -> np.ndarray:
    out = mask.copy()
    out[1:, :] &= mask[:-1, :]
    out[:-1, :] &= mask[1:, :]
    out[:, 1:] &= mask[:, :-1]
    out[:, :-1] &= mask[:, 1:]
    return out


def stroke_survival(path: Path, size: int = NORMALISED) -> tuple[float, float]:
    """Return (object_area_ratio, survival_after_two_erosions)."""
    gray = load_gray(path)
    x0, y0, x1, y1 = content_box(gray)
    crop = gray[y0:y1, x0:x1]
    if crop.size == 0:
        crop = gray

    arr = np.asarray(Image.fromarray(crop).resize((size, size), Image.LANCZOS), dtype=np.int16)
    obj = arr < DARK_THRESHOLD
    total = int(obj.sum())
    if total == 0:
        return 0.0, 0.0

    return float(total / obj.size), float(_erode(_erode(obj)).sum() / total)


def classify(public_path: Path, survival: float) -> tuple[str, str]:
    """Return (kind, reason) where kind is 'photo' or 'drawing'."""
    url = "/images/products/" + public_path.name

    if url in FORCE_DRAWING:
        return "drawing", "pinned drawing: " + FORCE_DRAWING[url]
    if url in FORCE_PHOTO:
        return "photo", "pinned photo: " + FORCE_PHOTO[url]
    if survival < PHOTO_MIN_SURVIVAL:
        return "drawing", f"hairline strokes (survival {survival:.2f})"

    return "photo", f"solid body (survival {survival:.2f})"
