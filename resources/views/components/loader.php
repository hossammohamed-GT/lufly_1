<?php
/**
 * LUFLY: Global Preloader & Loading Experience
 * "Building the brand" assembly (seed dot, glyph pieces, settle, reflection),
 * then water rises inside the glyphs (= real progress), factory craft
 * background on initial load, dynamic progress and informative messages
 * in English, Turkish and Czech.
 *
 * @var Core\Localization\Translator|null $translator
 */
$locale = isset($translator) && $translator instanceof \Core\Localization\Translator
    ? $translator->getLocale()
    : (string) config('localization.default', 'en');

$kickers = [
    'en' => 'LUFLY Architectural Sanitary Ware',
    'tr' => 'LUFLY Mimari Vitrifiye ve Armatür',
    'cs' => 'LUFLY Prémiová sanitární keramika',
];

$tags = [
    'en' => 'Bathroom Culture Sculpted with Perfection',
    'tr' => 'Mükemmellikle Şekillenen Banyo Kültürü',
    'cs' => 'Koupelnová kultura tvořená k dokonalosti',
];

$msgs = [
    'en' => 'Precision casting of high-purity brass',
    'tr' => 'Yüksek saflıkta pirinç gövde dökümü',
    'cs' => 'Přesné lití vysoce čisté mosazi',
];

$foots = [
    'en' => 'LUFLY HQ & Manufacturing Hub Gaziantep Turkey',
    'tr' => 'LUFLY Genel Merkez ve Üretim Tesisleri Gaziantep',
    'cs' => 'LUFLY HQ a Výrobní závod Gaziantep Turecko',
];

$kicker = $kickers[$locale] ?? $kickers['en'];
$tag = $tags[$locale] ?? $tags['en'];
$msg = $msgs[$locale] ?? $msgs['en'];
$foot = $foots[$locale] ?? $foots['en'];

/* Deployment base path ('/lufly_1' when served from a sub-folder, '' at a
   domain root) so the JS can classify URLs correctly anywhere. */
$basePath = rtrim((string) (parse_url((string) config('app.url'), PHP_URL_PATH) ?? ''), '/');
?>
<div class="ld-loader is-initial" id="ldLoader" role="status" aria-label="Loading" data-base="<?= e($basePath) ?>">
    <div class="ld-blob a"></div>
    <div class="ld-blob b"></div>
    <div class="ld-vignette"></div>

    <div class="ld-center">
        <div class="ld-kicker"><?= e($kicker) ?></div>

        <div class="ld-logoBox" id="ldLogoBox">
            <!-- seed: a single mint dot before the brand assembles -->
            <span class="ld-seed" aria-hidden="true"></span>

            <!-- "Building the brand": each glyph piece enters on its own
                 motion path (up, from the side, arc, bounce) and settles -->
            <svg class="ld-draw" viewBox="0 0 510 325" aria-hidden="true">
                <path pathLength="1" d="M 30 16 L 30 298 Q 30 306 38 306 L 116 306"/>
                <path pathLength="1" d="M 64 16 L 64 174"/>
                <path pathLength="1" d="M 64 244 L 116 244"/>
                <path pathLength="1" d="M 158 148 L 158 246 Q 158 292 198 292 Q 238 292 238 246 L 238 148"/>
                <path pathLength="1" d="M 288 292 L 288 82 Q 288 42 330 42"/>
                <path pathLength="1" d="M 256 148 L 320 148"/>
                <path pathLength="1" d="M 378 16 L 378 292"/>
                <path pathLength="1" d="M 416 148 L 456 238 L 496 148 L 424 306"/>
            </svg>

            <!-- water, masked to the real glyph shapes -->
            <div class="ld-water" aria-hidden="true">
                <div class="ld-waterBody" id="ldWaterBody">
                    <span class="wave"></span>
                    <span class="wave w2"></span>
                </div>
            </div>

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

        <div class="ld-tag" id="ldTag"><?= e($tag) ?></div>

        <div class="ld-ui" id="ldUi">
            <div class="ld-row">
                <div class="ld-track"><span class="ld-fill" id="ldFill"></span></div>
                <span class="ld-pct" id="ldPct">0%</span>
            </div>
            <div class="ld-msg" id="ldMsg"><?= e($msg) ?></div>
        </div>
    </div>

    <div class="ld-foot"><?= e($foot) ?></div>
</div>
