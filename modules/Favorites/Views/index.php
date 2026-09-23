<?php
/**
 * Saved-products page ("favorites").
 *
 * Editorial layout in the storefront identity: coded header with the count,
 * a card grid of everything the visitor saved (each card links straight to the
 * product page) and a sticky side column that mails the list, hands out the
 * permanent link and can empty it again.
 *
 * @var Core\View\View $view
 * @var string $locale
 * @var array<int, array<string, mixed>> $items
 * @var int $count
 * @var string $shareUrl
 * @var int $maxItems
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/favorites/favorites.css');
$view->pushScript('frontend/favorites/favorites.js');

$items = $items ?? [];
$count = (int) ($count ?? count($items));
/* the visitor was never asked yet -> the box is ticked */
$notify = $notifyChoice === null ? true : (bool) $notifyChoice;
$shareUrl = (string) ($shareUrl ?? '');
$fallbackImg = asset('/images/products/prod_146_1620-111-a.jpg');

$mailEndpoint = route('favorites.email');
$clearEndpoint = route('favorites.clear');
$toggleEndpoint = route('favorites.toggle');
?>
<main class="favs" id="main"
      data-favorites
      data-fav-endpoint="<?= e($toggleEndpoint) ?>"
      data-fav-mail-endpoint="<?= e($mailEndpoint) ?>"
      data-fav-clear-endpoint="<?= e($clearEndpoint) ?>"
      data-fav-empty="<?= e(trans('favorites.empty_title')) ?>">
    <div class="favs-inner">
        <header class="favs-hero">
            <span class="favs-beam" aria-hidden="true"></span>
            <div class="favs-hero-main">
                <span class="favs-eyebrow"><?= e(trans('favorites.eyebrow')) ?></span>
                <h1 class="favs-title"><?= e(trans('favorites.title')) ?></h1>
                <p class="favs-lede"><?= e(trans('favorites.lede')) ?></p>
            </div>
            <dl class="favs-stats">
                <div class="favs-stat">
                    <dt class="favs-stat-label"><?= e(trans('favorites.stat_items')) ?></dt>
                    <dd class="favs-stat-value" data-fav-count><?= (int) $count ?></dd>
                </div>
                <div class="favs-stat">
                    <dt class="favs-stat-label"><?= e(trans('favorites.stat_language')) ?></dt>
                    <dd class="favs-stat-value"><?= e(strtoupper($locale)) ?></dd>
                </div>
                <div class="favs-stat">
                    <dt class="favs-stat-label"><?= e(trans('favorites.stat_access')) ?></dt>
                    <dd class="favs-stat-value is-word"><?= e(trans('favorites.stat_access_value')) ?></dd>
                </div>
            </dl>
        </header>

        <?php if ($items === []): ?>
            <section class="favs-empty" data-fav-empty-state>
                <span class="favs-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20.6 4.2 12.8a5.1 5.1 0 0 1 0-7.2 5.1 5.1 0 0 1 7.2 0l.6.6.6-.6a5.1 5.1 0 0 1 7.2 0 5.1 5.1 0 0 1 0 7.2Z"/>
                    </svg>
                </span>
                <h2 class="favs-empty-title"><?= e(trans('favorites.empty_title')) ?></h2>
                <p class="favs-empty-text"><?= e(trans('favorites.empty_text')) ?></p>
                <a class="favs-empty-cta" href="<?= e(route('products.index')) ?>">
                    <span><?= e(trans('favorites.empty_cta')) ?></span>
                    <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </a>

                <ol class="favs-steps">
                    <li class="favs-step">
                        <span class="favs-step-n">01</span>
                        <strong><?= e(trans('favorites.step_1_title')) ?></strong>
                        <p><?= e(trans('favorites.step_1_text')) ?></p>
                    </li>
                    <li class="favs-step">
                        <span class="favs-step-n">02</span>
                        <strong><?= e(trans('favorites.step_2_title')) ?></strong>
                        <p><?= e(trans('favorites.step_2_text')) ?></p>
                    </li>
                    <li class="favs-step">
                        <span class="favs-step-n">03</span>
                        <strong><?= e(trans('favorites.step_3_title')) ?></strong>
                        <p><?= e(trans('favorites.step_3_text')) ?></p>
                    </li>
                </ol>
            </section>
        <?php else: ?>
            <div class="favs-layout">
                <section class="favs-list" aria-label="<?= e(trans('favorites.list_title')) ?>">
                    <div class="favs-list-head">
                        <h2 class="favs-block-title"><?= e(trans('favorites.list_title')) ?></h2>
                        <span class="favs-list-count">
                            <?= e(\Modules\Favorites\Support\Text::count('favorites.list_count', $count)) ?>
                        </span>
                    </div>

                    <div class="favs-grid" data-fav-grid>
                        <?php foreach ($items as $index => $item): ?>
                            <?php
                            $productId = (int) ($item['id'] ?? 0);
                            $productUrl = (string) ($item['fav_url'] ?? '#');
                            $image = (string) ($item['image'] ?? '');
                            $code = (string) ($item['model_code'] ?? $item['sku'] ?? '');
                            $desc = trim((string) ($item['short_description'] ?? ''));
                            ?>
                            <article class="fav-card" data-fav-card="<?= $productId ?>"
                                     style="--fav-delay: <?= e((string) (0.05 * (int) (($index % 6) + 1))) ?>s">
                                <a class="fav-card-media" href="<?= e($productUrl) ?>" tabindex="-1" aria-hidden="true">
                                    <img src="<?= e($image !== '' ? asset($image) : $fallbackImg) ?>"
                                         alt="" loading="<?= $index < 3 ? 'eager' : 'lazy' ?>" decoding="async"
                                         width="420" height="300"
                                         onerror="this.onerror=null; this.src='<?= e($fallbackImg) ?>';">
                                    <span class="fav-card-index"><?= str_pad((string) ((int) $index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                </a>
                                <div class="fav-card-body">
                                    <span class="fav-card-code"><?= e(trans('common.sku')) ?> <?= e($code) ?></span>
                                    <h3 class="fav-card-title">
                                        <a href="<?= e($productUrl) ?>"><?= e($item['name'] ?? '') ?></a>
                                    </h3>
                                    <?php if ($desc !== ''): ?>
                                        <p class="fav-card-desc"><?= e($desc) ?></p>
                                    <?php endif; ?>
                                    <div class="fav-card-actions">
                                        <a class="fav-card-open" href="<?= e($productUrl) ?>">
                                            <span><?= e(trans('favorites.open_product')) ?></span>
                                            <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                        </a>
                                        <button type="button" class="fav-card-remove"
                                                data-fav-toggle
                                                data-fav-product="<?= $productId ?>"
                                                data-fav-endpoint="<?= e($toggleEndpoint) ?>"
                                                data-fav-added="<?= e(trans('favorites.added_named', ['name' => (string) ($item['name'] ?? '')])) ?>"
                                                data-fav-removed="<?= e(trans('favorites.removed_named', ['name' => (string) ($item['name'] ?? '')])) ?>"
                                                data-fav-label-on="<?= e(trans('favorites.saved')) ?>"
                                                data-fav-label-off="<?= e(trans('favorites.save')) ?>"
                                                data-fav-removing
                                                aria-label="<?= e(trans('favorites.remove')) ?> — <?= e($item['name'] ?? '') ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" aria-hidden="true">
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                            </svg>
                                            <span><?= e(trans('favorites.remove')) ?></span>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <aside class="favs-side">
                    <section class="favs-panel favs-panel-mail">
                        <span class="favs-panel-eyebrow"><?= e(trans('favorites.mail_eyebrow')) ?></span>
                        <h2 class="favs-panel-title"><?= e(trans('favorites.mail_title')) ?></h2>
                        <p class="favs-panel-text"><?= e(\Modules\Favorites\Support\Text::count('favorites.mail_text', $count)) ?></p>

                        <form class="favs-mail" method="post" action="<?= e($mailEndpoint) ?>" data-fav-mail-form>
                            <?= csrf_field() ?>
                            <label class="favs-mail-field">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2.5"/>
                                    <path d="m4 7 8 6 8-6"/>
                                </svg>
                                <input type="email" name="email" required
                                       value="<?= e($favorite !== null ? (string) ($favorite->email ?? '') : '') ?>"
                                       placeholder="<?= e(trans('favorites.mail_placeholder')) ?>"
                                       aria-label="<?= e(trans('favorites.mail_placeholder')) ?>"
                                       autocomplete="email" inputmode="email">
                            </label>
                            <label class="favs-notify">
                                <input type="checkbox" name="notify" value="1" <?= $notify ? 'checked' : '' ?>>
                                <span><?= e(trans('favorites.notify_label')) ?></span>
                            </label>

                            <button type="submit" class="favs-mail-btn" data-fav-mail-submit>
                                <span data-fav-mail-label><?= e(trans('favorites.mail_button')) ?></span>
                                <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <p class="favs-mail-status" data-fav-mail-status role="status" aria-live="polite"
                               data-sending="<?= e(trans('favorites.mail_sending')) ?>"></p>
                        </form>

                        <p class="favs-notify-hint"><?= e(trans('favorites.notify_hint')) ?></p>

                        <ul class="favs-points">
                            <li><?= e(trans('favorites.mail_point_1')) ?></li>
                            <li><?= e(trans('favorites.mail_point_2')) ?></li>
                            <li><?= e(trans('favorites.mail_point_3')) ?></li>
                        </ul>
                    </section>

                    <?php if ($shareUrl !== ''): ?>
                        <section class="favs-panel favs-panel-link">
                            <h2 class="favs-panel-title is-small"><?= e(trans('favorites.link_title')) ?></h2>
                            <p class="favs-panel-text"><?= e(trans('favorites.link_text')) ?></p>
                            <div class="favs-linkrow">
                                <input class="favs-linkinput" type="text" readonly value="<?= e($shareUrl) ?>"
                                       aria-label="<?= e(trans('favorites.link_title')) ?>" data-fav-link>
                                <button type="button" class="favs-copy" data-fav-copy
                                        data-copied="<?= e(trans('favorites.copied')) ?>"
                                        data-copy-error="<?= e(trans('favorites.copy_failed')) ?>"
                                        data-label="<?= e(trans('favorites.copy')) ?>">
                                    <?= e(trans('favorites.copy')) ?>
                                </button>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="favs-panel favs-panel-foot">
                        <form method="post" action="<?= e($clearEndpoint) ?>" data-fav-clear-form
                              data-confirm="<?= e(trans('favorites.clear_confirm')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="favs-clear">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 7h16"/>
                                    <path d="M9 7V5h6v2"/>
                                    <path d="M6 7l1 13h10l1-13"/>
                                </svg>
                                <span><?= e(trans('favorites.clear_button')) ?></span>
                            </button>
                        </form>
                        <div class="favs-help">
                            <strong><?= e(trans('favorites.help_title')) ?></strong>
                            <p><?= e(trans('favorites.help_text')) ?></p>
                            <a href="<?= e(route('contact')) ?>"><?= e(trans('favorites.help_cta')) ?></a>
                        </div>
                    </section>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>
