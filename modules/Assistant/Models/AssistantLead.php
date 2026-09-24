<?php

declare(strict_types=1);

namespace Modules\Assistant\Models;

use Core\Database\Model;

class AssistantLead extends Model
{
    protected static string $table = 'assistant_leads';

    protected static array $fillable = [
        'token', 'ip_hash', 'locale', 'email', 'message', 'product_id',
        'image_path', 'image_url',
        'image_name', 'image_mime', 'image_kb', 'summary', 'terms', 'category',
        'results_count', 'source', 'notified_at', 'status',
    ];

    protected static bool $softDelete = false;

    protected static array $casts = [
        'id' => 'int',
        'image_kb' => 'int',
        'results_count' => 'int',
    ];
}
