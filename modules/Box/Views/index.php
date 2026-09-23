<?php
/**
 * The quotation box, on the site.
 *
 * The same editorial shape as the saved list — coded header, a card grid, a
 * sticky side column — because it is the same kind of page: a list of pieces the
 * visitor collected. What differs is what it is for: every piece is waiting for a
 * price, the list has one link that opens it anywhere, and the last step sends
 * the whole box to the team, once, with the visitor's address on it.
 *
 * @var Core\View\View $view
 * @var string $locale
 * @var array<int, array<string, mixed>> $items
 * @var int $count
 * @var \Modules\Box\Models\Box|null $box
 * @var string $shareUrl
 * @var int $maxItems
 * @var string $shopMail
 */
$view->layout('layouts.frontend');
/* the box reuses the saved-list styling and adds its own button/panel touches */
$view->pushStyle('frontend/favorites/favorites.css');
$view->pushStyle('frontend/box/box.css');
$view->pushScript('frontend/box/box.js');

$items = $items ?? [];
$count = (int) ($count ?? count($items));
$locale = (string) ($locale ?? 'en');
$shareUrl = (string) ($shareUrl ?? '');
$maxItems = (int) ($maxItems ?? 40);
$shopMail = (string) ($shopMail ?? 'info@lufly.tr');
$fallbackImg = asset('/images/products/prod_146_1620-111-a.jpg');
$savedEmail = (string) ($box->email ?? '');
$savedNote = (string) ($box->note ?? '');

$addEndpoint = route('box.add');
$removeEndpoint = route('box.remove');
$sendEndpoint = route('box.send');
$clearEndpoint = route('box.clear');
$countKey = $count === 1 ? 'box.list_count_one' : 'box.list_count_many';
?>
<main class="favs box" id="main"
      data-box-page
      data-box-endpoint="<?= e($addEndpoint) ?>"
      data-box-remove-endpoint="<?= e($removeEndpoint) ?>"
      data-box-token="<?= e((string) ($box->token ?? '')) ?>"
      data-box-max="<?= $maxItems ?>"
      data-box-err="<?= e(trans('box.err')) ?>">
    <div class="favs-inner">
        <header class="favs-hero">
            <span class="favs-beam" aria-hidden="true"></span>
            <div class="favs-hero-main">
                <span class="favs-eyebrow"><?= e(trans('box.eyebrow')) ?></span>
                <h1 class="favs-title"><?= e(trans('box.title')) ?></h1>
                <p class="favs-lede"><?= e(trans('box.lede')) ?></p>
            </div>
            <dl class="favs-stats">
                <div class="favs-stat">
                    <dt class="favs-stat-label"><?= e(trans('box.stat_items')) ?></dt>
                    <dd class="favs-stat-value" data-box-count><?= $count ?></dd>
                </div>
                <div class="favs-stat">
                    <dt class="favs-stat-label"><?= e(trans('box.stat_language')) ?></dt>
                    <dd class="favs-stat-value"><?= e(strtoupper($locale)) ?></dd>
                </div>
                <div class="favs-stat">
                    <dt class="favs-stat-label"><?= e(trans('box.stat_access')) ?></dt>
                    <dd class="favs-stat-value is-word"><?= e(trans('box.stat_access_value')) ?></dd>
                </div>
            </dl>
        </header>

        <?php if ($items === []): ?>
            <section class="favs-empty" data-box-empty>
                <span class="favs-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 8h16l-1.2 11.2a2 2 0 0 1-2 1.8H7.2a2 2 0 0 1-2-1.8Z"/>
                        <path d="M9 8V6a3 3 0 0 1 6 0v2"/>
                    </svg>
                </span>
                <h2 class="favs-empty-title"><?= e(trans('box.empty_title')) ?></h2>
                <p class="favs-empty-text"><?= e(trans('box.empty_text')) ?></p>
                <a class="favs-empty-cta" href="<?= e(route('products.index')) ?>">
                    <span><?= e(trans('box.empty_cta')) ?></span>
                    <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </a>

                <ol class="favs-steps">
                    <li class="favs-step">
                        <span class="favs-step-n">01</span>
                        <strong><?= e(trans('box.step_1_title')) ?></strong>
                        <p><?= e(trans('box.step_1_text')) ?></p>
                    </li>
                    <li class="favs-step">
                        <span class="favs-step-n">02</span>
                        <strong><?= e(trans('box.step_2_title')) ?></strong>
                        <p><?= e(trans('box.step_2_text')) ?></p>
                    </li>
                    <li class="favs-step">
                        <span class="favs-step-n">03</span>
                        <strong><?= e(trans('box.step_3_title')) ?></strong>
                        <p><?= e(trans('box.step_3_text')) ?></p>
                    </li>
                </ol>
            </section>
        <?php else: ?>
            <div class="favs-layout">
                <section class="favs-list" aria-label="<?= e(trans('box.list_title')) ?>">
                    <div class="favs-list-head">
                        <h2 class="favs-block-title"><?= e(trans('box.list_title')) ?></h2>
                        <span class="favs-list-count">
                            <?= e(trans($countKey, ['n' => (string) $count])) ?>
                        </span>
                    </div>

                    <div class="favs-grid" data-box-grid>
                        <?php foreach ($items as $index => $item): ?>
                            <?php
                            $productId = (int) ($item['id'] ?? 0);
                            $productUrl = (string) ($item['box_url'] ?? '#');
                            $image = (string) ($item['image'] ?? '');
                            $code = (string) ($item['model_code'] ?? $item['sku'] ?? '');
                            $desc = trim((string) ($item['short_description'] ?? ''));
                            $name = (string) ($item['name'] ?? '');
                            ?>
                            <article class="fav-card" data-box-card="<?= $productId ?>"
                                     style="--fav-delay: <?= e((string) (0.05 * (int) (($index % 6) + 1))) ?>s">
                                <a class="fav-card-media" href="<?= e($productUrl) ?>" tabindex="-1" aria-hidden="true">
                                    <img src="<?= e($image !== '' ? asset($image) : $fallbackImg) ?>"
                                         alt="" loading="<?= $index < 3 ? 'eager' : 'lazy' ?>"
                                         width="420" height="300" decoding="async"
                                         onerror="this.onerror=null; this.src='<?= e($fallbackImg) ?>';">
                                    <span class="fav-card-index"><?= str_pad((string) ((int) $index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                </a>
                                <div class="fav-card-body">
                                    <span class="fav-card-code"><?= e(trans('common.sku')) ?> <?= e($code) ?></span>
                                    <h3 class="fav-card-title">
                                        <a href="<?= e($productUrl) ?>"><?= e($name) ?></a>
                                    </h3>
                                    <p class="fav-card-desc box-card-wait"><?= e(trans('box.waiting_price')) ?></p>
                                    <div class="fav-card-actions">
                                        <a class="fav-card-open" href="<?= e($productUrl) ?>">
                                            <span><?= e(trans('box.open_product')) ?></span>
                                            <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                        </a>
                                        <button type="button" class="fav-card-remove"
                                                data-box-remove
                                                data-box-product="<?= $productId ?>"
                                                data-box-endpoint="<?= e($removeEndpoint) ?>"
                                                data-box-removed="<?= e(trans('box.removed_named', ['name' => $name])) ?>"
                                                data-box-label-off="<?= e(trans('box.add')) ?>"
                                                data-box-label-on="<?= e(trans('box.added')) ?>"
                                                aria-label="<?= e(trans('box.remove')) ?> — <?= e($name) ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" aria-hidden="true">
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                            </svg>
                                            <span><?= e(trans('box.remove')) ?></span>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <p class="box-limit-note">
                        <?= e(trans('box.limit_reached', ['n' => (string) $maxItems])) ?>
                    </p>
                </section>

                <aside class="favs-side">
                    <section class="favs-panel favs-panel-mail">
                        <span class="favs-panel-eyebrow"><?= e(trans('box.send_eyebrow')) ?></span>
                        <h2 class="favs-panel-title"><?= e(trans('box.send_title')) ?></h2>
                        <p class="favs-panel-text">
                            <?= e(trans($count === 1 ? 'box.send_text_one' : 'box.send_text_many', ['n' => (string) $count])) ?>
                        </p>

                        <form class="favs-mail" method="post" action="<?= e($sendEndpoint) ?>" data-box-send-form>
                            <?= csrf_field() ?>
                            <label class="favs-mail-block">
                                <span class="favs-mail-label"><?= e(trans('assistant.ask_label')) ?></span>
                                <span class="favs-mail-field">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="14" rx="2.5"/>
                                        <path d="m4 7 8 6 8-6"/>
                                    </svg>
                                    <input type="email" name="email" required autocomplete="email"
                                           value="<?= e($savedEmail) ?>"
                                           placeholder="<?= e(trans('box.send_placeholder')) ?>">
                                </span>
                            </label>
                            <label class="favs-mail-block">
                                <span class="favs-mail-label"><?= e(trans('box.note_label')) ?></span>
                                <textarea name="note" rows="3" maxlength="600"
                                          placeholder="<?= e(trans('box.note_placeholder')) ?>"><?= e($savedNote) ?></textarea>
                            </label>
                            <button type="submit" class="favs-mail-btn" data-box-send-submit>
                                <span data-box-send-label><?= e(trans('box.send_button')) ?></span>
                            </button>
                            <p class="favs-mail-status" data-box-status role="status" aria-live="polite"
                               data-box-sending="<?= e(trans('box.send_sending')) ?>"></p>
                        </form>

                        <p class="favs-notify-hint"><?= e(trans('box.send_foot')) ?></p>

                        <ul class="favs-points">
                            <li><?= e(trans('box.step_1_text')) ?></li>
                            <li><?= e(trans('box.step_3_text')) ?></li>
                        </ul>
                    </section>

                    <section class="favs-panel favs-panel-link">
                        <h2 class="favs-panel-title is-small"><?= e(trans('box.link_title')) ?></h2>
                        <p class="favs-panel-text"><?= e(trans('box.link_text')) ?></p>
                        <div class="favs-linkrow">
                            <input class="favs-linkinput" type="text" readonly value="<?= e($shareUrl) ?>"
                                   aria-label="<?= e(trans('box.link_title')) ?>" data-box-link>
                            <button type="button" class="favs-copy" data-box-copy
                                    data-box-copied="<?= e(trans('box.copied')) ?>"
                                    data-box-copy-failed="<?= e(trans('box.copy_failed')) ?>">
                                <span data-box-copy-label><?= e(trans('box.copy_button')) ?></span>
                            </button>
                        </div>
                    </section>

                    <section class="favs-panel favs-panel-foot">
                        <form method="post" action="<?= e($clearEndpoint) ?>" data-box-clear-form
                              data-box-confirm="<?= e(trans('box.clear_confirm')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="favs-clear">
                                <span><?= e(trans('box.clear')) ?></span>
                            </button>
                        </form>

                        <div class="favs-help">
                            <p><?= e(trans('box.send_foot')) ?></p>
                            <a href="mailto:<?= e($shopMail) ?>"><?= e($shopMail) ?></a>
                        </div>
                    </section>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>
