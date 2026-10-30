<?php
/** @var array<string, mixed> $node */
/** @var string $locale */

$id = (int) $node['id'];
$depth = max(1, (int) ($node['depth'] ?? 1));
$status = (string) ($node['status'] ?? 'active');
$isFeatured = !empty($node['is_featured']);
$image = trim((string) ($node['image'] ?? ''));
$deletable = (int) ($node['children_count'] ?? 0) === 0 && (int) ($node['direct_products'] ?? 0) === 0;
?>
<div class="cat-row is-depth-<?= $depth ?><?= $status !== 'active' ? ' is-inactive' : '' ?>"
     data-category-row="<?= $id ?>"
     data-id="<?= $id ?>"
     data-parent-id="<?= (int) ($node['parent_id'] ?? 0) ?>"
     data-depth="<?= $depth ?>"
     data-name="<?= e(mb_strtolower((string) $node['name'])) ?>"
     data-path="<?= e(mb_strtolower((string) $node['path'])) ?>"
     data-inline-url="<?= e(route('admin.categories.inline', ['id' => $id])) ?>"
     data-editor-url="<?= e(route('admin.categories.editor', ['id' => $id])) ?>"
     data-move-url="<?= e(route('admin.categories.move', ['id' => $id])) ?>"
     data-delete-url="<?= e(route('admin.categories.destroy', ['id' => $id])) ?>">
    <div class="cat-row-main">
        <span class="cat-row-indent" aria-hidden="true"></span>
        <?php if ($image !== ''): ?>
            <button type="button" class="cat-row-thumb" data-expand-editor title="<?= e(trans('categories.image')) ?>">
                <img src="<?= e(asset($image)) ?>" alt="" loading="lazy">
            </button>
        <?php else: ?>
            <button type="button" class="cat-row-thumb cat-row-thumb-empty" data-expand-editor title="<?= e(trans('categories.image')) ?>">🖼</button>
        <?php endif; ?>

        <div class="cat-row-name">
            <strong data-inline="name" data-locale="<?= e($locale) ?>" tabindex="0"><?= e((string) $node['name']) ?></strong>
            <span class="cat-row-slug">/<?= e((string) $node['slug']) ?></span>
        </div>

        <span class="cat-row-counts" title="<?= e(trans('common.products')) ?>">
            <?= (int) $node['direct_products'] ?> / <?= (int) $node['subtree_products'] ?>
            <?php if ((int) $node['children_count'] > 0): ?>
                <span class="badge badge-ghost"><?= (int) $node['children_count'] ?> ↳</span>
            <?php endif; ?>
        </span>

        <button type="button" class="adm-star adm-star-sm<?= $isFeatured ? ' is-on' : '' ?>" data-cat-featured
                title="<?= e(trans('common.featured')) ?>" aria-pressed="<?= $isFeatured ? 'true' : 'false' ?>">★</button>

        <select class="input input-sm cat-row-status" data-inline-select="status" aria-label="<?= e(trans('common.status')) ?>">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= e(trans('common.active')) ?></option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>><?= e(trans('common.inactive')) ?></option>
        </select>

        <span class="cat-row-sort" title="<?= e(trans('categories.sort_order')) ?>">
            <span data-inline="sort_order" data-input-type="number" tabindex="0"><?= (int) $node['sort_order'] ?></span>
        </span>

        <span class="cat-row-actions">
            <button type="button" class="btn btn-sm btn-ghost" data-move="up" title="<?= e(trans('products.row_move_up')) ?>">↑</button>
            <button type="button" class="btn btn-sm btn-ghost" data-move="down" title="<?= e(trans('products.row_move_down')) ?>">↓</button>
            <button type="button" class="btn btn-sm btn-secondary" data-quick-child title="<?= e(trans('categories.add_child')) ?>">+ ↳</button>
            <button type="button" class="btn btn-sm btn-secondary" data-expand-editor aria-expanded="false"><?= e(trans('common.edit')) ?> ▾</button>
            <?php if ($deletable): ?>
                <button type="button" class="btn btn-sm btn-danger" data-delete-category
                        data-confirm="<?= e(trans('common.confirm_delete')) ?>"><?= e(trans('common.delete')) ?></button>
            <?php endif; ?>
        </span>
    </div>
    <div class="cat-row-editor" data-editor hidden></div>
</div>
