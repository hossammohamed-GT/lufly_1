<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
/** @var Core\View\View $view */
/** @var array $categories */
/** @var array $featuredProducts */
/** @var string $locale */
?>

<!-- ==========================================================================
     SECTION 1: HERO MONOLITH — DIRECTION 01 / 10 (Aquatic Luxury & Pure Flow)
     ========================================================================== -->
<section class="hero-monolith">
    <div class="container">
        <div class="hero-grid">
            <!-- Left Column: Architectural Voice -->
            <div>
                <div class="direction-badge">
                    <span class="direction-dot"></span>
                    <span>Direction 01 / 10 &middot; Tidal Monolith &middot; 2026</span>
                </div>

                <h1 class="hero-heading">
                    Pure flow.<br>
                    <span class="accent-teal">Designed to be felt.</span>
                </h1>

                <p class="hero-subheading">
                    Make water feel structural. Let every line carry the confidence of a precisely engineered fixture. Premium mixers, architectural vitreous ceramics, and smart water rituals shaped for Europe's finest specifications.
                </p>

                <div class="hero-cta-group">
                    <a href="<?= e(route('products.index')) ?>" class="btn-primary-teal">
                        <span>Explore 2026 Collections</span>
                        <span>&rarr;</span>
                    </a>
                    <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, please provide the 2026 Master BIM/CAD specifications and B2B pricing.') ?>" 
                       target="_blank" rel="noopener" class="btn-secondary-glass">
                        <span>📥 Request Master BIM / CAD</span>
                    </a>
                </div>

                <!-- Key Technical Metrics -->
                <div class="hero-metrics">
                    <div class="metric-item">
                        <span class="metric-value">1,250°C</span>
                        <span class="metric-label">Robotic Kiln China</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-value">500k</span>
                        <span class="metric-label">Tested Tap Cycles</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-value">34+</span>
                        <span class="metric-label">Export Nations</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Floating Monolith Fixture Card -->
            <div>
                <div class="hero-stage-card">
                    <div class="hero-stage-content">
                        <div class="hero-stage-header">
                            <span class="hero-stage-tag">Flagship Specification</span>
                            <span class="hero-stage-cycle">EN 817 &middot; PVD Rose Gold</span>
                        </div>

                        <div class="hero-stage-image-wrap">
                            <img src="<?= e(asset('images/finishes/brushed-rose-gold.jpg')) ?>" 
                                 alt="LUFLY Architectural Basin Mixer" 
                                 class="hero-stage-img"
                                 id="hero-banner-img">
                        </div>

                        <div class="hero-stage-footer">
                            <div class="hero-stage-meta">
                                <h3>DUERO High-Rise Vessel Mixer</h3>
                                <p>Solid Brass CW617N &middot; Neoperl® Aerator</p>
                            </div>
                            <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I would like to inquire about the DUERO High-Rise Vessel Mixer in Brushed Rose Gold.') ?>" 
                               target="_blank" rel="noopener" class="btn-primary-teal" style="padding: 10px 18px; font-size: 13px;">
                                Inquire &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
