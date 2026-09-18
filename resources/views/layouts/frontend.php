<?php
/** @var Core\View\View $view */
/** @var string $content */
/** @var string $title */
$translator = $translator ?? null;
$locale = $translator instanceof \Core\Localization\Translator ? $translator->getLocale() : (string) config('localization.default', 'en');
$basePath = rtrim((string) parse_url(url('/'), PHP_URL_PATH), '/');
$direction = in_array($locale, ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr';

/* Chrome first: the navbar declares its own stylesheet and controller, so it has
   to render before the asset lists are collected for <head>. */
$navbar = $translator !== null
    ? $view->renderFile($view->resolvePath('components.navbar'), [])
    : '';

$styles = $view->styles();
$scripts = $view->scripts();
$preloads = $view->preloads();

/* Third-party origins get a preconnect instead of a blocking request. */
$externalOrigins = [];

foreach ($styles as $style) {
    $scheme = parse_url($style, PHP_URL_SCHEME);
    $host = parse_url($style, PHP_URL_HOST);

    if (is_string($scheme) && $scheme !== '' && is_string($host) && $host !== '') {
        $externalOrigins[$scheme . '://' . $host] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>" dir="<?= e($direction) ?>" data-theme="light" data-base="<?= e($basePath) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="<?= e(asset('images/logo.png')) ?>">
<script>
/* theme before first paint */
try {
    var stored = localStorage.getItem('lufly-theme');
    var dark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute('data-theme', stored || (dark ? 'dark' : 'light'));
} catch (error) { /* storage unavailable */ }
</script>
<?= $view->renderFile($view->resolvePath('components.seo'), ['seo' => $seo ?? null]) ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<?php foreach (array_keys($externalOrigins) as $origin): ?>
<link rel="preconnect" href="<?= e($origin) ?>" crossorigin>
<?php endforeach; ?>
<?php foreach ($preloads as $preload): ?>
<link rel="preload" href="<?= e($preload['href']) ?>"<?php foreach ($preload['attributes'] as $name => $value): ?> <?= e($name) ?>="<?= e($value) ?>"<?php endforeach; ?>>
<?php endforeach; ?>
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/css/app.css')) ?>">
<?php foreach ($styles as $style): ?>
<?php if (preg_match('#^https?://#i', $style) === 1): ?>
<?php /* Third-party CSS never blocks the first paint; the noscript link keeps it available without JS. */ ?>
<link rel="stylesheet" href="<?= e($style) ?>" media="print" onload="this.media='all'" crossorigin="anonymous" referrerpolicy="no-referrer">
<noscript><link rel="stylesheet" href="<?= e($style) ?>" crossorigin="anonymous" referrerpolicy="no-referrer"></noscript>
<?php else: ?>
<link rel="stylesheet" href="<?= e(asset($style)) ?>">
<?php endif; ?>
<?php endforeach; ?>
</head>
<body class="ds-app aquatic-stage<?= $navbar !== '' ? ' has-nav-rail' : '' ?>">
<a class="skip-link" href="#main"><?= e(trans('common.skip_to_content')) ?></a>
<?= $navbar ?>
<main class="ds-main" id="main">
<?= $view->renderFile($view->resolvePath('components.alert'), []) ?>
<?= $content ?>
</main>
<?php if ($translator !== null): ?>
<?= $view->renderFile($view->resolvePath('components.footer'), []) ?>
<?php endif; ?>
<script src="<?= e(asset('frontend/js/app.js')) ?>"></script>
<?php foreach ($scripts as $script): ?>
<script src="<?= e(asset($script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
