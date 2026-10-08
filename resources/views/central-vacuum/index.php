<?php
$view->layout('layouts.frontend');
$view->pushStyle('lufly-central.css');
$view->pushScript('lufly-central.js');
$tr = static fn (string $key): string => e(trans('central_vacuum.' . $key));
$img = static fn (string $name): string => e(asset($name));
?>
<div class="lufly-central" data-lufly-central>
    <section class="lc-hero lc-reveal">
        <div class="lc-hero__copy">
            <span class="lc-eyebrow"><?= $tr('hero_kicker') ?></span>
            <h1><?= $tr('hero_title_line_1') ?><br><em><?= $tr('hero_title_line_2') ?></em></h1>
            <p><?= $tr('hero_desc') ?></p>
            <a class="lc-button lc-button--light" href="#lc-how"><?= $tr('hero_primary') ?><span>↓</span></a>
        </div>
        <div class="lc-media"><img src="<?= $img('Hero.png') ?>" alt="LUFLY central vacuum system in a modern home" fetchpriority="high"><i></i></div>
    </section>

    <section class="lc-intro lc-reveal" id="lc-how">
        <div><span class="lc-eyebrow"><?= $tr('how_tag') ?></span><h2><?= $tr('how_title') ?></h2></div>
        <p><?= $tr('how_desc') ?></p>
    </section>

    <section class="lc-feature lc-feature--light lc-feature--focus-system lc-reveal">
        <div class="lc-feature__media"><img src="<?= $img('Pipe Network.png') ?>" alt="Concealed pipe network inside a home"></div>
        <div class="lc-feature__copy"><span class="lc-eyebrow"><?= $tr('how_tag') ?></span><h2><?= $tr('step_2_title') ?></h2><p><?= $tr('step_2_desc') ?></p><div class="lc-points"><b><?= $tr('step_4_title') ?></b><span><?= $tr('step_4_desc') ?></span></div></div>
    </section>

    <section class="lc-feature lc-feature--dark lc-feature--reverse lc-feature--focus-inlet lc-reveal">
        <div class="lc-feature__media"><img src="<?= $img('Wall Inlets.png') ?>" alt="Discreet LUFLY wall inlet"></div>
        <div class="lc-feature__copy"><span class="lc-eyebrow"><?= $tr('components_tag') ?></span><h2><?= $tr('step_3_title') ?></h2><p><?= $tr('step_3_desc') ?></p><div class="lc-points"><b><?= $tr('component_2_title') ?></b><span><?= $tr('component_2_desc') ?></span></div></div>
    </section>

    <section class="lc-feature lc-feature--warm lc-feature--focus-storage lc-reveal">
        <div class="lc-feature__media"><img src="<?= $img('Hose Storag.png') ?>" alt="LUFLY hose storage and cleaning accessories"></div>
        <div class="lc-feature__copy"><span class="lc-eyebrow"><?= $tr('components_tag') ?></span><h2><?= $tr('component_4_title') ?></h2><p><?= $tr('component_4_desc') ?></p><div class="lc-points"><b><?= $tr('benefit_5_title') ?></b><span><?= $tr('benefit_5_desc') ?></span></div></div>
    </section>

    <section class="lc-feature lc-feature--dark lc-feature--reverse lc-feature--focus-power lc-reveal">
        <div class="lc-feature__media"><img src="<?= $img('Power Unit.png') ?>" alt="Remote LUFLY central power unit"></div>
        <div class="lc-feature__copy"><span class="lc-eyebrow"><?= $tr('components_tag') ?></span><h2><?= $tr('component_1_title') ?></h2><p><?= $tr('component_1_desc') ?></p><div class="lc-points"><b><?= $tr('benefit_2_title') ?></b><span><?= $tr('benefit_2_desc') ?></span></div></div>
    </section>

    <section class="lc-benefits lc-reveal">
        <span class="lc-eyebrow"><?= $tr('benefits_tag') ?></span><h2><?= $tr('benefits_title') ?></h2><p class="lc-lead"><?= $tr('benefits_desc') ?></p>
        <div class="lc-benefits__grid">
            <?php for ($i = 1; $i <= 6; $i++): ?><article><h3><?= $tr('benefit_' . $i . '_title') ?></h3><p><?= $tr('benefit_' . $i . '_desc') ?></p></article><?php endfor; ?>
        </div>
    </section>

    <section class="lc-feature lc-feature--light lc-feature--plan lc-feature--focus-plan lc-reveal">
        <div class="lc-feature__media"><img src="<?= $img('Installation _ Plan.png') ?>" alt="LUFLY installation plan for a home"></div>
        <div class="lc-feature__copy"><span class="lc-eyebrow"><?= $tr('install_tag') ?></span><h2><?= $tr('install_title') ?></h2><p><?= $tr('install_desc') ?></p><ol><?php for ($i = 1; $i <= 5; $i++): ?><li><?= $tr('install_' . $i) ?></li><?php endfor; ?></ol></div>
    </section>

    <section class="lc-compare lc-reveal">
        <span class="lc-eyebrow"><?= $tr('compare_tag') ?></span><h2><?= $tr('compare_title') ?></h2><div class="lc-compare__grid"><div><h3><?= $tr('traditional_title') ?></h3><ul><?php for ($i = 1; $i <= 4; $i++): ?><li><?= $tr('traditional_' . $i) ?></li><?php endfor; ?></ul></div><div class="is-highlight"><h3><?= $tr('central_title') ?></h3><ul><?php for ($i = 1; $i <= 4; $i++): ?><li><?= $tr('central_' . $i) ?></li><?php endfor; ?></ul></div></div></section>

    <section class="lc-video lc-reveal">
        <div class="lc-video__intro"><span class="lc-eyebrow"><?= $tr('video_tag') ?></span><h2><?= $tr('video_title') ?></h2><p><?= $tr('video_desc') ?></p></div>
        <div class="lc-video__frame"><iframe src="https://www.youtube-nocookie.com/embed/vIN7fmPc_Uo?rel=0&modestbranding=1&playsinline=1" title="<?= $tr('video_aria') ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>
    </section>

    <section class="lc-cta lc-reveal"><span class="lc-eyebrow"><?= $tr('cta_tag') ?></span><h2><?= $tr('cta_title') ?></h2><p><?= $tr('cta_desc') ?></p><a class="lc-button" href="<?= e(route('contact')) ?>"><?= $tr('cta_primary') ?><span>↗</span></a></section>
</div>
