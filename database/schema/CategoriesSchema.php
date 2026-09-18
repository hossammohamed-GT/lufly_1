<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Category tree - unlimited depth through parent_id.
 * Example: Bathroom > Toilets > Wall Hung Toilets.
 */
class CategoriesSchema extends SchemaDefinition
{
    protected string $table = 'categories';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->bigInteger('parent_id')->nullable()->index();
        $table->slug()->unique();
        $table->string_255('image')->nullable();
        $table->string_255('icon')->nullable();
        $table->boolean('is_featured')->default(0)->index();
        $table->integer('sort_order')->default(0);
        $table->status();
        $table->seo();
        $table->timestamps();
        $table->soft_delete();
    }
}
