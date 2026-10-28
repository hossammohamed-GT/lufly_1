<?php
/**
 * Product detail page — Flagship Luxury Marketplace Showroom & Technical Dossier.
 *
 * @var Core\View\View                    $view
 * @var array<string, mixed>              $product
 * @var array<string, mixed>|null         $card
 * @var bool|null                         $saved
 * @var bool|null                         $inBox
 * @var array<int, int>|null              $savedIds
 * @var array<int, int>|null              $boxIds
 * @var string|null                       $datasheetUrl
 * @var array<int, array<string, mixed>>  $similarProducts
 * @var array<int, array<string, mixed>>  $complementaryProducts
 * @var array<int, array<string, string>> $categoryBreadcrumbs
 * @var string                            $locale
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/css/product-card.css');
$view->pushStyle('frontend/products/detail/detail.css');
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

$seatCovers = array_values(array_filter(
    (array) ($product['seat_covers'] ?? []),
    static fn ($seat): bool => is_array($seat) && (int) ($seat['id'] ?? 0) > 0,
));
$primaryDrawingUrl = $drawings !== [] ? asset((string) $drawings[0]) : null;
$firstDrawingIndex = count($gallery);
$datasheetUrl = isset($datasheetUrl) && is_string($datasheetUrl) && trim($datasheetUrl) !== ''
    ? trim($datasheetUrl)
    : null;
$similarProducts = is_array($similarProducts ?? null) ? $similarProducts : [];
$complementaryProducts = is_array($complementaryProducts ?? null) ? $complementaryProducts : [];
?>
<main class="pdp" id="main">
    <div class="pdp-inner">
        <div class="pdp-topbar">
            <nav class="pdp-crumbs" aria-label="<?= e(trans('products.breadcrumb_label')) ?>">
                <a href="<?= e(route('home')) ?>"><?= e(trans('products.catalog_home')) ?></a>
                <span aria-hidden="true">/</span>
                <a href="<?= e(route('products.index')) ?>"><?= e(trans('products.title')) ?></a>
                <?php foreach ($categoryTrail as $crumb): ?>
                    <span aria-hidden="true">/</span>
                    <a href="<?= e($crumb['url']) ?>"><?= e($crumb['name']) ?></a>
                <?php endforeach; ?>
                <span aria-hidden="true">/</span>
                <span class="is-current"><?= e($name) ?></span>
            </nav>

            <?php if ($leafCategoryName !== ''): ?>
                <a class="pdp-category-pill" href="<?= e($leafCategoryUrl) ?>">
                    <span><?= e(trans('products.pdp_view_all_category', ['category' => $leafCategoryName])) ?></span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>

        <div class="pdp-grid">
            <!-- Left Column: Sticky Multi-View Studio Stage -->
            <section class="pdp-stage<?= $isFeatured ? ' is-featured' : '' ?>" data-pdp-stage aria-label="<?= e($name) ?>">
                <div class="pdp-stage-head">
                    <?php
                    $groupsPresent = array_values(array_filter([
                        count($gallery) > 0 ? ['key' => 'gallery', 'label' => trans('products.view_main'), 'count' => count($gallery)] : null,
                        count($drawings) > 0 ? ['key' => 'drawing', 'label' => trans('products.view_drawing'), 'count' => count($drawings)] : null,
                        count($situImages) > 0 ? ['key' => 'situ', 'label' => trans('products.view_situ'), 'count' => count($situImages)] : null,
                    ]));
                    ?>
                    <?php if (count($groupsPresent) > 1): ?>
                        <div class="pdp-views" role="tablist" aria-label="<?= e(trans('products.pdp_media_views')) ?>">
                            <?php foreach ($groupsPresent as $gi => $group): ?>
                                <button type="button" role="tab" class="pdp-view-tab<?= $gi === 0 ? ' is-active' : '' ?>"
                                        data-pdp-group="<?= e($group['key']) ?>"
                                        aria-selected="<?= $gi === 0 ? 'true' : 'false' ?>">
                                    <?php if ($group['key'] === 'drawing'): ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 7h18M3 17h18M7 3v18M17 3v18"/>
                                        </svg>
                                    <?php elseif ($group['key'] === 'gallery'): ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="3" width="18" height="18" rx="2.5"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 21h18M5 21V7l7-4 7 4v14"/>
                                        </svg>
                                    <?php endif; ?>
                                    <span><?= e($group['label']) ?></span>
                                    <em><?= (int) $group['count'] ?></em>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span class="pdp-stage-tag"><?= e(trans('products.view_main')) ?></span>
                    <?php endif; ?>

                    <?php if (count($slides) > 0): ?>
                        <span class="pdp-slide-counter" data-pdp-counter aria-live="polite">1 / <?= count($slides) ?></span>
                    <?php endif; ?>
                </div>

                <div class="pdp-viewport" data-pdp-viewport data-active-group="<?= e((string) ($slides[0]['group'] ?? 'gallery')) ?>">
                    <?php if ($slides !== []): ?>
                        <?php foreach ($slides as $idx => $slide): ?>
                            <figure class="pdp-slide<?= $idx === 0 ? ' is-active' : '' ?><?= ($slide['group'] ?? '') === 'drawing' ? ' is-drawing' : '' ?>"
                                    data-pdp-slide="<?= (int) $idx ?>"
                                    data-slide-group="<?= e((string) $slide['group']) ?>"
                                    <?= $idx === 0 ? '' : 'hidden' ?>>
                                <img src="<?= e((string) $slide['url']) ?>"
                                     alt="<?= e((string) $slide['alt']) ?>"
                                     <?= $idx === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                                     decoding="async"
                                     onerror="this.onerror=null; this.src='<?= e(asset($fallbackImg)) ?>';">
                            </figure>
                        <?php endforeach; ?>

                        <?php if (count($slides) > 1): ?>
                            <button type="button" class="pdp-nav pdp-nav--prev" data-pdp-prev
                                    aria-label="<?= e(trans('products.view_prev')) ?>">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <button type="button" class="pdp-nav pdp-nav--next" data-pdp-next
                                    aria-label="<?= e(trans('products.view_next')) ?>">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        <?php endif; ?>

                        <button type="button" class="pdp-zoom-btn" data-pdp-zoom
                                aria-label="<?= e(trans('products.zoom_image', ['image' => $name])) ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M11 8v6M8 11h6"/>
                            </svg>
                        </button>
                    <?php else: ?>
                        <div class="pdp-empty-media" aria-hidden="true">
                            <span>LUFLY</span>
                        </div>
                    <?php endif; ?>
                </div>

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

                <div class="pdp-stage-bar">
                    <?php if ($slides !== []): ?>
                        <button type="button" class="pdp-stage-tool" data-pdp-zoom>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
                            </svg>
                            <span><?= e(trans('products.pdp_fullscreen')) ?></span>
                        </button>
                    <?php endif; ?>

                    <?php if ($primaryDrawingUrl !== null): ?>
                        <button type="button" class="pdp-stage-tool" data-pdp-goto-group="drawing">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 7h18M3 17h18M7 3v18M17 3v18"/>
                            </svg>
                            <span><?= e(trans('products.pdp_view_cad')) ?></span>
                        </button>
                    <?php endif; ?>

                    <button type="button" class="pdp-stage-tool"
                            data-pdp-copy="<?= e($canonicalUrl) ?>"
                            data-copied-label="<?= e(trans('products.pdp_copied')) ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                        </svg>
                        <span data-copy-text><?= e(trans('products.pdp_share_link')) ?></span>
                    </button>
                </div>
            </section>

            <!-- Right Column: Luxury Marketplace Buy-Box Cockpit -->
            <aside class="pdp-info">
                <div class="pdp-header-meta">
                    <?php if ($leafCategoryName !== ''): ?>
                        <a class="pdp-eyebrow" href="<?= e($leafCategoryUrl) ?>"><?= e($leafCategoryName) ?></a>
                    <?php endif; ?>

                    <?php if ($isFeatured): ?>
                        <span class="pdp-featured-badge pdp-code-chip is-gold">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2.5 14.9 8.4l6.5.9-4.7 4.6 1.1 6.5L12 17.3 6.2 20.4l1.1-6.5-4.7-4.6 6.5-.9L12 2.5z"/>
                            </svg>
                            <span><?= e(trans('products.pdp_featured')) ?></span>
                        </span>
                    <?php endif; ?>

                    <span class="pdp-stock-pill">
                        <i aria-hidden="true"></i>
                        <span><?= e(trans('products.pdp_in_stock')) ?></span>
                    </span>
                </div>

                <h1 class="pdp-title"><?= e($name) ?></h1>

                <?php if ($code !== '' || $dimensionSummary !== ''): ?>
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

                        <?php if ($dimensionSummary !== ''): ?>
                            <span class="pdp-dim-chip">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 6H3M21 18H3M6 3v18M18 3v18"/>
                                </svg>
                                <span><?= e($dimensionSummary) ?></span>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="pdp-colors-card">
                    <?= $view->component('product-color-variants', [
                        'item' => $product,
                        'card' => $card,
                    ]) ?>
                </div>

                <?php if ($presentedFeatures !== [] || $cardFeatureBadges !== []): ?>
                    <section class="pdp-highlights-box" aria-label="<?= e(trans('products.pdp_highlights_title')) ?>">
                        <h2 class="pdp-box-heading"><?= e(trans('products.pdp_highlights_title')) ?></h2>
                        <?php if ($presentedFeatures !== []): ?>
                            <ul class="pdp-highlights">
                                <?php foreach ($presentedFeatures as $pf): ?>
                                    <li class="pdp-highlight">
                                        <?= $view->component('product-card-icon', ['name' => (string) ($pf['icon'] ?? 'spark')]) ?>
                                        <div class="pdp-highlight-copy">
                                            <small><?= e((string) ($pf['label'] ?? '')) ?></small>
                                            <strong><?= e((string) ($pf['value'] ?? '')) ?></strong>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <ul class="pdp-highlights">
                                <?php foreach ($cardFeatureBadges as $feat): ?>
                                    <li class="pdp-highlight">
                                        <?= $view->component('product-card-icon', ['name' => (string) ($feat['icon'] ?? 'spark')]) ?>
                                        <span><?= e((string) ($feat['label'] ?? '')) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <!-- Flagship Commercial Buy-Box -->
                <div class="pdp-buybox">
                    <div class="pdp-buybox-top">
                        <div class="pdp-price-block">
                            <span class="pdp-price-kicker"><?= e(trans('products.pdp_pricing_badge')) ?></span>
                            <span class="pdp-price<?= $hasPrice ? ' has-price' : '' ?>"><?= e($priceFormatted) ?></span>
                        </div>
                        <?php if ($primaryDrawingUrl !== null): ?>
                            <button type="button" class="pdp-cad-chip" data-pdp-goto-group="drawing">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 7h18M3 17h18M7 3v18M17 3v18"/>
                                </svg>
                                <span>CAD</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="pdp-actions-primary">
                        <a class="pdp-cta-primary" href="<?= e($quoteHref) ?>">
                            <span><?= e(trans('products.request_quote')) ?></span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>
                            </svg>
                        </a>

                        <div class="pdp-actions-pills">
                            <?= $view->component('box-button', [
                                'product_id' => $productId,
                                'name' => $name,
                                'variant' => 'pill',
                                'on' => $inBox,
                            ]) ?>
                            <?= $view->component('favorite-button', [
                                'product_id' => $productId,
                                'name' => $name,
                                'variant' => 'pill',
                                'on' => $isSaved,
                            ]) ?>
                        </div>
                    </div>

                    <div class="pdp-actions-utility">
                        <?= $view->component('box-button', [
                            'product_id' => $productId,
                            'name' => $name,
                            'variant' => 'cart',
                            'on' => $inBox,
                            'label' => trans('products.add_to_cart'),
                            'disabled' => empty($card['cart_enabled']),
                            'disabled_label' => (string) ($card['cart_label'] ?? trans('products.cart_pending')),
                        ]) ?>

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

                        <?php if ($specs !== [] || $cleanDescription !== ''): ?>
                            <a class="pdp-specs-jump" href="#pdp-dossier">
                                <span><?= e(trans('products.pdp_jump_specs')) ?></span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

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

                <!-- 3-Pillar Marketplace Trust Strip -->
                <div class="pdp-trust-grid">
                    <div class="pdp-trust-item">
                        <span class="pdp-trust-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
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
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
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
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 7h18M3 17h18M7 3v18M17 3v18"/>
                            </svg>
                        </span>
                        <div>
                            <strong><?= e(trans('products.pdp_trust_bim_title')) ?></strong>
                            <span><?= e(trans('products.pdp_trust_bim_sub')) ?></span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Full-Width Engineering & Documentation Dossier -->
        <?php if ($cleanDescription !== '' || $specs !== [] || $primaryDrawingUrl !== null): ?>
            <section class="pdp-dossier" id="pdp-dossier" aria-labelledby="pdp-dossier-heading">
                <header class="pdp-section-head">
                    <div>
                        <span class="pdp-section-kicker"><?= e(trans('products.pdp_dossier_kicker')) ?></span>
                        <h2 class="pdp-section-title" id="pdp-dossier-heading"><?= e(trans('products.pdp_dossier_title')) ?></h2>
                    </div>
                </header>

                <div class="pdp-dossier-grid">
                    <div class="pdp-dossier-overview">
                        <?php if ($cleanDescription !== ''): ?>
                            <div class="pdp-prose">
                                <p><?= nl2br(e($cleanDescription)) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($primaryDrawingUrl !== null): ?>
                            <div class="pdp-blueprint-card">
                                <div class="pdp-blueprint-preview"
                                     data-pdp-external-zoom="<?= e($primaryDrawingUrl) ?>"
                                     data-pdp-external-alt="<?= e($name . ' — ' . trans('products.pdp_view_cad')) ?>">
                                    <img src="<?= e($primaryDrawingUrl) ?>"
                                         alt="<?= e($name . ' — ' . trans('products.pdp_view_cad')) ?>"
                                         loading="lazy"
                                         decoding="async">
                                </div>
                                <div class="pdp-blueprint-copy">
                                    <span class="pdp-blueprint-kicker"><?= e(trans('products.pdp_cad_available')) ?></span>
                                    <strong><?= e($name) ?></strong>
                                    <?php if ($dimensionSummary !== ''): ?>
                                        <span class="pdp-blueprint-dim"><?= e($dimensionSummary) ?></span>
                                    <?php endif; ?>
                                    <button type="button" class="pdp-blueprint-btn"
                                            data-pdp-external-zoom="<?= e($primaryDrawingUrl) ?>"
                                            data-pdp-external-alt="<?= e($name . ' — ' . trans('products.pdp_view_cad')) ?>"
                                            data-slide-target="<?= (int) $firstDrawingIndex ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M11 8v6M8 11h6"/>
                                        </svg>
                                        <span><?= e(trans('products.pdp_cad_open')) ?></span>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="pdp-downloads-box">
                            <h3 class="pdp-box-heading"><?= e(trans('products.pdp_downloads_title')) ?></h3>
                            <div class="pdp-downloads-row">
                                <?php if ($datasheetUrl !== null): ?>
                                    <a class="pdp-datasheet-btn is-ready"
                                       href="<?= e(asset($datasheetUrl)) ?>"
                                       target="_blank"
                                       rel="noopener"
                                       download>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/>
                                        </svg>
                                        <span><?= e(trans('products.pdp_datasheet_download')) ?></span>
                                    </a>
                                <?php else: ?>
                                    <button type="button"
                                            class="pdp-datasheet-btn"
                                            disabled
                                            aria-disabled="true"
                                            title="<?= e(trans('products.pdp_datasheet_pending')) ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>
                                        </svg>
                                        <span><?= e(trans('products.pdp_datasheet_btn')) ?> (PDF)</span>
                                        <em class="pdp-datasheet-tag"><?= e(trans('products.pdp_datasheet_soon')) ?></em>
                                    </button>
                                <?php endif; ?>

                                <?php if ($primaryDrawingUrl !== null): ?>
                                    <button type="button"
                                            class="pdp-doc-link"
                                            data-pdp-external-zoom="<?= e($primaryDrawingUrl) ?>"
                                            data-pdp-external-alt="<?= e($name . ' — ' . trans('products.pdp_view_cad')) ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 7h18M3 17h18M7 3v18M17 3v18"/>
                                        </svg>
                                        <span><?= e(trans('products.pdp_view_cad')) ?></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($specs !== [] || $hasDims): ?>
                        <div class="pdp-specs">
                            <h3 class="pdp-specs-title"><?= e(trans('products.specs_title')) ?></h3>
                            <dl class="pdp-spec-grid">
                                <?php if ($categoryTrail !== []): ?>
                                    <div class="pdp-spec-row">
                                        <dt><?= e(trans('products.category_label')) ?></dt>
                                        <dd><a class="pdp-spec-link" href="<?= e($leafCategoryUrl) ?>"><?= e(implode(' › ', array_column($categoryTrail, 'name'))) ?></a></dd>
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
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Marketplace Tier 1: Similar Products in This Category -->
        <?php if ($similarProducts !== []): ?>
            <section class="pdp-market-section" data-pdp-similar aria-labelledby="pdp-similar-heading">
                <header class="pdp-section-head">
                    <div>
                        <span class="pdp-section-kicker"><?= e(trans('products.pdp_similar_kicker')) ?></span>
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
                        <span class="pdp-section-kicker"><?= e(trans('products.pdp_complete_kicker')) ?></span>
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
        <img class="pdp-lightbox-img" data-pdp-lightbox-img src="" alt="">
    </div>
</main>
