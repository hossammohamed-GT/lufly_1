<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<string, int> $stats */
$cards = [
    ['label' => trans('common.products'), 'value' => $stats['products'] ?? 0, 'href' => route('admin.products.index')],
    ['label' => trans('common.featured'), 'value' => $stats['featured'] ?? 0, 'href' => route('admin.products.index', ['featured' => 1])],
    ['label' => trans('products.hidden_media_incomplete'), 'value' => $stats['media_incomplete'] ?? 0, 'href' => route('admin.products.index')],
    ['label' => trans('common.categories'), 'value' => $stats['categories'] ?? 0, 'href' => route('admin.categories.index')],
    ['label' => trans('products.category_leaf'), 'value' => $stats['leaf_categories'] ?? 0, 'href' => route('admin.categories.index')],
    ['label' => trans('common.collections'), 'value' => $stats['collections'] ?? 0, 'href' => route('admin.collections.index')],
    ['label' => trans('common.brands'), 'value' => $stats['brands'] ?? 0, 'href' => route('admin.brands.index')],
];
?>
<?php if (!empty($missingAdminAccess)): ?>
<div class="access-warning" role="alert">
    <strong><?= e(trans('common.no_admin_permissions_title')) ?></strong>
    <p><?= e(trans('common.no_admin_permissions_body')) ?></p>
</div>
<?php endif; ?>
<div class="grid grid-4 stat-grid">
    <?php foreach ($cards as $card): ?>
        <a class="card stat-card" href="<?= e($card['href']) ?>">
            <div class="card-body">
                <p class="stat-value"><?= (int) $card['value'] ?></p>
                <p class="stat-label"><?= e($card['label']) ?></p>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<div class="dash-links">
    <?= $view->component('button', ['label' => trans('common.create_product'), 'variant' => 'primary', 'href' => route('admin.products.index')]) ?>
    <?= $view->component('button', ['label' => trans('common.products'), 'variant' => 'secondary', 'href' => route('admin.products.index')]) ?>
    <?= $view->component('button', ['label' => trans('common.create_category'), 'variant' => 'secondary', 'href' => route('admin.categories.index')]) ?>
    <?= $view->component('button', ['label' => trans('common.categories'), 'variant' => 'ghost', 'href' => route('admin.categories.index')]) ?>
    <?= $view->component('button', ['label' => trans('common.collections'), 'variant' => 'ghost', 'href' => route('admin.collections.index')]) ?>
    <?= $view->component('button', ['label' => trans('common.brands'), 'variant' => 'ghost', 'href' => route('admin.brands.index')]) ?>
</div>
