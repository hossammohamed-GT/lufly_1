<?php
$ink = '#102a2a';
$muted = '#617674';
$line = '#d3deda';
$teal = '#1c8b8b';
$page = '#edf3f1';
?>
<!DOCTYPE html>
<html lang="<?= e($locale ?? 'en') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e((string) $adminSubject) ?></title>
</head>
<body style="margin:0; padding:0; background:<?= e($page) ?>; font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:<?= e($ink) ?>;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?= e($page) ?>; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0"
                   style="width:640px; max-width:100%; background:#ffffff; border:1px solid <?= e($line) ?>; border-radius:14px; overflow:hidden;">
                <tr>
                    <td style="background:#0c4347; padding:20px 26px;">
                        <span style="font-size:18px; font-weight:800; letter-spacing:0.3em; color:#ffffff;">LUFLY</span>
                        <span style="float:right; font-size:10px; font-weight:700; letter-spacing:0.2em; text-transform:uppercase; color:#7fe2d0;">
                            <?= e(trans('favorites.admin_eyebrow')) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 26px 6px;">
                        <h1 style="margin:0 0 8px; font-size:20px; line-height:1.3;">
                            <?= e(trans('favorites.mail_admin_intro')) ?>
                        </h1>
                        <p style="margin:0; font-size:14px; line-height:1.6; color:<?= e($muted) ?>;">
                            <?= e(\Modules\Favorites\Support\Text::count('favorites.mail_admin_meta', $count)) ?>
                        </p>
                        <p style="margin:10px 0 0; font-size:14px; line-height:1.6;">
                            <strong><?= e(trans('favorites.admin_visitor')) ?></strong>
                            <a href="mailto:<?= e($email) ?>" style="color:<?= e($teal) ?>; text-decoration:none;"><?= e($email) ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 26px 8px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                            <tr>
                                <th align="left" style="font-size:10px; letter-spacing:0.16em; text-transform:uppercase; color:<?= e($muted) ?>; padding:0 0 8px; border-bottom:1px solid <?= e($line) ?>;">#</th>
                                <th align="left" style="font-size:10px; letter-spacing:0.16em; text-transform:uppercase; color:<?= e($muted) ?>; padding:0 0 8px; border-bottom:1px solid <?= e($line) ?>;"><?= e(trans('favorites.admin_product')) ?></th>
                                <th align="left" style="font-size:10px; letter-spacing:0.16em; text-transform:uppercase; color:<?= e($muted) ?>; padding:0 0 8px; border-bottom:1px solid <?= e($line) ?>;"><?= e(trans('favorites.admin_model')) ?></th>
                                <th align="right" style="font-size:10px; letter-spacing:0.16em; text-transform:uppercase; color:<?= e($muted) ?>; padding:0 0 8px; border-bottom:1px solid <?= e($line) ?>;">&nbsp;</th>
                            </tr>
                            <?php foreach ($items as $index => $item): ?>
                                <tr>
                                    <td style="padding:12px 0; font-family:'SFMono-Regular',Menlo,Consolas,monospace; font-size:11px; color:<?= e($muted) ?>; border-bottom:1px solid #eef2f1; vertical-align:top;">
                                        <?= str_pad((string) ((int) $index + 1), 2, '0', STR_PAD_LEFT) ?>
                                    </td>
                                    <td style="padding:12px 10px 12px 0; font-size:14px; font-weight:600; border-bottom:1px solid #eef2f1; vertical-align:top;">
                                        <?= e((string) ($item['name'] ?? '')) ?>
                                    </td>
                                    <td style="padding:12px 0; font-family:'SFMono-Regular',Menlo,Consolas,monospace; font-size:11px; letter-spacing:0.08em; color:<?= e($teal) ?>; border-bottom:1px solid #eef2f1; vertical-align:top;">
                                        <?= e((string) ($item['model_code'] ?? $item['sku'] ?? '')) ?>
                                    </td>
                                    <td align="right" style="padding:12px 0; border-bottom:1px solid #eef2f1; vertical-align:top;">
                                        <a href="<?= e((string) ($item['fav_url'] ?? '#')) ?>" style="font-size:11px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:<?= e($teal) ?>; text-decoration:none;">
                                            <?= e(trans('favorites.admin_open')) ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 26px 26px;">
                        <a href="<?= e($listUrl) ?>" style="display:inline-block; padding:11px 20px; border-radius:999px; background:#0c4347; color:#ffffff; font-size:11px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; text-decoration:none;">
                            <?= e(trans('favorites.mail_list_button')) ?>
                        </a>
                        <p style="margin:16px 0 0; font-size:12px; line-height:1.6; color:<?= e($muted) ?>;">
                            <?= e(trans('favorites.admin_reply_hint')) ?>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
