<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\Migration\Migration;
use Database\Schema\BoxItemsSchema;
use Database\Schema\BoxesSchema;

/**
 * The quotation box: the list a visitor fills with the pieces he wants priced,
 * and the one message that sends it to the team.
 */
class CreateBoxesTables extends Migration
{
    public function up(): void
    {
        $this->schema->createFromDefinition(new BoxesSchema());
        $this->schema->createFromDefinition(new BoxItemsSchema());
    }

    public function down(): void
    {
        $this->schema->dropIfExists('box_items');
        $this->schema->dropIfExists('boxes');
    }
}
