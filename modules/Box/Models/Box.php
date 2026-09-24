<?php

declare(strict_types=1);

namespace Modules\Box\Models;

use Core\Database\Model;

class Box extends Model
{
    protected static string $table = 'boxes';

    protected static array $fillable = [
        'token', 'email', 'locale', 'note', 'ip_hash', 'user_agent',
        'sends', 'sends_today', 'last_sent_at', 'notified_at', 'claimed_at',
    ];

    protected static array $casts = [
        'id' => 'int',
        'sends' => 'int',
        'sends_today' => 'int',
    ];
}
