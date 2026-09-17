<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
/** @var Core\View\View $view */
/** @var array $categories */
/** @var array $featuredProducts */
/** @var string $locale */
?>

<!-- ==========================================================================
     SECTION 1: DEANTE-STYLE CINEMATIC HERO SLIDER
     ========================================================================== -->
<section class="deante-hero-container">
    <div class="deante-hero-viewport" id="hero-slider-viewport">
        <div class="deante-hero-track" id="hero-slider-track">
            <!-- Slide 1: Smart WC (Pure Wellness) -->
            <a href="<?= e(route('products.index', ['category_id' => 1])) ?>" class="deante-hero-slide" title="LUFLY Smart WC Pure Wellness">
                <img src="<?= e(asset('images/lifestyle/hero-slide-smart-wc-hd.png')) ?>" 
                     alt="LUFLY Silia Smart WC - Pure Wellness" 
                     class="slide-bg">
                <div class="deante-hero-overlay" style="opacity: 0; pointer-events: none;">
                    <div class="deante-hero-title-group">
                        <span class="deante-hero-script">Silia</span>
                        <span class="deante-hero-caps">WC SMART</span>
                    </div>
                    <div class="deante-hero-subtitle">pure wellness</div>
                    <span class="deante-hero-cta">See products &rarr;</span>
                </div>
            </a>

            <!-- Slide 2: Luxury Faucets & Flow -->
            <a href="<?= e(route('products.index', ['category_id' => 4])) ?>" class="deante-hero-slide" title="LUFLY Architectural Faucets & Mixers">
                <img src="<?= e(asset('images/lifestyle/hero-slide-faucets-hd.jpg')) ?>" 
                     alt="LUFLY Architectural Basin Mixers" 
                     class="slide-bg">
                <div class="deante-hero-overlay">
                    <div class="deante-hero-title-group">
                        <span class="deante-hero-script">Duero</span>
                        <span class="deante-hero-caps">HYDRO FLOW</span>
                    </div>
                    <div class="deante-hero-subtitle">pure water</div>
                    <span class="deante-hero-cta">See products &rarr;</span>
                </div>
            </a>

            <!-- Slide 3: Executive Spa Suite -->
            <a href="<?= e(route('products.index')) ?>" class="deante-hero-slide" title="LUFLY Architectural Sanctuaries">
                <img src="<?= e(asset('images/lifestyle/spa-suite.jpg')) ?>" 
                     alt="LUFLY Luxury Calacatta Suite" 
                     class="slide-bg">
                <div class="deante-hero-overlay" style="background: radial-gradient(circle, rgba(14,20,22,0.7) 0%, transparent 85%); padding: 60px 40px; border-radius: 20px;">
                    <div class="deante-hero-title-group">
                        <span class="deante-hero-script">Calacatta</span>
                        <span class="deante-hero-caps">SUITE</span>
                    </div>
                    <div class="deante-hero-subtitle">pure luxury</div>
                    <span class="deante-hero-cta">See products &rarr;</span>
                </div>
            </a>
        </div>

        <!-- Inside Bottom-Right Pagination Dots -->
        <div class="deante-hero-dots" id="hero-slider-dots">
            <span class="deante-hero-dot is-active" data-slide="0"></span>
            <span class="deante-hero-dot" data-slide="1"></span>
            <span class="deante-hero-dot" data-slide="2"></span>
        </div>
    </div>

    <!-- Outside Bottom-Right Arrow Buttons -->
    <div class="deante-hero-controls">
        <button type="button" class="deante-hero-arrow" id="hero-prev-btn" aria-label="Previous Slide">&lt;</button>
        <button type="button" class="deante-hero-arrow" id="hero-next-btn" aria-label="Next Slide">&gt;</button>
    </div>
</section>

<!-- ==========================================================================
     SECTION 2: ENGINEERING TRUST PILLARS (Deante-Inspired Badges)
     ========================================================================== -->
<section class="trust-bar">
    <div class="container">
        <div class="trust-grid">
            <div class="trust-card">
                <div class="trust-icon">🛡️</div>
                <div class="trust-info">
                    <h4>10-Year Factory Guarantee</h4>
                    <p>Rigorous European EN 997 & EN 817 quality certification with 400 kg load tolerance.</p>
                </div>
            </div>

            <div class="trust-card">
                <div class="trust-icon">💧</div>
                <div class="trust-info">
                    <h4>PRO-ECO Hydrodynamics</h4>
                    <p>Swiss Neoperl® precision aerators saving up to 45% water without compromising volume.</p>
                </div>
            </div>

            <div class="trust-card">
                <div class="trust-icon">🔥</div>
                <div class="trust-info">
                    <h4>1,250°C Vitreous Ceramic</h4>
                    <p>Sub-0.35% water absorption rate with poreless nano-antibacterial hygienic glazing.</p>
                </div>
            </div>

            <div class="trust-card">
                <div class="trust-icon">🚚</div>
                <div class="trust-info">
                    <h4>Direct European Logistics</h4>
                    <p>Strategic export dispatch from Gaziantep manufacturing facilities to 34+ global markets.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     SECTION 3: INTERACTIVE FINISH / COLOR SELECTOR ("Choose color of fittings")
     Exact requested feature from user's Deante brand reference
     ========================================================================== -->
<section class="finishes-section" id="finishes">
    <div class="container">
        <div class="section-header-center">
            <span class="section-tag">Surface Metallurgy & Finishes</span>
            <h2 class="section-title">Choose Color of Fittings</h2>
            <p class="section-subtitle">
                Six architectural finishes engineered with physical vapor deposition (PVD) titanium plasma and multi-layer electroplating for lifelong scratch and chemical resistance.
            </p>
        </div>

        <!-- 6 Interactive Swatches -->
        <div class="finishes-swatch-row">
            <button type="button" class="finish-swatch-btn is-active" data-finish="brushed-rose-gold" aria-label="Brushed Rose Gold">
                <span class="swatch-circle swatch-brushed-rose-gold"></span>
                <span class="swatch-label">Rose Gold</span>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="chrome" aria-label="Mirror Chrome">
                <span class="swatch-circle swatch-chrome"></span>
                <span class="swatch-label">Chrome</span>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="brushed-gold" aria-label="Brushed Gold">
                <span class="swatch-circle swatch-brushed-gold"></span>
                <span class="swatch-label">Brushed Gold</span>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="matte-black" aria-label="Matte Black">
                <span class="swatch-circle swatch-matte-black"></span>
                <span class="swatch-label">Matte Black</span>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="brushed-steel" aria-label="Brushed Steel">
                <span class="swatch-circle swatch-brushed-steel"></span>
                <span class="swatch-label">Brushed Steel</span>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="gunmetal" aria-label="Gunmetal Grey">
                <span class="swatch-circle swatch-gunmetal"></span>
                <span class="swatch-label">Gunmetal</span>
            </button>
        </div>

        <!-- Interactive Split Showcase -->
        <div class="finish-showcase-box">
            <div class="finish-showcase-image-wrap">
                <img src="<?= e(asset('images/finishes/brushed-rose-gold.jpg')) ?>" 
                     alt="LUFLY Finish Showcase" 
                     id="finish-showcase-img" 
                     class="finish-showcase-img">
            </div>

            <div class="finish-showcase-details">
                <span class="finish-spec-pill" id="finish-tag">PVD TITANIUM VAPOR DEPOSITION &middot; 10-YEAR COLOR STABILITY</span>
                <h3 class="finish-title" id="finish-title">Brushed Rose Gold (PVD)</h3>
                <p class="finish-desc" id="finish-desc">
                    An opulent, warm metallic hue crafted via vacuum plasma PVD. Ultra-resistant to micro-scratches, finger marks, and corrosion in coastal and humid spa environments.
                </p>

                <div class="finish-tech-specs">
                    <div class="tech-spec-item">
                        <span id="finish-base-label">Base Alloy</span>
                        <b id="finish-base">Solid Brass CW617N</b>
                    </div>
                    <div class="tech-spec-item">
                        <span id="finish-coating-label">Coating Spec</span>
                        <b id="finish-coating">PVD Titanium 0.4µm</b>
                    </div>
                    <div class="tech-spec-item">
                        <span id="finish-cartridge-label">Internal Cartridge</span>
                        <b id="finish-cartridge">Kerox® Hungary 35mm Ceramic</b>
                    </div>
                    <div class="tech-spec-item">
                        <span id="finish-aerator-label">Aerator Standard</span>
                        <b id="finish-aerator">Neoperl® Coin-Slot Pro-Eco 5.7 L/min</b>
                    </div>
                </div>

                <div style="display: flex; gap: 16px; align-items: center;">
                    <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I am specifying the Brushed Rose Gold finish for an architectural project. Please share high-res renders and export pricing.') ?>" 
                       id="finish-wa-btn" 
                       target="_blank" 
                       rel="noopener" 
                       class="btn-primary-teal">
                        <span>💬 Inquire This Finish via WhatsApp</span>
                    </a>
                    <a href="<?= e(route('products.index', ['category_id' => 4])) ?>" class="btn-secondary-glass">
                        <span>View All Mixers</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     SECTION 4: "DESIGN YOUR SPACE" — CURATED ARCHITECTURAL CATEGORIES
     ========================================================================== -->
<section class="categories-section" id="categories">
    <div class="container">
        <div class="section-header-center">
            <span class="section-tag">Curated Collections</span>
            <h2 class="section-title">Design Your Space</h2>
            <p class="section-subtitle">
                Engineered ceramic suites, tapware, and wellness fixtures designed for monolithic architectural harmony.
            </p>
        </div>

        <div class="category-mosaic">
            <!-- 1. Wall-Hung Toilets -->
            <a href="<?= e(route('products.index', ['category_id' => 1])) ?>" class="category-card-monolith">
                <img src="<?= e(asset('images/products/prod_146_1620-111-a.jpg')) ?>" alt="Wall-Hung Toilets" class="category-card-bg">
                <div class="category-card-gradient"></div>
                <div class="category-card-info">
                    <h3>Toilets & Bidets</h3>
                    <p>Rimless EN 997 China &middot; Soft-Close</p>
                </div>
            </a>

            <!-- 2. Washbasins -->
            <a href="<?= e(route('products.index', ['category_id' => 2])) ?>" class="category-card-monolith">
                <img src="<?= e(asset('images/products/prod_180_1610-242-65.jpg')) ?>" alt="Washbasins" class="category-card-bg">
                <div class="category-card-gradient"></div>
                <div class="category-card-info">
                    <h3>Washbasins</h3>
                    <p>Countertop & Monolithic Sinks</p>
                </div>
            </a>

            <!-- 3. Faucets & Mixers -->
            <a href="<?= e(route('products.index', ['category_id' => 4])) ?>" class="category-card-monolith">
                <img src="<?= e(asset('images/finishes/brushed-gold.jpg')) ?>" alt="Faucets & Mixers" class="category-card-bg">
                <div class="category-card-gradient"></div>
                <div class="category-card-info">
                    <h3>Faucets & Mixers</h3>
                    <p>Solid Brass &middot; PVD Finishes</p>
                </div>
            </a>

            <!-- 4. Showers & Wellness -->
            <a href="<?= e(route('products.index')) ?>" class="category-card-monolith">
                <img src="<?= e(asset('images/lifestyle/luxury-shower.png')) ?>" alt="Showers" class="category-card-bg">
                <div class="category-card-gradient"></div>
                <div class="category-card-info">
                    <h3>Concealed Showers</h3>
                    <p>Thermostatic 38°C Safety Core</p>
                </div>
            </a>

            <!-- 5. Commercial Sanitary -->
            <a href="<?= e(route('products.index', ['category_id' => 3])) ?>" class="category-card-monolith">
                <img src="<?= e(asset('images/products/prod_2050_1690-000.jpg')) ?>" alt="Urinals" class="category-card-bg">
                <div class="category-card-gradient"></div>
                <div class="category-card-info">
                    <h3>Commercial Sanitary</h3>
                    <p>Radar Sensor Urinals & Fittings</p>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- ==========================================================================
     SECTION 5: "GET INSPIRED" — ARCHITECTURAL ROOM CONCEPTS (Deante Reference)
     ========================================================================== -->
<section class="inspiration-section" id="inspiration">
    <div class="container">
        <div class="section-header-center">
            <span class="section-tag">Spatial Showcase</span>
            <h2 class="section-title">Get Inspired</h2>
            <p class="section-subtitle">
                Explore real architectural installations featuring LUFLY fixtures in luxury residential and hospitality environments.
            </p>
        </div>

        <div class="inspiration-carousel">
            <!-- Room 1 -->
            <div class="inspiration-card">
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/spa-suite.jpg')) ?>" alt="The Nordic Spa Suite" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag">Spa & Wellness</span>
                    <h4>The Calacatta Executive Suite</h4>
                    <p>Freestanding soaking tub paired with brushed gold floor-mounted mixer and monolithic vitreous sanitary ware.</p>
                </div>
            </div>

            <!-- Room 2 -->
            <div class="inspiration-card">
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/modern-bathroom.png')) ?>" alt="Modern Bathroom" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag">Minimalist Living</span>
                    <h4>The Minimalist Oak En-Suite</h4>
                    <p>Matte black concealed tapware creating a tactile contrast against natural micro-cement and warm timber.</p>
                </div>
            </div>

            <!-- Room 3 -->
            <div class="inspiration-card">
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/minimal-basin.png')) ?>" alt="Powder Room" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag">Architectural Powder Room</span>
                    <h4>The Serene Japandi Retreat</h4>
                    <p>Pristine white ceramic vessel basin with high-rise rose gold faucet and atmospheric indirect lighting.</p>
                </div>
            </div>

            <!-- Room 4 -->
            <div class="inspiration-card">
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/kitchen-suite.png')) ?>" alt="Kitchen Suite" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag">Gourmet Architecture</span>
                    <h4>The Contemporary Culinary Suite</h4>
                    <p>Architectural pull-out dual-spray chef mixer in brushed steel over an undermount composite quartz sink.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     SECTION 6: WATER RITUALS & HYDRODYNAMICS (Sensory Water Flow Feature)
     ========================================================================== -->
<section class="rituals-section" id="rituals">
    <div class="container">
        <div class="section-header-center">
            <span class="section-tag">Hydrodynamic Engineering</span>
            <h2 class="section-title">Water Rituals Shaped by Precision</h2>
            <p class="section-subtitle">
                Every drop is choreographed to deliver sensory comfort, silent operation, and responsible conservation.
            </p>
        </div>

        <div class="rituals-grid">
            <div class="ritual-card">
                <div class="ritual-number">01 / HYDRODYNAMIC FLOW</div>
                <h3>Air-Infused Crystal Stream</h3>
                <p>
                    Precision aeration enriches the water stream with micro-bubbles of air, creating a velvety, dense flow that eliminates splashing and cuts water consumption by up to 45%.
                </p>
                <span class="ritual-metric-tag">Pro-Eco 5.7 L/min Standard</span>
            </div>

            <div class="ritual-card">
                <div class="ritual-number">02 / CERAMIC DISCS</div>
                <h3>500,000 Cycle Durability</h3>
                <p>
                    Diamond-hard ceramic discs from Kerox® Hungary guarantee leak-free operation, buttery lever movement, and complete resistance to aggressive limescale and mineral deposits.
                </p>
                <span class="ritual-metric-tag">Zero Drift &middot; Class 1 Acoustic</span>
            </div>

            <div class="ritual-card">
                <div class="ritual-number">03 / THERMOSTAT SAFETY</div>
                <h3>Instant 0.2s Temperature Balance</h3>
                <p>
                    Concealed thermostatic cartridges respond to pressure shifts in 0.2 seconds, holding water precisely at 38°C to eliminate cold shocks and scalding risks for hotel guests and families.
                </p>
                <span class="ritual-metric-tag">38°C Safety Lock Standard</span>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     SECTION 7: FEATURED MASTERPIECES (Real SQLite Database Products)
     ========================================================================== -->
<section class="masterpieces-section">
    <div class="container">
        <div style="display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 40px;">
            <div>
                <span class="section-tag">Masterpiece Registry</span>
                <h2 class="section-title" style="margin-bottom: 0;">Featured Architectural Fixtures</h2>
            </div>
            <a href="<?= e(route('products.index')) ?>" class="btn-secondary-glass">
                <span>View Full Catalog (280+ Fixtures)</span>
                <span>&rarr;</span>
            </a>
        </div>

        <div class="products-grid">
            <?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend'); foreach (array_slice($featuredProducts, 0, 4) as $product): ?>
                <?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
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
                            <span>💬 Inquire B2B</span>
                        </a>
                        <a href="<?= e(route('products.show', ['slug' => $product['slug'] ?? $product['id']])) ?>" style="font-size: 12px; color: var(--lufly-text); font-weight: 600;">
                            Tech Sheet &rarr;
                        </a>
                    </div>
                </div>
            <?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend'); endforeach; ?>
        </div>
    </div>
</section>

<!-- ==========================================================================
     SECTION 8: OFFICIAL CORPORATE ENTITY & GAZIANTEP MANUFACTURING HUB
     ========================================================================== -->
<section class="corporate-section" id="corporate">
    <div class="container">
        <div class="corporate-box">
            <div class="corporate-info">
                <span class="corporate-legal-badge">Official Manufacturer Entity</span>
                <h3>LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ</h3>
                <p>
                    LUFLY operates modern robotic ceramic kilns and CNC brassware manufacturing facilities headquartered in Gaziantep, Turkey. We engineer sanitary architecture for prestigious hotel projects, high-end developers, and commercial specifiers across the European Union, the Middle East, and beyond.
                </p>

                <ul class="corporate-details-list">
                    <li><b>Legal Name:</b> LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ</li>
                    <li><b>Headquarters:</b> Gaziantep, Turkey (Direct European Logistics Corridor)</li>
                    <li><b>Compliance:</b> CE Mark &middot; EN 997 &middot; EN 817 &middot; ISO 9001:2015</li>
                    <li><b>Export Office:</b> +90 850 3040 817 &middot; info@lufly.tr</li>
                </ul>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="glass-panel" style="padding: 24px; text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 8px;">📐</div>
                    <h4 style="margin: 0 0 8px 0; font-size: 16px; color: var(--lufly-text);">Architectural Specification Desk</h4>
                    <p style="font-size: 13px; color: var(--lufly-muted); margin: 0 0 16px 0;">
                        Download complete CAD blocks, BIM Revit families, and master price books for project tenders.
                    </p>
                    <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, please send the complete 2026 Architect Tender Package (CAD/BIM/PDF).') ?>" 
                       target="_blank" rel="noopener" class="btn-primary-teal" style="width: 100%; justify-content: center; box-sizing: border-box;">
                        Request Architect Tender Pack
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
