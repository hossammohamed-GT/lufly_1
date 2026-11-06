<?php
/**
 * Contact page.
 *
 * Structure, top to bottom:
 *   hero        - who you are talking to + trust badge + direct-line panel
 *   channels    - hotline / email / factory, every one icon-led
 *   form        - project enquiry, composed into an e-mail draft
 *   why         - four reassurance points
 *   quick       - one-tap request lines (prefilled e-mails)
 *   faq         - the questions the sales desk answers every day
 *
 * Every block is icon-led so the page has a readable structure at a glance.
 *
 * There is no messaging app on this site: the published number is a telephone
 * hotline only, and every written request leaves through e-mail.
 *
 * @var Core\View\View $view
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/pages/contact.css');
$view->pushScript('frontend/pages/contact.js');

$phone = '+90 850 3040 817';
$phoneHref = 'tel:+908503040817';
$email = 'info@lufly.tr';

$mail = static fn (string $subject, string $body): string => 'mailto:' . $email
    . '?subject=' . rawurlencode($subject)
    . '&body=' . rawurlencode($body);

/* inline icon helper: keeps the markup readable and the set consistent */
$icon = static function (string $name, int $size = 20): string {
    $paths = [
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
    ['code' => 'R-01', 'icon' => 'tag',     'key' => 'quick_quote',   'href' => $mail('Request for factory pricing', "Hello LUFLY Team,\n\nI would like to request direct factory pricing and project specifications for architectural sanitary ware.\n\nProject:\nQuantities:\nDelivery country:\n\nThank you.")],
    ['code' => 'R-02', 'icon' => 'doc',     'key' => 'quick_catalog', 'href' => $mail('Request for the 2026 Master Technical PDF Catalog', "Hello LUFLY,\n\nPlease send the 2026 Master Technical PDF Catalog.\n\nThank you.")],
    ['code' => 'R-03', 'icon' => 'cube',    'key' => 'quick_bim',     'href' => $mail('Request for BIM and 3D Revit files', "Hello LUFLY,\n\nI require BIM and 3D Revit files for project specification.\n\nProject:\nThank you.")],
    ['code' => 'R-04', 'icon' => 'list',    'key' => 'quick_tender',  'href' => $mail('Request for the architect tender package', "Hello LUFLY,\n\nPlease send the complete 2026 Architect Tender Package (CAD/BIM/PDF).\n\nThank you.")],
    ['code' => 'R-05', 'icon' => 'palette', 'key' => 'quick_finish',  'href' => $mail('Finish specification enquiry', "Hello LUFLY,\n\nI am specifying a finish for an architectural project. Please share high-res renders and export pricing.\n\nFinish:\nThank you.")],
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

$mapUrl = 'https://maps.app.goo.gl/b4syJ4YpmdgtTV7d8';
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

            <!-- signature direct-line panel -->
            <aside class="cpage-hot" aria-label="<?= e(trans('contact.hotline_title')) ?>">
                <span class="cpage-hot-shimmer" aria-hidden="true"></span>
                <span class="cpage-hot-grain" aria-hidden="true"></span>
                <span class="cpage-hot-beam" aria-hidden="true"></span>
                <span class="cpage-hot-chip">
                    <?= $icon('phone', 15) ?>
                    <span><?= e(trans('contact.hotline_title')) ?></span>
                </span>
                <p class="cpage-hot-desc"><?= e(trans('contact.hotline_desc')) ?></p>
                <a class="cpage-hot-number" href="<?= e($phoneHref) ?>"><?= e($phone) ?></a>
                <a class="cpage-hot-cta" href="<?= e($phoneHref) ?>">
                    <span><?= e(trans('contact.hotline_cta')) ?></span>
                    <?= $icon('arrow', 15) ?>
                </a>
                <p class="cpage-hot-hours">
                    <?= $icon('clock', 13) ?>
                    <span><?= e(trans('contact.hotline_hours')) ?></span>
                </p>
            </aside>
        </section>

        <!-- ============ channels ============ -->
        <div class="cpage-grid">
            <article class="cpage-card">
                <span class="cpage-card-icon" aria-hidden="true"><?= $icon('phone', 21) ?></span>
                <h2><?= e(trans('contact.phone_title')) ?></h2>
                <p class="cpage-card-desc"><?= e(trans('contact.phone_desc')) ?></p>
                <a class="cpage-strong" href="<?= e($phoneHref) ?>"><?= e($phone) ?></a>
                <dl class="cpage-hours">
                    <dt><?= $icon('clock', 13) ?> <?= e(trans('contact.hours_label')) ?></dt>
                    <dd><?= e(trans('contact.hours_value')) ?></dd>
                </dl>
            </article>

            <article class="cpage-card">
                <span class="cpage-card-icon" aria-hidden="true"><?= $icon('mail', 21) ?></span>
                <h2><?= e(trans('contact.email_title')) ?></h2>
                <p class="cpage-card-desc"><?= e(trans('contact.email_desc')) ?></p>
                <a class="cpage-strong" href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
            </article>

            <article class="cpage-card">
                <span class="cpage-card-icon" aria-hidden="true"><?= $icon('factory', 21) ?></span>
                <h2><?= e(trans('contact.factory_title')) ?></h2>
                <p class="cpage-company"><?= e(trans('contact.factory_company')) ?></p>
                <p class="cpage-card-desc">
                    <?= e(trans('common.factory_address')) ?><br>
                    <?= e(trans('common.factory_logistics')) ?>
                </p>
                <a class="cpage-maplink" href="<?= e($mapUrl) ?>" <?= external_link_attrs($mapUrl) ?>>
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

            <form class="cpage-form" data-contact-form data-mail-to="<?= e($email) ?>" data-loader-skip>
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
                    <a class="cpage-line" href="<?= e($line['href']) ?>">
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
