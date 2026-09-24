<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\Migration\Migration;
use Database\Schema\AssistantLeadSchema;

class CreateAssistantLeadsTable extends Migration
{
    public function up(): void
    {
        $this->schema->createFromDefinition(new AssistantLeadSchema());
    }

    public function down(): void
    {
        $this->schema->dropIfExists('assistant_leads');
    }
}
