-- Migration 006: Create logs table
-- Created: 2025-01-11

CREATE TABLE IF NOT EXISTS `logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level` ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL') NOT NULL DEFAULT 'INFO',
    `category` ENUM('AUTH', 'SCRAPER', 'PROXY', 'API', 'CRON', 'SYSTEM', 'EXPORT', 'SECURITY') NOT NULL DEFAULT 'SYSTEM',
    `message` TEXT NOT NULL,
    `context` JSON NULL,
    `user_id` INT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_level` (`level`),
    INDEX `idx_category` (`category`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_level_category` (`level`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
