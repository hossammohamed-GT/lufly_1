<?php
/** @var Core\View\View $view */
?>
<header class="navbar">
    <div class="navbar-inner container">
        <a class="navbar-brand" href="<?= e(route('home')) ?>">
            <img src="<?= e(asset('frontend/design-system/logo.png')) ?>" alt="LUFLY" class="navbar-logo" style="height: 34px; width: auto;">
            <span style="font-weight: 700; letter-spacing: 0.04em;">LUFLY</span>
        </a>
        <nav class="navbar-links">
            <a href="<?= e(route('home')) ?>">Home</a>
            <a href="<?= e(route('products.index')) ?>">Collections & Products</a>
            <a href="#standards">Standards</a>
            <a href="#factory">Factory & Entity</a>
            <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I would like to request B2B export pricing & technical specifications.') ?>" target="_blank" rel="noopener" style="color: #BFA16F; font-weight: 600;">
                💬 +90 850 3040 817
            </a>
            <?php if (auth()->check()): ?>
                <a href="<?= e(route('admin.dashboard')) ?>"><?= e(trans('common.admin_panel')) ?></a>
            <?php endif; ?>
        </nav>
        <div class="navbar-actions">
            <?= $view->renderFile($view->resolvePath('components.language-switcher'), []) ?>
            <?= $view->renderFile($view->resolvePath('components.theme-switcher'), []) ?>
        </div>
    </div>
</header>
