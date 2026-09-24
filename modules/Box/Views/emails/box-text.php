<?php
$audience = (string) ($audience ?? 'admin');
$admin = $audience !== 'visitor';
$saved = $audience === 'saved';
$lines = [];

$lines[] = trans('box.eyebrow');
$lines[] = str_repeat('-', 48);
$lines[] = $saved
    ? trans('box.mail_saved_hello', ['n' => (string) $count])
    : ($admin ? trans('box.mail_admin_hello', ['n' => (string) $count]) : trans('box.mail_copy_hello'));
$lines[] = '';

if ($admin) {
    $lines[] = trans('box.mail_admin_email', ['email' => $email !== '' ? $email : trans('box.mail_no_email')]);

    if ($saved) {
        $lines[] = trans('box.mail_saved_text');
    }

    if ($note !== '') {
        $lines[] = '';
        $lines[] = $note;
    }
} else {
    $lines[] = trans('box.mail_copy_note', ['email' => $email]);
}

$lines[] = '';
$lines[] = trans('box.list_title') . ': ' . $count;

foreach ($items as $index => $item) {
    $lines[] = sprintf(
        '%02d. %s%s — %s',
        (int) $index + 1,
        (string) ($item['name'] ?? ''),
        trim((string) ($item['size'] ?? '')) !== '' ? ' (' . $item['size'] . ')' : '',
        (string) ($item['box_url'] ?? ''),
    );
}

$lines[] = '';
$lines[] = trans('box.mail_open') . ': ' . $boxUrl;
$lines[] = trans('box.mail_foot');

echo implode("\n", $lines) . "\n";
