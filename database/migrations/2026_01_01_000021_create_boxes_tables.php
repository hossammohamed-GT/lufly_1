<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\Migration\Migration;
use Database\Schema\BoxItemsSchema;
use Database\Schema\BoxesSchema;

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
