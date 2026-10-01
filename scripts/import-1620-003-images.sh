#!/usr/bin/env bash
# Install the supplied 1620-003 product images without resizing, re-encoding,
# stripping metadata, or changing their file format. The destination names are
# the paths registered in the catalog database.
set -Eeuo pipefail

main_source="public/deleted/1.png"
drawing_source="public/deleted/WhatsApp Image 2026-10-01 at 5.21.59 PM.jpeg"
main_destination="public/images/products/prod_1620-003-main.png"
drawing_destination="public/images/products/prod_1620-003-drawing.jpeg"

for source in "$main_source" "$drawing_source"; do
    if [[ ! -f "$source" ]]; then
        printf 'Missing source image: %s\n' "$source" >&2
        exit 1
    fi
done

# cp copies the bytes verbatim; --preserve=all retains source metadata where
# the filesystem allows it. cmp verifies that no pixel or metadata data changed.
cp --preserve=all -- "$main_source" "$main_destination"
cp --preserve=all -- "$drawing_source" "$drawing_destination"
cmp --silent -- "$main_source" "$main_destination"
cmp --silent -- "$drawing_source" "$drawing_destination"

printf 'Installed original-quality images:\n- %s\n- %s\n' "$main_destination" "$drawing_destination"
sha256sum "$main_destination" "$drawing_destination"
