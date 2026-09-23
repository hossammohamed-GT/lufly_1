<?php
/**
 * The mail the shop gets when a visitor asks the finder for a piece — with the
 * photo as a link, so the picture opens straight from the inbox.
 *
 * @var Core\View\View $view
 * @var \Modules\Assistant\Models\AssistantLead $lead
 * @var string $image      public link to the photo ('' when there is none)
 * @var string $visitor    the address the visitor typed ('' when skipped)
 * @var string $product_url the product page the question came from
 * @var string $terms
 * @var string $site
 */
$rows = array_filter([
    'Request' => '#' . (int) $lead->id,
    'Name' => (string) $lead->email,
    'Visitor typed' => $visitor,
    'Language' => (string) $lead->locale,
    'Asked with' => $lead->source === 'photo' ? 'a photo' : 'a description',
    'What it is' => (string) $lead->summary,
    'Search words' => $terms,
    'Matches shown' => (string) (int) $lead->results_count,
    'Product page' => $product_url,
    'When' => (string) $lead->created_at,
], static fn (string $value): bool => trim($value) !== '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(trans('assistant.mail_subject', ['n' => (string) $lead->id])) ?></title>
</head>
<body style="margin:0;padding:24px;background:#f4f5f7;font-family:Inter,Segoe UI,Arial,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;">
    <tr>
        <td style="padding:20px 24px;border-bottom:1px solid #e5e7eb;">
            <strong style="font-size:15px;letter-spacing:.08em;text-transform:uppercase;">LUFLY</strong>
            <div style="font-size:13px;color:#6b7280;margin-top:4px;"><?= e(trans('assistant.mail_subject', ['n' => (string) $lead->id])) ?></div>
        </td>
    </tr>

    <?php if ($image !== ''): ?>
        <tr>
            <td style="padding:20px 24px 0;">
                <a href="<?= e($image) ?>" style="display:block;">
                    <img src="<?= e($image) ?>" alt="<?= e((string) $lead->image_name) ?>"
                         style="display:block;width:100%;max-width:592px;height:auto;border:1px solid #e5e7eb;">
                </a>
                <p style="margin:10px 0 0;font-size:13px;">
                    <a href="<?= e($image) ?>" style="color:#0f766e;"><?= e($image) ?></a>
                </p>
            </td>
        </tr>
    <?php endif; ?>

    <tr>
        <td style="padding:20px 24px;">
            <?php if (trim((string) $lead->message) !== ''): ?>
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;white-space:pre-line;"><?= e((string) $lead->message) ?></p>
            <?php endif; ?>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
                <?php foreach ($rows as $label => $value): ?>
                    <tr>
                        <td style="padding:6px 12px 6px 0;color:#6b7280;white-space:nowrap;vertical-align:top;"><?= e($label) ?></td>
                        <td style="padding:6px 0;color:#111827;word-break:break-word;"><?= e($value) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:16px 24px;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">
            <?= e($site) ?> · <?= e(trans('assistant.foot')) ?>
        </td>
    </tr>
</table>
</body>
</html>
