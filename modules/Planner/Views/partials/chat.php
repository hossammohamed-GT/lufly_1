<?php
/**
 * The conversation itself: the greeting, the question on screen and the line
 * that rotates while the assistant works.
 *
 * Shared by the planner page and the floating chat, so both speak with the
 * same voice and carry the same markup (frontend/planner/planner.js wires
 * whichever one the page shows).
 *
 * @var Core\View\View $view
 * @var array<string, mixed> $answers
 * @var string $locale
 * @var string $step        the question to open with
 * @var int $progress
 */
$step = (string) ($step ?? 'size');
$progress = (int) ($progress ?? 1);
?>
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
            'step' => $step,
            'answers' => $answers,
            'locale' => $locale,
            'progress' => $progress,
        ]) ?>
    </div>
</div>

<!-- one sentence at a time, never the same one twice -->
<div class="planner-thinking" data-planner-thinking hidden>
    <span class="planner-dots" aria-hidden="true"><i></i><i></i><i></i></span>
    <span class="planner-thinking-text" data-planner-thinking-text></span>
</div>
