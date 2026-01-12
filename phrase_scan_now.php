<?php
/**
 * Scan Phrase Now
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentUser = Auth::user();
$userId = $currentUser['id'];

// Validate CSRF token
validateCsrf();

$phraseId = (int)input('id');

// Check rate limiting
$rateLimiter = new RateLimiter();
if (!$rateLimiter->checkManualScan($userId)) {
    setFlash('error', MSG_ERROR_RATE_LIMIT);
    redirect('/phrases.php');
}

try {
    $phraseModel = new Phrase();
    $phrase = $phraseModel->getById($phraseId, $userId);

    if (!$phrase) {
        setFlash('error', 'Phrase not found');
        redirect('/phrases.php');
    }

    // Generate grid points
    $gridPoints = GridGenerator::generate(
        $phrase['location_lat'],
        $phrase['location_lng'],
        $phrase['radius_km'],
        $phrase['grid_size']
    );

    // Create scan history record
    $scanHistory = new ScanHistory();
    $scanId = $scanHistory->create($phraseId, $userId, count($gridPoints));

    // Get scraper config
    $scraperConfig = Config::load('scraper');
    $nodePath = $scraperConfig['node_path'];
    $scraperPath = $scraperConfig['scraper_path'];

    // Execute scraper in background
    $command = sprintf(
        '%s %s --phrase-id=%d --scan-id=%d > /dev/null 2>&1 &',
        escapeshellarg($nodePath),
        escapeshellarg($scraperPath),
        $phraseId,
        $scanId
    );

    exec($command);

    Logger::info('Manual scan initiated', [
        'phrase_id' => $phraseId,
        'scan_id' => $scanId,
        'user_id' => $userId
    ], 'SCRAPER');

    setFlash('success', MSG_SUCCESS_SCAN_STARTED);
    redirect('/history_details.php?id=' . $scanId);

} catch (Exception $e) {
    Logger::error('Failed to start scan', [
        'phrase_id' => $phraseId,
        'error' => $e->getMessage()
    ], 'SCRAPER');

    setFlash('error', 'Failed to start scan: ' . $e->getMessage());
    redirect('/phrases.php');
}
