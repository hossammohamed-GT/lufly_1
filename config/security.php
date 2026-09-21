<?php

declare(strict_types=1);

return [
    'bcrypt_cost' => (int) env('SECURITY_BCRYPT_COST', 12),

    'login_max_attempts' => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS', 5),
    'login_lockout_seconds' => (int) env('SECURITY_LOGIN_LOCKOUT_SECONDS', 300),

    'session_name' => (string) env('SESSION_NAME', 'lufly_session'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', 7200),
    'session_secure_cookie' => (bool) env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production' || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')),
    'session_samesite' => (string) env('SESSION_SAMESITE', 'Lax'),
    'session_httponly' => true,

    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-XSS-Protection' => '1; mode=block',
    ],

    'uploads' => [
        'blocked_extensions' => ['php', 'phtml', 'phar', 'sh', 'bat', 'exe', 'dll', 'cgi'],
    ],
];
