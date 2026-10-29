<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<string, mixed>|null $brand */

$isEdit = $brand !== null;
$action = $isEdit ? route('admin.brands.update', ['id' => (int) $brand['id']]) : route('admin.brands.store');
$val = static fn (string $key, string $default = ''): string => (string) old($key, $default);
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="stack admin-form">
    <?= csrf_field() ?>

    <div class="grid grid-2">
        <?= $view->component('input', ['name' => 'name', 'label' => trans('common.name'), 'value' => $val('name', (string) ($brand['name'] ?? '')), 'required' => true]) ?>
        <?= $view->component('input', ['name' => 'slug', 'label' => trans('common.slug'), 'value' => $val('slug', (string) ($brand['slug'] ?? ''))]) ?>
        <div class="field">
            <label class="field-label" for="status"><?= e(trans('common.status')) ?></label>
            <select class="input" id="status" name="status">
                <?php foreach (['active', 'inactive'] as $option): ?>
                    <option value="<?= $option ?>" <?= $val('status', (string) ($brand['status'] ?? 'active')) === $option ? 'selected' : '' ?>><?= e(trans('common.' . $option)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?= $view->component('input', ['name' => 'country', 'label' => trans('categories.country'), 'value' => $val('country', (string) ($brand['country'] ?? ''))]) ?>
        <?= $view->component('input', ['name' => 'website', 'label' => trans('categories.website'), 'value' => $val('website', (string) ($brand['website'] ?? ''))]) ?>
    </div>

    <div class="grid grid-2">
        <div class="field">
            <label class="field-label" for="logo"><?= e(trans('categories.logo')) ?></label>
            <?php if (!empty($brand['logo'])): ?>
                <p><img src="<?= e(asset((string) $brand['logo'])) ?>" alt="" style="max-width: 180px; border-radius: 8px; border: 1px solid var(--ds-border, #e2e8f0); display: block; background: #fff;"></p>
            <?php endif; ?>
            <input class="input" id="logo" name="logo" type="file" accept="image/*">
            <p class="field-help"><?= e(trans('categories.image_upload_hint')) ?></p>
        </div>
        <?= $view->component('input', ['name' => 'logo_path', 'label' => trans('categories.logo_path'), 'value' => $val('logo_path', (string) ($brand['logo'] ?? ''))]) ?>
    </div>

    <div class="form-actions">
        <?= $view->component('button', ['label' => trans('common.save'), 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= $view->component('button', ['label' => trans('common.cancel'), 'variant' => 'ghost', 'href' => route('admin.brands.index')]) ?>
    </div>
</form>
