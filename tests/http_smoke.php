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
    ['GET', '/en/products?category=kitchen', [200]],
    ['GET', '/en/products?category=kitchen.kitchen-mixers', [200]],
    ['GET', '/en/products?category=kitchen.kitchen-mixers.pull-out-mixers', [200]],
    ['GET', '/en/products?q=1520', [200]],
    ['GET', '/en/products/1653-8033c', [200]],
    ['GET', '/tr/urunler/1653-8033c', [200]],
    ['GET', '/cs/produkty/1653-8033c', [200]],
    ['GET', '/en/products/1520-2030', [200]],
    ['GET', '/tr/urunler/1520-2030', [200]],
    ['GET', '/cs/produkty/1520-2030', [200]],
    ['GET', '/en/products/1654-8032c', [200]],
    ['GET', '/en/products/1685-2030', [302]],
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

    $customError = '';
    if (in_array($uri, [
        '/en',
        '/tr',
        '/cs',
        '/en/products',
        '/tr/urunler',
        '/cs/produkty',
        '/en/products?category=kitchen',
        '/en/products?category=kitchen.kitchen-mixers',
        '/en/products?category=kitchen.kitchen-mixers.pull-out-mixers',
    ], true)) {
        if (!preg_match('/<article\s+class="pcard\b([^"]*)"[^>]*data-product-card[^>]*>(.*?)<\/article>/s', $body, $firstCard)) {
            $customError = 'No product card found in grid';
        } else {
            $cardClasses = $firstCard[1];
            $cardInner = $firstCard[2];
            if (!str_contains(' ' . $cardClasses . ' ', ' is-featured ')) {
                $customError = 'First product card is missing golden frame class is-featured';
            } elseif (!str_contains($cardInner, '/1653-8033c"')) {
                $customError = 'First product card is not Pull-Out Kitchen Mixer (1653-8033c)';
            } elseif (!str_contains($cardInner, 'is-featured-badge')) {
                $customError = 'First product card is missing golden featured badge';
            }
        }
    } elseif (in_array($uri, ['/en/products/1653-8033c', '/tr/urunler/1653-8033c', '/cs/produkty/1653-8033c'], true)) {
        $expectedDatasheetLabel = match ($uri) {
            '/tr/urunler/1653-8033c' => 'Teknik Föy',
            '/cs/produkty/1653-8033c' => 'Technický list',
            default => 'Data Sheet',
        };
        if (!str_contains($body, 'pdp-stage is-featured') || !str_contains($body, 'pdp-code-chip is-gold')) {
            $customError = 'Featured PDP is missing golden stage or golden featured chip';
        } elseif (!str_contains($body, 'class="pdp-datasheet-btn"') || !str_contains($body, 'aria-disabled="true"') || !str_contains($body, $expectedDatasheetLabel)) {
            $customError = 'PDP is missing localized disabled Data Sheet button (' . $expectedDatasheetLabel . ')';
        } elseif (!str_contains($body, 'data-pdp-variant-cards') || !str_contains($body, 'pdp-vcard is-current')) {
            $customError = 'Multi-color PDP is missing desktop visual variant cards band';
        } elseif (!str_contains($body, 'data-pdp-similar') || !str_contains($body, 'data-pdp-complementary')) {
            $customError = 'PDP is missing similar or complementary marketplace discovery sections';
        } elseif (substr_count($body, 'data-product-card') < 4) {
            $customError = 'PDP rendered fewer than 4 marketplace discovery product cards';
        }
    }

    if ($customError === '' && preg_match_all('/<article\s+class="pcard\b[^"]*"[^>]*data-product-card[^>]*>(.*?)<\/article>/s', $body, $allCards)) {
        foreach ($allCards[1] as $idx => $cHtml) {
            if (!str_contains($cHtml, 'data-pcard-colors') || !str_contains($cHtml, 'pcard-swatch--')) {
                $customError = 'Product card #' . ($idx + 1) . ' is missing rendered finish/color swatches';
                break;
            }
            if (str_contains($cHtml, 'pcard-swatch--neutral')) {
                $customError = 'Product card #' . ($idx + 1) . ' rendered a neutral fallback swatch';
                break;
            }
        }
    }

    if (!in_array($status, $allowedStatuses, true) || str_contains($body, 'Something went wrong. Please try again later.') || $customError !== '') {
        $failed++;
        $logDump = $customError !== '' ? ($customError . ' ') : '';
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
