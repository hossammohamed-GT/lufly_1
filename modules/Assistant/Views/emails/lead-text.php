<?php
$lines = [
    trans('assistant.mail_subject', ['n' => (string) $lead->id]),
    str_repeat('-', 40),
    'Name: ' . (string) $lead->email,
];

if ($visitor !== '') {
    $lines[] = 'Visitor typed: ' . $visitor;
}

if (trim((string) $lead->message) !== '') {
    $lines[] = 'Message: ' . (string) $lead->message;
}

if ((string) $lead->summary !== '') {
    $lines[] = 'What it is: ' . (string) $lead->summary;
}

if ($terms !== '') {
    $lines[] = 'Search words: ' . $terms;
}

if ($product_url !== '') {
    $lines[] = 'Product page: ' . $product_url;
}

if ($image !== '') {
    $lines[] = 'Photo: ' . $image;
}

$lines[] = 'Matches shown: ' . (int) $lead->results_count;
$lines[] = 'When: ' . (string) $lead->created_at;
$lines[] = $site;

echo implode("\n", $lines);
