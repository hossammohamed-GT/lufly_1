<?php
/** @var Core\View\View $view */
?>
<!-- Deante-Style Minimalist Luxury Header -->
<header class="deante-header">
    <!-- Top Row: Logo, Wide Search Pill, User / Wishlist / Flag Icons -->
    <div class="deante-header-top">
        <div class="container deante-header-inner">
            <!-- Brand Logo -->
            <a class="deante-logo-wrap" href="<?= e(route('home')) ?>" title="LUFLY Sanitary Architecture">
                <img src="<?= e(asset('frontend/design-system/logo.png')) ?>" alt="LUFLY" class="deante-logo-img">
                <span class="deante-logo-text">lufly</span>
            </a>

            <!-- Wide Search Pill with Magnifying Glass on the Right -->
            <div class="deante-search-container">
                <input type="search" 
                       class="deante-search-pill lufly-search-input" 
                       placeholder="Magnetic" 
                       aria-label="Search LUFLY sanitary fixtures">
                <span class="deante-search-btn" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <div class="lufly-search-results"></div>
            </div>

            <!-- Right Utility Icons: User, Wishlist Heart, UK Flag -->
            <div class="deante-actions-group">
                <!-- User Account -->
                <a href="<?= auth()->check() ? e(route('admin.dashboard')) : e(route('login')) ?>" class="deante-icon-link" title="Account / Admin">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>

                <!-- Wishlist Heart -->
                <a href="<?= e(route('products.index')) ?>" class="deante-icon-link" title="Wishlist / Favorites">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </a>

                <!-- Language Flag (UK / English) -->
                <div class="deante-flag-wrap" title="English (United Kingdom)">
                    <svg width="22" height="16" viewBox="0 0 60 30" style="border-radius: 2px; box-shadow: 0 1px 3px rgba(0,0,0,0.18); display: block;">
                        <clipPath id="uk-clip"><path d="M0,0 v30 h60 v-30 z"/></clipPath>
                        <clipPath id="uk-diag"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath>
                        <g clip-path="url(#uk-clip)">
                            <path d="M0,0 v30 h60 v-30 z" fill="#012169"/>
                            <path d="M0,0 L60,30 M60,0 L0,30" stroke="#ffffff" stroke-width="6"/>
                            <path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#uk-diag)" stroke="#C8102E" stroke-width="4"/>
                            <path d="M30,0 v30 M0,15 h60" stroke="#ffffff" stroke-width="10"/>
                            <path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/>
                        </g>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Category Nav Links & Outlet -->
    <div class="deante-subnav-strip">
        <div class="container deante-subnav-inner">
            <nav class="deante-nav-list">
                <a href="<?= e(route('products.index', ['category_id' => 1])) ?>" class="deante-nav-item">Bathroom</a>
                <a href="<?= e(route('products.index', ['category_id' => 4])) ?>" class="deante-nav-item">Kitchen</a>
                <a href="<?= e(route('products.index', ['sort' => 'newest'])) ?>" class="deante-nav-item">Latest</a>
                <a href="<?= e(route('products.index')) ?>" class="deante-nav-item">Collections</a>
                <a href="#inspiration" class="deante-nav-item">Inspirations</a>
                <a href="#corporate" class="deante-nav-item">News</a>
                <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I would like to contact your architectural team.') ?>" target="_blank" rel="noopener" class="deante-nav-item">Contact</a>
            </nav>
            <div class="deante-nav-right">
                <a href="<?= e(route('products.index')) ?>" class="deante-nav-item deante-outlet-link">Outlet</a>
            </div>
        </div>
    </div>
</header>
