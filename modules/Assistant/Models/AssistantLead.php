<?php

declare(strict_types=1);

namespace Modules\Assistant\Models;

use Core\Database\Model;

/**
 * One visitor request that came with an address (or a photo): "this person is
 * looking for something like this".
 *
 * The row is what the shop keeps — the words, the photo, the address the
 * visitor wants to be answered on — and what turns into the mail the team
 * reads. The photo itself lives in the public uploads folder, so the link in
 * that mail opens straight from the inbox.
 */
class AssistantLead extends Model
{
    protected static string $table = 'assistant_leads';

    protected static array $fillable = [
        'token', 'ip_hash', 'locale', 'email', 'message', 'product_id',
        'image_path', 'image_url',
        'image_name', 'image_mime', 'image_kb', 'summary', 'terms', 'category',
        'results_count', 'source', 'notified_at', 'status',
    ];

    /* the table has no deleted_at column: the base model would filter rows out */
    protected static bool $softDelete = false;

    protected static array $casts = [
        'id' => 'int',
        'image_kb' => 'int',
        'results_count' => 'int',
    ];
}
