<?php
$view->layout('layouts.frontend');
$view->pushStyle('lufly-central.css');
$view->pushStyle('lufly-central-story.css');
$view->pushScript('lufly-central.js');
$view->pushScript('lufly-central-story.js');
$tr = static fn (string $key): string => e(trans('central_vacuum.' . $key));
$img = static fn (string $name): string => e(asset($name));

/* One image for the whole story. The camera moves across it (zoom and pan)
   as the user scrolls; the copy changes per chapter. Focus points live in the
   script's KEYFRAMES list. */
$total = 8;
?>
<div class="lufly-central" data-lufly-central>

    <section class="lcs" data-lcs aria-label="<?= $tr('story_aria') ?>">
        <div class="lcs-stage">
            <div class="lcs-camera" data-lcs-camera>
                <img class="lcs-img" src="<?= $img('images/central-vacuum/hero-wide.jpg') ?>"
                     alt="<?= $tr('story_image_alt') ?>" width="1376" height="768" fetchpriority="high" decoding="async">
                <?php
                /* same-framing variants: each one is faded in only while the camera
                   is on its chapter (index = chapter position, 0-based) */
                $variants = [
                    1 => 'hero-network.jpg',
                    2 => 'chapter-03-inlet-hose.jpg',
                    3 => 'chapter-04-hose-cabinet.jpg',
                    4 => 'chapter-05-cleaning.jpg',
                    5 => 'chapter-06-power-unit.jpg',
                    6 => 'chapter-07-coverage.jpg',
                    7 => 'chapter-08-calm.jpg',
                ];
                foreach ($variants as $idx => $file): ?>
                <img class="lcs-img lcs-img--variant" data-lcs-variant="<?= (int) $idx ?>"
                     src="<?= $img('images/central-vacuum/' . $file) ?>"
                     alt="" width="1376" height="768" loading="lazy" decoding="async" aria-hidden="true">
                <?php endforeach; ?>
            </div>

            <div class="lcs-shade" aria-hidden="true"></div>

            <div class="lcs-copy">
                <?php for ($n = 1; $n <= $total; $n++):
                    $k = sprintf('story_c%02d_', $n);
                ?>
                    <div class="lcs-chapter" data-lcs-chapter>
                        <span class="lcs-kicker"><?= $tr($k . 'kicker') ?></span>
                        <?php if ($n === 1): ?>
                            <h1><?= $tr($k . 'title') ?></h1>
                        <?php else: ?>
                            <h2><?= $tr($k . 'title') ?></h2>
                        <?php endif; ?>
                        <p><?= $tr($k . 'body') ?></p>
                        <?php if ($n === $total): ?>
                            <a class="lcs-cta" href="<?= e(route('contact')) ?>"><?= $tr('cta_primary') ?><span>↗</span></a>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="lcs-dots" role="group" aria-label="<?= $tr('story_aria') ?>">
                <?php for ($n = 1; $n <= $total; $n++): ?>
                    <button type="button" data-lcs-dot aria-label="<?= e((string) $n) ?>"></button>
                <?php endfor; ?>
            </div>

            <span class="lcs-hint" aria-hidden="true"><?= $tr('story_scroll_hint') ?></span>
        </div>
    </section>

    <section class="lc-compare lc-reveal">
        <span class="lc-eyebrow"><?= $tr('compare_tag') ?></span><h2><?= $tr('compare_title') ?></h2><div class="lc-compare__grid"><div><h3><?= $tr('traditional_title') ?></h3><ul><?php for ($i = 1; $i <= 4; $i++): ?><li><?= $tr('traditional_' . $i) ?></li><?php endfor; ?></ul></div><div class="is-highlight"><h3><?= $tr('central_title') ?></h3><ul><?php for ($i = 1; $i <= 4; $i++): ?><li><?= $tr('central_' . $i) ?></li><?php endfor; ?></ul></div></div>
    </section>

    <section class="lc-feature lc-feature--plan lc-feature--focus-plan lc-reveal">
        <div class="lc-feature__media"><img src="<?= $img('Installation _ Plan.png') ?>" alt="LUFLY installation plan for a home" loading="lazy"></div>
        <div class="lc-feature__copy"><span class="lc-eyebrow"><?= $tr('install_tag') ?></span><h2><?= $tr('install_title') ?></h2><p><?= $tr('install_desc') ?></p><ol><?php for ($i = 1; $i <= 5; $i++): ?><li><?= $tr('install_' . $i) ?></li><?php endfor; ?></ol></div>
    </section>

    <section class="lc-video lc-reveal">
        <div class="lc-video__intro"><span class="lc-eyebrow"><?= $tr('video_tag') ?></span><h2><?= $tr('video_title') ?></h2><p><?= $tr('video_desc') ?></p></div>
        <div class="lc-video__frame"><iframe src="https://www.youtube-nocookie.com/embed/vIN7fmPc_Uo?rel=0&modestbranding=1&playsinline=1" title="<?= $tr('video_aria') ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>
    </section>

    <section class="lc-cta lc-reveal"><span class="lc-eyebrow"><?= $tr('cta_tag') ?></span><h2><?= $tr('cta_title') ?></h2><p><?= $tr('cta_desc') ?></p><a class="lc-button" href="<?= e(route('contact')) ?>"><?= $tr('cta_primary') ?><span>↗</span></a></section>
</div>
