<?php
use Modules\Products\Services\ProductService;

/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var string $mode */
/** @var array<string, mixed> $filters */
/** @var array{ids: int[], total: int, pages: int, page: int, families: array} $grid */
/** @var array<int, array<string, mixed>> $categoryTree */
/** @var array<int, array<string, mixed>> $presets */
/** @var array<int, array{key: string, label: string, svg: string}> $icons */
/** @var array<int, array{label: string, swatch: string, tr: string, cs: string}> $finishCatalog */
/** @var array<string, int> $reasonSummary */
/** @var int $incompleteTotal */

$isIncomplete = $mode === 'incomplete';
$gridState = [
    'q' => (string) $filters['search'],
    'status' => (string) $filters['status'],
    'sort' => (string) $filters['sort'],
    'featured' => !empty($filters['featured']) ? 1 : 0,
    'is_new' => !empty($filters['is_new']) ? 1 : 0,
    'incomplete' => $isIncomplete || !empty($filters['incomplete']) ? 1 : 0,
    'category_id' => (int) $filters['category_id'],
    'page' => (int) $filters['page'],
];
?>
<div class="adm-browser" data-products-browser
     data-mode="<?= e($mode) ?>"
     data-grid-url="<?= e(route('admin.products.grid')) ?>"
     data-draft-url="<?= e(route('admin.products.draft')) ?>">

    <?php if ($isIncomplete): ?>
        <div class="adm-incomplete-summary">
            <strong><?= e(trans('products.incomplete_title')) ?>: <?= (int) $incompleteTotal ?></strong>
            <?php foreach (['photo', 'drawing', 'name', 'category', 'price', 'features', 'finish'] as $reason): ?>
                <?php if (!empty($reasonSummary[$reason])): ?>
                    <span class="adm-reason"><?= e(trans('products.reason_' . $reason)) ?> · <?= (int) $reasonSummary[$reason] ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="adm-catbrowser" data-catbrowser aria-label="<?= e(trans('common.category')) ?>">
        <div class="adm-catcrumbs" data-catcrumbs></div>
        <div class="adm-catcols">
            <?php for ($level = 0; $level < 3; $level++): ?>
                <div class="adm-catcol" data-catcol="<?= $level ?>" hidden>
                    <input type="search" class="input input-sm adm-catsearch" data-catsearch
                           placeholder="<?= e(trans('common.search')) ?>…" aria-label="<?= e(trans('common.search')) ?>">
                    <div class="adm-catlist" data-catlist></div>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <div class="admin-toolbar adm-filters" data-grid-filters>
        <input class="input" type="search" data-filter="q" value="<?= e($gridState['q']) ?>"
               placeholder="<?= e(trans('products.search_placeholder')) ?>">
        <select class="input" data-filter="status" aria-label="<?= e(trans('common.status')) ?>">
            <option value=""><?= e(trans('products.filter_all_statuses')) ?></option>
            <?php foreach (ProductService::STATUSES as $statusOption): ?>
                <option value="<?= $statusOption ?>" <?= $gridState['status'] === $statusOption ? 'selected' : '' ?>><?= e(trans('common.' . $statusOption)) ?></option>
            <?php endforeach; ?>
            <option value="not_live" <?= $gridState['status'] === 'not_live' ? 'selected' : '' ?>><?= e(trans('products.filter_not_live')) ?></option>
        </select>
        <select class="input" data-filter="sort" aria-label="<?= e(trans('products.sort_label')) ?>">
            <option value="model" <?= $gridState['sort'] === 'model' ? 'selected' : '' ?>><?= e(trans('products.sort_model')) ?></option>
            <option value="name" <?= $gridState['sort'] === 'name' ? 'selected' : '' ?>><?= e(trans('products.sort_name')) ?></option>
            <option value="newest" <?= $gridState['sort'] === 'newest' ? 'selected' : '' ?>><?= e(trans('products.sort_newest')) ?></option>
            <option value="curated" <?= $gridState['sort'] === 'curated' ? 'selected' : '' ?>><?= e(trans('products.sort_curated')) ?></option>
        </select>
        <button type="button" class="adm-chip-toggle<?= $gridState['featured'] ? ' is-on' : '' ?>" data-filter-toggle="featured" aria-pressed="<?= $gridState['featured'] ? 'true' : 'false' ?>">★ <?= e(trans('common.featured')) ?></button>
        <button type="button" class="adm-chip-toggle<?= $gridState['is_new'] ? ' is-on' : '' ?>" data-filter-toggle="is_new" aria-pressed="<?= $gridState['is_new'] ? 'true' : 'false' ?>"><?= e(trans('common.new')) ?></button>
        <span class="adm-grid-total" data-grid-total><?= (int) $grid['total'] ?></span>
        <button type="button" class="btn btn-primary" data-add-product>+ <?= e(trans('common.create_product')) ?></button>
    </div>

    <p class="admin-hint"><?= e(trans('products.inline_hint')) ?></p>

    <div class="adm-cards" data-grid>
        <?= $view->render('products::Admin._grid', [
            'products' => $products,
            'payloads' => $payloads,
            'cards' => $cards,
            'mediaState' => $mediaState,
            'chips' => $chips,
            'finishSpecs' => $finishSpecs,
            'covers' => $covers,
            'members' => $members,
            'reasons' => $reasons,
            'categoryPaths' => $categoryPaths,
            'mode' => $mode,
            'locale' => $locale,
        ]) ?>
    </div>

    <nav class="adm-pager" data-grid-pager data-page="<?= (int) $grid['page'] ?>" data-pages="<?= (int) $grid['pages'] ?>">
        <button type="button" class="btn btn-sm btn-secondary" data-pager="prev">&larr;</button>
        <span data-pager-label><?= (int) $grid['page'] ?> / <?= (int) $grid['pages'] ?></span>
        <button type="button" class="btn btn-sm btn-secondary" data-pager="next">&rarr;</button>
    </nav>
</div>

<script type="application/json" id="category-tree-json"><?= json_encode($categoryTree, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script type="application/json" id="grid-state-json"><?= json_encode($gridState, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script type="application/json" id="feature-presets-json"><?=
    json_encode(array_reduce($presets, static function (array $carry, array $preset): array {
        $labels = [];
        $placeholders = [];
        foreach (($preset['translations'] ?? []) as $loc => $tr) {
            $labels[$loc] = (string) ($tr['label'] ?? '');
            $placeholders[$loc] = (string) ($tr['placeholder'] ?? '');
        }
        $carry[(int) $preset['id']] = ['icon' => (string) $preset['icon_key'], 'labels' => $labels, 'placeholders' => $placeholders];
        return $carry;
    }, []), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)
?></script>
<script type="application/json" id="feature-icons-json"><?=
    json_encode(array_reduce($icons, static function (array $carry, array $icon): array {
        $carry[$icon['key']] = ['label' => $icon['label'], 'svg' => $icon['svg']];
        return $carry;
    }, []), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)
?></script>
<script type="application/json" id="finish-catalog-json"><?= json_encode($finishCatalog ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
