<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
        fwrite(STDERR, "FAIL: {$message}\n");
    } else {
        fwrite(STDOUT, "PASS: {$message}\n");
    }
};

require $root . '/core/Config/Env.php';
use Core\Config\Env;

$assert(Env::parseValue('true') === true, 'Env parses booleans');
$assert(Env::parseValue('value # comment') === 'value', 'Env strips inline comments');
$assert(Env::parseValue('"value # kept"') === 'value # kept', 'Env preserves quoted values');

$example = (string) file_get_contents($root . '/.env.example');
$assert(preg_match('/^APP_KEY=\s*$/m', $example) === 1, 'Example environment contains no shared APP_KEY');

$csrf = (string) file_get_contents($root . '/core/Http/Middleware/CsrfGuard.php');
$assert(!str_contains($csrf, 'permit same-origin AJAX'), 'AJAX requests cannot bypass CSRF token validation');
$assert(str_contains($csrf, 'hash_equals'), 'CSRF tokens use timing-safe comparison');

$security = (string) file_get_contents($root . '/config/security.php')
    . (string) file_get_contents($root . '/core/Http/Middleware/SecurityHeaders.php');
foreach (['Permissions-Policy', 'Content-Security-Policy', 'Strict-Transport-Security'] as $control) {
    $assert(str_contains($security, $control), "Security configuration covers {$control}");
}

$schema = (string) file_get_contents($root . '/app/Services/SEOService.php');
$assert(str_contains($schema, '$price > 0'), 'Product offers require a positive price');
$assert(!str_contains($schema, "'priceCurrency' => 'EUR'"), 'Product schema does not invent a currency');

$rootHtaccess = (string) file_get_contents($root . '/.htaccess');
$assert(!preg_match('/FilesMatch "\\\.\(css.*?immutable/s', $rootHtaccess), 'Unhashed CSS/JS are not cached as immutable');

if ($failures !== []) {
    fwrite(STDERR, "\n" . count($failures) . " contract test(s) failed.\n");
    exit(1);
}

fwrite(STDOUT, "\nAll repository contract tests passed.\n");
