<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Support\Str;

class Session
{
    private bool $started = false;

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }
        if (headers_sent()) {
            return;
        }

        $secure = (bool) config('security.session_secure_cookie', false);
        if (!$secure && (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
            || env('APP_ENV') === 'production'
        )) {
            $secure = true;
        }

        session_set_cookie_params([
            'lifetime' => (int) config('security.session_lifetime', 7200),
            'path' => '/',
            'httponly' => (bool) config('security.session_httponly', true),
            'samesite' => (string) config('security.session_samesite', 'Lax'),
            'secure' => $secure,
        ]);
        session_name((string) config('security.session_name', 'lufly_session'));
        session_start();
        $this->started = true;

        $this->ageFlash();
    }

    public function id(): string
    {
        $this->start();
        return session_id() ?: '';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $this->start();
        return $_SESSION ?? [];
    }

    public function flash(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION['_flash']['new'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION['_flash']['old'][$key] ?? $default;
    }

    private function ageFlash(): void
    {
        $_SESSION['_flash']['old'] = $_SESSION['_flash']['new'] ?? [];
        $_SESSION['_flash']['new'] = [];
    }

    public function csrfToken(): string
    {
        $this->start();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = Str::random(40);
        }

        return $_SESSION['_csrf'];
    }

    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        $this->started = false;
    }
}
