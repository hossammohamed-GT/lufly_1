<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * What a visitor asked the finder for: their words, the photo (as a link the
 * shop can open), the address to answer on, and whether the team was already
 * told about it.
 */
class AssistantLeadSchema extends SchemaDefinition
{
    protected string $table = 'assistant_leads';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->string('token', 64)->unique();
        $table->string_100('ip_hash')->index();
        $table->string('locale', 10)->default('en');
        /* the visitor's address; when the field is left empty the customers
           table stores the shop's own default instead */
        $table->string('email', 190)->default('hossam545mohamed@gmail.com');
        $table->text('message')->nullable();
        /* the product page the visitor was looking at, when the ask came from one */
        $table->integer('product_id')->default(0);
        $table->string_255('image_path')->nullable();
        $table->string_255('image_url')->nullable();
        $table->string_255('image_name')->nullable();
        $table->string_100('image_mime')->nullable();
        $table->integer('image_kb')->default(0);
        /* whatever the model said the piece was, in one line */
        $table->string_255('summary')->nullable();
        $table->string_255('terms')->nullable();
        $table->string_255('category')->nullable();
        $table->integer('results_count')->default(0);
        $table->string('source', 20)->default('text');
        $table->string('status', 20)->default('new');
        $table->dateTime('notified_at')->nullable();
        $table->timestamps();
    }
}
