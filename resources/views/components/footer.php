<footer class="lufly-mega-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-col">
                <div class="footer-brand">
                    <img class="footer-brand-logo" src="<?= e(asset('images/logo.png')) ?>" alt="LUFLY">
                    <span class="footer-brand-name">LUFLY</span>
                </div>
                <p class="footer-brand-text">
                    <?= e(trans('common.footer_brand_desc')) ?>
                </p>
                <div class="footer-brand-note">
                    TIDAL MONOLITH SYSTEM · 2026 ARCHITECTURAL SPECIFICATION
                </div>
            </div>

            <!-- Collections -->
            <div class="footer-col">
                <h5><?= e(trans('common.footer_sanitary')) ?></h5>
                <ul>
                    <li><a href="<?= e(route('products.index', ['category' => 'bathroom-ceramics'])) ?>"><?= e(trans('home.category_toilets')) ?></a></li>
                    <li><a href="<?= e(route('products.index', ['category' => 'washbasin-mixers'])) ?>"><?= e(trans('home.category_basins')) ?></a></li>
                    <li><a href="<?= e(route('products.index', ['category' => 'shower-sets'])) ?>"><?= e(trans('home.category_showers')) ?></a></li>
                    <li><a href="<?= e(route('products.index', ['category' => 'sensor-products'])) ?>"><?= e(trans('home.category_commercial')) ?></a></li>
                    <li><a href="<?= e(route('products.index', ['category' => 'kids'])) ?>"><?= e(trans('home.category_kids')) ?></a></li>
                </ul>
            </div>

            <!-- Brassware & Finishes -->
            <div class="footer-col">
                <h5><?= e(trans('common.footer_finishes')) ?></h5>
                <ul>
                    <li><a href="<?= e(route('home')) ?>#finishes"><?= e(trans('home.finish_rose_title')) ?></a></li>
                    <li><a href="<?= e(route('home')) ?>#finishes"><?= e(trans('home.finish_gold_title')) ?></a></li>
                    <li><a href="<?= e(route('home')) ?>#finishes"><?= e(trans('home.finish_chrome_title')) ?></a></li>
                    <li><a href="<?= e(route('home')) ?>#finishes"><?= e(trans('home.finish_black_title')) ?></a></li>
                    <li><a href="<?= e(route('home')) ?>#finishes"><?= e(trans('home.finish_gunmetal_title')) ?></a></li>
                </ul>
            </div>

            <!-- Technical & Specifiers -->
            <div class="footer-col">
                <h5><?= e(trans('common.footer_technical')) ?></h5>
                <ul>
                    <li><a href="<?= e(route('contact')) ?>"><?= e(trans('nav.download_catalog')) ?></a></li>
                    <li><a href="<?= e(route('contact')) ?>"><?= e(trans('nav.tender_pack')) ?></a></li>
                    <li><a href="<?= e(route('home')) ?>#corporate">EN 997 &middot; EN 817 &middot; CE</a></li>
                    <li><a href="<?= e(route('home')) ?>#rituals"><?= e(trans('home.rituals_title')) ?></a></li>
                    <li><a href="<?= e(route('products.index')) ?>"><?= e(trans('home.full_catalog')) ?></a></li>
                </ul>
            </div>

            <!-- Legal Entity -->
            <div class="footer-col">
                <h5><?= e(trans('common.footer_factory')) ?></h5>
                <p class="footer-legal-name">
                    LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ
                </p>
                <p class="footer-legal-text">
                    Gaziantep Manufacturing & Export Hub, Turkey.<br>
                    Direct European Logistics Dispatch.
                </p>
                <p class="footer-contact">
                    <a class="footer-link-strong" href="<?= e(route('contact')) ?>"><?= e(trans('nav.contact')) ?></a><br>
                    <a class="footer-link-strong" href="https://wa.me/908503040817" <?= external_link_attrs('https://wa.me/908503040817') ?>>WhatsApp: +90 850 3040 817</a><br>
                    <a class="footer-link-muted" href="mailto:info@lufly.tr">
                        Email: info@lufly.tr
                    </a>
                </p>
            </div>
        </div>

        <div class="footer-bottom-bar">
            <span>&copy; <?= date('Y') ?> LUFLY İNŞAAT SAN. VE TİC. LTD. ŞTİ. <?= e(trans('common.footer_rights')) ?></span>
            <span><?= e(trans('common.footer_architecture')) ?></span>
        </div>
    </div>
</footer>
