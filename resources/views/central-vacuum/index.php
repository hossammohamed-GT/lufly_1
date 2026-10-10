<?php
$view->layout('layouts.frontend');
$view->pushStyle('lufly-central.css');
$view->pushStyle('lufly-central-story.css');
$view->pushScript('lufly-central.js');
$view->pushScript('lufly-central-story.js');
$tr = static fn (string $key): string => e(trans('central_vacuum.' . $key));
$img = static fn (string $name): string => e(asset($name));

/* The sticky story: one cutaway house, seven chapters (intro + six benefits).
   Each chapter sets a camera focus (percent of the stage) and the scale the
   camera pushes to. Overlay layers carry data-ch lists, toggled by the script. */
$chapters = [
    ['kicker' => 'intro',  'focus' => '50% 50%', 'scale' => '1'],
    ['kicker' => '01',     'focus' => '52% 90%', 'scale' => '1.5', 'benefit' => 1],
    ['kicker' => '02',     'focus' => '59% 68%', 'scale' => '1.35', 'benefit' => 2],
    ['kicker' => '03',     'focus' => '66% 59%', 'scale' => '1.3', 'benefit' => 3],
    ['kicker' => '04',     'focus' => '70% 45%', 'scale' => '1.35', 'benefit' => 4],
    ['kicker' => '05',     'focus' => '80% 73%', 'scale' => '1.5', 'benefit' => 5],
    ['kicker' => '06',     'focus' => '50% 50%', 'scale' => '1', 'benefit' => 6],
];
$total = count($chapters);
?>
<div class="lufly-central" data-lufly-central>

    <section class="lcs" data-lcs aria-label="<?= $tr('story_aria') ?>">
        <div class="lcs-stage">
            <div class="lcs-camera" data-lcs-camera>
                <img src="<?= $img('images/central-vacuum/master-cutaway.jpg') ?>" alt="<?= $tr('story_image_alt') ?>" width="1568" height="882" fetchpriority="high">
                <svg class="lcs-svg" viewBox="0 0 1568 882" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
                    <!-- the water-blue network: draws itself in on chapter 03 -->
                    <g class="lcs-pipes">
                        <path class="lcs-pipe" data-ch="3,4,5,6" pathLength="1" d="M820 800 L1030 800 L1030 150"/>
                        <path class="lcs-pipe" data-ch="3,4,5,6" pathLength="1" d="M1030 640 L905 640"/>
                        <path class="lcs-pipe" data-ch="3,4,5,6" pathLength="1" d="M1030 640 L1250 640"/>
                        <path class="lcs-pipe" data-ch="3,4,5,6" pathLength="1" d="M1030 440 L1300 440"/>
                        <path class="lcs-pipe" data-ch="3,4,5,6" pathLength="1" d="M1030 250 L1200 250"/>
                    </g>

                    <!-- 01 power unit -->
                    <g class="lcs-l" data-ch="1,2">
                        <circle class="lcs-halo" cx="820" cy="800" r="48"/>
                        <rect class="lcs-unit" x="802" y="768" width="36" height="64" rx="8"/>
                        <text class="lcs-label" x="820" y="748" text-anchor="middle"><?= $tr('home_label_unit') ?></text>
                    </g>

                    <!-- 02 quiet -->
                    <g class="lcs-l" data-ch="2">
                        <ellipse class="lcs-ring" cx="930" cy="600" rx="200" ry="105"/>
                        <ellipse class="lcs-ring lcs-ring--outer" cx="930" cy="600" rx="300" ry="160"/>
                        <text class="lcs-label" x="930" y="462" text-anchor="middle"><?= $tr('story_quiet_label') ?></text>
                    </g>

                    <!-- 03 cleaner air: dust travels from the inlet to the unit -->
                    <g class="lcs-l" data-ch="3">
                        <circle class="lcs-dust" r="6"><animateMotion dur="2.6s" repeatCount="indefinite" path="M905 640 L1030 640 L1030 800 L820 800"/></circle>
                        <circle class="lcs-dust" r="5"><animateMotion dur="2.6s" begin="0.9s" repeatCount="indefinite" path="M905 640 L1030 640 L1030 800 L820 800"/></circle>
                        <circle class="lcs-dust" r="4"><animateMotion dur="2.6s" begin="1.8s" repeatCount="indefinite" path="M905 640 L1030 640 L1030 800 L820 800"/></circle>
                    </g>

                    <!-- 05 + 06 inlets and the hose -->
                    <g class="lcs-l" data-ch="5,6">
                        <path class="lcs-hose" d="M1250 640 Q1215 700 1160 742"/>
                        <g class="lcs-inlet"><circle class="lcs-inlet-dot" cx="905" cy="640" r="10"/></g>
                        <g class="lcs-inlet"><circle class="lcs-inlet-dot" cx="1250" cy="640" r="10"/></g>
                        <g class="lcs-inlet"><circle class="lcs-inlet-dot" cx="1300" cy="440" r="10"/></g>
                        <g class="lcs-inlet"><circle class="lcs-inlet-dot" cx="1200" cy="250" r="10"/></g>
                    </g>

                    <!-- 06 room names -->
                    <g class="lcs-l" data-ch="6">
                        <text class="lcs-label" x="905" y="612" text-anchor="middle"><?= $tr('diagram_living') ?></text>
                        <text class="lcs-label" x="1250" y="612" text-anchor="middle"><?= $tr('diagram_kitchen') ?></text>
                        <text class="lcs-label" x="1300" y="412" text-anchor="middle"><?= $tr('diagram_suite') ?></text>
                        <text class="lcs-label" x="1200" y="222" text-anchor="middle"><?= $tr('label_study') ?></text>
                    </g>
                </svg>
            </div>

            <div class="lcs-shade" aria-hidden="true"></div>

            <div class="lcs-copy">
                <?php foreach ($chapters as $i => $c): ?>
                    <div class="lcs-chapter"
                         data-lcs-chapter
                         data-focus="<?= e($c['focus']) ?>"
                         data-scale="<?= e($c['scale']) ?>">
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
