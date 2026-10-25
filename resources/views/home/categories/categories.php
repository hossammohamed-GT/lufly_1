<?php
/**
 * Home category atlas. The complete tree and its own images come from
 * HomeController / CategoryService; no product inventory is required.
 *
 * @var Core\View\View $view
 * @var array<int, array<string, mixed>> $categories
 */
echo $view->component('category-explorer', [
    'categories' => is_array($categories ?? null) ? $categories : [],
    'categoryCounts' => is_array($categoryCounts ?? null) ? $categoryCounts : null,
    'mode' => 'home',
]);
