<?php
/**
 * Application Configuration
 *
 * Main application settings
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

return [
    // Application
    'name' => getenv('APP_NAME') ?: 'Google Maps Monitor',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN) ?: false,
    'timezone' => getenv('TIMEZONE') ?: 'Europe/Warsaw',

    // Security
    'session_lifetime' => (int)(getenv('SESSION_LIFETIME') ?: 7200), // 2 hours
    'password_min_length' => (int)(getenv('PASSWORD_MIN_LENGTH') ?: 8),
    'max_login_attempts' => (int)(getenv('MAX_LOGIN_ATTEMPTS') ?: 5),
    'login_timeout' => (int)(getenv('LOGIN_TIMEOUT') ?: 900), // 15 minutes
    'csrf_token_name' => 'csrf_token',
    'csrf_token_expire' => 3600, // 1 hour

    // Rate Limiting
    'rate_limit_api' => 100, // requests per hour
    'rate_limit_ajax' => 60, // requests per minute
    'rate_limit_scan_manual' => 5, // scans per day

    // Limits
    'max_phrases_per_user' => (int)(getenv('MAX_PHRASES_PER_USER') ?: 50),
    'max_results_per_scan' => (int)(getenv('MAX_RESULTS_PER_SCAN') ?: 1000),
    'results_retention_days' => (int)(getenv('RESULTS_RETENTION_DAYS') ?: 180),

    // Pagination
    'results_per_page' => 50,
    'max_results_per_page' => 100,

    // Paths
    'storage_path' => APP_ROOT . '/storage',
    'logs_path' => APP_ROOT . '/logs',
    'exports_path' => APP_ROOT . '/storage/exports',
    'screenshots_path' => APP_ROOT . '/storage/screenshots',
    'temp_path' => APP_ROOT . '/storage/temp',
];
