<?php
$view->layout('layouts.frontend');
$component = static fn (string $name, array $data = []): string => $view->renderFile(
    $view->resolvePath('home.' . $name . '.' . $name),
    $data,
);
?>
<?= $component('hero-cinema') ?>
<?= $component('trust-bar') ?>
<?= $component('finishes') ?>
<?php
?>
<?= $component('categories', ['categories' => $categories ?? []]) ?>
<?= $component('inspiration') ?>
<?= $component('rituals') ?>
<?= $component('masterpieces', ['featuredProducts' => $featuredProducts]) ?>
<?= $component('corporate') ?>
