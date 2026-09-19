<?php
/**
 * Storefront product page - "machined editorial" identity.
 * Three essential views per product: main shot, live technical drawing
 * (dimension lines from product_dimensions when present), in-situ shot.
 *
 * @var Core\View\View $view
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/products/detail/detail.css');
$view->pushScript('frontend/products/detail/detail.js');
/** @var array<string, mixed> $product */
/** @var string $locale */

$fallbackImg = '/images/products/prod_146_1620-111-a.jpg';
$img = (string) ($product['image'] ?? '') !== '' ? (string) $product['image'] : $fallbackImg;
$situImg = (string) ($product['situ_image'] ?? '');
$modelCode = (string) ($product['model_code'] ?? $product['sku'] ?? '');
$variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];
$specs = is_array($product['specs'] ?? null) ? $product['specs'] : [];
$dimensions = is_array($product['dimensions'] ?? null) ? $product['dimensions'] : null;
$categorySlug = (string) ($product['category_slug'] ?? '');
$productUrl = route('products.show', ['slug' => $product['slug'] ?? $slug]);

/* pick the technical-sheet silhouette from the category */
$silhouette = 'basin';
if ($categorySlug === 'wall-hung-toilets' || $categorySlug === 'luxury-bidets') {
    $silhouette = 'toilet';
} elseif ($categorySlug === 'vanity-cabinets') {
    $silhouette = 'vanity';
}

/* dimensions in millimetres (real data when the factory enters it) */
$dims = [
    'w' => isset($dimensions['width_mm']) ? (float) $dimensions['width_mm'] : 0.0,
    'h' => isset($dimensions['height_mm']) ? (float) $dimensions['height_mm'] : 0.0,
    'd' => isset($dimensions['depth_mm']) ? (float) $dimensions['depth_mm'] : 0.0,
];
$hasDims = $dims['w'] > 0 && $dims['h'] > 0;

/* default sheet proportions per silhouette when no dimensions are stored */
$defaults = [
    'toilet' => ['w' => 540.0, 'h' => 340.0],
    'basin' => ['w' => 600.0, 'h' => 180.0],
    'vanity' => ['w' => 600.0, 'h' => 500.0],
];
$sheetW = $hasDims ? $dims['w'] : $defaults[$silhouette]['w'];
$sheetH = $hasDims ? $dims['h'] : $defaults[$silhouette]['h'];

/* scale millimetres onto a 300px-max drawing area, keep 1:1 aspect */
$k = 300.0 / max($sheetW, $sheetH, 1.0);
$k = min($k, 0.9);
$dw = $sheetW * $k;
$dh = $sheetH * $k;

/* sheet geometry (viewBox 520 x 430) */
$bx = 260.0 - $dw / 2.0;   /* outline left */
$by = 250.0 - $dh;         /* outline top (floor at y=250) */
$fmt = static fn (float $v): string => rtrim(rtrim(number_format($v, 0, '.', ''), '0'), '.');
?>
<main class="pdp" id="main">
    <div class="pdp-inner">
        <nav class="pdp-crumb" aria-label="Breadcrumb">
            <a href="<?= e(route('home')) ?>"><?= e(trans('common.factory_direct')) ?></a>
            <span class="pdp-crumb-sep" aria-hidden="true">/</span>
            <a href="<?= e(route('products.index')) ?>"><?= e(trans('products.title')) ?></a>
            <span class="pdp-crumb-sep" aria-hidden="true">/</span>
            <span class="pdp-crumb-here"><?= e($product['name'] ?? '') ?></span>
        </nav>

        <div class="pdp-grid">
            <!-- ================= media stage: 3 essential views ================= -->
            <section class="pdp-stage" aria-label="<?= e(trans('products.sheet_title')) ?>">
                <span class="pdp-stage-beam" aria-hidden="true"></span>

                <figure class="pdp-view is-on" data-pdp-view="main">
                    <img src="<?= e(asset($img)) ?>"
                         alt="<?= e($product['name'] ?? '') ?>"
                         class="pdp-view-img"
                         width="900" height="700"
                         decoding="async"
                         onerror="this.onerror=null; this.src='<?= e(asset($fallbackImg)) ?>';">
                    <figcaption class="pdp-view-tag">01 &middot; <?= e(trans('products.view_main')) ?></figcaption>
                </figure>

                <figure class="pdp-view" data-pdp-view="drawing">
                    <div class="pdp-sheet">
                        <svg class="pdp-sheet-svg" viewBox="0 0 520 430" role="img"
                             aria-label="<?= e(trans('products.sheet_title')) ?> <?= e($modelCode) ?>">
                            <!-- frame + corner ticks -->
                            <rect class="pdp-frame" x="10" y="10" width="500" height="410"/>
                            <path class="pdp-tick" d="M10 34h18M34 10v18M510 396h-18M486 420v-18"/>

                            <?php if ($silhouette === 'toilet'): ?>
                                <!-- wall-hung fixture, side elevation -->
                                <path class="pdp-line" d="M96 70v180"/>
                                <path class="pdp-ghost" d="M96 90l<?= e($dw + 40) ?> -12"/>
                                <rect class="pdp-line" x="<?= e($bx) ?>" y="<?= e($by) ?>" width="<?= e($dw) ?>" height="<?= e(round($dh * 0.58)) ?>" rx="14"/>
                                <path class="pdp-line" d="M<?= e($bx + $dw * 0.14) ?> <?= e($by + $dh * 0.58) ?> h<?= e($dw * 0.72) ?> q<?= e($dw * 0.14) ?> 0 <?= e($dw * 0.14) ?> <?= e($dh * 0.20) ?> q0 <?= e($dh * 0.22) ?> -<?= e($dw * 0.5) ?> <?= e($dh * 0.22) ?> q-<?= e($dw * 0.5) ?> 0 -<?= e($dw * 0.5) ?> -<?= e($dh * 0.22) ?> q0 -<?= e($dh * 0.14) ?> <?= e($dw * 0.14) ?> -<?= e($dh * 0.20) ?> z"/>
                                <line class="pdp-thin" x1="<?= e($bx) ?>" y1="<?= e($by + $dh * 0.30) ?>" x2="<?= e($bx + $dw) ?>" y2="<?= e($by + $dh * 0.30) ?>"/>
                            <?php elseif ($silhouette === 'vanity'): ?>
                                <!-- cabinet front elevation -->
                                <rect class="pdp-line" x="<?= e($bx) ?>" y="<?= e($by) ?>" width="<?= e($dw) ?>" height="<?= e($dh) ?>" rx="8"/>
                                <line class="pdp-thin" x1="260" y1="<?= e($by + 10) ?>" x2="260" y2="<?= e($by + $dh - 10) ?>"/>
                                <rect class="pdp-thinfill" x="<?= e($bx + $dw * 0.08) ?>" y="<?= e($by + $dh * 0.10) ?>" width="<?= e($dw * 0.84) ?>" height="<?= e($dh * 0.14) ?>" rx="6"/>
                                <line class="pdp-line" x1="<?= e($bx) ?>" y1="<?= e($by + $dh) ?>" x2="<?= e($bx + $dw) ?>" y2="<?= e($by + $dh) ?>"/>
                            <?php else: ?>
                                <!-- washbasin front elevation -->
                                <path class="pdp-line" d="M<?= e($bx) ?> <?= e($by) ?> h<?= e($dw) ?> q6 0 6 8 v<?= e(round($dh * 0.34) - 8) ?> q0 <?= e($dh * 0.38) ?> -<?= e($dw * 0.30) ?> <?= e($dh * 0.46) ?> h-<?= e($dw * 0.40) ?> q-<?= e($dw * 0.30) ?> <?= e(-$dh * 0.08) ?> -<?= e($dw * 0.30) ?> -<?= e($dh * 0.46) ?> v-<?= e(round($dh * 0.34) - 8) ?> q0 -8 6 -8 z"/>
                                <circle class="pdp-thin" cx="260" cy="<?= e($by + 12) ?>" r="4"/>
                            <?php endif; ?>

                            <?php if ($hasDims): ?>
                                <!-- width dimension line (top) -->
                                <line class="pdp-dim" x1="<?= e($bx) ?>" y1="<?= e($by - 26) ?>" x2="<?= e($bx + $dw) ?>" y2="<?= e($by - 26) ?>"/>
                                <line class="pdp-dim" x1="<?= e($bx) ?>" y1="<?= e($by - 32) ?>" x2="<?= e($bx) ?>" y2="<?= e($by - 20) ?>"/>
                                <line class="pdp-dim" x1="<?= e($bx + $dw) ?>" y1="<?= e($by - 32) ?>" x2="<?= e($bx + $dw) ?>" y2="<?= e($by - 20) ?>"/>
                                <text class="pdp-dim-label" x="<?= e(260) ?>" y="<?= e($by - 34) ?>" text-anchor="middle">W <?= e($fmt($dims['w'])) ?></text>
                                <!-- height dimension line (right) -->
                                <line class="pdp-dim" x1="<?= e($bx + $dw + 26) ?>" y1="<?= e($by) ?>" x2="<?= e($bx + $dw + 26) ?>" y2="<?= e($by + $dh) ?>"/>
                                <line class="pdp-dim" x1="<?= e($bx + $dw + 20) ?>" y1="<?= e($by) ?>" x2="<?= e($bx + $dw + 32) ?>" y2="<?= e($by) ?>"/>
                                <line class="pdp-dim" x1="<?= e($bx + $dw + 20) ?>" y1="<?= e($by + $dh) ?>" x2="<?= e($bx + $dw + 32) ?>" y2="<?= e($by + $dh) ?>"/>
                                <text class="pdp-dim-label" x="<?= e($bx + $dw + 38) ?>" y="<?= e($by + $dh / 2) ?>" text-anchor="start">H <?= e($fmt($dims['h'])) ?></text>
                            <?php else: ?>
                                <text class="pdp-dim-label" x="260" y="<?= e($by - 30) ?>" text-anchor="middle"><?= e(trans('products.sheet_dims_on_request')) ?></text>
                            <?php endif; ?>

                            <!-- title block -->
                            <line class="pdp-thin" x1="10" y1="382" x2="510" y2="382"/>
                            <text class="pdp-block-strong" x="24" y="402">MODEL <?= e($modelCode) ?></text>
                            <text class="pdp-block" x="290" y="402">EN 997 &middot; CE</text>
                            <text class="pdp-block" x="400" y="402"><?= e(trans('products.sheet_scale')) ?></text>
                        </svg>
                    </div>
                    <figcaption class="pdp-view-tag">02 &middot; <?= e(trans('products.view_drawing')) ?></figcaption>
                </figure>

                <figure class="pdp-view" data-pdp-view="situ">
                    <?php if ($situImg !== ''): ?>
                        <img src="<?= e(asset($situImg)) ?>"
                             alt="<?= e(($product['name'] ?? '') . ' - installed') ?>"
                             class="pdp-view-img pdp-view-img-cover"
                             width="900" height="700"
                             loading="lazy" decoding="async">
                    <?php endif; ?>
                    <figcaption class="pdp-view-tag">03 &middot; <?= e(trans('products.view_situ')) ?></figcaption>
                </figure>

                <div class="pdp-viewtabs" role="tablist" aria-label="<?= e(trans('products.sheet_title')) ?>">
                    <button type="button" class="pdp-viewtab is-on" data-pdp-tab="main" role="tab" aria-selected="true">
                        <span class="pdp-viewtab-code">01</span><?= e(trans('products.view_main')) ?>
                    </button>
                    <button type="button" class="pdp-viewtab" data-pdp-tab="drawing" role="tab" aria-selected="false">
                        <span class="pdp-viewtab-code">02</span><?= e(trans('products.view_drawing')) ?>
                    </button>
                    <button type="button" class="pdp-viewtab" data-pdp-tab="situ" role="tab" aria-selected="false">
                        <span class="pdp-viewtab-code">03</span><?= e(trans('products.view_situ')) ?>
                    </button>
                </div>
            </section>

            <!-- ================= info panel ================= -->
            <aside class="pdp-info">
                <span class="pdp-eyebrow"><?= e(trans('common.factory_direct')) ?></span>
                <h1 class="pdp-title"><?= e($product['name'] ?? '') ?></h1>

                <div class="pdp-meta-row">
                    <span class="pdp-code-chip"><?= e(trans('common.sku')) ?>: <?= e($modelCode) ?></span>
                    <?php if (!empty($product['is_featured'])): ?>
                        <span class="pdp-code-chip is-teal"><?= e(trans('common.featured')) ?></span>
                    <?php endif; ?>
                </div>

                <?php if (count($variants) > 1): ?>
                    <div class="pdp-variants" aria-label="<?= e(trans('products.variants_label')) ?>">
                        <?php foreach (array_slice($variants, 0, 4) as $variant): ?>
                            <span class="pdp-variant"><?= e((string) ($variant['variant_name'] ?? $variant['sku'] ?? '')) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="pdp-guarantee">
                    <span class="pdp-guarantee-shield" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M12 3 20 6.2v5.3c0 4.9-3.4 8.6-8 10.5-4.6-1.9-8-5.6-8-10.5V6.2L12 3Z"/><path d="m8.8 12 2.2 2.2 4.2-4.7"/></svg>
                        <b>10Y</b>
                    </span>
                    <div>
                        <strong><?= e(trans('products.guarantee_title')) ?></strong>
                        <p><?= e(trans('products.guarantee_std')) ?></p>
                    </div>
                </div>

                <?php $desc = (string) ($product['short_description'] ?? $product['description'] ?? ''); ?>
                <?php if ($desc !== ''): ?>
                    <div class="pdp-desc">
                        <h3 class="pdp-block-title"><?= e(trans('products.description')) ?></h3>
                        <p><?= nl2br(e($desc)) ?></p>
                    </div>
                <?php endif; ?>

                <div class="pdp-specs">
                    <h3 class="pdp-block-title"><?= e(trans('products.specs_title')) ?></h3>
                    <dl class="pdp-specs-list">
                        <?php foreach ($specs as $row): ?>
                            <?php
                                $key = (string) ($row['spec_key'] ?? '');
                                $value = (string) ($row['spec_value'] ?? '');
                                if ($key === '' || $value === '') { continue; }
                                $unit = (string) ($row['unit'] ?? '');
                            ?>
                            <div class="pdp-spec-row">
                                <dt><?= e($key) ?></dt>
                                <dd><?= e($value . ($unit !== '' ? ' ' . $unit : '')) ?></dd>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($hasDims): ?>
                            <div class="pdp-spec-row">
                                <dt><?= e(trans('products.dimensions')) ?></dt>
                                <dd><?= e($fmt($dims['w'])) ?> &times; <?= e($fmt($dims['h'])) ?> &times; <?= e($fmt($dims['d'])) ?> mm</dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                    <p class="pdp-spec-note">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <?= e(trans('products.spec_note')) ?>
                    </p>
                </div>

                <div class="pdp-actions">
                    <a href="<?= e(route('contact')) ?>" class="pdp-cta">
                        <span><?= e(trans('products.request_quote')) ?></span>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="<?= e(route('products.index')) ?>" class="pdp-back">&larr; <?= e(trans('products.title')) ?></a>
                </div>
                <p class="pdp-quote-note"><?= e(trans('products.quote_note')) ?></p>
            </aside>
        </div>
    </div>
</main>

<!-- Schema.org JSON-LD Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": <?= json_encode($product['name'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
  "image": <?= json_encode(array_values(array_filter([
      asset($img),
      $situImg !== '' ? asset($situImg) : null,
  ])), JSON_UNESCAPED_UNICODE) ?>,
  "description": <?= json_encode($product['short_description'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
  "sku": <?= json_encode(($product['sku'] ?? '') !== '' ? $product['sku'] : $modelCode, JSON_UNESCAPED_UNICODE) ?>,
  "mpn": <?= json_encode($modelCode, JSON_UNESCAPED_UNICODE) ?>,
  "brand": {
    "@type": "Brand",
    "name": "LUFLY"
  },
  "manufacturer": {
    "@type": "Organization",
    "name": "LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ"
  },
  "offers": {
    "@type": "Offer",
    "url": <?= json_encode($productUrl, JSON_UNESCAPED_UNICODE) ?>,
    "priceCurrency": "EUR",
    "availability": "https://schema.org/<?= e(($product['stock_status'] ?? 'in_stock') === 'out_of_stock' ? 'OutOfStock' : 'InStock') ?>"
  }
}
</script>
