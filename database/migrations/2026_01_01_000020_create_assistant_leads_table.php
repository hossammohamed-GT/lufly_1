<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\Migration\Migration;
use Database\Schema\AssistantLeadSchema;

/**
 * The finder's customers: every "I am looking for something like this" that
 * arrived with an address or a photo.
 *
 * The photo is not stored in the table — only its public path and the exact
 * link the shop gets in the mail, so the inbox opens it in one click.
 */
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
