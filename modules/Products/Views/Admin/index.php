<?php
use Modules\Products\Services\ProductFeatureIconCatalog;
use Modules\Products\Services\ProductService;
use Modules\Products\Services\ProductVisibility;

/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var Core\Database\Paginator $paginator */
/** @var array<int, array<string, mixed>> $cards */
/** @var array<int, array<string, mixed>> $payloads */
/** @var array<int, array{photo: bool, drawing: bool, ready: bool}> $mediaState */
/** @var array<int, array<int, array{id: int, icon: string, label: string, value: string}>> $chips */
/** @var array<int, array{id: int, value: string}> $finishSpecs */
/** @var array<int, array<string, mixed>> $categoryOptions */
/** @var array<int, string> $categoryPaths */
/** @var array<string, mixed> $filters */
/** @var string $locale */

$fmtPrice = static function (float $value): string {
    return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
};
$leafOptions = array_values(array_filter($categoryOptions, static fn (array $c): bool => !empty($c['is_leaf'])));
?>
<form method="get" class="admin-toolbar adm-filters">
    <input class="input" type="search" name="q" value="<?= e((string) $filters['q']) ?>" placeholder="<?= e(trans('common.search')) ?>">
    <select class="input" name="category_id" aria-label="<?= e(trans('common.category')) ?>">
        <option value=""><?= e(trans('common.all_categories')) ?></option>
        <?php foreach ($categoryOptions as $option): ?>
            <option value="<?= (int) $option['id'] ?>" <?= (int) $filters['category_id'] === (int) $option['id'] ? 'selected' : '' ?>><?= e((string) ($option['path'] ?? $option['slug'])) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="input" name="status" aria-label="<?= e(trans('common.status')) ?>">
        <option value=""><?= e(trans('products.filter_all_statuses')) ?></option>
        <?php foreach (ProductService::STATUSES as $statusOption): ?>
            <option value="<?= $statusOption ?>" <?= $filters['status'] === $statusOption ? 'selected' : '' ?>><?= e(trans('common.' . $statusOption)) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="input" name="sort" aria-label="<?= e(trans('products.sort_label')) ?>">
        <option value="model" <?= $filters['sort'] === 'model' ? 'selected' : '' ?>><?= e(trans('products.sort_model')) ?></option>
        <option value="name" <?= $filters['sort'] === 'name' ? 'selected' : '' ?>><?= e(trans('products.sort_name')) ?></option>
        <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>><?= e(trans('products.sort_newest')) ?></option>
        <option value="curated" <?= $filters['sort'] === 'curated' ? 'selected' : '' ?>><?= e(trans('products.sort_curated')) ?></option>
    </select>
    <label class="adm-check">
        <input type="checkbox" name="featured" value="1" <?= !empty($filters['featured']) ? 'checked' : '' ?>>
        <?= e(trans('products.filter_featured_only')) ?>
    </label>
    <?= $view->component('button', ['label' => trans('common.search'), 'variant' => 'secondary', 'type' => 'submit']) ?>
    <?= $view->component('button', ['label' => trans('common.create_product'), 'variant' => 'primary', 'href' => route('admin.products.create')]) ?>
</form>

<p class="admin-hint"><?= e(trans('products.inline_hint')) ?></p>

<?php if ($paginator->items() === []): ?>
    <p class="media-empty"><?= e(trans('common.no_results')) ?></p>
<?php endif; ?>

<div class="adm-cards">
    <?php foreach ($paginator->items() as $product): ?>
        <?php
        $pid = (int) $product->id;
        $payload = $payloads[$pid] ?? [];
        $card = $cards[$pid] ?? [];
        $media = $mediaState[$pid] ?? ['photo' => false, 'drawing' => false, 'ready' => false];
        $isFeatured = !empty($payload['is_featured']);
        $status = (string) ($payload['status'] ?? $product->status);
        $isPublic = in_array($status, ProductVisibility::PUBLIC_STATUSES, true) && $media['ready'];
        $image = trim((string) ($payload['image'] ?? ''));
        $productChips = $chips[$pid] ?? [];
        $finishSpec = $finishSpecs[$pid] ?? null;
        $selectedCategory = (int) ($payload['category_id'] ?? 0);
        $price = (float) ($payload['price'] ?? 0);
        $stock = (string) ($payload['stock_status'] ?? 'in_stock');
        ?>
        <article class="adm-card<?= $isFeatured ? ' is-featured' : '' ?>"
                 data-product-card="<?= $pid ?>"
                 data-inline-url="<?= e(route('admin.products.inline', ['id' => $pid])) ?>"
                 data-featured-url="<?= e(route('admin.products.toggle-featured', ['id' => $pid])) ?>">
            <div class="adm-card-media">
                <a href="<?= e(route('admin.products.edit', ['id' => $pid])) ?>#media-photos" title="<?= e(trans('products.images_title')) ?>">
                    <?php if ($image !== ''): ?>
                        <img src="<?= e(asset($image)) ?>" alt="<?= e((string) ($payload['name'] ?? '')) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="adm-noimg"><?= e(trans('products.no_image')) ?></span>
                    <?php endif; ?>
                </a>
                <button type="button" class="adm-star<?= $isFeatured ? ' is-on' : '' ?>" data-featured-toggle
                        title="<?= e(trans('products.featured_toggle')) ?>" aria-pressed="<?= $isFeatured ? 'true' : 'false' ?>">★</button>
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

                <label class="adm-field">
                    <span><?= e(trans('common.category')) ?></span>
                    <select class="input input-sm" data-inline-select="category_id">
                        <option value="">—</option>
                        <?php $hasCurrent = false; ?>
                        <?php foreach ($leafOptions as $option): ?>
                            <?php if ((int) $option['id'] === $selectedCategory) { $hasCurrent = true; } ?>
                            <option value="<?= (int) $option['id'] ?>" <?= $selectedCategory === (int) $option['id'] ? 'selected' : '' ?>><?= e((string) ($option['path'] ?? $option['slug'])) ?></option>
                        <?php endforeach; ?>
                        <?php if ($selectedCategory > 0 && !$hasCurrent): ?>
                            <option value="<?= $selectedCategory ?>" selected><?= e($categoryPaths[$selectedCategory] ?? ('#' . $selectedCategory)) ?></option>
                        <?php endif; ?>
                    </select>
                </label>

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

                <?php if (!$media['ready']): ?>
                    <p class="adm-media-warn">
                        <?= e(trans('products.hidden_media_incomplete')) ?>:
                        <?= e(trans($media['photo'] ? 'products.main_image_present' : 'products.main_image_missing')) ?> ·
                        <?= e(trans($media['drawing'] ? 'products.drawing_present' : 'products.drawing_missing')) ?>
                    </p>
                <?php endif; ?>

                <div class="adm-card-actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(route('admin.products.edit', ['id' => $pid])) ?>"><?= e(trans('products.full_edit')) ?></a>
                    <form method="post" action="<?= e(route('admin.products.destroy', ['id' => $pid])) ?>" class="inline-form" data-confirm="<?= e(trans('products.delete_product_confirm')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><?= e(trans('common.delete')) ?></button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?= $view->component('pagination', ['paginator' => $paginator]) ?>
