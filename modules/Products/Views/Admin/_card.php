<?php
use Modules\Products\Services\ProductFeatureIconCatalog;
use Modules\Products\Services\ProductService;
use Modules\Products\Services\ProductVisibility;

/** @var \Modules\Products\Models\Product $product */
/** @var array<int, array<string, mixed>> $payloads */
/** @var array<int, array<string, mixed>> $cards */
/** @var array<int, array{photo: bool, drawing: bool, ready: bool}> $mediaState */
/** @var array<int, array<int, array{id: int, icon: string, label: string, value: string}>> $chips */
/** @var array<int, array{id: int, value: string}> $finishSpecs */
/** @var array<int, array{attachment_id: int, path: string, section: string}|null> $covers */
/** @var array<int, array<int, array{id: int, model_code: string, name: string, finish_key: string, finish_label: string}>> $members */
/** @var array<int, string[]> $reasons */
/** @var array<int, string> $categoryPaths */
/** @var string $mode */
/** @var string $locale */

$pid = (int) $product->id;
$payload = $payloads[$pid] ?? [];
$card = $cards[$pid] ?? [];
$media = $mediaState[$pid] ?? ['photo' => false, 'drawing' => false, 'ready' => false];
$cover = $covers[$pid] ?? null;
$isFeatured = !empty($payload['is_featured']);
$isNew = !empty($payload['is_new']);
$status = (string) ($payload['status'] ?? $product->status);
$isPublic = in_array($status, ProductVisibility::PUBLIC_STATUSES, true) && $media['ready'];
$image = trim((string) ($payload['image'] ?? ''));
$productChips = $chips[$pid] ?? [];
$finishSpec = $finishSpecs[$pid] ?? null;
$categoryId = (int) ($payload['category_id'] ?? 0);
$categoryLabel = $categoryId > 0 ? ($categoryPaths[$categoryId] ?? ('#' . $categoryId)) : '';
$familyMembers = $members[$pid] ?? [];
$productReasons = $reasons[$pid] ?? [];
$price = (float) ($payload['price'] ?? 0);
$stock = (string) ($payload['stock_status'] ?? 'in_stock');
$fmtPrice = static fn (float $value): string => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
?>
<article class="adm-card<?= $isFeatured ? ' is-featured' : '' ?>"
         data-product-card="<?= $pid ?>"
         data-id="<?= $pid ?>"
         data-inline-url="<?= e(route('admin.products.inline', ['id' => $pid])) ?>"
         data-featured-url="<?= e(route('admin.products.toggle-featured', ['id' => $pid])) ?>"
         data-card-url="<?= e(route('admin.products.card', ['id' => $pid])) ?>"
         data-editor-url="<?= e(route('admin.products.editor', ['id' => $pid])) ?>"
         data-media-url="<?= e(route('admin.products.media-fragment', ['id' => $pid])) ?>"
         data-delete-url="<?= e(route('admin.products.destroy', ['id' => $pid])) ?>">
    <div class="adm-card-face" data-card-face>
        <div class="adm-card-media">
            <?php if ($cover !== null && $image !== ''): ?>
                <button type="button" class="adm-cover" data-lightbox="product"
                        data-attachment-id="<?= (int) $cover['attachment_id'] ?>"
                        data-product-id="<?= $pid ?>"
                        data-path="<?= e((string) $cover['path']) ?>"
                        data-section="<?= e((string) $cover['section']) ?>"
                        data-caption="<?= e((string) ($payload['model_code'] ?? '') . ' · ' . (string) ($payload['name'] ?? '')) ?>"
                        title="<?= e(trans('products.images_title')) ?>">
                    <img src="<?= e(asset($image)) ?>" alt="<?= e((string) ($payload['name'] ?? '')) ?>" loading="lazy">
                </button>
            <?php else: ?>
                <button type="button" class="adm-cover adm-cover-empty" data-expand-editor title="<?= e(trans('products.images_title')) ?>">
                    <span class="adm-noimg"><?= e(trans('products.no_image')) ?></span>
                </button>
            <?php endif; ?>
            <button type="button" class="adm-star<?= $isFeatured ? ' is-on' : '' ?>" data-featured-toggle
                    title="<?= e(trans('products.featured_toggle')) ?>" aria-pressed="<?= $isFeatured ? 'true' : 'false' ?>">★</button>
            <button type="button" class="adm-new-pill<?= $isNew ? ' is-on' : '' ?>" data-new-toggle
                    title="<?= e(trans('products.new_toggle')) ?>" aria-pressed="<?= $isNew ? 'true' : 'false' ?>"><?= e(trans('common.new')) ?></button>
            <span class="badge badge-<?= $isPublic ? 'active' : 'inactive' ?> adm-card-visibility">
                <?= e(trans($isPublic ? 'products.visible' : 'products.hidden')) ?>
            </span>
        </div>

        <div class="adm-card-body">
            <h3 class="adm-card-title">
                <span data-inline="name" data-locale="<?= e($locale) ?>" tabindex="0"><?= e((string) ($payload['name'] ?? '')) ?></span>
            </h3>

            <div class="adm-card-codes">
                <code data-inline="model_code" tabindex="0"><?= e((string) ($payload['model_code'] ?? '')) ?></code>
                <span class="adm-slug">
                    /<span data-inline="slug" tabindex="0"><?= e((string) ($payload['slug'] ?? '')) ?></span>
                    <a href="<?= e(route('products.show', ['slug' => (string) ($payload['slug'] ?? '')])) ?>" target="_blank" rel="noopener" title="<?= e(trans('common.view')) ?>">↗</a>
                </span>
            </div>

            <?php if ($familyMembers !== []): ?>
                <div class="adm-family">
                    <span class="adm-family-label"><?= e(trans('products.family_label')) ?></span>
                    <span class="adm-family-dots">
                        <?php foreach ($familyMembers as $member): ?>
                            <?php
                            $memberUrl = route('admin.products.card', ['id' => (int) $member['id']]);
                            $memberTitle = trim((string) $member['finish_label'] . ' · ' . (string) $member['model_code'], ' ·');
                            ?>
                            <button type="button" class="adm-family-dot adm-swatch adm-swatch--<?= e($member['finish_key']) ?><?= (int) $member['id'] === $pid ? ' is-active' : '' ?>"
                                    data-member-card="<?= e($memberUrl) ?>" title="<?= e($memberTitle) ?>"
                                    aria-pressed="<?= (int) $member['id'] === $pid ? 'true' : 'false' ?>"></button>
                        <?php endforeach; ?>
                    </span>
                    <span class="adm-family-count"><?= count($familyMembers) ?></span>
                </div>
            <?php endif; ?>

            <div class="adm-field">
                <span><?= e(trans('common.category')) ?></span>
                <button type="button" class="input input-sm adm-catbtn<?= $categoryId > 0 ? '' : ' is-empty' ?>"
                        data-cat-picker data-category-id="<?= $categoryId ?>"
                        title="<?= e(trans('products.choose_category')) ?>">
                    <span data-cat-label><?= e($categoryLabel !== '' ? $categoryLabel : trans('products.choose_category')) ?></span>
                    <span class="adm-catbtn-caret">▾</span>
                </button>
            </div>

            <?php if ($productChips !== []): ?>
                <div class="adm-chips">
                    <?php foreach ($productChips as $chip): ?>
                        <span class="adm-chip">
                            <span class="adm-chip-icon"><?= ProductFeatureIconCatalog::svg($chip['icon']) ?></span>
                            <span class="adm-chip-label" data-inline="feature_label" data-feature-id="<?= (int) $chip['id'] ?>" data-locale="<?= e($locale) ?>" tabindex="0"><?= e($chip['label']) ?></span>
                            <strong data-inline="feature_value" data-feature-id="<?= (int) $chip['id'] ?>" data-locale="<?= e($locale) ?>" tabindex="0"><?= e($chip['value']) ?></strong>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="adm-finish-row">
                <span class="adm-swatches" data-finishes>
                    <?php foreach ((array) ($card['finishes'] ?? []) as $finish): ?>
                        <i class="adm-swatch adm-swatch--<?= e((string) ($finish['key'] ?? 'neutral')) ?>" title="<?= e((string) ($finish['label'] ?? '')) ?>"></i>
                    <?php endforeach; ?>
                </span>
                <?php if ($finishSpec !== null): ?>
                    <span class="adm-finish-text" data-inline="spec_value" data-spec-id="<?= (int) $finishSpec['id'] ?>" data-locale="<?= e($locale) ?>" tabindex="0"><?= e($finishSpec['value']) ?></span>
                <?php else: ?>
                    <span class="adm-finish-text is-muted"><?= e(trans('products.no_finish')) ?></span>
                <?php endif; ?>
            </div>

            <div class="adm-card-row">
                <label class="adm-field">
                    <span><?= e(trans('common.price')) ?></span>
                    <span class="adm-price" data-inline="price" data-input-type="number" data-value="<?= e($fmtPrice($price)) ?>" tabindex="0"><?= e($fmtPrice($price)) ?></span>
                </label>
                <label class="adm-field">
                    <span><?= e(trans('products.stock_in_stock')) ?></span>
                    <select class="input input-sm" data-inline-select="stock_status">
                        <?php foreach (ProductService::STOCK_STATUSES as $stockOption): ?>
                            <option value="<?= $stockOption ?>" <?= $stock === $stockOption ? 'selected' : '' ?>><?= e(trans('products.stock_' . $stockOption)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="adm-card-row">
                <label class="adm-field">
                    <span><?= e(trans('common.status')) ?></span>
                    <select class="input input-sm" data-inline-select="status">
                        <?php foreach (ProductService::STATUSES as $statusOption): ?>
                            <option value="<?= $statusOption ?>" <?= $status === $statusOption ? 'selected' : '' ?>><?= e(trans('common.' . $statusOption)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="adm-field">
                    <span><?= e(trans('products.priority_order')) ?></span>
                    <span data-inline="sort_order" data-input-type="number" data-sort-order-display tabindex="0"><?= (int) ($payload['sort_order'] ?? 0) ?></span>
                </label>
            </div>

            <?php if ($mode === 'incomplete' && $productReasons !== []): ?>
                <div class="adm-reasons">
                    <span class="adm-reasons-label"><?= e(trans('products.missing_label')) ?></span>
                    <?php foreach ($productReasons as $reason): ?>
                        <span class="adm-reason"><?= e(trans('products.reason_' . $reason)) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php elseif (!$media['ready']): ?>
                <p class="adm-media-warn">
                    <?= e(trans('products.hidden_media_incomplete')) ?>:
                    <?= e(trans($media['photo'] ? 'products.main_image_present' : 'products.main_image_missing')) ?> ·
                    <?= e(trans($media['drawing'] ? 'products.drawing_present' : 'products.drawing_missing')) ?>
                </p>
            <?php endif; ?>

            <div class="adm-card-actions">
                <button type="button" class="btn btn-sm btn-secondary" data-expand-editor aria-expanded="false">
                    <?= e(trans('products.expand_editor')) ?> <span data-expand-caret>▾</span>
                </button>
                <button type="button" class="btn btn-sm btn-danger" data-delete-product
                        data-confirm="<?= e(trans('products.delete_product_confirm')) ?>"><?= e(trans('common.delete')) ?></button>
            </div>
        </div>
    </div>

    <div class="adm-card-editor" data-editor hidden></div>
</article>
