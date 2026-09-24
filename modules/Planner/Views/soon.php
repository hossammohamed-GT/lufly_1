<?php
$view->layout('layouts.frontend');
$view->pushStyle('frontend/planner/planner.css');
$view->pushStyle('frontend/assistant/chat.css');
?>
<main class="planner planner-soon" id="main">
    <div class="planner-inner">
        <header class="planner-head">
            <span class="planner-eyebrow"><?= e(trans('planner.eyebrow')) ?></span>
            <h1 class="planner-title"><?= e(trans('planner.soon_title')) ?></h1>
            <p class="planner-lede"><?= e(trans('planner.soon_text')) ?></p>
        </header>

        <div class="planner-grid">
            <section class="planner-chat">
                <span class="planner-soon-pill"><?= e(trans('planner.soon_pill')) ?></span>
                <p class="planner-foot"><?= e(trans('planner.soon_note')) ?></p>
                <a class="planner-go" href="<?= e($finderUrl) ?>"><?= e(trans('planner.soon_finder')) ?></a>
            </section>

            <aside class="planner-board">
                <div class="planner-board-empty">
                    <p><?= e(trans('planner.soon_waiting')) ?></p>
                </div>
            </aside>
        </div>
    </div>
</main>
