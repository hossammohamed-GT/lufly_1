<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Core\Database\Model;

/**
 * One AI call, kept for the guard rails: the daily limit per visitor and the
 * "how much quota is left today" overview in the admin.
 *
 * The IP is stored hashed — the same rule the favorites lists follow.
 */
class AiUsageLog extends Model
{
    protected static string $table = 'ai_usage';

    protected static array $fillable = [
        'scope', 'ip_hash', 'key_slot', 'model', 'prompt_tokens', 'output_tokens',
        'duration_ms', 'ok', 'day', 'error',
    ];

    /* neither table carries deleted_at: the base model would filter rows out */
    protected static bool $softDelete = false;

    protected static array $casts = [
        'id' => 'int',
        'key_slot' => 'int',
        'prompt_tokens' => 'int',
        'output_tokens' => 'int',
        'duration_ms' => 'int',
        'ok' => 'bool',
    ];
}
