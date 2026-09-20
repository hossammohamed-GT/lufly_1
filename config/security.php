<?php

declare(strict_types=1);

return [
    'bcrypt_cost' => (int) env('SECURITY_BCRYPT_COST', 12),

    'login_max_attempts' => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS', 5),
    'login_lockout_seconds' => (int) env('SECURITY_LOGIN_LOCKOUT_SECONDS', 300),

    'session_name' => 'lufly_session',
    'session_lifetime' => (int) env('SESSION_LIFETIME', 3600),
    /* Set SESSION_SECURE_COOKIE=true together with HTTPS in production;
       a Secure cookie over plain http would break local logins. */
    'session_secure_cookie' => (bool) env('SESSION_SECURE_COOKIE', false),

    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        /* Report-Only: collects violations without breaking the inline theme
           bootstrap / JSON-LD. Promote to enforcing CSP after a report cycle. */
        'Content-Security-Policy-Report-Only' => "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self' https://wa.me",
    ],

    'uploads' => [
        'blocked_extensions' => ['php', 'phtml', 'phar', 'sh', 'bat', 'exe', 'dll', 'cgi'],
    ],
];
