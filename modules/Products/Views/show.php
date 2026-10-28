<?php
/**
 * Product detail page — Editorial Luxury Showroom & Marketplace Experience.
 *
 * - Laptop / Desktop (>= 980px): Left vertical thumbnail rail + warm studio
 *   pedestal stage, Playfair Display editorial typography, #1C8B8B signature
 *   teal actions, full-width visual "Other Available Variants" cards band,
 *   3-column tabbed architectural dossier, and two-tier marketplace discovery.
 * - Mobile (< 980px): Compact thumb-friendly color swatches right inside the
 *   product header (variant cards hidden to keep mobile fast and scannable) +
 *   sticky bottom conversion dock.
 *
 * @var Core\View\View                                                            $view
 * @var array<string, mixed>                                                      $product
 * @var array<string, mixed>|null                                                 $card
 * @var bool|null                                                                 $saved
 * @var bool|null                                                                 $inBox
 * @var array<int, int>|null                                                      $savedIds
 * @var array<int, int>|null                                                      $boxIds
 * @var string|null                                                               $datasheetUrl
 * @var array{position:int,total:int,prev_slug:?string,next_slug:?string}|null    $categoryNeighbors
 * @var array<int, array<string, mixed>>                                          $similarProducts
 * @var array<int, array<string, mixed>>                                          $complementaryProducts
 * @var array<int, array<string, string>>                                         $categoryBreadcrumbs
 * @var string                                                                    $locale
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/css/product-card.css');
$view->pushStyle('frontend/products/detail/detail.css');
$view->pushScript('frontend/products/catalog/catalog.js');
$view->pushScript('frontend/js/product-card-variants.js');
$view->pushScript('frontend/products/detail/detail.js');
if (feature('favorites', true)) {
    $view->pushStyle('frontend/favorites/favorites.css');
    $view->pushScript('frontend/favorites/favorites.js');
}
if (feature('box', true)) {
    $view->pushStyle('frontend/box/box.css');
    $view->pushScript('frontend/box/box.js');
}

$fallbackImg = '/images/favicon.png';
$img = trim((string) ($product['image'] ?? '')) !== '' ? trim((string) $product['image']) : $fallbackImg;
$situImg = trim((string) ($product['situ_image'] ?? ''));

$name = trim((string) ($product['name'] ?? ''));
if ($name === '') {
    $name = trans('products.name_pending');
}
$product['name'] = $name;

$code = trim((string) ($product['model_code'] ?? $product['sku'] ?? ''));
$productId = (int) ($product['id'] ?? 0);
$productSlug = (string) ($product['slug'] ?? ($slug ?? ''));
$productUrl = route('products.show', ['slug' => $productSlug]);
$canonicalUrl = url($productUrl);

/* Three independent media groups populated by Product::translate() */
$gallery = array_values(array_filter(
    is_array($product['gallery'] ?? null) ? $product['gallery'] : [],
    static fn ($src): bool => is_string($src) && trim($src) !== '',
));
$drawings = array_values(array_filter(
    is_array($product['drawings'] ?? null) ? $product['drawings'] : [],
    static fn ($src): bool => is_string($src) && trim($src) !== '',
));
$situImages = array_values(array_filter(
    is_array($product['situ_images'] ?? null) ? $product['situ_images'] : [],
    static fn ($src): bool => is_string($src) && trim($src) !== '',
));
if ($situImages === [] && $situImg !== '') {
    $situImages = [$situImg];
}
if ($gallery === [] && $drawings === [] && $img !== '') {
    $gallery = [$img];
}

$slides = [];
foreach ($gallery as $src) {
    $slides[] = ['url' => asset($src), 'alt' => $name . ' — ' . trans('products.view_main'), 'group' => 'gallery'];
}
foreach ($drawings as $src) {
    $slides[] = ['url' => asset($src), 'alt' => $name . ' — ' . trans('products.view_drawing'), 'group' => 'drawing'];
}
foreach ($situImages as $src) {
    $slides[] = ['url' => asset($src), 'alt' => $name . ' — ' . trans('products.view_situ'), 'group' => 'situ'];
}

$categoryName = trim((string) ($product['category_name'] ?? ''));
$categorySlug = trim((string) ($product['category_slug'] ?? ''));
$categoryBreadcrumbs = is_array($categoryBreadcrumbs ?? null) ? $categoryBreadcrumbs : [];
$categoryTrail = [];
if ($categoryBreadcrumbs !== []) {
    foreach ($categoryBreadcrumbs as $crumb) {
        $crumbName = trim((string) ($crumb['name'] ?? ''));
        $crumbSlug = trim((string) ($crumb['slug'] ?? ''));
        if ($crumbName !== '' && $crumbSlug !== '') {
            $categoryTrail[] = [
                'name' => $crumbName,
                'slug' => $crumbSlug,
                'url' => route('products.index', ['category' => $crumbSlug]),
            ];
        }
    }
} elseif ($categoryName !== '' && $categorySlug !== '') {
    $categoryTrail[] = [
        'name' => $categoryName,
        'slug' => $categorySlug,
        'url' => route('products.index', ['category' => $categorySlug]),
    ];
}
$leafCrumb = $categoryTrail !== [] ? $categoryTrail[count($categoryTrail) - 1] : null;
$leafCategoryName = (string) ($leafCrumb['name'] ?? $categoryName);
$leafCategoryUrl = (string) ($leafCrumb['url'] ?? ($categorySlug !== ''
    ? route('products.index', ['category' => $categorySlug])
    : route('products.index')));

$dimensions = is_array($product['dimensions'] ?? null) ? $product['dimensions'] : null;
$dims = [
    'w' => isset($dimensions['width_mm']) ? (float) $dimensions['width_mm'] : 0.0,
    'h' => isset($dimensions['height_mm']) ? (float) $dimensions['height_mm'] : 0.0,
    'd' => isset($dimensions['depth_mm']) ? (float) $dimensions['depth_mm'] : 0.0,
];
$hasDims = $dims['w'] > 0 && $dims['h'] > 0;
$fmt = static fn (float $v): string => rtrim(rtrim(number_format($v, 0, '.', ''), '0'), '.');

$card = is_array($card ?? null) ? $card : \Modules\Products\Services\ProductCardPresenter::make($product, $locale ?? null);
$dimensionSummary = trim((string) ($card['dimension'] ?? ''));
if ($dimensionSummary === '' && $hasDims) {
    $dimensionSummary = $fmt($dims['w']) . ' × ' . $fmt($dims['h'])
        . ($dims['d'] > 0 ? ' × ' . $fmt($dims['d']) : '') . ' mm';
}

/* Share minimal context with the floating AI Bathroom Planner chat */
if (feature('planner', true) && (bool) config('planner.enabled', true)) {
    $plannerText = trim((string) preg_replace(
        '/\s+/u',
        ' ',
        strip_tags((string) ($product['short_description'] ?? $product['description'] ?? '')),
    ));
    if ($dimensionSummary !== '') {
        $plannerText .= ($plannerText !== '' ? ' · ' : '') . $dimensionSummary;
    }
    if ($code !== '') {
        $plannerText .= ($plannerText !== '' ? ' · ' : '') . 'Code ' . $code;
    }
    $view->share(['plannerContext' => [
        'id' => $productId,
        'name' => $name,
        'text' => mb_substr($plannerText, 0, 600),
        'category' => $categorySlug,
        'url' => $productUrl,
    ]]);
}

$description = trim((string) ($product['description'] ?? $product['short_description'] ?? ''));
$cleanDescription = trim((string) preg_replace(
    '/\s*(?:Verified drawing dimensions|Doğrulanmış çizim ölçüleri|Ověřené rozměry z výkresu)\s*:\s*[^.]+\.?/iu',
    '',
    $description,
));
if ($cleanDescription === '') {
    $cleanDescription = $description;
}

$presentedFeatures = is_array($product['presented_features'] ?? null)
    ? array_slice($product['presented_features'], 0, 6)
    : array_slice((array) ($card['specs'] ?? []), 0, 6);
$cardFeatureBadges = array_slice((array) ($card['features'] ?? []), 0, 6);

$specs = array_values(array_filter(
    (array) ($product['specs'] ?? []),
    static fn ($row): bool => is_array($row)
        && trim((string) ($row['spec_key'] ?? '')) !== ''
        && trim((string) ($row['spec_value'] ?? '')) !== '',
));

$price = $product['price'] ?? null;
$hasPrice = $price !== null && (float) $price > 0;
$priceFormatted = $hasPrice
    ? (string) ($card['price_label'] ?? ('€' . number_format((float) $price, 2)))
    : trans('products.pdp_price_on_request');

$quoteParams = array_filter([
    'product' => $productSlug !== '' ? $productSlug : null,
    'sku' => $code !== '' ? $code : null,
]);
$quoteHref = route('contact') . ($quoteParams !== [] ? '?' . http_build_query($quoteParams) : '');

$isFeatured = !empty($product['is_featured']);
$isSaved = isset($saved)
    ? (bool) $saved
    : (feature('favorites', true)
        && class_exists(\Modules\Favorites\Services\FavoriteService::class)
        && app(\Modules\Favorites\Services\FavoriteService::class)->has($productId));
$inBox = isset($inBox)
    ? (bool) $inBox
    : (feature('box', true)
        && class_exists(\Modules\Box\Services\BoxService::class)
        && app(\Modules\Box\Services\BoxService::class)->has($productId));
$boxIds = is_array($boxIds ?? null) ? $boxIds : [];

/* Color family & current piece finishes */
$colorVariants = is_array($product['color_variants'] ?? null) ? array_values($product['color_variants']) : [];
$hasMultipleVariants = count($colorVariants) > 1;
$currentFinishes = is_array($card['finishes'] ?? null) ? $card['finishes'] : [];
$currentFinishLabels = [];
foreach ($currentFinishes as $cf) {
    $lbl = trim((string) ($cf['label'] ?? ''));
    if ($lbl !== '' && !in_array($lbl, $currentFinishLabels, true)) {
        $currentFinishLabels[] = $lbl;
    }
}
$currentFinishText = implode(' / ', $currentFinishLabels);
$renderToneDots = static function (array $finishes): string {
    $html = '';
    foreach (array_slice($finishes, 0, 4) as $f) {
        $k = preg_replace('/[^a-z-]/', '', strtolower((string) ($f['key'] ?? 'chrome'))) ?: 'chrome';
        $html .= '<i class="pcard-tone pcard-swatch--' . e($k) . '" aria-hidden="true"></i>';
    }
    return $html;
};

$seatCovers = array_values(array_filter(
    (array) ($product['seat_covers'] ?? []),
    static fn ($seat): bool => is_array($seat) && (int) ($seat['id'] ?? 0) > 0,
));
$primaryDrawingUrl = $drawings !== [] ? asset((string) $drawings[0]) : null;
$datasheetUrl = isset($datasheetUrl) && is_string($datasheetUrl) && trim($datasheetUrl) !== ''
    ? trim($datasheetUrl)
    : null;
$categoryNeighbors = is_array($categoryNeighbors ?? null) ? $categoryNeighbors : null;
$similarProducts = is_array($similarProducts ?? null) ? $similarProducts : [];
$complementaryProducts = is_array($complementaryProducts ?? null) ? $complementaryProducts : [];
?>
<main class="pdp" id="main" data-pdp-root data-pdp-product-id="<?= $productId ?>">
    <div class="pdp-inner">
        <!-- Top Editorial Breadcrumb & Category Lookbook Stepper -->
        <div class="pdp-topbar">
            <nav class="pdp-crumbs" aria-label="<?= e(trans('products.breadcrumb_label')) ?>">
                <a class="pdp-crumb-home" href="<?= e(route('home')) ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span><?= e(trans('products.catalog_home')) ?></span>
                </a>
                <span class="pdp-crumb-sep" aria-hidden="true">›</span>
                <a href="<?= e(route('products.index')) ?>"><?= e(trans('products.title')) ?></a>
                <?php foreach ($categoryTrail as $crumb): ?>
                    <span class="pdp-crumb-sep" aria-hidden="true">›</span>
                    <a href="<?= e($crumb['url']) ?>"><?= e($crumb['name']) ?></a>
                <?php endforeach; ?>
                <span class="pdp-crumb-sep" aria-hidden="true">›</span>
                <span class="is-current"><?= e($name) ?></span>
            </nav>

            <div class="pdp-topbar-right">
                <?php if ($categoryNeighbors !== null && (int) ($categoryNeighbors['total'] ?? 0) > 0): ?>
                    <div class="pdp-cat-stepper">
                        <?php if (!empty($categoryNeighbors['prev_slug'])): ?>
                            <a class="pdp-step-btn"
                               data-pdp-step="prev"
                               href="<?= e(route('products.show', ['slug' => (string) $categoryNeighbors['prev_slug']])) ?>"
                               aria-label="<?= e(trans('products.pdp_prev_product')) ?>"
                               title="<?= e(trans('products.pdp_prev_product')) ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($categoryNeighbors['next_slug'])): ?>
                            <a class="pdp-step-btn"
                               data-pdp-step="next"
                               href="<?= e(route('products.show', ['slug' => (string) $categoryNeighbors['next_slug']])) ?>"
                               aria-label="<?= e(trans('products.pdp_next_product')) ?>"
                               title="<?= e(trans('products.pdp_next_product')) ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </a>
                        <?php endif; ?>
                        <span class="pdp-step-count"><?= (int) $categoryNeighbors['position'] ?> / <?= (int) $categoryNeighbors['total'] ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($leafCategoryName !== ''): ?>
                    <a class="pdp-category-pill" href="<?= e($leafCategoryUrl) ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                        </svg>
                        <span><?= e(trans('products.pdp_view_all_category', ['category' => $leafCategoryName])) ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Two-Column Architectural Showroom Hero -->
        <div class="pdp-grid">
            <!-- Left Column: Vertical Thumbnail Rail + Warm Architectural Gallery Stage -->
            <section class="pdp-stage<?= $isFeatured ? ' is-featured' : '' ?><?= count($slides) > 1 ? ' has-rail' : '' ?>"
                     data-pdp-stage
                     aria-label="<?= e($name) ?>">

                <?php if (count($slides) > 1): ?>
                    <div class="pdp-thumbs" role="list">
                        <?php foreach ($slides as $idx => $slide): ?>
                            <button type="button" role="listitem"
                                    class="pdp-thumb<?= $idx === 0 ? ' is-active' : '' ?><?= ($slide['group'] ?? '') === 'drawing' ? ' is-drawing' : '' ?>"
                                    data-pdp-thumb="<?= (int) $idx ?>"
                                    data-thumb-group="<?= e((string) $slide['group']) ?>"
                                    aria-label="<?= e(trans('products.pdp_image_n', ['n' => $idx + 1])) ?>"
                                    aria-current="<?= $idx === 0 ? 'true' : 'false' ?>">
                                <img src="<?= e((string) $slide['url']) ?>" alt="" loading="lazy" decoding="async"
                                     onerror="this.onerror=null; this.src='<?= e(asset($fallbackImg)) ?>';">
                                <?php if (($slide['group'] ?? '') === 'drawing'): ?>
                                    <span class="pdp-thumb-badge">CAD</span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="pdp-stage-main">
                    <div class="pdp-stage-card">
                        <div class="pdp-viewport" data-pdp-viewport data-active-group="<?= e((string) ($slides[0]['group'] ?? 'gallery')) ?>">
                            <?php if ($slides !== []): ?>
                                <?php foreach ($slides as $idx => $slide): ?>
                                    <?php $sGroup = (string) ($slide['group'] ?? 'gallery'); ?>
                                    <img src="<?= e((string) $slide['url']) ?>"
                                         alt="<?= e((string) $slide['alt']) ?>"
                                         class="pdp-slide is-group-<?= e($sGroup) ?><?= $idx === 0 ? ' is-active' : '' ?>"
                                         data-pdp-slide="<?= (int) $idx ?>"
                                         data-slide-group="<?= e($sGroup) ?>"
                                         <?= $idx === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                                         decoding="async"
                                         aria-hidden="<?= $idx === 0 ? 'false' : 'true' ?>"
                                         onerror="this.onerror=null; this.src='<?= e(asset($fallbackImg)) ?>';">
                                <?php endforeach; ?>

                                <?php if (count($slides) > 1): ?>
                                    <button type="button" class="pdp-nav pdp-nav--prev" data-pdp-prev
                                            aria-label="<?= e(trans('products.view_prev')) ?>">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                    </button>
                                    <button type="button" class="pdp-nav pdp-nav--next" data-pdp-next
                                            aria-label="<?= e(trans('products.view_next')) ?>">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="pdp-empty-media" aria-hidden="true">
                                    <span>LUFLY</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php
                        $groupsPresent = array_values(array_filter([
                            count($gallery) > 0 ? ['key' => 'gallery', 'label' => trans('products.view_main'), 'count' => count($gallery)] : null,
                            count($drawings) > 0 ? ['key' => 'drawing', 'label' => trans('products.view_drawing'), 'count' => count($drawings)] : null,
                            count($situImages) > 0 ? ['key' => 'situ', 'label' => trans('products.view_situ'), 'count' => count($situImages)] : null,
                        ]));
                        ?>
                        <div class="pdp-stage-meta">
                            <?php if (count($groupsPresent) > 1): ?>
                                <div class="pdp-views" role="tablist" data-pdp-views aria-label="<?= e(trans('products.pdp_media_views')) ?>">
                                    <span class="pdp-views-glider" data-pdp-views-glider aria-hidden="true"></span>
                                    <?php foreach ($groupsPresent as $gi => $group): ?>
                                        <button type="button" role="tab" class="pdp-view-tab<?= $gi === 0 ? ' is-active' : '' ?>"
                                                data-pdp-group="<?= e($group['key']) ?>"
                                                aria-selected="<?= $gi === 0 ? 'true' : 'false' ?>">
                                            <span><?= e($group['label']) ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="pdp-stage-view-label"><?= e(trans('products.view_main')) ?></span>
                            <?php endif; ?>

                            <div class="pdp-stage-meta-right">
                                <?php if (count($slides) > 1): ?>
                                    <span class="pdp-slide-counter" data-pdp-counter>1 / <?= count($slides) ?></span>
                                <?php endif; ?>
                                <?php if ($slides !== []): ?>
                                    <button type="button" class="pdp-zoom-btn" data-pdp-zoom
                                            aria-label="<?= e(trans('products.zoom_image', ['image' => $name])) ?>"
                                            title="<?= e(trans('products.pdp_fullscreen')) ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
                                        </svg>
                                        <span><?= e(trans('products.pdp_fullscreen')) ?></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Right Column: Editorial Showroom Info & Action Panel -->
            <aside class="pdp-info">
                <div class="pdp-header-top">
                    <div class="pdp-eyebrow-wrap">
                        <?php if ($leafCategoryName !== ''): ?>
                            <a class="pdp-eyebrow" href="<?= e($leafCategoryUrl) ?>">
                                <span><?= e($leafCategoryName) ?></span>
                                <i aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ($isFeatured): ?>
                            <span class="pdp-featured-badge pdp-code-chip is-gold">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 2.5 14.9 8.4l6.5.9-4.7 4.6 1.1 6.5L12 17.3 6.2 20.4l1.1-6.5-4.7-4.6 6.5-.9L12 2.5z"/>
                                </svg>
                                <span><?= e(trans('products.pdp_featured')) ?></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="pdp-fav-corner">
                        <?= $view->component('favorite-button', [
                            'product_id' => $productId,
                            'name' => $name,
                            'variant' => 'card',
                            'on' => $isSaved,
                        ]) ?>
                    </div>
                </div>

                <h1 class="pdp-title"><?= e($name) ?></h1>

                <div class="pdp-code-row">
                    <?php if ($code !== ''): ?>
                        <button type="button" class="pdp-sku-chip"
                                data-pdp-copy="<?= e($code) ?>"
                                data-copied-label="<?= e(trans('products.pdp_copied')) ?>"
                                title="<?= e(trans('products.pdp_copy_code')) ?>">
                            <span class="pdp-sku-label"><?= e(trans('products.code_label')) ?></span>
                            <strong class="pdp-sku-val"><?= e($code) ?></strong>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                            </svg>
                            <em class="pdp-sku-feedback" data-copy-badge hidden><?= e(trans('products.pdp_copied')) ?></em>
                        </button>
                    <?php endif; ?>

                    <?php if ($hasMultipleVariants && $currentFinishes !== []): ?>
                        <a class="pdp-desktop-finish-jump" href="#pdp-variants-band">
                            <span class="pcard-tone-swatch" aria-hidden="true"><?= $renderToneDots($currentFinishes) ?></span>
                            <strong><?= e($currentFinishText) ?></strong>
                            <span class="pdp-finish-jump-count">· <?= e(trans('products.pdp_finishes_count', ['n' => count($colorVariants)])) ?> ↓</span>
                        </a>
                    <?php endif; ?>

                    <span class="pdp-stock-pill">
                        <i aria-hidden="true"></i>
                        <span><?= e(trans('products.pdp_in_stock')) ?></span>
                    </span>
                </div>

                <?php if ($cleanDescription !== ''): ?>
                    <p class="pdp-lead"><?= nl2br(e($cleanDescription)) ?></p>
                <?php endif; ?>

                <!-- Mobile (< 980px): Compact thumb-friendly color circles;
                     Laptop (>= 980px): Hidden when full variant cards band renders below -->
                <div class="pdp-colors-mobile<?= $hasMultipleVariants ? ' has-variant-cards' : '' ?>">
                    <?= $view->component('product-color-variants', [
                        'item' => $product,
                        'card' => $card,
                    ]) ?>
                </div>

                <!-- 4–6 Architectural Key Spec Tiles -->
                <?php if ($presentedFeatures !== [] || $cardFeatureBadges !== []): ?>
                    <?php if ($presentedFeatures !== []): ?>
                        <ul class="pdp-highlights" aria-label="<?= e(trans('products.pdp_highlights_title')) ?>">
                            <?php foreach (array_slice($presentedFeatures, 0, 4) as $pf): ?>
                                <li class="pdp-highlight">
                                    <span class="pdp-highlight-icon" aria-hidden="true">
                                        <?= $view->component('product-card-icon', ['name' => (string) ($pf['icon'] ?? 'spark')]) ?>
                                    </span>
                                    <div class="pdp-highlight-copy">
                                        <small><?= e((string) ($pf['label'] ?? '')) ?></small>
                                        <strong><?= e((string) ($pf['value'] ?? '')) ?></strong>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <ul class="pdp-highlights" aria-label="<?= e(trans('products.pdp_highlights_title')) ?>">
                            <?php foreach (array_slice($cardFeatureBadges, 0, 4) as $feat): ?>
                                <li class="pdp-highlight">
                                    <span class="pdp-highlight-icon" aria-hidden="true">
                                        <?= $view->component('product-card-icon', ['name' => (string) ($feat['icon'] ?? 'spark')]) ?>
                                    </span>
                                    <div class="pdp-highlight-copy">
                                        <strong><?= e((string) ($feat['label'] ?? '')) ?></strong>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Signature Teal Action Row + Disabled Cart Square -->
                <div class="pdp-cta-block">
                    <?php if ($hasPrice): ?>
                        <div class="pdp-price-inline">
                            <span><?= e(trans('products.price_label')) ?></span>
                            <strong><?= e($priceFormatted) ?></strong>
                        </div>
                    <?php endif; ?>

                    <div class="pdp-cta-row">
                        <a class="pdp-cta-primary" href="<?= e($quoteHref) ?>">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2" y="4" width="20" height="16" rx="2.5"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                            </svg>
                            <span><?= e(trans('products.request_quote')) ?></span>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>
                            </svg>
                        </a>

                        <div class="pdp-cta-secondary">
                            <?= $view->component('box-button', [
                                'product_id' => $productId,
                                'name' => $name,
                                'variant' => 'pill',
                                'on' => $inBox,
                            ]) ?>

                            <?= $view->component('box-button', [
                                'product_id' => $productId,
                                'name' => $name,
                                'variant' => 'cart',
                                'on' => $inBox,
                                'label' => trans('products.add_to_cart'),
                                'disabled' => empty($card['cart_enabled']),
                                'disabled_label' => (string) ($card['cart_label'] ?? trans('products.cart_pending')),
                            ]) ?>
                        </div>
                    </div>

                    <!-- Technical Resource Bar: Disabled Data Sheet Button + CAD Drawing + Full Specs -->
                    <div class="pdp-resource-strip">
                        <?php if ($datasheetUrl !== null): ?>
                            <a class="pdp-datasheet-btn is-ready"
                               href="<?= e(asset($datasheetUrl)) ?>"
                               target="_blank"
                               rel="noopener"
                               download
                               title="<?= e(trans('products.pdp_datasheet_download')) ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/>
                                </svg>
                                <span><?= e(trans('products.pdp_datasheet_btn')) ?></span>
                                <small>(PDF)</small>
                            </a>
                        <?php else: ?>
                            <button type="button"
                                    class="pdp-datasheet-btn"
                                    disabled
                                    aria-disabled="true"
                                    title="<?= e(trans('products.pdp_datasheet_pending')) ?>"
                                    aria-label="<?= e(trans('products.pdp_datasheet_btn') . ' — ' . trans('products.pdp_datasheet_pending')) ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>
                                </svg>
                                <span><?= e(trans('products.pdp_datasheet_btn')) ?></span>
                                <em class="pdp-datasheet-tag"><?= e(trans('products.pdp_datasheet_soon')) ?></em>
                            </button>
                        <?php endif; ?>

                        <?php if ($primaryDrawingUrl !== null): ?>
                            <button type="button" class="pdp-res-link" data-pdp-goto-group="drawing">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                    <path d="M3.27 6.96 12 12.01l8.73-5.05"/><path d="M12 22.08V12"/>
                                </svg>
                                <span><?= e(trans('products.pdp_view_cad')) ?></span>
                            </button>
                        <?php endif; ?>

                        <a class="pdp-res-link" href="#pdp-dossier">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h8"/>
                            </svg>
                            <span><?= e(trans('products.pdp_jump_specs')) ?></span>
                        </a>
                    </div>
                </div>

                <!-- Matched WC Seat Covers Configurator (#wc-seat-options) -->
                <?php if ($seatCovers !== []): ?>
                    <?php
                    $seatHeading = count($seatCovers) > 1
                        ? trans('products.wc_seat_title_many')
                        : trans('products.wc_seat_title_one');
                    ?>
                    <section class="pdp-seat-covers" id="wc-seat-options" data-wc-seat-options
                             aria-label="<?= e($seatHeading) ?>">
                        <div class="pdp-seat-head">
                            <div>
                                <span class="pdp-seat-kicker"><?= e(trans('products.wc_seat_kicker')) ?></span>
                                <h2 class="pdp-specs-title"><?= e($seatHeading) ?></h2>
                                <p class="pdp-seat-sub"><?= e(trans('products.wc_seat_subtitle')) ?></p>
                            </div>
                        </div>
                        <div class="pdp-seat-list">
                            <?php foreach ($seatCovers as $seat): ?>
                                <?php
                                $seatId = (int) ($seat['id'] ?? 0);
                                $seatName = trim((string) ($seat['name'] ?? ''));
                                if ($seatName === '') {
                                    $seatName = trans('products.name_pending');
                                }
                                $seatCode = trim((string) ($seat['model_code'] ?? $seat['sku'] ?? ''));
                                $seatImg = trim((string) ($seat['image'] ?? $fallbackImg));
                                $seatDrawings = is_array($seat['drawings'] ?? null) ? array_values($seat['drawings']) : [];
                                $seatDrawingUrl = $seatDrawings !== [] ? asset((string) $seatDrawings[0]) : '';
                                $seatFeatures = is_array($seat['presented_features'] ?? null)
                                    ? array_slice($seat['presented_features'], 0, 4)
                                    : [];
                                $seatInBox = isset($boxIds[$seatId])
                                    || (feature('box', true)
                                        && class_exists(\Modules\Box\Services\BoxService::class)
                                        && app(\Modules\Box\Services\BoxService::class)->has($seatId));
                                $bundleBothInBox = $inBox && $seatInBox;
                                ?>
                                <article class="pdp-seat-card" data-seat-cover-id="<?= $seatId ?>">
                                    <div class="pdp-seat-media">
                                        <img src="<?= e(asset($seatImg)) ?>" alt="<?= e($seatName) ?>" loading="lazy" decoding="async"
                                             onerror="this.onerror=null; this.src='<?= e(asset($fallbackImg)) ?>';">
                                        <?php if ($seatDrawingUrl !== ''): ?>
                                            <button type="button" class="pdp-seat-drawing-btn"
                                                    data-pdp-external-zoom="<?= e($seatDrawingUrl) ?>"
                                                    data-pdp-external-alt="<?= e($seatName . ' — ' . trans('products.wc_seat_drawing')) ?>"
                                                    aria-label="<?= e(trans('products.wc_seat_drawing')) ?>">
                                                <span><?= e(trans('products.wc_seat_drawing')) ?></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pdp-seat-body">
                                        <div class="pdp-seat-meta">
                                            <?php if ($seatCode !== ''): ?>
                                                <span class="pdp-seat-code"><?= e(trans('products.code_label') . ' · ' . $seatCode) ?></span>
                                            <?php endif; ?>
                                            <h3 class="pdp-seat-name"><?= e($seatName) ?></h3>
                                        </div>
                                        <?php if ($seatFeatures !== []): ?>
                                            <ul class="pdp-seat-feats">
                                                <?php foreach ($seatFeatures as $sFeat): ?>
                                                    <li>
                                                        <?= $view->component('product-card-icon', ['name' => (string) ($sFeat['icon'] ?? 'spark')]) ?>
                                                        <span><?= e((string) ($sFeat['value'] ?? $sFeat['label'] ?? '')) ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                        <?php if (feature('box', true)): ?>
                                            <div class="pdp-seat-actions">
                                                <?= $view->component('box-button', [
                                                    'product_id' => $seatId,
                                                    'name' => $seatName,
                                                    'variant' => 'pill',
                                                    'on' => $seatInBox,
                                                    'label' => trans('products.wc_seat_add'),
                                                ]) ?>
                                                <button type="button" class="pdp-seat-bundle-btn<?= $bundleBothInBox ? ' is-in-box' : '' ?>"
                                                        data-box-bundle-add="<?= e($productId . ',' . $seatId) ?>"
                                                        data-bundle-added-label="<?= e(trans('products.wc_seat_bundle_added')) ?>">
                                                    <span><?= e($bundleBothInBox ? trans('products.wc_seat_bundle_added') : trans('products.wc_seat_bundle_add')) ?></span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </aside>
        </div>

        <!-- Laptop / Desktop (>= 980px): Full-Width Visual "Other Available Variants" Cards Band -->
        <?php if ($hasMultipleVariants): ?>
            <section class="pdp-variants-band" id="pdp-variants-band" data-pdp-variant-cards
                     aria-labelledby="pdp-variants-heading">
                <header class="pdp-editorial-head">
                    <span class="pdp-editorial-rule" aria-hidden="true"></span>
                    <h2 class="pdp-editorial-title" id="pdp-variants-heading"><?= e(trans('products.pdp_other_variants')) ?></h2>
                </header>

                <div class="pdp-vcards-grid">
                    <?php foreach ($colorVariants as $variant): ?>
                        <?php
                        $vId = (int) ($variant['id'] ?? 0);
                        $isCurrentVar = $vId === $productId;
                        $vCode = trim((string) ($variant['model_code'] ?? $variant['sku'] ?? ''));
                        $vFinish = is_array($variant['finish'] ?? null) ? $variant['finish'] : [];
                        $vOptionFinishes = !empty($variant['card']['finishes']) && is_array($variant['card']['finishes'])
                            ? $variant['card']['finishes']
                            : ($vFinish !== [] ? [$vFinish] : []);
                        $vLabels = [];
                        foreach ($vOptionFinishes as $vf) {
                            $vl = trim((string) ($vf['label'] ?? ''));
                            if ($vl !== '' && !in_array($vl, $vLabels, true)) {
                                $vLabels[] = $vl;
                            }
                        }
                        $vFinishTitle = $vLabels !== []
                            ? implode(' / ', $vLabels)
                            : trim((string) ($vFinish['label'] ?? $variant['display_name'] ?? ''));
                        $vImg = trim((string) ($variant['image'] ?? '')) !== '' ? trim((string) $variant['image']) : $fallbackImg;
                        $vUrl = (string) ($variant['url'] ?? route('products.show', ['slug' => (string) ($variant['slug'] ?? '')]));
                        ?>
                        <article class="pdp-vcard<?= $isCurrentVar ? ' is-current' : '' ?>"
                                 data-pdp-vcard="<?= $vId ?>"
                                 data-pdp-vcard-url="<?= e($vUrl) ?>"
                                 data-pdp-vcard-img="<?= e(asset($vImg)) ?>"
                                 data-pdp-vcard-alt="<?= e($vFinishTitle . ($vCode !== '' ? ' — ' . $vCode : '')) ?>">
                            <a class="pdp-vcard-media" href="<?= e($vUrl) ?>"<?= $isCurrentVar ? ' aria-current="page"' : '' ?>>
                                <img src="<?= e(asset($vImg)) ?>"
                                     alt="<?= e($vFinishTitle . ($vCode !== '' ? ' — ' . $vCode : '')) ?>"
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.onerror=null; this.src='<?= e(asset($fallbackImg)) ?>';">
                            </a>

                            <div class="pdp-vcard-body">
                                <?php if ($vCode !== ''): ?>
                                    <span class="pdp-vcard-code"><?= e($vCode) ?></span>
                                <?php endif; ?>
                                <div class="pdp-vcard-title-row">
                                    <?php if ($vOptionFinishes !== []): ?>
                                        <span class="pcard-tone-swatch" aria-hidden="true"><?= $renderToneDots($vOptionFinishes) ?></span>
                                    <?php endif; ?>
                                    <strong class="pdp-vcard-title"><?= e($vFinishTitle) ?></strong>
                                </div>

                                <?php if ($isCurrentVar): ?>
                                    <span class="pdp-vcard-btn is-current"><?= e(trans('products.pdp_current_product')) ?></span>
                                <?php else: ?>
                                    <a class="pdp-vcard-btn" href="<?= e($vUrl) ?>">
                                        <span><?= e(trans('products.pdp_view_variant')) ?></span>
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php if ($leafCategoryName !== ''): ?>
                        <aside class="pdp-vcard pdp-vcard--explore">
                            <div class="pdp-vcard-explore-inner">
                                <span class="pdp-vcard-rings" aria-hidden="true">
                                    <svg width="36" height="36" viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <circle cx="14.5" cy="14.5" r="8.5"/>
                                        <circle cx="21.5" cy="21.5" r="8.5"/>
                                    </svg>
                                </span>
                                <strong class="pdp-vcard-title"><?= e(trans('products.pdp_compare_finishes')) ?></strong>
                                <p class="pdp-vcard-sub"><?= e(trans('products.pdp_compare_sub', ['category' => $leafCategoryName])) ?></p>
                            </div>
                            <a class="pdp-vcard-btn" href="<?= e($leafCategoryUrl) ?>">
                                <span><?= e(trans('products.pdp_view_all')) ?></span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>
                                </svg>
                            </a>
                        </aside>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Tabbed Architectural Engineering, Specifications & Downloads Panel -->
        <?php if ($cleanDescription !== '' || $specs !== [] || $primaryDrawingUrl !== null): ?>
            <section class="pdp-dossier" id="pdp-dossier" data-pdp-dossier aria-label="<?= e(trans('products.pdp_dossier_title')) ?>">
                <div class="pdp-dossier-tabs" role="tablist" data-dossier-tabs>
                    <span class="pdp-dossier-glider" data-dossier-glider aria-hidden="true"></span>
                    <button type="button" role="tab" class="pdp-dtab is-active" data-dossier-tab="overview" aria-selected="true">
                        <?= e(trans('products.pdp_tab_overview')) ?>
                    </button>
                    <?php if ($primaryDrawingUrl !== null): ?>
                        <button type="button" role="tab" class="pdp-dtab" data-dossier-tab="drawing" aria-selected="false">
                            <?= e(trans('products.pdp_tab_drawing')) ?>
                        </button>
                    <?php endif; ?>
                    <?php if ($specs !== [] || $hasDims): ?>
                        <button type="button" role="tab" class="pdp-dtab" data-dossier-tab="specs" aria-selected="false">
                            <?= e(trans('products.pdp_tab_specs')) ?>
                        </button>
                    <?php endif; ?>
                    <button type="button" role="tab" class="pdp-dtab" data-dossier-tab="guarantee" aria-selected="false">
                        <?= e(trans('products.pdp_tab_guarantee')) ?>
                    </button>
                </div>

                <div class="pdp-dossier-body" data-dossier-body>
                <!-- Tab 1 (Default): 3-Column Architectural Overview (Drawing + Key Specs + Downloads) -->
                <div class="pdp-dpanel is-active" data-dossier-panel="overview">
                    <div class="pdp-overview-3col<?= $primaryDrawingUrl === null ? ' no-drawing' : '' ?>">
                        <?php if ($primaryDrawingUrl !== null): ?>
                            <div class="pdp-blueprint-sheet"
                                 data-pdp-external-zoom="<?= e($primaryDrawingUrl) ?>"
                                 data-pdp-external-alt="<?= e($name . ' — ' . trans('products.pdp_view_cad')) ?>">
                                <img src="<?= e($primaryDrawingUrl) ?>"
                                     alt="<?= e($name . ' — ' . trans('products.pdp_view_cad')) ?>"
                                     loading="lazy"
                                     decoding="async">
                                <span class="pdp-blueprint-zoom-hint">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M11 8v6M8 11h6"/>
                                    </svg>
                                    <span><?= e(trans('products.pdp_cad_open')) ?></span>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="pdp-key-specs">
                            <h3 class="pdp-panel-heading"><?= e(trans('products.pdp_key_specs')) ?></h3>
                            <dl class="pdp-spec-grid">
                                <?php if ($code !== ''): ?>
                                    <div class="pdp-spec-row">
                                        <dt><?= e(trans('products.code_label')) ?></dt>
                                        <dd><?= e($code) ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if ($hasDims): ?>
                                    <div class="pdp-spec-row">
                                        <dt><?= e(trans('products.dimensions')) ?></dt>
                                        <dd><?= e($fmt($dims['w'])) ?> &times; <?= e($fmt($dims['h'])) ?><?= $dims['d'] > 0 ? ' &times; ' . e($fmt($dims['d'])) : '' ?> mm</dd>
                                    </div>
                                <?php endif; ?>
                                <?php foreach ($specs as $spec): ?>
                                    <?php $unit = trim((string) ($spec['unit'] ?? '')); ?>
                                    <div class="pdp-spec-row">
                                        <dt><?= e((string) $spec['spec_key']) ?></dt>
                                        <dd><?= e((string) $spec['spec_value'] . ($unit !== '' ? ' ' . $unit : '')) ?></dd>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($categoryTrail !== []): ?>
                                    <div class="pdp-spec-row">
                                        <dt><?= e(trans('products.category_label')) ?></dt>
                                        <dd><a class="pdp-spec-link" href="<?= e($leafCategoryUrl) ?>"><?= e(implode(' › ', array_column($categoryTrail, 'name'))) ?></a></dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        </div>

                        <div class="pdp-downloads-col">
                            <h3 class="pdp-panel-heading"><?= e(trans('products.pdp_downloads_short')) ?></h3>
                            <div class="pdp-file-list">
                                <?php if ($datasheetUrl !== null): ?>
                                    <a class="pdp-file-row is-ready" href="<?= e(asset($datasheetUrl)) ?>" target="_blank" rel="noopener" download>
                                        <span class="pdp-file-left">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                                            </svg>
                                            <strong><?= e(trans('products.pdp_datasheet_btn')) ?></strong>
                                            <span class="pdp-file-ext">PDF</span>
                                        </span>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <button type="button"
                                            class="pdp-datasheet-btn pdp-file-row"
                                            disabled
                                            aria-disabled="true"
                                            title="<?= e(trans('products.pdp_datasheet_pending')) ?>">
                                        <span class="pdp-file-left">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                                            </svg>
                                            <strong><?= e(trans('products.pdp_datasheet_btn')) ?></strong>
                                            <span class="pdp-file-ext">PDF</span>
                                        </span>
                                        <em class="pdp-datasheet-tag"><?= e(trans('products.pdp_datasheet_soon')) ?></em>
                                    </button>
                                <?php endif; ?>

                                <?php if ($primaryDrawingUrl !== null): ?>
                                    <button type="button"
                                            class="pdp-file-row is-ready"
                                            data-pdp-external-zoom="<?= e($primaryDrawingUrl) ?>"
                                            data-pdp-external-alt="<?= e($name . ' — ' . trans('products.pdp_view_cad')) ?>">
                                        <span class="pdp-file-left">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 7h18M3 17h18M7 3v18M17 3v18"/>
                                            </svg>
                                            <strong><?= e(trans('products.pdp_view_cad')) ?></strong>
                                            <span class="pdp-file-ext">CAD</span>
                                        </span>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                                        </svg>
                                    </button>
                                <?php endif; ?>

                                <a class="pdp-file-row is-ready" href="<?= e($quoteHref) ?>">
                                    <span class="pdp-file-left">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                        </svg>
                                        <strong><?= e(trans('products.pdp_bim_request')) ?></strong>
                                        <span class="pdp-file-ext"><?= e(trans('products.pdp_on_request_tag')) ?></span>
                                    </span>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Dedicated Large Technical Drawing Sheet -->
                <?php if ($primaryDrawingUrl !== null): ?>
                    <div class="pdp-dpanel" data-dossier-panel="drawing" hidden>
                        <div class="pdp-drawing-focus">
                            <?php foreach ($drawings as $dSrc): ?>
                                <?php $dUrl = asset((string) $dSrc); ?>
                                <figure class="pdp-drawing-sheet-lg"
                                        data-pdp-external-zoom="<?= e($dUrl) ?>"
                                        data-pdp-external-alt="<?= e($name . ' — ' . trans('products.pdp_view_cad')) ?>">
                                    <img src="<?= e($dUrl) ?>" alt="<?= e($name . ' — ' . trans('products.pdp_view_cad')) ?>" loading="lazy" decoding="async">
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tab 3: Full Specifications -->
                <?php if ($specs !== [] || $hasDims): ?>
                    <div class="pdp-dpanel" data-dossier-panel="specs" hidden>
                        <div class="pdp-specs-only">
                            <dl class="pdp-spec-grid">
                                <?php if ($code !== ''): ?>
                                    <div class="pdp-spec-row">
                                        <dt><?= e(trans('products.code_label')) ?></dt>
                                        <dd><?= e($code) ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php foreach ($specs as $spec): ?>
                                    <?php $unit = trim((string) ($spec['unit'] ?? '')); ?>
                                    <div class="pdp-spec-row">
                                        <dt><?= e((string) $spec['spec_key']) ?></dt>
                                        <dd><?= e((string) $spec['spec_value'] . ($unit !== '' ? ' ' . $unit : '')) ?></dd>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($hasDims): ?>
                                    <div class="pdp-spec-row">
                                        <dt><?= e(trans('products.dimensions')) ?></dt>
                                        <dd><?= e($fmt($dims['w'])) ?> &times; <?= e($fmt($dims['h'])) ?><?= $dims['d'] > 0 ? ' &times; ' . e($fmt($dims['d'])) : '' ?> mm</dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tab 4: Materials, Guarantee & European Certifications -->
                <div class="pdp-dpanel" data-dossier-panel="guarantee" hidden>
                    <div class="pdp-trust-grid">
                        <div class="pdp-trust-item">
                            <span class="pdp-trust-icon" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>
                                </svg>
                            </span>
                            <div>
                                <strong><?= e(trans('products.pdp_trust_warranty_title')) ?></strong>
                                <span><?= e(trans('products.pdp_trust_warranty_sub')) ?></span>
                            </div>
                        </div>

                        <div class="pdp-trust-item">
                            <span class="pdp-trust-icon" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                    <path d="M3.27 6.96 12 12.01l8.73-5.05"/><path d="M12 22.08V12"/>
                                </svg>
                            </span>
                            <div>
                                <strong><?= e(trans('products.pdp_trust_supply_title')) ?></strong>
                                <span><?= e(trans('products.pdp_trust_supply_sub')) ?></span>
                            </div>
                        </div>

                        <div class="pdp-trust-item">
                            <span class="pdp-trust-icon" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 7h18M3 17h18M7 3v18M17 3v18"/>
                                </svg>
                            </span>
                            <div>
                                <strong><?= e(trans('products.pdp_trust_bim_title')) ?></strong>
                                <span><?= e(trans('products.pdp_trust_bim_sub')) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Marketplace Tier 1: Similar Products in This Category -->
        <?php if ($similarProducts !== []): ?>
            <section class="pdp-market-section" data-pdp-similar aria-labelledby="pdp-similar-heading">
                <header class="pdp-section-head">
                    <div>
                        <div class="pdp-editorial-head">
                            <span class="pdp-editorial-rule" aria-hidden="true"></span>
                            <span class="pdp-section-kicker"><?= e(trans('products.pdp_similar_kicker')) ?></span>
                        </div>
                        <h2 class="pdp-section-title" id="pdp-similar-heading">
                            <?= e($leafCategoryName !== ''
                                ? trans('products.pdp_similar_title', ['category' => $leafCategoryName])
                                : trans('products.pdp_similar_fallback_title')) ?>
                        </h2>
                    </div>

                    <?php if ($leafCategoryName !== ''): ?>
                        <a class="pdp-section-link" href="<?= e($leafCategoryUrl) ?>">
                            <span><?= e(trans('products.pdp_view_all_category', ['category' => $leafCategoryName])) ?></span>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </header>

                <div class="pdp-market-grid">
                    <?php foreach ($similarProducts as $simIdx => $simItem): ?>
                        <?= $view->component('product-card', [
                            'product' => $simItem['product'],
                            'slug' => $simItem['slug'],
                            'index' => $simIdx,
                            'saved' => $simItem['saved'],
                            'in_box' => $simItem['in_box'],
                        ]) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Marketplace Tier 2: Complete the Space (Coordinated & Complementary Pieces) -->
        <?php if ($complementaryProducts !== []): ?>
            <section class="pdp-market-section pdp-market-section--alt" data-pdp-complementary aria-labelledby="pdp-complete-heading">
                <header class="pdp-section-head">
                    <div>
                        <div class="pdp-editorial-head">
                            <span class="pdp-editorial-rule" aria-hidden="true"></span>
                            <span class="pdp-section-kicker"><?= e(trans('products.pdp_complete_kicker')) ?></span>
                        </div>
                        <h2 class="pdp-section-title" id="pdp-complete-heading"><?= e(trans('products.pdp_complete_title')) ?></h2>
                        <p class="pdp-section-sub"><?= e(trans('products.pdp_complete_sub')) ?></p>
                    </div>

                    <a class="pdp-section-link" href="<?= e(route('products.index')) ?>">
                        <span><?= e(trans('products.title')) ?></span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>
                        </svg>
                    </a>
                </header>

                <div class="pdp-market-grid">
                    <?php foreach ($complementaryProducts as $compIdx => $compItem): ?>
                        <?= $view->component('product-card', [
                            'product' => $compItem['product'],
                            'slug' => $compItem['slug'],
                            'index' => $compIdx,
                            'saved' => $compItem['saved'],
                            'in_box' => $compItem['in_box'],
                        ]) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <!-- Mobile Sticky Conversion Bar -->
    <div class="pdp-mobile-dock" aria-label="<?= e($name) ?>">
        <div class="pdp-mobile-meta">
            <?php if ($code !== ''): ?>
                <span class="pdp-mobile-code"><?= e($code) ?></span>
            <?php endif; ?>
            <strong class="pdp-mobile-price"><?= e($priceFormatted) ?></strong>
        </div>
        <div class="pdp-mobile-actions">
            <?= $view->component('box-button', [
                'product_id' => $productId,
                'name' => $name,
                'variant' => 'pill',
                'on' => $inBox,
            ]) ?>
            <a class="pdp-mobile-cta" href="<?= e($quoteHref) ?>">
                <span><?= e(trans('products.request_quote')) ?></span>
            </a>
        </div>
    </div>

    <div class="pdp-lightbox" data-pdp-lightbox hidden role="dialog" aria-modal="true"
         aria-label="<?= e($name) ?>">
        <button type="button" class="pdp-lightbox-close" data-pdp-lightbox-close
                aria-label="<?= e(trans('products.zoom_close')) ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
        <button type="button" class="pdp-lightbox-nav pdp-lightbox-nav--prev" data-pdp-lightbox-prev
                aria-label="<?= e(trans('products.view_prev')) ?>" <?= count($slides) > 1 ? '' : 'hidden' ?>>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <img class="pdp-lightbox-img" data-pdp-lightbox-img src="" alt="">
        <button type="button" class="pdp-lightbox-nav pdp-lightbox-nav--next" data-pdp-lightbox-next
                aria-label="<?= e(trans('products.view_next')) ?>" <?= count($slides) > 1 ? '' : 'hidden' ?>>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </button>
    </div>
</main>
