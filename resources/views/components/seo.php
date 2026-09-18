<?php
/**
 * Global SEO & Social Meta Component
 * @var \App\Services\SEOService|null $seo
 * @var string|null $title
 */

$defaults = (array) config('seo.defaults', []);
$meta = $seo !== null ? $seo->toArray() : $defaults;

$pageTitle = !empty($title) ? $title : ($meta['title'] ?? 'LUFLY | European Architectural Sanitary Ware');
$description = (string) ($meta['description'] ?? $defaults['description'] ?? '');
$keywords = is_array($meta['keywords'] ?? null)
    ? implode(', ', $meta['keywords'])
    : (string) ($meta['keywords'] ?? implode(', ', (array) ($defaults['keywords'] ?? [])));

$currentUrl = (string) request()->url();
$canonical = $meta['canonical'] ?? $currentUrl;
$robots = $meta['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
$ogType = $meta['type'] ?? 'website';
$siteName = $meta['site_name'] ?? 'LUFLY Architectural Sanitary Ware';
$image = !empty($meta['image']) ? asset($meta['image']) : asset('/images/lifestyle/heroc-1.webp');

$translator = app('Core\Localization\Translator');
$currentLocale = $translator instanceof \Core\Localization\Translator ? $translator->getLocale() : 'en';
$supportedLocales = $translator instanceof \Core\Localization\Translator ? $translator->supported() : ['en' => 'English', 'ar' => 'العربية', 'tr' => 'Türkçe', 'cs' => 'Čeština'];

$localeMap = [
    'en' => 'en_US',
    'ar' => 'ar_AE',
    'tr' => 'tr_TR',
    'cs' => 'cs_CZ',
];
$ogLocale = $localeMap[$currentLocale] ?? 'en_US';
?>
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($description) ?>">
<?php if ($keywords !== ''): ?>
<meta name="keywords" content="<?= e($keywords) ?>">
<?php endif; ?>
<meta name="robots" content="<?= e($robots) ?>">
<meta name="author" content="LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ">
<meta name="publisher" content="LUFLY">
<link rel="canonical" href="<?= e($canonical) ?>">

<!-- Multilingual SEO / Hreflang Tags -->
<?php foreach ($supportedLocales as $code => $name): 
    $altUrl = url('/' . $code . (parse_url($canonical, PHP_URL_PATH) ? preg_replace('#^/(?:en|ar|tr|cs)#', '', parse_url($canonical, PHP_URL_PATH)) : ''));
?>
<link rel="alternate" hreflang="<?= e($code) ?>" href="<?= e($altUrl) ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?= e(url('/en')) ?>">

<!-- Open Graph / Facebook -->
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

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">
<meta name="twitter:image" content="<?= e($image) ?>">
<meta name="twitter:image:alt" content="<?= e($pageTitle) ?>">

<!-- Global Organization & WebSite JSON-LD Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "<?= e(url('/#organization')) ?>",
      "name": "LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ",
      "alternateName": "LUFLY",
      "url": "<?= e(url('/')) ?>",
      "logo": {
        "@type": "ImageObject",
        "url": "<?= e(asset('images/logo.png')) ?>",
        "caption": "LUFLY Sanitary Architecture"
      },
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Gaziantep",
        "addressCountry": "TR"
      },
      "contactPoint": [
        {
          "@type": "ContactPoint",
          "telephone": "+90-850-3040-817",
          "contactType": "customer service",
          "email": "info@lufly.tr",
          "availableLanguage": ["English", "Arabic", "Turkish", "Czech"]
        }
      ]
    },
    {
      "@type": "WebSite",
      "@id": "<?= e(url('/#website')) ?>",
      "url": "<?= e(url('/')) ?>",
      "name": "LUFLY",
      "description": "European Architectural Sanitary Ware Manufacturer & Global Export",
      "publisher": {
        "@id": "<?= e(url('/#organization')) ?>"
      },
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= e(url('/products?q={search_term_string}')) ?>",
        "query-input": "required name=search_term_string"
      }
    }
  ]
}
</script>
<?php
if ($seo !== null) {
    foreach ($seo->structuredData() as $block) {
        $payload = array_merge(['@context' => 'https://schema.org', '@type' => $block['type']], $block['data']);
        echo '<script type="application/ld+json">' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
?>
