<?php
/** @var array<string, mixed> $brand */
/** @var string $locale */

$id = (int) $brand['id'];
$status = (string) ($brand['status'] ?? 'active');
$logo = trim((string) ($brand['logo'] ?? ''));
$inUse = (int) ($brand['products_total'] ?? 0) > 0;
?>
<div class="cat-row<?= $status !== 'active' ? ' is-inactive' : '' ?>"
     data-generic-row="<?= $id ?>"
     data-id="<?= $id ?>"
     data-inline-url="<?= e(route('admin.brands.inline', ['id' => $id])) ?>"
     data-editor-url="<?= e(route('admin.brands.editor', ['id' => $id])) ?>"
     data-delete-url="<?= e(route('admin.brands.destroy', ['id' => $id])) ?>">
    <div class="cat-row-main">
        <?php if ($logo !== ''): ?>
            <button type="button" class="cat-row-thumb" data-expand-editor title="<?= e(trans('categories.brand_logo')) ?>">
                <img src="<?= e(asset($logo)) ?>" alt="" loading="lazy">
            </button>
        <?php else: ?>
            <button type="button" class="cat-row-thumb cat-row-thumb-empty" data-expand-editor title="<?= e(trans('categories.brand_logo')) ?>">🖼</button>
        <?php endif; ?>

        <div class="cat-row-name">
            <strong data-inline="name" tabindex="0"><?= e((string) $brand['name']) ?></strong>
            <span class="cat-row-slug">/<?= e((string) $brand['slug']) ?></span>
        </div>

        <span class="cat-row-counts" title="<?= e(trans('common.products')) ?>"><?= (int) $brand['products_total'] ?></span>

        <span class="cat-row-meta" title="<?= e(trans('categories.brand_country')) ?>">
            <span data-inline="country" tabindex="0"><?= e((string) ($brand['country'] ?? '')) ?></span>
        </span>

        <span class="cat-row-meta" title="<?= e(trans('categories.brand_website')) ?>">
            <span data-inline="website" tabindex="0"><?= e((string) ($brand['website'] ?? '')) ?></span>
        </span>

        <select class="input input-sm cat-row-status" data-inline-select="status" aria-label="<?= e(trans('common.status')) ?>">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= e(trans('common.active')) ?></option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>><?= e(trans('common.inactive')) ?></option>
        </select>

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
