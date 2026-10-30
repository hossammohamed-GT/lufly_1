<?php
/**
 * LUFLY: First-entry brand intro.
 * The LUFLY mark — the exact same glyph paths and colours as the loading
 * loader (components.loader) — drawn large, stroke by stroke, over a deep
 * aquatic stage with rising light motes and a glass reflection; then the
 * whole stage lifts away like a curtain. Shown on the home page once per
 * visit (session); the gate script + intro.js handle show/skip/cleanup,
 * intro.css carries the whole choreography. The classic loader keeps its
 * own loading/navigation role untouched.
 *
 * @var Core\View\View $view
 */

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
        <!-- seed: a single mint dot before the mark draws itself -->
        <span class="intro-seed" aria-hidden="true"></span>

        <!-- the LUFLY mark, identical glyph paths to the loader's;
             pathLength normalises every stroke so CSS can "draw" them -->
        <svg class="intro-draw" viewBox="0 0 510 325" aria-hidden="true">
            <path pathLength="100" d="M 30 16 L 30 298 Q 30 306 38 306 L 116 306"/>
            <path pathLength="100" d="M 64 16 L 64 174"/>
            <path pathLength="100" d="M 64 244 L 116 244"/>
            <path pathLength="100" d="M 158 148 L 158 246 Q 158 292 198 292 Q 238 292 238 246 L 238 148"/>
            <path pathLength="100" d="M 288 292 L 288 82 Q 288 42 330 42"/>
            <path pathLength="100" d="M 256 148 L 320 148"/>
            <path pathLength="100" d="M 378 16 L 378 292"/>
            <path pathLength="100" d="M 416 148 L 456 238 L 496 148 L 424 306"/>
        </svg>

        <!-- glass-floor reflection under the finished mark -->
        <span class="intro-floor" aria-hidden="true"></span>
        <svg class="intro-reflect" viewBox="0 0 510 325" aria-hidden="true">
            <path d="M 30 16 L 30 298 Q 30 306 38 306 L 116 306"/>
            <path d="M 64 16 L 64 174"/>
            <path d="M 64 244 L 116 244"/>
            <path d="M 158 148 L 158 246 Q 158 292 198 292 Q 238 292 238 246 L 238 148"/>
            <path d="M 288 292 L 288 82 Q 288 42 330 42"/>
            <path d="M 256 148 L 320 148"/>
            <path d="M 378 16 L 378 292"/>
            <path d="M 416 148 L 456 238 L 496 148 L 424 306"/>
        </svg>
    </div>
</div>
