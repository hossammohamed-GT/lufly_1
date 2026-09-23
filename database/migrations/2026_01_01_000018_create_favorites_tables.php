<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\Migration\Migration;
use Database\Schema\FavoriteItemsSchema;
use Database\Schema\FavoritesSchema;

class CreateFavoritesTables extends Migration
{
    public function up(): void
    {
        $this->schema->createFromDefinition(new FavoritesSchema());
        $this->schema->createFromDefinition(new FavoriteItemsSchema());
    }

    public function down(): void
    {
        $this->schema->dropIfExists('favorite_items');
        $this->schema->dropIfExists('favorites');
    }
}
