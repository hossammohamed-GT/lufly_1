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

<!-- 1. Cinematic Multi-Scene Video Hero Section -->
<section class="hero-cinematic">
    <!-- Ambient Background Videos Layer -->
    <div class="hero-video-bg-wrapper">
        <video class="hero-bg-video active" id="hero-video-1" autoplay loop muted playsinline poster="<?= e(asset('images/products/prod_146_1620-111-a.jpg')) ?>">
            <source src="<?= e(asset('videos/hero_scene_1.mp4')) ?>" type="video/mp4">
        </video>
        <video class="hero-bg-video" id="hero-video-2" loop muted playsinline poster="<?= e(asset('images/products/prod_205_ESINO.jpg')) ?>">
            <source src="<?= e(asset('videos/hero_scene_2.mp4')) ?>" type="video/mp4">
        </video>
        <video class="hero-bg-video" id="hero-video-3" loop muted playsinline poster="<?= e(asset('images/products/prod_2109_1654-001.jpg')) ?>">
            <source src="<?= e(asset('videos/hero_scene_3.mp4')) ?>" type="video/mp4">
        </video>
        <div class="hero-cinematic-overlay"></div>
    </div>

    <!-- Hero Content Layer -->
    <div class="hero-cinematic-inner">
        <div class="container">
            <div class="hero-cinematic-grid">
                <!-- Left Story Column -->
                <div class="hero-cinematic-story">
                    <div class="hero-pill-badge">
                        <span class="pulse-beacon"></span>
                        <span id="hero-scene-tag">01 • ARCHITECTURAL SANITARY SUITE</span>
                    </div>

                    <div class="hero-story-text">
                        <h1 class="hero-display-title" id="hero-scene-title">
                            Architectural Sanitary Ceramics Engineered for European Living.
                        </h1>
                        <p class="hero-text-desc" id="hero-scene-desc">
                            Precision-engineered rimless wall-hung toilets, designer countertop washbasins, and architectural tapware. Fired at 1,250°C for exceptional durability and European project specification.
                        </p>
                    </div>

                    <div class="hero-actions-bar">
                        <a href="<?= e(route('products.index')) ?>" class="btn btn-primary btn-lg" style="padding: 0.9rem 1.75rem;">
                            <span>Explore Collections</span>
                            &rarr;
                        </a>

                        <button type="button" class="btn-reel" id="hero-watch-reel-btn" aria-label="Watch Factory & Production Reel">
                            <span class="reel-play-circle">
                                ▶
                            </span>
                            <span>Watch Factory Reel</span>
                        </button>

                        <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I would like to request the 2025 Architectural Master Catalog & Price List.') ?>" 
                           target="_blank" rel="noopener" class="btn btn-ghost" style="color: #FFFFFF; border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.06); padding: 0.9rem 1.5rem;">
                            <span>Download 2025 Catalog</span>
                        </a>
                    </div>

                    <div class="hero-stats-bar">
                        <div class="hero-stat-item">
                            <h4>1,250°C</h4>
                            <p>Vitreous China Firing</p>
                        </div>
                        <div class="hero-stat-item">
                            <h4>EN 997</h4>
                            <p>European Standard</p>
                        </div>
                        <div class="hero-stat-item">
                            <h4>10 Years</h4>
                            <p>Factory Guarantee</p>
                        </div>
                        <div class="hero-stat-item">
                            <h4>34+</h4>
                            <p>Export Countries</p>
                        </div>
                    </div>
                </div>

                <!-- Right Spotlight Model Showcase Card -->
                <div class="hero-spotlight-wrap">
                    <div class="hero-spotlight-card" id="hero-spotlight-card">
                        <div class="hero-spotlight-header">
                            <span class="hero-spotlight-badge" id="hero-spotlight-badge">FLAGSHIP • EN 997</span>
                            <span class="hero-spotlight-sku" id="hero-spotlight-sku">SKU: 1620-111</span>
                        </div>

                        <div class="hero-spotlight-img-box">
                            <img src="<?= e(asset('images/products/prod_146_1620-111-a.jpg')) ?>" 
                                 alt="Lufly Rimless Wall-Hung WC Pan" 
                                 id="hero-spotlight-img"
                                 width="380" 
                                 height="240"
                                 loading="eager">
                        </div>

                        <div class="hero-spotlight-title" id="hero-spotlight-title">
                            Rimless Wall-Hung WC Pan
                        </div>
                        <div class="hero-spotlight-specs" id="hero-spotlight-specs">
                            540 × 360 mm • Concealed Wall-Hung
                        </div>

                        <div class="hero-spotlight-footer">
                            <a href="<?= e(route('products.index')) ?>" class="btn btn-primary" id="hero-spotlight-link" style="width: 100%; justify-content: center; padding: 0.75rem 1rem;">
                                <span>Technical Specifications</span>
                                &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline & Scene Switcher Bar -->
    <div class="hero-timeline-controls">
        <div class="container hero-timeline-grid">
            <div class="hero-tabs-group" role="tablist" aria-label="Hero Scene Tabs">
                <button type="button" class="hero-tab-btn active" data-scene="0" role="tab" aria-selected="true">
                    <div class="hero-tab-progress-track">
                        <div class="hero-tab-progress-fill" style="width: 0%;"></div>
                    </div>
                    <div class="hero-tab-meta">
                        <span class="hero-tab-num">01</span>
                        <span class="hero-tab-title">Architectural Sanitary Suite</span>
                    </div>
                </button>

                <button type="button" class="hero-tab-btn" data-scene="1" role="tab" aria-selected="false">
                    <div class="hero-tab-progress-track">
                        <div class="hero-tab-progress-fill" style="width: 0%;"></div>
                    </div>
                    <div class="hero-tab-meta">
                        <span class="hero-tab-num">02</span>
                        <span class="hero-tab-title">Vessel & Art Washbasins</span>
                    </div>
                </button>

                <button type="button" class="hero-tab-btn" data-scene="2" role="tab" aria-selected="false">
                    <div class="hero-tab-progress-track">
                        <div class="hero-tab-progress-fill" style="width: 0%;"></div>
                    </div>
                    <div class="hero-tab-meta">
                        <span class="hero-tab-num">03</span>
                        <span class="hero-tab-title">Thermostatic Brass & PVD</span>
                    </div>
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Fullscreen Video Lightbox Modal -->
<div class="theater-modal" id="theater-modal" role="dialog" aria-modal="true" aria-label="Factory Reel Video">
    <div class="theater-modal-content">
        <button type="button" class="theater-modal-close" id="theater-modal-close" aria-label="Close video">&times;</button>
        <video id="theater-modal-video" controls playsinline>
            <source src="<?= e(asset('videos/hero_scene_1.mp4')) ?>" type="video/mp4">
            Your browser does not support HTML5 video.
        </video>
    </div>
</div>

<!-- 2. Category Collections Section -->
<?php if (!empty($categories)): ?>
<section class="container" style="padding: 5rem 0 3rem;">
    <div class="section-header" style="text-align: center; max-width: 760px; margin: 0 auto 3rem;">
        <span class="eyebrow" style="color: #BFA16F; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.8rem;">
            European Standards • Vitreous China
        </span>
        <h2 style="font-size: 2.25rem; font-weight: 700; margin-top: 0.5rem; margin-bottom: 0.75rem;">
            Sanitary Ware & Ceramic Collections
        </h2>
        <p style="color: #64748B; font-size: 1.05rem; line-height: 1.6;">
            Engineered with antibacterial glaze, ultra-low water absorption, and clean architectural geometry for commercial projects, luxury hospitality, and high-end residential developments.
        </p>
    </div>

    <div class="grid grid-3" style="gap: 2rem;">
        <?php foreach ($categories as $cat): ?>
            <a href="<?= e(route('products.index', ['category' => $cat['slug']])) ?>" class="card" style="text-decoration: none; color: inherit; transition: transform 0.3s ease, box-shadow 0.3s ease; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column;">
                <div style="height: 220px; background: #FAFAFA; display: flex; align-items: center; justify-content: center; padding: 1.5rem; overflow: hidden;">
                    <img src="<?= e(asset($cat['image'] ?: 'images/products/prod_146_1620-111-a.jpg')) ?>" 
                         alt="<?= e($cat['name']) ?>" 
                         loading="lazy" 
                         style="max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.4s ease;">
                </div>
                <div class="card-body" style="padding: 1.5rem; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.4rem;"><?= e($cat['name']) ?></h3>
                        <p style="font-size: 0.875rem; color: #64748B; line-height: 1.5; margin: 0;"><?= e($cat['description'] ?? 'European Vitreous China collection.') ?></p>
                    </div>
                    <div style="margin-top: 1.25rem; font-weight: 600; color: #122920; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>View Products</span> &rarr;
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- 3. Featured Masterpieces Section -->
<?php if (!empty($featuredProducts)): ?>
<section class="container" style="padding: 3rem 0 5rem;">
    <div class="section-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span class="eyebrow" style="color: #BFA16F; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.8rem;">
                Factory Direct • Export Catalog
            </span>
            <h2 style="font-size: 2.25rem; font-weight: 700; margin-top: 0.5rem; margin-bottom: 0;">
                Featured Architectural Models
            </h2>
        </div>
        <a href="<?= e(route('products.index')) ?>" class="btn btn-secondary" style="padding: 0.6rem 1.25rem;">
            View All 283 Products &rarr;
        </a>
    </div>

    <div class="grid grid-3" style="gap: 2rem;">
        <?php foreach ($featuredProducts as $product): ?>
            <div class="card" style="border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="position: relative; height: 230px; background: #FFFFFF; display: flex; align-items: center; justify-content: center; padding: 1.5rem; border-bottom: 1px solid #E2E8F0;">
                    <span style="position: absolute; top: 1rem; left: 1rem; font-family: var(--font-mono, monospace); font-size: 0.7rem; background: #F1F5F9; color: #475569; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600;">
                        SKU: <?= e($product['sku']) ?>
                    </span>
                    <img src="<?= e(asset($product['image'] ?: 'images/products/prod_146_1620-111-a.jpg')) ?>" 
                         alt="<?= e($product['name']) ?>" 
                         loading="lazy" 
                         style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </div>
                <div class="card-body" style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; flex-grow: 1;">
                    <div>
                        <h4 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 0.4rem; color: #0F172A;">
                            <?= e($product['name']) ?>
                        </h4>
                        <p style="font-size: 0.85rem; color: #64748B; margin-bottom: 1rem; line-height: 1.5;">
                            Vitreous China sanitary ceramic with antibacterial surface.
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 1rem; border-top: 1px solid #F1F5F9;">
                        <span style="font-size: 1.25rem; font-weight: 700; color: #122920;">
                            <?= $product['price'] > 0 ? '$' . number_format((float) $product['price'], 2) : 'B2B Inquiry' ?>
                        </span>
                        <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I would like to inquire about specifications and volume export pricing for SKU: ' . $product['sku']) ?>" 
                           target="_blank" rel="noopener" class="btn btn-primary" style="padding: 0.5rem 0.9rem; font-size: 0.8125rem;">
                            💬 Inquire
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- 4. European Engineering & Manufacturing Standards -->
<section id="standards" style="background: #F8FAFC; padding: 5rem 0; border-top: 1px solid #E2E8F0; border-bottom: 1px solid #E2E8F0;">
    <div class="container">
        <div class="section-header" style="text-align: center; max-width: 720px; margin: 0 auto 3.5rem;">
            <span class="eyebrow" style="color: #BFA16F; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.8rem;">
                Factory Engineering & Quality
            </span>
            <h2 style="font-size: 2.25rem; font-weight: 700; margin-top: 0.5rem; margin-bottom: 0.75rem;">
                Architectural Standards You Can Rely On
            </h2>
            <p style="color: #64748B; font-size: 1.05rem; line-height: 1.6;">
                Every sanitary piece leaving our production line is verified against European performance, acoustic, and hydraulic standards.
            </p>
        </div>

        <div class="grid grid-4" style="gap: 1.75rem;">
            <div class="card" style="padding: 2rem; border-radius: 8px; background: #FFFFFF;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🛡️</div>
                <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 0.5rem;">EN 997 & CE Certified</h3>
                <p style="font-size: 0.875rem; color: #64748B; line-height: 1.6; margin: 0;">
                    Complete compliance with European Class 1 flush performance, acoustic dampening, and mechanical load tolerance up to 400 kg.
                </p>
            </div>

            <div class="card" style="padding: 2rem; border-radius: 8px; background: #FFFFFF;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔥</div>
                <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 0.5rem;">1,250°C Vitreous Firing</h3>
                <p style="font-size: 0.875rem; color: #64748B; line-height: 1.6; margin: 0;">
                    High-density molecular fusion produces ultra-low water absorption (&lt;0.5%), guaranteeing zero odor absorption and lifetime crack resistance.
                </p>
            </div>

            <div class="card" style="padding: 2rem; border-radius: 8px; background: #FFFFFF;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">✨</div>
                <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 0.5rem;">Nano Antibacterial Glaze</h3>
                <p style="font-size: 0.875rem; color: #64748B; line-height: 1.6; margin: 0;">
                    Micro-poreless ionic surface finish repels dirt, reduces cleaning chemical requirements, and prevents bacterial formation.
                </p>
            </div>

            <div class="card" style="padding: 2rem; border-radius: 8px; background: #FFFFFF;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🌍</div>
                <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 0.5rem;">34+ Export Nations</h3>
                <p style="font-size: 0.875rem; color: #64748B; line-height: 1.6; margin: 0;">
                    Strategic logistics hub in Gaziantep, Turkey, providing rapid container shipments to European, UK, and Middle Eastern construction projects.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 5. Corporate Entity & Export Dispatch Information -->
<section id="factory" class="container" style="padding: 5rem 0;">
    <div style="background: linear-gradient(135deg, #122920 0%, #0B1612 100%); color: #FFFFFF; border-radius: 12px; padding: 3.5rem; box-shadow: 0 20px 50px rgba(0,0,0,0.15);">
        <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 3rem; align-items: center;">
            <div>
                <span class="eyebrow" style="color: #BFA16F; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.8rem;">
                    Official Manufacturer & Exporter
                </span>
                <h3 style="font-size: 1.85rem; font-weight: 700; margin-top: 0.5rem; margin-bottom: 1rem; color: #FFFFFF;">
                    LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ
                </h3>
                <p style="font-size: 1.05rem; color: #CBD5E1; line-height: 1.7; margin-bottom: 1.5rem;">
                    We supply architects, project contractors, sanitary distributors, and retail chains across Europe with certified Vitreous China fixtures.
                </p>
                <div style="display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.95rem; color: #94A3B8;">
                    <div>📍 <strong>Facility:</strong> Gaziantep, Turkey</div>
                    <div>📞 <strong>Hotline & WhatsApp:</strong> <a href="tel:+908503040817" style="color: #BFA16F; text-decoration: none;">+90 850 3040 817</a></div>
                    <div>✉️ <strong>Direct Export Email:</strong> <a href="mailto:info@lufly.tr" style="color: #BFA16F; text-decoration: none;">info@lufly.tr</a></div>
                </div>
            </div>

            <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 8px; padding: 2rem; backdrop-filter: blur(10px); text-align: center;">
                <h4 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.75rem; color: #FFFFFF;">B2B Tender Inquiries</h4>
                <p style="font-size: 0.875rem; color: #94A3B8; margin-bottom: 1.5rem; line-height: 1.5;">
                    Need technical DWG drawings, CE certificates, or container volume quotation? Connect directly with our export team.
                </p>
                <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I am requesting technical documentation, DWG files, and container export pricing.') ?>" 
                   target="_blank" rel="noopener" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.85rem 1.25rem;">
                    💬 Open WhatsApp Direct Chat
                </a>
            </div>
        </div>
    </div>
</section>
