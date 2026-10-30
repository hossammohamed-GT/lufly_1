<?php
/**
 * Storefront catalog - machined editorial identity.
 * Coded header with result count, blueprint toolbar (search + sort),
 * three-level category atlas with descendant counts, editorial product cards
 * (main shot with a situ crossfade on hover), machined pagination.
 *
 * @var Core\View\View $view
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/css/product-card.css');
$view->pushStyle('frontend/css/live-search.css');
$view->pushStyle('frontend/products/catalog/catalog.css');
$view->pushScript('frontend/js/live-search.js');
$view->pushScript('frontend/js/product-card-variants.js');
$view->pushScript('frontend/products/catalog/catalog.js');
$view->pushScript('frontend/products/catalog/catalog-dock.js');
$view->pushStyle('frontend/components/skeleton/skeleton.css');
$view->pushScript('frontend/components/skeleton/skeleton.js');
$view->pushStyle('frontend/favorites/favorites.css');
$view->pushScript('frontend/favorites/favorites.js');
$view->pushStyle('frontend/box/box.css');
$view->pushScript('frontend/box/box.js');
/** @var Core\Database\Paginator $paginator */
/** @var array $categories */
/** @var array $categoryCounts */
/** @var string $activeCategory */
/** @var string $searchQuery */
/** @var string $sort */
/** @var string $locale */
$categories = $categories ?? [];
$categoryCounts = $categoryCounts ?? [];
$activeCategory = $activeCategory ?? '';
$categoryFallbackName = $categoryFallbackName ?? null;
$searchQuery = $searchQuery ?? '';
$sort = $sort ?? 'curated';
$latestOnly = $latestOnly ?? false;
$total = (int) $paginator->total();

$sortOptions = [
    'curated' => trans('products.sort_curated'),
    'newest' => trans('products.sort_newest'),
    'name' => trans('products.sort_name'),
    'model' => trans('products.sort_model'),
];

$buildUrl = static function (array $overrides = []) use ($activeCategory, $searchQuery, $sort, $latestOnly): string {
    $params = array_filter(array_merge([
        'category' => $activeCategory !== '' ? $activeCategory : null,
        'q' => $searchQuery !== '' ? $searchQuery : null,
        'sort' => $sort !== 'curated' ? $sort : null,
        'latest' => $latestOnly ? 1 : null,
    ], $overrides), static fn ($v) => $v !== null && $v !== '');

    return route('products.index') . (count($params) > 0 ? '?' . http_build_query($params) : '');
};

$heroImage = '/images/categories/001-bathroom.jpg';
$heroName = trans('products.title');
foreach ($categories as $category) {
    if (($category['slug'] ?? '') === 'bathroom') {
        $heroImage = trim((string) ($category['image'] ?? '')) ?: $heroImage;
        $heroName = (string) ($category['name'] ?? $heroName);
        break;
    }
}

/* Products this visitor already saved, so every card shows the heart in the
   right state before the script runs (one query, and none at all for a visitor
   who never used the list). */
$savedIds = [];
if (feature('favorites', true) && class_exists(\Modules\Favorites\Services\FavoriteService::class)) {
    $savedIds = array_flip(app(\Modules\Favorites\Services\FavoriteService::class)->productIds());
}

/* and the pieces already in the quotation box */
$boxIds = [];
if (feature('box', true) && class_exists(\Modules\Box\Services\BoxService::class)) {
    $boxIds = array_flip(app(\Modules\Box\Services\BoxService::class)->productIds());
}
$colorFamilies = app(\Modules\Products\Services\ProductColorFamilyService::class);
?>
<main class="catalog" id="main">
    <div class="catalog-inner">
        <header class="catalog-hero">
            <div class="catalog-hero-copy">
                <nav class="catalog-breadcrumb" aria-label="<?= e(trans('products.catalog_eyebrow')) ?>">
                    <a href="<?= e(route('home')) ?>"><?= e(trans('products.catalog_home')) ?></a>
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" aria-hidden="true"><path d="m4 2 4 4-4 4"/></svg>
                    <span aria-current="page"><?= e($latestOnly ? trans('nav.latest') : trans('products.title')) ?></span>
                </nav>
                <span class="catalog-eyebrow"><?= e(trans('products.catalog_eyebrow')) ?></span>
                <h1 class="catalog-title"><?= e(trans('products.catalog_hero_title')) ?> <em><?= e(trans('products.catalog_hero_accent')) ?></em></h1>
                <p class="catalog-hero-description"><?= e(trans('products.catalog_hero_desc')) ?></p>
                <ul class="catalog-hero-features" aria-label="<?= e(trans('products.catalog_eyebrow')) ?>">
                    <li>
                        <span class="catalog-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m12 3 8 5v8l-8 5-8-5V8l8-5Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg></span>
                        <span><?= e(trans('products.catalog_quality')) ?></span>
                    </li>
                    <li>
                        <span class="catalog-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 20 20 4M7 4h13v13M4 8v12h12"/></svg></span>
                        <span><?= e(trans('products.catalog_design')) ?></span>
                    </li>
                    <li>
                        <span class="catalog-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M19.5 4.5C11 4 5 8 5 14a5 5 0 0 0 5 5c6 0 10-6 9.5-14.5Z"/><path d="M4 21c2-6 6-9 12-12"/></svg></span>
                        <span><?= e(trans('products.catalog_sustainable')) ?></span>
                    </li>
                </ul>
            </div>
            <figure class="catalog-hero-media">
                <img src="<?= e(asset(ltrim($heroImage, '/'))) ?>" alt="<?= e($heroName) ?>" width="1536" height="1024" fetchpriority="high" decoding="async">
                <figcaption><span aria-hidden="true"></span><?= e(trans('products.catalog_hero_note')) ?></figcaption>
            </figure>
        </header>

        <?= $view->component('category-explorer', [
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'activeCategory' => $activeCategory,
            'buildUrl' => $buildUrl,
            'mode' => 'catalog',
        ]) ?>

        <div class="catalog-toolbar" data-livesearch
            data-livesearch-grid="[data-catalog-grid]"
            data-livesearch-pagination="[data-catalog-pagination]"
            data-livesearch-count=".catalog-count"
            data-favorites-endpoint="<?= feature('favorites', true) ? e(route('favorites.toggle')) : '' ?>"
            data-box-endpoint="<?= feature('box', true) && (bool) config('box.enabled', true) ? e(route('box.add')) : '' ?>">
            <div class="catalog-results-head" id="catalog-results" tabindex="-1">
                <div>
                    <span><?= e(trans('products.catalog_eyebrow')) ?></span>
                    <h2><?= e(trans('products.catalog_results_title')) ?></h2>
                </div>
                <span class="catalog-count">
                    <?= e($total === 1 ? trans('products.results_one') : trans('products.results_many', ['n' => number_format($total)])) ?>
                </span>
            </div>
            <div class="catalog-search-slot" data-catalog-dock-slot>
                <form method="GET" action="<?= e(route('products.index')) ?>" class="catalog-searchform" data-catalog-dock role="search" aria-label="<?= e(trans('products.catalog_dock_label')) ?>">
                    <input type="hidden" name="category" value="<?= e($activeCategory) ?>">

                    <?= $view->component('catalog-category-picker', [
                        'categories' => $categories,
                        'categoryCounts' => $categoryCounts,
                        'activeCategory' => $activeCategory,
                        'buildUrl' => $buildUrl,
                    ]) ?>

                    <div class="catalog-search" data-catalog-field>
                        <span class="catalog-search-field">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="<?= e(trans('products.catalog_dock_search')) ?>" class="catalog-search-input" data-livesearch-input data-livesearch-limit="36" data-locale="<?= e($locale) ?>" autocomplete="off" aria-label="<?= e(trans('products.catalog_dock_search')) ?>" aria-controls="catalog-results" spellcheck="false" enterkeyhint="search">
                            <button type="button" class="catalog-search-clear" data-catalog-search-clear aria-label="<?= e(trans('products.catalog_dock_clear')) ?>" title="<?= e(trans('products.catalog_dock_clear')) ?>"<?= $searchQuery === '' ? ' hidden' : '' ?>>
                                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 8 8M14 6l-8 8"/></svg>
                            </button>
                        </span>
                        <button type="submit" class="catalog-go" aria-label="<?= e(trans('common.search')) ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" width="15" height="15" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                            <span><?= e(trans('common.search')) ?></span>
                        </button>
                    </div>

                    <label class="catalog-sort" data-catalog-sortwrap>
                        <span class="catalog-sort-label"><?= e(trans('products.sort_label')) ?></span>
                        <select name="sort" class="catalog-sort-select" data-catalog-sort>
                            <?php foreach ($sortOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $sort === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
            </div>

            <div class="catalog-guide" data-livesearch-results aria-live="polite">
                <div class="catalog-scope" data-livesearch-scope>
                    <span class="catalog-scope-label"><?= e(trans('products.scope_label')) ?></span>
                    <button type="button" class="catalog-scope-btn<?= $activeCategory !== '' ? ' is-active' : '' ?>" data-scope="category"<?= $activeCategory === '' ? ' hidden' : '' ?>><?= e(trans('products.scope_category')) ?></button>
                    <button type="button" class="catalog-scope-btn <?= $activeCategory === '' ? 'is-active' : '' ?>" data-scope="all"><?= e(trans('products.scope_all')) ?></button>
                </div>
            </div>
        </div>

        <div class="catalog-active-filter" data-catalog-active-filter<?= $activeCategory === '' ? ' hidden' : '' ?>>
            <a href="<?= e($buildUrl(['category' => null, 'page' => null])) ?>"
               data-category-filter="" data-category-name="<?= e(trans('common.all_categories')) ?>">
                <?= e(trans('common.all_categories')) ?>
                <span><?= e(number_format($categoryCounts[''] ?? 0)) ?></span>
            </a>
        </div>

        <div data-sk-host>
            <div class="sk-grid" data-sk-skeleton aria-hidden="true">
                <?php for ($sk = 0; $sk < 8; $sk++): ?>
                    <div class="sk sk-card">
                        <div class="sk-card-media"></div>
                        <div class="sk-card-body">
                            <div class="sk-card-top">
                                <span class="sk-line"></span>
                                <span class="sk-line"></span>
                            </div>
                            <div class="sk-line is-lg w-85"></div>
                            <div class="sk-line is-sm w-55"></div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="catalog-grid" data-catalog-grid data-sk-real>
                <?php if (($categoryFallbackName ?? null) !== null): ?>
                    <div class="catalog-category-fallback" data-category-fallback role="status">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 10.5v6M12 7.25v.25" stroke-linecap="round"></path>
                        </svg>
                        <p><?= e(trans('products.category_fallback_notice', ['category' => $categoryFallbackName])) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($paginator->items() === []): ?>
                    <div class="catalog-empty">
                        <span class="catalog-empty-code">404 &middot; <?= e(trans('common.no_results')) ?></span>
                        <p><?= e(trans('products.empty')) ?></p>
                        <a href="<?= e(route('products.index')) ?>" class="catalog-empty-btn"
                           data-category-filter="" data-category-name="<?= e(trans('common.all_categories')) ?>"><?= e(trans('common.all_categories')) ?></a>
                    </div>
                <?php else: ?>
                <?php foreach ($paginator->items() as $index => $product): ?>
                    <?= $view->component('product-card', [
                        'product' => $colorFamilies->attach($product->translate($locale), $locale, $savedIds, $boxIds),
                        'slug' => $product->slug,
                        'index' => $index,
                        'saved' => isset($savedIds[(int) $product->getKey()]),
                        'in_box' => isset($boxIds[(int) $product->getKey()]),
                    ]) ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <nav class="catpag" data-catalog-pagination aria-label="Pagination"<?= $paginator->lastPage() <= 1 ? ' hidden' : '' ?>>
                <?php if ($paginator->lastPage() > 1): ?>
                    <?php if ($paginator->page() > 1): ?>
                        <a class="catpag-arrow" href="<?= e($buildUrl(['page' => $paginator->page() - 1])) ?>" aria-label="Previous">&larr;</a>
                    <?php endif; ?>
                    <?php
                    $start = max(1, $paginator->page() - 2);
                    $end = min($paginator->lastPage(), $paginator->page() + 2);
                    if ($start > 1): ?>
                        <a class="catpag-num" href="<?= e($buildUrl(['page' => 1])) ?>">1</a>
                        <?php if ($start > 2): ?><span class="catpag-dots">&hellip;</span><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($p = $start; $p <= $end; $p++): ?>
                        <?php if ($p === $paginator->page()): ?>
                            <span class="catpag-num is-active" aria-current="page"><?= str_pad((string) $p, 2, '0', STR_PAD_LEFT) ?></span>
                        <?php else: ?>
                            <a class="catpag-num" href="<?= e($buildUrl(['page' => $p])) ?>"><?= str_pad((string) $p, 2, '0', STR_PAD_LEFT) ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($end < $paginator->lastPage()): ?>
                        <?php if ($end < $paginator->lastPage() - 1): ?><span class="catpag-dots">&hellip;</span><?php endif; ?>
                        <a class="catpag-num" href="<?= e($buildUrl(['page' => $paginator->lastPage()])) ?>"><?= $paginator->lastPage() ?></a>
                    <?php endif; ?>
                    <?php if ($paginator->page() < $paginator->lastPage()): ?>
                        <a class="catpag-arrow" href="<?= e($buildUrl(['page' => $paginator->page() + 1])) ?>" aria-label="Next">&rarr;</a>
                    <?php endif; ?>
                <?php endif; ?>
        </nav>
    </div>
</main>
