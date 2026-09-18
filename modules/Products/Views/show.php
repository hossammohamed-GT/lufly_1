<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/products/detail/detail.css');
/** @var array<string, mixed> $product */
/** @var string $locale */
$fallbackImg = '/images/products/prod_146_1620-111-a.jpg';
$img = (string) ($product['image'] ?? '') !== '' ? (string) $product['image'] : $fallbackImg;
$modelCode = (string) ($product['model_code'] ?? $product['sku'] ?? '');
$variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];
$specs = is_array($product['specs'] ?? null) ? $product['specs'] : [];
$dimensions = is_array($product['dimensions'] ?? null) ? $product['dimensions'] : null;

$waMsg = rawurlencode('Hello LUFLY B2B Export Team, I would like to request formal quotation and catalog specifications for product: ' . ($product['name'] ?? '') . ' (Model: ' . $modelCode . ')');
?>
<section class="container product-detail-page">
    <nav class="breadcrumb-nav">
        <a href="<?= e(route('home')) ?>"><?= e(trans('home.hero_title')) ?></a>
        <span class="separator">/</span>
        <a href="<?= e(route('products.index')) ?>"><?= e(trans('products.title')) ?></a>
        <span class="separator">/</span>
        <span class="current"><?= e($product['name'] ?? '') ?></span>
    </nav>

    <div class="product-detail-grid">
        <div class="product-gallery">
            <div class="product-gallery-card card">
                <img src="<?= e(asset($img)) ?>"
                     alt="<?= e($product['name'] ?? '') ?>"
                     class="product-detail-image"
                     onerror="this.onerror=null; this.src='<?= e(asset($fallbackImg)) ?>';">
                <span class="product-sku-badge-lg"><?= e(trans('common.sku')) ?>: <?= e($modelCode) ?></span>
            </div>
        </div>

        <div class="product-info-panel">
            <span class="eyebrow"><?= e(trans('common.factory_direct')) ?></span>
            <h1 class="product-detail-title"><?= e($product['name'] ?? '') ?></h1>
            <p class="product-detail-sku"><strong><?= e(trans('common.model_code')) ?>:</strong> <?= e($modelCode) ?></p>

            <?php if ($variants !== [] && count($variants) > 1): ?>
                <div class="product-variants">
                    <?php foreach ($variants as $variant): ?>
                        <span class="badge badge-active"><?= e((string) ($variant['variant_name'] ?? $variant['sku'] ?? '')) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="product-guarantee-banner">
                <span class="badge-shield" aria-hidden="true">10Y</span>
                <div>
                    <strong>10 Years Factory Guarantee • CE & EN-997 Standard</strong>
                    <p>Direct export from LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ, Gaziantep, Turkey.</p>
                </div>
            </div>

            <div class="product-description-box">
                <h3><?= e(trans('products.description')) ?></h3>
                <p><?= nl2br(e($product['short_description'] ?? $product['description'] ?? '')) ?></p>
            </div>

            <!-- Technical Specifications Table -->
            <div class="product-specs-box card">
                <div class="card-header">
                    <h3 class="card-title"><?= e(trans('common.specifications')) ?></h3>
                </div>
                <div class="card-body">
                    <table class="specs-table">
                        <tbody>
                            <?php $hasRows = false; ?>
                            <?php foreach ($specs as $row): ?>
                                <?php
                                    $key = (string) ($row['spec_key'] ?? '');
                                    $value = (string) ($row['spec_value'] ?? '');
                                    if ($key === '' || $value === '') {
                                        continue;
                                    }
                                    $hasRows = true;
                                    $unit = (string) ($row['unit'] ?? '');
                                ?>
                                <tr>
                                    <th><?= e($key) ?></th>
                                    <td><?= e($value . ($unit !== '' ? ' ' . $unit : '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($dimensions !== null): ?>
                                <?php
                                    $dims = array_filter([
                                        (string) trans('products.width') => $dimensions['width_mm'] ?? null,
                                        (string) trans('products.height') => $dimensions['height_mm'] ?? null,
                                        (string) trans('products.depth') => $dimensions['depth_mm'] ?? null,
                                    ], static fn ($v) => $v !== null && $v !== '');
                                ?>
                                <?php if ($dims !== []): ?>
                                    <tr>
                                        <th><?= e(trans('products.dimensions')) ?></th>
                                        <td><?= e(implode(' × ', array_map(static fn ($v) => rtrim(rtrim((string) $v, '0'), '.'), $dims))) ?> mm</td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($dimensions['weight_kg'])): ?>
                                    <tr>
                                        <th><?= e(trans('products.weight')) ?></th>
                                        <td><?= e((string) $dimensions['weight_kg']) ?> kg</td>
                                    </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!$hasRows): ?>
                                <tr>
                                    <th>Material</th>
                                    <td>Vitreous China / Premium Ceramic</td>
                                </tr>
                                <tr>
                                    <th>Manufacturing Origin</th>
                                    <td>Manufactured in Turkey / European Standards</td>
                                </tr>
                                <tr>
                                    <th>Surface Glaze</th>
                                    <td>Glossy Hygienic Glaze</td>
                                </tr>
                                <tr>
                                    <th>Factory Warranty</th>
                                    <td>10 Years Factory Guarantee</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="product-actions-bar">
                <a href="https://wa.me/908503040817?text=<?= $waMsg ?>" target="_blank" rel="noopener" class="btn btn-primary btn-lg btn-whatsapp-lg">
                    <span><?= e(trans('products.inquire_whatsapp')) ?> (+90 850 3040 817)</span>
                </a>
                <a href="<?= e(route('products.index')) ?>" class="btn btn-ghost btn-lg">
                    &larr; <?= e(trans('common.back')) ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Schema.org JSON-LD Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": <?= json_encode($product['name'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
  "image": <?= json_encode(asset($img), JSON_UNESCAPED_UNICODE) ?>,
  "description": <?= json_encode($product['short_description'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
  "sku": <?= json_encode(($product['sku'] ?? '') !== '' ? $product['sku'] : $modelCode, JSON_UNESCAPED_UNICODE) ?>,
  "mpn": <?= json_encode($modelCode, JSON_UNESCAPED_UNICODE) ?>,
  "brand": {
    "@type": "Brand",
    "name": "LUFLY"
  },
  "manufacturer": {
    "@type": "Organization",
    "name": "LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ"
  },
  "offers": {
    "@type": "Offer",
    "url": <?= json_encode(route('products.show', ['slug' => $product['slug']]), JSON_UNESCAPED_UNICODE) ?>,
    "priceCurrency": "EUR",
    "availability": "https://schema.org/<?= e(($product['stock_status'] ?? 'in_stock') === 'out_of_stock' ? 'OutOfStock' : 'InStock') ?>"
  }
}
</script>
