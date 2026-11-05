#!/usr/bin/env python3
"""Etch the LUFLY mark onto the mirror photos, without touching the product.

The brief is strict: **the product must not change**. No re-render, no reshaping,
no background work — the only difference is that the maker's mark now sits on
the piece, in a place a manufacturer would really put it.

So nothing is drawn here. The tool takes the existing photo, takes the mark
from `tools/lufly_logo_mark.py` (traced from `public/images/logo.png`), and
composites it in multiply mode over the area the mark covers. That is what a
laser etch does to a surface: it darkens it slightly, following every highlight
and shadow underneath, so the engraving inherits the curvature, the reflections
and the grain of the part it sits on. A faint lip along the lower edge of each
stroke keeps it reading as cut rather than painted.

Two of the photos already carried a hand-drawn "Lufly" that is not the brand's
wordmark. Those are cleared first and the real mark is etched over the same
spot, at the same size, so the photos stay recognisable.

    python3 tools/lufly_logo_engrave.py              # write every placement
    python3 tools/lufly_logo_engrave.py --check      # report, write nothing
    python3 tools/lufly_logo_engrave.py --preview 3  # crop dumps for QA

The untouched photos live in `public/images/products/originals/`, so re-running
is safe: the engraving is always applied to the pristine picture, never on top
of a previous run.

Photos are written at 1600 px wide (the catalogue size); `media` is then synced
by `tools/lufly_media_sync.py`.
"""

from __future__ import annotations

import sys
from dataclasses import dataclass
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw, ImageFilter

from lufly_logo_mark import lockup

ROOT = Path(__file__).resolve().parent.parent
CATALOG = ROOT / "public" / "images" / "products" / "catalog"
# the untouched photos, so re-running never engraves on top of an engraving
ORIGINALS = ROOT / "public" / "images" / "products" / "originals"
PREVIEW = Path("/tmp/work/preview")
OUTPUT_WIDTH = 1600


@dataclass(frozen=True)
class Placement:
    """One engraving: which photo, how big the mark is, and where it sits."""

    file: str
    product: str
    # mark width as a fraction of the photo's width
    width_frac: float
    # centre of the mark, as a fraction of the photo's width and height
    centre: tuple[float, float]
    # area of an older, hand-drawn mark to clear first (x1, y1, x2, y2 as fractions)
    clear: tuple[float, float, float, float] | None = None
    # how hard the etch bites; 1.0 is a discreet tone-on-tone marking
    strength: float = 1.0
    note: str = ""


# --------------------------------------------------------------------------- #
# Placements — all measured on the original pixels, then written as fractions
# --------------------------------------------------------------------------- #
PLACEMENTS = [
    # The two makeup mirrors are signed on the glass, centred — the way an arm
    # mirror is marked, and the only place a mark can sit square on a round head.
    Placement(
        file="c7181bc35d636359403fdd8a.webp",           # 1600 x 1145
        product="1610-0051 · wall-mounted LED makeup mirror",
        width_frac=210 / 1600,
        centre=(1132 / 1600, 470 / 1145),
        strength=1.5,
        note="on the glass, horizontally centred above the touch icon",
    ),
    Placement(
        file="b032652d6a19ed62de154309.webp",           # 1600 x 1056
        product="1610-0044 · wall-mounted LED makeup mirror",
        width_frac=215 / 1600,
        centre=(1148 / 1600, 330 / 1056),
        strength=1.5,
        note="on the glass, horizontally centred in the upper half of the disc",
    ),
    # Framed mirror: on the glass, centred just above the bronze rail.
    Placement(
        file="f0c10843681c6fb5db27e2e4.webp",           # 1600 x 1952, rail top y 1596
        product="SS-90626 · bronze-framed mirror",
        width_frac=200 / 1600,
        centre=(800 / 1600, 1660 / 1952),
        strength=1.35,
        note="on the glass, centred low above the bronze rail",
    ),
    # Tall LED mirror: on the glass, centred, clear of the touch sensor.
    Placement(
        file="fcbd5a0b475aa37bd34c1462.webp",           # 1600 x 3783, sensor centre (622, 3255)
        product="custom-made-171 · LED sensor mirror",
        width_frac=230 / 1600,
        centre=(795 / 1600, 3100 / 3783),
        strength=1.35,
        note="on the glass, centred above the touch sensor",
    ),
]


# --------------------------------------------------------------------------- #
# Compositing
# --------------------------------------------------------------------------- #
def clear_old_mark(photo: Image.Image, area: tuple[float, float, float, float]) -> tuple[Image.Image, tuple[int, int, int, int]]:
    """Erase a drawn mark before the real one is etched in its place.

    The patch is not blanked: every column is rebuilt from the rows just above
    and just below the area, so the surface keeps its own shading and any
    specular streak running through that part of the plate. The seam is then
    feathered so no rectangle edge is left behind.
    """
    width, height = photo.size
    left = max(0, round(area[0] * width))
    top = max(0, round(area[1] * height))
    right = min(width, round(area[2] * width))
    bottom = min(height, round(area[3] * height))
    if right <= left or bottom <= top:
        raise SystemExit(f"empty clear area {area}")

    band = np.array(photo.convert("RGB"), dtype=np.float32)
    pad = 3
    above = band[max(0, top - pad):top].mean(axis=0)
    below = band[bottom:bottom + pad].mean(axis=0)
    rows = bottom - top
    for offset in range(rows):
        weight = (offset + 0.5) / rows
        band[top + offset, left:right] = above[left:right] * (1 - weight) + below[left:right] * weight

    result = Image.fromarray(np.clip(band, 0, 255).round().astype(np.uint8), "RGB")

    # soften the two horizontal seams and the two vertical ones
    patch_box = (left - pad, top - pad, right + pad, bottom + pad)
    patch = result.crop(patch_box).filter(ImageFilter.GaussianBlur(1.1))
    mask = Image.new("L", patch.size, 0)
    draw = ImageDraw.Draw(mask)
    draw.rectangle([pad + 1, pad + 1, patch.width - pad - 2, patch.height - pad - 2], fill=255)
    mask = mask.filter(ImageFilter.GaussianBlur(1.6))
    result.paste(patch, patch_box[:2], mask)

    return result, (left, top, right, bottom)


def engrave(photo: Image.Image, placement: Placement) -> tuple[Image.Image, dict[str, object]]:
    info: dict[str, object] = {}
    if placement.clear is not None:
        photo, cleared = clear_old_mark(photo, placement.clear)
        info["cleared"] = cleared

    width, height = photo.size
    mark = lockup(max(24, round(width * placement.width_frac)))
    left = round(width * placement.centre[0] - mark.width / 2)
    top = round(height * placement.centre[1] - mark.height / 2)
    left = max(0, min(width - mark.width, left))
    top = max(0, min(height - mark.height, top))
    box = (left, top, left + mark.width, top + mark.height)

    canvas = np.array(photo.convert("RGB"), dtype=np.float32) / 255.0
    alpha = np.array(mark, dtype=np.float32) / 255.0
    alpha = np.array(Image.fromarray((alpha * 255).astype(np.uint8)).filter(
        ImageFilter.GaussianBlur(0.5)), dtype=np.float32) / 255.0

    patch = canvas[box[1]:box[3], box[0]:box[2]]
    ink = np.clip(patch.reshape(-1, 3).mean(axis=0) * 0.42, 0.0, 1.0)

    bite = 0.58 * placement.strength
    etched = patch * (1.0 - bite * alpha[..., None]) + ink * (bite * alpha[..., None])

    # the cut edge: a thin lift along the bottom of every stroke
    lip = np.clip(alpha - np.roll(alpha, 2, axis=0), 0.0, 1.0) * alpha
    etched = np.clip(etched + lip[..., None] * 0.13, 0.0, 1.0)

    canvas[box[1]:box[3], box[0]:box[2]] = etched

    info.update({
        "box": box,
        "mark": mark.size,
        "surface": tuple(int(round(c * 255)) for c in patch.reshape(-1, 3).mean(axis=0)),
        "ink": tuple(int(round(c * 255)) for c in ink),
    })
    return Image.fromarray((np.clip(canvas, 0, 1) * 255).round().astype(np.uint8), "RGB"), info


def main() -> None:
    check = "--check" in sys.argv
    PREVIEW.mkdir(parents=True, exist_ok=True)

    for index, placement in enumerate(PLACEMENTS):
        path = CATALOG / placement.file
        source = ORIGINALS / placement.file
        if not source.is_file():
            raise SystemExit(f"missing pristine original: {source}")
        original = Image.open(source).convert("RGB")

        # bring the photo to catalogue size first, then etch: the mark is then
        # drawn at full resolution and stays crisp instead of being upscaled
        height = round(original.height * OUTPUT_WIDTH / original.width)
        enlarged = original.resize((OUTPUT_WIDTH, height), Image.LANCZOS)
        out, info = engrave(enlarged, placement)

        print(f"{placement.product}")
        print(f"  mark {info['mark'][0]}x{info['mark'][1]} px in {info['box']} "
              f"| surface {info['surface']} -> ink {info['ink']}")
        if "cleared" in info:
            print(f"  old drawn mark cleared at {info['cleared']}")
        print(f"  {placement.note}")

        if not check:
            out.save(path, "WEBP", quality=94, method=6)
            print(f"  written {path.name} {out.size} {path.stat().st_size // 1024} KB")

        # a close crop of the engraving, for checking by eye
        left, top, right, bottom = info["box"]
        margin = max(30, (right - left) // 2)
        crop = out.crop((max(0, left - margin), max(0, top - margin),
                         min(out.width, right + margin), min(out.height, bottom + margin)))
        crop = crop.resize((crop.width * 2, crop.height * 2), Image.LANCZOS)
        crop.save(PREVIEW / f"{index + 1}_{placement.file.split('.')[0][:8]}.png")


if __name__ == "__main__":
    main()
