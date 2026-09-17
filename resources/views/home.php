<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
/** @var array $categories */
/** @var array $featuredProducts */
/** @var string $locale */
$categories = $categories ?? [];
$featuredProducts = $featuredProducts ?? [];
$locale = $locale ?? 'en';
?>
<!-- Hero Section -->
<section class="container hero-sanitary">
    <div class="hero-copy">
        <span class="eyebrow"><?= e(trans('common.factory_direct')) ?> • Gaziantep, Turkey</span>
        <h1 class="hero-title"><?= e(trans('home.hero_title')) ?></h1>
        <p class="hero-subtitle"><?= e(trans('home.hero_subtitle')) ?></p>
        <div class="hero-actions">
            <a href="<?= e(route('products.index')) ?>" class="btn btn-primary btn-lg">
                <?= e(trans('home.cta_products')) ?> &rarr;
            </a>
            <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I would like to inquire about B2B European export orders.') ?>" 
               target="_blank" rel="noopener" class="btn btn-ghost btn-lg">
                💬 <?= e(trans('common.whatsapp_inquiry')) ?> (+90 850 3040 817)
            </a>
        </div>
    </div>
</section>

<!-- Category Showcase Section -->
<?php if (!empty($categories)): ?>
<section class="container section-categories">
    <div class="section-header">
        <span class="eyebrow"><?= e(trans('common.categories')) ?></span>
        <h2 class="section-title"><?= $locale === 'ar' ? 'أقسام منتجات الخزف الصحي' : ($locale === 'tr' ? 'Sıhhi Tesisat ve Seramik Koleksiyonları' : 'Sanitary Ware & Ceramic Collections') ?></h2>
        <p class="section-subtitle"><?= $locale === 'ar' ? 'تشكيلة متكاملة من أحدث تصاميم الحمامات الأوروبية المصنعة بأعلى درجات الصلابة والنقاء' : 'Engineered with Vitreous China, antibacterial glaze, and timeless European geometry.' ?></p>
    </div>

    <div class="grid grid-3 category-grid">
        <?php foreach ($categories as $cat): ?>
            <a href="<?= e(route('products.index', ['category' => $cat['slug']])) ?>" class="card category-card">
                <div class="category-card-media">
                    <img src="<?= e(asset($cat['image'] ?: '/images/products/prod_146_1620-111-a.jpg')) ?>" 
                         alt="<?= e($cat['name']) ?>" 
                         loading="lazy" 
                         class="category-image"
                         onerror="this.onerror=null; this.src='<?= e(asset('public/images/products/prod_146_1620-111-a.jpg')) ?>';">
                </div>
                <div class="card-body">
                    <h3 class="category-title"><?= e($cat['name']) ?></h3>
                    <p class="category-desc"><?= e($cat['description'] ?? '') ?></p>
                </div>
                <div class="card-footer category-footer">
                    <span class="link-label"><?= e(trans('products.view_details')) ?> &rarr;</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Quality & Engineering Standards Section -->
<section class="container section-features">
    <div class="section-header">
        <span class="eyebrow">Enterprise Standards</span>
        <h2 class="section-title"><?= e(trans('home.features_title')) ?></h2>
    </div>
    <div class="grid grid-3">
        <div class="card feature-card">
            <div class="card-body">
                <div class="feature-icon">🛡️</div>
                <h3 class="feature-title"><?= e(trans('home.feature_modules')) ?></h3>
                <p class="feature-desc"><?= e(trans('home.feature_modules_desc')) ?></p>
            </div>
        </div>
        <div class="card feature-card">
            <div class="card-body">
                <div class="feature-icon">✨</div>
                <h3 class="feature-title"><?= e(trans('home.feature_localization')) ?></h3>
                <p class="feature-desc"><?= e(trans('home.feature_localization_desc')) ?></p>
            </div>
        </div>
        <div class="card feature-card">
            <div class="card-body">
                <div class="feature-icon">🌍</div>
                <h3 class="feature-title"><?= e(trans('home.feature_api')) ?></h3>
                <p class="feature-desc"><?= e(trans('home.feature_api_desc')) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Corporate Legal Entity Info Section -->
<section class="container section-corporate">
    <div class="card corporate-card">
        <div class="card-body corporate-inner">
            <div class="corporate-info">
                <h3>LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ</h3>
                <p>Official Manufacturer & Exporter of Premium Vitreous China Sanitary Ware.</p>
                <p class="corporate-details">
                    📍 Gaziantep, Turkey &nbsp;|&nbsp; 
                    📞 Phone / WhatsApp: <a href="tel:+908503040817">+90 850 3040 817</a> &nbsp;|&nbsp; 
                    ✉️ Email: <a href="mailto:info@lufly.tr">info@lufly.tr</a>
                </p>
            </div>
            <div class="corporate-cta">
                <a href="<?= e(route('products.index')) ?>" class="btn btn-primary">
                    <?= e(trans('home.cta_products')) ?>
                </a>
            </div>
        </div>
    </div>
</section>
