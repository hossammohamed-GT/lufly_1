<?php
/** @var Core\View\View $view */
/** @var string $content */
/** @var string $title */
$translator = $translator ?? null;
$locale = $translator instanceof \Core\Localization\Translator ? $translator->getLocale() : (string) config('localization.default', 'en');
$basePath = rtrim((string) parse_url(url('/'), PHP_URL_PATH), '/');
$direction = in_array($locale, ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr';
$currentPath = trim((string) request()->path(), '/');
$navItems = [
    ['href' => route('admin.dashboard'), 'label' => trans('common.dashboard'), 'active' => $currentPath === 'admin'],
    ['href' => route('admin.products.index'), 'label' => trans('common.products'), 'active' => str_starts_with($currentPath, 'admin/products')],
    ['href' => route('admin.categories.index'), 'label' => trans('common.categories'), 'active' => str_starts_with($currentPath, 'admin/categories')],
    ['href' => route('admin.collections.index'), 'label' => trans('common.collections'), 'active' => str_starts_with($currentPath, 'admin/collections')],
    ['href' => route('admin.brands.index'), 'label' => trans('common.brands'), 'active' => str_starts_with($currentPath, 'admin/brands')],
];
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>" dir="<?= e($direction) ?>" data-theme="light" data-base="<?= e($basePath) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>Lufly</title>
<link rel="icon" type="image/png" href="<?= e(asset('images/logo.png')) ?>">
<script>
/* theme before first paint */
try {
    var stored = localStorage.getItem('lufly-theme');
    var dark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute('data-theme', stored || (dark ? 'dark' : 'light'));
} catch (error) { /* storage unavailable */ }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/admin/admin.css')) ?>">
</head>
<body class="ds-app admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand"><?= e($appName) ?></div>
        <nav class="admin-nav">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= e($item['href']) ?>"<?= $item['active'] ? ' class="is-active" aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <h1 class="admin-title"><?= e($title ?? '') ?></h1>
            <div class="admin-topbar-actions">
                <?= $view->renderFile($view->resolvePath('components.theme-switcher'), []) ?>
                <a class="btn btn-ghost" href="<?= e(route('home')) ?>"><?= e(trans('common.view_site')) ?></a>
                <a class="btn btn-ghost" href="<?= e(route('admin.password.edit')) ?>"><?= e(trans('common.change_password')) ?></a>
                <form method="post" action="<?= e(url('/logout')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost"><?= e(trans('auth.logout')) ?></button>
                </form>
            </div>
        </header>
        <div class="admin-content">
            <?= $view->renderFile($view->resolvePath('components.alert'), []) ?>
            <?= $content ?>
        </div>
    </div>
</div>
<script src="<?= e(asset('frontend/js/app.js')) ?>"></script>
<script src="<?= e(asset('frontend/admin/admin.js')) ?>"></script>
</body>
</html>
