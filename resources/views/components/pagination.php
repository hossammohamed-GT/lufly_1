<?php
/**
 * Reusable pagination component.
 *
 * Props:
 * - paginator: Core\Database\Paginator
 * - route: (optional) route name
 * - params: (optional) route params
 */
$paginator = $props['paginator'] ?? null;
if (!$paginator instanceof \Core\Database\Paginator || $paginator->lastPage() <= 1) {
    return;
}

$routeName = $props['route'] ?? null;
$routeParams = (array) ($props['params'] ?? []);
$currentQuery = request()->query();

$pageUrl = static function (int $page) use ($routeName, $routeParams, $currentQuery): string {
    $query = array_merge($currentQuery, ['page' => $page]);
    if ($page === 1) {
        unset($query['page']);
    }
    $queryString = http_build_query(array_filter($query, static fn ($v): bool => $v !== null && $v !== ''));
    $baseUrl = $routeName !== null ? route($routeName, $routeParams) : strtok((string) request()->url(), '?');

    return $baseUrl . ($queryString !== '' ? '?' . $queryString : '');
};

$currentPage = $paginator->page();
$lastPage = $paginator->lastPage();
$total = $paginator->total();
$perPage = $paginator->perPage();
$from = max(1, ($currentPage - 1) * $perPage + 1);
$to = min($total, $currentPage * $perPage);

$window = 2;
$start = max(1, $currentPage - $window);
$end = min($lastPage, $currentPage + $window);
?>
<div class="admin-pagination-wrapper">
    <div class="admin-pagination-info">
        <?= e(trans('common.page')) ?> <strong><?= $currentPage ?></strong> <?= e(trans('common.of')) ?> <strong><?= $lastPage ?></strong> &middot; <?= $from ?>&ndash;<?= $to ?> <?= e(trans('common.of')) ?> <?= $total ?>
    </div>
    <nav class="admin-pagination" aria-label="Pagination">
        <?php if ($currentPage > 1): ?>
            <a class="admin-page-link" href="<?= e($pageUrl($currentPage - 1)) ?>" aria-label="Previous">&laquo;</a>
        <?php endif; ?>

        <?php if ($start > 1): ?>
            <a class="admin-page-link" href="<?= e($pageUrl(1)) ?>">1</a>
            <?php if ($start > 2): ?>
                <span class="admin-page-dots">&hellip;</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($p = $start; $p <= $end; $p++): ?>
            <?php if ($p === $currentPage): ?>
                <span class="admin-page-current" aria-current="page"><?= $p ?></span>
            <?php else: ?>
                <a class="admin-page-link" href="<?= e($pageUrl($p)) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($end < $lastPage): ?>
            <?php if ($end < $lastPage - 1): ?>
                <span class="admin-page-dots">&hellip;</span>
            <?php endif; ?>
            <a class="admin-page-link" href="<?= e($pageUrl($lastPage)) ?>"><?= $lastPage ?></a>
        <?php endif; ?>

        <?php if ($currentPage < $lastPage): ?>
            <a class="admin-page-link" href="<?= e($pageUrl($currentPage + 1)) ?>" aria-label="Next">&raquo;</a>
        <?php endif; ?>
    </nav>
</div>
