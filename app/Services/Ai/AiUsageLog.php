<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Core\Database\Model;

class AiUsageLog extends Model
{
    protected static string $table = 'ai_usage';

    protected static array $fillable = [
        'scope', 'ip_hash', 'key_slot', 'model', 'prompt_tokens', 'output_tokens',
        'duration_ms', 'ok', 'day', 'error',
    ];

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
