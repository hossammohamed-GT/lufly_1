<?php
$items = (array) ($plan['items'] ?? []);
$room = (array) ($plan['room'] ?? []);
$clearance = (int) ($plan['clearance'] ?? 0);
$handoff = (array) ($handoff ?? []);
$renderEnabled = (bool) ($renderEnabled ?? false);
$renderSoon = (bool) ($renderSoon ?? false);
$fit = is_array($fit ?? null) ? $fit : null;
$fallbackImg = asset('/images/products/prod_146_1620-111-a.jpg');
?>
<div class="planp" data-planner-plan>
    <header class="planp-head">
        <span class="planp-eyebrow"><?= e(trans('planner.plan_ready')) ?></span>
        <h2 class="planp-title"><?= e(trans('planner.title')) ?></h2>
        <p class="planp-summary"><?= e((string) ($plan['summary'] ?? '')) ?></p>
    </header>

    <dl class="planp-facts">
        <div class="planp-fact">
            <dt><?= e(trans('planner.room')) ?></dt>
            <dd><?= e((string) $room['w']) ?> × <?= e((string) $room['l']) ?> cm</dd>
        </div>
        <div class="planp-fact">
            <dt><?= e(trans('planner.look')) ?></dt>
            <dd><?= e((string) trans('planner.look_' . $answers['look'])) ?></dd>
        </div>
        <div class="planp-fact">
            <dt><?= e(trans('planner.door')) ?></dt>
            <dd><?= e((string) ($room['door_note'] ?? '')) ?></dd>
        </div>
        <?php if (($room['window_note'] ?? '') !== ''): ?>
            <div class="planp-fact">
                <dt><?= e(trans('planner.window')) ?></dt>
                <dd><?= e((string) $room['window_note']) ?></dd>
            </div>
        <?php endif; ?>
    </dl>
<section class="planp-items">
        <h3 class="planp-h3"><?= e(trans('planner.items')) ?></h3>
        <ol class="planp-list">
            <?php foreach ($items as $index => $item): ?>
                <?php ?>
                <li class="planp-item">
                    <span class="planp-n"><?= e(str_pad((string) ((int) $index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                    <div class="planp-item-body">
                        <p class="planp-item-name">
                            <?= e((string) $item['label']) ?>
                            <span class="planp-zone"><?= e((string) $item['zone']) ?></span>
                        </p>
                        <p class="planp-item-size">
                            <span class="planp-size-label"><?= e(trans('planner.recommended')) ?></span>
                            <span class="planp-size-value"><?= e((string) $item['size']) ?></span>
                        </p>

                        <?php if (($item['picks'] ?? []) !== []): ?>
                            <div class="planp-picks">
                                <?php foreach ($item['picks'] as $pick): ?>
                                    <?php
                                    $image = (string) ($pick['image'] ?? '');
                                    $name = (string) ($pick['name'] ?? '');
                                    ?>
                                    <article class="planp-pick">
                                        <a class="planp-pick-media" href="<?= e((string) $pick['url']) ?>" tabindex="-1" aria-hidden="true">
                                            <img src="<?= e($image !== '' ? asset($image) : $fallbackImg) ?>"
                                                 alt="" loading="lazy" decoding="async" width="120" height="120"
                                                 onerror="this.onerror=null; this.src='<?= e($fallbackImg) ?>';">
                                        </a>
                                        <div class="planp-pick-body">
                                            <a class="planp-pick-name" href="<?= e((string) $pick['url']) ?>"><?= e($name) ?></a>
                                            <?php if (($pick['size'] ?? '') !== ''): ?>
                                                <span class="planp-pick-size"><?= e((string) $pick['size']) ?></span>
                                            <?php endif; ?>
                                            <span class="planp-pick-link"><?= e(trans('planner.see_product')) ?></span>
                                        </div>
                                        <?= $view->component('favorite-button', [
                                            'product_id' => (int) $pick['id'],
                                            'name' => $name,
                                            'variant' => 'card',
                                        ]) ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
<section class="planp-draw">
        <div class="planp-draw-head">
            <h3 class="planp-h3"><?= e(trans('planner.drawing_title')) ?></h3>
            <button type="button" class="planp-btn is-quiet" data-planner-print>
                <?= e(trans('planner.print')) ?>
            </button>
        </div>
        <div class="planp-canvas"><?= $plan['drawing'] ?></div>
        <p class="planp-note"><?= e(trans('planner.drawing_note')) ?></p>
        <p class="planp-clearance <?= $clearance >= 0 ? 'is-ok' : 'is-tight' ?>">
            <?= e((string) ($plan['clearance_note'] ?? '')) ?>
        </p>
    </section>

    <?php if ($renderSoon): ?>
<section class="planp-render is-soon" data-planner-render-soon>
            <h3 class="planp-h3">
                <?= e(trans('planner.render_title')) ?>
                <span class="planp-pill"><?= e(trans('planner.render_soon_pill')) ?></span>
            </h3>
            <p class="planp-note"><?= e(trans('planner.render_soon_hint')) ?></p>
        </section>

    <?php elseif ($renderEnabled): ?>
<section class="planp-render" data-planner-render-block>
            <h3 class="planp-h3"><?= e(trans('planner.render_title')) ?></h3>
            <p class="planp-note"><?= e(trans('planner.render_hint')) ?></p>
            <button type="button" class="planp-btn is-primary" data-planner-render>
                <?= e(trans('planner.render_cta')) ?>
            </button>
            <div class="planp-render-out" data-planner-render-out hidden></div>
        </section>
    <?php endif; ?>

    <?php if ($fit !== null): ?>
<section class="planp-fit" data-planner-fit-block>
            <h3 class="planp-h3"><?= e(trans('planner.fit_title')) ?></h3>
            <p class="planp-note"><?= e((string) $fit['hint']) ?></p>
            <button type="button" class="planp-btn is-primary" data-planner-fit>
                <?= e(trans('planner.fit_cta')) ?>
            </button>
            <p class="planp-fit-about"><?= e(trans('planner.fit_about', ['name' => (string) $fit['name']])) ?></p>
            <div class="planp-fit-out" data-planner-fit-out hidden></div>
        </section>
    <?php endif; ?>
<section class="planp-handoff">
        <h3 class="planp-h3"><?= e(trans('planner.handoff_title')) ?></h3>
        <p class="planp-note"><?= e(trans('planner.handoff_text')) ?></p>

        <div class="planp-handoff-actions">
            <a class="planp-btn is-whatsapp" href="<?= e((string) $handoff['whatsapp']) ?>"
               target="_blank" rel="noopener">
                <?= e(trans('planner.handoff_whatsapp')) ?>
            </a>

            <form class="planp-mail" data-planner-send novalidate>
                <label class="planp-field">
                    <span><?= e(trans('planner.email_label')) ?></span>
                    <input type="email" name="email" inputmode="email" autocomplete="email"
                           placeholder="<?= e(trans('planner.email_placeholder')) ?>" required>
                </label>
                <button type="submit" class="planp-btn is-quiet"><?= e(trans('planner.handoff_email')) ?></button>
            </form>
        </div>

        <?php if (($handoff['phone'] ?? '') !== ''): ?>
            <p class="planp-phone"><?= e(trans('planner.handoff_phone', ['phone' => (string) $handoff['phone']])) ?></p>
        <?php endif; ?>

        <p class="planp-mail-state" data-planner-send-state hidden></p>
    </section>

    <button type="button" class="planp-btn is-ghost planp-restart" data-planner-restart>
        <?= e(trans('planner.restart')) ?>
    </button>
</div>
