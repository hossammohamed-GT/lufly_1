<?php
use Modules\Products\Services\ProductService;

/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var \Modules\Products\Models\Product|null $product */
/** @var array<string, array<string, mixed>> $translations */
/** @var array<string, array<int, array<string, mixed>>> $mediaSections */
/** @var array<int, array<string, mixed>> $specs */
/** @var array<int, array<string, mixed>> $features */
/** @var array<int, array<string, mixed>> $presets */
/** @var array<int, array{key: string, label: string, svg: string}> $icons */
/** @var array<int, array{label: string, swatch: string, tr: string, cs: string}> $finishCatalog */
/** @var array<int, array<string, mixed>> $variants */
/** @var array<string, mixed>|null $dimensions */
/** @var array<string, array<string, mixed>> $seoRows */
/** @var array{color_family: int[], seat_covers: int[], parent_wcs: int[], seat_cover_only: bool} $relationState */
/** @var array<int, array{id: int, model_code: string, name: string}> $relationLabels */
/** @var array<string, array{document_id: int, media_id: int, path: string, original_name: string}> $datasheets */

$action = $product === null ? route('admin.products.store') : route('admin.products.update', ['id' => $product->id]);
$locales = $translator->locales();
$fallbackLocale = (string) config('localization.fallback', 'en');
$productId = $product === null ? 0 : (int) $product->id;
$val = static fn (string $key, string $default = ''): string => (string) old($key, $default);
$fmtNum = static function (mixed $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
};

$status = $val('status', (string) ($product->status ?? 'active'));
$connection = \Modules\Products\Models\Product::query()->connection();
$categories = app(\App\Services\CategoryService::class)->flatten($translator->getLocale(), $fallbackLocale);
$collections = $connection->select("SELECT c.id, c.slug, COALESCE(ct.name, c.slug) AS display_name FROM collections c LEFT JOIN collection_translations ct ON ct.collection_id = c.id AND ct.locale = 'en' WHERE c.deleted_at IS NULL AND c.status = 'active' ORDER BY c.sort_order ASC, c.id ASC");
$brands = $connection->select("SELECT id, name FROM brands WHERE deleted_at IS NULL AND status = 'active' ORDER BY id ASC");

$categoryId = $val('category_id', (string) ($product->category_id ?? ''));
$collectionId = $val('collection_id', (string) ($product->collection_id ?? ''));
$brandId = $val('brand_id', (string) ($product->brand_id ?? ''));
$isFeatured = (int) $val('is_featured', (string) ($product === null ? 0 : (int) $product->is_featured));

$finishKeys = ['en' => 'Finish', 'tr' => 'Yüzey Kaplaması', 'cs' => 'Povrchová úprava'];

/* ---------------------------------------------------------------- variants */
$variantRow = static function (string $idx, array $row) use ($fmtNum): string {
    ob_start(); ?>
    <div class="rep-row" data-repeater-row>
        <input type="hidden" name="variants[<?= e($idx) ?>][id]" value="<?= (int) ($row['id'] ?? 0) ?>">
        <div class="rep-row-grid">
            <label class="field"><span class="field-label"><?= e(trans('common.sku')) ?></span>
                <input class="input" type="text" name="variants[<?= e($idx) ?>][sku]" maxlength="100" value="<?= e((string) ($row['sku'] ?? '')) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('products.variant_name')) ?></span>
                <input class="input" type="text" name="variants[<?= e($idx) ?>][variant_name]" maxlength="150" value="<?= e((string) ($row['variant_name'] ?? '')) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('common.price')) ?></span>
                <input class="input" type="number" step="any" min="0" name="variants[<?= e($idx) ?>][price]" value="<?= e($fmtNum($row['price'] ?? 0)) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('products.stock_in_stock')) ?></span>
                <select class="input" name="variants[<?= e($idx) ?>][stock_status]">
                    <?php foreach (ProductService::STOCK_STATUSES as $stockOption): ?>
                        <option value="<?= $stockOption ?>" <?= (string) ($row['stock_status'] ?? 'in_stock') === $stockOption ? 'selected' : '' ?>><?= e(trans('products.stock_' . $stockOption)) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label class="field"><span class="field-label"><?= e(trans('common.status')) ?></span>
                <select class="input" name="variants[<?= e($idx) ?>][status]">
                    <?php foreach (['active', 'inactive'] as $statusOption): ?>
                        <option value="<?= $statusOption ?>" <?= (string) ($row['status'] ?? 'active') === $statusOption ? 'selected' : '' ?>><?= e(trans('common.' . $statusOption)) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label class="field"><span class="field-label"><?= e(trans('categories.sort_order')) ?></span>
                <input class="input" type="number" step="1" name="variants[<?= e($idx) ?>][sort_order]" value="<?= (int) ($row['sort_order'] ?? 0) ?>"></label>
        </div>
        <div class="rep-row-actions">
            <button type="button" class="btn btn-sm btn-ghost" data-repeater-up title="<?= e(trans('products.row_move_up')) ?>">&uarr;</button>
            <button type="button" class="btn btn-sm btn-ghost" data-repeater-down title="<?= e(trans('products.row_move_down')) ?>">&darr;</button>
            <button type="button" class="btn btn-sm btn-danger" data-repeater-remove title="<?= e(trans('common.remove')) ?>">&times;</button>
        </div>
    </div>
    <?php return (string) ob_get_clean();
};

/* --------------------------------------------------------------------- specs */
$specRow = static function (string $idx, array $row) use ($locales, $fallbackLocale, $finishCatalog): string {
    $enValue = (string) (($row['translations'][$fallbackLocale]['spec_value'] ?? '') ?: ($row['spec_value'] ?? ''));
    $isFinish = mb_strtolower(trim((string) ($row['spec_key'] ?? ''))) === 'finish';
    $chosenFinishes = array_map('trim', explode('/', $enValue));
    ob_start(); ?>
    <div class="rep-row<?= $isFinish ? ' is-finish' : '' ?>" data-repeater-row data-spec-row>
        <input type="hidden" name="specs[<?= e($idx) ?>][id]" value="<?= (int) ($row['id'] ?? 0) ?>">
        <div class="rep-locales">
            <?php foreach ($locales as $locale): $tr = $row['translations'][$locale] ?? []; ?>
                <div class="rep-locale">
                    <span class="rep-locale-tag"><?= e(strtoupper($locale)) ?></span>
                    <input class="input" type="text" maxlength="150" placeholder="<?= e(trans('products.spec_key_label')) ?>"
                           name="specs[<?= e($idx) ?>][key][<?= e($locale) ?>]" value="<?= e((string) ($tr['spec_key'] ?? ($locale === $fallbackLocale ? (string) ($row['spec_key'] ?? '') : ''))) ?>" <?= $locale === $fallbackLocale ? 'data-spec-key-en' : '' ?>>
                    <input class="input" type="text" maxlength="255" placeholder="<?= e(trans('products.spec_value_label')) ?>"
                           name="specs[<?= e($idx) ?>][value][<?= e($locale) ?>]" value="<?= e((string) ($tr['spec_value'] ?? ($locale === $fallbackLocale ? (string) ($row['spec_value'] ?? '') : ''))) ?>" data-spec-value="<?= e($locale) ?>">
                </div>
            <?php endforeach; ?>
            <label class="field rep-unit"><span class="field-label"><?= e(trans('products.spec_unit_label')) ?></span>
                <input class="input" type="text" maxlength="50" name="specs[<?= e($idx) ?>][unit]" value="<?= e((string) ($row['unit'] ?? '')) ?>"></label>
        </div>
        <div class="finish-picker" data-finish-picker>
            <p class="field-help"><?= e(trans('products.finish_picker')) ?> — <?= e(trans('products.finish_picker_hint')) ?></p>
            <div class="finish-picker-grid">
                <?php foreach ($finishCatalog as $finishOption): ?>
                    <label class="finish-option">
                        <input type="checkbox" data-finish-option value="<?= e($finishOption['label']) ?>"
                               data-tr="<?= e($finishOption['tr']) ?>" data-cs="<?= e($finishOption['cs']) ?>"
                               <?= in_array($finishOption['label'], $chosenFinishes, true) ? 'checked' : '' ?>>
                        <i class="adm-swatch adm-swatch--<?= e($finishOption['swatch']) ?>"></i>
                        <span><?= e($finishOption['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="rep-row-actions">
            <button type="button" class="btn btn-sm btn-ghost" data-repeater-up title="<?= e(trans('products.row_move_up')) ?>">&uarr;</button>
            <button type="button" class="btn btn-sm btn-ghost" data-repeater-down title="<?= e(trans('products.row_move_down')) ?>">&darr;</button>
            <button type="button" class="btn btn-sm btn-danger" data-repeater-remove title="<?= e(trans('common.remove')) ?>">&times;</button>
        </div>
    </div>
    <?php return (string) ob_get_clean();
};

/* ------------------------------------------------------------------ features */
$featureRow = static function (string $idx, array $row) use ($locales, $presets, $icons): string {
    $selectedPreset = (int) ($row['preset_id'] ?? 0);
    $selectedIcon = (string) ($row['icon_key'] ?? '');
    $iconSvg = '';
    foreach ($icons as $icon) {
        if ($icon['key'] === $selectedIcon) {
            $iconSvg = (string) $icon['svg'];
            break;
        }
    }
    ob_start(); ?>
    <div class="rep-row" data-repeater-row data-feature-row>
        <input type="hidden" name="features[<?= e($idx) ?>][id]" value="<?= (int) ($row['id'] ?? 0) ?>">
        <div class="rep-row-grid">
            <label class="field"><span class="field-label"><?= e(trans('products.feature_preset')) ?></span>
                <select class="input" name="features[<?= e($idx) ?>][preset_id]" data-preset-select>
                    <option value=""><?= e(trans('products.feature_preset_custom')) ?></option>
                    <?php foreach ($presets as $preset): ?>
                        <option value="<?= (int) $preset['id'] ?>" <?= $selectedPreset === (int) $preset['id'] ? 'selected' : '' ?>><?= e((string) ($preset['translations']['en']['label'] ?? $preset['preset_key'])) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label class="field"><span class="field-label"><?= e(trans('products.feature_icon')) ?></span>
                <span class="icon-select-wrap">
                    <select class="input" name="features[<?= e($idx) ?>][icon_key]" data-icon-select>
                        <?php foreach ($icons as $icon): ?>
                            <option value="<?= e($icon['key']) ?>" <?= $selectedIcon === $icon['key'] ? 'selected' : '' ?>><?= e($icon['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="icon-preview" data-icon-preview><?= $iconSvg ?></span>
                </span></label>
        </div>
        <div class="rep-locales">
            <?php foreach ($locales as $locale): $tr = $row['translations'][$locale] ?? []; ?>
                <div class="rep-locale">
                    <span class="rep-locale-tag"><?= e(strtoupper($locale)) ?></span>
                    <input class="input" type="text" maxlength="120" placeholder="<?= e(trans('products.feature_label')) ?>"
                           name="features[<?= e($idx) ?>][label][<?= e($locale) ?>]" value="<?= e((string) ($tr['label'] ?? '')) ?>" data-feature-label="<?= e($locale) ?>">
                    <input class="input" type="text" maxlength="255" placeholder="<?= e(trans('products.feature_value')) ?>"
                           name="features[<?= e($idx) ?>][value][<?= e($locale) ?>]" value="<?= e((string) ($tr['value'] ?? '')) ?>" data-feature-value="<?= e($locale) ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <div class="rep-row-actions">
            <button type="button" class="btn btn-sm btn-ghost" data-repeater-up title="<?= e(trans('products.row_move_up')) ?>">&uarr;</button>
            <button type="button" class="btn btn-sm btn-ghost" data-repeater-down title="<?= e(trans('products.row_move_down')) ?>">&darr;</button>
            <button type="button" class="btn btn-sm btn-danger" data-repeater-remove title="<?= e(trans('common.remove')) ?>">&times;</button>
        </div>
    </div>
    <?php return (string) ob_get_clean();
};

/* ------------------------------------------------------------------- pickers */
$picker = static function (string $field, array $ids) use ($relationLabels, $productId): string {
    ob_start(); ?>
    <div class="picker" data-picker data-field="<?= e($field) ?>" data-exclude="<?= $productId ?>" data-search-url="<?= e(route('admin.products.search')) ?>">
        <input type="search" class="input" data-picker-search placeholder="<?= e(trans('products.picker_placeholder')) ?>" autocomplete="off">
        <div class="picker-results" data-picker-results hidden></div>
        <div class="picker-chips" data-picker-chips>
            <?php foreach ($ids as $linkedId): $label = $relationLabels[(int) $linkedId] ?? null; ?>
                <span class="picker-chip" data-id="<?= (int) $linkedId ?>">
                    <strong><?= e((string) ($label['model_code'] ?? ('#' . (int) $linkedId))) ?></strong>
                    <span><?= e((string) ($label['name'] ?? '')) ?></span>
                    <button type="button" data-picker-remove title="<?= e(trans('common.remove')) ?>">&times;</button>
                    <input type="hidden" name="<?= e($field) ?>[]" value="<?= (int) $linkedId ?>">
                </span>
            <?php endforeach; ?>
        </div>
        <p class="field-help" data-picker-empty <?= $ids !== [] ? 'hidden' : '' ?>><?= e(trans('products.picker_empty')) ?></p>
    </div>
    <?php return (string) ob_get_clean();
};

/* --------------------------------------------------------------------- media */
$mediaSections = $mediaSections ?? [];
$imageSections = [
    'photos' => ['label' => trans('products.section_photos'), 'hint' => trans('products.section_photos_hint'), 'badge' => trans('products.tab_photos')],
    'drawings' => ['label' => trans('products.section_drawings'), 'hint' => trans('products.section_drawings_hint'), 'badge' => trans('products.tab_drawings')],
    'situ' => ['label' => trans('products.section_situ'), 'hint' => trans('products.section_situ_hint'), 'badge' => trans('products.tab_situ')],
];
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="stack admin-form admin-form-wide">
    <?= csrf_field() ?>

    <fieldset class="admin-section">
        <legend><?= e(trans('products.section_basics')) ?></legend>
        <div class="grid grid-2">
            <?= $view->component('input', ['name' => 'model_code', 'label' => trans('common.model_code'), 'value' => $val('model_code', (string) ($product->model_code ?? '')), 'required' => true]) ?>
            <?= $view->component('input', ['name' => 'slug', 'label' => trans('common.slug'), 'value' => $val('slug', (string) ($product->slug ?? ''))]) ?>
            <div class="field">
                <label class="field-label" for="status"><?= e(trans('common.status')) ?></label>
                <select class="input" id="status" name="status">
                    <?php foreach (ProductService::STATUSES as $option): ?>
                        <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(trans('common.' . $option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="category_id"><?= e(trans('common.category')) ?></label>
                <select class="input" id="category_id" name="category_id">
                    <option value="">-</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" title="<?= e((string) $category['slug']) ?>" <?= empty($category['is_leaf']) ? 'disabled' : '' ?> <?= $categoryId === (string) $category['id'] ? 'selected' : '' ?>><?= e((string) ($category['path'] ?? $category['slug'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="collection_id"><?= e(trans('common.collections')) ?></label>
                <select class="input" id="collection_id" name="collection_id">
                    <option value="">-</option>
                    <?php foreach ($collections as $collection): ?>
                        <option value="<?= (int) $collection['id'] ?>" <?= $collectionId === (string) $collection['id'] ? 'selected' : '' ?>><?= e((string) $collection['display_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="brand_id"><?= e(trans('common.brands')) ?></label>
                <select class="input" id="brand_id" name="brand_id">
                    <option value="">-</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int) $brand['id'] ?>" <?= $brandId === (string) $brand['id'] ? 'selected' : '' ?>><?= e((string) $brand['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="is_featured"><?= e(trans('common.featured')) ?></label>
                <select class="input" id="is_featured" name="is_featured">
                    <option value="1" <?= $isFeatured === 1 ? 'selected' : '' ?>><?= e(trans('products.featured_yes')) ?></option>
                    <option value="0" <?= $isFeatured === 0 ? 'selected' : '' ?>><?= e(trans('products.featured_no')) ?></option>
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="sort_order"><?= e(trans('products.priority_order')) ?></label>
                <input class="input" id="sort_order" name="sort_order" type="number" min="0" step="1" value="<?= e($val('sort_order', (string) (int) ($product->sort_order ?? 0))) ?>">
            </div>
        </div>
        <p class="field-help"><?= e(trans('products.keywords_note')) ?></p>
    </fieldset>

    <fieldset class="admin-section">
        <legend><?= e(trans('products.section_variants')) ?></legend>
        <p class="field-help"><?= e(trans('products.variants_hint')) ?></p>
        <div data-repeater="variants">
            <?php
            $variantRows = $variants !== [] ? $variants : [['sku' => '', 'price' => 0, 'stock_status' => 'in_stock', 'status' => 'active', 'sort_order' => 1]];
            foreach (array_values($variantRows) as $i => $row) {
                echo $variantRow((string) $i, $row);
            }
            ?>
        </div>
        <button type="button" class="btn btn-secondary" data-repeater-add data-target="variants" data-template="variant-template"><?= e(trans('products.variant_add')) ?></button>
        <template id="variant-template"><?= $variantRow('__INDEX__', ['stock_status' => 'in_stock', 'status' => 'active', 'sort_order' => 0]) ?></template>
    </fieldset>

    <fieldset class="admin-section">
        <legend><?= e(trans('common.translations')) ?></legend>
        <?php foreach ($locales as $locale): $tr = $translations[$locale] ?? []; ?>
            <fieldset class="translation-set">
                <legend><?= e(strtoupper($locale)) ?></legend>
                <?= $view->component('input', ['name' => 'name_' . $locale, 'label' => trans('products.name') . ' (' . strtoupper($locale) . ')', 'value' => $val('name_' . $locale, (string) ($tr['name'] ?? '')), 'required' => $locale === $fallbackLocale]) ?>
                <div class="field">
                    <label class="field-label" for="short_description_<?= e($locale) ?>"><?= e(trans('products.description')) ?> - short (<?= e(strtoupper($locale)) ?>)</label>
                    <input class="input" id="short_description_<?= e($locale) ?>" name="short_description_<?= e($locale) ?>" type="text" maxlength="255" value="<?= e($val('short_description_' . $locale, (string) ($tr['short_description'] ?? ''))) ?>">
                </div>
                <div class="field">
                    <label class="field-label" for="description_<?= e($locale) ?>"><?= e(trans('products.description')) ?> (<?= e(strtoupper($locale)) ?>)</label>
                    <textarea class="input" id="description_<?= e($locale) ?>" name="description_<?= e($locale) ?>" rows="3"><?= e($val('description_' . $locale, (string) ($tr['description'] ?? ''))) ?></textarea>
                </div>
            </fieldset>
        <?php endforeach; ?>
    </fieldset>

    <fieldset class="admin-section">
        <legend><?= e(trans('products.section_specs')) ?></legend>
        <p class="field-help"><?= e(trans('products.specs_hint')) ?></p>
        <div data-repeater="specs">
            <?php foreach (array_values($specs) as $i => $row) { echo $specRow((string) $i, $row); } ?>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" data-repeater-add data-target="specs" data-template="spec-template"><?= e(trans('products.spec_add')) ?></button>
            <button type="button" class="btn btn-ghost" data-add-finish data-finish-keys="<?= e((string) json_encode($finishKeys, JSON_UNESCAPED_UNICODE)) ?>"><?= e(trans('products.spec_finish_add')) ?></button>
        </div>
        <template id="spec-template"><?= $specRow('__INDEX__', []) ?></template>
    </fieldset>

    <fieldset class="admin-section">
        <legend><?= e(trans('products.section_features')) ?> <span class="badge badge-ghost" data-features-count><?= count($features) ?></span></legend>
        <p class="field-help"><?= e(trans('products.features_hint')) ?></p>
        <div data-repeater="features">
            <?php foreach (array_values($features) as $i => $row) { echo $featureRow((string) $i, $row); } ?>
        </div>
        <button type="button" class="btn btn-secondary" data-repeater-add data-target="features" data-template="feature-template"><?= e(trans('products.feature_add')) ?></button>
        <template id="feature-template"><?= $featureRow('__INDEX__', []) ?></template>
    </fieldset>

    <fieldset class="admin-section">
        <legend><?= e(trans('products.section_dimensions')) ?></legend>
        <div class="grid grid-2">
            <label class="field"><span class="field-label"><?= e(trans('products.width')) ?> (mm)</span>
                <input class="input" type="number" step="any" min="0" name="dim_width_mm" value="<?= e($val('dim_width_mm', $fmtNum($dimensions['width_mm'] ?? null))) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('products.height')) ?> (mm)</span>
                <input class="input" type="number" step="any" min="0" name="dim_height_mm" value="<?= e($val('dim_height_mm', $fmtNum($dimensions['height_mm'] ?? null))) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('products.depth')) ?> (mm)</span>
                <input class="input" type="number" step="any" min="0" name="dim_depth_mm" value="<?= e($val('dim_depth_mm', $fmtNum($dimensions['depth_mm'] ?? null))) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('products.weight')) ?> (kg)</span>
                <input class="input" type="number" step="any" min="0" name="dim_weight_kg" value="<?= e($val('dim_weight_kg', $fmtNum($dimensions['weight_kg'] ?? null))) ?>"></label>
        </div>
    </fieldset>

    <fieldset class="admin-section">
        <legend><?= e(trans('products.section_relations')) ?></legend>
        <input type="hidden" name="relations_present" value="1">
        <h3><?= e(trans('products.rel_color_title')) ?></h3>
        <p class="field-help"><?= e(trans('products.rel_color_hint')) ?></p>
        <?= $picker('color_variant_ids', $relationState['color_family']) ?>
        <h3><?= e(trans('products.rel_seats_title')) ?></h3>
        <p class="field-help"><?= e(trans('products.rel_seats_hint')) ?></p>
        <?= $picker('seat_cover_ids', $relationState['seat_covers']) ?>
        <h3><?= e(trans('products.rel_wc_title')) ?></h3>
        <p class="field-help"><?= e(trans('products.rel_wc_hint')) ?></p>
        <?= $picker('wc_ids', $relationState['parent_wcs']) ?>
        <label class="adm-check">
            <input type="checkbox" name="seat_cover_only" value="1" <?= !empty($relationState['seat_cover_only']) ? 'checked' : '' ?>>
            <?= e(trans('products.rel_seat_only')) ?>
        </label>
    </fieldset>

    <fieldset class="admin-section">
        <legend><?= e(trans('products.section_documents')) ?></legend>
        <p class="field-help"><?= e(trans('products.datasheet_hint')) ?></p>
        <?php foreach ($locales as $locale): $sheet = $datasheets[$locale] ?? null; ?>
            <div class="datasheet-row">
                <span class="rep-locale-tag"><?= e(strtoupper($locale)) ?></span>
                <?php if ($sheet !== null): ?>
                    <span><?= e(trans('products.datasheet_current')) ?>: <a href="<?= e(asset($sheet['path'])) ?>" target="_blank" rel="noopener"><?= e($sheet['original_name'] !== '' ? $sheet['original_name'] : basename($sheet['path'])) ?></a></span>
                <?php endif; ?>
                <label class="field"><span class="field-label"><?= e(trans($sheet !== null ? 'products.datasheet_replace' : 'products.datasheet_upload')) ?></span>
                    <input class="input" type="file" name="datasheet[<?= e($locale) ?>]" accept="application/pdf"></label>
                <?php if ($sheet !== null): ?>
                    <label class="adm-check"><input type="checkbox" name="datasheet_delete[<?= e($locale) ?>]" value="1"> <?= e(trans('products.datasheet_remove')) ?></label>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </fieldset>

    <fieldset class="admin-section">
        <legend><?= e(trans('products.section_seo')) ?></legend>
        <?php foreach ($locales as $locale): $seo = $seoRows[$locale] ?? []; ?>
            <fieldset class="translation-set">
                <legend><?= e(strtoupper($locale)) ?></legend>
                <label class="field"><span class="field-label"><?= e(trans('products.seo_meta_title')) ?></span>
                    <input class="input" type="text" maxlength="255" name="seo[<?= e($locale) ?>][meta_title]" value="<?= e((string) ($seo['meta_title'] ?? '')) ?>"></label>
                <label class="field"><span class="field-label"><?= e(trans('products.seo_meta_description')) ?></span>
                    <textarea class="input" rows="2" maxlength="500" name="seo[<?= e($locale) ?>][meta_description]"><?= e((string) ($seo['meta_description'] ?? '')) ?></textarea></label>
                <label class="field"><span class="field-label"><?= e(trans('products.seo_og_title')) ?></span>
                    <input class="input" type="text" maxlength="255" name="seo[<?= e($locale) ?>][og_title]" value="<?= e((string) ($seo['og_title'] ?? '')) ?>"></label>
                <label class="field"><span class="field-label"><?= e(trans('products.seo_og_description')) ?></span>
                    <textarea class="input" rows="2" maxlength="500" name="seo[<?= e($locale) ?>][og_description]"><?= e((string) ($seo['og_description'] ?? '')) ?></textarea></label>
                <label class="field"><span class="field-label"><?= e(trans('products.seo_canonical')) ?></span>
                    <input class="input" type="text" maxlength="255" name="seo[<?= e($locale) ?>][canonical_url]" value="<?= e((string) ($seo['canonical_url'] ?? '')) ?>"></label>
            </fieldset>
        <?php endforeach; ?>
    </fieldset>

    <div class="form-actions is-sticky">
        <?= $view->component('button', ['label' => trans('common.save'), 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= $view->component('button', ['label' => trans('common.cancel'), 'variant' => 'ghost', 'href' => route('admin.products.index')]) ?>
    </div>
</form>

<script type="application/json" id="feature-presets-json"><?=
    json_encode(array_reduce($presets, static function (array $carry, array $preset): array {
        $labels = [];
        $placeholders = [];
        foreach (($preset['translations'] ?? []) as $loc => $tr) {
            $labels[$loc] = (string) ($tr['label'] ?? '');
            $placeholders[$loc] = (string) ($tr['placeholder'] ?? '');
        }
        $carry[(int) $preset['id']] = ['icon' => (string) $preset['icon_key'], 'labels' => $labels, 'placeholders' => $placeholders];
        return $carry;
    }, []), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)
?></script>
<script type="application/json" id="feature-icons-json"><?=
    json_encode(array_reduce($icons, static function (array $carry, array $icon): array {
        $carry[$icon['key']] = ['label' => $icon['label'], 'svg' => $icon['svg']];
        return $carry;
    }, []), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)
?></script>

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
                                          data-confirm="<?= e(trans('products.media_remove_confirm')) ?>">
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
