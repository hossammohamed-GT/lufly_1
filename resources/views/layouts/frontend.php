<?php
/** @var Core\View\View $view */
/** @var string $content */
/** @var string $title */
$translator = $translator ?? null;
$locale = $translator instanceof \Core\Localization\Translator ? $translator->getLocale() : (string) config('localization.default', 'en');
$basePath = rtrim((string) parse_url(url('/'), PHP_URL_PATH), '/');
$direction = in_array($locale, ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr';
$styles = $view->styles();
$scripts = $view->scripts();
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>" dir="<?= e($direction) ?>" data-theme="light" data-base="<?= e($basePath) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/css/app.css')) ?>">
<?php foreach ($styles as $style): ?>
<link rel="stylesheet" href="<?= e(asset($style)) ?>">
<?php endforeach; ?>
</head>
<body class="ds-app aquatic-stage">
<a class="skip-link" href="#main"><?= e(trans('common.skip_to_content')) ?></a>
<?php if ($translator !== null): ?>
<?= $view->renderFile($view->resolvePath('components.navbar'), []) ?>
<?php endif; ?>
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
