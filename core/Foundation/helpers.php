<?php

declare(strict_types=1);

use Core\Auth\Auth;
use Core\Config\Config;
use Core\Foundation\Application;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Router;
use Core\Http\Session;
use Core\Localization\Translator;
use Core\Logging\Log;
use Core\View\View;

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        /** @var Application|null $instance */
        $instance = $GLOBALS['__app'] ?? null;

        if ($abstract === null) {
            return $instance;
        }

        return $instance instanceof Application ? $instance->get($abstract) : null;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            'empty' => '',
            default => $value,
        };
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return app(Application::class)->basePath($path);
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return app(Application::class)->storagePath($path);
    }
}

if (!function_exists('resource_path')) {
    function resource_path(string $path = ''): string
    {
        return app(Application::class)->resourcePath($path);
    }
}

if (!function_exists('trans')) {
    function trans(string $key, array $params = [], ?string $locale = null): string
    {
        return app(Translator::class)->trans($key, $params, $locale);
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = [], bool $absolute = true): string
    {
        return app(Router::class)->url($name, $params, $absolute);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return app(Router::class)->baseUrl($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return app(Router::class)->baseUrl(ltrim($path, '/'));
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): Response
    {
        return Response::view($template, $data);
    }
}

if (!function_exists('request')) {
    function request(): Request
    {
        return app(Request::class);
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        $session = app(Session::class);

        return $key === null ? $session : $session->get($key, $default);
    }
}

if (!function_exists('auth')) {
    function auth(): Auth
    {
        return app(Auth::class);
    }
}

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(\App\Services\SettingsService::class)->get($key, $default);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($to, $status);
    }
}

if (!function_exists('back')) {
    function back(int $status = 302): RedirectResponse
    {
        $referer = app(Request::class)->header('HTTP_REFERER');

        return new RedirectResponse($referer ?? url('/'), $status);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return app(Session::class)->csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = app(Session::class)->getFlash('_old_input', []);

        return $old[$key] ?? $default;
    }
}

if (!function_exists('logger')) {
    function logger(string $message, array $context = [], string $level = 'info', string $channel = 'app'): void
    {
        Log::channel($channel)->log($level, $message, $context);
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): never
    {
        throw match ($code) {
            404 => new \Core\Exceptions\NotFoundException($message ?: 'Not Found'),
            401 => new \Core\Exceptions\AuthenticationException($message ?: 'Unauthenticated'),
            403 => new \Core\Exceptions\AuthorizationException($message ?: 'Forbidden'),
            default => new \Core\Exceptions\AppException($message ?: 'Server Error', $code),
        };
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$values): never
    {
        foreach ($values as $value) {
            var_dump($value);
        }
        exit(1);
    }
}

if (!function_exists('feature')) {
    function feature(string $name, bool $default = false): bool
    {
        return \Core\Foundation\FeatureFlag::enabled($name, $default);
    }
}

