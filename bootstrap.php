<?php

declare(strict_types=1);

use Core\Config\Config;
use Core\Config\Env;
use Core\Exceptions\Handler;
use Core\Foundation\Application;
use Core\Foundation\Autoloader;

require __DIR__ . '/core/Foundation/Autoloader.php';

Autoloader::register([
    'Core\\'      => __DIR__ . '/core',
    'App\\'       => __DIR__ . '/app',
    'Modules\\'   => __DIR__ . '/modules',
    'Database\\'  => __DIR__ . '/database',
]);

require __DIR__ . '/core/Foundation/helpers.php';

Env::load(__DIR__ . '/.env');

$app = new Application(__DIR__);
$GLOBALS['__app'] = $app;

Config::setPath($app->basePath('config'));

date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));

/* Never let a production installation silently share a fallback signing key.
   Local setup remains frictionless, while production fails before serving a
   request with misleading security guarantees. */
$appEnv = strtolower((string) env('APP_ENV', 'production'));
$appKey = trim((string) env('APP_KEY', ''));
if ($appEnv === 'production' && ($appKey === '' || $appKey === 'lufly')) {
    throw new RuntimeException('APP_KEY is required in production. Run: php cli key:generate');
}

Handler::register($app);

$app->registerProviders();
$app->bootProviders();

return $app;
