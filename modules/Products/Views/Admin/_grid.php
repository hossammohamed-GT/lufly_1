<?php
/** @var Core\View\View $view */
/** @var \Modules\Products\Models\Product[] $products */
?>
<?php if ($products === []): ?>
    <p class="media-empty"><?= e(trans('common.no_results')) ?></p>
<?php endif; ?>
<?php foreach ($products as $product): ?>
    <?= $view->render('products::Admin._card', [
        'product' => $product,
        'payloads' => $payloads,
        'cards' => $cards,
        'mediaState' => $mediaState,
        'chips' => $chips,
        'finishSpecs' => $finishSpecs,
        'covers' => $covers,
        'members' => $members,
        'reasons' => $reasons,
        'categoryPaths' => $categoryPaths,
        'mode' => $mode,
        'locale' => $locale,
    ]) ?>
<?php endforeach; ?>
