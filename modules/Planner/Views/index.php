<?php
$view->layout('layouts.frontend');
$view->pushStyle('frontend/planner/planner.css');
$view->pushScript('frontend/planner/planner.js');

if (feature('favorites', true)) {
    $view->pushStyle('frontend/favorites/favorites.css');
    $view->pushScript('frontend/favorites/favorites.js');
}

$endpoints = $endpoints ?? [];
$waiting = $waiting ?? [];

$view->share(['plannerInline' => true]);
?>
<main class="planner" id="main"
      data-planner
      data-planner-step-endpoint="<?= e($endpoints['step'] ?? '') ?>"
      data-planner-fit-endpoint="<?= e($endpoints['fit'] ?? '') ?>"
      data-planner-render-endpoint="<?= e($endpoints['render'] ?? '') ?>"
      data-planner-send-endpoint="<?= e($endpoints['send'] ?? '') ?>"
      data-planner-waiting="<?= e((string) json_encode($waiting, JSON_UNESCAPED_UNICODE)) ?>"
      data-planner-render-wait="<?= e(trans('planner.render_wait')) ?>"
      data-planner-render-again="<?= e(trans('planner.render_again')) ?>"
      data-planner-send-wait="<?= e(trans('planner.handoff_sending')) ?>"
      data-planner-custom-error="<?= e(trans('planner.err_custom')) ?>"
      data-planner-fit-wait="<?= e(trans('planner.fit_wait')) ?>"
      data-planner-answered="">
    <div class="planner-inner">
        <header class="planner-hero">
            <span class="planner-beam" aria-hidden="true"></span>
            <div class="planner-hero-main">
                <span class="planner-eyebrow"><?= e(trans('planner.eyebrow')) ?></span>
                <h1 class="planner-title"><?= e(trans('planner.title')) ?></h1>
                <p class="planner-lede"><?= e(trans('planner.lede')) ?></p>
            </div>
            <ol class="planner-trail" aria-hidden="true">
                <li class="planner-trail-step is-on"><span>01</span><?= e(trans('planner.q_size')) ?></li>
                <li class="planner-trail-step"><span>02</span><?= e(trans('planner.q_wet')) ?></li>
                <li class="planner-trail-step"><span>03</span><?= e(trans('planner.q_look')) ?></li>
            </ol>
        </header>

        <div class="planner-grid">
<section class="planner-chat" aria-label="<?= e(trans('planner.title')) ?>">
                <?= $view->renderFile($view->resolvePath('planner::partials.chat'), [
                    'answers' => $answers,
                    'locale' => $locale,
                    'step' => 'size',
                    'progress' => 1,
                ]) ?>
            </section>
<?= $view->renderFile($view->resolvePath('planner::partials.board'), ['variant' => 'page']) ?>

        </div>

        <p class="planner-foot"><?= e(trans('planner.foot')) ?></p>
    </div>

    <div class="planner-toast" data-planner-toast role="status" aria-live="polite"></div>
</main>
