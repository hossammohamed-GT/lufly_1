<?php
/**
 * Storefront catalog - machined editorial identity.
 * Coded header with result count, blueprint toolbar (search + sort),
 * category rail with counts, editorial product cards (main shot with a
 * situ crossfade on hover), machined pagination.
 *
 * @var Core\View\View $view
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/css/product-card.css');
$view->pushStyle('frontend/css/live-search.css');
$view->pushStyle('frontend/products/catalog/catalog.css');
$view->pushScript('frontend/js/live-search.js');
$view->pushScript('frontend/products/catalog/catalog.js');
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
$searchQuery = $searchQuery ?? '';
$sort = $sort ?? 'newest';
$total = (int) $paginator->total();

$sortOptions = [
    'newest' => trans('products.sort_newest'),
    'name' => trans('products.sort_name'),
    'model' => trans('products.sort_model'),
];

$buildUrl = static function (array $overrides = []) use ($activeCategory, $searchQuery, $sort): string {
    $params = array_filter(array_merge([
        'category' => $activeCategory !== '' ? $activeCategory : null,
        'q' => $searchQuery !== '' ? $searchQuery : null,
        'sort' => $sort !== 'newest' ? $sort : null,
    ], $overrides), static fn ($v) => $v !== null && $v !== '');

    return route('products.index') . (count($params) > 0 ? '?' . http_build_query($params) : '');
};

$fallbackImg = '/images/products/prod_146_1620-111-a.jpg';
?>
<main class="catalog" id="main">
    <div class="catalog-inner">
        <header class="catalog-head">
            <div class="catalog-titles">
                <span class="catalog-eyebrow"><?= e(trans('common.factory_direct')) ?> &middot; EN-997 / CE</span>
                <h1 class="catalog-title"><?= e(trans('products.title')) ?></h1>
            </div>
            <span class="catalog-count">
                <?= e($total === 1 ? trans('products.results_one') : trans('products.results_many', ['n' => number_format($total)])) ?>
            </span>
        </header>

        <div class="catalog-toolbar" data-livesearch
            data-livesearch-grid="[data-catalog-grid]"
            data-livesearch-pagination="[data-catalog-pagination]"
            data-livesearch-count=".catalog-count">
            <form method="GET" action="<?= e(route('products.index')) ?>" class="catalog-searchform">
                <?php if ($activeCategory !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($activeCategory) ?>">
                <?php endif; ?>

                <div class="catalog-search" data-catalog-field>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="<?= e(trans('common.search')) ?>" class="catalog-search-input" data-livesearch-input data-livesearch-limit="36" data-locale="<?= e($locale) ?>" autocomplete="off" aria-label="<?= e(trans('common.search')) ?>">
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

            <div class="catalog-guide" data-livesearch-results aria-live="polite">
                <div class="catalog-scope" data-livesearch-scope>
                    <span class="catalog-scope-label"><?= e(trans('products.scope_label')) ?></span>
                    <?php if ($activeCategory !== ''): ?>
                        <button type="button" class="catalog-scope-btn is-active" data-scope="category"><?= e(trans('products.scope_category')) ?></button>
                    <?php endif; ?>
                    <button type="button" class="catalog-scope-btn <?= $activeCategory === '' ? 'is-active' : '' ?>" data-scope="all"><?= e(trans('products.scope_all')) ?></button>
                </div>
            </div>
        </div>

        <nav class="catalog-rail" aria-label="<?= e(trans('common.category')) ?>">
            <a href="<?= e($buildUrl(['category' => null])) ?>"
               class="catalog-pill <?= $activeCategory === '' ? 'is-active' : '' ?>">
                <?= e(trans('common.all_categories')) ?>
                <span class="catalog-pill-n"><?= e(number_format($categoryCounts[''] ?? 0)) ?></span>
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= e($buildUrl(['category' => $cat['slug']])) ?>"
                   class="catalog-pill <?= $activeCategory === $cat['slug'] ? 'is-active' : '' ?>">
                    <?= e($cat['name'] ?: $cat['slug']) ?>
                    <span class="catalog-pill-n"><?= e(number_format($categoryCounts[(string) $cat['slug']] ?? 0)) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($paginator->items() === []): ?>
            <div class="catalog-empty">
                <span class="catalog-empty-code">404 &middot; <?= e(trans('common.no_results')) ?></span>
                <p><?= e(trans('products.empty')) ?></p>
                <a href="<?= e(route('products.index')) ?>" class="catalog-empty-btn"><?= e(trans('common.all_categories')) ?></a>
            </div>
        <?php else: ?>
            <div class="catalog-grid" data-catalog-grid>
                <?php foreach ($paginator->items() as $index => $product):
                    $item = $product->translate($locale);
                    $img = (string) ($item['image'] ?? '') !== '' ? (string) $item['image'] : $fallbackImg;
                    $situ = (string) ($item['situ_image'] ?? '');
                    $cardCode = (string) ($item['sku'] ?? $item['model_code'] ?? '');
                    $cardNo = (int) (($paginator->page() - 1) * $paginator->perPage() + $index + 1);
                ?>
                    <article class="pcard" style="--pcard-delay: <?= e((string) (0.04 * (int) (($index % 6) + 1))) ?>s">
                        <a class="pcard-media" href="<?= e(route('products.show', ['slug' => $product->slug])) ?>">
                            <img src="<?= e(asset($img)) ?>"
                                 alt="<?= e($item['name'] ?? '') ?>"
                                 class="pcard-img"
                                 loading="lazy" decoding="async" width="420" height="320"
                                 onerror="this.onerror=null; this.src='<?= e(asset($fallbackImg)) ?>';">
                            <?php if ($situ !== ''): ?>
                                <img src="<?= e(asset($situ)) ?>"
                                     alt=""
                                     class="pcard-img pcard-img-situ"
                                     loading="lazy" decoding="async" width="420" height="320"
                                     onerror="this.onerror=null; this.remove();">
                            <?php endif; ?>
                            <span class="pcard-hint" aria-hidden="true">
                                <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                <?= e(trans('products.view_details')) ?>
                            </span>
                        </a>
                        <div class="pcard-body">
                            <div class="pcard-top">
                                <span class="pcard-code"><?= e($cardCode) ?></span>
                                <span class="pcard-no"><?= str_pad((string) $cardNo, 3, '0', STR_PAD_LEFT) ?></span>
                            </div>
                            <h2 class="pcard-title">
                                <a href="<?= e(route('products.show', ['slug' => $product->slug])) ?>"><?= e($item['name'] ?? '') ?></a>
                            </h2>
                            <?php $shortDesc = trim((string) ($item['short_description'] ?? '')); ?>
                            <?php if ($shortDesc !== ''): ?>
                                <p class="pcard-desc"><?= e($shortDesc) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($paginator->lastPage() > 1): ?>
                <nav class="catpag" data-catalog-pagination aria-label="Pagination">
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
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
