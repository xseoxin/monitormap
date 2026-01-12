<?php
/**
 * Cron Job: Weekly Scan
 *
 * Automatically scan phrases that are due for scanning
 * Run this script via cron: 0 2 * * * /usr/bin/php /path/to/cron_weekly_scan.php
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

Logger::info('Weekly scan cron started', [], 'CRON');

try {
    $phraseModel = new Phrase();
    $scanHistory = new ScanHistory();

    // Get phrases ready for scanning
    $phrases = $phraseModel->getReadyForScan();

    Logger::info('Found phrases ready for scan', ['count' => count($phrases)], 'CRON');

    $scannedCount = 0;
    $failedCount = 0;

    foreach ($phrases as $phrase) {
        try {
            // Generate grid points
            $gridPoints = GridGenerator::generate(
                $phrase['location_lat'],
                $phrase['location_lng'],
                $phrase['radius_km'],
                $phrase['grid_size']
            );

            // Create scan history record
            $scanId = $scanHistory->create($phrase['id'], $phrase['user_id'], count($gridPoints));

            Logger::info('Starting scan for phrase', [
                'phrase_id' => $phrase['id'],
                'scan_id' => $scanId,
                'phrase' => $phrase['phrase']
            ], 'CRON');

            // Get scraper config
            $scraperConfig = Config::load('scraper');
            $nodePath = $scraperConfig['node_path'];
            $scraperPath = $scraperConfig['scraper_path'];

            // Execute scraper in background
            $command = sprintf(
                '%s %s --phrase-id=%d --scan-id=%d > /dev/null 2>&1 &',
                escapeshellarg($nodePath),
                escapeshellarg($scraperPath),
                $phrase['id'],
                $scanId
            );

            exec($command);

            // Update phrase next scan time
            $phraseModel->updateLastScanned($phrase['id']);

            $scannedCount++;

            Logger::info('Scan initiated', [
                'phrase_id' => $phrase['id'],
                'scan_id' => $scanId
            ], 'CRON');

            // Small delay between scans
            sleep(2);

        } catch (Exception $e) {
            $failedCount++;
            Logger::error('Failed to initiate scan', [
                'phrase_id' => $phrase['id'],
                'error' => $e->getMessage()
            ], 'CRON');
        }
    }

    Logger::info('Weekly scan cron completed', [
        'scanned' => $scannedCount,
        'failed' => $failedCount
    ], 'CRON');

    // Send summary email to admins
    if ($scannedCount > 0) {
        $notification = new Notification();
        $notification->send('scan_completed', [
            'scans_initiated' => $scannedCount,
            'scans_failed' => $failedCount,
            'time' => date('Y-m-d H:i:s')
        ]);
    }

} catch (Exception $e) {
    Logger::critical('Weekly scan cron failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 'CRON');
}
