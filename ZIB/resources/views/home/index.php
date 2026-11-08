<?php $view->layout('layouts.frontend'); $view->pushStyle('frontend/home/text-reveal.css'); $view->pushScript('frontend/home/text-reveal.js'); $component = static fn (string $name, array $data = []): string => $view->renderFile( $view->resolvePath('home.' . $name . '.' . $name), $data, ); ?>
<div class="home-animation-scope">
<?= $component('hero') ?>
<?= $component('trust-bar') ?>
<?= $component('finishes') ?>
<?php
 ?>
<?= $component('categories', ['categories' => $categories ?? []]) ?>
<?= $component('inspiration') ?>
<?= $component('rituals') ?>
<?php $component('central-vacuum'); ?>
<?= $component('masterpieces', [ 'featuredProducts' => $featuredProducts, 'savedIds' => $savedIds ?? [], 'boxIds' => $boxIds ?? [], ]) ?>
<?= $component('agents-map') ?>
<?= $component('corporate') ?>
</div>
