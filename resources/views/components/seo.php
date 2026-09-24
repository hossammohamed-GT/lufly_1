<?php
$defaults = (array) config('seo.defaults', []);
$meta = $seo !== null ? $seo->toArray() : $defaults;

$pageTitle = !empty($title) ? $title : ($meta['title'] ?? 'LUFLY | European Architectural Sanitary Ware');
$description = (string) ($meta['description'] ?? $defaults['description'] ?? '');
$keywords = is_array($meta['keywords'] ?? null)
    ? implode(', ', $meta['keywords'])
    : (string) ($meta['keywords'] ?? implode(', ', (array) ($defaults['keywords'] ?? [])));

$currentUrl = (string) request()->url();
$canonical = $meta['canonical'] ?? $currentUrl;
$ogType = $meta['type'] ?? 'website';
$siteName = $meta['site_name'] ?? 'LUFLY Architectural Sanitary Ware';
$rawImage = (string) ($meta['image'] ?? '');
$image = $rawImage !== ''
    ? (preg_match('#^https?://#i', $rawImage) ? $rawImage : asset($rawImage))
    : asset('/images/lifestyle/heroc-1.webp');

$robots = $meta['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
if (isset($status) && (int) $status >= 400) {
    $robots = 'noindex, nofollow';
}

$translator = app('Core\Localization\Translator');
$currentLocale = $translator instanceof \Core\Localization\Translator ? $translator->getLocale() : 'en';
$supportedLocales = $translator instanceof \Core\Localization\Translator ? $translator->supported() : ['en' => 'English', 'tr' => 'Türkçe', 'cs' => 'Čeština'];

$localeMap = ['en' => 'en_US', 'tr' => 'tr_TR', 'cs' => 'cs_CZ'];
$ogLocale = $localeMap[$currentLocale] ?? 'en_US';

$alternates = $seo !== null ? $seo->alternates() : [];
$xDefault = null;
if ($alternates !== []) {
    $defaultLocale = (string) config('localization.default', 'en');
    $xDefault = $alternates[$defaultLocale] ?? reset($alternates);
} elseif ($seo === null && !isset($status)) {
    foreach (array_keys($supportedLocales) as $code) {
        $path = (string) (parse_url($canonical, PHP_URL_PATH) ?? '');
        $alternates[$code] = url('/' . $code . preg_replace('#^/(?:en|tr|cs)#', '', $path));
    }
    $xDefault = url('/en');
}

$verification = (array) config('seo.verification', []);
$twitterHandle = (string) ($defaults['twitter_handle'] ?? '');
$business = (array) config('seo.business', []);

$searchPath = '/' . $currentLocale;
if ($translator instanceof \Core\Localization\Translator) {
    $translated = $translator->trans('routes.products.index', [], $currentLocale);
    if ($translated !== 'routes.products.index') {
        $searchPath .= '/' . ltrim($translated, '/');
    }
}

$orgId = url('/#organization');
$siteId = url('/#website');
?>
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($description) ?>">
<?php if ($keywords !== ''): ?>
<meta name="keywords" content="<?= e($keywords) ?>">
<?php endif; ?>
<meta name="robots" content="<?= e($robots) ?>">
<meta name="author" content="<?= e($business['legal_name'] ?? 'LUFLY') ?>">
<meta name="publisher" content="LUFLY">
<link rel="canonical" href="<?= e($canonical) ?>">

<?php if (!empty($verification['google'])): ?>
<meta name="google-site-verification" content="<?= e($verification['google']) ?>">
<?php endif; ?>
<?php if (!empty($verification['bing'])): ?>
<meta name="msvalidate.01" content="<?= e($verification['bing']) ?>">
<?php endif; ?>
<?php foreach ($alternates as $code => $altUrl): ?>
<link rel="alternate" hreflang="<?= e((string) $code) ?>" href="<?= e($altUrl) ?>">
<?php endforeach; ?>
<?php if ($xDefault !== null): ?>
<link rel="alternate" hreflang="x-default" href="<?= e($xDefault) ?>">
<?php endif; ?>
<meta property="og:type" content="<?= e($ogType) ?>">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($image) ?>">
<meta property="og:image:secure_url" content="<?= e($image) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= e($pageTitle) ?>">
<meta property="og:locale" content="<?= e($ogLocale) ?>">
<?php foreach ($localeMap as $c => $loc): ?>
<?php if ($c !== $currentLocale): ?>
<meta property="og:locale:alternate" content="<?= e($loc) ?>">
<?php endif; ?>
<?php endforeach; ?>
<meta name="twitter:card" content="summary_large_image">
<?php if ($twitterHandle !== ''): ?>
<meta name="twitter:site" content="<?= e($twitterHandle) ?>">
<?php endif; ?>
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">
<meta name="twitter:image" content="<?= e($image) ?>">
<meta name="twitter:image:alt" content="<?= e($pageTitle) ?>">
<?php
$graph = [
    [
        '@type' => 'Organization',
        '@id' => $orgId,
        'name' => $business['legal_name'] ?? 'LUFLY',
        'alternateName' => $business['brand_name'] ?? 'LUFLY',
        'url' => url('/'),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => asset('images/logo.png'),
            'caption' => 'LUFLY Sanitary Architecture',
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $business['street'] ?? '',
            'addressLocality' => $business['city'] ?? 'Gaziantep',
            'addressRegion' => $business['region'] ?? 'Gaziantep',
            'postalCode' => $business['postal_code'] ?? '',
            'addressCountry' => $business['country'] ?? 'TR',
        ],
        'contactPoint' => [[
            '@type' => 'ContactPoint',
            'telephone' => $business['telephone'] ?? '',
            'contactType' => 'customer service',
            'email' => $business['email'] ?? '',
            'availableLanguage' => ['English', 'Turkish', 'Czech'],
        ]],
    ],
    [
        '@type' => 'LocalBusiness',
        '@id' => url('/#localbusiness'),
        'name' => $business['brand_name'] ?? 'LUFLY',
        'description' => $business['description'] ?? '',
        'url' => url('/'),
        'image' => $image,
        'telephone' => $business['telephone'] ?? '',
        'email' => $business['email'] ?? '',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $business['street'] ?? '',
            'addressLocality' => $business['city'] ?? 'Gaziantep',
            'addressRegion' => $business['region'] ?? 'Gaziantep',
            'postalCode' => $business['postal_code'] ?? '',
            'addressCountry' => $business['country'] ?? 'TR',
        ],
        'parentOrganization' => ['@id' => $orgId],
        'priceRange' => '$$',
    ],
    [
        '@type' => 'WebSite',
        '@id' => $siteId,
        'url' => url('/'),
        'name' => $business['brand_name'] ?? 'LUFLY',
        'description' => $business['description'] ?? '',
        'inLanguage' => array_keys($supportedLocales),
        'publisher' => ['@id' => $orgId],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url($searchPath) . '?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ],
];

if (!empty($business['same_as'])) {
    $graph[0]['sameAs'] = array_values((array) $business['same_as']);
}
if (!empty($business['latitude']) && !empty($business['longitude'])) {
    $graph[1]['geo'] = [
        '@type' => 'GeoCoordinates',
        'latitude' => (float) $business['latitude'],
        'longitude' => (float) $business['longitude'],
    ];
}
?>
<script type="application/ld+json">
<?= json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
<?php
if ($seo !== null) {
    foreach ($seo->structuredData() as $block) {
        $payload = array_merge(['@context' => 'https://schema.org', '@type' => $block['type']], $block['data']);
        echo '<script type="application/ld+json">' . json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
