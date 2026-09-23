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
?>
<main class="planner" id="main"
      data-planner
      data-planner-step-endpoint="<?= e($endpoints['step'] ?? '') ?>"
      data-planner-render-endpoint="<?= e($endpoints['render'] ?? '') ?>"
      data-planner-send-endpoint="<?= e($endpoints['send'] ?? '') ?>"
      data-planner-waiting="<?= e((string) json_encode($waiting, JSON_UNESCAPED_UNICODE)) ?>"
      data-planner-render-wait="<?= e(trans('planner.render_wait')) ?>"
      data-planner-render-again="<?= e(trans('planner.render_again')) ?>"
      data-planner-send-wait="<?= e(trans('planner.handoff_sending')) ?>"
      data-planner-custom-error="<?= e(trans('planner.err_custom')) ?>"
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
                <div class="planner-log" data-planner-log aria-live="polite">
                    <div class="planner-msg is-bot">
                        <span class="planner-avatar" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 20V9.5L12 4l8 5.5V20"/>
                                <path d="M9 20v-5.5h6V20"/>
                            </svg>
                        </span>
                        <div class="planner-bubble">
                            <p><?= e(trans('planner.greeting')) ?></p>
                        </div>
                    </div>

                    <div class="planner-questions" data-planner-questions>
                        <?php /* renderFile: $view->render() would drop the layout */ ?>
                        <?= $view->renderFile($view->resolvePath('planner::partials.question'), [
                            'step' => 'size',
                            'answers' => $answers,
                            'locale' => $locale,
                            'progress' => 1,
                        ]) ?>
                    </div>
                </div>

                <!-- one sentence at a time, never the same one twice -->
                <div class="planner-thinking" data-planner-thinking hidden>
                    <span class="planner-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span class="planner-thinking-text" data-planner-thinking-text></span>
                </div>
            </section>

            <!-- the plan -->
            <aside class="planner-board" data-planner-board aria-live="polite">
                <div class="planner-board-empty" data-planner-board-empty>
                    <span class="planner-board-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1"
                             stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <path d="M3 10h18M10 4v16"/>
                        </svg>
                    </span>
                    <p><?= e(trans('planner.board_empty')) ?></p>
                </div>
                <div class="planner-board-plan" data-planner-board-plan></div>
            </aside>
        </div>

        <p class="planner-foot"><?= e(trans('planner.foot')) ?></p>
    </div>

    <div class="planner-toast" data-planner-toast role="status" aria-live="polite"></div>
</main>
