<?php
/**
 * The planner as a floating chat — the bubble in the corner of every page.
 *
 * One tap opens the panel; inside it is the same conversation as /planner, with
 * the plan appearing under the questions once the last answer lands. The panel
 * can be dragged bigger or expanded, and the browser remembers the size.
 *
 * Rendered from the layout *before* the asset lists are collected, which is why
 * it declares its own stylesheet and script here.
 *
 * @var Core\View\View $view
 * @var array{id: int, name: string, text: string, category: string, url: string}|null $plannerContext
 * @var bool $plannerInline   true on /planner, where the chat is the page
 */
if (($plannerInline ?? false) === true) {
    return;
}

if (!feature('planner', true) || !(bool) config('planner.enabled', true)) {
    return;
}

$service = app(\Modules\Planner\Services\PlannerService::class);
$locale = (string) ($translator instanceof \Core\Localization\Translator ? $translator->getLocale() : 'en');
$context = is_array($plannerContext ?? null) && ($plannerContext['name'] ?? '') !== '' ? $plannerContext : null;
$waiting = $service->waitingMessages($locale);

$view->pushStyle('frontend/planner/planner.css');
$view->pushScript('frontend/planner/planner.js');

if (feature('favorites', true)) {
    $view->pushStyle('frontend/favorites/favorites.css');
    $view->pushScript('frontend/favorites/favorites.js');
}
?>
<div class="aichat" data-aichat
     data-planner
     data-planner-step-endpoint="<?= e(route('planner.step')) ?>"
     data-planner-fit-endpoint="<?= e(route('planner.fit')) ?>"
     data-planner-render-endpoint="<?= e(route('planner.render')) ?>"
     data-planner-send-endpoint="<?= e(route('planner.send')) ?>"
     data-planner-waiting="<?= e((string) json_encode($waiting, JSON_UNESCAPED_UNICODE)) ?>"
     data-planner-context="<?= e((string) json_encode($context, JSON_UNESCAPED_UNICODE)) ?>"
     data-planner-render-wait="<?= e(trans('planner.render_wait')) ?>"
     data-planner-render-again="<?= e(trans('planner.render_again')) ?>"
     data-planner-send-wait="<?= e(trans('planner.handoff_sending')) ?>"
     data-planner-custom-error="<?= e(trans('planner.err_custom')) ?>"
     data-planner-fit-wait="<?= e(trans('planner.fit_wait')) ?>"
     data-planner-answered="">

    <button type="button" class="aichat-fab" data-aichat-toggle
            aria-expanded="false" aria-controls="lufly-chat-panel">
        <span class="aichat-fab-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 20V9.5L12 4l8 5.5V20"/>
                <path d="M9 20v-5.5h6V20"/>
            </svg>
        </span>
        <span class="aichat-fab-text">
            <strong><?= e(trans('planner.chat_open')) ?></strong>
            <em><?= e(trans('planner.chat_open_hint')) ?></em>
        </span>
        <span class="aichat-fab-dot" aria-hidden="true"></span>
    </button>

    <section class="aichat-panel" id="lufly-chat-panel" data-aichat-panel hidden
             role="dialog" aria-modal="false" aria-labelledby="lufly-chat-title">
        <span class="aichat-grip" data-aichat-resize role="separator" aria-label="<?= e(trans('planner.chat_resize')) ?>"></span>

        <header class="aichat-head">
            <span class="aichat-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 20V9.5L12 4l8 5.5V20"/>
                    <path d="M9 20v-5.5h6V20"/>
                </svg>
            </span>
            <span class="aichat-head-text">
                <strong id="lufly-chat-title"><?= e(trans('planner.title')) ?></strong>
                <em><?= e(trans('planner.chat_subtitle')) ?></em>
            </span>
            <button type="button" class="aichat-icon-btn" data-aichat-grow
                    aria-pressed="false" title="<?= e(trans('planner.chat_grow')) ?>">
                <span class="visually-hidden"><?= e(trans('planner.chat_grow')) ?></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 10V4h6M20 14v6h-6M4 4l6 6M20 20l-6-6"/>
                </svg>
            </button>
            <button type="button" class="aichat-icon-btn" data-aichat-close
                    title="<?= e(trans('planner.chat_close')) ?>">
                <span class="visually-hidden"><?= e(trans('planner.chat_close')) ?></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                     stroke-linecap="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </header>

        <?php if ($context !== null): ?>
            <p class="aichat-about">
                <span class="aichat-about-label"><?= e(trans('planner.chat_about')) ?></span>
                <strong><?= e((string) $context['name']) ?></strong>
            </p>
        <?php endif; ?>

        <div class="aichat-body" data-aichat-body>
            <div class="planner-grid is-chat">
                <section class="planner-chat" aria-label="<?= e(trans('planner.title')) ?>">
                    <?= $view->renderFile($view->resolvePath('planner::partials.chat'), [
                        'answers' => $service->answers([]),
                        'locale' => $locale,
                        'step' => 'size',
                        'progress' => 1,
                    ]) ?>
                </section>

                <?= $view->renderFile($view->resolvePath('planner::partials.board'), ['variant' => 'chat']) ?>
            </div>
        </div>

        <footer class="aichat-foot">
            <span class="aichat-foot-free"><?= e(trans('planner.chat_foot')) ?></span>
            <a class="aichat-foot-link" href="<?= e(route('planner.index')) ?>"><?= e(trans('planner.chat_full')) ?></a>
        </footer>
    </section>

    <div class="planner-toast" data-planner-toast role="status" aria-live="polite"></div>
</div>
