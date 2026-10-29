<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<string, mixed>|null $collection */
/** @var array<string, array<string, mixed>> $translations */

$isEdit = $collection !== null;
$action = $isEdit ? route('admin.collections.update', ['id' => (int) $collection['id']]) : route('admin.collections.store');
$locales = $translator->locales();
$fallbackLocale = (string) config('localization.fallback', 'en');
$val = static fn (string $key, string $default = ''): string => (string) old($key, $default);
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="stack admin-form">
    <?= csrf_field() ?>

    <div class="grid grid-2">
        <?= $view->component('input', ['name' => 'slug', 'label' => trans('common.slug'), 'value' => $val('slug', (string) ($collection['slug'] ?? ''))]) ?>
        <div class="field">
            <label class="field-label" for="status"><?= e(trans('common.status')) ?></label>
            <select class="input" id="status" name="status">
                <?php foreach (['active', 'inactive'] as $option): ?>
                    <option value="<?= $option ?>" <?= $val('status', (string) ($collection['status'] ?? 'active')) === $option ? 'selected' : '' ?>><?= e(trans('common.' . $option)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?= $view->component('input', ['name' => 'sort_order', 'label' => trans('categories.sort_order'), 'type' => 'number', 'value' => $val('sort_order', (string) (int) ($collection['sort_order'] ?? 0))]) ?>
    </div>

    <div class="grid grid-2">
        <div class="field">
            <label class="field-label" for="image"><?= e(trans('categories.image')) ?></label>
            <?php if (!empty($collection['image'])): ?>
                <p><img src="<?= e(asset((string) $collection['image'])) ?>" alt="" style="max-width: 180px; border-radius: 8px; border: 1px solid var(--ds-border, #e2e8f0); display: block;"></p>
            <?php endif; ?>
            <input class="input" id="image" name="image" type="file" accept="image/*">
            <p class="field-help"><?= e(trans('categories.image_upload_hint')) ?></p>
        </div>
        <?= $view->component('input', ['name' => 'image_path', 'label' => trans('categories.image_path'), 'value' => $val('image_path', (string) ($collection['image'] ?? ''))]) ?>
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
        <?= $view->component('button', ['label' => trans('common.cancel'), 'variant' => 'ghost', 'href' => route('admin.collections.index')]) ?>
    </div>
</form>
