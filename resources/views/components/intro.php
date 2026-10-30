<?php
/**
 * LUFLY: First-entry brand intro.
 * The real LUFLY logo image itself (images/logo.png — the same artwork the
 * navbar carries, in its true teal ink) painted in large by a travelling
 * pen of light over a deep aquatic stage with rising light motes and a
 * glass reflection; then the whole stage lifts away like a curtain. Shown
 * on the home page once per visit (session); the gate script + intro.js
 * handle show/skip/cleanup, intro.css carries the whole choreography.
 * The classic loader (its own line-drawn mark) keeps its loading and
 * navigation role untouched.
 *
 * @var Core\View\View $view
 */

/* the one true logo artwork, exactly as the navbar uses it */
$introLogo = asset('images/logo.png');

/* deterministic rising-mote field: [left %, size px, delay s, drift px] */
$introMotes = [
    [6, 3, 0.1, 30], [13, 2, 0.7, -22], [21, 4, 0.4, 18], [29, 2, 1.1, -30],
    [37, 3, 0.2, 24], [45, 2, 0.9, -16], [53, 4, 0.5, 28], [61, 2, 1.3, -26],
    [69, 3, 0.3, 20], [77, 2, 0.8, -20], [85, 3, 0.6, 26], [93, 2, 1.0, -24],
];
?>
<div class="intro" id="siteIntro" role="presentation" aria-hidden="true">
    <div class="intro-aura" aria-hidden="true"></div>

    <div class="intro-motes" aria-hidden="true">
        <?php foreach ($introMotes as $mote): ?>
            <i style="--x:<?= (int) $mote[0] ?>%; --s:<?= (int) $mote[1] ?>px; --d:<?= e((string) $mote[2]) ?>s; --drift:<?= (int) $mote[3] ?>px"></i>
        <?php endforeach; ?>
    </div>

    <div class="intro-logoBox">
        <!-- seed: a single mint dot before the logo paints itself in -->
        <span class="intro-seed" aria-hidden="true"></span>

        <!-- the actual LUFLY logo image, revealed left-to-right by light;
             the intro-pen bar rides the reveal edge like a brush of light -->
        <img class="intro-logo"
             src="<?= e($introLogo) ?>"
             alt=""
             width="510"
             height="325"
             fetchpriority="high"
             decoding="async">
        <span class="intro-pen" aria-hidden="true"></span>

        <!-- glass-floor reflection under the finished logo -->
        <span class="intro-floor" aria-hidden="true"></span>
        <div class="intro-reflect" aria-hidden="true">
            <img src="<?= e($introLogo) ?>" alt="" width="510" height="325" decoding="async">
        </div>
    </div>
</div>
