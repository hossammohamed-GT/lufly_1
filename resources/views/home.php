<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
/** @var Core\View\View $view */
/** @var array $categories */
/** @var array $featuredProducts */
/** @var string $locale */
?>

<!-- ==========================================================================
     SECTION 1: DEANTE-STYLE CINEMATIC HERO SLIDER (4 BESPOKE LUXURY SLIDES)
     "Pure flow. Designed to be felt." & Aquatic Glassmorphism
     ========================================================================== -->
<section class="deante-hero-container">
    <div class="deante-hero-viewport" id="hero-slider-viewport">
        <div class="deante-hero-track" id="hero-slider-track">
            <!-- Slide 1: Smart WC (Silia WC SMART — Pure Wellness) -->
            <div class="deante-hero-slide" title="LUFLY Silia Smart WC Architecture">
                <img src="<?= e(asset('images/lifestyle/hero-slide-smart-wc.jpg')) ?>" 
                     alt="LUFLY Silia Smart WC - Pure Wellness" 
                     class="slide-bg">
                <div class="deante-hero-overlay-luxury">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span>LUFLY · TIDAL MONOLITH SYSTEM</span>
                    </div>
                    <div class="deante-hero-heading-luxury">
                        <span class="deante-hero-script-luxury">Silia</span>
                        <span class="deante-hero-caps-luxury">WC SMART</span>
                    </div>
                    <p class="deante-hero-desc-luxury">
                        Pure flow. Designed to be felt. Wall-hung rimless ceramic architecture with integrated hydro-wellness, thermal bidet comfort, and soft ambient cyan lighting.
                    </p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index', ['category_id' => 1])) ?>" class="deante-hero-cta-primary">
                            <span>See Smart Toilets &rarr;</span>
                        </a>
                        <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I would like to inquire about the Silia Smart WC specification and project pricing.') ?>" 
                           target="_blank" rel="noopener" class="deante-hero-cta-secondary">
                            <span>WhatsApp Specifier</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Slide 2: Luxury Faucets & Flow (Duero Hydro Flow) -->
            <div class="deante-hero-slide" title="LUFLY Architectural Faucets & Mixers">
                <img src="<?= e(asset('images/lifestyle/hero-slide-basin-mixer.jpg')) ?>" 
                     alt="LUFLY Architectural Basin Mixers" 
                     class="slide-bg">
                <div class="deante-hero-overlay-luxury">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span>LUFLY · HYDRODYNAMIC FLOW</span>
                    </div>
                    <div class="deante-hero-heading-luxury">
                        <span class="deante-hero-script-luxury">Duero</span>
                        <span class="deante-hero-caps-luxury">PVD TAPWARE</span>
                    </div>
                    <p class="deante-hero-desc-luxury">
                        Architectural brassware crafted with titanium vapor deposition. Air-infused laminar streams powered by Swiss Neoperl® precision aerators.
                    </p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index', ['category_id' => 4])) ?>" class="deante-hero-cta-primary">
                            <span>View All Mixers &rarr;</span>
                        </a>
                        <a href="#finishes" class="deante-hero-cta-secondary">
                            <span>Explore 6 PVD Finishes</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Slide 3: Executive Spa Suite (Calacatta Monolithic Sanctuary) -->
            <div class="deante-hero-slide" title="LUFLY Architectural Sanctuaries">
                <img src="<?= e(asset('images/lifestyle/hero-slide-freestanding-tub.jpg')) ?>" 
                     alt="LUFLY Luxury Calacatta Suite" 
                     class="slide-bg">
                <div class="deante-hero-overlay-luxury">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span>LUFLY · SPATIAL ARCHITECTURE</span>
                    </div>
                    <div class="deante-hero-heading-luxury">
                        <span class="deante-hero-script-luxury">Calacatta</span>
                        <span class="deante-hero-caps-luxury">MONOLITH SUITE</span>
                    </div>
                    <p class="deante-hero-desc-luxury">
                        Museum-grade composite stone soaking sanctuaries engineered for ultra-luxury residential projects and boutique hotel suites worldwide.
                    </p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index')) ?>" class="deante-hero-cta-primary">
                            <span>Explore Sanctuaries &rarr;</span>
                        </a>
                        <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, please share 3D CAD/BIM models for freestanding tubs.') ?>" 
                           target="_blank" rel="noopener" class="deante-hero-cta-secondary">
                            <span>Request BIM Models</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Slide 4: Concealed Hydrotherapy Shower (Kallisto Thermostatic Sanctuary) -->
            <div class="deante-hero-slide" title="LUFLY Concealed Thermostatic Shower Systems">
                <img src="<?= e(asset('images/lifestyle/hero-slide-rain-shower.jpg')) ?>" 
                     alt="LUFLY Kallisto Concealed Thermostatic Shower" 
                     class="slide-bg">
                <div class="deante-hero-overlay-luxury">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span>LUFLY · HYDROTHERAPY WELLNESS</span>
                    </div>
                    <div class="deante-hero-heading-luxury">
                        <span class="deante-hero-script-luxury">Kallisto</span>
                        <span class="deante-hero-caps-luxury">RAIN SHOWER</span>
                    </div>
                    <p class="deante-hero-desc-luxury">
                        Concealed 38°C anti-scald hydrotherapy rain showers with illuminated micro-droplet mist and aerospace gunmetal titanium controls.
                    </p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index')) ?>" class="deante-hero-cta-primary">
                            <span>Discover Showers &rarr;</span>
                        </a>
                        <a href="#rituals" class="deante-hero-cta-secondary">
                            <span>Hydrodynamic Rituals</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inside Bottom-Right Pagination Dots (4 Slides) -->
        <div class="deante-hero-dots" id="hero-slider-dots">
            <span class="deante-hero-dot is-active" data-slide="0" aria-label="Slide 1"></span>
            <span class="deante-hero-dot" data-slide="1" aria-label="Slide 2"></span>
            <span class="deante-hero-dot" data-slide="2" aria-label="Slide 3"></span>
            <span class="deante-hero-dot" data-slide="3" aria-label="Slide 4"></span>
        </div>
    </div>

    <!-- Outside Bottom-Right Arrow Buttons -->
    <div class="deante-hero-controls">
        <button type="button" class="deante-hero-arrow" id="hero-prev-btn" aria-label="Previous Slide">&lt;</button>
        <button type="button" class="deante-hero-arrow" id="hero-next-btn" aria-label="Next Slide">&gt;</button>
    </div>
</section>

<!-- ========================================================================
     SECTION 2: ENGINEERING TRUST PILLARS
     ========================================================================== -->
<section class="trust-bar" aria-labelledby="trust-title">
    <div class="trust-inner">
        <div class="trust-heading">
            <span class="trust-kicker">Our advantage</span>
            <h2 id="trust-title">Built for a <em>Better</em> Tomorrow</h2>
            <div class="trust-heading-rule" aria-hidden="true"></div>
            <p>European quality. Sustainable innovation. Global reach.</p>
        </div>

        <div class="trust-grid">
            <article class="trust-card">
                <div class="trust-icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48" role="img"><path d="M24 5 38 11v10c0 10-5.7 17.4-14 22-8.3-4.6-14-12-14-22V11l14-6Z"/><path d="m17 24 5 5 10-11"/></svg>
                </div>
                <div class="trust-stat"><strong>10</strong><span>YEAR</span></div>
                <span class="trust-accent" aria-hidden="true"></span>
                <h3>Factory Guarantee</h3>
                <p>Rigorous European EN 997 & EN 817 quality certification with 400 kg load tolerance.</p>
            </article>

            <article class="trust-card">
                <div class="trust-icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48" role="img"><path d="M24 6c-2 7-10 13-10 22a10 10 0 0 0 20 0C34 19 26 13 24 6Z"/><path d="M20 32c-1-3 0-6 3-8"/></svg>
                </div>
                <div class="trust-stat"><strong>45</strong><span>%</span></div>
                <span class="trust-accent" aria-hidden="true"></span>
                <h3>PRO-ECO Hydrodynamics</h3>
                <p>Swiss Neoperl® precision aerators saving up to 45% water without compromising volume.</p>
            </article>

            <article class="trust-card">
                <div class="trust-icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48" role="img"><path d="M24 7v24"/><path d="M19 12a5 5 0 1 1 10 0v18a9 9 0 1 1-10 0V12Z"/><circle cx="24" cy="36" r="3"/><path d="M32 15h4M32 21h4"/></svg>
                </div>
                <div class="trust-stat"><strong>1,250</strong><span>°C</span></div>
                <span class="trust-accent" aria-hidden="true"></span>
                <h3>Vitreous Ceramic</h3>
                <p>Sub-0.35% water absorption rate with poreless nano-antibacterial hygienic glazing.</p>
            </article>

            <article class="trust-card">
                <div class="trust-icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48" role="img"><circle cx="24" cy="24" r="16"/><path d="M8 24h32M24 8c5 5 7 10 7 16s-2 11-7 16M24 8c-5 5-7 10-7 16s2 11 7 16M10 16h28M10 32h28"/></svg>
                </div>
                <div class="trust-stat"><strong>34</strong><span>+</span></div>
                <span class="trust-accent" aria-hidden="true"></span>
                <h3>Global Markets</h3>
                <p>Strategic export dispatch from Gaziantep manufacturing facilities to 34+ global markets.</p>
            </article>
        </div>

        <div class="trust-footer-line" aria-hidden="true"><span></span><small>Quality today. A cleaner tomorrow.</small><span></span></div>
    </div>
</section>

<!-- ========================================================================
     SECTION 3: INTERACTIVE FINISH / COLOR SELECTOR
     ========================================================================== -->
<section class="finishes-section" id="finishes">
    <div class="finishes-backdrop" aria-hidden="true"></div>
    <div class="container finishes-container">
        <div class="finishes-heading-row">
            <div class="section-header-left">
                <span class="section-tag">Surface Metallurgy & Finishes</span>
                <h2 class="section-title">Choose the<br><em>Finish</em> of Your Space</h2>
                <p class="section-subtitle">Six architectural finishes engineered with Physical Vapor Deposition (PVD) technology for exceptional durability, refined aesthetics, and long-lasting resistance to scratches, corrosion, and harsh environments.</p>
            </div>
            <div class="finishes-heading-note">Finishes<br>that endure<br>beauty<br>that lasts<span></span></div>
        </div>

        <div class="finishes-swatch-row">
            <button type="button" class="finish-swatch-btn is-active" data-finish="brushed-rose-gold" aria-label="Brushed Rose Gold">
                <img src="<?= e(asset('images/finishes/brushed-rose-gold.jpg')) ?>" alt="Rose Gold finish"><span class="swatch-label">Rose Gold</span><small>PVD</small>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="chrome" aria-label="Mirror Chrome">
                <img src="<?= e(asset('images/finishes/chrome.jpg')) ?>" alt="Chrome finish"><span class="swatch-label">Chrome</span><small>PVD</small>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="brushed-gold" aria-label="Brushed Gold">
                <img src="<?= e(asset('images/finishes/brushed-gold.jpg')) ?>" alt="Brushed Gold finish"><span class="swatch-label">Brushed Gold</span><small>PVD</small>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="matte-black" aria-label="Matte Black">
                <img src="<?= e(asset('images/finishes/matte-black.jpg')) ?>" alt="Matte Black finish"><span class="swatch-label">Matte Black</span><small>PVD</small>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="brushed-steel" aria-label="Brushed Steel">
                <img src="<?= e(asset('images/finishes/brushed-steel.jpg')) ?>" alt="Brushed Steel finish"><span class="swatch-label">Brushed Steel</span><small>PVD</small>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="gunmetal" aria-label="Gunmetal Grey">
                <img src="<?= e(asset('images/finishes/gunmetal.jpg')) ?>" alt="Gunmetal finish"><span class="swatch-label">Gunmetal</span><small>PVD</small>
            </button>
        </div>

        <div class="finish-showcase-box">
            <div class="finish-showcase-image-wrap">
                <img src="<?= e(asset('images/finishes/brushed-rose-gold.jpg')) ?>" 
                     alt="LUFLY Brushed Rose Gold finish"
                     id="finish-showcase-img" 
                     class="finish-showcase-img">
                <span class="finish-image-caption">A finish<br>for a higher<br>standard</span>
            </div>

            <div class="finish-showcase-details">
                <div class="finish-detail-meta"><span class="finish-spec-pill" id="finish-tag">PVD TITANIUM VAPOR DEPOSITION</span><span>01 / 06</span></div>
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

                <div class="finish-actions">
                    <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I am specifying the Brushed Rose Gold finish for an architectural project. Please share high-res renders and export pricing.') ?>" 
                       id="finish-wa-btn" 
                       target="_blank" 
                       rel="noopener" 
                       class="btn-primary-teal">
                        <span>Inquire This Finish via WhatsApp</span>
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
                            <span>Inquire B2B</span>
                        </a>
                        <a href="<?= e(route('products.show', ['slug' => $product['slug'] ?? $product['id']])) ?>" style="font-size: 12px; color: var(--lufly-text); font-weight: 600;">
                            Tech Sheet &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
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
                    <div style="font-size: 18px; font-weight: 700; letter-spacing: 0.08em; margin-bottom: 12px; color: var(--lufly-teal);" aria-hidden="true">CAD / BIM</div>
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
