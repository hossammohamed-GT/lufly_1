<?php
/**
 * Bathroom planner — the chat.
 *
 * The visitor answers three questions on the left; the plan, the drawing and
 * the matching products appear on the right as soon as the last answer lands.
 * Everything the page needs to talk to the server travels in data attributes,
 * so the flow never depends on a hard-coded route or locale.
 *
 * @var Core\View\View $view
 * @var string $locale
 * @var array<string, mixed> $answers
 * @var array<int, string> $waiting
 * @var array<string, string> $endpoints
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/planner/planner.css');
$view->pushScript('frontend/planner/planner.js');

/* the plan offers the matching products with the usual heart, so the saved-list
   script comes along whenever the feature is on */
if (feature('favorites', true)) {
    $view->pushStyle('frontend/favorites/favorites.css');
    $view->pushScript('frontend/favorites/favorites.js');
}

$endpoints = $endpoints ?? [];
$waiting = $waiting ?? [];

/* the floating chat stays out of this page: the chat *is* the page here */
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
            <!-- the conversation -->
            <section class="planner-chat" aria-label="<?= e(trans('planner.title')) ?>">
                <?= $view->renderFile($view->resolvePath('planner::partials.chat'), [
                    'answers' => $answers,
                    'locale' => $locale,
                    'step' => 'size',
                    'progress' => 1,
                ]) ?>
            </section>

            <!-- the plan -->
            <?= $view->renderFile($view->resolvePath('planner::partials.board'), ['variant' => 'page']) ?>

        </div>

        <p class="planner-foot"><?= e(trans('planner.foot')) ?></p>
    </div>

    <div class="planner-toast" data-planner-toast role="status" aria-live="polite"></div>
</main>
