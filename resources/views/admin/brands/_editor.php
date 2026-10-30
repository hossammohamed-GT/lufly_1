<?php
/** @var array<string, mixed> $brand */

$id = (int) $brand['id'];
$status = (string) ($brand['status'] ?? 'active');
$logo = trim((string) ($brand['logo'] ?? ''));
?>
<div class="card-editor" data-editor-root>
    <section class="editor-block">
        <h3 class="editor-block-title"><?= e(trans('categories.brand_logo')) ?></h3>
        <div class="cat-image-box" data-generic-image-box data-image-url="<?= e(route('admin.brands.image', ['id' => $id])) ?>">
            <?php if ($logo !== ''): ?>
                <img class="cat-image-preview" data-generic-image-preview src="<?= e(asset($logo)) ?>" alt="">
            <?php else: ?>
                <span class="cat-image-preview cat-image-empty" data-generic-image-preview><?= e(trans('products.no_image')) ?></span>
            <?php endif; ?>
            <p class="field-help"><?= e(trans('categories.image_hint')) ?></p>
            <div class="form-actions">
                <button type="button" class="btn btn-sm btn-secondary" data-generic-image-pick><?= e($logo !== '' ? trans('products.media_replace') : trans('categories.image_add')) ?></button>
                <?php if ($logo !== ''): ?>
                    <button type="button" class="btn btn-sm btn-danger" data-generic-image-delete
                            data-confirm="<?= e(trans('common.confirm_delete')) ?>"><?= e(trans('products.media_remove')) ?></button>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <form class="editor-block" data-section-form="details" method="post"
          action="<?= e(route('admin.brands.section', ['id' => $id])) ?>">
        <?= csrf_field() ?>
        <h3 class="editor-block-title"><?= e(trans('categories.details')) ?></h3>

        <div class="grid grid-2">
            <label class="field"><span class="field-label"><?= e(trans('products.name')) ?></span>
                <input class="input" type="text" maxlength="100" name="name" value="<?= e((string) ($brand['name'] ?? '')) ?>" required></label>
            <label class="field"><span class="field-label">Slug</span>
                <input class="input" type="text" maxlength="255" name="slug" value="<?= e((string) ($brand['slug'] ?? '')) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('categories.brand_country')) ?></span>
                <input class="input" type="text" maxlength="100" name="country" value="<?= e((string) ($brand['country'] ?? '')) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('categories.brand_website')) ?></span>
                <input class="input" type="text" maxlength="255" name="website" value="<?= e((string) ($brand['website'] ?? '')) ?>"></label>
            <div class="field">
                <label class="field-label"><?= e(trans('common.status')) ?></label>
                <select class="input" name="status">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= e(trans('common.active')) ?></option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>><?= e(trans('common.inactive')) ?></option>
                </select>
            </div>
        </div>

        <div class="editor-save-row">
            <button type="submit" class="btn btn-sm btn-primary"><?= e(trans('common.save')) ?></button>
            <span class="editor-saved" data-saved-flag hidden>✓ <?= e(trans('common.saved')) ?></span>
        </div>
    </form>
</div>
