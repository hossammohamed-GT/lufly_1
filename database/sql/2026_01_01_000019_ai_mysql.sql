-- =============================================================================
-- LUFLY — AI foundation — MySQL patch
-- -----------------------------------------------------------------------------
-- The database side of the AI features (Google Gemini key pool), for a database
-- that is already running (production / phpMyAdmin / XAMPP) instead of a fresh
-- import:
--
--   * ai_cache  — answers already paid for, so the same question is never asked
--                 twice (the single biggest saving on a free quota)
--   * ai_usage  — one row per call: which key answered, tokens, latency, errors
--                 (feeds the per-visitor daily limit and the admin overview)
--   * one row in `migrations` so the application knows migration 19 has run
--
-- How to use it
--   1. phpMyAdmin -> select the `lufly` database -> SQL tab
--   2. paste this whole file -> Go
--   3. run the verification query at the bottom
--
-- Alternative: `php cli migrate` does the same thing by itself.
-- Safe to run twice: IF NOT EXISTS plus a guarded migrations insert.
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. cached AI answers
--    scope + fingerprint is the lookup key (fingerprint = sha256 of the
--    question and its inputs, including the bytes of an uploaded picture).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_cache` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `scope` VARCHAR(50) NOT NULL,
    `fingerprint` VARCHAR(100) NOT NULL,
    `payload` JSON NOT NULL,
    `model` VARCHAR(100) NULL,
    `hits` INTEGER NOT NULL DEFAULT 0,
    `expires_at` DATETIME NULL,
    KEY ai_cache_expires_at_index (`expires_at`),
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    UNIQUE KEY ai_cache_scope_fingerprint_unique (`scope`, `fingerprint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. usage log (one row per AI call)
--    The visitor IP is stored hashed, never in the clear.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_usage` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `scope` VARCHAR(50) NOT NULL,
    `ip_hash` VARCHAR(100) NOT NULL,
    KEY ai_usage_ip_hash_index (`ip_hash`),
    `key_slot` INTEGER NOT NULL DEFAULT 0,
    `model` VARCHAR(100) NULL,
    `prompt_tokens` INTEGER NOT NULL DEFAULT 0,
    `output_tokens` INTEGER NOT NULL DEFAULT 0,
    `duration_ms` INTEGER NOT NULL DEFAULT 0,
    `ok` TINYINT(1) NOT NULL DEFAULT 1,
    `day` DATE NOT NULL,
    KEY ai_usage_day_index (`day`),
    `error` VARCHAR(255) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. record the migration (skipped when the row is already there)
-- -----------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`, `applied_at`)
SELECT
    '2026_01_01_000019_create_ai_tables.php',
    COALESCE((SELECT MAX(`batch`) FROM (SELECT `batch` FROM `migrations`) AS `b`), 0) + 1,
    NOW()
FROM (SELECT 1 AS `x`) AS `one`
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) AS `applied`
    WHERE `applied`.`migration` = '2026_01_01_000019_create_ai_tables.php'
);

-- -----------------------------------------------------------------------------
-- 4. verification — both tables must be listed
-- -----------------------------------------------------------------------------
SHOW TABLES LIKE 'ai\_%';

SELECT `migration`, `batch`, `applied_at`
FROM `migrations`
WHERE `migration` = '2026_01_01_000019_create_ai_tables.php';
