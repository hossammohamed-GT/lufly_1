#!/usr/bin/env python3
"""Etch the LUFLY mark onto the Kitchen photos, without touching the product.

Same contract as `lufly_logo_engrave.py`: the mark is never drawn by hand. It is
read from `public/images/logo.png` through `lufly_logo_mark.lockup()`, so what
lands on the product is the brand's own nested double-L plus the "Lufly"
wordmark, and it is composited in multiply mode so the etch inherits every
highlight, reflection and grain of the surface underneath.

Where a mark goes is decided by what the object is, not by where it is easy:

  mixers   the front face of the body or the column, between the spout base and
           the base plate -- the flat machined panel a manufacturer stamps.
           A tall lever-style mixer also takes it on the front of the paddle.
  sinks    on the base of the bowl, just in front of the drain, which is where
           a stamp survives decades of scrubbing and stays clear of the
           overflow and of the tap.
  traps    on the flat face of the trap body.

The pristine plates live in `public/images/products/originals/kitchen/`, so the
tool can be re-run any number of times without stacking one etch on another.

    python3 tools/lufly_kitchen_engrave.py             # write every placement
    python3 tools/lufly_kitchen_engrave.py --check     # report, write nothing
    python3 tools/lufly_kitchen_engrave.py --preview   # QA crops in /tmp
"""

from __future__ import annotations

import sys
from dataclasses import dataclass
from pathlib import Path

import numpy as np
from PIL import Image, ImageFilter

from lufly_logo_mark import lockup

ROOT = Path(__file__).resolve().parent.parent
PLATES = ROOT / "public" / "images" / "products" / "originals" / "kitchen"
CATALOG = ROOT / "public" / "images" / "products" / "catalog"
UPLOADS = ROOT / "public" / "images" / "products" / "uploads"
PREVIEW = Path("/tmp/work/kitchen/preview")
OUTPUT_WIDTH = 1600
# a little breathing room around the product, so every card is framed alike
MARGIN = 0.022


@dataclass(frozen=True)
class Placement:
    """One engraving, measured in the plate's own pixels."""

    plate: str           # file in originals/kitchen/
    target: str          # file the site actually serves
    product: str
    mark_width: int      # width of the mark, in plate pixels
    centre: tuple[int, int]  # mark centre, in plate pixels
    strength: float = 1.4
    # laser marks read as a darker tone on light surfaces and a lighter one on
    # dark surfaces; which way it goes is decided by the surface, not by taste
    polarity: str = "auto"
    note: str = ""


PLACEMENTS = [
    # ---- mixers: front of the body / column ---------------------------------
    Placement(
        plate="1653-8033c.webp",
        target="2384317c777399237308792133359e3c0006.jpg",
        product="1653-8033C · pull-out kitchen mixer",
        mark_width=92,
        centre=(612, 1112),
        strength=1.45,
        note="front face of the body column, between the spout base and the base plate",
    ),
    Placement(
        plate="1654-0760.webp",
        target="6c0c9f0c12e74411d6af8b3c.webp",
        product="1654-0760 · wall-mounted bath/shower mixer",
        mark_width=190,
        centre=(330, 600),
        strength=1.4,
        note="front face of the horizontal body, below the swan-neck spout",
    ),
    Placement(
        plate="1644-8002gg.webp",
        target="036119f3be279325d18d444a.webp",
        product="1644-8002GG · single-lever pull-out mixer, brushed gold",
        mark_width=76,
        centre=(612, 1100),
        strength=1.6,
        note="front face of the column, below the lever hub and above the base plate",
    ),
    Placement(
        plate="1644-8033mb.webp",
        target="cdf70e8ccda1660955018315.webp",
        product="1644-8033MB · single-lever mixer, matte black",
        mark_width=104,
        centre=(638, 900),
        strength=1.7,
        note="front face of the square column, above the base plate",
    ),
    # ---- sinks: base of the bowl, in front of the drain ---------------------
    Placement(
        plate="4000-270a.webp",
        target="3b0254da3af2a128ac0bbe97.webp",
        product="4000-270A · square undermount sink",
        mark_width=230,
        centre=(517, 700),
        strength=1.5,
        note="on the base of the bowl, in front of the drain",
    ),
    Placement(
        plate="4000-277a.webp",
        target="e9fb76f276725089c5bf91d9.webp",
        product="4000-277A · rectangular undermount sink",
        mark_width=200,
        centre=(700, 640),
        strength=1.5,
        note="on the base of the bowl, in front of the drain",
    ),
    Placement(
        plate="4000-359c.webp",
        target="8a60f12ac2523604192fdd87.webp",
        product="4000-359C · topmount sink with drainer",
        mark_width=250,
        centre=(430, 566),
        strength=1.5,
        note="on the base of the bowl, in front of the drain",
    ),
    Placement(
        plate="4000-288.webp",
        target="0b8d50ee5e32ca1aedd459de.webp",
        product="4000-288 · small topmount sink",
        mark_width=270,
        centre=(585, 650),
        strength=1.5,
        note="on the base of the bowl, in front of the drain",
    ),
    Placement(
        plate="4000-7644a.webp",
        target="bc6de6dc65ed7b02bf18327b.webp",
        product="4000-7644A · double-bowl undermount sink",
        mark_width=200,
        centre=(390, 596),
        strength=1.5,
        note="on the base of the left-hand bowl, in front of the drain",
    ),
    # ---- second batch: positions 11-20 on the Kitchen grid -------------------
    # The bin is a plain cylinder: the mark sits mid-front, where a maker
    # stamps the body, not on the lid.
    Placement(
        plate="ga-00403.webp",
        target="20d22cbada0d5c99f1f2e74b.webp",
        product="GA-00403 · stainless-steel kitchen bin",
        mark_width=150,
        centre=(368, 1150),
        strength=1.5,
        note="front face of the cylindrical body, mid height",
    ),
    # Sinks: on the bowl base in front of the drain, as in the first batch.
    Placement(
        plate="3001-1600.webp",
        target="9d6f863916d6a7ed1c4edb17.webp",
        product="3001-1600 · single-bowl sink with drainer",
        mark_width=165,
        centre=(470, 580),
        strength=1.5,
        note="on the base of the bowl, in front of the drain",
    ),
    # Freestanding unit: the flat apron below the basin, facing the customer.
    Placement(
        plate="3001-1630.webp",
        target="9dfe4f7c3d8956d9319f8d7d.webp",
        product="3001-1630 · freestanding sink unit",
        mark_width=150,
        centre=(530, 500),
        strength=1.55,
        note="front apron of the basin, below the rim",
    ),
    # Mixers: front face of the column, above the base plate.
    Placement(
        plate="1644-8003c.webp",
        target="9a69719994144324c0407f66.webp",
        product="1644-8003C · single-lever pull-out mixer",
        mark_width=82,
        centre=(516, 1000),
        strength=1.45,
        note="front face of the column, above the base plate",
    ),
    Placement(
        plate="1644-8004rg.webp",
        target="01c6fa9818402e86a608ba9b.webp",
        product="1644-8004RG · single-lever pull-out mixer, brushed gold",
        mark_width=83,
        centre=(213, 1120),
        strength=1.6,
        note="front face of the column, above the base plate",
    ),
    Placement(
        plate="1644-7004g.webp",
        target="206488717c77e1d4e76444e4.webp",
        product="1644-7004G · single-lever mixer",
        mark_width=75,
        centre=(581, 895),
        strength=1.45,
        note="front face of the column, below the lever hub",
    ),
    Placement(
        plate="1644-932cg.webp",
        target="76525453a0acac93814ec998.webp",
        product="1644-932CG · single-lever pull-out mixer, gunmetal",
        mark_width=76,
        centre=(519, 985),
        strength=1.55,
        note="front face of the column, above the base plate",
    ),
    Placement(
        plate="1644-7005gg.webp",
        target="5c7a87339c600e14f852cd4b.webp",
        product="1644-7005GG · single-lever mixer, anthracite",
        mark_width=88,
        centre=(492, 1035),
        strength=1.6,
        note="front face of the column, below the lever hub",
    ),
    Placement(
        plate="1653-7005gg.webp",
        target="b7e627c611e9c47404ec7b18d9149adbc85e.jpg",
        product="1653-7005GG · single-lever pull-out mixer, gunmetal",
        mark_width=210,
        centre=(1360, 2620),
        strength=1.55,
        note="front face of the column, above the base plate",
    ),
    Placement(
        plate="lxd-6010.webp",
        target="acf0511933da417db4f3585b.webp",
        product="LXD-6010 · double-bowl sink drain kit",
        mark_width=70,
        centre=(303, 468),
        strength=1.6,
        note="on the flat face of the left-hand trap body",
    ),
    # ---- plumbing: flat face of the trap body ------------------------------
    Placement(
        plate="lxd-6005.webp",
        target="7dfacc3262347268a7692f47.webp",
        product="LXD-6005 · sink drain and trap kit",
        mark_width=64,
        centre=(132, 556),
        strength=1.6,
        note="on the flat face of the trap body",
    ),
]


def frame(photo: Image.Image) -> tuple[Image.Image, float, tuple[int, int]]:
    """Crop to the product with a small margin, then bring it to 1600 px wide.

    Returns the framed photo, the scale applied to plate pixels, and the crop
    origin, so a placement measured on the plate can be mapped exactly.
    """
    grey = np.array(photo.convert("L"), dtype=np.int16)
    mask = grey < 247
    if not mask.any():
        mask = grey < 252
    rows = np.where(mask.any(axis=1))[0]
    cols = np.where(mask.any(axis=0))[0]
    pad = round(MARGIN * max(photo.size))
    left = max(0, int(cols[0]) - pad)
    top = max(0, int(rows[0]) - pad)
    right = min(photo.width, int(cols[-1]) + 1 + pad)
    bottom = min(photo.height, int(rows[-1]) + 1 + pad)

    cropped = photo.crop((left, top, right, bottom))
    scale = OUTPUT_WIDTH / cropped.width
    height = max(1, round(cropped.height * scale))
    return cropped.resize((OUTPUT_WIDTH, height), Image.LANCZOS), scale, (left, top)


def engrave(photo: Image.Image, placement: Placement) -> tuple[Image.Image, dict]:
    """Burn the lock-up into the pixels already there."""
    mark = lockup(max(24, round(placement.mark_width)))
    left = round(placement.centre[0] - mark.width / 2)
    top = round(placement.centre[1] - mark.height / 2)
    left = max(0, min(photo.width - mark.width, left))
    top = max(0, min(photo.height - mark.height, top))
    box = (left, top, left + mark.width, top + mark.height)

    canvas = np.array(photo.convert("RGB"), dtype=np.float32) / 255.0
    alpha = np.array(mark, dtype=np.float32) / 255.0
    alpha = np.array(
        Image.fromarray((alpha * 255).astype(np.uint8)).filter(ImageFilter.GaussianBlur(0.5)),
        dtype=np.float32,
    ) / 255.0

    patch = canvas[box[1]:box[3], box[0]:box[2]]
    surface = patch.reshape(-1, 3).mean(axis=0)

    polarity = placement.polarity
    if polarity == "auto":
        # a frosted laser mark on black chrome is lighter than the metal; on
        # pale steel and brass it is darker. Judge from the surface itself.
        polarity = "lift" if float(surface.mean()) < 0.42 else "darken"

    if polarity == "lift":
        ink = np.clip(surface + 0.30, 0.0, 1.0)
        bite = 0.55 * placement.strength
        etched = patch * (1.0 - bite * alpha[..., None]) + ink * (bite * alpha[..., None])
    else:
        ink = np.clip(surface * 0.42, 0.0, 1.0)
        bite = 0.58 * placement.strength
        etched = patch * (1.0 - bite * alpha[..., None]) + ink * (bite * alpha[..., None])

    # the cut edge: a thin lift along the bottom of every stroke
    lip = np.clip(alpha - np.roll(alpha, 2, axis=0), 0.0, 1.0) * alpha
    etched = np.clip(etched + lip[..., None] * 0.13, 0.0, 1.0)

    canvas[box[1]:box[3], box[0]:box[2]] = etched
    info = {
        "box": box,
        "mark": mark.size,
        "surface": tuple(int(round(c * 255)) for c in surface),
        "ink": tuple(int(round(c * 255)) for c in ink),
        "polarity": polarity,
    }
    return Image.fromarray((np.clip(canvas, 0, 1) * 255).round().astype(np.uint8), "RGB"), info


def main() -> None:
    check = "--check" in sys.argv
    want_preview = "--preview" in sys.argv
    PREVIEW.mkdir(parents=True, exist_ok=True)

    for index, placement in enumerate(PLACEMENTS):
        plate_path = PLATES / placement.plate
        if not plate_path.is_file():
            raise SystemExit(f"missing pristine plate: {plate_path}")
        plate = Image.open(plate_path).convert("RGB")

        framed, scale, origin = frame(plate)
        # map the measurement from plate pixels into the framed photo
        centre = (
            (placement.centre[0] - origin[0]) * scale,
            (placement.centre[1] - origin[1]) * scale,
        )
        scaled = Placement(
            plate=placement.plate,
            target=placement.target,
            product=placement.product,
            mark_width=max(24, round(placement.mark_width * scale)),
            centre=(round(centre[0]), round(centre[1])),
            strength=placement.strength,
            polarity=placement.polarity,
            note=placement.note,
        )
        out, info = engrave(framed, scaled)

        target_dir = UPLOADS if placement.target.endswith(".jpg") else CATALOG
        path = target_dir / placement.target

        print(f"{placement.product}")
        print(f"  plate {plate.size} -> {out.size} (x{scale:.2f})")
        print(f"  mark {info['mark'][0]}x{info['mark'][1]} px in {info['box']} "
              f"| surface {info['surface']} -> ink {info['ink']} ({info['polarity']})")
        print(f"  {placement.note}")

        if not check:
            if path.suffix.lower() == ".jpg":
                out.save(path, "JPEG", quality=95, subsampling=0)
            else:
                out.save(path, "WEBP", quality=94, method=6)
            print(f"  written {path.name} {path.stat().st_size // 1024} KB")

        left, top, right, bottom = info["box"]
        margin = max(40, (right - left) // 2)
        crop = out.crop((max(0, left - margin), max(0, top - margin),
                         min(out.width, right + margin), min(out.height, bottom + margin)))
        crop.resize((crop.width * 2, crop.height * 2), Image.LANCZOS).save(
            PREVIEW / f"{index + 1}_{placement.plate.split('.')[0][:12]}.png"
        )
        if want_preview:
            out.save(PREVIEW / f"full_{index + 1}.png")


if __name__ == "__main__":
    main()
