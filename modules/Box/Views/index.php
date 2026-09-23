<?php
/**
 * The quotation box page.
 *
 * Same editorial language as the saved list, with one difference that is the
 * whole point of the box: the side panel does not mail the list to the visitor —
 * it sends the list to the team as a request for prices, and hands out the one
 * link that opens this exact box anywhere.
 *
 * @var Core\View\View $view
 * @var string $locale
 * @var array<int, array<string, mixed>> $items
 * @var int $count
 * @var string $shareUrl
 * @var int $maxItems
 * @var string $shopMail
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/favorites/favorites.css');
$view->pushStyle('frontend/box/box.css');
$view->pushScript('frontend/box/box.js');

$items = $items ?? [];
$count = (int) ($count ?? count($items));
$shareUrl = (string) ($shareUrl ?? '');
$fallbackImg = asset('/images/products/prod_146_1620-111-a.jpg');
$addEndpoint = route('box.add');
$sendEndpoint = route('box.send');
$clearEndpoint = route('box.clear');
?>
<main class="boxs" id="main"
      data-box-page
      data-box-endpoint="<?= e($addEndpoint) ?>"
      data-box-send-endpoint="<?= e($sendEndpoint) ?>"
      data-box-clear-endpoint="<?= e($clearEndpoint) ?>"
      data-box-copied="<?= e(trans('box.copied')) ?>"
      data-box-copy-failed="<?= e(trans('box.copy_failed')) ?>"
      data-box-removed="<?= e(trans('box.removed')) ?>"
      data-box-empty="<?= e(trans('box.empty_title')) ?>">
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
                    <dd class="favs-stat-value" data-box-count><?= (int) $count ?></dd>
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
            <section class="favs-empty">
                <span class="favs-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 8.5 6 4h12l2 4.5"/>
                        <path d="M4 8.5h16V19a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8.5Z"/>
                        <path d="M9.5 13h5"/>
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
                            <?= e(count($items) === 1 ? trans('box.list_count_one') : trans('box.list_count_many', ['n' => (string) count($items)])) ?>
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
                            ?>
                            <article class="fav-card" data-box-card="<?= $productId ?>"
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
                                            <span><?= e(trans('box.open_product')) ?></span>
                                            <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                        </a>
                                        <button type="button" class="fav-card-remove"
                                                data-box-remove
                                                data-box-product="<?= $productId ?>"
                                                data-box-endpoint="<?= e($addEndpoint) ?>"
                                                aria-label="<?= e(trans('box.remove')) ?> — <?= e($item['name'] ?? '') ?>">
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
                </section>

                <aside class="favs-side">
                    <section class="favs-panel favs-panel-mail is-box">
                        <span class="favs-panel-eyebrow"><?= e(trans('box.send_eyebrow')) ?></span>
                        <h2 class="favs-panel-title"><?= e(trans('box.send_title')) ?></h2>
                        <p class="favs-panel-text">
                            <?= e(count($items) === 1 ? trans('box.send_text_one') : trans('box.send_text_many', ['n' => (string) count($items)])) ?>
                        </p>

                        <form class="favs-mail" method="post" action="<?= e($sendEndpoint) ?>" data-box-send-form>
                            <?= csrf_field() ?>
                            <label class="favs-mail-field">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2.5"/>
                                    <path d="m4 7 8 6 8-6"/>
                                </svg>
                                <input type="email" name="email" required
                                       value="<?= e($box !== null ? (string) ($box->email ?? '') : '') ?>"
                                       placeholder="<?= e(trans('box.send_placeholder')) ?>"
                                       aria-label="<?= e(trans('box.send_placeholder')) ?>"
                                       autocomplete="email" inputmode="email">
                            </label>

                            <label class="favs-mail-field is-note">
                                <textarea name="note" rows="3" maxlength="<?= (int) config('box.max_note', 600) ?>"
                                          placeholder="<?= e(trans('box.note_placeholder')) ?>"
                                          aria-label="<?= e(trans('box.note_label')) ?>"><?= e((string) ($box?->note ?? '')) ?></textarea>
                            </label>

                            <button type="submit" class="favs-mail-btn" data-box-send-submit>
                                <span data-box-send-label><?= e(trans('box.send_button')) ?></span>
                                <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <p class="favs-mail-status" data-box-send-status role="status" aria-live="polite"
                               data-sending="<?= e(trans('box.send_sending')) ?>"></p>
                        </form>

                        <p class="favs-notify-hint"><?= e(trans('box.send_hint')) ?></p>
                    </section>

                    <?php if ($shareUrl !== ''): ?>
                        <section class="favs-panel favs-panel-link">
                            <span class="favs-panel-eyebrow"><?= e(trans('box.link_eyebrow')) ?></span>
                            <h2 class="favs-panel-title"><?= e(trans('box.link_title')) ?></h2>
                            <p class="favs-panel-text"><?= e(trans('box.link_text')) ?></p>

                            <div class="favs-link">
                                <input type="text" readonly value="<?= e($shareUrl) ?>" data-box-link
                                       aria-label="<?= e(trans('box.link_title')) ?>">
                                <button type="button" class="favs-link-btn" data-box-copy
                                        data-copied="<?= e(trans('box.copied')) ?>"
                                        data-copy-error="<?= e(trans('box.copy_failed')) ?>">
                                    <?= e(trans('box.copy_button')) ?>
                                </button>
                            </div>

                            <form method="post" action="<?= e($clearEndpoint) ?>" data-box-clear-form>
                                <?= csrf_field() ?>
                                <button type="submit" class="favs-clear"
                                        data-confirm="<?= e(trans('box.clear_confirm')) ?>">
                                    <?= e(trans('box.clear')) ?>
                                </button>
                            </form>
                        </section>
                    <?php endif; ?>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>
