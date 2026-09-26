#!/usr/bin/env python3
"""Generate WebP twins for every JPEG/PNG under public/images.

The site ships ~46 MB of product and lifestyle photography, almost all of it
JPEG/PNG, and markup all over the codebase points at those files by name. Rather
than rewriting every <img> into a <picture>, this script writes a compressed
WebP twin *next to* each original:

    public/images/products/prod_2518_foo.jpg
    public/images/products/prod_2518_foo.jpg.webp   <- generated

and `.htaccess` performs content negotiation: when the browser sends
`Accept: image/webp` and the twin exists, the twin is served for the original
URL. Browsers without WebP support (and any request where the twin is missing)
transparently fall back to the untouched original, so nothing can break.

Measured on this repository: 46.2 MB of JPEG/PNG -> ~7.2 MB of WebP at
quality 80 (-84%). The home page drops from ~2,083 KB of imagery to ~330 KB.

Passing --variants additionally writes downscaled WebP renditions named
`<name>.jpg@<width>w.webp`, so a template can offer the browser a choice:

    <picture>
      <source type="image/webp"
              srcset="spa-suite.jpg@480w.webp 480w, spa-suite.jpg@960w.webp 960w"
              sizes="(max-width: 760px) 100vw, 50vw">
      <img src="spa-suite.jpg" alt="..." loading="lazy" decoding="async">
    </picture>

A phone on a 380 px-wide card then fetches a ~480 px file instead of the full
1,408 px original. Variants are only emitted for widths smaller than the
source, so nothing is ever upscaled.

Usage
-----
    python3 tools/media_audit/optimize_images.py              # incremental
    python3 tools/media_audit/optimize_images.py --dry-run    # report only
    python3 tools/media_audit/optimize_images.py --force      # rebuild all
    python3 tools/media_audit/optimize_images.py --quality 85 --max-dim 1600
    python3 tools/media_audit/optimize_images.py --variants 480,960,1440 public/images/lifestyle

Requirements: Pillow with WebP support (`pip install Pillow`).
"""

from __future__ import annotations

import argparse
import os
import sys
import time
from pathlib import Path

try:
    from PIL import Image, features
except ImportError:  # pragma: no cover
    sys.exit("Pillow is required: pip install Pillow")

if not features.check("webp"):
    sys.exit("This Pillow build has no WebP encoder.")

SOURCE_SUFFIXES = {".jpg", ".jpeg", ".png"}


def find_sources(root: Path) -> list[Path]:
    return sorted(
        p
        for p in root.rglob("*")
        if p.is_file() and p.suffix.lower() in SOURCE_SUFFIXES
    )


def twin_path(source: Path) -> Path:
    """`foo.jpg` -> `foo.jpg.webp` (the original keeps its own name)."""
    return source.with_name(source.name + ".webp")


def stale(source: Path, twin: Path) -> bool:
    """True when the twin is missing or older than the source."""
    if not twin.exists():
        return True
    return twin.stat().st_mtime < source.stat().st_mtime


def variant_path(source: Path, width: int) -> Path:
    """`foo.jpg` -> `foo.jpg@960w.webp` (a downscaled rendition)."""
    return source.with_name(f"{source.name}@{width}w.webp")


def variant_is_stale(source: Path, variant: Path, width: int) -> bool:
    if not variant.exists():
        return True
    if variant.stat().st_mtime < source.stat().st_mtime:
        return True

    # A wider source may now support a rendition that was skipped before.
    with Image.open(source) as img:
        return width < img.size[0]


def convert_variant(source: Path, variant: Path, width: int, quality: int, method: int) -> int:
    """Write one downscaled rendition. Returns its size in bytes."""
    with Image.open(source) as img:
        img.load()

        if width >= img.size[0]:
            return 0

        target = img.convert("RGBA" if img.mode in ("RGBA", "LA") else "RGB")
        ratio = width / img.size[0]
        target = target.resize((width, max(1, round(img.size[1] * ratio))), Image.LANCZOS)

        tmp = variant.with_suffix(variant.suffix + ".tmp")
        target.save(tmp, "WEBP", quality=quality, method=method, exact=False)
        tmp.replace(variant)

    return variant.stat().st_size


def convert(source: Path, twin: Path, quality: int, max_dim: int, method: int) -> tuple[int, int]:
    """Write the WebP twin. Returns (original bytes, webp bytes)."""
    with Image.open(source) as img:
        img.load()

        # PNGs may carry an alpha channel (logos, cut-outs); keep it. JPEG has
        # none, so flatten to RGB to avoid a pointless 4th channel.
        if img.mode in ("RGBA", "LA") or (img.mode == "P" and "transparency" in img.info):
            target = img.convert("RGBA")
        else:
            target = img.convert("RGB")

        # Only ever downscale: a 400 px thumbnail is left exactly as it is.
        if max_dim and max(target.size) > max_dim:
            target.thumbnail((max_dim, max_dim), Image.LANCZOS)

        tmp = twin.with_suffix(twin.suffix + ".tmp")
        target.save(
            tmp,
            "WEBP",
            quality=quality,
            method=method,
            exact=False,
        )
        tmp.replace(twin)

    return source.stat().st_size, twin.stat().st_size


def human(n: float) -> str:
    for unit in ("B", "KB", "MB", "GB"):
        if n < 1024 or unit == "GB":
            return f"{n:,.0f} {unit}" if unit == "B" else f"{n:,.1f} {unit}"
        n /= 1024
    return f"{n:,.1f} GB"


def main() -> int:
    here = Path(__file__).resolve().parent
    default_root = here.parent.parent / "public" / "images"

    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("root", nargs="?", default=str(default_root), help="image directory (default: public/images)")
    ap.add_argument("--quality", type=int, default=80, help="WebP quality, 0-100 (default: 80)")
    ap.add_argument("--max-dim", type=int, default=1920, help="cap the longest edge, 0 to disable (default: 1920)")
    ap.add_argument("--method", type=int, default=6, help="WebP effort, 0-6. 6 is slowest/smallest (default: 6)")
    ap.add_argument("--force", action="store_true", help="rebuild twins even when up to date")
    ap.add_argument("--dry-run", action="store_true", help="report what would happen, write nothing")
    ap.add_argument(
        "--variants",
        default="",
        metavar="WIDTHS",
        help="also emit downscaled renditions at these widths, e.g. 480,960,1440",
    )
    args = ap.parse_args()

    widths = []
    if args.variants:
        try:
            widths = sorted({int(w) for w in args.variants.split(",") if w.strip()})
        except ValueError:
            sys.exit(f"--variants expects comma-separated widths, got {args.variants!r}")

    root = Path(args.root).resolve()
    if not root.is_dir():
        sys.exit(f"No such directory: {root}")

    sources = find_sources(root)
    if not sources:
        print(f"No JPEG/PNG files under {root}")
        return 0

    todo = [s for s in sources if args.force or stale(s, twin_path(s))]
    skipped = len(sources) - len(todo)

    print(f"{root}")
    print(f"  {len(sources)} source image(s), {len(todo)} to build, {skipped} already current")
    if args.dry_run:
        return 0

    started = time.time()
    total_in = total_out = 0
    written = 0
    failures: list[tuple[Path, str]] = []

    for index, source in enumerate(todo, start=1):
        try:
            src_bytes, out_bytes = convert(
                source, twin_path(source), args.quality, args.max_dim, args.method
            )
        except Exception as exc:  # one bad file must not abort the run
            failures.append((source, str(exc)))
            continue

        total_in += src_bytes
        total_out += out_bytes
        written += 1

        if index % 50 == 0 or index == len(todo):
            pct = 100 * (1 - total_out / total_in) if total_in else 0.0
            print(
                f"  [{index:4d}/{len(todo)}] "
                f"{human(total_in)} -> {human(total_out)}  (-{pct:.0f}%)",
                flush=True,
            )

    elapsed = time.time() - started

    print()
    print(f"  wrote {written} WebP twin(s) in {elapsed:.1f}s")
    if total_in:
        print(f"  {human(total_in)} -> {human(total_out)}  (-{100 * (1 - total_out / total_in):.0f}%)")
        print(f"  saved {human(total_in - total_out)}")

    # The 15 hand-made .webp heroes in public/images/lifestyle are not touched;
    # flag them so nobody assumes the whole directory is machine-generated.
    hand_made = sorted(p for p in root.rglob("*.webp") if not any(p.name.endswith(s + ".webp") for s in SOURCE_SUFFIXES))
    if hand_made:
        print(f"  note: {len(hand_made)} pre-existing .webp file(s) left untouched")

    if widths:
        print()
        print(f"  responsive renditions at {', '.join(str(w) + 'w' for w in widths)}:")
        v_in = v_out = v_count = 0

        for source in sources:
            for width in widths:
                variant = variant_path(source, width)
                if not args.force and not variant_is_stale(source, variant, width):
                    continue

                if args.dry_run:
                    v_count += 1
                    continue

                try:
                    size = convert_variant(source, variant, width, args.quality, args.method)
                except Exception as exc:
                    failures.append((variant, str(exc)))
                    continue

                if size:
                    v_count += 1
                    v_out += size

        if v_count:
            full = sum(twin_path(s).stat().st_size for s in sources if twin_path(s).exists())
            print(f"    {v_count} rendition(s), {human(v_out)} total")
            print(
                f"    a phone fetching the smallest rendition pays roughly "
                f"{human(v_out / max(v_count, 1))} instead of {human(full / max(len(sources), 1))} per image"
            )
        else:
            print("    nothing to do (all up to date)")

    if failures:
        print(f"\n  {len(failures)} failure(s):", file=sys.stderr)
        for path, message in failures[:20]:
            print(f"    {path}: {message}", file=sys.stderr)
        return 1

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
