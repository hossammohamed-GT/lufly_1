<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var Core\Database\Paginator $paginator */
$locale = $translator->getLocale();
$names = [];
foreach ($paginator->items() as $product) {
    $names[(int) $product->id] = $product->translate($locale)['name'] ?? '';
}
?>
<div class="admin-toolbar">
    <form method="get" class="inline-form">
        <input class="input" type="search" name="q" value="<?= e(request()->query('q', '')) ?>" placeholder="<?= e(trans('common.search')) ?>">
        <?= $view->component('button', ['label' => trans('common.search'), 'variant' => 'secondary', 'type' => 'submit']) ?>
    </form>
    <?= $view->component('button', ['label' => trans('common.create_product'), 'variant' => 'primary', 'href' => route('admin.products.create')]) ?>
</div>

<div class="product-table-wrap">
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Preview</th>
            <th><?= e(trans('common.model_code')) ?></th>
            <th><?= e(trans('common.name')) ?></th>
            <th><?= e(trans('common.slug')) ?></th>
            <th><?= e(trans('common.price')) ?></th>
            <th><?= e(trans('common.status')) ?></th>
            <th><?= e(trans('products.media_status')) ?></th>
            <th><?= e(trans('products.visibility')) ?></th>
            <th><?= e(trans('common.actions')) ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($paginator->items() as $product): ?>
            <?php
            $data = $product->translate($locale);
            $productImage = !empty($data['image']) ? asset((string) $data['image']) : null;
            $productName = (string) ($names[(int) $product->id] ?? $product->model_code ?? '');
            $status = (string) $product->status;
            $hasMainImage = $product->gallery() !== [];
            $hasDrawing = !empty($data['drawings']);
            $isPublic = in_array($status, ['active', 'coming_soon'], true);
            $canToggleVisibility = in_array($status, ['active', 'hidden'], true);
            ?>
            <tr>
                <td><?= (int) $product->id ?></td>
                <td style="width: 60px;">
                    <?php if ($productImage): ?>
                        <a href="<?= $productImage ?>" title="<?= e($productName) ?>">
                            <img src="<?= $productImage ?>" alt="<?= e($productName) ?>" loading="lazy" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid var(--ds-border, #e2e8f0); display: block; background: var(--ds-surface, #fff);">
                        </a>
                    <?php else: ?>
                        <div style="width: 48px; height: 48px; border-radius: 6px; border: 1px dashed var(--ds-border, #e2e8f0); display: flex; align-items: center; justify-content: center; background: var(--ds-surface-alt, #f8fafc); font-size: 11px; color: var(--ds-text-muted, #94a3b8); font-weight: 500;">
                            No img
                        </div>
                    <?php endif; ?>
                </td>
                <td><strong><?= e((string) ($product->model_code ?? '')) ?></strong></td>
                <td><?= e($productName) ?></td>
                <td><code><?= e((string) $product->slug) ?></code></td>
                <td><?= e((string) ($data['price'] ?? 0)) ?></td>
                <td><span class="badge badge-<?= e($status) ?>"><?= e(trans('common.' . $status)) ?></span></td>
                <td>
                    <div class="product-media-flags">
                        <span class="badge badge-<?= $hasMainImage ? 'active' : 'draft' ?>">
                            <?= e(trans($hasMainImage ? 'products.main_image_present' : 'products.main_image_missing')) ?>
                        </span>
                        <span class="badge badge-<?= $hasDrawing ? 'active' : 'draft' ?>">
                            <?= e(trans($hasDrawing ? 'products.drawing_present' : 'products.drawing_missing')) ?>
                        </span>
                    </div>
                </td>
                <td>
                    <div class="product-visibility">
                        <span class="badge badge-<?= $isPublic ? 'active' : 'inactive' ?>">
                            <?= e(trans($isPublic ? 'products.visible' : 'products.hidden')) ?>
                        </span>
                        <?php if ($canToggleVisibility): ?>
                            <form method="post" action="<?= e(route('admin.products.visibility', ['id' => $product->id])) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="page" value="<?= max(1, (int) request()->query('page', '1')) ?>">
                                <input type="hidden" name="q" value="<?= e((string) request()->query('q', '')) ?>">
                                <button type="submit" class="btn btn-sm btn-secondary"
                                        aria-label="<?= e(trans($status === 'active' ? 'products.hide_product' : 'products.show_product') . ' — ' . $productName) ?>">
                                    <?= e(trans($status === 'active' ? 'products.hide_product' : 'products.show_product')) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="row-actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(route('admin.products.edit', ['id' => $product->id])) ?>"><?= e(trans('common.edit')) ?></a>
                    <form method="post" action="<?= e(route('admin.products.destroy', ['id' => $product->id])) ?>" class="inline-form"
                          onsubmit="return confirm('Are you sure you want to delete this product?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><?= e(trans('common.delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?= $view->component('pagination', ['paginator' => $paginator]) ?>
