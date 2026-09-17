<?php
/** @var \App\Services\SEOService|null $seo */
$meta = $seo !== null ? $seo->toArray() : (array) config('seo.defaults', []);
$keywords = is_array($meta['keywords'] ?? null) ? implode(', ', $meta['keywords']) : (string) ($meta['keywords'] ?? '');
$canonical = $meta['canonical'] ?? request()->url();
?>
<title>Lufly</title>
<meta name="description" content="<?= e($meta['description'] ?? '') ?>">
<?php if ($keywords !== ''): ?><meta name="keywords" content="<?= e($keywords) ?>"><?php endif; ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($meta['title'] ?? '') ?>">
<meta property="og:description" content="<?= e($meta['description'] ?? '') ?>">
<meta property="og:site_name" content="<?= e($meta['site_name'] ?? $appName) ?>">
<?php if (($meta['image'] ?? '') !== ''): ?><meta property="og:image" content="<?= e($meta['image']) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<?php
if ($seo !== null) {
    foreach ($seo->structuredData() as $block) {
        $payload = array_merge(['@context' => 'https://schema.org', '@type' => $block['type']], $block['data']);
        echo '<script type="application/ld+json">' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
