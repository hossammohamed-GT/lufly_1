<?php

declare(strict_types=1);

/*
 * Saved-products lists ("favorites").
 *
 * Storefront visitors are anonymous: a list is identified by a random token that
 * lives in a long-lived cookie, and the same token travels in the e-mailed copy
 * of the list, so a visitor can open it later — and on another device.
 */
return [
    /* cookie that keeps the visitor's list between visits (1 year) */
    'cookie' => env('FAVORITES_COOKIE', 'lufly_favorites'),
    'cookie_days' => (int) env('FAVORITES_COOKIE_DAYS', 365),

    /* a list stops accepting new products at this size */
    'max_items' => (int) env('FAVORITES_MAX_ITEMS', 60),

    /* e-mailing the list: seconds between two sends of the same list, and the
       number of sends allowed per list per day (the endpoint is public, so it
       has to be boring for a spammer to abuse) */
    'email_cooldown' => (int) env('FAVORITES_EMAIL_COOLDOWN', 60),
    'email_daily_limit' => (int) env('FAVORITES_EMAIL_DAILY_LIMIT', 5),

    /* A mailed list goes to the visitor who asked for it, and to nobody else.
       The store inbox copy is off by default (FAVORITES_NOTIFY_ADMIN=true turns
       it back on for a team that wants the lead in its mail). */
    'notify_admin' => (bool) env('FAVORITES_NOTIFY_ADMIN', false),
];
