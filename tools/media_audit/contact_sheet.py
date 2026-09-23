#!/usr/bin/env python3
"""Build labelled contact sheets (PNG) so a human can eyeball many images fast."""
from __future__ import annotations

import argparse
from pathlib import Path

from PIL import Image, ImageDraw

CELL = 260
PAD = 6
LABEL_H = 14


def sheet(paths: list[Path], cols: int, out: Path, cell: int = CELL) -> None:
    rows = (len(paths) + cols - 1) // cols
    w = cols * (cell + PAD) + PAD
    h = rows * (cell + LABEL_H + PAD) + PAD
    canvas = Image.new("RGB", (w, h), "white")
    draw = ImageDraw.Draw(canvas)
    for i, p in enumerate(paths):
        r, c = divmod(i, cols)
        x = PAD + c * (cell + PAD)
        y = PAD + r * (cell + LABEL_H + PAD)
        draw.text((x + 2, y), f"{i}: {p.name[:44]}", fill="black")
        try:
            with Image.open(p) as im:
                im = im.convert("RGB")
                im.thumbnail((cell, cell), Image.LANCZOS)
                canvas.paste(im, (x + (cell - im.width) // 2, y + LABEL_H + (cell - im.height) // 2))
        except Exception as exc:  # pragma: no cover - diagnostics only
            draw.text((x + 2, y + 40), f"ERR {exc}", fill="red")
        draw.rectangle([x, y + LABEL_H, x + cell - 1, y + LABEL_H + cell - 1], outline=(200, 200, 200))
    out.parent.mkdir(parents=True, exist_ok=True)
    canvas.save(out)


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--out", required=True)
    ap.add_argument("--cols", type=int, default=6)
    ap.add_argument("files", nargs="+")
    args = ap.parse_args()
    sheet([Path(f) for f in args.files], args.cols, Path(args.out))


if __name__ == "__main__":
    main()
