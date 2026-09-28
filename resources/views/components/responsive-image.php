<?php
/**
 * Photographic <img> at FULL original quality.
 *
 * Quality policy (2026-09-29, owner decision): no image on this site is ever
 * served at reduced quality. The old downscaled-WebP rendition ladder
 * (`<name>.jpg@<width>w.webp`, built by tools/media_audit/optimize_images.py)
 * and the .htaccess WebP negotiation were removed: every screen now fetches
 * the untouched original file, whatever its width. This partial keeps its
 * props API for callers but simply renders a plain <img>.
 *
 * @var string   $src     path under public/, e.g. images/lifestyle/spa-suite.jpg
 * @var string   $alt
 * @var string   $sizes   ignored (kept for caller compatibility)
 * @var string   $class   classes for the <img>
 * @var string   $id      id for the <img>
 * @var int|null $width   intrinsic width, for reserving layout space
 * @var int|null $height  intrinsic height
 * @var string   $loading "lazy" (default) or "eager"
 * @var int[]    $widths  ignored (kept for caller compatibility)
 */

$loading = $loading ?? 'lazy';

$attributes = '';
$attributes .= ' src="' . e(asset($src)) . '"';
$attributes .= ' alt="' . e($alt) . '"';
$attributes .= isset($class) && $class !== '' ? ' class="' . e($class) . '"' : '';
$attributes .= isset($id) && $id !== '' ? ' id="' . e($id) . '"' : '';
$attributes .= isset($width) ? ' width="' . (int) $width . '"' : '';
$attributes .= isset($height) ? ' height="' . (int) $height . '"' : '';
$attributes .= ' loading="' . e($loading) . '"';
$attributes .= ' decoding="async"';
?>
<img<?= $attributes ?>>
