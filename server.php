<?php

declare(strict_types=1);

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Serve static files from public/ directly
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

// Serve static files from root directly (frontend design system, admin assets, etc.)
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Route all other application traffic through the front controller
require_once __DIR__ . '/public/index.php';
