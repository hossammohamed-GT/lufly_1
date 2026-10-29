<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<int, array<string, mixed>> $brands */
?>
<div class="admin-toolbar">
    <span></span>
    <?= $view->component('button', ['label' => trans('common.create_brand'), 'variant' => 'primary', 'href' => route('admin.brands.create')]) ?>
</div>

<div class="product-table-wrap">
<table class="table">
    <thead>
        <tr>
            <th><?= e(trans('common.name')) ?></th>
            <th><?= e(trans('common.slug')) ?></th>
            <th><?= e(trans('categories.country')) ?></th>
            <th><?= e(trans('common.products')) ?></th>
            <th><?= e(trans('common.status')) ?></th>
            <th><?= e(trans('common.actions')) ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($brands === []): ?>
            <tr><td colspan="6" class="cell-empty"><?= e(trans('categories.brands_empty')) ?></td></tr>
        <?php endif; ?>
        <?php foreach ($brands as $brand): ?>
            <tr>
                <td>
                    <div class="cat-cell">
                        <?php if (!empty($brand['logo'])): ?>
                            <img src="<?= e(asset((string) $brand['logo'])) ?>" alt="" loading="lazy" style="width: 40px; height: 40px; object-fit: contain; border-radius: 6px; border: 1px solid var(--ds-border, #e2e8f0); background: #fff;">
                        <?php endif; ?>
                        <strong><?= e((string) $brand['name']) ?></strong>
                    </div>
                </td>
                <td><code><?= e((string) $brand['slug']) ?></code></td>
                <td><?= e((string) ($brand['country'] ?? '')) ?></td>
                <td><?= (int) $brand['products_total'] ?></td>
                <td><span class="badge badge-<?= e((string) $brand['status']) ?>"><?= e(trans('common.' . (string) $brand['status'])) ?></span></td>
                <td class="row-actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(route('admin.brands.edit', ['id' => (int) $brand['id']])) ?>"><?= e(trans('common.edit')) ?></a>
                    <form method="post" action="<?= e(route('admin.brands.destroy', ['id' => (int) $brand['id']])) ?>" class="inline-form" data-confirm="<?= e(trans('categories.brand_delete_confirm')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><?= e(trans('common.delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
