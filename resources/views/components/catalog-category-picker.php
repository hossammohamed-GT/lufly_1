<?php
/**
 * Progressive category drill-down for the catalog search bar.
 * Real links remain usable without JavaScript; the controller only enhances
 * the nested navigation and no-reload product filtering.
 *
 * @var array<int, array<string, mixed>> $categories
 */
$tree = is_array($categories ?? null) ? array_values($categories) : [];
if ($tree === []) {
    return;
}

$counts = is_array($categoryCounts ?? null) ? $categoryCounts : [];
$activeSlug = trim((string) ($activeCategory ?? ''));
$urlBuilder = $buildUrl ?? null;
$nodes = [];
$walk = static function (array $items, string $parent = '', int $depth = 0, array $ancestors = []) use (&$walk, &$nodes): void {
    foreach ($items as $item) {
        $slug = trim((string) ($item['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }
        $name = trim((string) ($item['name'] ?? '')) ?: $slug;
        $lineage = [...$ancestors, $name];
        $nodes[$slug] = [
            'slug' => $slug,
            'name' => $name,
            'image' => trim((string) ($item['image'] ?? '')),
            'children' => is_array($item['children'] ?? null) ? array_values($item['children']) : [],
            'parent' => $parent,
            'depth' => $depth,
            'path' => implode(' › ', $lineage),
        ];
        $walk($nodes[$slug]['children'], $slug, $depth + 1, $lineage);
    }
};
$walk($tree);

$selected = isset($nodes[$activeSlug]) ? $nodes[$activeSlug] : null;
$currentName = $selected['name'] ?? trans('common.all_categories');
$currentPath = $selected['path'] ?? $currentName;
$currentImage = $selected['image'] ?? '';
$categoryUrl = static function (string $slug) use ($urlBuilder): string {
    if (is_callable($urlBuilder)) {
        return (string) $urlBuilder(['category' => $slug !== '' ? $slug : null, 'page' => null]);
    }
    return route('products.index', $slug !== '' ? ['category' => $slug] : []);
};
$imageUrl = static fn (string $path): string => $path !== '' ? asset(ltrim($path, '/')) : '';
?>
<details class="catalog-category-picker" data-catalog-category-picker data-category-all-name="<?= e(trans('common.all_categories')) ?>">
    <summary class="catalog-category-summary" data-catalog-category-summary title="<?= e($currentPath) ?>">
        <span class="catalog-category-current-thumb" aria-hidden="true">
            <img<?= $currentImage !== '' ? ' src="' . e($imageUrl($currentImage)) . '"' : '' ?> alt="" width="40" height="40" data-catalog-category-current-image onerror="this.hidden=true;this.nextElementSibling.hidden=false"<?= $currentImage === '' ? ' hidden' : '' ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" data-catalog-category-current-fallback<?= $currentImage !== '' ? ' hidden' : '' ?>><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></svg>
        </span>
        <span class="catalog-category-current-copy">
            <small><?= e(trans('common.category')) ?></small>
            <strong data-catalog-category-current-name><?= e($currentName) ?></strong>
        </span>
        <svg class="catalog-category-summary-chevron" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m4 6 4 4 4-4"/></svg>
    </summary>

    <div class="catalog-category-menu" data-catalog-category-menu role="group" aria-label="<?= e(trans('products.catalog_category_prompt')) ?>">
        <span class="visually-hidden" aria-live="polite" data-catalog-category-status></span>
        <div class="catalog-category-view" data-category-view="roots" data-category-view-label="<?= e(trans('products.catalog_category_prompt')) ?>">
            <div class="catalog-category-grid">
                <?php foreach ($tree as $root): ?>
                    <?php $rootNode = $nodes[(string) $root['slug']]; ?>
                    <a href="<?= e($categoryUrl($rootNode['slug'])) ?>"
                       class="catalog-category-item<?= $selected !== null && $selected['slug'] === $rootNode['slug'] ? ' is-active' : '' ?>"
                       data-category-slug="<?= e($rootNode['slug']) ?>" data-category-name="<?= e($rootNode['name']) ?>"
                       data-category-path="<?= e($rootNode['path']) ?>" data-category-image="<?= e($imageUrl($rootNode['image'])) ?>"
                       <?= $rootNode['children'] !== [] ? 'data-category-drill="' . e($rootNode['slug']) . '" aria-label="' . e(trans('products.category_open', ['name' => $rootNode['name']])) . '"' : 'data-category-choice' ?>>
                        <span class="catalog-category-thumb" aria-hidden="true">
                            <?php if ($rootNode['image'] !== ''): ?>
                                <img src="<?= e($imageUrl($rootNode['image'])) ?>" alt="" loading="lazy" decoding="async" width="48" height="48" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                            <?php endif; ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"<?= $rootNode['image'] !== '' ? ' hidden' : '' ?>><path d="M4 7h16M7 12h10M10 17h4"/></svg>
                        </span>
                        <span class="catalog-category-item-copy"><strong><?= e($rootNode['name']) ?></strong></span>
                        <span class="catalog-category-count"><?= e(number_format((int) ($counts[$rootNode['slug']] ?? 0))) ?></span>
                        <?php if ($rootNode['children'] !== []): ?><svg class="catalog-category-arrow" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php foreach ($nodes as $node): ?>
            <?php if ($node['children'] === []) { continue; } ?>
            <?php
            $parentView = $node['parent'] !== '' ? $node['parent'] : 'roots';
            $parentName = $node['parent'] !== '' && isset($nodes[$node['parent']])
                ? $nodes[$node['parent']]['name']
                : trans('common.all_categories');
            ?>
            <div class="catalog-category-view" data-category-view="<?= e($node['slug']) ?>" data-parent-view="<?= e($parentView) ?>"
                 data-category-view-label="<?= e(trans('products.catalog_category_browse', ['category' => $node['name']])) ?>" hidden>
                <div class="catalog-category-branch-head">
                    <button type="button" class="catalog-category-back" data-category-back="<?= e($parentView) ?>"
                            aria-label="<?= e(trans('products.catalog_category_back_to', ['category' => $parentName])) ?>">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m12 4-6 6 6 6"/></svg>
                        <span><?= e(trans('common.back')) ?></span>
                    </button>
                    <a href="<?= e($categoryUrl($node['slug'])) ?>" class="catalog-category-branch-current<?= $selected !== null && $selected['slug'] === $node['slug'] ? ' is-active' : '' ?>"
                       data-category-choice data-category-slug="<?= e($node['slug']) ?>" data-category-name="<?= e($node['name']) ?>"
                       data-category-path="<?= e($node['path']) ?>" data-category-image="<?= e($imageUrl($node['image'])) ?>">
                        <span><?= e(trans('products.catalog_category_all', ['category' => $node['name']])) ?></span>
                        <svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 9h11m-4-4 4 4-4 4"/></svg>
                    </a>
                </div>
                <div class="catalog-category-grid">
                    <?php foreach ($node['children'] as $child): ?>
                        <?php $childNode = $nodes[(string) $child['slug']]; ?>
                        <a href="<?= e($categoryUrl($childNode['slug'])) ?>"
                           class="catalog-category-item<?= $selected !== null && $selected['slug'] === $childNode['slug'] ? ' is-active' : '' ?>"
                           data-category-slug="<?= e($childNode['slug']) ?>" data-category-name="<?= e($childNode['name']) ?>"
                           data-category-path="<?= e($childNode['path']) ?>" data-category-image="<?= e($imageUrl($childNode['image'])) ?>"
                           <?= $childNode['children'] !== [] ? 'data-category-drill="' . e($childNode['slug']) . '" aria-label="' . e(trans('products.category_open', ['name' => $childNode['name']])) . '"' : 'data-category-choice' ?>>
                            <span class="catalog-category-thumb" aria-hidden="true">
                                <?php if ($childNode['image'] !== ''): ?>
                                    <img src="<?= e($imageUrl($childNode['image'])) ?>" alt="" loading="lazy" decoding="async" width="48" height="48" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                                <?php endif; ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"<?= $childNode['image'] !== '' ? ' hidden' : '' ?>><path d="M4 7h16M7 12h10M10 17h4"/></svg>
                            </span>
                            <span class="catalog-category-item-copy"><strong><?= e($childNode['name']) ?></strong></span>
                            <span class="catalog-category-count"><?= e(number_format((int) ($counts[$childNode['slug']] ?? 0))) ?></span>
                            <?php if ($childNode['children'] !== []): ?><svg class="catalog-category-arrow" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</details>
