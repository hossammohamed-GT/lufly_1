<?php
/** @var Core\View\View $view */
/** @var string $content */
/** @var string $title */
$translator = $translator ?? null;
$locale = $translator instanceof \Core\Localization\Translator ? $translator->getLocale() : (string) config('localization.default', 'en');
$dir = 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>" dir="ltr" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?= $view->renderFile($view->resolvePath('components.seo'), ['seo' => $seo ?? null]) ?>
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/colors.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/spacing.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/typography.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/radius.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/shadows.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/animations.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/themes.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/components.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/tidal-monolith.css')) ?>">
</head>
<body class="ds-app aquatic-stage">
<?php if ($translator !== null): ?>
<?= $view->renderFile($view->resolvePath('components.navbar'), []) ?>
<?php endif; ?>
<main class="ds-main">
<?= $view->renderFile($view->resolvePath('components.alert'), []) ?>
<?= $content ?>
</main>
<?php if ($translator !== null): ?>
<?= $view->renderFile($view->resolvePath('components.footer'), []) ?>
<?php endif; ?>
<script src="<?= e(asset('frontend/js/theme-switcher.js')) ?>"></script>
<script src="<?= e(asset('frontend/js/modal.js')) ?>"></script>
<script src="<?= e(asset('frontend/js/tidal-monolith.js')) ?>"></script>
</body>
</html>
