<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Central media library - every uploaded image/video/file registers here
 * first and is then attached through product_media / product_documents.
 */
class MediaSchema extends SchemaDefinition
{
    protected string $table = 'media';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->char('uuid', 36)->nullable()->index();
        $table->string_50('collection')->index();
        $table->string_255('filename');
        $table->string_255('original_name');
        $table->string_255('path');
        $table->string_100('mime_type');
        $table->string_50('extension');
        $table->bigInteger('size')->default(0);
        $table->integer('width')->nullable();
        $table->integer('height')->nullable();
        $table->json('meta')->nullable();
        $table->foreignId('owner_id')->nullable();
        $table->status();
        $table->timestamps();
        $table->soft_delete();

        $table->foreign('owner_id')->references('id')->on('users')->onDelete('SET NULL');
    }
}
