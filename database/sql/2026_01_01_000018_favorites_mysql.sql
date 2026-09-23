-- =============================================================================
-- LUFLY — saved products ("favorites") — MySQL patch
-- -----------------------------------------------------------------------------
-- The only database change the favorites feature needs, for a database that is
-- already running (production / phpMyAdmin / XAMPP) instead of a fresh import.
--
--   * two tables:  favorites        one saved list per visitor (anonymous)
--                  favorite_items   the products inside a list
--   * one row in `migrations` so the application knows the migration has run
--     (without it, a later `php cli migrate` would try to create the tables
--      again and fail with "table already exists")
--
-- How to use it
--   1. phpMyAdmin -> select the `lufly` database -> SQL tab
--   2. paste this whole file -> Go
--   3. run the verification queries at the bottom
--
-- Alternative: if you have terminal access, `php cli migrate` does the same
-- thing by itself (this file is its MySQL equivalent).
--
-- Safe to run twice: the tables use IF NOT EXISTS and the migrations row is
-- only inserted when it is missing.
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. a visitor's saved list
--    `token` is the public handle (64 hex characters): it lives in the visitor's
--    cookie and in every e-mailed copy of the list.
--    `notify` = 1 when the visitor asked to be e-mailed on every new save.
--    `user_id` stays NULL for storefront visitors; it is only set if an admin
--    account happens to save products while logged in.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `favorites` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `token` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(255) NULL,
    `locale` VARCHAR(50) NOT NULL DEFAULT 'en',
    `user_id` BIGINT UNSIGNED NULL,
    KEY favorites_user_id_index (`user_id`),
    `ip_hash` VARCHAR(100) NULL,
    `user_agent` VARCHAR(255) NULL,
    `emails_sent` INTEGER NOT NULL DEFAULT 0,
    `emails_today` INTEGER NOT NULL DEFAULT 0,
    `last_emailed_at` DATETIME NULL,
    `claimed_at` DATETIME NULL,
    `notify` TINYINT(1) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    KEY favorites_deleted_at_index (`deleted_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. the products inside a list (one row per product, no duplicates)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `favorite_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `favorite_id` BIGINT UNSIGNED NOT NULL,
    KEY favorite_items_favorite_id_index (`favorite_id`),
    `product_id` BIGINT UNSIGNED NOT NULL,
    KEY favorite_items_product_id_index (`product_id`),
    `sort_order` INTEGER NOT NULL DEFAULT 0,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    UNIQUE KEY favorite_items_favorite_id_product_id_unique (`favorite_id`, `product_id`),
    FOREIGN KEY (`favorite_id`) REFERENCES `favorites` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. record the migration (skipped automatically when the row is already there)
-- -----------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`, `applied_at`)
SELECT
    '2026_01_01_000018_create_favorites_tables.php',
    COALESCE((SELECT MAX(`batch`) FROM (SELECT `batch` FROM `migrations`) AS `b`), 0) + 1,
    NOW()
FROM (SELECT 1 AS `x`) AS `one`
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) AS `applied`
    WHERE `applied`.`migration` = '2026_01_01_000018_create_favorites_tables.php'
);

-- -----------------------------------------------------------------------------
-- 4. verification — the first query lists the two new tables, the second shows
--    the recorded migration row
-- -----------------------------------------------------------------------------
SHOW TABLES LIKE 'favorite%';

SELECT `migration`, `batch`, `applied_at`
FROM `migrations`
WHERE `migration` = '2026_01_01_000018_create_favorites_tables.php';
