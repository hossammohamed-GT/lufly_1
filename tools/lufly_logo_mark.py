#!/usr/bin/env python3
"""The LUFLY lock-up, taken straight from the brand file.

Every product photo in the catalogue carries the same mark, so it is defined
once here: `public/images/logo.png` — the nested double-L symbol plus the
"Lufly" wordmark — turned into an anti-aliased alpha mask that can be stamped
onto a photo at any size and in any tone. Nothing is redrawn by hand, so the
engraving always matches the logo the site itself uses.

    python3 tools/lufly_logo_mark.py out.png 600      # preview, 600 px wide
"""

from __future__ import annotations

import sys
from functools import lru_cache
from pathlib import Path

import numpy as np
from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
LOGO = ROOT / "public" / "images" / "logo.png"

# traced at this multiple of the final size, then reduced: keeps the corners of
# the glyphs square and the hairlines of the symbol solid
WORK_SCALE = 3


@lru_cache(maxsize=1)
def _mask() -> tuple[Image.Image, float]:
    """The mark's ink as a clean L-mode image, cropped to its bounding box."""
    logo = Image.open(LOGO).convert("RGBA")
    alpha = logo.getchannel("A")
    box = alpha.getbbox()
    if box is None:
        raise SystemExit("logo.png has no ink")
    alpha = alpha.crop(box)

    # the brand file is 1-bit alpha: rebuild a crisp edge, then soften it once
    alpha = alpha.resize((alpha.width * WORK_SCALE, alpha.height * WORK_SCALE), Image.LANCZOS)
    alpha = alpha.point(lambda value: 255 if value >= 128 else 0)
    if alpha.mode != "L":
        alpha = alpha.convert("L")
    return alpha, alpha.width / alpha.height


def lockup(width_px: int) -> Image.Image:
    """The mark as a white-on-transparent mask, `width_px` pixels wide."""
    source, ratio = _mask()
    height_px = max(1, round(width_px / ratio))
    return source.resize((width_px, height_px), Image.LANCZOS)


def ink(alpha: Image.Image, colour: tuple[int, int, int]) -> Image.Image:
    """The mask filled with a single colour, ready to paste."""
    layer = Image.new("RGB", alpha.size, colour)
    layer.putalpha(alpha)
    return layer


if __name__ == "__main__":
    out_path = Path(sys.argv[1]) if len(sys.argv) > 1 else Path("/tmp/work/lockup.png")
    target = int(sys.argv[2]) if len(sys.argv) > 2 else 600
    mark = lockup(target)
    preview = Image.new("RGB", (mark.width + 80, mark.height + 80), (38, 38, 42))
    preview.paste((238, 238, 242), (40, 40), mark)
    preview.save(out_path)
    print(f"saved {out_path} — mark {mark.size}, from {np.array(mark).mean():.0f}/255 average ink")
