<?php

declare(strict_types=1);

namespace Modules\Favorites\Models;

use Core\Database\Model;

/**
 * One anonymous saved-products list. `token` is the public handle: it lives in
 * the visitor's cookie and in every e-mailed copy of the list.
 */
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

    /**
     * Whether a newly saved product should be mailed to the visitor.
     *
     * The address has to be known and the option has to be on: a list that was
     * never asked (null) counts as "yes", because the visitor ticks the box when
     * they hand over the address in the first place.
     */
    public function wantsUpdates(): bool
    {
        return $this->notify !== false && trim((string) $this->email) !== '';
    }
}
