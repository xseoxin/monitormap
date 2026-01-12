<?php
/**
 * Cron Job: Proxy Health Check
 *
 * Check health of all active proxies
 * Run this script via cron: 0 * * * * /usr/bin/php /path/to/cron_proxy_health.php
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

Logger::info('Proxy health check cron started', [], 'CRON');

try {
    $proxyManager = new ProxyManager();

    // Run health check on all active proxies
    $results = $proxyManager->healthCheckAll();

    Logger::info('Proxy health check completed', $results, 'CRON');

    // Send notification if many proxies failed
    if ($results['deactivated'] > 0) {
        $notification = new Notification();
        $notification->send('proxy_deactivated', [
            'deactivated_count' => $results['deactivated'],
            'total_tested' => $results['tested'],
            'time' => date('Y-m-d H:i:s')
        ]);
    }

} catch (Exception $e) {
    Logger::critical('Proxy health check cron failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 'CRON');
}
