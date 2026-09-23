<?php
/** @var Core\View\View $view */
/** @var string $content */
/** @var string $title */
$translator = $translator ?? null;
$locale = $translator instanceof \Core\Localization\Translator ? $translator->getLocale() : (string) config('localization.default', 'en');
$basePath = rtrim((string) parse_url(url('/'), PHP_URL_PATH), '/');
$direction = in_array($locale, ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr';

/* Push loader assets before collection */
$view->pushStyle('frontend/components/loader/loader.css');
$view->pushScript('frontend/components/loader/loader.js');

/* Chrome first: the navbar declares its own stylesheet and controller, so it has
   to render before the asset lists are collected for <head>. */
$navbar = $translator !== null
    ? $view->renderFile($view->resolvePath('components.navbar'), [])
    : '';

/* The announcement bar also declares its own stylesheet, so it must render
   before the asset lists are collected - it is echoed in the body below. */
$announcement = $translator !== null
    ? $view->renderFile($view->resolvePath('components.announcement'), ['translator' => $translator])
    : '';

/* The bathroom planner travels as a floating chat on every storefront page
   (it bows out on /planner, where the chat *is* the page). Rendered before the
   assets are collected because it brings its own stylesheet and script. */
$plannerChat = $translator !== null
    && feature('planner', true)
    && (bool) config('planner.enabled', true)
    && $view->exists('planner::partials.widget')
    ? $view->renderFile($view->resolvePath('planner::partials.widget'), [])
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
<?php /* storefront scripts post to the app with this token (favourites, quick actions) */ ?>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="icon" type="image/png" href="<?= e(asset('images/logo.png')) ?>">
<script>
/* theme before first paint */
try {
    var stored = localStorage.getItem('lufly-theme');
    var dark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute('data-theme', stored || (dark ? 'dark' : 'light'));
} catch (error) { /* storage unavailable */ }
</script>
<?= $view->renderFile($view->resolvePath('components.seo'), ['seo' => $seo ?? null, 'title' => $title ?? null, 'status' => $status ?? null]) ?>
<?php
/* Analytics & measurement: every provider renders only when its ID is
   configured, so a bare install ships zero third-party requests. */
$analytics = (array) config('seo.analytics', []);
$ga4 = trim((string) ($analytics['ga4'] ?? ''));
$gtm = trim((string) ($analytics['gtm'] ?? ''));
$clarity = trim((string) ($analytics['clarity'] ?? ''));
?>
<?php if ($gtm !== ''): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($gtm) ?>');</script>
<?php endif; ?>
<?php if ($ga4 !== ''): ?>
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga4) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($ga4) ?>',{anonymize_ip:true});</script>
<?php endif; ?>
<?php if ($clarity !== ''): ?>
<!-- Microsoft Clarity -->
<script>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,"clarity","script","<?= e($clarity) ?>");</script>
<?php endif; ?>
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
<body class="ds-app aquatic-stage ld-loading">
<?php if ($gtm !== ''): ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($gtm) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>
<?= $view->renderFile($view->resolvePath('components.loader'), ['translator' => $translator]) ?>
<?= $announcement ?>
<a class="skip-link" href="#main"><?= e(trans('common.skip_to_content')) ?></a>
<?= $navbar ?>
<main class="ds-main" id="main">
<?= $view->renderFile($view->resolvePath('components.alert'), []) ?>
<?= $content ?>
</main>
<?php if ($translator !== null): ?>
<?= $view->renderFile($view->resolvePath('components.footer'), []) ?>
<?php endif; ?>
<?= $view->renderFile($view->resolvePath('components.favorite-mail-prompt'), []) ?>
<?= $plannerChat ?>
<script defer src="<?= e(asset('frontend/js/app.js')) ?>"></script>
<?php foreach ($scripts as $script): ?>
<script defer src="<?= e(asset($script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
