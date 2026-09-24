<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class AnnouncementTranslationsSchema extends SchemaDefinition
{
    protected string $table = 'announcement_translations';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('announcement_id')->index();
        $table->string_50('locale')->index();
        $table->string('message', 500);
        $table->string_150('cta_label')->nullable();

        $table->foreign('announcement_id')->references('id')->on('announcements')->onDelete('CASCADE');
        $table->unique('announcement_id', 'locale');
    }
}
