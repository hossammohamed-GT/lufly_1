<?php
/**
 * Contact page: everything contact-related in one place.
 * Machined editorial layout: blueprint grid, a signature WhatsApp panel,
 * beam-hover channel cards and coded request lines.
 *
 * @var Core\View\View $view
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/pages/contact.css');

$wa = static fn (string $message): string => 'https://wa.me/908503040817?text=' . rawurlencode($message);

$requestLines = [
    ['code' => 'R-01', 'key' => 'quick_quote', 'href' => $wa('Hello LUFLY Team, I would like to request direct factory pricing and project specifications for architectural sanitary ware.')],
    ['code' => 'R-02', 'key' => 'quick_catalog', 'href' => $wa('Hello LUFLY, please send the 2026 Master Technical PDF Catalog.')],
    ['code' => 'R-03', 'key' => 'quick_bim', 'href' => $wa('Hello LUFLY, I require BIM and 3D Revit files for project specification.')],
    ['code' => 'R-04', 'key' => 'quick_tender', 'href' => $wa('Hello LUFLY, please send the complete 2026 Architect Tender Package (CAD/BIM/PDF).')],
    ['code' => 'R-05', 'key' => 'quick_finish', 'href' => $wa('Hello LUFLY, I am specifying a finish for an architectural project. Please share high-res renders and export pricing.')],
];
?>
<main class="cpage" id="main">
    <div class="cpage-inner">
        <section class="cpage-hero">
            <div class="cpage-intro">
                <p class="cpage-kicker"><?= e(trans('contact.kicker')) ?></p>
                <h1 class="cpage-title"><?= e(trans('contact.title')) ?></h1>
                <p class="cpage-sub"><?= e(trans('contact.subtitle')) ?></p>
                <p class="cpage-note">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2 2a1 1 0 001.414-1.414L11 9.586V6z" clip-rule="evenodd"/></svg>
                    <?= e(trans('contact.response_note')) ?>
                </p>
            </div>

            <!-- Signature machined WhatsApp panel -->
            <aside class="cpage-wa" aria-label="<?= e(trans('contact.whatsapp_title')) ?>">
                <span class="cpage-wa-shimmer" aria-hidden="true"></span>
                <span class="cpage-wa-grain" aria-hidden="true"></span>
                <span class="cpage-wa-beam" aria-hidden="true"></span>
                <span class="cpage-wa-chip">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2ZM12.05 20.15C10.56 20.15 9.11 19.76 7.85 19.01L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 14.99 3.81 13.47 3.81 11.91C3.81 7.37 7.5 3.68 12.04 3.68C14.25 3.68 16.31 4.54 17.87 6.1C19.42 7.66 20.28 9.72 20.28 11.92C20.28 16.46 16.59 20.15 12.05 20.15ZM16.57 14.43C16.32 14.3 15.1 13.71 14.88 13.62C14.65 13.54 14.48 13.5 14.32 13.75C14.15 14 13.67 14.57 13.52 14.74C13.38 14.9 13.23 14.92 12.98 14.8C12.73 14.67 11.94 14.41 11 13.57C10.27 12.91 9.78 12.1 9.63 11.85C9.48 11.6 9.61 11.47 9.74 11.34C9.85 11.23 9.99 11.05 10.12 10.9C10.24 10.75 10.28 10.64 10.36 10.48C10.44 10.31 10.4 10.17 10.34 10.05C10.28 9.92 9.78 8.7 9.58 8.19C9.38 7.69 9.18 7.76 9.03 7.75C8.89 7.74 8.72 7.74 8.56 7.74C8.39 7.74 8.12 7.8 7.89 8.05C7.67 8.3 7.03 8.9 7.03 10.12C7.03 11.34 7.92 12.52 8.04 12.68C8.17 12.85 9.78 15.33 12.26 16.4C12.85 16.65 13.31 16.81 13.67 16.92C14.26 17.11 14.8 17.08 15.22 17.02C15.7 16.95 16.67 16.43 16.88 15.86C17.08 15.29 17.08 14.8 17.02 14.7C16.96 14.6 16.81 14.55 16.57 14.43Z"/></svg>
                    <span><?= e(trans('contact.whatsapp_title')) ?></span>
                </span>
                <p class="cpage-wa-desc"><?= e(trans('contact.whatsapp_desc')) ?></p>
                <a class="cpage-wa-number" href="https://wa.me/908503040817" target="_blank" rel="noopener noreferrer">+90 850 3040 817</a>
                <a class="cpage-wa-cta" href="<?= e($wa('Hello LUFLY, I would like to contact your architectural team.')) ?>" target="_blank" rel="noopener noreferrer">
                    <span><?= e(trans('contact.whatsapp_cta')) ?></span>
                    <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </a>
            </aside>
        </section>

        <div class="cpage-grid">
            <!-- Email -->
            <article class="cpage-card">
                <svg class="cpage-beam" aria-hidden="true"><rect rx="14" ry="14" pathLength="100" /></svg>
                <span class="cpage-card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="21" height="21"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                </span>
                <h2><?= e(trans('contact.email_title')) ?></h2>
                <p class="cpage-card-desc"><?= e(trans('contact.email_desc')) ?></p>
                <a class="cpage-strong" href="mailto:info@lufly.tr">info@lufly.tr</a>
            </article>

            <!-- Factory -->
            <article class="cpage-card">
                <svg class="cpage-beam" aria-hidden="true"><rect rx="14" ry="14" pathLength="100" /></svg>
                <span class="cpage-card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="21" height="21"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/><path d="M9 10h.01M15 10h.01"/></svg>
                </span>
                <h2><?= e(trans('contact.factory_title')) ?></h2>
                <p class="cpage-company"><?= e(trans('contact.factory_company')) ?></p>
                <p class="cpage-card-desc">
                    <?= e(trans('contact.factory_line1')) ?><br>
                    <?= e(trans('contact.factory_line2')) ?>
                </p>
                <dl class="cpage-hours">
                    <dt><?= e(trans('contact.hours_label')) ?></dt>
                    <dd><?= e(trans('contact.hours_value')) ?></dd>
                </dl>
            </article>
        </div>

        <!-- Coded request lines -->
        <section class="cpage-quick" aria-label="<?= e(trans('contact.quick_title')) ?>">
            <h2 class="cpage-quick-title"><?= e(trans('contact.quick_title')) ?></h2>
            <div class="cpage-lines">
                <?php foreach ($requestLines as $line): ?>
                    <a class="cpage-line" href="<?= e($line['href']) ?>" target="_blank" rel="noopener noreferrer">
                        <span class="cpage-line-code"><?= e($line['code']) ?></span>
                        <span class="cpage-line-label"><?= e(trans('contact.' . $line['key'])) ?></span>
                        <svg class="cpage-line-arrow" viewBox="0 0 20 20" fill="currentColor" width="15" height="15" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>
