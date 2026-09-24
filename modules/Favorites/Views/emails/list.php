<?php
$brand = '#0c4347';
$teal = '#1c8b8b';
$tealDark = '#147174';
$ink = '#102a2a';
$muted = '#617674';
$line = '#d3deda';
$page = '#edf3f1';
$surface = '#ffffff';

$year = date('Y');
$fallbackImg = asset('/images/products/prod_146_1620-111-a.jpg');
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<title><?= e(trans('favorites.mail_title')) ?></title>
</head>
<body style="margin:0; padding:0; background:<?= e($page) ?>; font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:<?= e($ink) ?>;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">
    <?= e(\Modules\Favorites\Support\Text::count('favorites.mail_preheader', $count)) ?>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?= e($page) ?>; padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="620" cellpadding="0" cellspacing="0" border="0"
                   style="width:620px; max-width:100%; background:<?= e($surface) ?>; border:1px solid <?= e($line) ?>; border-radius:16px; overflow:hidden;">
<tr>
                    <td style="background:<?= e($brand) ?>; padding:26px 30px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="font-size:22px; font-weight:800; letter-spacing:0.34em; color:#ffffff;">LUFLY</td>
                                <td align="right" style="font-size:10px; font-weight:700; letter-spacing:0.22em; color:#7fe2d0; text-transform:uppercase;">
                                    <?= e(trans('favorites.eyebrow')) ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
<tr>
                    <td style="padding:30px 30px 6px;">
                        <h1 style="margin:0 0 8px; font-size:26px; line-height:1.2; color:<?= e($ink) ?>;">
                            <?= e(trans('favorites.mail_title')) ?>
                        </h1>
                        <p style="margin:0; font-size:15px; line-height:1.6; color:<?= e($muted) ?>;">
                            <?= e(\Modules\Favorites\Support\Text::count('favorites.mail_intro', $count)) ?>
                        </p>
                    </td>
                </tr>
<tr>
                    <td style="padding:18px 30px 4px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                            <?php foreach ($items as $index => $item): ?>
                                <?php
                                $name = (string) ($item['name'] ?? '');
                                $code = (string) ($item['model_code'] ?? $item['sku'] ?? '');
                                $url = (string) ($item['fav_url'] ?? '#');
                                $image = (string) ($item['image'] ?? '');
                                ?>
                                <tr>
                                    <td style="padding:16px 0; border-top:1px solid <?= e($line) ?>;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="64" valign="top" style="width:64px;">
                                                    <div style="width:56px; height:56px; border:1px solid <?= e($line) ?>; border-radius:10px; background:#ffffff; overflow:hidden;">
                                                        <img src="<?= e($image !== '' ? asset($image) : $fallbackImg) ?>"
                                                             alt="" width="56" height="56"
                                                             style="display:block; width:56px; height:56px; object-fit:contain;">
                                                    </div>
                                                </td>
                                                <td valign="top" style="padding-left:16px;">
                                                    <div style="font-family:'SFMono-Regular',Menlo,Consolas,monospace; font-size:10px; font-weight:700; letter-spacing:0.16em; text-transform:uppercase; color:<?= e($tealDark) ?>;">
                                                        <?= str_pad((string) ((int) $index + 1), 2, '0', STR_PAD_LEFT) ?>
                                                        &nbsp;·&nbsp; <?= e($code) ?>
                                                    </div>
                                                    <div style="font-size:16px; font-weight:700; line-height:1.35; color:<?= e($ink) ?>; padding-top:5px;">
                                                        <?= e($name) ?>
                                                    </div>
                                                    <div style="padding-top:10px;">
                                                        <a href="<?= e($url) ?>"
                                                           style="display:inline-block; padding:9px 16px; border-radius:999px; background:<?= e($teal) ?>; color:#ffffff; font-size:11px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; text-decoration:none;">
                                                            <?= e(trans('favorites.mail_open_product')) ?> &rarr;
                                                        </a>
                                                        <span style="display:inline-block; padding-left:12px; font-size:12px; color:<?= e($muted) ?>;">
                                                            <?= e(trans('favorites.mail_direct_link')) ?>
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                </tr>
<tr>
                    <td style="padding:8px 30px 30px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                               style="background:#f3f7f5; border:1px solid <?= e($line) ?>; border-radius:12px;">
                            <tr>
                                <td style="padding:20px 22px;">
                                    <p style="margin:0 0 12px; font-size:14px; line-height:1.6; color:<?= e($ink) ?>;">
                                        <strong><?= e(trans('favorites.mail_list_title')) ?></strong><br>
                                        <span style="color:<?= e($muted) ?>;"><?= e(trans('favorites.mail_list_hint')) ?></span>
                                    </p>
                                    <a href="<?= e($listUrl) ?>"
                                       style="display:inline-block; padding:12px 22px; border-radius:999px; background:<?= e($brand) ?>; color:#ffffff; font-size:12px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; text-decoration:none;">
                                        <?= e(trans('favorites.mail_list_button')) ?>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
<tr>
                    <td style="background:#f3f7f5; border-top:1px solid <?= e($line) ?>; padding:22px 30px;">
                        <p style="margin:0 0 10px; font-size:13px; line-height:1.6; color:<?= e($muted) ?>;">
                            <?= e(trans('favorites.mail_why')) ?>
                        </p>
                        <p style="margin:0 0 6px; font-size:13px; line-height:1.6; color:<?= e($ink) ?>;">
                            <?= e(trans('favorites.mail_contact')) ?>
                        </p>
                        <p style="margin:0; font-size:13px; line-height:1.7; color:<?= e($muted) ?>;">
                            <a href="https://wa.me/908503040817" style="color:<?= e($tealDark) ?>; text-decoration:none;">WhatsApp +90 850 3040 817</a>
                            &nbsp;·&nbsp;
                            <a href="mailto:<?= e((string) config('mail.admin_address', 'info@lufly.tr')) ?>" style="color:<?= e($tealDark) ?>; text-decoration:none;"><?= e((string) config('mail.admin_address', 'info@lufly.tr')) ?></a>
                        </p>
                        <p style="margin:14px 0 0; font-size:11px; letter-spacing:0.16em; text-transform:uppercase; color:#8aa09a;">
                            LUFLY &middot; Gaziantep &middot; <?= e((string) $year) ?>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
