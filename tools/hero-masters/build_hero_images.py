#!/usr/bin/env python3
"""Build the Lufly hero artwork set from the AI masters in tools/hero-masters.

For each of the five scenes (bathroom, kitchen, shower, accessories, smart)
and each mood (dark "", light "-light"):

    public/images/hero/hero-<name><v>.webp       full-width WebP (q96)
    public/images/hero/hero-<name><v>@<w>w.webp  downscaled WebP renditions
    public/images/hero/hero-<name><v>-thumb.webp 400x278 tab thumbnail crop

Quality policy: the hero is the brand photograph - it is NEVER squeezed.
The full-width WebP is saved at q96 (visually lossless) straight from the
lossless PNG master, and the renditions keep q92 so even phones get the
same fidelity at their resolution. This deliberately does NOT follow the
aggressive settings of tools/media_audit/optimize_images.py; if you need
smaller files, shrink the rendition ladder, not the quality.

The masters are 1376x768 (the generator's ceiling). Nothing is ever
upscaled: the renditions only go DOWN from the master width.

The thumbnail is a 1.44:1 (130x90 in the design) crop of the product side
of the frame, so the tab reads as the product, not as empty negative space.

    python3 tools/hero-masters/build_hero_images.py
"""
from __future__ import annotations

from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
SRC = ROOT / "tools/hero-masters"
OUT = ROOT / "public/images/hero"

SCENES = ["bathroom", "kitchen", "shower", "accessories", "smart"]
VARIANTS = ["", "-light"]             # dark masters + bright daylight set

WIDTHS = [480, 760, 1024]             # renditions below the master width
THUMB_SIZE = (400, 278)               # 1.44:1 -> 130x90 CSS px at ~3x
THUMB_WINDOW_X = (0.22, 1.0)          # keep the product (right side) in the crop

Q_FULL = 96                           # hero policy: near-lossless
Q_RENDITION = 92


def build_scene(name: str, variant: str) -> None:
    master = Image.open(SRC / f"{name}{variant}.png").convert("RGB")
    w, h = master.size

    OUT.mkdir(parents=True, exist_ok=True)
    stem = f"hero-{name}{variant}"

    master.save(OUT / f"{stem}.webp", "WEBP", quality=Q_FULL, method=6)

    for target in WIDTHS:
        if target >= w:
            continue
        scaled = master.resize((target, round(h * target / w)), Image.LANCZOS)
        scaled.save(OUT / f"{stem}@{target}w.webp", "WEBP",
                    quality=Q_RENDITION, method=6)

    # Thumbnail: crop a 1.44:1 window over the product, then shrink.
    thumb_w = round(h * THUMB_SIZE[0] / THUMB_SIZE[1])
    x0 = round(w * THUMB_WINDOW_X[0])
    x1 = min(w, x0 + thumb_w)
    if x1 - x0 < thumb_w:                      # never exceed the frame
        x0 = max(0, x1 - thumb_w)
    thumb = master.crop((x0, 0, x1, h)).resize(THUMB_SIZE, Image.LANCZOS)
    thumb.save(OUT / f"{stem}-thumb.webp", "WEBP", quality=Q_RENDITION, method=6)

    print(f"{stem}: master {w}x{h} -> webp(q{Q_FULL}) "
          f"+ {len([t for t in WIDTHS if t < w])} renditions(q{Q_RENDITION}) "
          f"+ thumb")


def main() -> None:
    for scene in SCENES:
        for variant in VARIANTS:
            build_scene(scene, variant)


if __name__ == "__main__":
    main()
