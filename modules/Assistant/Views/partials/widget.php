<?php
/**
 * The finder as a floating chat — the bubble in the corner of every page.
 *
 * Two tabs, because the visitor arrives with one of two questions:
 *
 *   1. "find me a piece like this"  — the tab the chat opens on. A description
 *      in any language, or a photo, comes back as cards from our catalogue.
 *   2. "plan my bathroom"           — promised, not delivered yet: the tab says
 *      so instead of pretending (the planner itself is one flag away).
 *
 * Rendered from the layout *before* the asset lists are collected, which is why
 * it declares its own stylesheet and script here. The shell (bubble, panel,
 * grow, resize) is styled by the shared chat sheet; the finder adds its own.
 *
 * @var Core\View\View $view
 * @var Core\Localization\Translator|null $translator
 * @var array{id: int, name: string, text: string, category: string, url: string}|null $plannerContext
 * @var bool $plannerInline   true where the chat is the page itself
 */
if (($plannerInline ?? false) === true) {
    return;
}

$assistant = app(\Modules\Assistant\Services\AssistantService::class);

if (!$assistant->enabled()) {
    return;
}

$locale = (string) ($translator instanceof \Core\Localization\Translator ? $translator->getLocale() : 'en');
$photos = $assistant->photosEnabled();
$context = is_array($plannerContext ?? null) && ($plannerContext['name'] ?? '') !== '' ? $plannerContext : null;

/* The planner is the chat's second tab, but only when the chat is allowed to
   talk about it at all (`PLANNER_CHAT`). While it is still on the way the tab
   promises instead of pretending — and the promise is the whole pane. */
$plannerTab = feature('planner', true)
    && (bool) config('planner.enabled', true)
    && (bool) config('planner.chat.enabled', true);
$plannerSoon = $plannerTab && (bool) config('planner.coming_soon', true);

$waiting = $assistant->waiting($locale);

$view->pushStyle('frontend/planner/planner.css');
$view->pushStyle('frontend/assistant/chat.css');
$view->pushScript('frontend/assistant/assistant.js');

if (feature('favorites', true)) {
    $view->pushStyle('frontend/favorites/favorites.css');
    $view->pushScript('frontend/favorites/favorites.js');
}
?>
<div class="aichat" data-aichat data-assistant
     data-assistant-endpoint="<?= e(route('assistant.ask')) ?>"
     data-assistant-waiting="<?= e((string) json_encode($waiting, JSON_UNESCAPED_UNICODE)) ?>"
     data-assistant-context="<?= e((string) json_encode($context, JSON_UNESCAPED_UNICODE)) ?>"
     data-assistant-photos="<?= $photos ? '1' : '0' ?>"
     data-assistant-max-kb="<?= (int) config('assistant.photo.max_kb', 4096) ?>"
     data-assistant-ask-email="<?= ((bool) config('assistant.lead.ask_email', true) && (bool) config('assistant.lead.email', '')) ? '1' : '0' ?>"
     data-assistant-planner-soon="<?= $plannerSoon ? '1' : '0' ?>"
     data-assistant-labels="<?= e((string) json_encode([
         'email_saved' => trans('assistant.ask_saved'),
         'photo_ready' => trans('assistant.photo_ready'),
         'photo_error' => trans('assistant.note_photo_rejected'),
         'err' => trans('assistant.err'),
         'see_all' => trans('assistant.see_all'),
         'support_mail' => trans('assistant.support_mail'),
         'support_whatsapp' => trans('assistant.support_whatsapp'),
     ], JSON_UNESCAPED_UNICODE)) ?>">

    <button type="button" class="aichat-fab" data-aichat-toggle
            aria-expanded="false" aria-controls="lufly-chat-panel">
        <span class="aichat-fab-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"/>
                <path d="m20 20-3.6-3.6"/>
            </svg>
        </span>
        <span class="aichat-fab-text">
            <strong><?= e(trans('assistant.open')) ?></strong>
            <em><?= e(trans('assistant.open_hint')) ?></em>
        </span>
        <span class="aichat-fab-dot" aria-hidden="true"></span>
    </button>

    <section class="aichat-panel" id="lufly-chat-panel" data-aichat-panel hidden
             role="dialog" aria-modal="false" aria-labelledby="lufly-chat-title">
        <span class="aichat-grip" data-aichat-resize aria-hidden="true"
              title="<?= e(trans('assistant.resize')) ?>"></span>

        <header class="aichat-head">
            <span class="aichat-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.6-3.6"/>
                </svg>
            </span>
            <span class="aichat-head-text">
                <strong id="lufly-chat-title"><?= e(trans('assistant.title')) ?></strong>
                <em><?= e(trans('assistant.subtitle')) ?></em>
            </span>
            <button type="button" class="aichat-icon-btn" data-aichat-grow
                    aria-pressed="false" title="<?= e(trans('assistant.grow')) ?>"
                    data-aichat-grow-label="<?= e(trans('assistant.grow')) ?>"
                    data-aichat-shrink-label="<?= e(trans('assistant.shrink')) ?>">
                <span class="visually-hidden" data-aichat-grow-text><?= e(trans('assistant.grow')) ?></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 10V4h6M20 14v6h-6M4 4l6 6M20 20l-6-6"/>
                </svg>
            </button>
            <button type="button" class="aichat-icon-btn" data-aichat-close
                    title="<?= e(trans('assistant.close')) ?>">
                <span class="visually-hidden"><?= e(trans('assistant.close')) ?></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                     stroke-linecap="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </header>

        <?php if ($plannerTab): ?>
        <div class="aichat-tabs" role="tablist" aria-label="<?= e(trans('assistant.title')) ?>">
            <button type="button" class="aichat-tab is-active" data-assistant-tab="find"
                    role="tab" aria-selected="true" aria-controls="lufly-chat-find">
                <?= e(trans('assistant.tab_find')) ?>
            </button>
            <button type="button" class="aichat-tab" data-assistant-tab="planner"
                    role="tab" aria-selected="false" aria-controls="lufly-chat-planner">
                <?= e(trans('assistant.tab_planner')) ?>
                <?php if ($plannerSoon): ?>
                    <span class="aichat-tab-badge"><?= e(trans('assistant.soon_pill')) ?></span>
                <?php endif; ?>
            </button>
        </div>
        <?php endif; ?>

        <div class="aichat-body" data-aichat-body>
            <div class="aichat-pane" id="lufly-chat-find" role="tabpanel" data-assistant-pane="find">
                <div class="aichat-welcome" data-assistant-welcome>
                    <p class="aichat-hello"><?= e(trans('assistant.ask_text')) ?></p>

                    <div class="aichat-chips" data-assistant-chips
                         data-chips-label="<?= e(trans('assistant.chips_label')) ?>">
                        <?php if ($context !== null): ?>
                            <button type="button" class="aichat-chip is-strong" data-assistant-chip
                                    data-assistant-chip-product="<?= (int) ($context['id'] ?? 0) ?>">
                                <?= e(trans('assistant.chip_like', ['name' => (string) $context['name']])) ?>
                            </button>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <button type="button" class="aichat-chip" data-assistant-chip
                                    data-assistant-chip-text="<?= e(trans('assistant.chip_' . $i)) ?>">
                                <?= e(trans('assistant.chip_' . $i)) ?>
                            </button>
                        <?php endfor; ?>
                    </div>

                    <form class="aichat-gate" data-assistant-gate hidden>
                        <label class="aichat-gate-label" for="lufly-chat-email"><?= e(trans('assistant.ask_label')) ?></label>
                        <div class="aichat-gate-row">
                            <input type="email" id="lufly-chat-email" name="email" inputmode="email"
                                   autocomplete="email" placeholder="<?= e(trans('assistant.ask_placeholder')) ?>"
                                   data-assistant-email>
                            <button type="submit" class="aichat-gate-go"><?= e(trans('assistant.ask_start')) ?></button>
                        </div>
                        <button type="button" class="aichat-gate-skip" data-assistant-skip><?= e(trans('assistant.ask_skip')) ?></button>
                    </form>
                </div>

                <div class="aichat-log-list" data-assistant-log role="log" aria-live="polite"></div>
            </div>

            <?php if ($plannerTab): ?>
            <div class="aichat-pane" id="lufly-chat-planner" role="tabpanel" data-assistant-pane="planner" hidden>
                <div class="aichat-soon">
                    <?php if ($plannerSoon): ?>
                        <span class="aichat-soon-pill"><?= e(trans('assistant.soon_pill')) ?></span>
                        <h3 class="aichat-soon-title"><?= e(trans('assistant.soon_title')) ?></h3>
                        <p class="aichat-soon-text"><?= e(trans('assistant.soon_text')) ?></p>
                        <p class="aichat-soon-note"><?= e(trans('assistant.soon_note')) ?></p>
                    <?php else: ?>
                        <h3 class="aichat-soon-title"><?= e(trans('assistant.plan_open_title')) ?></h3>
                        <p class="aichat-soon-text"><?= e(trans('assistant.plan_open_text')) ?></p>
                        <a class="aichat-soon-link" href="<?= e(route('planner.index')) ?>"><?= e(trans('planner.chat_full')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <form class="aichat-compose" data-assistant-form>
            <?php if ($photos): ?>
                <label class="aichat-attach" data-assistant-attach
                       title="<?= e(trans('assistant.attach')) ?>">
                    <span class="visually-hidden"><?= e(trans('assistant.attach')) ?></span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                        <circle cx="9" cy="10" r="1.6"/>
                        <path d="m21 15-4.5-4.5L7 20"/>
                    </svg>
                    <input type="file" accept="image/*" data-assistant-file
                           aria-label="<?= e(trans('assistant.attach')) ?>">
                </label>
            <?php endif; ?>

            <div class="aichat-field">
                <div class="aichat-shot" data-assistant-shot hidden>
                    <img alt="" data-assistant-shot-img>
                    <button type="button" class="aichat-shot-x" data-assistant-shot-remove
                            title="<?= e(trans('assistant.remove_photo')) ?>">
                        <span class="visually-hidden"><?= e(trans('assistant.remove_photo')) ?></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                    <span class="aichat-shot-name" data-assistant-shot-name></span>
                </div>
                <textarea class="aichat-input" data-assistant-input rows="1"
                          placeholder="<?= e(trans('assistant.placeholder')) ?>"
                          aria-label="<?= e(trans('assistant.placeholder')) ?>"
                          maxlength="<?= (int) config('assistant.chat.max_chars', 600) ?>"></textarea>
            </div>

            <button type="submit" class="aichat-send" data-assistant-send>
                <span class="visually-hidden"><?= e(trans('assistant.send')) ?></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 12h15M13 6l6 6-6 6"/>
                </svg>
            </button>
        </form>

        <footer class="aichat-foot">
            <span class="aichat-foot-free"><?= e(trans('assistant.foot')) ?></span>
            <a class="aichat-foot-link" href="<?= e(route('products.index')) ?>"><?= e(trans('common.products', [], $locale)) ?></a>
        </footer>
    </section>
</div>
