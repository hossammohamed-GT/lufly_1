<?php

declare(strict_types=1);

return [
    'cookie' => env('FAVORITES_COOKIE', 'lufly_favorites'),
    'cookie_days' => (int) env('FAVORITES_COOKIE_DAYS', 365),

    'max_items' => (int) env('FAVORITES_MAX_ITEMS', 60),

    'email_cooldown' => (int) env('FAVORITES_EMAIL_COOLDOWN', 60),
    'email_daily_limit' => (int) env('FAVORITES_EMAIL_DAILY_LIMIT', 5),

    'notify_admin' => (bool) env('FAVORITES_NOTIFY_ADMIN', false),
];
