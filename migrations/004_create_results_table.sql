-- Migration 004: Create results table
-- Created: 2025-01-11

CREATE TABLE IF NOT EXISTS `results` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `phrase_id` INT UNSIGNED NOT NULL,
    `scan_id` INT UNSIGNED NOT NULL,
    `place_name` VARCHAR(255) NOT NULL,
    `address` VARCHAR(500) NULL,
    `rating` DECIMAL(2, 1) NULL,
    `reviews_count` INT UNSIGNED NULL DEFAULT 0,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `website` VARCHAR(500) NULL,
    `place_url` VARCHAR(1000) NULL,
    `grid_point_lat` DECIMAL(10, 8) NOT NULL,
    `grid_point_lng` DECIMAL(11, 8) NOT NULL,
    `found_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`phrase_id`) REFERENCES `phrases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`scan_id`) REFERENCES `scan_history`(`id`) ON DELETE CASCADE,
    INDEX `idx_phrase_id` (`phrase_id`),
    INDEX `idx_scan_id` (`scan_id`),
    INDEX `idx_rating` (`rating`),
    INDEX `idx_location` (`latitude`, `longitude`),
    INDEX `idx_grid_point` (`grid_point_lat`, `grid_point_lng`),
    INDEX `idx_found_at` (`found_at`),
    INDEX `idx_place_name` (`place_name`),
    FULLTEXT INDEX `ftx_place_name_address` (`place_name`, `address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
