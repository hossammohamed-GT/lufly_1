<?php

declare(strict_types=1);

@ini_set('memory_limit', '512M');

use Core\Http\Kernel;
use Core\Http\Request;

$root = dirname(__DIR__);
foreach (glob($root . '/storage/logs/*.log') ?: [] as $oldLog) {
    @unlink($oldLog);
}

$app = require $root . '/bootstrap.php';

/** @var Kernel $kernel */
$kernel = $app->get(Kernel::class);

$routes = [
    ['GET', '/', [301, 302, 307, 308]],
    ['GET', '/en', [200]],
    ['GET', '/tr', [200]],
    ['GET', '/cs', [200]],
    ['GET', '/en/products', [200]],
    ['GET', '/tr/urunler', [200]],
    ['GET', '/cs/produkty', [200]],
    ['GET', '/en/products?sort=name', [200]],
    ['GET', '/en/products?sort=newest', [200]],
    ['GET', '/en/products?sort=model', [200]],
    ['GET', '/en/products?category=bathroom.angle-valves', [200]],
    ['GET', '/en/products?q=1520', [200]],
    ['GET', '/en/products/1520-015', [200]],
    ['GET', '/tr/urunler/1520-015', [200]],
    ['GET', '/cs/produkty/1520-015', [200]],
    ['GET', '/en/products/1654-8032c', [200]],
    ['GET', '/en/products/1685-015', [301, 302, 404]],
    ['GET', '/en/contact', [200]],
    ['GET', '/tr/iletisim', [200]],
    ['GET', '/cs/kontakt', [200]],
    ['GET', '/en/central-vacuum', [200]],
    ['GET', '/en/favorites', [200]],
    ['GET', '/en/box', [200]],
    ['GET', '/lang/tr', [301, 302]],
    ['GET', '/api/health', [200]],
    ['GET', '/api/products?locale=tr', [200]],
    ['GET', '/api/products/search?q=1520&locale=cs', [200]],
    ['GET', '/sitemap.xml', [200]],
    ['GET', '/sitemaps/products.xml', [200]],
];

$failed = 0;

foreach ($routes as [$method, $uri, $allowedStatuses]) {
    foreach (glob($root . '/storage/logs/*.log') ?: [] as $oldLog) {
        @unlink($oldLog);
    }

    $parts = parse_url($uri) ?: [];
    $queryStr = (string) ($parts['query'] ?? '');
    $queryArr = [];
    if ($queryStr !== '') {
        parse_str($queryStr, $queryArr);
    }

    $server = [
        'REQUEST_METHOD' => $method,
        'REQUEST_URI' => $uri,
        'HTTP_HOST' => 'localhost',
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_REFERER' => 'http://localhost/en/products?category=bathroom',
    ];

    $req = new Request($queryArr, [], [], [], $server);
    $res = $kernel->handle($req);
    $status = $res->getStatus();
    $body = $res->getContent();

    if (!in_array($status, $allowedStatuses, true) || str_contains($body, 'Something went wrong. Please try again later.')) {
        $failed++;
        $logDump = '';
        foreach (glob($root . '/storage/logs/*.log') ?: [] as $lf) {
            $tail = array_slice(file($lf) ?: [], -12);
            $logDump .= basename($lf) . ': ' . implode(' | ', array_map('trim', $tail)) . ' ';
        }
        $cleanMsg = str_replace(["\r", "\n", '%'], [' ', ' ', '%25'], trim("FAIL [{$method} {$uri}] HTTP {$status} :: {$logDump}"));
        fwrite(STDOUT, "::error title=HTTPSmoke::{$cleanMsg}\n");
        fwrite(STDERR, "{$cleanMsg}\n");
    } else {
        fwrite(STDOUT, "PASS [{$method} {$uri}]: HTTP {$status}\n");
    }
}

if ($failed > 0) {
    fwrite(STDERR, "\n{$failed} HTTP smoke test(s) failed.\n");
    exit(1);
}

fwrite(STDOUT, "\nAll HTTP smoke tests passed with zero 500 errors.\n");
