-- =============================================================================
-- LUFLY — finder (assistant) — MySQL patch
-- -----------------------------------------------------------------------------
-- The customers the chat collects: "I am looking for something like this",
-- asked in words or with a photo.
--
--   * assistant_leads — one row per request: the visitor's words, the photo
--                       (path + the public link the shop opens from the mail),
--                       the address to answer on, the model's one-line summary
--                       and whether the team was already told
--   * one row in `migrations` so the application knows migration 20 has run
--
-- How to use it
--   1. phpMyAdmin -> select the `lufly` database -> SQL tab
--   2. paste this whole file -> Go
--   3. run the verification query at the bottom
--
-- Alternative: `php cli migrate` does the same thing by itself.
-- Safe to run twice: IF NOT EXISTS plus a guarded migrations insert.
--
-- The default on `email` is the mailbox the shop reads: a visitor who skips the
-- field is still stored with an address the team can answer, and
-- ASSISTANT_LEAD_DEFAULT changes it without touching this file.
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `assistant_leads` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `token` VARCHAR(64) NOT NULL,
    UNIQUE KEY assistant_leads_token_unique (`token`),
    `ip_hash` VARCHAR(100) NOT NULL,
    KEY assistant_leads_ip_hash_index (`ip_hash`),
    `locale` VARCHAR(10) NOT NULL DEFAULT 'en',
    `email` VARCHAR(190) NOT NULL DEFAULT 'hossam545mohamed@gmail.com',
    `message` LONGTEXT NULL,
    `product_id` INTEGER NOT NULL DEFAULT 0,
    `image_path` VARCHAR(255) NULL,
    `image_url` VARCHAR(255) NULL,
    `image_name` VARCHAR(255) NULL,
    `image_mime` VARCHAR(100) NULL,
    `image_kb` INTEGER NOT NULL DEFAULT 0,
    `summary` VARCHAR(255) NULL,
    `terms` VARCHAR(255) NULL,
    `category` VARCHAR(255) NULL,
    `results_count` INTEGER NOT NULL DEFAULT 0,
    `source` VARCHAR(20) NOT NULL DEFAULT 'text',
    `status` VARCHAR(20) NOT NULL DEFAULT 'new',
    KEY assistant_leads_status_index (`status`),
    `notified_at` DATETIME NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- the application must know this migration ran, or `php cli migrate` runs it again
INSERT INTO `migrations` (`migration`, `batch`, `applied_at`)
SELECT
    '2026_01_01_000020_create_assistant_leads_table.php',
    COALESCE((SELECT MAX(`batch`) FROM (SELECT `batch` FROM `migrations`) AS `b`), 0) + 1,
    NOW()
FROM (SELECT 1 AS `x`) AS `one`
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) AS `applied`
    WHERE `applied`.`migration` = '2026_01_01_000020_create_assistant_leads_table.php'
);

-- verification
SELECT COUNT(*) AS assistant_leads_exists FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'assistant_leads';
