<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/masterpieces/masterpieces.css');
?>
<section class="masterpieces-section">
    <div class="container">
        <div class="row-end masterpieces-head">
            <div>
                <span class="section-tag"><?= e(trans('home.featured_tag')) ?></span>
                <h2 class="section-title"><?= e(trans('home.featured_title')) ?></h2>
            </div>
            <a href="<?= e(route('products.index')) ?>" class="btn-secondary-glass">
                <span><?= e(trans('home.full_catalog')) ?></span>
                <span>&rarr;</span>
            </a>
        </div>

        <div class="products-grid">
            <?php foreach (array_slice($featuredProducts, 0, 4) as $product): ?>
                <?php
                $img = $product['image'] ?? '/images/logo.png';
                $name = $product['name'] ?? ('LUFLY ' . ($product['sku'] ?? 'Sanitary Fixture'));
                $sku = $product['sku'] ?? 'SKU-PENDING';
                $desc = $product['short_description'] ?? 'Premium European specification sanitary fixture.';
                $inquireMsg = rawurlencode("Hello LUFLY Export Desk, I would like to inquire about {$name} (SKU: {$sku}) for an architectural project.");
                ?>
                <div class="product-card-luxury">
                    <div class="product-img-box">
                        <img src="<?= e(asset($img)) ?>" alt="<?= e($name) ?>" loading="lazy">
                    </div>
                    <span class="product-sku-badge">SKU: <?= e($sku) ?></span>
                    <h3 class="product-title"><?= e($name) ?></h3>
                    <p class="product-specs"><?= e($desc) ?></p>
                    <div class="product-card-actions">
                        <a href="https://wa.me/908503040817?text=<?= $inquireMsg ?>" target="_blank" rel="noopener" class="btn-inquire-whatsapp">
                            <span><?= e(trans('home.inquire_b2b')) ?></span>
                        </a>
                        <a href="<?= e(route('products.show', ['slug' => $product['slug'] ?? $product['id']])) ?>" class="product-link">
                            Tech Sheet &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
