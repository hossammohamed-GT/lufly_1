<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class CategoriesSchema extends SchemaDefinition
{
    protected string $table = 'categories';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->bigInteger('parent_id')->nullable()->index();
        $table->slug()->unique();
        $table->string_255('image')->nullable();
        $table->status();
        $table->seo();
        $table->timestamps();
        $table->soft_delete();
    }
}
