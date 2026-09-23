<?php
/**
 * One question of the planner chat: the sentence, the hint and the choices.
 *
 * Every choice is a button carrying the field it answers and the value it
 * holds — the browser never builds an answer of its own (see planner.js).
 *
 * @var Core\View\View $view
 * @var string $step        size | custom | wet | look
 * @var array<string, mixed> $answers
 * @var string $locale
 * @var int $progress
 */
$progress = (int) ($progress ?? 1);
$room = (array) ($answers['room'] ?? ['w' => 200, 'l' => 250]);
$limits = (array) config('planner.custom', ['min' => 100, 'max' => 600]);
$min = (int) ($limits['min'] ?? 100);
$max = (int) ($limits['max'] ?? 600);
?>
<div class="planner-msg is-bot" data-planner-question="<?= e($step) ?>">
    <span class="planner-avatar" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 20V9.5L12 4l8 5.5V20"/>
            <path d="M9 20v-5.5h6V20"/>
        </svg>
    </span>
    <div class="planner-bubble">
        <span class="planner-progress"><?= e(trans('planner.step_of', ['n' => (string) max(1, min(3, $progress))])) ?></span>

        <?php if ($step === 'custom'): ?>
            <p class="planner-q"><?= e(trans('planner.q_custom')) ?></p>
            <p class="planner-hint"><?= e(trans('planner.q_custom_hint', ['min' => (string) $min, 'max' => (string) $max])) ?></p>
            <form class="planner-numbers" data-planner-custom novalidate>
                <label class="planner-field">
                    <span><?= e(trans('planner.custom_w')) ?></span>
                    <input type="number" inputmode="numeric" name="w" min="<?= $min ?>" max="<?= $max ?>"
                           step="5" value="<?= (int) ($room['w'] ?? 200) ?>" required>
                </label>
                <label class="planner-field">
                    <span><?= e(trans('planner.custom_l')) ?></span>
                    <input type="number" inputmode="numeric" name="l" min="<?= $min ?>" max="<?= $max ?>"
                           step="5" value="<?= (int) ($room['l'] ?? 250) ?>" required>
                </label>
                <button type="submit" class="planner-submit" data-planner-answer="custom" data-planner-value="custom">
                    <?= e(trans('planner.custom_send')) ?>
                </button>
            </form>

        <?php elseif ($step === 'wet'): ?>
            <p class="planner-q"><?= e(trans('planner.q_wet')) ?></p>
            <p class="planner-hint"><?= e(trans('planner.wet_hint')) ?></p>
            <div class="planner-choices is-two">
                <button type="button" class="planner-chip" data-planner-answer="wet" data-planner-value="shower">
                    <span class="planner-chip-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M5 12a4 4 0 0 1 4-4h2a3 3 0 0 0 3-3"/>
                            <path d="M8 16v1M12 16v2M16 16v1"/>
                        </svg>
                    </span>
                    <span class="planner-chip-text"><?= e(trans('planner.wet_shower')) ?></span>
                </button>
                <button type="button" class="planner-chip" data-planner-answer="wet" data-planner-value="bath">
                    <span class="planner-chip-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 11h18v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                            <path d="M6 11V6a2 2 0 0 1 4 0"/>
                        </svg>
                    </span>
                    <span class="planner-chip-text"><?= e(trans('planner.wet_bath')) ?></span>
                </button>
            </div>

        <?php elseif ($step === 'look'): ?>
            <p class="planner-q"><?= e(trans('planner.q_look')) ?></p>
            <p class="planner-hint"><?= e(trans('planner.look_hint')) ?></p>
            <div class="planner-choices is-four">
                <?php foreach (['modern-chrome', 'modern-matte', 'classic-chrome', 'minimal-steel'] as $look): ?>
                    <button type="button" class="planner-chip is-look" data-planner-answer="look" data-planner-value="<?= e($look) ?>">
                        <span class="planner-swatch is-<?= e($look) ?>" aria-hidden="true"></span>
                        <span class="planner-chip-text"><?= e(trans('planner.look_' . $look)) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <p class="planner-q"><?= e(trans('planner.q_size')) ?></p>
            <p class="planner-hint"><?= e(trans('planner.q_size_hint')) ?></p>
            <div class="planner-choices is-four">
                <?php foreach (['compact', 'standard', 'family'] as $size): ?>
                    <button type="button" class="planner-chip is-size" data-planner-answer="size" data-planner-value="<?= e($size) ?>">
                        <span class="planner-chip-text"><?= e(trans('planner.size_' . $size)) ?></span>
                    </button>
                <?php endforeach; ?>
                <button type="button" class="planner-chip is-ghost" data-planner-answer="size" data-planner-value="custom">
                    <span class="planner-chip-text"><?= e(trans('planner.size_custom')) ?></span>
                </button>
            </div>
        <?php endif; ?>

        <p class="planner-error" data-planner-error hidden></p>
    </div>
</div>
