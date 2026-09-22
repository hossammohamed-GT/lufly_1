<?php

declare(strict_types=1);

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Block sensitive files and directories immediately
$isBlocked = preg_match('/(^|\/)(\.env|\.git|\.sqlite|\.db|\.sql|\.log)($|\.|\/)/i', $uri)
    || preg_match('#^/(storage|database|core|app|modules|config|scratch|tests)(/|$)#i', $uri);

if ($isBlocked) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo '403 Forbidden: Access Denied';
    exit;
}

// Block executable scripts from being served directly as static files
$isExecutableScript = preg_match('/\.(php|phtml|phar|php[34578]|cgi|pl|py|sh|bash|exe|dll|bat|cmd)($|\?)/i', $uri);
if ($isExecutableScript && $uri !== '/index.php') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo '403 Forbidden: Execution Denied';
    exit;
}

// Serve static files from public/ directly (excluding sensitive/script extensions)
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri) && !is_dir(__DIR__ . '/public' . $uri)) {
    return false;
}

// Serve static frontend assets from root directly (css, js, fonts, images)
if (str_starts_with($uri, '/frontend/') && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Route all other application traffic through the front controller
require_once __DIR__ . '/public/index.php';

