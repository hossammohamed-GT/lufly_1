<?php
/** @var array<string, mixed> $category */
/** @var array<string, array<string, mixed>> $translations */
/** @var array<int, array<string, mixed>> $tree */
/** @var string $seoTitle */

$id = (int) $category['id'];
$locales = $translator->locales();
$fallback = (string) config('localization.fallback', 'en');
$slugParts = explode('.', (string) ($category['slug'] ?? ''));
$slugSegment = (string) end($slugParts);
$parentId = (int) ($category['parent_id'] ?? 0);
$status = (string) ($category['status'] ?? 'active');
$image = trim((string) ($category['image'] ?? ''));

/* Exclude the category itself and its descendants from the parent picker by
   slug-path prefix: a descendant's slug starts with this category's slug. */
$ownSlug = (string) ($category['slug'] ?? '');
$parentOptions = array_filter($tree, static function (array $node) use ($id, $ownSlug): bool {
    if ((int) $node['id'] === $id) {
        return false;
    }
    return $ownSlug === '' || !str_starts_with((string) $node['slug'], $ownSlug . '.');
});
?>
<div class="card-editor" data-editor-root>
    <section class="editor-block">
        <h3 class="editor-block-title"><?= e(trans('categories.image')) ?></h3>
        <div class="cat-image-box" data-category-image-box data-image-url="<?= e(route('admin.categories.image', ['id' => $id])) ?>">
            <?php if ($image !== ''): ?>
                <img class="cat-image-preview" data-category-image-preview src="<?= e(asset($image)) ?>" alt="">
            <?php else: ?>
                <span class="cat-image-preview cat-image-empty" data-category-image-preview><?= e(trans('products.no_image')) ?></span>
            <?php endif; ?>
            <p class="field-help"><?= e(trans('categories.image_hint')) ?></p>
            <div class="form-actions">
                <button type="button" class="btn btn-sm btn-secondary" data-category-image-pick><?= e($image !== '' ? trans('products.media_replace') : trans('categories.image_add')) ?></button>
                <?php if ($image !== ''): ?>
                    <button type="button" class="btn btn-sm btn-danger" data-category-image-delete
                            data-confirm="<?= e(trans('common.confirm_delete')) ?>"><?= e(trans('products.media_remove')) ?></button>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <form class="editor-block" data-section-form="details" method="post"
          action="<?= e(route('admin.categories.section', ['id' => $id])) ?>">
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
            <div class="field">
                <label class="field-label"><?= e(trans('categories.parent')) ?></label>
                <button type="button" class="input adm-catbtn" data-cat-picker data-category-id="<?= $parentId ?>" data-picker-field="parent_id"
                        title="<?= e(trans('products.choose_category')) ?>">
                    <span data-cat-label><?= e($parentId > 0 ? trans('categories.parent_current') . ': ' . $parentId : trans('categories.no_parent')) ?></span>
                    <span class="adm-catbtn-caret">▾</span>
                </button>
                <input type="hidden" name="parent_id" value="<?= $parentId ?>">
                <noscript>
                    <select class="input" name="parent_id">
                        <option value=""><?= e(trans('categories.no_parent')) ?></option>
                        <?php foreach ($parentOptions as $node): ?>
                            <option value="<?= (int) $node['id'] ?>" <?= $parentId === (int) $node['id'] ? 'selected' : '' ?>><?= e((string) $node['path']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </noscript>
            </div>
            <label class="field"><span class="field-label"><?= e(trans('categories.slug_segment')) ?></span>
                <input class="input" type="text" maxlength="80" name="slug_segment" value="<?= e($slugSegment) ?>">
                <span class="field-help"><?= e(trans('categories.slug_segment_hint')) ?></span></label>
            <div class="field">
                <label class="field-label"><?= e(trans('common.status')) ?></label>
                <select class="input" name="status">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= e(trans('common.active')) ?></option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>><?= e(trans('common.inactive')) ?></option>
                </select>
            </div>
            <label class="field"><span class="field-label"><?= e(trans('categories.sort_order')) ?></span>
                <input class="input" type="number" step="1" min="0" name="sort_order" value="<?= (int) ($category['sort_order'] ?? 0) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('categories.icon')) ?></span>
                <input class="input" type="text" maxlength="255" name="icon" value="<?= e((string) ($category['icon'] ?? '')) ?>"></label>
            <div class="field">
                <label class="field-label"><?= e(trans('common.featured')) ?></label>
                <label class="adm-check"><input type="checkbox" name="is_featured" value="1" <?= !empty($category['is_featured']) ? 'checked' : '' ?>> <?= e(trans('categories.featured_in_menu')) ?></label>
            </div>
        </div>

        <label class="field"><span class="field-label"><?= e(trans('products.seo_meta_title')) ?></span>
            <input class="input" type="text" maxlength="255" name="seo_title" value="<?= e($seoTitle) ?>"></label>

        <div class="editor-save-row">
            <button type="submit" class="btn btn-sm btn-primary"><?= e(trans('common.save')) ?></button>
            <span class="editor-saved" data-saved-flag hidden>✓ <?= e(trans('common.saved')) ?></span>
        </div>
    </form>
</div>
