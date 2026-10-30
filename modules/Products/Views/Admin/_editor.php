<?php
use Modules\Products\Services\ProductService;

/** @var Core\View\View $view */
/** @var \Modules\Products\Models\Product $product */
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
/** @var array<int, array<string, mixed>> $collections */
/** @var array<int, array<string, mixed>> $brands */

$pid = (int) $product->id;
$locales = $translator->locales();
$fallbackLocale = (string) config('localization.fallback', 'en');
$sectionUrl = static fn (string $section): string => route('admin.products.section', ['id' => $pid, 'section' => $section]);
$fmtNum = static function (mixed $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
};
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
    <div class="rep-row<?= $selectedPreset > 0 ? ' is-preset' : '' ?>" data-repeater-row data-feature-row>
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
                           name="features[<?= e($idx) ?>][label][<?= e($locale) ?>]" value="<?= e((string) ($tr['label'] ?? '')) ?>" data-feature-label="<?= e($locale) ?>"<?= $selectedPreset > 0 ? ' readonly' : '' ?>>
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
$picker = static function (string $field, array $ids) use ($relationLabels, $pid): string {
    ob_start(); ?>
    <div class="picker" data-picker data-field="<?= e($field) ?>" data-exclude="<?= $pid ?>" data-search-url="<?= e(route('admin.products.search')) ?>">
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

$saveBtn = static fn (): string => '<div class="editor-save-row"><button type="submit" class="btn btn-sm btn-primary">' . e(trans('common.save')) . '</button><span class="editor-saved" data-saved-flag hidden>✓ ' . e(trans('common.saved')) . '</span></div>';
?>
<div class="card-editor" data-editor-root data-product-id="<?= $pid ?>">
    <?php /* Steps: one topic at a time inside the editor modal — no endless scroll. */ ?>
    <nav class="adm-steps" data-editor-steps role="tablist" aria-label="<?= e(trans('products.expand_editor')) ?>">
        <button type="button" class="adm-step-btn is-active" data-step-btn="media" role="tab" aria-selected="true"><?= e(trans('products.images_title')) ?></button>
        <button type="button" class="adm-step-btn" data-step-btn="basics" role="tab" aria-selected="false"><?= e(trans('products.section_basics')) ?></button>
        <button type="button" class="adm-step-btn" data-step-btn="prices" role="tab" aria-selected="false"><?= e(trans('products.section_variants')) ?></button>
        <button type="button" class="adm-step-btn" data-step-btn="specs" role="tab" aria-selected="false"><?= e(trans('products.section_specs')) ?></button>
        <button type="button" class="adm-step-btn" data-step-btn="features" role="tab" aria-selected="false"><?= e(trans('products.section_features')) ?></button>
        <button type="button" class="adm-step-btn" data-step-btn="relations" role="tab" aria-selected="false"><?= e(trans('products.section_relations')) ?></button>
        <button type="button" class="adm-step-btn" data-step-btn="files" role="tab" aria-selected="false"><?= e(trans('products.section_documents')) ?></button>
    </nav>

    <div class="adm-step-panel" data-step-panel="media">
    <section class="editor-block" data-editor-media>
        <h3 class="editor-block-title"><?= e(trans('products.images_title')) ?></h3>
        <div data-media-slot>
            <?= $view->render('products::Admin._media', ['product' => $product, 'mediaSections' => $mediaSections]) ?>
        </div>
    </section>
    </div>

    <div class="adm-step-panel" data-step-panel="basics" hidden>
    <form class="editor-block" data-section-form="classification" method="post" action="<?= e($sectionUrl('classification')) ?>">
        <?= csrf_field() ?>
        <h3 class="editor-block-title"><?= e(trans('products.section_basics')) ?></h3>
        <div class="grid grid-2">
            <div class="field">
                <label class="field-label"><?= e(trans('common.collections')) ?></label>
                <select class="input" name="collection_id">
                    <option value="">-</option>
                    <?php foreach ($collections as $collection): ?>
                        <option value="<?= (int) $collection['id'] ?>" <?= (int) ($product->collection_id ?? 0) === (int) $collection['id'] ? 'selected' : '' ?>><?= e((string) $collection['display_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="field-label"><?= e(trans('common.brands')) ?></label>
                <select class="input" name="brand_id">
                    <option value="">-</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int) $brand['id'] ?>" <?= (int) ($product->brand_id ?? 0) === (int) $brand['id'] ? 'selected' : '' ?>><?= e((string) $brand['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <p class="field-help"><?= e(trans('products.keywords_note')) ?></p>
        <?= $saveBtn() ?>
    </form>

    <form class="editor-block" data-section-form="texts" method="post" action="<?= e($sectionUrl('texts')) ?>">
        <?= csrf_field() ?>
        <h3 class="editor-block-title"><?= e(trans('common.translations')) ?></h3>
        <?php foreach ($locales as $locale): $tr = $translations[$locale] ?? []; ?>
            <fieldset class="translation-set">
                <legend><?= e(strtoupper($locale)) ?></legend>
                <label class="field"><span class="field-label"><?= e(trans('products.name')) ?> (<?= e(strtoupper($locale)) ?>)</span>
                    <input class="input" type="text" maxlength="255" name="name_<?= e($locale) ?>" value="<?= e((string) ($tr['name'] ?? '')) ?>"></label>
                <label class="field"><span class="field-label"><?= e(trans('products.description')) ?> - short (<?= e(strtoupper($locale)) ?>)</span>
                    <input class="input" type="text" maxlength="255" name="short_description_<?= e($locale) ?>" value="<?= e((string) ($tr['short_description'] ?? '')) ?>"></label>
                <label class="field"><span class="field-label"><?= e(trans('products.description')) ?> (<?= e(strtoupper($locale)) ?>)</span>
                    <textarea class="input" rows="3" name="description_<?= e($locale) ?>"><?= e((string) ($tr['description'] ?? '')) ?></textarea></label>
            </fieldset>
        <?php endforeach; ?>
        <?= $saveBtn() ?>
    </form>
    </div>

    <div class="adm-step-panel" data-step-panel="prices" hidden>
    <form class="editor-block" data-section-form="variants" method="post" action="<?= e($sectionUrl('variants')) ?>">
        <?= csrf_field() ?>
        <h3 class="editor-block-title"><?= e(trans('products.section_variants')) ?></h3>
        <p class="field-help"><?= e(trans('products.variants_hint')) ?></p>
        <div data-repeater="variants">
            <?php
            $variantRows = $variants !== [] ? $variants : [['sku' => '', 'price' => 0, 'stock_status' => 'in_stock', 'status' => 'active', 'sort_order' => 1]];
            foreach (array_values($variantRows) as $i => $row) {
                echo $variantRow((string) $i, $row);
            }
            ?>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" data-repeater-add data-target="variants" data-template="variant-template-<?= $pid ?>"><?= e(trans('products.variant_add')) ?></button>
        <template id="variant-template-<?= $pid ?>"><?= $variantRow('__INDEX__', ['stock_status' => 'in_stock', 'status' => 'active', 'sort_order' => 0]) ?></template>
        <?= $saveBtn() ?>
    </form>
    </div>

    <div class="adm-step-panel" data-step-panel="specs" hidden>
    <form class="editor-block" data-section-form="specs" method="post" action="<?= e($sectionUrl('specs')) ?>">
        <?= csrf_field() ?>
        <h3 class="editor-block-title"><?= e(trans('products.section_specs')) ?></h3>
        <p class="field-help"><?= e(trans('products.specs_hint')) ?></p>
        <div data-repeater="specs">
            <?php foreach (array_values($specs) as $i => $row) { echo $specRow((string) $i, $row); } ?>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-repeater-add data-target="specs" data-template="spec-template-<?= $pid ?>"><?= e(trans('products.spec_add')) ?></button>
            <button type="button" class="btn btn-ghost btn-sm" data-add-finish data-template="spec-template-<?= $pid ?>" data-finish-keys="<?= e((string) json_encode($finishKeys, JSON_UNESCAPED_UNICODE)) ?>"><?= e(trans('products.spec_finish_add')) ?></button>
        </div>
        <template id="spec-template-<?= $pid ?>"><?= $specRow('__INDEX__', []) ?></template>
        <?= $saveBtn() ?>
    </form>

    <form class="editor-block" data-section-form="dimensions" method="post" action="<?= e($sectionUrl('dimensions')) ?>">
        <?= csrf_field() ?>
        <h3 class="editor-block-title"><?= e(trans('products.section_dimensions')) ?></h3>
        <div class="grid grid-2">
            <label class="field"><span class="field-label"><?= e(trans('products.width')) ?> (mm)</span>
                <input class="input" type="number" step="any" min="0" name="dim_width_mm" value="<?= e($fmtNum($dimensions['width_mm'] ?? null)) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('products.height')) ?> (mm)</span>
                <input class="input" type="number" step="any" min="0" name="dim_height_mm" value="<?= e($fmtNum($dimensions['height_mm'] ?? null)) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('products.depth')) ?> (mm)</span>
                <input class="input" type="number" step="any" min="0" name="dim_depth_mm" value="<?= e($fmtNum($dimensions['depth_mm'] ?? null)) ?>"></label>
            <label class="field"><span class="field-label"><?= e(trans('products.weight')) ?> (kg)</span>
                <input class="input" type="number" step="any" min="0" name="dim_weight_kg" value="<?= e($fmtNum($dimensions['weight_kg'] ?? null)) ?>"></label>
        </div>
        <?= $saveBtn() ?>
    </form>
    </div>

    <div class="adm-step-panel" data-step-panel="features" hidden>
    <form class="editor-block" data-section-form="features" method="post" action="<?= e($sectionUrl('features')) ?>">
        <?= csrf_field() ?>
        <h3 class="editor-block-title"><?= e(trans('products.section_features')) ?> <span class="badge badge-ghost" data-features-count><?= count($features) ?></span></h3>
        <p class="field-help"><?= e(trans('products.features_hint')) ?></p>
        <div data-repeater="features">
            <?php foreach (array_values($features) as $i => $row) { echo $featureRow((string) $i, $row); } ?>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" data-repeater-add data-target="features" data-template="feature-template-<?= $pid ?>"><?= e(trans('products.feature_add')) ?></button>
        <template id="feature-template-<?= $pid ?>"><?= $featureRow('__INDEX__', []) ?></template>
        <?= $saveBtn() ?>
    </form>
    </div>

    <div class="adm-step-panel" data-step-panel="relations" hidden>
    <form class="editor-block" data-section-form="relations" method="post" action="<?= e($sectionUrl('relations')) ?>">
        <?= csrf_field() ?>
        <h3 class="editor-block-title"><?= e(trans('products.section_relations')) ?></h3>
        <input type="hidden" name="relations_present" value="1">
        <h4><?= e(trans('products.rel_color_title')) ?></h4>
        <p class="field-help"><?= e(trans('products.rel_color_hint')) ?></p>
        <?= $picker('color_variant_ids', $relationState['color_family']) ?>
        <h4><?= e(trans('products.rel_seats_title')) ?></h4>
        <p class="field-help"><?= e(trans('products.rel_seats_hint')) ?></p>
        <?= $picker('seat_cover_ids', $relationState['seat_covers']) ?>
        <h4><?= e(trans('products.rel_wc_title')) ?></h4>
        <p class="field-help"><?= e(trans('products.rel_wc_hint')) ?></p>
        <?= $picker('wc_ids', $relationState['parent_wcs']) ?>
        <label class="adm-check">
            <input type="checkbox" name="seat_cover_only" value="1" <?= !empty($relationState['seat_cover_only']) ? 'checked' : '' ?>>
            <?= e(trans('products.rel_seat_only')) ?>
        </label>
        <?= $saveBtn() ?>
    </form>
    </div>

    <div class="adm-step-panel" data-step-panel="files" hidden>
    <form class="editor-block" data-section-form="documents" method="post" enctype="multipart/form-data" action="<?= e($sectionUrl('documents')) ?>">
        <?= csrf_field() ?>
        <h3 class="editor-block-title"><?= e(trans('products.section_documents')) ?></h3>
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
        <?= $saveBtn() ?>
    </form>

    <form class="editor-block" data-section-form="seo" method="post" action="<?= e($sectionUrl('seo')) ?>">
        <?= csrf_field() ?>
        <h3 class="editor-block-title"><?= e(trans('products.section_seo')) ?></h3>
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
        <?= $saveBtn() ?>
    </form>
    </div>
</div>
