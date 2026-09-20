<?php
/**
 * Contact page.
 *
 * Structure, top to bottom:
 *   hero        - who you are talking to + trust badge + WhatsApp panel
 *   channels    - WhatsApp / email / phone / factory, every one icon-led
 *   form        - project enquiry, composed into a WhatsApp message
 *   why         - four reassurance points
 *   quick       - one-tap request lines
 *   faq         - the questions the sales desk answers every day
 *
 * Every block is icon-led so the page has a readable structure at a glance.
 *
 * @var Core\View\View $view
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/pages/contact.css');
$view->pushScript('frontend/pages/contact.js');

$waNumber = '908503040817';
$wa = static fn (string $message): string => 'https://wa.me/' . $waNumber . '?text=' . rawurlencode($message);

/* inline icon helper: keeps the markup readable and the set consistent */
$icon = static function (string $name, int $size = 20): string {
    $paths = [
        'whatsapp' => '<path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2ZM12.05 20.15C10.56 20.15 9.11 19.76 7.85 19.01L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 14.99 3.81 13.47 3.81 11.91C3.81 7.37 7.5 3.68 12.04 3.68C14.25 3.68 16.31 4.54 17.87 6.1C19.42 7.66 20.28 9.72 20.28 11.92C20.28 16.46 16.59 20.15 12.05 20.15ZM16.57 14.43C16.32 14.3 15.1 13.71 14.88 13.62C14.65 13.54 14.48 13.5 14.32 13.75C14.15 14 13.67 14.57 13.52 14.74C13.38 14.9 13.23 14.92 12.98 14.8C12.73 14.67 11.94 14.41 11 13.57C10.27 12.91 9.78 12.1 9.63 11.85C9.48 11.6 9.61 11.47 9.74 11.34C9.85 11.23 9.99 11.05 10.12 10.9C10.24 10.75 10.28 10.64 10.36 10.48C10.44 10.31 10.4 10.17 10.34 10.05C10.28 9.92 9.78 8.7 9.58 8.19C9.38 7.69 9.18 7.76 9.03 7.75C8.89 7.74 8.72 7.74 8.56 7.74C8.39 7.74 8.12 7.8 7.89 8.05C7.67 8.3 7.03 8.9 7.03 10.12C7.03 11.34 7.92 12.52 8.04 12.68C8.17 12.85 9.78 15.33 12.26 16.4C12.85 16.65 13.31 16.81 13.67 16.92C14.26 17.11 14.8 17.08 15.22 17.02C15.7 16.95 16.67 16.43 16.88 15.86C17.08 15.29 17.08 14.8 17.02 14.7C16.96 14.6 16.81 14.55 16.57 14.43Z" fill="currentColor" stroke="none"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.2 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.1 9.9a16 16 0 0 0 6 6l1.26-1.26a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/>',
        'factory' => '<path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/><path d="M9 10h.01M15 10h.01"/>',
        'pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'bolt' => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>',
        'shield' => '<path d="M12 3 20 6.2v5.3c0 4.9-3.4 8.6-8 10.5-4.6-1.9-8-5.6-8-10.5V6.2L12 3Z"/><path d="m8.8 12 2.2 2.2 4.2-4.7"/>',
        'badge' => '<circle cx="12" cy="8" r="6"/><path d="M8.2 13.5 7 22l5-3 5 3-1.2-8.5"/>',
        'ship' => '<path d="M3 17h18l-2 4H5l-2-4Z"/><path d="M5 17V9l7-4 7 4v8"/><path d="M12 5V2"/>',
        'doc' => '<path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7Z"/><path d="M14 2v5h5"/>',
        'cube' => '<path d="m12 2 9 5v10l-9 5-9-5V7Z"/><path d="m3 7 9 5 9-5"/><path d="M12 12v10"/>',
        'tag' => '<path d="M20.6 13.4 12 22l-9-9V4a1 1 0 0 1 1-1h9l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'palette' => '<circle cx="12" cy="12" r="9"/><circle cx="8.5" cy="10" r="1"/><circle cx="12" cy="8" r="1"/><circle cx="15.5" cy="10" r="1"/>',
        'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L3 21l1.9-6.4A8 8 0 1 1 21 12Z"/>',
        'arrow' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'send' => '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/>',
        'user' => '<path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="8" r="4"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z"/>',
        'building' => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>',
    ];
    $d = $paths[$name] ?? '';
    return '<svg class="cico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" '
        . 'stroke-linecap="round" stroke-linejoin="round" width="' . $size . '" height="' . $size . '" aria-hidden="true">'
        . $d . '</svg>';
};

$requestLines = [
    ['code' => 'R-01', 'icon' => 'tag',     'key' => 'quick_quote',   'href' => $wa('Hello LUFLY Team, I would like to request direct factory pricing and project specifications for architectural sanitary ware.')],
    ['code' => 'R-02', 'icon' => 'doc',     'key' => 'quick_catalog', 'href' => $wa('Hello LUFLY, please send the 2026 Master Technical PDF Catalog.')],
    ['code' => 'R-03', 'icon' => 'cube',    'key' => 'quick_bim',     'href' => $wa('Hello LUFLY, I require BIM and 3D Revit files for project specification.')],
    ['code' => 'R-04', 'icon' => 'list',    'key' => 'quick_tender',  'href' => $wa('Hello LUFLY, please send the complete 2026 Architect Tender Package (CAD/BIM/PDF).')],
    ['code' => 'R-05', 'icon' => 'palette', 'key' => 'quick_finish',  'href' => $wa('Hello LUFLY, I am specifying a finish for an architectural project. Please share high-res renders and export pricing.')],
];

$whyPoints = [
    ['icon' => 'factory', 't' => 'why_1_t', 'd' => 'why_1_d'],
    ['icon' => 'shield',  't' => 'why_2_t', 'd' => 'why_2_d'],
    ['icon' => 'badge',   't' => 'why_3_t', 'd' => 'why_3_d'],
    ['icon' => 'ship',    't' => 'why_4_t', 'd' => 'why_4_d'],
];

$faqs = [
    ['q' => 'faq_1_q', 'a' => 'faq_1_a'],
    ['q' => 'faq_2_q', 'a' => 'faq_2_a'],
    ['q' => 'faq_3_q', 'a' => 'faq_3_a'],
    ['q' => 'faq_4_q', 'a' => 'faq_4_a'],
];

$mapUrl = 'https://www.google.com/maps/search/?api=1&query=Gaziantep%2C%20T%C3%BCrkiye';
?>
<main class="cpage" id="main">
    <div class="cpage-inner">

        <!-- ============ hero ============ -->
        <section class="cpage-hero">
            <div class="cpage-intro">
                <p class="cpage-kicker">
                    <?= $icon('pin', 14) ?>
                    <?= e(trans('contact.kicker')) ?>
                </p>
                <h1 class="cpage-title"><?= e(trans('contact.title')) ?></h1>
                <p class="cpage-sub"><?= e(trans('contact.subtitle')) ?></p>

                <p class="cpage-badge">
                    <?= $icon('bolt', 14) ?>
                    <?= e(trans('contact.response_badge')) ?>
                </p>
            </div>

            <!-- signature WhatsApp panel -->
            <aside class="cpage-wa" aria-label="<?= e(trans('contact.whatsapp_title')) ?>">
                <span class="cpage-wa-shimmer" aria-hidden="true"></span>
                <span class="cpage-wa-grain" aria-hidden="true"></span>
                <span class="cpage-wa-beam" aria-hidden="true"></span>
                <span class="cpage-wa-chip">
                    <svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true"><path fill="currentColor" d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2ZM12.05 20.15C10.56 20.15 9.11 19.76 7.85 19.01L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 14.99 3.81 13.47 3.81 11.91C3.81 7.37 7.5 3.68 12.04 3.68C14.25 3.68 16.31 4.54 17.87 6.1C19.42 7.66 20.28 9.72 20.28 11.92C20.28 16.46 16.59 20.15 12.05 20.15ZM16.57 14.43C16.32 14.3 15.1 13.71 14.88 13.62C14.65 13.54 14.48 13.5 14.32 13.75C14.15 14 13.67 14.57 13.52 14.74C13.38 14.9 13.23 14.92 12.98 14.8C12.73 14.67 11.94 14.41 11 13.57C10.27 12.91 9.78 12.1 9.63 11.85C9.48 11.6 9.61 11.47 9.74 11.34C9.85 11.23 9.99 11.05 10.12 10.9C10.24 10.75 10.28 10.64 10.36 10.48C10.44 10.31 10.4 10.17 10.34 10.05C10.28 9.92 9.78 8.7 9.58 8.19C9.38 7.69 9.18 7.76 9.03 7.75C8.89 7.74 8.72 7.74 8.56 7.74C8.39 7.74 8.12 7.8 7.89 8.05C7.67 8.3 7.03 8.9 7.03 10.12C7.03 11.34 7.92 12.52 8.04 12.68C8.17 12.85 9.78 15.33 12.26 16.4C12.85 16.65 13.31 16.81 13.67 16.92C14.26 17.11 14.8 17.08 15.22 17.02C15.7 16.95 16.67 16.43 16.88 15.86C17.08 15.29 17.08 14.8 17.02 14.7C16.96 14.6 16.81 14.55 16.57 14.43Z"/></svg>
                    <span><?= e(trans('contact.whatsapp_title')) ?></span>
                </span>
                <p class="cpage-wa-desc"><?= e(trans('contact.whatsapp_desc')) ?></p>
                <a class="cpage-wa-number" href="https://wa.me/<?= e($waNumber) ?>" target="_blank" rel="noopener noreferrer">+90 850 3040 817</a>
                <a class="cpage-wa-cta" href="<?= e($wa('Hello LUFLY, I would like to contact your architectural team.')) ?>" target="_blank" rel="noopener noreferrer">
                    <span><?= e(trans('contact.whatsapp_cta')) ?></span>
                    <?= $icon('arrow', 15) ?>
                </a>
            </aside>
        </section>

        <!-- ============ channels ============ -->
        <div class="cpage-grid">
            <article class="cpage-card">
                <span class="cpage-card-icon" aria-hidden="true"><?= $icon('mail', 21) ?></span>
                <h2><?= e(trans('contact.email_title')) ?></h2>
                <p class="cpage-card-desc"><?= e(trans('contact.email_desc')) ?></p>
                <a class="cpage-strong" href="mailto:info@lufly.tr">info@lufly.tr</a>
            </article>

            <article class="cpage-card">
                <span class="cpage-card-icon" aria-hidden="true"><?= $icon('phone', 21) ?></span>
                <h2><?= e(trans('contact.phone_title')) ?></h2>
                <p class="cpage-card-desc"><?= e(trans('contact.phone_desc')) ?></p>
                <a class="cpage-strong" href="tel:+908503040817">+90 850 3040 817</a>
                <dl class="cpage-hours">
                    <dt><?= $icon('clock', 13) ?> <?= e(trans('contact.hours_label')) ?></dt>
                    <dd><?= e(trans('contact.hours_value')) ?></dd>
                </dl>
            </article>

            <article class="cpage-card">
                <span class="cpage-card-icon" aria-hidden="true"><?= $icon('factory', 21) ?></span>
                <h2><?= e(trans('contact.factory_title')) ?></h2>
                <p class="cpage-company"><?= e(trans('contact.factory_company')) ?></p>
                <p class="cpage-card-desc">
                    <?= e(trans('contact.factory_line1')) ?><br>
                    <?= e(trans('contact.factory_line2')) ?>
                </p>
                <a class="cpage-maplink" href="<?= e($mapUrl) ?>" target="_blank" rel="noopener noreferrer">
                    <?= $icon('pin', 14) ?>
                    <span><?= e(trans('contact.map_cta')) ?></span>
                </a>
            </article>
        </div>

        <!-- ============ enquiry form ============ -->
        <section class="cpage-form-wrap" aria-labelledby="cform-title">
            <div class="cpage-form-head">
                <span class="cpage-card-icon" aria-hidden="true"><?= $icon('chat', 21) ?></span>
                <div>
                    <h2 id="cform-title"><?= e(trans('contact.form_title')) ?></h2>
                    <p class="cpage-card-desc"><?= e(trans('contact.form_desc')) ?></p>
                </div>
            </div>

            <form class="cpage-form" data-contact-form data-wa-number="<?= e($waNumber) ?>" data-loader-skip>
                <div class="cpage-field">
                    <label for="cf-name"><?= $icon('user', 14) ?> <?= e(trans('contact.form_name')) ?> *</label>
                    <input id="cf-name" name="name" type="text" required autocomplete="name">
                </div>

                <div class="cpage-field">
                    <label for="cf-email"><?= $icon('mail', 14) ?> <?= e(trans('contact.form_email')) ?> *</label>
                    <input id="cf-email" name="email" type="email" required autocomplete="email">
                </div>

                <div class="cpage-field">
                    <label for="cf-company"><?= $icon('building', 14) ?> <?= e(trans('contact.form_company')) ?></label>
                    <input id="cf-company" name="company" type="text" autocomplete="organization">
                </div>

                <div class="cpage-field">
                    <label for="cf-country"><?= $icon('globe', 14) ?> <?= e(trans('contact.form_country')) ?></label>
                    <input id="cf-country" name="country" type="text" autocomplete="country-name">
                </div>

                <div class="cpage-field cpage-field-full">
                    <label for="cf-type"><?= $icon('list', 14) ?> <?= e(trans('contact.form_type')) ?></label>
                    <select id="cf-type" name="type">
                        <option value="<?= e(trans('contact.form_type_quote')) ?>"><?= e(trans('contact.form_type_quote')) ?></option>
                        <option value="<?= e(trans('contact.form_type_catalog')) ?>"><?= e(trans('contact.form_type_catalog')) ?></option>
                        <option value="<?= e(trans('contact.form_type_tender')) ?>"><?= e(trans('contact.form_type_tender')) ?></option>
                        <option value="<?= e(trans('contact.form_type_partner')) ?>"><?= e(trans('contact.form_type_partner')) ?></option>
                        <option value="<?= e(trans('contact.form_type_other')) ?>"><?= e(trans('contact.form_type_other')) ?></option>
                    </select>
                </div>

                <div class="cpage-field cpage-field-full">
                    <label for="cf-message"><?= $icon('doc', 14) ?> <?= e(trans('contact.form_message')) ?> *</label>
                    <textarea id="cf-message" name="message" rows="5" required
                              placeholder="<?= e(trans('contact.form_message_ph')) ?>"></textarea>
                </div>

                <div class="cpage-form-foot">
                    <button type="submit" class="cpage-submit">
                        <?= $icon('send', 16) ?>
                        <span><?= e(trans('contact.form_submit')) ?></span>
                    </button>
                    <p class="cpage-form-hint"><?= e(trans('contact.form_hint')) ?></p>
                </div>
            </form>
        </section>

        <!-- ============ reassurance ============ -->
        <section class="cpage-why" aria-labelledby="cwhy-title">
            <h2 class="cpage-section-title" id="cwhy-title">
                <?= $icon('shield', 18) ?>
                <?= e(trans('contact.why_title')) ?>
            </h2>
            <div class="cpage-why-grid">
                <?php foreach ($whyPoints as $point): ?>
                    <div class="cpage-why-item">
                        <span class="cpage-why-icon" aria-hidden="true"><?= $icon($point['icon'], 19) ?></span>
                        <h3><?= e(trans('contact.' . $point['t'])) ?></h3>
                        <p><?= e(trans('contact.' . $point['d'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ============ quick request lines ============ -->
        <section class="cpage-quick" aria-labelledby="cquick-title">
            <h2 class="cpage-section-title" id="cquick-title">
                <?= $icon('bolt', 18) ?>
                <?= e(trans('contact.quick_title')) ?>
            </h2>
            <div class="cpage-lines">
                <?php foreach ($requestLines as $line): ?>
                    <a class="cpage-line" href="<?= e($line['href']) ?>" target="_blank" rel="noopener noreferrer">
                        <span class="cpage-line-code"><?= e($line['code']) ?></span>
                        <span class="cpage-line-ico" aria-hidden="true"><?= $icon($line['icon'], 17) ?></span>
                        <span class="cpage-line-label"><?= e(trans('contact.' . $line['key'])) ?></span>
                        <span class="cpage-line-arrow" aria-hidden="true"><?= $icon('arrow', 15) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ============ faq ============ -->
        <section class="cpage-faq" aria-labelledby="cfaq-title">
            <h2 class="cpage-section-title" id="cfaq-title">
                <?= $icon('chat', 18) ?>
                <?= e(trans('contact.faq_title')) ?>
            </h2>
            <div class="cpage-faq-list">
                <?php foreach ($faqs as $faq): ?>
                    <details class="cpage-faq-item">
                        <summary>
                            <span><?= e(trans('contact.' . $faq['q'])) ?></span>
                            <span class="cpage-faq-plus" aria-hidden="true"><?= $icon('plus', 16) ?></span>
                        </summary>
                        <p><?= e(trans('contact.' . $faq['a'])) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>

    </div>
</main>
