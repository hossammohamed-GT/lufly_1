<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\Migration\Migration;
use Database\Schema\AiCacheSchema;
use Database\Schema\AiUsageSchema;

/**
 * The AI foundation: cached answers and the usage counters that keep a free
 * quota alive (see app/Services/Ai).
 */
class CreateAiTables extends Migration
{
    public function up(): void
    {
        $this->schema->createFromDefinition(new AiCacheSchema());
        $this->schema->createFromDefinition(new AiUsageSchema());
    }

    public function down(): void
    {
        $this->schema->dropIfExists('ai_usage');
        $this->schema->dropIfExists('ai_cache');
    }
}
