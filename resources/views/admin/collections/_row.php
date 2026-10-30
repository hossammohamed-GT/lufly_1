<?php
/** @var array<string, mixed> $collection */
/** @var string $locale */

$id = (int) $collection['id'];
$status = (string) ($collection['status'] ?? 'active');
$image = trim((string) ($collection['image'] ?? ''));
$inUse = (int) ($collection['products_total'] ?? 0) > 0;
?>
<div class="cat-row<?= $status !== 'active' ? ' is-inactive' : '' ?>"
     data-generic-row="<?= $id ?>"
     data-id="<?= $id ?>"
     data-inline-url="<?= e(route('admin.collections.inline', ['id' => $id])) ?>"
     data-editor-url="<?= e(route('admin.collections.editor', ['id' => $id])) ?>"
     data-delete-url="<?= e(route('admin.collections.destroy', ['id' => $id])) ?>">
    <div class="cat-row-main">
        <?php if ($image !== ''): ?>
            <button type="button" class="cat-row-thumb" data-expand-editor title="<?= e(trans('categories.image')) ?>">
                <img src="<?= e(asset($image)) ?>" alt="" loading="lazy">
            </button>
        <?php else: ?>
            <button type="button" class="cat-row-thumb cat-row-thumb-empty" data-expand-editor title="<?= e(trans('categories.image')) ?>">🖼</button>
        <?php endif; ?>

        <div class="cat-row-name">
            <strong data-inline="name" data-locale="<?= e($locale) ?>" tabindex="0"><?= e((string) $collection['display_name']) ?></strong>
            <span class="cat-row-slug">/<?= e((string) $collection['slug']) ?></span>
        </div>

        <span class="cat-row-counts" title="<?= e(trans('common.products')) ?>"><?= (int) $collection['products_total'] ?></span>

        <select class="input input-sm cat-row-status" data-inline-select="status" aria-label="<?= e(trans('common.status')) ?>">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= e(trans('common.active')) ?></option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>><?= e(trans('common.inactive')) ?></option>
        </select>

        <span class="cat-row-sort" title="<?= e(trans('categories.sort_order')) ?>">
            <span data-inline="sort_order" data-input-type="number" tabindex="0"><?= (int) $collection['sort_order'] ?></span>
        </span>

        <span class="cat-row-actions">
            <button type="button" class="btn btn-sm btn-secondary" data-expand-editor aria-expanded="false"><?= e(trans('common.edit')) ?> ▾</button>
            <?php if (!$inUse): ?>
                <button type="button" class="btn btn-sm btn-danger" data-delete-row
                        data-confirm="<?= e(trans('common.confirm_delete')) ?>"><?= e(trans('common.delete')) ?></button>
            <?php endif; ?>
        </span>
    </div>
    <div class="cat-row-editor" data-editor hidden></div>
</div>
