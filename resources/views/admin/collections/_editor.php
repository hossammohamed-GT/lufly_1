<?php
/** @var array<string, mixed> $collection */
/** @var array<string, array<string, mixed>> $translations */

$id = (int) $collection['id'];
$locales = $translator->locales();
$fallback = (string) config('localization.fallback', 'en');
$status = (string) ($collection['status'] ?? 'active');
$image = trim((string) ($collection['image'] ?? ''));
?>
<div class="card-editor" data-editor-root>
    <section class="editor-block">
        <h3 class="editor-block-title"><?= e(trans('categories.image')) ?></h3>
        <div class="cat-image-box" data-generic-image-box data-image-url="<?= e(route('admin.collections.image', ['id' => $id])) ?>">
            <?php if ($image !== ''): ?>
                <img class="cat-image-preview" data-generic-image-preview src="<?= e(asset($image)) ?>" alt="">
            <?php else: ?>
                <span class="cat-image-preview cat-image-empty" data-generic-image-preview><?= e(trans('products.no_image')) ?></span>
            <?php endif; ?>
            <p class="field-help"><?= e(trans('categories.image_hint')) ?></p>
            <div class="form-actions">
                <button type="button" class="btn btn-sm btn-secondary" data-generic-image-pick><?= e($image !== '' ? trans('products.media_replace') : trans('categories.image_add')) ?></button>
                <?php if ($image !== ''): ?>
                    <button type="button" class="btn btn-sm btn-danger" data-generic-image-delete
                            data-confirm="<?= e(trans('common.confirm_delete')) ?>"><?= e(trans('products.media_remove')) ?></button>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <form class="editor-block" data-section-form="details" method="post"
          action="<?= e(route('admin.collections.section', ['id' => $id])) ?>">
        <?= csrf_field() ?>
        <h3 class="editor-block-title"><?= e(trans('categories.details')) ?></h3>

        <?php foreach ($locales as $locale): $tr = $translations[$locale] ?? []; ?>
            <fieldset class="translation-set">
                <legend><?= e(strtoupper($locale)) ?></legend>
                <label class="field"><span class="field-label"><?= e(trans('products.name')) ?> (<?= e(strtoupper($locale)) ?>)</span>
                    <input class="input" type="text" maxlength="255" name="name_<?= e($locale) ?>" value="<?= e((string) ($tr['name'] ?? '')) ?>"<?= $locale === $fallback ? ' required' : '' ?>></label>
                <label class="field"><span class="field-label"><?= e(trans('products.description')) ?> (<?= e(strtoupper($locale)) ?>)</span>
                    <textarea class="input" rows="2" name="description_<?= e($locale) ?>"><?= e((string) ($tr['description'] ?? '')) ?></textarea></label>
            </fieldset>
        <?php endforeach; ?>

        <div class="grid grid-2">
            <label class="field"><span class="field-label">Slug</span>
                <input class="input" type="text" maxlength="255" name="slug" value="<?= e((string) ($collection['slug'] ?? '')) ?>"></label>
            <div class="field">
                <label class="field-label"><?= e(trans('common.status')) ?></label>
                <select class="input" name="status">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= e(trans('common.active')) ?></option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>><?= e(trans('common.inactive')) ?></option>
                </select>
            </div>
            <label class="field"><span class="field-label"><?= e(trans('categories.sort_order')) ?></span>
                <input class="input" type="number" step="1" min="0" name="sort_order" value="<?= (int) ($collection['sort_order'] ?? 0) ?>"></label>
        </div>

        <div class="editor-save-row">
            <button type="submit" class="btn btn-sm btn-primary"><?= e(trans('common.save')) ?></button>
            <span class="editor-saved" data-saved-flag hidden>✓ <?= e(trans('common.saved')) ?></span>
        </div>
    </form>
</div>
