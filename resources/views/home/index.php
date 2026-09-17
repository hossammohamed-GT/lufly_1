<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
$component = static fn (string $name, array $data = []): string => $view->renderFile(
    $view->resolvePath('home.' . $name . '.' . $name),
    $data,
);
?>
<?= $component('hero-cinema') ?>
<?= $component('trust-bar') ?>
<?= $component('finishes') ?>
<?= $component('categories') ?>
<?= $component('inspiration') ?>
<?= $component('rituals') ?>
<?= $component('masterpieces', ['featuredProducts' => $featuredProducts]) ?>
<?= $component('corporate') ?>
