-- Migration 005: Create proxies table
-- Created: 2025-01-11

CREATE TABLE IF NOT EXISTS `proxies` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `proxy_url` VARCHAR(255) NOT NULL UNIQUE,
    `type` ENUM('http', 'https', 'socks5') NOT NULL DEFAULT 'http',
    `country` VARCHAR(50) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `response_time_ms` INT UNSIGNED NULL,
    `success_rate` DECIMAL(5, 2) NULL DEFAULT 100.00,
    `last_used_at` TIMESTAMP NULL,
    `last_checked_at` TIMESTAMP NULL,
    `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `total_requests` INT UNSIGNED NOT NULL DEFAULT 0,
    `successful_requests` INT UNSIGNED NOT NULL DEFAULT 0,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_type` (`type`),
    INDEX `idx_country` (`country`),
    INDEX `idx_response_time` (`response_time_ms`),
    INDEX `idx_success_rate` (`success_rate`),
    INDEX `idx_last_used_at` (`last_used_at`),
    INDEX `idx_last_checked_at` (`last_checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
