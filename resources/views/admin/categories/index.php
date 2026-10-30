<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<int, array<string, mixed>> $tree */
/** @var string $locale */

/* Nested tree for the shared drill-down parent picker. */
$buildTree = static function (array $nodes): array {
    $childrenOf = [];
    foreach ($nodes as $node) {
        $parentId = (int) ($node['parent_id'] ?? 0);
        $childrenOf[$parentId][] = $node;
    }
    $build = static function (int $parentId) use (&$build, $childrenOf): array {
        $out = [];
        foreach ($childrenOf[$parentId] ?? [] as $node) {
            $out[] = [
                'id' => (int) $node['id'],
                'name' => (string) $node['name'],
                'path' => (string) $node['path'],
                'status' => (string) $node['status'],
                'is_leaf' => (int) $node['children_count'] === 0,
                'children' => $build((int) $node['id']),
            ];
        }
        return $out;
    };
    return $build(0);
};
?>
<div data-categories-page
     data-quick-url="<?= e(route('admin.categories.quick')) ?>"
     data-tree-url="<?= e(route('admin.categories.tree')) ?>">

    <div class="admin-toolbar adm-filters">
        <input class="input" type="search" data-category-search
               placeholder="<?= e(trans('common.search')) ?>…" aria-label="<?= e(trans('common.search')) ?>">
        <span class="adm-quick-add">
            <input class="input" type="text" data-quick-name maxlength="255"
                   placeholder="<?= e(trans('categories.quick_name_placeholder')) ?>"
                   aria-label="<?= e(trans('categories.quick_name_placeholder')) ?>">
            <button type="button" class="btn btn-primary" data-quick-add-root>+ <?= e(trans('common.create_category')) ?></button>
        </span>
    </div>

    <p class="admin-hint"><?= e(trans('categories.inline_hint')) ?></p>

    <div class="cat-tree" data-category-tree>
        <?php if ($tree === []): ?>
            <p class="media-empty"><?= e(trans('common.no_results')) ?></p>
        <?php endif; ?>
        <?php foreach ($tree as $node): ?>
            <?= $view->render('admin.categories._row', ['node' => $node, 'locale' => $locale]) ?>
        <?php endforeach; ?>
    </div>
</div>

<script type="application/json" id="category-tree-json"><?= json_encode($buildTree($tree), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
