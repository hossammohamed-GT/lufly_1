#!/usr/bin/env python3
"""Build the portrait (phone) hero artwork.

The phone hero is a full-height photo, i.e. a tall box (~0.5 aspect), and the
masters are landscape, so the crop is what makes or breaks it. Instead of
letting the browser always take the middle, the window is picked per photo:

  * the window where the frame is brightest (the lit subject on these dark
    product shots) when that is where the product really is;
  * the centred window otherwise - the brightness pick is occasionally fooled
    by a bright wall, and the contact sheet is what decides.

Nothing is upscaled: the full height of the master is kept, so the crop is the
tallest portrait slice the source can give (1920x1072 -> 640x1072).

    python3 tools/media_audit/hero_portrait_crops.py --sheet

Writes public/images/lifestyle/heroc-<n>-p.webp (dark) and
heroc-<n>-light-p.jpg (light), plus a contact sheet when --sheet is given.
"""
from __future__ import annotations

import argparse
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
LIFE = ROOT / 'public/images/lifestyle'
REPORT = ROOT / 'storage/reports'

COLS = 192          # brightness profile resolution
WIN_COLS = 80       # window width in profile columns (80/192 * width)

# Heroc-1 (tap, right of frame) and heroc-2 (rain shower, upper centre) sit off
# centre and the brightness pick finds them; 3-5 are centred compositions where
# the pick locks onto a bright wall instead of the product.
WINDOW_MODE = {1: 'auto', 2: 'auto', 3: 'centre', 4: 'centre', 5: 'centre'}

DARK_CROP_H = 1072  # the master's full height
LIGHT_CROP_H = 1290  # light portrait masters are 768x1376


def run(args: list[str]) -> str:
    return subprocess.run(args, capture_output=True, text=True, check=True).stdout


def luma_profile(path: Path) -> list[float]:
    text = run(['convert', str(path), '-resize', f'{COLS}x1!', '-depth', '8', 'txt:-'])
    values = []
    for line in text.splitlines()[1:]:
        m = re.match(r'^\d+,\d+: \((\d+),(\d+),(\d+)', line)
        if m:
            r, g, b = (float(x) for x in m.groups())
            values.append(0.299 * r + 0.587 * g + 0.114 * b)
    return values


def best_window(values: list[float]) -> int:
    best, best_mean = 0, -1.0
    for start in range(0, max(1, len(values) - WIN_COLS + 1)):
        mean = sum(values[start:start + WIN_COLS]) / WIN_COLS
        if mean > best_mean:
            best, best_mean = start, mean
    return best


def window_for(index: int, master: Path) -> tuple[int, int]:
    """(x offset, crop width) in master pixels."""
    width = int(run(['identify', '-format', '%w', str(master)]).strip())
    crop_w = round(width * WIN_COLS / COLS)
    value = width / COLS

    if WINDOW_MODE[index] == 'auto':
        x = round(best_window(luma_profile(master)) * value)
    else:
        x = round((width - crop_w) / 2)

    return max(0, min(x, width - crop_w)), crop_w


def build_dark(index: int) -> dict:
    master = LIFE / f'heroc-{index}.webp'
    x, crop_w = window_for(index, master)
    out = LIFE / f'heroc-{index}-p.webp'
    run([
        'convert', str(master),
        '-crop', f'{crop_w}x{DARK_CROP_H}+{x}+0', '+repage',
        '-quality', '84', '-define', 'webp:method=6',
        str(out),
    ])
    return {'file': out.name, 'from': master.name, 'window': f'x={x} w={crop_w}',
            'mode': WINDOW_MODE[index]}


def build_light(index: int) -> dict:
    master = LIFE / f'heroc-{index}-light-m.jpg'      # already a portrait frame
    height = int(run(['identify', '-format', '%h', str(master)]).strip())
    y = max(0, round((height - LIGHT_CROP_H) / 2))
    out = LIFE / f'heroc-{index}-light-p.jpg'
    run([
        'convert', str(master),
        '-crop', f'768x{LIGHT_CROP_H}+0+{y}', '+repage',
        '-quality', '82', '-strip', '-interlace', 'Plane',
        str(out),
    ])
    return {'file': out.name, 'from': master.name, 'window': f'y={y} h={LIGHT_CROP_H}',
            'mode': 'portrait master'}


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument('--sheet', action='store_true', help='write a contact sheet of the crops')
    args = ap.parse_args()

    rows = [build_dark(i) for i in range(1, 6)]
    rows += [build_light(i) for i in range(1, 6)]

    print(f'{"file":26s} {"from":24s} {"window":22s} mode')
    total = 0
    for row in rows:
        path = LIFE / row['file']
        size = path.stat().st_size
        total += size
        dims = run(['identify', '-format', '%wx%h', str(path)]).strip()
        print(f'{row["file"]:26s} {row["from"]:24s} {row["window"]:22s} {row["mode"]}  {dims} {size // 1024} KB')
    print(f'total {total // 1024} KB')

    if args.sheet:
        tiles = []
        for i in range(1, 6):
            tile = REPORT / f'_p_{i}.png'
            # the phone box the artwork has to fill (iPhone 12: 390x748 css px)
            box = REPORT / f'_box_{i}.png'
            for src, dst in ((LIFE / f'heroc-{i}-p.webp', tile), (LIFE / f'heroc-{i}-light-p.jpg', box)):
                run(['convert', str(src), '-resize', '300x520^', '-gravity', 'center',
                     '-extent', '300x520', str(dst)])
            tiles += [tile, box]
        run(['montage', *[str(t) for t in tiles], '-tile', '5x2', '-geometry', '+5+5',
             '-background', '#20262b', str(REPORT / 'hero-portrait-crops.png')])
        for tile in tiles:
            tile.unlink()
        print('wrote storage/reports/hero-portrait-crops.png')

    return 0


if __name__ == '__main__':
    sys.exit(main())
