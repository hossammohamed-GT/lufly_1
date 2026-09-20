<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/errors/errors.css');
/** @var int $status */
/** @var string $title */
/** @var string $message */

$displayTitle = trim((string) ($title ?? ''));
if ($displayTitle === '' || strcasecmp($displayTitle, 'Not Found') === 0) {
    $displayTitle = trans('errors.page_not_found_title');
}
$displayMessage = trim((string) ($message ?? ''));
if ($displayMessage === '' || strcasecmp($displayMessage, 'Not Found') === 0) {
    $displayMessage = trans('errors.page_not_found_message');
}
?>
<section class="container error-page error-page-404">
    <div class="error-orb error-orb-a" aria-hidden="true"></div>
    <div class="error-orb error-orb-b" aria-hidden="true"></div>

    <p class="error-code" aria-hidden="true"><span>4</span><span>0</span><span>4</span></p>

    <h1 class="error-title"><?= e($displayTitle) ?></h1>
    <p class="error-message"><?= e($displayMessage) ?></p>

    <div class="error-actions">
        <?= $view->component('button', ['label' => trans('errors.back_home'), 'variant' => 'primary', 'href' => route('home')]) ?>
        <?= $view->component('button', ['label' => trans('errors.browse_products'), 'variant' => 'ghost', 'href' => route('products.index')]) ?>
    </div>

    <p class="error-hint"><?= e(trans('errors.page_not_found_hint')) ?></p>
</section>
