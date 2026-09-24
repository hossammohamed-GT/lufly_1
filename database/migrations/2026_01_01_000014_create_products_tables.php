<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\Migration\Migration;
use Database\Schema\AttributeOptionsSchema;
use Database\Schema\AttributesSchema;
use Database\Schema\BrandsSchema;
use Database\Schema\CollectionTranslationsSchema;
use Database\Schema\CollectionsSchema;
use Database\Schema\ProductAttributeValuesSchema;
use Database\Schema\ProductDimensionsSchema;
use Database\Schema\ProductDocumentsSchema;
use Database\Schema\ProductImportLogsSchema;
use Database\Schema\ProductMediaSchema;
use Database\Schema\ProductRelationsSchema;
use Database\Schema\ProductSearchKeywordsSchema;
use Database\Schema\ProductSpecificationsSchema;
use Database\Schema\ProductTranslationsSchema;
use Database\Schema\ProductVariantsSchema;
use Database\Schema\ProductsSchema;
use Database\Schema\SeoMetaSchema;

class CreateProductsTables extends Migration
{
    public function up(): void
    {
        $this->schema->createFromDefinition(new BrandsSchema());
        $this->schema->createFromDefinition(new CollectionsSchema());
        $this->schema->createFromDefinition(new CollectionTranslationsSchema());
        $this->schema->createFromDefinition(new ProductsSchema());
        $this->schema->createFromDefinition(new ProductTranslationsSchema());
        $this->schema->createFromDefinition(new ProductVariantsSchema());
        $this->schema->createFromDefinition(new AttributesSchema());
        $this->schema->createFromDefinition(new AttributeOptionsSchema());
        $this->schema->createFromDefinition(new ProductAttributeValuesSchema());
        $this->schema->createFromDefinition(new ProductSpecificationsSchema());
        $this->schema->createFromDefinition(new ProductDimensionsSchema());
        $this->schema->createFromDefinition(new ProductMediaSchema());
        $this->schema->createFromDefinition(new ProductDocumentsSchema());
        $this->schema->createFromDefinition(new ProductRelationsSchema());
        $this->schema->createFromDefinition(new SeoMetaSchema());
        $this->schema->createFromDefinition(new ProductSearchKeywordsSchema());
        $this->schema->createFromDefinition(new ProductImportLogsSchema());
    }

    public function down(): void
    {
        foreach ([
            'product_import_logs',
            'product_search_keywords',
            'seo_meta',
            'product_relations',
            'product_documents',
            'product_media',
            'product_dimensions',
            'product_specifications',
            'product_attribute_values',
            'attribute_options',
            'attributes',
            'product_variants',
            'product_translations',
            'products',
            'collection_translations',
            'collections',
            'brands',
        ] as $table) {
            $this->schema->dropIfExists($table);
        }
    }
}
