#!/usr/bin/env python3
"""Build the Lufly hero artwork set from the AI masters in tools/hero-masters.

For each of the four scenes (bathroom, kitchen, shower, smart)
and each mood (dark "", light "-light"):

    public/images/hero/hero-<name><v>.webp        full-width LOSSLESS WebP
    public/images/hero/hero-<name><v>-thumb.webp  450x300 LOSSLESS tab thumbnail

Quality policy (2026-09-29, owner decision): the hero is the brand photograph
and it is NEVER reduced. Every file is saved as lossless WebP straight from
the lossless PNG master - pixel-identical to the source, zero compression
artifacts, on every screen size. There are NO downscaled renditions and NO
lossy quality settings: a phone downloads exactly the same master-quality
frame as a desktop. The old rendition ladder (@480w/@760w/@1024w at q92 and
q96 full frames) was deliberately removed at the owner's request; do not
reintroduce reduced-quality variants here. If page weight ever matters more
than fidelity, talk to the design owner first.

The masters are 1376x768 (the generator's ceiling). Nothing is ever upscaled.

The thumbnail is a 1.5:1 (150x100 in the design) crop of the product side of
the frame, so the tab reads as the product, not as empty negative space. It
is 3x the CSS size and also saved lossless.

    python3 tools/hero-masters/build_hero_images.py
"""
from __future__ import annotations

from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
SRC = ROOT / "tools/hero-masters"
OUT = ROOT / "public/images/hero"

SCENES = ["bathroom", "kitchen", "shower", "smart"]
VARIANTS = ["", "-light"]             # dark masters + bright daylight set

THUMB_SIZE = (450, 300)               # 1.5:1 -> 150x100 CSS px at 3x
THUMB_WINDOW_X = (0.22, 1.0)          # keep the product (right side) in the crop


def build_scene(name: str, variant: str) -> None:
    master = Image.open(SRC / f"{name}{variant}.png").convert("RGB")
    w, h = master.size

    OUT.mkdir(parents=True, exist_ok=True)
    stem = f"hero-{name}{variant}"

    master.save(OUT / f"{stem}.webp", "WEBP", lossless=True, method=6)

    # Thumbnail: crop a 1.5:1 window over the product, then shrink - lossless.
    thumb_w = round(h * THUMB_SIZE[0] / THUMB_SIZE[1])
    x0 = round(w * THUMB_WINDOW_X[0])
    x1 = min(w, x0 + thumb_w)
    if x1 - x0 < thumb_w:                      # never exceed the frame
        x0 = max(0, x1 - thumb_w)
    thumb = master.crop((x0, 0, x1, h)).resize(THUMB_SIZE, Image.LANCZOS)
    thumb.save(OUT / f"{stem}-thumb.webp", "WEBP", lossless=True, method=6)

    print(f"{stem}: master {w}x{h} -> lossless webp + lossless thumb")


def main() -> None:
    for scene in SCENES:
        for variant in VARIANTS:
            build_scene(scene, variant)


if __name__ == "__main__":
    main()
