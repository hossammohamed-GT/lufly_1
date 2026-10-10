<?php
$view->layout('layouts.frontend');
$view->pushStyle('lufly-central.css');
$view->pushStyle('lufly-central-story.css');
$view->pushScript('lufly-central.js');
$view->pushScript('lufly-central-story.js');
$tr = static fn (string $key): string => e(trans('central_vacuum.' . $key));
$img = static fn (string $name): string => e(asset($name));

/* One sticky story: each chapter has its own image (same framing), and the
   scroll position cross-fades between them while the copy changes. */
$chapters = [
    ['image' => 'base-system.jpg', 'kicker' => 'intro'],
    ['image' => 'chapter-01-suction.jpg', 'kicker' => '01', 'benefit' => 1],
    ['image' => 'chapter-02-quiet.jpg',   'kicker' => '02', 'benefit' => 2],
    ['image' => 'chapter-03-air.jpg',     'kicker' => '03', 'benefit' => 3],
    ['image' => 'chapter-04-hidden.jpg',  'kicker' => '04', 'benefit' => 4],
    ['image' => 'chapter-05-hose.jpg',    'kicker' => '05', 'benefit' => 5],
    ['image' => 'chapter-06-villa.jpg',   'kicker' => '06', 'benefit' => 6],
];
$total = count($chapters);
?>
<div class="lufly-central" data-lufly-central>

    <section class="lcs" data-lcs aria-label="<?= $tr('story_aria') ?>">
        <div class="lcs-stage">
            <div class="lcs-images">
                <?php foreach ($chapters as $i => $c): ?>
                    <img class="lcs-img<?= $i === 0 ? ' is-on' : '' ?>"
                         data-lcs-img
                         src="<?= $img('images/central-vacuum/' . $c['image']) ?>"
                         alt="<?= $i === 0 ? $tr('story_image_alt') : '' ?>"
                         width="1280" height="1280"
                         <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                         decoding="async"
                         aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
                <?php endforeach; ?>
            </div>

            <div class="lcs-shade" aria-hidden="true"></div>

            <div class="lcs-copy">
                <?php foreach ($chapters as $i => $c): ?>
                    <div class="lcs-chapter" data-lcs-chapter>
                        <span class="lcs-kicker">
                            <?php if ($c['kicker'] === 'intro'): ?>
                                <?= $tr('story_intro_kicker') ?>
                            <?php else: ?>
                                <?= e($c['kicker']) ?> / <?= e(sprintf('%02d', $total - 1)) ?>
                            <?php endif; ?>
                        </span>
                        <?php if ($c['kicker'] === 'intro'): ?>
                            <h1><?= $tr('story_intro_title') ?></h1>
                            <p><?= $tr('story_intro_desc') ?></p>
                        <?php else: ?>
                            <h2><?= $tr('benefit_' . $c['benefit'] . '_title') ?></h2>
                            <p><?= $tr('benefit_' . $c['benefit'] . '_desc') ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="lcs-dots" role="group" aria-label="<?= $tr('story_aria') ?>">
                <?php foreach ($chapters as $i => $c): ?>
                    <button type="button" data-lcs-dot aria-label="<?= e((string) ($i + 1)) ?>"></button>
                <?php endforeach; ?>
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
