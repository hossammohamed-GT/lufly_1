<?php

declare(strict_types=1);

return [
    /*
     * Transports
     *   log   -> nothing leaves the server, the message is written to storage/logs/mail.log
     *   mail  -> PHP mail() (the hosting MTA on XAMPP / cPanel; the From address must be local)
     *   smtp  -> authenticated SMTP conversation (recommended for info@lufly.tr on any host)
     */
    'transport' => env('MAIL_TRANSPORT', 'log'),

    'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@lufly.test'),
    'from_name' => env('MAIL_FROM_NAME', 'LUFLY'),

    /* Replies from a customer land on the store inbox, not on no-reply. */
    'reply_to_address' => env('MAIL_REPLY_TO', env('MAIL_FROM_ADDRESS', 'no-reply@lufly.test')),
    'reply_to_name' => env('MAIL_REPLY_TO_NAME', ''),

    /* Store inbox: receives the copy of every saved list (a lead to follow up). */
    'admin_address' => env('MAIL_ADMIN_ADDRESS', 'info@lufly.tr'),

    'smtp' => [
        'host' => env('MAIL_HOST', '127.0.0.1'),
        'port' => (int) env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        // tls = STARTTLS (usually port 587) · ssl = implicit TLS (usually port 465) · none = plain
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'auth' => (bool) env('MAIL_AUTH', true),
        'timeout' => (int) env('MAIL_TIMEOUT', 15),
        // Some shared hosts need the HELO name to match the sending domain.
        'helo' => env('MAIL_HELO', ''),
    ],
];
