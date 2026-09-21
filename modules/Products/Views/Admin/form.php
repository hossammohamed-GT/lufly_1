<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var \Modules\Products\Models\Product|null $product */
/** @var array<string, array<string, mixed>> $translations */
$action = $product === null ? route('admin.products.store') : route('admin.products.update', ['id' => $product->id]);
$locales = $translator->locales();

/* default variant price (the simple price field edits the first variant) */
$price = '';
if ($product !== null) {
    $variants = $product->variants();
    $price = (string) ($variants[0]['price'] ?? '0');
}

$statusOptions = ['draft', 'active', 'hidden', 'discontinued', 'coming_soon'];
$status = $product->status ?? 'active';

$connection = \Modules\Products\Models\Product::query()->connection();
$categories = $connection->select("SELECT id, slug FROM categories WHERE deleted_at IS NULL AND status = 'active' ORDER BY sort_order ASC, id ASC");
$collections = $connection->select("SELECT id, slug FROM collections WHERE deleted_at IS NULL AND status = 'active' ORDER BY sort_order ASC, id ASC");
$brands = $connection->select("SELECT id, name FROM brands WHERE deleted_at IS NULL AND status = 'active' ORDER BY id ASC");

$categoryId = (string) ($product->category_id ?? '');
$collectionId = (string) ($product->collection_id ?? '');
$brandId = (string) ($product->brand_id ?? '');
$isFeatured = $product === null ? 0 : (int) $product->is_featured;
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="stack admin-form">
    <?= csrf_field() ?>

    <div class="field">
        <label class="field-label" for="image"><?= e(trans('common.image') ?? 'Image') ?></label>
        <?php if (!empty($primaryImage)): ?>
            <div class="product-thumb-box" style="margin-bottom: 8px;">
                <img src="<?= asset($primaryImage) ?>" alt="Product preview" class="product-form-preview" style="max-width: 120px; max-height: 120px; object-fit: contain; border-radius: 6px; border: 1px solid var(--ds-border, #e2e8f0); padding: 4px; background: var(--ds-surface, #fff);">
            </div>
        <?php endif; ?>
        <input class="input" type="file" id="image" name="image" accept="image/*">
        <span class="field-help" style="display: block; margin-top: 4px; font-size: 0.82rem; color: var(--ds-text-muted, #64748b);"><?= !empty($primaryImage) ? 'Upload a new image to replace current primary image' : 'Upload primary product image (JPG, PNG, WebP)' ?></span>
    </div>

    <div class="grid grid-2">
        <?= $view->component('input', ['name' => 'model_code', 'label' => trans('common.model_code'), 'value' => $product->model_code ?? '', 'required' => true]) ?>
        <?= $view->component('input', ['name' => 'slug', 'label' => trans('common.slug'), 'value' => $product->slug ?? '']) ?>
        <?= $view->component('input', ['name' => 'price', 'label' => trans('common.price'), 'type' => 'number', 'value' => $price !== '' ? $price : '0']) ?>
        <div class="field">
            <label class="field-label" for="status"><?= e(trans('common.status')) ?></label>
            <select class="input" id="status" name="status">
                <?php foreach ($statusOptions as $option): ?>
                    <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(trans('common.' . $option)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="field">
            <label class="field-label" for="category_id"><?= e(trans('common.category')) ?></label>
            <select class="input" id="category_id" name="category_id">
                <option value="">-</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (string) $category['id'] ? 'selected' : '' ?>><?= e((string) $category['slug']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label class="field-label" for="collection_id">Collection</label>
            <select class="input" id="collection_id" name="collection_id">
                <option value="">-</option>
                <?php foreach ($collections as $collection): ?>
                    <option value="<?= (int) $collection['id'] ?>" <?= $collectionId === (string) $collection['id'] ? 'selected' : '' ?>><?= e((string) $collection['slug']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="field">
            <label class="field-label" for="brand_id">Brand</label>
            <select class="input" id="brand_id" name="brand_id">
                <option value="">-</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= (int) $brand['id'] ?>" <?= $brandId === (string) $brand['id'] ? 'selected' : '' ?>><?= e((string) $brand['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label class="field-label" for="is_featured"><?= e(trans('common.status')) ?> / Featured</label>
            <select class="input" id="is_featured" name="is_featured">
                <option value="1" <?= $isFeatured === 1 ? 'selected' : '' ?>>Featured on home</option>
                <option value="0" <?= $isFeatured === 0 ? 'selected' : '' ?>>Standard</option>
            </select>
        </div>
    </div>

    <h2><?= e(trans('common.translations')) ?></h2>
    <?php foreach ($locales as $locale): $tr = $translations[$locale] ?? []; ?>
        <fieldset class="translation-set">
            <legend><?= e(strtoupper($locale)) ?></legend>
            <?= $view->component('input', ['name' => 'name_' . $locale, 'label' => trans('products.name') . ' (' . strtoupper($locale) . ')', 'value' => $tr['name'] ?? '', 'required' => $locale === 'en']) ?>
            <div class="field">
                <label class="field-label" for="short_description_<?= e($locale) ?>"><?= e(trans('products.description')) ?> - short (<?= e(strtoupper($locale)) ?>)</label>
                <input class="input" id="short_description_<?= e($locale) ?>" name="short_description_<?= e($locale) ?>" type="text" maxlength="500" value="<?= e((string) ($tr['short_description'] ?? '')) ?>">
            </div>
            <div class="field">
                <label class="field-label" for="description_<?= e($locale) ?>"><?= e(trans('products.description')) ?> (<?= e(strtoupper($locale)) ?>)</label>
                <textarea class="input" id="description_<?= e($locale) ?>" name="description_<?= e($locale) ?>" rows="3"><?= e($tr['description'] ?? '') ?></textarea>
            </div>
        </fieldset>
    <?php endforeach; ?>

    <h2>SEO (en)</h2>
    <?= $view->component('input', ['name' => 'meta_title', 'label' => 'Meta title', 'value' => '']) ?>
    <?= $view->component('input', ['name' => 'meta_description', 'label' => 'Meta description', 'value' => '']) ?>

    <div class="form-actions">
        <?= $view->component('button', ['label' => trans('common.save'), 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= $view->component('button', ['label' => trans('common.cancel'), 'variant' => 'ghost', 'href' => route('admin.products.index')]) ?>
    </div>
</form>
