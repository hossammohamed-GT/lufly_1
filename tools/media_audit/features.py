#!/usr/bin/env python3
"""Small image helpers shared by the media audit tooling.

Two things are needed for every file attached to a product:

* the bounding box of the actual product (to drop the generous white margin the
  legacy catalogue uses) — `content_box()`;
* grayscale pixels to measure ink coverage and stroke thickness on —
  `load_gray()`.

The photo-vs-drawing decision itself lives in `classify.py`.
"""
from __future__ import annotations

from pathlib import Path

import numpy as np
from PIL import Image

Image.MAX_IMAGE_PIXELS = None


def load_gray(path: Path) -> np.ndarray:
    with Image.open(path) as img:
        return np.asarray(img.convert("L"), dtype=np.uint8)


def content_box(gray: np.ndarray, bg_threshold: int = 244) -> tuple[int, int, int, int]:
    """Bounding box (x0, y0, x1, y1) of everything that is not near-white paper."""
    mask = gray < bg_threshold
    rows = np.where(mask.any(axis=1))[0]
    cols = np.where(mask.any(axis=0))[0]
    if rows.size == 0 or cols.size == 0:
        height, width = gray.shape
        return 0, 0, width, height

    return int(cols[0]), int(rows[0]), int(cols[-1]) + 1, int(rows[-1]) + 1
