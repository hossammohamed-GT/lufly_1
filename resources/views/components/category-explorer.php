<?php
/**
 * Reusable three-level category atlas.
 *
 * All nodes and links are rendered by the server. JavaScript only enhances
 * drill-down navigation; without it every category still opens its filtered
 * product collection. Product counts, when supplied, already include all
 * descendants through CategoryService.
 *
 * @var Core\View\View $view
 * @var array<int, array<string, mixed>> $categories
 */
$view->pushDeferredStyle('frontend/components/category-explorer/category-explorer.css');
$view->pushScript('frontend/components/category-explorer/category-explorer.js');

$tree = is_array($categories ?? null) ? array_values($categories) : [];
if ($tree === []) {
    return;
}

$mode = ($mode ?? 'home') === 'catalog' ? 'catalog' : 'home';
$activeSlug = trim((string) ($activeCategory ?? ''));
$counts = is_array($categoryCounts ?? null) ? $categoryCounts : null;
if ($counts !== null) {
    $pruneEmpty = static function (array $items) use (&$pruneEmpty, $counts): array {
        $filtered = [];
        foreach ($items as $item) {
            $slug = trim((string) ($item['slug'] ?? ''));
            if ($slug === '' || (int) ($counts[$slug] ?? 0) <= 0) {
                continue;
            }
            $item['children'] = is_array($item['children'] ?? null) ? $pruneEmpty($item['children']) : [];
            $filtered[] = $item;
        }
        return $filtered;
    };
    $tree = $pruneEmpty($tree);
    if ($tree === []) {
        return;
    }
}
$urlBuilder = $buildUrl ?? null;
$fallbackImage = asset('images/favicon.png');

$nodes = [];
$order = [];
$walk = function (array $items, array $ancestors = [], string $rootSlug = '') use (&$walk, &$nodes, &$order): void {
    foreach ($items as $item) {
        $slug = trim((string) ($item['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }
        $name = trim((string) ($item['name'] ?? '')) ?: $slug;
        $children = is_array($item['children'] ?? null) ? array_values($item['children']) : [];
        $lineage = [...$ancestors, ['slug' => $slug, 'name' => $name]];
        $node = $item;
        $node['slug'] = $slug;
        $node['name'] = $name;
        $node['children'] = $children;
        $node['lineage'] = $lineage;
        $node['root'] = $rootSlug !== '' ? $rootSlug : $slug;
        $nodes[$slug] = $node;
        $order[] = $slug;
        $walk($children, $lineage, (string) $node['root']);
    }
};
$walk($tree);

$selectedSlug = isset($nodes[$activeSlug]) ? $activeSlug : '';
$selectedRoot = $selectedSlug !== '' ? (string) ($nodes[$selectedSlug]['root'] ?? '') : '';
$openPanelSlug = $selectedSlug;
if ($mode === 'catalog' && $selectedSlug !== '' && isset($nodes[$selectedSlug])) {
    $selectedNode = $nodes[$selectedSlug];
    $selectedDepth = (int) ($selectedNode['depth'] ?? count($selectedNode['lineage'] ?? []));
    $selectedChildren = is_array($selectedNode['children'] ?? null) ? $selectedNode['children'] : [];
    if ($selectedDepth >= 2 && $selectedChildren === []) {
        $lineage = is_array($selectedNode['lineage'] ?? null) ? $selectedNode['lineage'] : [];
        $parentSlug = count($lineage) >= 2 ? (string) ($lineage[count($lineage) - 2]['slug'] ?? '') : '';
        if ($parentSlug !== '' && isset($nodes[$parentSlug])) {
            $openPanelSlug = $parentSlug;
        }
    }
}

$categoryUrl = static function (string $slug) use ($urlBuilder): string {
    if (is_callable($urlBuilder)) {
        return (string) $urlBuilder(['category' => $slug !== '' ? $slug : null, 'page' => null]);
    }

    return route('products.index', $slug !== '' ? ['category' => $slug] : []);
};
$productsUrl = static fn (string $slug): string => $categoryUrl($slug) . '#catalog-results';
$childLabel = static fn (int $number): string => $number === 1
    ? trans('products.category_children_one')
    : trans('products.category_children_many', ['n' => number_format($number)]);
$productLabel = static fn (int $number): string => $number === 1
    ? trans('products.results_one')
    : trans('products.results_many', ['n' => number_format($number)]);
$levelLabel = static fn (int $depth): string => trans('products.category_level_' . min(3, max(1, $depth)));
$isLifestyle = static fn (string $path): bool => (bool) preg_match('/\.jpe?g$/i', $path);
?>
<section class="category-explorer category-explorer--<?= e($mode) ?>"
         id="<?= $mode === 'home' ? 'categories' : 'category-explorer' ?>"
         data-category-explorer data-selected="<?= e($selectedSlug) ?>">
    <div class="category-explorer-inner<?= $mode === 'home' ? ' container' : '' ?>">
        <?php if ($mode === 'home'): ?>
            <div class="section-header-center section-header-on-photo scroll-reveal category-explorer-heading">
                <span class="section-tag"><?= e(trans('home.collections_tag')) ?></span>
                <h2 class="section-title"><?= e(trans('home.design_title')) ?></h2>
                <p class="section-subtitle"><?= e(trans('home.design_desc')) ?></p>
                <span class="category-explorer-total"><?= e(trans('products.category_total', ['n' => number_format(count($nodes))])) ?></span>
            </div>
        <?php else: ?>
            <div class="category-explorer-compact-head">
                <span class="category-explorer-kicker"><?= e(trans('products.category_atlas')) ?></span>
                <span class="category-explorer-total"><?= e(trans('products.category_total', ['n' => number_format(count($nodes))])) ?></span>
            </div>
        <?php endif; ?>

        <div class="category-explorer-shell">
            <div class="category-roots">
                <div class="category-roots-label">
                    <span><?= e(trans('products.category_level_1')) ?></span>
                    <span><?= str_pad((string) count($tree), 2, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="category-root-list">
                    <?php foreach ($tree as $rootIndex => $root): ?>
                        <?php
                        $rootSlug = (string) $root['slug'];
                        $name = (string) ($root['name'] ?? $rootSlug);
                        $image = trim((string) ($root['image'] ?? '')) ?: '/images/favicon.png';
                        $children = is_array($root['children'] ?? null) ? $root['children'] : [];
                        $isActive = $selectedRoot === $rootSlug;
                        $isLead = $selectedRoot !== '' ? $isActive : $rootIndex === 0;
                        $meta = $counts !== null
                            ? $productLabel((int) ($counts[$rootSlug] ?? 0))
                            : (count($children) > 0 ? $childLabel(count($children)) : trans('products.category_view_products'));
                        ?>
                        <a href="<?= e($categoryUrl($rootSlug)) ?>"
                           class="category-root category-root--shape-<?= ($rootIndex % 4) + 1 ?><?= $isActive ? ' is-active' : '' ?><?= $isLead ? ' is-lead' : '' ?>"
                           data-category-select="<?= e($rootSlug) ?>" data-category-root="<?= e($rootSlug) ?>"
                           aria-controls="category-panels-<?= e($mode) ?>-<?= (int) $root['id'] ?>"
                           aria-expanded="<?= $isActive ? 'true' : 'false' ?>"
                           <?= $isActive ? 'aria-current="location"' : '' ?>
                           aria-label="<?= e(trans('products.category_open', ['name' => $name])) ?>">
                            <span class="category-root-index"><?= str_pad((string) ($rootIndex + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="category-root-image<?= $isLifestyle($image) ? ' is-lifestyle' : '' ?>">
                                <img src="<?= e(asset(ltrim($image, '/'))) ?>" alt="" loading="lazy" decoding="async" width="320" height="220"
                                     onerror="this.onerror=null;this.src='<?= e($fallbackImage) ?>';">
                            </span>
                            <span class="category-root-copy">
                                <strong><?= e($name) ?></strong>
                                <small><?= e($meta) ?></small>
                            </span>
                            <svg class="category-root-arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 10h11m-4-4 4 4-4 4"/></svg>
                        </a>
                        <div id="category-panels-<?= e($mode) ?>-<?= (int) $root['id'] ?>" class="category-panels" data-category-panels-root="<?= e($rootSlug) ?>" <?= $isActive ? '' : 'hidden' ?>>
                            <?php foreach ($order as $slug): ?>
                                <?php if ((string) $nodes[$slug]['root'] !== $rootSlug) { continue; } ?>
                                <?php
                                $node = $nodes[$slug];
                                $name = (string) $node['name'];
                                $depth = (int) ($node['depth'] ?? 1);
                                $children = $node['children'];
                                $image = trim((string) ($node['image'] ?? '')) ?: '/images/favicon.png';
                                $description = trim((string) ($node['description'] ?? ''));
                                $isSelected = $slug === $openPanelSlug;
                                $panelId = 'category-panel-' . $mode . '-' . (int) $node['id'];
                                $hasRootContext = $depth === 2 && $children !== [] && isset($nodes[(string) $node['root']]);
                                $contextRoot = $hasRootContext ? $nodes[(string) $node['root']] : null;
                                ?>
                                <article id="<?= e($panelId) ?>" class="category-panel"
                                         data-category-panel="<?= e($slug) ?>" data-root="<?= e((string) $node['root']) ?>" data-depth="<?= (int) $depth ?>"
                                         aria-labelledby="category-heading-<?= e($mode) ?>-<?= (int) $node['id'] ?>"
                                         <?= $isSelected ? '' : 'hidden' ?>>
                                    <nav class="category-breadcrumb" aria-label="<?= e(trans('products.category_atlas')) ?>">
                                        <a href="<?= e($categoryUrl('')) ?>"<?= $mode === 'catalog' ? ' data-category-clear' : '' ?>><?= e(trans('products.category_atlas')) ?></a>
                                        <?php foreach ($node['lineage'] as $crumbIndex => $crumb): ?>
                                            <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" aria-hidden="true"><path d="m4 2 4 4-4 4"/></svg>
                                            <?php if ($crumbIndex === count($node['lineage']) - 1): ?>
                                                <span aria-current="page"><?= e((string) $crumb['name']) ?></span>
                                            <?php else: ?>
                                                <a href="<?= e($categoryUrl((string) $crumb['slug'])) ?>"
                                                   data-category-select="<?= e((string) $crumb['slug']) ?>"
                                                   aria-label="<?= e(trans('products.category_back', ['name' => (string) $crumb['name']])) ?>">
                                                    <?= e((string) $crumb['name']) ?>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </nav>

                                    <div class="category-panel-hero<?= $hasRootContext ? ' has-root-context' : '' ?>">
                                        <?php if ($hasRootContext): ?>
                                            <?php
                                            $contextSlug = (string) $contextRoot['slug'];
                                            $contextName = (string) ($contextRoot['name'] ?? $contextSlug);
                                            $contextImage = trim((string) ($contextRoot['image'] ?? '')) ?: '/images/favicon.png';
                                            $contextChildren = is_array($contextRoot['children'] ?? null) ? $contextRoot['children'] : [];
                                            ?>
                                            <div class="category-root-context">
                                                <span class="category-root-context-image<?= $isLifestyle($contextImage) ? ' is-lifestyle' : '' ?>" aria-hidden="true">
                                                    <img src="<?= e(asset(ltrim($contextImage, '/'))) ?>" alt="" loading="lazy" decoding="async" width="320" height="220"
                                                         onerror="this.onerror=null;this.src='<?= e($fallbackImage) ?>';">
                                                </span>
                                                <div class="category-root-context-heading">
                                                    <span class="category-root-context-title">
                                                        <small><?= e($levelLabel(1)) ?></small>
                                                        <strong><?= e($contextName) ?></strong>
                                                    </span>
                                                </div>
                                                <div class="category-root-context-meta">
                                                    <span><?= e($childLabel(count($contextChildren))) ?></span>
                                                    <?php if ($counts !== null): ?>
                                                        <span><?= e($productLabel((int) ($counts[$contextSlug] ?? 0))) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <a class="category-root-context-cta" href="<?= e($productsUrl($contextSlug)) ?>"
                                                   <?= $mode === 'catalog' ? 'data-category-select="' . e($contextSlug) . '" data-category-reveal="true"' : '' ?>
                                                   aria-label="<?= e(trans('products.category_view_products')) ?>: <?= e($contextName) ?>">
                                                    <span><?= e(trans('products.category_view_products')) ?></span>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="category-panel-copy">
                                            <span class="category-panel-level">
                                                <b><?= str_pad((string) $depth, 2, '0', STR_PAD_LEFT) ?></b>
                                                <?= e($levelLabel($depth)) ?>
                                            </span>
                                            <h3 id="category-heading-<?= e($mode) ?>-<?= (int) $node['id'] ?>" tabindex="-1" data-category-heading><?= e($name) ?></h3>
                                            <?php if ($description !== ''): ?>
                                                <p><?= e($description) ?></p>
                                            <?php endif; ?>
                                            <div class="category-panel-meta">
                                                <?php if ($children !== []): ?>
                                                    <span><?= e($childLabel(count($children))) ?></span>
                                                <?php endif; ?>
                                                <?php if ($counts !== null): ?>
                                                    <span><?= e($productLabel((int) ($counts[$slug] ?? 0))) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="category-panel-actions">
                                                <a class="category-panel-cta" href="<?= e($productsUrl($slug)) ?>"
                                                   data-category-filter="<?= e($slug) ?>" data-category-reveal="true">
                                                    <span><?= e(trans('products.category_view_products')) ?></span>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg>
                                                </a>
                                                <?php if ($depth === 2 && $children !== []): ?>
                                                    <?php $parentName = (string) ($node['lineage'][0]['name'] ?? $node['root']); ?>
                                                    <button type="button" class="category-panel-collapse"
                                                            data-category-select="<?= e($slug) ?>"
                                                            aria-controls="<?= e($panelId) ?>" aria-expanded="true"
                                                            aria-label="<?= e(trans('products.category_back', ['name' => $parentName])) ?>"
                                                            title="<?= e(trans('products.category_back', ['name' => $parentName])) ?>">
                                                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m5 12 5-5 5 5"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="category-panel-visual<?= $isLifestyle($image) ? ' is-lifestyle' : '' ?>">
                                            <img src="<?= e(asset(ltrim($image, '/'))) ?>" alt="<?= e($name) ?>"
                                                 loading="lazy" decoding="async" width="840" height="560"
                                                 onerror="this.onerror=null;this.src='<?= e($fallbackImage) ?>';">
                                            <span class="category-panel-number" aria-hidden="true">L / <?= str_pad((string) $node['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                        </div>
                                    </div>

                                    <?php if ($children !== []): ?>
                                        <div class="category-children-head">
                                            <span><?= e($levelLabel($depth + 1)) ?></span>
                                            <span><?= str_pad((string) count($children), 2, '0', STR_PAD_LEFT) ?></span>
                                        </div>
                                        <div class="category-child-grid">
                                            <?php foreach ($children as $child): ?>
                                                <?php
                                                $childSlug = (string) $child['slug'];
                                                $childName = (string) ($child['name'] ?? $childSlug);
                                                $childImage = trim((string) ($child['image'] ?? '')) ?: '/images/favicon.png';
                                                $grandchildren = is_array($child['children'] ?? null) ? $child['children'] : [];
                                                $isLeafChild = $grandchildren === [];
                                                $isChildActive = $mode === 'catalog' && $isLeafChild && $selectedSlug === $childSlug;
                                                $childMeta = $counts !== null
                                                    ? $productLabel((int) ($counts[$childSlug] ?? 0))
                                                    : ($grandchildren !== [] ? $childLabel(count($grandchildren)) : trans('products.category_view_products'));
                                                ?>
                                                <a href="<?= e($grandchildren !== [] ? $categoryUrl($childSlug) : $productsUrl($childSlug)) ?>"
                                                    class="category-child<?= $isChildActive ? ' is-active' : '' ?>"
                                                    data-category-leaf="<?= $isLeafChild ? 'true' : 'false' ?>"
                                                    <?= ($grandchildren !== [] || $mode === 'catalog') ? 'data-category-select="' . e($childSlug) . '" aria-controls="category-panel-' . e($mode) . '-' . (int) $child['id'] . '"' : '' ?>
                                                    <?= $grandchildren !== [] ? 'aria-expanded="false"' : '' ?>
                                                    <?= $isChildActive ? 'aria-current="location"' : '' ?>
                                                    aria-label="<?= e(trans('products.category_open', ['name' => $childName])) ?>">
                                                    <span class="category-child-image<?= $isLifestyle($childImage) ? ' is-lifestyle' : '' ?>">
                                                        <img src="<?= e(asset(ltrim($childImage, '/'))) ?>" alt="" loading="lazy" decoding="async" width="480" height="310"
                                                             onerror="this.onerror=null;this.src='<?= e($fallbackImage) ?>';">
                                                    </span>
                                                    <span class="category-child-copy">
                                                        <strong><?= e($childName) ?></strong>
                                                        <small><?= e($childMeta) ?></small>
                                                    </span>
                                                    <svg class="category-child-arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 10h11m-4-4 4 4-4 4"/></svg>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="category-leaf-note">
                                            <span aria-hidden="true"></span>
                                            <p><?= e(trans('products.category_leaf')) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="sr-only" aria-live="polite" data-category-status></p>
            </div>
            <?php if ($mode === 'catalog'): ?>
                <a class="category-results-cue" href="#catalog-results" data-category-results-cue
                   data-label-ready="<?= e(trans('products.category_results_below')) ?>"
                   data-label-loading="<?= e(trans('products.category_results_loading')) ?>"
                   data-label-error="<?= e(trans('products.category_results_error')) ?>" hidden>
                    <span class="category-results-cue-symbol" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 5h14M5 12h14M5 19h9"/></svg>
                    </span>
                    <span class="category-results-cue-copy" aria-live="polite">
                        <small data-category-results-label><?= e(trans('products.category_results_below')) ?></small>
                        <strong data-category-results-name></strong>
                    </span>
                    <span class="category-results-cue-count" data-category-results-count></span>
                    <svg class="category-results-cue-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 4v15m-6-6 6 6 6-6"/></svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
