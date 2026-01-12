<?php
/**
 * Cron Job: Cleanup Old Logs
 *
 * Compress and delete old log files
 * Run this script via cron: 0 3 * * * /usr/bin/php /path/to/cron_cleanup_logs.php
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

Logger::info('Log cleanup cron started', [], 'CRON');

try {
    // Clean up log files
    $result = Logger::cleanup();

    Logger::info('Log cleanup completed', [
        'deleted' => $result['deleted'],
        'compressed' => $result['compressed']
    ], 'CRON');

    // Clean up rate limiter cache
    $rateLimiterDeleted = RateLimiter::cleanup();

    Logger::info('Rate limiter cleanup completed', [
        'deleted' => $rateLimiterDeleted
    ], 'CRON');

} catch (Exception $e) {
    Logger::critical('Log cleanup cron failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 'CRON');
}
