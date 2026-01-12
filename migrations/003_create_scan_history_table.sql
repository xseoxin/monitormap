-- Migration 003: Create scan_history table
-- Created: 2025-01-11

CREATE TABLE IF NOT EXISTS `scan_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `phrase_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    `duration_seconds` INT UNSIGNED NULL,
    `total_grid_points` INT UNSIGNED NOT NULL DEFAULT 0,
    `scanned_points` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_points` INT UNSIGNED NOT NULL DEFAULT 0,
    `results_found` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`phrase_id`) REFERENCES `phrases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_phrase_id` (`phrase_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_started_at` (`started_at`),
    INDEX `idx_completed_at` (`completed_at`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
