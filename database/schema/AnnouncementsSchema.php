<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Site announcements ("the word") - short marketing messages the admin
 * publishes on the storefront, e.g. "50% discount today".
 * The message text per language lives in announcement_translations.
 */
class AnnouncementsSchema extends SchemaDefinition
{
    protected string $table = 'announcements';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->string_50('placement')->default('topbar')->index(); // topbar|home_banner
        $table->string_50('style')->default('promo')->index();      // promo|info|warning
        $table->string_255('link_url')->nullable();
        $table->boolean('is_active')->default(1)->index();
        $table->dateTime('starts_at')->nullable();
        $table->dateTime('ends_at')->nullable();
        $table->integer('sort_order')->default(0);
        $table->timestamps();
        $table->soft_delete();
    }
}
