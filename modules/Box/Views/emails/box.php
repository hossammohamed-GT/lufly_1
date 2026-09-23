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
                                $url = (string) ($item['box_url'] ?? '#');
                                $image = (string) ($item['image'] ?? '');
                                $code = (string) ($item['model_code'] ?? $item['sku'] ?? '');
                                $size = trim((string) ($item['size'] ?? ''));
                                ?>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #f0ebe2;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="86" valign="top" style="padding-right:14px;">
                                                    <img src="<?= e($image !== '' ? asset($image) : $fallback) ?>" alt=""
                                                         width="86" height="62"
                                                         style="display:block;width:86px;height:62px;object-fit:cover;border-radius:10px;border:1px solid #eee7dc;">
                                                </td>
                                                <td valign="top">
                                                    <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#9a8f7c;">
                                                        <?= str_pad((string) ((int) $index + 1), 2, '0', STR_PAD_LEFT) ?>
                                                        <?php if ($code !== ''): ?>
                                                            · <?= e($code) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <a href="<?= e($url) ?>" style="display:block;margin-top:4px;font-size:15px;font-weight:600;color:#1f2421;text-decoration:none;">
                                                        <?= e($name) ?>
                                                    </a>
                                                    <?php if ($size !== ''): ?>
                                                        <div style="margin-top:4px;font-size:13px;color:#7a7264;"><?= e($size) ?></div>
                                                    <?php endif; ?>
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
                    <td style="padding:20px 28px 8px;">
                        <a href="<?= e($boxUrl) ?>"
                           style="display:inline-block;padding:13px 22px;border-radius:999px;background:#1f2421;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;">
                            <?= e(trans('box.mail_open')) ?>
                        </a>
                        <p style="margin:12px 0 0;font-size:13px;line-height:1.6;color:#7a7264;">
                            <?= e(trans('box.mail_link_hint')) ?>
                        </p>
                        <p style="margin:6px 0 0;font-size:12px;word-break:break-all;color:#9a8f7c;"><?= e($boxUrl) ?></p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 28px 26px;border-top:1px solid #eee7dc;">
                        <p style="margin:0;font-size:12px;line-height:1.6;color:#9a8f7c;">
                            <?= e(trans('box.mail_foot')) ?> ·
                            <a href="<?= e($site) ?>" style="color:#9a8f7c;"><?= e(parse_url((string) $site, PHP_URL_HOST) ?: $site) ?></a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
