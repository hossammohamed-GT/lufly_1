<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Core\Database\Model;

class AiCacheEntry extends Model
{
    protected static string $table = 'ai_cache';

    protected static array $fillable = [
        'scope', 'fingerprint', 'payload', 'hits', 'model', 'expires_at',
    ];

    protected static bool $softDelete = false;

    protected static array $casts = [
        'id' => 'int',
        'hits' => 'int',
        'payload' => 'json',
    ];
}
