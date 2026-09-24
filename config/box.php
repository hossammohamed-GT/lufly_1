<?php

declare(strict_types=1);

/*
 * The box — a quotation shopping list.
 *
 * A visitor collects the pieces he wants priced (from the finder's cards or
 * while he browses) into one list, gets a link to it, and sends it to the shop
 * in one message: "price these for me". It is deliberately *not* the saved
 * list (favorites): that one is private and mails itself to the visitor, this
 * one is a quotation request that ends in the team's inbox.
 *
 * Nothing is created until the first piece is put in the box: an empty visitor
 * costs nothing, and the box is found again through a long-lived cookie — or
 * through the link printed in the message.
 */
return [
    'enabled' => (bool) env('BOX_ENABLED', true),

    /* cookie that keeps the box between visits */
    'cookie' => env('BOX_COOKIE', 'lufly_box'),
    'cookie_days' => (int) env('BOX_COOKIE_DAYS', 180),

    /* how many pieces one box takes */
    'max_items' => (int) env('BOX_MAX_ITEMS', 40),

    /* the mail the request goes to, and the address stored when the visitor
       sends the box without leaving one */
    'mail' => (string) env('BOX_MAIL', 'hossam545mohamed@gmail.com'),
    'default_email' => (string) env('BOX_MAIL_DEFAULT', 'hossam545mohamed@gmail.com'),

    /* send a copy to the visitor as well (he keeps the link that way) */
    'copy_visitor' => (bool) env('BOX_COPY_VISITOR', true),

    /* seconds between two sends of the same box, and sends per box per day */
    'send_cooldown' => (int) env('BOX_SEND_COOLDOWN', 60),
    'send_daily_limit' => (int) env('BOX_SEND_DAILY_LIMIT', 5),

    /* Off by default: the shop keeps quiet until the visitor himself sends.
       A box that is only being filled is his business, not the team's — turn
       BOX_SAVED_NOTICE=true on when the team wants to watch boxes fill, and the
       cooldown below keeps that to one note per box per window. */
    'saved_notice' => (bool) env('BOX_SAVED_NOTICE', false),
    'saved_cooldown' => (int) env('BOX_SAVED_COOLDOWN', 300),

    /* how much the visitor may write with the request */
    'max_note' => (int) env('BOX_MAX_NOTE', 600),
];
