-- Migration 002: Create phrases table
-- Created: 2025-01-11

CREATE TABLE IF NOT EXISTS `phrases` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `phrase` VARCHAR(255) NOT NULL,
    `location_lat` DECIMAL(10, 8) NOT NULL,
    `location_lng` DECIMAL(11, 8) NOT NULL,
    `radius_km` INT UNSIGNED NOT NULL DEFAULT 10,
    `grid_size` VARCHAR(10) NOT NULL DEFAULT '5x5',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `priority` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `scan_frequency` VARCHAR(20) NOT NULL DEFAULT 'weekly',
    `next_scan_at` TIMESTAMP NULL,
    `last_scanned_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_next_scan_at` (`next_scan_at`),
    INDEX `idx_priority` (`priority`),
    INDEX `idx_location` (`location_lat`, `location_lng`),
    INDEX `idx_last_scanned_at` (`last_scanned_at`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
