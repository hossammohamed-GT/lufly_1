<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Product catalog - collections (marketing product lines).
 * Example: Milano Collection, Elite Collection, Luxury Collection.
 */
class CollectionsSchema extends SchemaDefinition
{
    protected string $table = 'collections';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->slug()->unique();
        $table->string_255('image')->nullable();
        $table->status();
        $table->integer('sort_order')->default(0);
        $table->timestamps();
        $table->soft_delete();
    }
}
