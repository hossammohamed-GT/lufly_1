<?php

declare(strict_types=1);

use Core\Foundation\Application;
use Core\Http\Kernel;
use Core\Http\Request;

define('LUFLY_START', microtime(true));

$app = require dirname(__DIR__) . '/bootstrap.php';

/* Canonical host enforcement (SEO): when SEO_ENFORCE_HOST=true, any request
   that arrives on a non-canonical scheme or host (http vs https, www vs
   non-www) is permanently redirected to the APP_URL form, so search engines
   index exactly one variant. Local development is untouched: the flag
   defaults to false and never fires on localhost-style hosts. */
if (config('seo.enforce_host')) {
    $canonicalBase = rtrim((string) config('app.url', ''), '/');
    $canonicalScheme = (string) (parse_url($canonicalBase, PHP_URL_SCHEME) ?: 'https');
    $canonicalHost = (string) (parse_url($canonicalBase, PHP_URL_HOST) ?: '');
    $requestHost = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    $isLocal = $requestHost === '' || (bool) preg_match('/^(localhost|127\.0\.0\.1|.*\.test|.*\.local)(:\d+)?$/i', $requestHost);

    if (!$isLocal && $canonicalHost !== ''
        && (strcasecmp($requestHost, $canonicalHost) !== 0 || ($canonicalScheme === 'https' && !$isHttps))) {
        $target = $canonicalBase . (string) ($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: ' . $target, true, 308);
        exit;
    }
}

$kernel = $app->get(Kernel::class);
$response = $kernel->handle(Request::capture());
$response->send();
$kernel->terminate();
