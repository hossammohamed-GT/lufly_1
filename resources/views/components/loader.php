<?php
/**
 * LUFLY: Global Preloader — "The Seed".
 * A single mint seed dot carries the whole loading moment: it pops in,
 * breathes, sends out water-ripple rings, charges up, then seals and the
 * stage slides away as a glowing curtain. Choreography is pure CSS;
 * loader.js only schedules seal/exit and classifies which pages get it.
 *
 * @var Core\Localization\Translator|null $translator  (unused, kept for API parity)
 */

/* Deployment base path ('/lufly_1' when served from a sub-folder, '' at a
   domain root) so the JS can classify URLs correctly anywhere. */
$basePath = rtrim((string) (parse_url((string) config('app.url'), PHP_URL_PATH) ?? ''), '/');
?>
<div class="ld-loader is-initial" id="ldLoader" role="status" aria-label="Loading" data-base="<?= e($basePath) ?>">
    <div class="ld-center">
        <div class="ld-orb">
            <span class="ld-halo" aria-hidden="true"></span>
            <span class="ld-ring r1" aria-hidden="true"></span>
            <span class="ld-ring r2" aria-hidden="true"></span>
            <span class="ld-ring r3" aria-hidden="true"></span>
            <span class="ld-seed" aria-hidden="true"></span>
        </div>
    </div>
</div>
