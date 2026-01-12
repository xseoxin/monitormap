-- Migration 001: Create users table
-- Created: 2025-01-11

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `api_token` VARCHAR(64) NULL UNIQUE,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `timezone` VARCHAR(50) NOT NULL DEFAULT 'Europe/Warsaw',
    `max_phrases` INT UNSIGNED NOT NULL DEFAULT 50,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_api_token` (`api_token`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_last_login` (`last_login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
