<footer class="lufly-mega-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-col">
                <div class="footer-brand">
                    <img class="footer-brand-logo" src="<?= e(asset('frontend/design-system/logo.png')) ?>" alt="LUFLY">
                    <span class="footer-brand-name">LUFLY</span>
                </div>
                <p class="footer-brand-text">
                    Bathroom culture enjoyed and shared. Precision engineering behind a refined surface. Manufactured for architects, interior specifiers, luxury hotels, and modern homes worldwide.
                </p>
                <div class="footer-brand-note">
                    TIDAL MONOLITH SYSTEM · 2026 ARCHITECTURAL SPECIFICATION
                </div>
            </div>

            <!-- Collections -->
            <div class="footer-col">
                <h5>Sanitary Ware</h5>
                <ul>
                    <li><a href="<?= e(route('products.index', ['category_id' => 1])) ?>">Wall-Hung Rimless WCs</a></li>
                    <li><a href="<?= e(route('products.index', ['category_id' => 1])) ?>">Bidet & Intelligent Toilets</a></li>
                    <li><a href="<?= e(route('products.index', ['category_id' => 2])) ?>">Countertop Vessel Basins</a></li>
                    <li><a href="<?= e(route('products.index', ['category_id' => 2])) ?>">Monolithic Freestanding Sinks</a></li>
                    <li><a href="<?= e(route('products.index', ['category_id' => 3])) ?>">Commercial Radar Urinals</a></li>
                </ul>
            </div>

            <!-- Brassware & Finishes -->
            <div class="footer-col">
                <h5>PVD & Brassware</h5>
                <ul>
                    <li><a href="#finishes">Brushed Rose Gold (PVD)</a></li>
                    <li><a href="#finishes">Brushed Royal Gold (PVD)</a></li>
                    <li><a href="#finishes">Polished Mirror Chrome</a></li>
                    <li><a href="#finishes">Matte Obsidian Black</a></li>
                    <li><a href="#finishes">Gunmetal Titanium Grey</a></li>
                </ul>
            </div>

            <!-- Technical & Specifiers -->
            <div class="footer-col">
                <h5>Technical & BIM</h5>
                <ul>
                    <li><a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, please send the 2026 Master Technical PDF Catalog.') ?>" target="_blank" rel="noopener">2026 Master Catalog (PDF)</a></li>
                    <li><a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I require BIM and 3D Revit files for project specification.') ?>" target="_blank" rel="noopener">Revit / CAD 3D Files</a></li>
                    <li><a href="#corporate">EN 997 & CE Declarations</a></li>
                    <li><a href="#rituals">PRO-ECO Hydrodynamics</a></li>
                    <li><a href="<?= e(route('products.index')) ?>">Complete Product Registry</a></li>
                </ul>
            </div>

            <!-- Legal Entity -->
            <div class="footer-col">
                <h5>Factory & Dispatch</h5>
                <p class="footer-legal-name">
                    LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ
                </p>
                <p class="footer-legal-text">
                    Gaziantep Manufacturing & Export Hub, Turkey.<br>
                    Direct European Logistics Dispatch.
                </p>
                <p class="footer-contact">
                    <a class="footer-link-strong" href="https://wa.me/908503040817" target="_blank" rel="noopener">WhatsApp: +90 850 3040 817</a><br>
                    <a class="footer-link-muted" href="mailto:info@lufly.tr">
                        Email: info@lufly.tr
                    </a>
                </p>
            </div>
        </div>

        <div class="footer-bottom-bar">
            <span>&copy; <?= date('Y') ?> LUFLY İNŞAAT SAN. VE TİC. LTD. ŞTİ. All rights reserved.</span>
            <span>European Sanitary Architecture &middot; Fixed Brand Teal #1C8B8B &middot; EN 997 / CE Certified</span>
        </div>
    </div>
</footer>
