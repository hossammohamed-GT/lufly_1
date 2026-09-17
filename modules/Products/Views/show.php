<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
/** @var array<string, mixed> $product */
/** @var string $locale */
$img = $product['image'] ?? '/images/products/prod_146_1620-111-a.jpg';
$sku = $product['sku'] ?? '';
$specs = is_array($product['specs'] ?? null) ? $product['specs'] : json_decode((string) ($product['specs'] ?? '[]'), true);
if (empty($specs)) {
    $specs = [
        'material' => 'Vitreous China / Premium Ceramic',
        'origin' => 'Manufactured in Turkey / European Standards',
        'finish' => 'Glossy Antibacterial Hygienic Glaze',
        'warranty' => '10 Years Factory Guarantee',
    ];
}
$waMsg = rawurlencode('Hello LUFLY B2B Export Team, I would like to request formal quotation and catalog specifications for product: ' . ($product['name'] ?? '') . ' (SKU: ' . $sku . ')');
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
                     onerror="this.onerror=null; this.src='<?= e(asset('public/images/products/prod_146_1620-111-a.jpg')) ?>';">
                <span class="product-sku-badge-lg">SKU: <?= e($sku) ?></span>
            </div>
        </div>

        <div class="product-info-panel">
            <span class="eyebrow"><?= e(trans('common.factory_direct')) ?></span>
            <h1 class="product-detail-title"><?= e($product['name'] ?? '') ?></h1>
            <p class="product-detail-sku"><strong><?= e(trans('common.sku')) ?>:</strong> <?= e($sku) ?></p>

            <div class="product-guarantee-banner">
                <span class="badge-shield">🛡️</span>
                <div>
                    <strong>10 Years Factory Guarantee • CE & EN-997 Standard</strong>
                    <p>Direct export from LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ, Gaziantep, Turkey.</p>
                </div>
            </div>

            <div class="product-description-box">
                <h3><?= e(trans('products.description')) ?></h3>
                <p><?= nl2br(e($product['description'] ?? $product['short_description'] ?? '')) ?></p>
            </div>

            <!-- Technical Specifications Table -->
            <div class="product-specs-box card">
                <div class="card-header">
                    <h3 class="card-title"><?= e(trans('common.specifications')) ?></h3>
                </div>
                <div class="card-body">
                    <table class="specs-table">
                        <tbody>
                            <tr>
                                <th>Material</th>
                                <td><?= e($specs['material'] ?? 'Vitreous China / Premium Ceramic') ?></td>
                            </tr>
                            <tr>
                                <th>Manufacturing Origin</th>
                                <td><?= e($specs['origin'] ?? 'Manufactured in Turkey / European Standards') ?></td>
                            </tr>
                            <tr>
                                <th>Surface Glaze</th>
                                <td><?= e($specs['finish'] ?? 'Glossy Hygienic Glaze') ?></td>
                            </tr>
                            <tr>
                                <th>Factory Warranty</th>
                                <td><?= e($specs['warranty'] ?? '10 Years Factory Guarantee') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="product-actions-bar">
                <a href="https://wa.me/908503040817?text=<?= $waMsg ?>" target="_blank" rel="noopener" class="btn btn-primary btn-lg btn-whatsapp-lg">
                    <span>💬 <?= e(trans('products.inquire_whatsapp')) ?> (+90 850 3040 817)</span>
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
  "sku": <?= json_encode($sku, JSON_UNESCAPED_UNICODE) ?>,
  "mpn": <?= json_encode($sku, JSON_UNESCAPED_UNICODE) ?>,
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
    "availability": "https://schema.org/InStock"
  }
}
</script>
