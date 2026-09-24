<?php
if (!feature('favorites', true) || !class_exists(\Modules\Favorites\Services\FavoriteService::class)) {
    return;
}

$view->pushStyle('frontend/favorites/favorites.css');
$view->pushScript('frontend/favorites/favorites.js');
?>
<aside class="favmail" data-fav-prompt hidden
       data-fav-mail-endpoint="<?= e(route('favorites.email')) ?>"
       data-fav-expired="<?= e(trans('favorites.expired')) ?>"
       role="dialog" aria-modal="false" aria-labelledby="favmail-title">
    <span class="favmail-beam" aria-hidden="true"></span>

    <button type="button" class="favmail-close" data-fav-prompt-close
            aria-label="<?= e(trans('favorites.prompt_close')) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" aria-hidden="true">
            <line x1="6" y1="6" x2="18" y2="18"/>
            <line x1="18" y1="6" x2="6" y2="18"/>
        </svg>
    </button>

    <span class="favmail-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20.6 4.2 12.8a5.1 5.1 0 0 1 0-7.2 5.1 5.1 0 0 1 7.2 0l.6.6.6-.6a5.1 5.1 0 0 1 7.2 0 5.1 5.1 0 0 1 0 7.2Z"/>
        </svg>
    </span>

    <span class="favmail-eyebrow"><?= e(trans('favorites.eyebrow')) ?></span>
    <h2 class="favmail-title" id="favmail-title"><?= e(trans('favorites.prompt_title')) ?></h2>
    <p class="favmail-text"><?= e(trans('favorites.prompt_text')) ?></p>

    <form class="favmail-form" method="post" action="<?= e(route('favorites.email')) ?>" data-fav-prompt-form>
        <?= csrf_field() ?>
        <label class="favmail-field">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="5" width="18" height="14" rx="2.5"/>
                <path d="m4 7 8 6 8-6"/>
            </svg>
            <input type="email" name="email" required data-fav-prompt-input
                   placeholder="<?= e(trans('favorites.mail_placeholder')) ?>"
                   aria-label="<?= e(trans('favorites.prompt_title')) ?>"
                   autocomplete="email" inputmode="email">
        </label>

        <label class="favmail-notify">
            <input type="checkbox" name="notify" value="1" checked>
            <span><?= e(trans('favorites.notify_label')) ?></span>
        </label>

        <button type="submit" class="favmail-btn" data-fav-prompt-submit>
            <span data-fav-prompt-label><?= e(trans('favorites.prompt_button')) ?></span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 12h15"/>
                <path d="m13 6 6 6-6 6"/>
            </svg>
        </button>

        <p class="favmail-status" data-fav-prompt-status role="status" aria-live="polite"
           data-sending="<?= e(trans('favorites.prompt_sending')) ?>"></p>
    </form>

    <p class="favmail-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="4" y="10.5" width="16" height="10" rx="2.5"/>
            <path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>
        </svg>
        <?= e(trans('favorites.prompt_note')) ?>
    </p>
</aside>
