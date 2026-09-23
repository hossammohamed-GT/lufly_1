<?php
/**
 * The box in an inbox — the team's copy and the visitor's copy.
 *
 * One template, two audiences: the shop reads "a visitor asked for prices on
 * these pieces" and can open the box on the site from the same link the visitor
 * has, the visitor reads his own list back with the same link.
 *
 * @var string $audience  'admin' (it was sent) | 'saved' (still being filled) | 'visitor'
 * @var array<int, array<string, mixed>> $items
 * @var int $count
 * @var string $boxUrl
 * @var string $email
 * @var string $note
 * @var string $site
 */
$audience = (string) ($audience ?? 'admin');
$admin = $audience !== 'visitor';
$saved = $audience === 'saved';
$fallback = rtrim((string) ($site ?? ''), '/') . '/images/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(trans('box.title')) ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f2ef;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2421;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f2ef;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e4ded4;">
                <tr>
                    <td style="padding:26px 28px 18px;border-bottom:1px solid #eee7dc;">
                        <div style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#9a8f7c;"><?= e(trans('box.eyebrow')) ?></div>
                        <h1 style="margin:8px 0 0;font-size:22px;line-height:1.3;font-weight:600;">
                            <?= e($saved
                                ? trans('box.mail_saved_hello', ['n' => (string) $count])
                                : ($admin ? trans('box.mail_admin_hello', ['n' => (string) $count]) : trans('box.mail_copy_hello'))) ?>
                        </h1>
                        <?php if ($admin): ?>
                            <p style="margin:10px 0 0;font-size:14px;line-height:1.6;color:#5d564b;">
                                <?= e(trans('box.mail_admin_email', ['email' => $email !== '' ? $email : trans('box.mail_no_email')])) ?>
                            </p>
                            <?php if ($saved): ?>
                                <p style="margin:10px 0 0;font-size:14px;line-height:1.6;color:#5d564b;">
                                    <?= e(trans('box.mail_saved_text')) ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($note !== ''): ?>
                                <p style="margin:10px 0 0;padding:10px 12px;background:#faf7f2;border-left:3px solid #c8a86b;font-size:14px;line-height:1.6;color:#3d3730;">
                                    <?= e($note) ?>
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="margin:10px 0 0;font-size:14px;line-height:1.6;color:#5d564b;">
                                <?= e(trans('box.mail_copy_note', ['email' => $email])) ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 28px 6px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <?php foreach ($items as $index => $item): ?>
                                <?php
                                $name = (string) ($item['name'] ?? '');
                                $size = trim((string) ($item['size'] ?? ''));
                                $url = (string) ($item['box_url'] ?? '');
                                $code = (string) ($item['code'] ?? '');
                                ?>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #f0ece4;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="46" valign="top" style="font-family:'SFMono-Regular',Consolas,monospace;font-size:12px;color:#a2988a;">
                                                    <?= e(sprintf('%02d', (int) $index + 1)) ?>
                                                </td>
                                                <td valign="top">
                                                    <a href="<?= e($url) ?>" style="color:#1f2421;font-size:15px;font-weight:600;text-decoration:none;"><?= e($name) ?></a>
                                                    <div style="margin-top:4px;font-size:12.5px;color:#8c8478;">
                                                        <?= e($code) ?><?= $size !== '' ? ' · ' . e($size) : '' ?>
                                                    </div>
                                                </td>
                                                <td valign="top" align="right" width="120">
                                                    <a href="<?= e($url) ?>" style="display:inline-block;padding:8px 12px;border:1px solid #ded7cb;border-radius:9px;font-size:12.5px;color:#3d3730;text-decoration:none;"><?= e(trans('box.open_product')) ?></a>
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
                    <td style="padding:10px 28px 24px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf7f2;border-radius:12px;">
                            <tr>
                                <td style="padding:16px 18px;">
                                    <div style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#9a8f7c;"><?= e(trans('box.link_eyebrow')) ?></div>
                                    <p style="margin:8px 0 10px;font-size:13.5px;line-height:1.6;color:#5d564b;"><?= e(trans('box.mail_link_hint')) ?></p>
                                    <a href="<?= e($boxUrl) ?>" style="display:inline-block;padding:11px 18px;background:#1f2421;border-radius:10px;color:#ffffff;font-size:13.5px;font-weight:600;text-decoration:none;"><?= e(trans('box.mail_open')) ?></a>
                                    <div style="margin-top:10px;font-size:12px;color:#8c8478;word-break:break-all;"><?= e($boxUrl) ?></div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 28px 26px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="left" valign="middle" style="font-size:11.5px;color:#a2988a;">
                                    <img src="<?= e($fallback) ?>" alt="" width="64" style="display:inline-block;vertical-align:middle;opacity:.75;">
                                    <span style="display:inline-block;vertical-align:middle;margin-left:8px;"><?= e(trans('box.mail_foot')) ?></span>
                                </td>
                                <td align="right" valign="middle" style="font-size:12px;color:#8c8478;">
                                    <a href="<?= e(rtrim((string) ($site ?? ''), '/')) ?>" style="color:#3d3730;text-decoration:none;"><?= e(preg_replace('#^https?://#', '', rtrim((string) ($site ?? ''), '/'))) ?></a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
