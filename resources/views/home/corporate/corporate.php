<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/corporate/corporate.css');
?>
<section class="corporate-section" id="corporate">
    <div class="container">
        <div class="corporate-box">
            <div class="corporate-info">
                <span class="corporate-legal-badge"><?= e(trans('home.corporate_badge')) ?></span>
                <h3>LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ</h3>
                <p><?= e(trans('home.corporate_desc')) ?></p>

                <ul class="corporate-details-list">
                    <li><b><?= e(trans('home.legal_name')) ?>:</b> LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ</li>
                    <li><b><?= e(trans('home.headquarters')) ?>:</b> Gaziantep, Turkey (Direct European Logistics Corridor)</li>
                    <li><b><?= e(trans('home.compliance')) ?>:</b> CE Mark &middot; EN 997 &middot; EN 817 &middot; ISO 9001:2015</li>
                    <li><b><?= e(trans('home.export_office')) ?>:</b> +90 850 3040 817 &middot; info@lufly.tr</li>
                </ul>
            </div>

            <div class="spec-desk-col">
                <div class="glass-panel spec-desk">
                    <div class="spec-desk-badge" aria-hidden="true">CAD / BIM</div>
                    <h4 class="spec-desk-title"><?= e(trans('home.spec_desk_title')) ?></h4>
                    <p class="spec-desk-text">
                        <?= e(trans('home.spec_desk_desc')) ?>
                    </p>
                    <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, please send the complete 2026 Architect Tender Package (CAD/BIM/PDF).') ?>"
                       target="_blank" rel="noopener" class="btn-primary-teal spec-desk-cta">
                        <?= e(trans('home.spec_desk_cta')) ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
