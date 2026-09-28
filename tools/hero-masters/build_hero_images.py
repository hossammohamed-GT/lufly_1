#!/usr/bin/env python3
"""Build the Lufly hero artwork set from the AI masters in tools/hero-masters.

For each of the five scenes (bathroom, kitchen, shower, accessories, smart):

    public/images/hero/hero-<name>.jpg            full-width JPEG fallback (q90)
    public/images/hero/hero-<name>.webp           full-width WebP (q88)
    public/images/hero/hero-<name>@<w>w.webp      downscaled WebP renditions
    public/images/hero/hero-<name>-thumb.webp     400x278 tab thumbnail crop

The masters are 1376x768 (the generator's ceiling). Nothing is ever upscaled:
the renditions only go DOWN from the master width, exactly like
tools/media_audit/optimize_images.py --variants.

The thumbnail is a 1.44:1 (130x90 in the design) crop of the product side of
the frame, so the tab reads as the product, not as empty negative space.

    python3 tools/hero-masters/build_hero_images.py
"""
from __future__ import annotations

from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
SRC = ROOT / "tools/hero-masters"
OUT = ROOT / "public/images/hero"

SCENES = ["bathroom", "kitchen", "shower", "accessories", "smart"]

WIDTHS = [480, 760, 1024]          # renditions below the master width
THUMB_SIZE = (400, 278)            # 1.44:1 -> 130x90 CSS px at ~3x
THUMB_WINDOW_X = (0.22, 1.0)       # keep the product (right side) in the crop


def build_scene(name: str) -> None:
    master = Image.open(SRC / f"{name}.png").convert("RGB")
    w, h = master.size

    OUT.mkdir(parents=True, exist_ok=True)

    master.save(OUT / f"hero-{name}.jpg", "JPEG", quality=90,
                optimize=True, progressive=True)
    master.save(OUT / f"hero-{name}.webp", "WEBP", quality=88, method=6)

    for target in WIDTHS:
        if target >= w:
            continue
        scaled = master.resize((target, round(h * target / w)), Image.LANCZOS)
        scaled.save(OUT / f"hero-{name}@{target}w.webp", "WEBP",
                    quality=86, method=6)

    # Thumbnail: crop a 1.44:1 window over the product, then shrink.
    thumb_w = round(h * THUMB_SIZE[0] / THUMB_SIZE[1])
    x0 = round(w * THUMB_WINDOW_X[0])
    x1 = min(w, x0 + thumb_w)
    if x1 - x0 < thumb_w:                      # never exceed the frame
        x0 = max(0, x1 - thumb_w)
    thumb = master.crop((x0, 0, x1, h)).resize(THUMB_SIZE, Image.LANCZOS)
    thumb.save(OUT / f"hero-{name}-thumb.webp", "WEBP", quality=85, method=6)

    print(f"hero-{name}: master {w}x{h} -> jpg + webp + "
          f"{len([t for t in WIDTHS if t < w])} renditions + thumb")


def main() -> None:
    for scene in SCENES:
        build_scene(scene)


if __name__ == "__main__":
    main()
