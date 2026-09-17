<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
$component = static fn (string $name): string => $view->renderFile($view->resolvePath('home.' . $name . '.' . $name));
?>
<?= $component('hero-slider') ?>
<?= $component('trust-bar') ?>
<?= $component('finishes') ?>
<?= $component('categories') ?>
<?= $component('inspiration') ?>
<?= $component('rituals') ?>
<?= $component('masterpieces') ?>
<?= $component('corporate') ?>
