<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/home/text-reveal.css');
$view->pushScript('frontend/home/text-reveal.js');
$component = static fn (string $name, array $data = []): string => $view->renderFile(
    $view->resolvePath('home.' . $name . '.' . $name),
    $data,
);
?>
<div class="home-animation-scope">
<?= $component('hero') ?>
<?= $component('trust-bar') ?>
<?= $component('finishes') ?>
<?php
/* The mosaic is data driven: without this hand-off the component sees no
   categories and returns early, which silently dropped the whole
   "Architectural Suites" band from the home page. */
?>
<?= $component('categories', ['categories' => $categories ?? []]) ?>
<?= $component('inspiration') ?>
<?= $component('rituals') ?>
<?= $component('central-vacuum') ?>
<?= $component('masterpieces', ['featuredProducts' => $featuredProducts]) ?>
<?= $component('corporate') ?>
</div>
