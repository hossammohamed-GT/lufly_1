<?php
/**
 * Where the plan lands: the empty shelf that explains what is about to happen,
 * and the host the finished plan is injected into.
 *
 * @var Core\View\View $view
 * @var string $variant   'page' (the planner page) or 'chat' (the floating panel)
 */
$variant = (string) ($variant ?? 'page');
?>
<aside class="planner-board is-<?= e($variant) ?>" data-planner-board aria-live="polite">
    <div class="planner-board-empty" data-planner-board-empty>
        <span class="planner-board-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1"
                 stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="16" rx="2"/>
                <path d="M3 10h18M10 4v16"/>
            </svg>
        </span>
        <p><?= e(trans('planner.board_empty')) ?></p>
    </div>
    <div class="planner-board-plan" data-planner-board-plan></div>
</aside>
