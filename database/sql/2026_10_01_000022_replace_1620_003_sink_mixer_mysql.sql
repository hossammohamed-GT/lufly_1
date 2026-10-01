-- ============================================================================
-- LUFLY catalog correction: 1620-003 -> Sink Mixer
-- Date: 2026-10-01
-- Target: MySQL 5.7+ / MySQL 8.x / MariaDB 10.4+
--
-- This replaces the former WC record carrying code 1620-003. It preserves the
-- product record when present (and therefore preserves its numeric ID), clears
-- all WC-specific catalog data, and writes the supplied sink mixer details.
--
-- Images are files, not MySQL blobs. Copy the originals without conversion
-- BEFORE running this script:
--   public/deleted/1.png
--     -> public/images/products/prod_1620-003-main.png
--   public/deleted/WhatsApp Image 2026-10-01 at 5.21.59 PM.jpeg
--     -> public/images/products/prod_1620-003-drawing.jpeg
--
-- In this repository use:
--   bash scripts/import-1620-003-images.sh
-- The script uses a byte-for-byte copy and verifies it with cmp; it does not
-- resize, recompress, convert, or strip image metadata.
-- ============================================================================

SET NAMES utf8mb4;
START TRANSACTION;

SET @catalog_now = UTC_TIMESTAMP();
SET @sink_mixers_category_id = (
    SELECT id FROM categories
    WHERE slug = 'sink-mixers' AND deleted_at IS NULL
    LIMIT 1
);
SET @lufly_brand_id = (
    SELECT id FROM brands
    WHERE slug = 'lufly' AND deleted_at IS NULL
    LIMIT 1
);

-- Find the old WC record by either old/new slug, or by its public model code.
SET @product_id = (
    SELECT id FROM products
    WHERE deleted_at IS NULL
      AND (
          slug IN ('wall-hung-water-closet-1620-003', 'sink-mixer-1620-003')
          OR model_code = '1620-003'
      )
    ORDER BY CASE WHEN slug = 'sink-mixer-1620-003' THEN 0 ELSE 1 END, id ASC
    LIMIT 1
);

-- Also works on an installation where the former WC record does not exist.
INSERT INTO products (
    model_code, slug, category_id, collection_id, brand_id,
    status, is_featured, sort_order, created_at, updated_at, deleted_at
)
SELECT
    '1620-003', 'sink-mixer-1620-003', @sink_mixers_category_id, NULL, @lufly_brand_id,
    'active', 0, 205, @catalog_now, @catalog_now, NULL
WHERE @product_id IS NULL;

SET @product_id = COALESCE(@product_id, LAST_INSERT_ID());

-- Remove all WC-only relations, translations, variants, specifications,
-- documents, SEO rows, search words, media links, and the old import record.
DELETE FROM product_relations
WHERE product_id = @product_id OR related_product_id = @product_id;
DELETE FROM product_documents WHERE product_id = @product_id;
DELETE FROM product_attribute_values WHERE product_id = @product_id;
DELETE FROM product_dimensions WHERE product_id = @product_id;
DELETE FROM product_specifications WHERE product_id = @product_id;
DELETE FROM product_media WHERE product_id = @product_id;
DELETE FROM product_variants WHERE product_id = @product_id;
DELETE FROM product_translations WHERE product_id = @product_id;
DELETE FROM seo_meta WHERE product_id = @product_id;
DELETE FROM product_search_keywords WHERE product_id = @product_id;
DELETE FROM product_import_logs WHERE product_id = @product_id;

-- The former WC assets are deleted from the media library only if they are not
-- shared by any other product. Delete their physical files from public/images/
-- separately after confirming they are no longer needed.
DELETE m
FROM media AS m
LEFT JOIN product_media AS pm ON pm.media_id = m.id
WHERE m.path IN (
    '/images/products/prod_2916_Screenshot-2026-07-22-163111.png',
    '/images/products/prod_2916_Screenshot-2026-07-22-163220.png',
    '/images/products/prod_1620-003-main.png',
    '/images/products/prod_1620-003-drawing.jpeg'
)
  AND pm.media_id IS NULL;

UPDATE products
SET
    model_code = '1620-003',
    slug = 'sink-mixer-1620-003',
    category_id = @sink_mixers_category_id,
    collection_id = NULL,
    brand_id = @lufly_brand_id,
    status = 'active',
    is_featured = 0,
    updated_at = @catalog_now,
    deleted_at = NULL
WHERE id = @product_id;

INSERT INTO product_translations (product_id, locale, name, short_description, description)
VALUES
(
    @product_id,
    'en',
    'Sink Mixer 1620-003',
    'Sink Mixer, Brass Faucet, Ceramic Cartridge, Brass Spout, 1/2" × 35 cm.',
    'Sink Mixer\nBrass Faucet\nCeramic Cartridge\nBrass Spout\n1/2" × 35 cm\n\nFinish: Chrome\nMixer Type: Single-Handle Mixer\nInstallation Method: Standing'
),
(
    @product_id,
    'tr',
    'Sink Mixer 1620-003',
    'Sink Mixer, Brass Faucet, Ceramic Cartridge, Brass Spout, 1/2" × 35 cm.',
    'Sink Mixer\nBrass Faucet\nCeramic Cartridge\nBrass Spout\n1/2" × 35 cm\n\nFinish: Chrome\nMixer Type: Single-Handle Mixer\nInstallation Method: Standing'
),
(
    @product_id,
    'cs',
    'Sink Mixer 1620-003',
    'Sink Mixer, Brass Faucet, Ceramic Cartridge, Brass Spout, 1/2" × 35 cm.',
    'Sink Mixer\nBrass Faucet\nCeramic Cartridge\nBrass Spout\n1/2" × 35 cm\n\nFinish: Chrome\nMixer Type: Single-Handle Mixer\nInstallation Method: Standing'
);

INSERT INTO product_variants (
    product_id, sku, variant_name, price, stock_status, sort_order, status,
    created_at, updated_at, deleted_at
) VALUES (
    @product_id, '1620-003', NULL, 0.00, 'in_stock', 1, 'active',
    @catalog_now, @catalog_now, NULL
);

INSERT INTO product_specifications (
    product_id, variant_id, spec_key, spec_value, unit, sort_order, created_at, updated_at
) VALUES
(@product_id, NULL, 'Faucet', 'Brass', NULL, 1, @catalog_now, @catalog_now),
(@product_id, NULL, 'Cartridge', 'Ceramic', NULL, 2, @catalog_now, @catalog_now),
(@product_id, NULL, 'Spout', 'Brass', NULL, 3, @catalog_now, @catalog_now),
(@product_id, NULL, 'Connection', '1/2" × 35 cm', NULL, 4, @catalog_now, @catalog_now),
(@product_id, NULL, 'Finish', 'Chrome', NULL, 5, @catalog_now, @catalog_now),
(@product_id, NULL, 'Mixer Type', 'Single-Handle Mixer', NULL, 6, @catalog_now, @catalog_now),
(@product_id, NULL, 'Installation Method', 'Standing', NULL, 7, @catalog_now, @catalog_now);

INSERT INTO media (
    uuid, collection, filename, original_name, path, mime_type, extension,
    size, width, height, meta, owner_id, status, created_at, updated_at, deleted_at
) VALUES (
    UUID(), 'sink-mixers', 'prod_1620-003-main.png', '1.png',
    '/images/products/prod_1620-003-main.png', 'image/png', 'png',
    0, NULL, NULL,
    JSON_OBJECT('source', 'manual-catalog-entry', 'source_filename', '1.png', 'role', 'main'),
    NULL, 'active', @catalog_now, @catalog_now, NULL
);
SET @main_media_id = LAST_INSERT_ID();

INSERT INTO media (
    uuid, collection, filename, original_name, path, mime_type, extension,
    size, width, height, meta, owner_id, status, created_at, updated_at, deleted_at
) VALUES (
    UUID(), 'sink-mixers', 'prod_1620-003-drawing.jpeg',
    'WhatsApp Image 2026-10-01 at 5.21.59 PM.jpeg',
    '/images/products/prod_1620-003-drawing.jpeg', 'image/jpeg', 'jpeg',
    0, NULL, NULL,
    JSON_OBJECT(
        'source', 'manual-catalog-entry',
        'source_filename', 'WhatsApp Image 2026-10-01 at 5.21.59 PM.jpeg',
        'role', 'drawing'
    ),
    NULL, 'active', @catalog_now, @catalog_now, NULL
);
SET @drawing_media_id = LAST_INSERT_ID();

INSERT INTO product_media (
    product_id, variant_id, media_id, type, sort_order, is_primary, created_at, updated_at
) VALUES
(@product_id, NULL, @main_media_id, 'main', 1, 1, @catalog_now, @catalog_now),
(@product_id, NULL, @drawing_media_id, 'drawing', 1, 0, @catalog_now, @catalog_now);

INSERT INTO seo_meta (
    product_id, locale, meta_title, meta_description, og_title, og_description,
    canonical_url, created_at, updated_at
) VALUES
(
    @product_id, 'en', 'Sink Mixer 1620-003 | LUFLY',
    'Sink Mixer, Brass Faucet, Ceramic Cartridge, Brass Spout, 1/2" × 35 cm.',
    NULL, NULL, NULL, @catalog_now, @catalog_now
),
(
    @product_id, 'tr', 'Sink Mixer 1620-003 | LUFLY',
    'Sink Mixer, Brass Faucet, Ceramic Cartridge, Brass Spout, 1/2" × 35 cm.',
    NULL, NULL, NULL, @catalog_now, @catalog_now
),
(
    @product_id, 'cs', 'Sink Mixer 1620-003 | LUFLY',
    'Sink Mixer, Brass Faucet, Ceramic Cartridge, Brass Spout, 1/2" × 35 cm.',
    NULL, NULL, NULL, @catalog_now, @catalog_now
);

INSERT INTO product_search_keywords (product_id, keyword) VALUES
(@product_id, 'lufly'),
(@product_id, '1620-003'),
(@product_id, 'sink'),
(@product_id, 'mixer'),
(@product_id, 'brass'),
(@product_id, 'ceramic'),
(@product_id, 'chrome'),
(@product_id, 'single-handle'),
(@product_id, 'standing'),
(@product_id, 'sink mixers');

INSERT INTO product_import_logs (
    product_id, source_file, source_page, detected_sku, status, raw_data,
    created_at, updated_at
) VALUES (
    @product_id,
    'manual-catalog-entry',
    NULL,
    '1620-003',
    'imported',
    JSON_OBJECT(
        'model_code', '1620-003',
        'slug', 'sink-mixer-1620-003',
        'name', 'Sink Mixer 1620-003',
        'main_image', '/images/products/prod_1620-003-main.png',
        'drawing_image', '/images/products/prod_1620-003-drawing.jpeg'
    ),
    @catalog_now,
    @catalog_now
);

COMMIT;
