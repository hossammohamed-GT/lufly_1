<?php
/**
 * TEMPORARY DIAGNOSTIC (delete after use).
 * Open in browser: http://localhost/lufly_1/debug-media.php
 * Prints exactly what the product-media pipeline returns on the CURRENT
 * database connection (whatever DB_CONNECTION is set to in .env).
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$app = require __DIR__ . '/bootstrap.php';

use Modules\Products\Models\Product;

echo "== 1. Connection being used\n";
$config = config('database');
echo "driver: " . ($config['connections'][$config['default']]['driver'] ?? '?') . "\n";
echo "database: " . ($config['connections'][$config['default']]['database'] ?? '?') . "\n\n";

$c = Product::query()->connection();

echo "== 2. Raw content of the media-related tables\n";
echo "media total: " . $c->selectOne("SELECT COUNT(*) AS n FROM media")['n'] . "\n";
echo "media by status: ";
print_r($c->select("SELECT status, COUNT(*) AS n FROM media GROUP BY status"));
echo "product_media by type: ";
print_r($c->select("SELECT type, COUNT(*) AS n FROM product_media GROUP BY type"));

$p = Product::query()->where('id', 30)->first();

echo "\n== 3. Product #30 — model state\n";
echo "getKey(): "; var_dump($p ? $p->getKey() : null);
echo "slug: " . ($p->slug ?? '?') . "  status: " . ($p->status ?? '?') . "\n\n";

echo "== 4. Step-by-step media() query (each variant)\n";
$tests = [
    'A. plain join (no filters)' =>
        "SELECT pm.type, m.status, m.path FROM product_media pm
         INNER JOIN media m ON m.id = pm.media_id
         WHERE pm.product_id = ?",
    'B. + variant_id IS NULL' =>
        "SELECT pm.type, m.status, m.path FROM product_media pm
         INNER JOIN media m ON m.id = pm.media_id
         WHERE pm.product_id = ? AND (pm.variant_id IS NULL)",
    'C. + status <> missing  (THIS IS THE APP QUERY)' =>
        "SELECT pm.type, m.status, m.path FROM product_media pm
         INNER JOIN media m ON m.id = pm.media_id
         WHERE pm.product_id = ? AND (pm.variant_id IS NULL) AND m.status <> 'missing'",
    'D. + type = drawing' =>
        "SELECT pm.type, m.status, m.path FROM product_media pm
         INNER JOIN media m ON m.id = pm.media_id
         WHERE pm.product_id = ? AND (pm.variant_id IS NULL) AND m.status <> 'missing' AND pm.type = 'drawing'",
];
foreach ($tests as $label => $sql) {
    $rows = $c->select($sql, [$p->getKey()]);
    echo "$label => " . count($rows) . " rows\n";
    foreach ($rows as $r) {
        printf("     %-8s | status=%-8s | path=%s\n", $r['type'], var_export($r['status'], true), $r['path']);
    }
}

echo "\n== 5. What the model methods return\n";
echo "media():    " . count($p->media())    . " rows\n";
echo "gallery():  " . count($p->gallery())  . " rows\n";
echo "drawings(): " . count($p->drawings()) . " rows\n";
echo "\nSend ALL of this output back (copy/paste).\n";
