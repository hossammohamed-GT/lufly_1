<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<int, array<string, mixed>> $collections */
?>
<div class="admin-toolbar">
    <span></span>
    <?= $view->component('button', ['label' => trans('common.create_collection'), 'variant' => 'primary', 'href' => route('admin.collections.create')]) ?>
</div>

<div class="product-table-wrap">
<table class="table">
    <thead>
        <tr>
            <th><?= e(trans('common.name')) ?></th>
            <th><?= e(trans('common.slug')) ?></th>
            <th><?= e(trans('common.products')) ?></th>
            <th><?= e(trans('common.status')) ?></th>
            <th><?= e(trans('categories.sort_order')) ?></th>
            <th><?= e(trans('common.actions')) ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($collections === []): ?>
            <tr><td colspan="6" class="cell-empty"><?= e(trans('categories.collections_empty')) ?></td></tr>
        <?php endif; ?>
        <?php foreach ($collections as $collection): ?>
            <tr>
                <td>
                    <div class="cat-cell">
                        <?php if (!empty($collection['image'])): ?>
                            <img src="<?= e(asset((string) $collection['image'])) ?>" alt="" loading="lazy" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid var(--ds-border, #e2e8f0);">
                        <?php endif; ?>
                        <strong><?= e((string) $collection['display_name']) ?></strong>
                    </div>
                </td>
                <td><code><?= e((string) $collection['slug']) ?></code></td>
                <td><?= (int) $collection['products_total'] ?></td>
                <td><span class="badge badge-<?= e((string) $collection['status']) ?>"><?= e(trans('common.' . (string) $collection['status'])) ?></span></td>
                <td><?= (int) $collection['sort_order'] ?></td>
                <td class="row-actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(route('admin.collections.edit', ['id' => (int) $collection['id']])) ?>"><?= e(trans('common.edit')) ?></a>
                    <form method="post" action="<?= e(route('admin.collections.destroy', ['id' => (int) $collection['id']])) ?>" class="inline-form" data-confirm="<?= e(trans('categories.collection_delete_confirm')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><?= e(trans('common.delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
