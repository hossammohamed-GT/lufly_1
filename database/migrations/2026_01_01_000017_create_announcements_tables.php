<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\Migration\Migration;
use Database\Schema\AnnouncementTranslationsSchema;
use Database\Schema\AnnouncementsSchema;

class CreateAnnouncementsTables extends Migration
{
    public function up(): void
    {
        $this->schema->createFromDefinition(new AnnouncementsSchema());
        $this->schema->createFromDefinition(new AnnouncementTranslationsSchema());
    }

    public function down(): void
    {
        $this->schema->dropIfExists('announcement_translations');
        $this->schema->dropIfExists('announcements');
    }
}
