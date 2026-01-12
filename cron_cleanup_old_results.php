<?php
/**
 * Cron Job: Cleanup Old Results
 *
 * Delete old scan results based on retention policy
 * Run this script via cron: 0 4 * * 0 /usr/bin/php /path/to/cron_cleanup_old_results.php (weekly)
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

Logger::info('Results cleanup cron started', [], 'CRON');

try {
    $resultModel = new Result();
    $scanHistory = new ScanHistory();

    // Get retention days from config
    $retentionDays = Config::get('results_retention_days', 180, 'app');

    // Delete old results
    $deletedResults = $resultModel->deleteOld($retentionDays);

    Logger::info('Old results deleted', [
        'deleted_count' => $deletedResults,
        'retention_days' => $retentionDays
    ], 'CRON');

    // Delete old scan history (older than 90 days)
    $deletedScans = $scanHistory->deleteOld(90);

    Logger::info('Old scan history deleted', [
        'deleted_count' => $deletedScans
    ], 'CRON');

    // Optimize tables
    $db = Database::getInstance();
    $db->execute('OPTIMIZE TABLE results');
    $db->execute('OPTIMIZE TABLE scan_history');

    Logger::info('Database tables optimized', [], 'CRON');

} catch (Exception $e) {
    Logger::critical('Results cleanup cron failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 'CRON');
}
