<?php

declare(strict_types=1);

namespace Modules\Favorites\Models;

use Core\Database\Model;

class Favorite extends Model
{
    protected static string $table = 'favorites';

    protected static array $fillable = [
        'token', 'email', 'locale', 'user_id', 'ip_hash', 'user_agent',
        'notify', 'emails_sent', 'emails_today', 'last_emailed_at', 'claimed_at',
    ];

    protected static array $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'emails_sent' => 'int',
        'emails_today' => 'int',
        'notify' => 'bool',
    ];

    public function wantsUpdates(): bool
    {
        return $this->notify !== false && trim((string) $this->email) !== '';
    }
}
