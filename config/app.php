<?php

declare(strict_types=1);

use Core\Http\Middleware\ApiLogger;
use Core\Http\Middleware\Authenticate;
use Core\Http\Middleware\Authorize;
use Core\Http\Middleware\CsrfGuard;
use Core\Http\Middleware\SecurityHeaders;
use Core\Http\Middleware\SetLocale;

return [
    'name' => env('APP_NAME', 'Lufly Platform'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', ''),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'version' => '1.0.0',

    'providers' => [
        App\Providers\AppServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ],

    'middleware_groups' => [
        'web' => ['locale', 'csrf', 'security'],
        'api' => ['locale', 'security', 'csrf', 'api.log'],
    ],

    'middleware_aliases' => [
        'auth' => Authenticate::class,
        'permission' => Authorize::class,
        'locale' => SetLocale::class,
        'csrf' => CsrfGuard::class,
        'security' => SecurityHeaders::class,
        'api.log' => ApiLogger::class,
    ],
];
