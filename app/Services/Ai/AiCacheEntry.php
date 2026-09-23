<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Core\Database\Model;

/**
 * A stored AI answer.
 *
 * `fingerprint` is a hash of (scope + the exact question + the inputs that
 * matter, e.g. the uploaded image), so the same question asked twice costs one
 * request instead of two — the single biggest saving on a free quota.
 */
class AiCacheEntry extends Model
{
    protected static string $table = 'ai_cache';

    protected static array $fillable = [
        'scope', 'fingerprint', 'payload', 'hits', 'model', 'expires_at',
    ];

    /* neither table carries deleted_at: the base model would filter rows out */
    protected static bool $softDelete = false;

    protected static array $casts = [
        'id' => 'int',
        'hits' => 'int',
        'payload' => 'json',
    ];
}
