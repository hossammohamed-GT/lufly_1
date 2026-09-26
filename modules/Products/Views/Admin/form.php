<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var \Modules\Products\Models\Product|null $product */
/** @var array<string, array<string, mixed>> $translations */
/** @var array<string, array<int, array<string, mixed>>> $mediaSections */
$action = $product === null ? route('admin.products.store') : route('admin.products.update', ['id' => $product->id]);
$locales = $translator->locales();
$fallbackLocale = (string) config('localization.fallback', 'en');

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

/* ------------------------------------------------------------------ images
   Three independent sections, exactly as the product page shows them:
   photos (main gallery), technical drawings, installed shots. */
$mediaSections = $mediaSections ?? [];
$imageSections = [
    'photos' => [
        'label' => trans('products.section_photos'),
        'hint' => trans('products.section_photos_hint'),
        'badge' => trans('products.tab_photos'),
    ],
    'drawings' => [
        'label' => trans('products.section_drawings'),
        'hint' => trans('products.section_drawings_hint'),
        'badge' => trans('products.tab_drawings'),
    ],
    'situ' => [
        'label' => trans('products.section_situ'),
        'hint' => trans('products.section_situ_hint'),
        'badge' => trans('products.tab_situ'),
    ],
];
$productId = $product === null ? 0 : (int) $product->id;
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="stack admin-form">
    <?= csrf_field() ?>

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
            <?= $view->component('input', ['name' => 'name_' . $locale, 'label' => trans('products.name') . ' (' . strtoupper($locale) . ')', 'value' => $tr['name'] ?? '', 'required' => $locale === $fallbackLocale]) ?>
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

<h2 class="media-manager-title"><?= e(trans('products.images_title')) ?></h2>

<?php if ($product === null): ?>
    <p class="field-help"><?= e(trans('products.images_after_create')) ?></p>
<?php else: ?>
    <p class="field-help"><?= e(trans('products.images_intro')) ?></p>

    <div class="media-manager">
        <?php foreach ($imageSections as $sectionKey => $sectionMeta): ?>
            <?php $items = $mediaSections[$sectionKey] ?? []; ?>
            <section class="media-section" id="media-<?= e($sectionKey) ?>">
                <header class="media-section-head">
                    <h3>
                        <?= e($sectionMeta['label']) ?>
                        <span class="badge badge-ghost"><?= count($items) ?></span>
                    </h3>
                    <p class="field-help"><?= e($sectionMeta['hint']) ?></p>
                </header>

                <?php if ($items === []): ?>
                    <p class="media-empty"><?= e(trans('products.media_empty')) ?></p>
                <?php else: ?>
                    <div class="media-grid">
                        <?php foreach ($items as $index => $item): ?>
                            <?php
                            $attachmentId = (int) ($item['attachment_id'] ?? 0);
                            $isPrimary = (int) ($item['is_primary'] ?? 0) === 1 && $sectionKey === 'photos';
                            $isMissing = (string) ($item['status'] ?? '') === 'missing';
                            $fileName = (string) ($item['filename'] ?? $item['original_name'] ?? '');
                            $moveTargets = array_diff(array_keys($imageSections), [$sectionKey]);
                            ?>
                            <figure class="media-card<?= $isPrimary ? ' is-primary' : '' ?><?= $isMissing ? ' is-missing' : '' ?>">
                                <div class="media-card-media">
                                    <a href="<?= e(asset((string) $item['path'])) ?>">
                                        <img src="<?= e(asset((string) $item['path'])) ?>" alt="<?= e((string) ($item['original_name'] ?? '')) ?>" loading="lazy" decoding="async"
                                             onerror="this.onerror=null; this.closest('.media-card').classList.add('is-missing');">
                                    </a>
                                    <?php if ($isPrimary): ?>
                                        <span class="media-card-flag"><?= e(trans('products.media_main')) ?></span>
                                    <?php endif; ?>
                                    <?php if ($isMissing): ?>
                                        <span class="media-card-flag is-warning"><?= e(trans('products.media_missing')) ?></span>
                                    <?php endif; ?>
                                </div>

                                <figcaption class="media-card-name" title="<?= e($fileName) ?>"><?= e($fileName) ?></figcaption>

                                <div class="media-card-actions">
                                    <?php if ($sectionKey === 'photos' && !$isPrimary): ?>
                                        <form method="post" action="<?= e(route('admin.products.media.primary', ['id' => $productId, 'attachmentId' => $attachmentId])) ?>" class="inline-form">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-secondary" title="<?= e(trans('products.media_set_main')) ?>">&#9733;</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="<?= e(route('admin.products.media.order', ['id' => $productId, 'attachmentId' => $attachmentId])) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="btn btn-sm btn-ghost" title="<?= e(trans('products.media_up')) ?>"<?= $index === 0 ? ' disabled' : '' ?>>&uarr;</button>
                                    </form>

                                    <form method="post" action="<?= e(route('admin.products.media.order', ['id' => $productId, 'attachmentId' => $attachmentId])) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="btn btn-sm btn-ghost" title="<?= e(trans('products.media_down')) ?>"<?= $index === count($items) - 1 ? ' disabled' : '' ?>>&darr;</button>
                                    </form>

                                    <form method="post" action="<?= e(route('admin.products.media.move', ['id' => $productId, 'attachmentId' => $attachmentId])) ?>" class="media-card-move">
                                        <?= csrf_field() ?>
                                        <select name="section" class="input input-sm" aria-label="<?= e(trans('products.media_move_to')) ?>">
                                            <?php foreach ($moveTargets as $target): ?>
                                                <option value="<?= e($target) ?>"><?= e($imageSections[$target]['badge']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-secondary" title="<?= e(trans('products.media_move_to')) ?>">&rarr;</button>
                                    </form>

                                    <form method="post" action="<?= e(route('admin.products.media.destroy', ['id' => $productId, 'attachmentId' => $attachmentId])) ?>" class="inline-form"
                                          onsubmit="return confirm('<?= e(trans('products.media_remove_confirm')) ?>');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-danger" title="<?= e(trans('products.media_remove')) ?>">&times;</button>
                                    </form>
                                </div>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(route('admin.products.media.store', ['id' => $productId])) ?>" enctype="multipart/form-data" class="media-upload">
                    <?= csrf_field() ?>
                    <input type="hidden" name="section" value="<?= e($sectionKey) ?>">
                    <input class="input" type="file" name="images[]" accept="image/*" multiple required>
                    <button type="submit" class="btn btn-secondary"><?= e(trans('products.media_upload_to')) ?> <?= e($sectionMeta['badge']) ?></button>
                </form>
            </section>
        <?php endforeach; ?>
    </div>

    <p class="field-help"><?= e(trans('products.media_library_note')) ?></p>
<?php endif; ?>
