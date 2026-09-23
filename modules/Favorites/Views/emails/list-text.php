<?php
/**
 * Plain-text twin of the saved-list e-mail (same content, no markup).
 *
 * @var array<int, array<string, mixed>> $items
 * @var int $count
 * @var string $listUrl
 */
$lines = [];
$lines[] = 'LUFLY — ' . trans('favorites.mail_title');
$lines[] = '';
$lines[] = \Modules\Favorites\Support\Text::count('favorites.mail_intro', $count);
$lines[] = '';

foreach ($items as $index => $item) {
    $lines[] = str_pad((string) ((int) $index + 1), 2, '0', STR_PAD_LEFT) . '. ' . (string) ($item['name'] ?? '');
    $code = (string) ($item['model_code'] ?? $item['sku'] ?? '');
    if ($code !== '') {
        $lines[] = '    ' . $code;
    }
    $lines[] = '    ' . (string) ($item['fav_url'] ?? '');
    $lines[] = '';
}

$lines[] = trans('favorites.mail_list_title');
$lines[] = trans('favorites.mail_list_hint');
$lines[] = $listUrl;
$lines[] = '';
$lines[] = trans('favorites.mail_contact');
$lines[] = 'WhatsApp +90 850 3040 817 · ' . (string) config('mail.admin_address', 'info@lufly.tr');
$lines[] = trans('favorites.mail_why');
$lines[] = '';
$lines[] = 'LUFLY · Gaziantep · ' . date('Y');

echo implode("\n", $lines) . "\n";
