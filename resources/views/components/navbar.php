<?php
/** @var Core\View\View $view */
?>
<!-- Top Utility Bar (Deante-Inspired Manufacturing Credentials) -->
<div class="lufly-topbar">
    <div class="container lufly-topbar-inner">
        <div class="lufly-topbar-credentials">
            <span class="lufly-topbar-item">🛡️ <b>EN 997 & CE</b> European Certified</span>
            <span class="lufly-topbar-item">🔥 <b>1,250°C</b> Robotic Kiln Vitreous China</span>
            <span class="lufly-topbar-item">🏭 <b>LUFLY İNŞAAT SAN. VE TİC. LTD. ŞTİ.</b></span>
        </div>
        <div class="lufly-topbar-contact">
            <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I am requesting B2B export specification sheets & master price catalog.') ?>" target="_blank" rel="noopener">
                💬 +90 850 3040 817
            </a>
            <span style="color: var(--lufly-border);">|</span>
            <a href="mailto:info@lufly.tr" style="color: var(--lufly-muted);">info@lufly.tr</a>
        </div>
    </div>
</div>

<!-- Main Sticky Header -->
<header class="lufly-nav">
    <div class="container lufly-nav-main">
        <!-- Brand Identity -->
        <a class="lufly-brand" href="<?= e(route('home')) ?>">
            <img src="<?= e(asset('frontend/design-system/logo.png')) ?>" alt="LUFLY" class="lufly-brand-logo">
            <div class="lufly-brand-text">
                <span class="lufly-brand-title">LUFLY</span>
                <span class="lufly-brand-subtitle">Sanitary Architecture</span>
            </div>
        </a>

        <!-- Architectural Search Bar with Real-Time Dropdown -->
        <div class="lufly-search-wrapper">
            <span class="lufly-search-icon">🔍</span>
            <input type="search" class="lufly-search-input" placeholder="Search sanitary fixtures, mixers, washbasins, SKU code..." aria-label="Search LUFLY catalog">
            <div class="lufly-search-results"></div>
        </div>

        <!-- Action Controls -->
        <div class="lufly-nav-actions">
            <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY Export Desk, I would like to request official B2B wholesale pricing.') ?>" 
               target="_blank" rel="noopener" class="lufly-btn-export">
                <span>💬 B2B Export Quote</span>
            </a>
            <?= $view->renderFile($view->resolvePath('components.theme-switcher'), []) ?>
            <?php if (auth()->check()): ?>
                <a href="<?= e(route('admin.dashboard')) ?>" style="font-size: 13px; font-weight: 600; color: var(--lufly-text);">Admin</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Secondary Category Subnavigation -->
    <div class="lufly-subnav">
        <div class="container lufly-subnav-inner">
            <div class="lufly-subnav-links">
                <a href="<?= e(route('products.index')) ?>" class="lufly-subnav-link">All Collections</a>
                <a href="<?= e(route('products.index', ['category_id' => 1])) ?>" class="lufly-subnav-link">Toilets & Bidets</a>
                <a href="<?= e(route('products.index', ['category_id' => 2])) ?>" class="lufly-subnav-link">Washbasins</a>
                <a href="<?= e(route('products.index', ['category_id' => 4])) ?>" class="lufly-subnav-link">Faucets & Mixers</a>
                <a href="#finishes" class="lufly-subnav-link">PVD Finishes</a>
                <a href="#inspiration" class="lufly-subnav-link">Inspirations</a>
                <a href="#rituals" class="lufly-subnav-link">Water Hydrodynamics</a>
                <a href="#corporate" class="lufly-subnav-link">Standards & Entity</a>
            </div>
            <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, please send the 2026 Master BIM/CAD specifications and technical catalog.') ?>" target="_blank" rel="noopener" class="lufly-subnav-badge">
                📥 2026 BIM / CAD Master Catalog
            </a>
        </div>
    </div>
</header>
