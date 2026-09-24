<?php
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
        <?php ?>
        <?= $view->renderFile($view->resolvePath('planner::partials.question'), [
            'step' => $step,
            'answers' => $answers,
            'locale' => $locale,
            'progress' => $progress,
        ]) ?>
    </div>
</div>
<div class="planner-thinking" data-planner-thinking hidden>
    <span class="planner-dots" aria-hidden="true"><i></i><i></i><i></i></span>
    <span class="planner-thinking-text" data-planner-thinking-text></span>
</div>
