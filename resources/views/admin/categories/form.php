<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<string, mixed>|null $category */
/** @var array<string, array<string, mixed>> $translations */
/** @var array<int, array<string, mixed>> $tree */
/** @var int $selectedParent */

$isEdit = $category !== null;
$action = $isEdit ? route('admin.categories.update', ['id' => (int) $category['id']]) : route('admin.categories.store');
$locales = $translator->locales();
$fallbackLocale = (string) config('localization.fallback', 'en');
$val = static fn (string $key, string $default = ''): string => (string) old($key, $default);

$currentSlug = (string) ($category['slug'] ?? '');
$slugParts = $currentSlug !== '' ? explode('.', $currentSlug) : [];
$currentSegment = $slugParts !== [] ? (string) end($slugParts) : '';
$seoData = [];
if ($isEdit && !empty($category['seo'])) {
    $decoded = json_decode((string) $category['seo'], true);
    $seoData = is_array($decoded) ? $decoded : [];
}

/* A category can never become its own ancestor: hide itself + descendants. */
$excluded = [];
if ($isEdit) {
    $selfId = (int) $category['id'];
    $childrenMap = [];
    foreach ($tree as $node) {
        if (!empty($node['parent_id'])) {
            $childrenMap[(int) $node['parent_id']][] = (int) $node['id'];
        }
    }
    $stack = [$selfId];
    while ($stack !== []) {
        $currentId = array_pop($stack);
        $excluded[$currentId] = true;
        foreach ($childrenMap[$currentId] ?? [] as $childId) {
            $stack[] = $childId;
        }
    }
}

$parentValue = (string) (int) old('parent_id', (string) $selectedParent);
$parentSlugPreview = '';
foreach ($tree as $node) {
    if ((string) (int) $node['id'] === $parentValue) {
        $parentSlugPreview = (string) $node['slug'];
        break;
    }
}
$segmentValue = $val('slug_segment', $currentSegment);
$slugPreview = $isEdit && $parentValue === (string) (int) ($category['parent_id'] ?? 0)
    ? $currentSlug
    : ($parentSlugPreview !== '' ? $parentSlugPreview . '.' : '') . ($segmentValue !== '' ? $segmentValue : '…');
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="stack admin-form" data-category-form>
    <?= csrf_field() ?>

    <div class="grid grid-2">
        <div class="field">
            <label class="field-label" for="parent_id"><?= e(trans('categories.parent')) ?></label>
            <select class="input" id="parent_id" name="parent_id" data-slug-parent>
                <option value="" data-slug=""><?= e(trans('categories.parent_root')) ?></option>
                <?php foreach ($tree as $node): ?>
                    <?php
                    $nodeId = (int) $node['id'];
                    if (isset($excluded[$nodeId])) {
                        continue;
                    }
                    $isDeepest = (int) $node['depth'] >= 3;
                    $holdsProducts = (int) $node['direct_products'] > 0;
                    $disabled = $isDeepest || ($holdsProducts && (string) $nodeId !== $parentValue);
                    $suffix = $isDeepest ? ' ' . trans('categories.level_max_suffix') : ($holdsProducts ? ' ' . trans('categories.has_products_suffix') : '');
                    ?>
                    <option value="<?= $nodeId ?>" data-slug="<?= e((string) $node['slug']) ?>" <?= $disabled ? 'disabled' : '' ?> <?= (string) $nodeId === $parentValue ? 'selected' : '' ?>><?= e((string) $node['path']) ?><?= e($suffix) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label class="field-label" for="slug_segment"><?= e(trans('categories.slug_segment')) ?></label>
            <input class="input" id="slug_segment" name="slug_segment" type="text" maxlength="80" value="<?= e($segmentValue) ?>" data-slug-segment>
            <p class="field-help">
                <?= e(trans('categories.slug_preview')) ?>: <code data-slug-preview><?= e($slugPreview) ?></code><br>
                <?= e(trans('categories.slug_segment_hint')) ?>
            </p>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="field">
            <label class="field-label" for="status"><?= e(trans('common.status')) ?></label>
            <select class="input" id="status" name="status">
                <?php foreach (['active', 'inactive'] as $option): ?>
                    <option value="<?= $option ?>" <?= $val('status', (string) ($category['status'] ?? 'active')) === $option ? 'selected' : '' ?>><?= e(trans('common.' . $option)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label class="field-label" for="is_featured"><?= e(trans('common.featured')) ?></label>
            <select class="input" id="is_featured" name="is_featured">
                <option value="1" <?= $val('is_featured', (string) (int) ($category['is_featured'] ?? 0)) === '1' ? 'selected' : '' ?>><?= e(trans('products.featured_yes')) ?></option>
                <option value="0" <?= $val('is_featured', (string) (int) ($category['is_featured'] ?? 0)) === '0' ? 'selected' : '' ?>><?= e(trans('products.featured_no')) ?></option>
            </select>
        </div>
        <?= $view->component('input', ['name' => 'sort_order', 'label' => trans('categories.sort_order'), 'type' => 'number', 'value' => $val('sort_order', (string) (int) ($category['sort_order'] ?? 0))]) ?>
        <?= $view->component('input', ['name' => 'icon', 'label' => trans('categories.icon'), 'value' => $val('icon', (string) ($category['icon'] ?? ''))]) ?>
    </div>

    <div class="grid grid-2">
        <div class="field">
            <label class="field-label" for="image"><?= e(trans('categories.image')) ?></label>
            <?php if (!empty($category['image'])): ?>
                <p><img src="<?= e(asset((string) $category['image'])) ?>" alt="" style="max-width: 180px; border-radius: 8px; border: 1px solid var(--ds-border, #e2e8f0); display: block;"></p>
            <?php endif; ?>
            <input class="input" id="image" name="image" type="file" accept="image/*">
            <p class="field-help"><?= e(trans('categories.image_upload_hint')) ?></p>
        </div>
        <?= $view->component('input', ['name' => 'image_path', 'label' => trans('categories.image_path'), 'value' => $val('image_path', (string) ($category['image'] ?? ''))]) ?>
        <?= $view->component('input', ['name' => 'seo_title', 'label' => trans('categories.seo_title'), 'value' => $val('seo_title', (string) ($seoData['title'] ?? ''))]) ?>
    </div>

    <h2><?= e(trans('common.translations')) ?></h2>
    <?php foreach ($locales as $locale): $tr = $translations[$locale] ?? []; ?>
        <fieldset class="translation-set">
            <legend><?= e(strtoupper($locale)) ?></legend>
            <?= $view->component('input', ['name' => 'name_' . $locale, 'label' => trans('common.name') . ' (' . strtoupper($locale) . ')', 'value' => $val('name_' . $locale, (string) ($tr['name'] ?? '')), 'required' => $locale === $fallbackLocale]) ?>
            <div class="field">
                <label class="field-label" for="description_<?= e($locale) ?>"><?= e(trans('categories.description')) ?> (<?= e(strtoupper($locale)) ?>)</label>
                <textarea class="input" id="description_<?= e($locale) ?>" name="description_<?= e($locale) ?>" rows="3"><?= e($val('description_' . $locale, (string) ($tr['description'] ?? ''))) ?></textarea>
            </div>
        </fieldset>
    <?php endforeach; ?>

    <div class="form-actions">
        <?= $view->component('button', ['label' => trans('common.save'), 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= $view->component('button', ['label' => trans('common.cancel'), 'variant' => 'ghost', 'href' => route('admin.categories.index')]) ?>
    </div>
</form>
