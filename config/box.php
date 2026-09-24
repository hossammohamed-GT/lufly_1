<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('BOX_ENABLED', true),

    'cookie' => env('BOX_COOKIE', 'lufly_box'),
    'cookie_days' => (int) env('BOX_COOKIE_DAYS', 180),

    'max_items' => (int) env('BOX_MAX_ITEMS', 40),

    'mail' => (string) env('BOX_MAIL', 'hossam545mohamed@gmail.com'),
    'default_email' => (string) env('BOX_MAIL_DEFAULT', 'hossam545mohamed@gmail.com'),

    'copy_visitor' => (bool) env('BOX_COPY_VISITOR', true),

    'send_cooldown' => (int) env('BOX_SEND_COOLDOWN', 60),
    'send_daily_limit' => (int) env('BOX_SEND_DAILY_LIMIT', 5),

    'saved_notice' => (bool) env('BOX_SAVED_NOTICE', false),
    'saved_cooldown' => (int) env('BOX_SAVED_COOLDOWN', 300),

    'max_note' => (int) env('BOX_MAX_NOTE', 600),
];
