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
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        'Cross-Origin-Opener-Policy' => 'same-origin-allow-popups',
    ],

    /* Start in report-only mode because this site intentionally contains some
       inline boot/analytics snippets. Review browser reports before setting
       SECURITY_CSP_REPORT_ONLY=false. A nonce-based policy is the end state. */
    'csp' => [
        'policy' => (string) env('SECURITY_CSP', "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.clarity.ms; connect-src 'self' https://www.google-analytics.com https://*.clarity.ms"),
        'report_only' => (bool) env('SECURITY_CSP_REPORT_ONLY', true),
    ],

    /* Enable only after every production/subdomain request is HTTPS. */
    'hsts' => [
        'enabled' => (bool) env('SECURITY_HSTS', false),
        'value' => 'max-age=31536000; includeSubDomains',
    ],

    'uploads' => [
        'blocked_extensions' => ['php', 'phtml', 'phar', 'sh', 'bat', 'exe', 'dll', 'cgi'],
    ],
];
