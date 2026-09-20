<?php
/**
 * LUFLY: Global Preloader — "Building the brand".
 * A mint seed dot pops, then the LUFLY mark assembles piece by piece —
 * each glyph flies in on its own motion path — settles, and its glass
 * reflection fades in below. Seal glow, glowing curtain exit.
 * Choreography is pure CSS; loader.js only schedules seal/exit and
 * classifies which pages get the loader at all.
 */

/* Deployment base path ('/lufly_1' when served from a sub-folder, '' at a
   domain root) so the JS can classify URLs correctly anywhere. */
$basePath = rtrim((string) (parse_url((string) config('app.url'), PHP_URL_PATH) ?? ''), '/');
?>
<div class="ld-loader is-initial" id="ldLoader" role="status" aria-label="Loading" data-base="<?= e($basePath) ?>">
    <div class="ld-center">
        <div class="ld-logoBox">
            <!-- seed: a single mint dot before the brand assembles -->
            <span class="ld-seed" aria-hidden="true"></span>

            <!-- "Building the brand": each glyph piece enters on its own
                 motion path (up, from the side, arc, bounce) and settles -->
            <svg class="ld-draw" viewBox="0 0 510 325" aria-hidden="true">
                <path d="M 30 16 L 30 298 Q 30 306 38 306 L 116 306"/>
                <path d="M 64 16 L 64 174"/>
                <path d="M 64 244 L 116 244"/>
                <path d="M 158 148 L 158 246 Q 158 292 198 292 Q 238 292 238 246 L 238 148"/>
                <path d="M 288 292 L 288 82 Q 288 42 330 42"/>
                <path d="M 256 148 L 320 148"/>
                <path d="M 378 16 L 378 292"/>
                <path d="M 416 148 L 456 238 L 496 148 L 424 306"/>
            </svg>

            <!-- glass-floor reflection under the assembled mark -->
            <span class="ld-floor" aria-hidden="true"></span>
            <svg class="ld-reflect" viewBox="0 0 510 325" aria-hidden="true">
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
</div>
