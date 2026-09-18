<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Import log - products ingested from external files
 * (PDF / Excel / CSV catalogs, AI extraction, ...).
 */
class ProductImportLogsSchema extends SchemaDefinition
{
    protected string $table = 'product_import_logs';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('product_id')->nullable()->index();
        $table->string_255('source_file')->nullable();
        $table->integer('source_page')->nullable();
        $table->string_100('detected_sku')->nullable();
        $table->string_50('status')->default('pending')->index(); // pending|imported|skipped|failed
        $table->long_text('raw_data')->nullable();
        $table->timestamps();

        $table->foreign('product_id')->references('id')->on('products')->onDelete('SET NULL');
    }
}
