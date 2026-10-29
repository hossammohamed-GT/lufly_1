<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<int, array<string, mixed>> $tree */
?>
<div class="admin-toolbar">
    <p class="admin-hint"><?= e(trans('categories.slug_segment_hint')) ?></p>
    <?= $view->component('button', ['label' => trans('common.create_category'), 'variant' => 'primary', 'href' => route('admin.categories.create')]) ?>
</div>

<div class="product-table-wrap">
<table class="table">
    <thead>
        <tr>
            <th><?= e(trans('common.category')) ?></th>
            <th><?= e(trans('categories.slug_preview')) ?></th>
            <th><?= e(trans('common.products')) ?></th>
            <th><?= e(trans('common.status')) ?></th>
            <th><?= e(trans('categories.sort_order')) ?></th>
            <th><?= e(trans('common.actions')) ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($tree === []): ?>
            <tr><td colspan="6" class="cell-empty"><?= e(trans('categories.empty')) ?></td></tr>
        <?php endif; ?>
        <?php foreach ($tree as $node): ?>
            <?php
            $id = (int) $node['id'];
            $depth = (int) $node['depth'];
            $direct = (int) $node['direct_products'];
            $subtree = (int) $node['subtree_products'];
            $childrenCount = (int) $node['children_count'];
            $canAddChild = $depth < 3 && $direct === 0;
            $canDelete = $childrenCount === 0 && $direct === 0;
            $isActive = (string) $node['status'] === 'active';
            ?>
            <tr>
                <td>
                    <div class="cat-cell" style="padding-inline-start: <?= ($depth - 1) * 26 ?>px">
                        <?php if (!empty($node['image'])): ?>
                            <img src="<?= e(asset((string) $node['image'])) ?>" alt="" loading="lazy" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid var(--ds-border, #e2e8f0);">
                        <?php else: ?>
                            <span style="width: 40px; height: 40px; border-radius: 6px; border: 1px dashed var(--ds-border, #e2e8f0); display: inline-flex; align-items: center; justify-content: center; font-size: 10px; color: var(--ds-text-muted, #94a3b8);">—</span>
                        <?php endif; ?>
                        <span>
                            <strong><?= e((string) $node['name']) ?></strong>
                            <?php if (!empty($node['is_featured'])): ?><span title="<?= e(trans('common.featured')) ?>">★</span><?php endif; ?>
                        </span>
                    </div>
                </td>
                <td><code><?= e((string) $node['slug']) ?></code></td>
                <td>
                    <?= $direct ?> <span class="stat-label"><?= e(trans('categories.products_direct')) ?></span>
                    <?php if ($subtree !== $direct): ?>
                        <br><?= $subtree ?> <span class="stat-label"><?= e(trans('categories.products_subtree')) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="product-visibility">
                        <span class="badge badge-<?= $isActive ? 'active' : 'inactive' ?>"><?= e(trans('common.' . ($isActive ? 'active' : 'inactive'))) ?></span>
                        <form method="post" action="<?= e(route('admin.categories.status', ['id' => $id])) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-secondary"><?= e(trans($isActive ? 'categories.deactivate' : 'categories.activate')) ?></button>
                        </form>
                    </div>
                </td>
                <td>
                    <div class="cat-order">
                        <form method="post" action="<?= e(route('admin.categories.move', ['id' => $id])) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="direction" value="up">
                            <button type="submit" class="btn btn-sm btn-ghost" title="<?= e(trans('categories.move_up')) ?>">&uarr;</button>
                        </form>
                        <form method="post" action="<?= e(route('admin.categories.move', ['id' => $id])) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="direction" value="down">
                            <button type="submit" class="btn btn-sm btn-ghost" title="<?= e(trans('categories.move_down')) ?>">&darr;</button>
                        </form>
                    </div>
                </td>
                <td class="row-actions">
                    <?php if ($canAddChild): ?>
                        <a class="btn btn-sm btn-ghost" href="<?= e(route('admin.categories.create', ['parent' => $id])) ?>"><?= e(trans('categories.add_child')) ?></a>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-secondary" href="<?= e(route('admin.categories.edit', ['id' => $id])) ?>"><?= e(trans('common.edit')) ?></a>
                    <?php if ($canDelete): ?>
                        <form method="post" action="<?= e(route('admin.categories.destroy', ['id' => $id])) ?>" class="inline-form" data-confirm="<?= e(trans('categories.delete_confirm')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-danger"><?= e(trans('common.delete')) ?></button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-danger" disabled title="<?= e(trans($childrenCount > 0 ? 'categories.delete_blocked_children' : 'categories.delete_blocked_products')) ?>"><?= e(trans('common.delete')) ?></button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
