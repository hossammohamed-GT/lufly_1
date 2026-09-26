<?php
/**
 * Responsive <picture> for a photographic image.
 *
 * `tools/media_audit/optimize_images.py --variants` writes downscaled WebP
 * renditions next to each JPEG/PNG as `<name>.jpg@<width>w.webp`. This partial
 * offers them to the browser through srcset/sizes and keeps the untouched
 * original as the fallback, so a phone on a narrow card fetches a few tens of
 * kilobytes instead of the full-size original.
 *
 * `.htaccess` negotiates WebP for the plain `<name>.jpg` URL as well, so even
 * the fallback path is served as WebP wherever the browser can take it.
 *
 * `<picture>` is marked `display: contents` (.respic) so wrapping an <img> in it
 * does not turn the image into a nested box: grid and flex parents keep
 * treating the <img> itself as their child.
 *
 * The widths that were actually found are published on the <img> as
 * `data-respic-widths`, so a script that swaps the image (the finishes
 * showcase) can rebuild the srcset for the new file instead of silently
 * falling back to the full-size original.
 *
 * @var string   $src     path under public/, e.g. images/lifestyle/spa-suite.jpg
 * @var string   $alt
 * @var string   $sizes   CSS sizes descriptor, e.g. "(max-width: 640px) 92vw, 23vw"
 * @var string   $class   classes for the <img>
 * @var string   $id      id for the <img>
 * @var int|null $width   intrinsic width, for reserving layout space
 * @var int|null $height  intrinsic height
 * @var string   $loading "lazy" (default) or "eager"
 * @var int[]    $widths  rendition widths to look for
 */

$widths = $widths ?? [480, 960, 1440];
$loading = $loading ?? 'lazy';

$srcset = [];
$found = [];
foreach ($widths as $variant) {
    $candidate = $src . '@' . $variant . 'w.webp';

    if (is_file(base_path('public/' . ltrim($candidate, '/')))) {
        $srcset[] = e(asset($candidate)) . ' ' . (int) $variant . 'w';
        $found[] = (int) $variant;
    }
}

$attributes = '';
$attributes .= ' src="' . e(asset($src)) . '"';
$attributes .= ' alt="' . e($alt) . '"';
$attributes .= isset($class) && $class !== '' ? ' class="' . e($class) . '"' : '';
$attributes .= isset($id) && $id !== '' ? ' id="' . e($id) . '"' : '';
$attributes .= isset($width) ? ' width="' . (int) $width . '"' : '';
$attributes .= isset($height) ? ' height="' . (int) $height . '"' : '';
$attributes .= ' loading="' . e($loading) . '"';
$attributes .= ' decoding="async"';
$attributes .= ' data-respic-widths="' . e(implode(',', $found)) . '"';

if ($srcset === []): ?>
<img<?= $attributes ?>>
<?php else: ?>
<picture class="respic">
    <source type="image/webp" srcset="<?= implode(', ', $srcset) ?>" sizes="<?= e($sizes ?? '100vw') ?>">
    <img<?= $attributes ?>>
</picture>
<?php endif; ?>
