#!/usr/bin/env php
<?php
/**
 * PHP Google Maps Scraper CLI
 *
 * Alternative to Node.js Puppeteer scraper
 * Works on any PHP hosting without Node.js/npm
 *
 * Usage: php scraper.php --phrase-id=1 --scan-id=1
 */

// Bootstrap
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/init.php';

// Parse command line arguments
$options = getopt('', ['phrase-id:', 'scan-id:', 'help']);

// Show help
if (isset($options['help']) || !isset($options['phrase-id']) || !isset($options['scan-id'])) {
    echo <<<HELP
PHP Google Maps Scraper
=======================

Usage:
  php scraper.php --phrase-id=<id> --scan-id=<id>

Options:
  --phrase-id=<id>   Phrase ID to scan
  --scan-id=<id>     Scan ID from scan_history table
  --help             Show this help message

Example:
  php scraper.php --phrase-id=1 --scan-id=1

Notes:
  - This is a PHP-based alternative to the Node.js Puppeteer scraper
  - Works on any shared hosting with PHP and cURL
  - No Node.js or npm installation required

HELP;
    exit(isset($options['help']) ? 0 : 1);
}

// Get parameters
$phraseId = (int)$options['phrase-id'];
$scanId = (int)$options['scan-id'];

if ($phraseId <= 0 || $scanId <= 0) {
    echo "Error: Invalid phrase-id or scan-id\n";
    exit(1);
}

// Log start
echo str_repeat('=', 60) . "\n";
echo "PHP Google Maps Scraper\n";
echo str_repeat('=', 60) . "\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n";
echo "Phrase ID: {$phraseId}\n";
echo "Scan ID: {$scanId}\n";
echo str_repeat('=', 60) . "\n\n";

try {
    // Create scraper instance
    $scraper = new PhpScraper();

    // Run scan
    $result = $scraper->runScan($phraseId, $scanId);

    // Display results
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Scan Results\n";
    echo str_repeat('=', 60) . "\n";

    if ($result['success']) {
        echo "✅ Status: COMPLETED\n";
        echo "📊 Total Results: {$result['total_results']}\n";
        echo "✅ Scanned Points: {$result['scanned_points']}\n";
        echo "❌ Failed Points: {$result['failed_points']}\n";
    } else {
        echo "❌ Status: FAILED\n";
        echo "Error: {$result['error']}\n";
    }

    echo str_repeat('=', 60) . "\n";
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 60) . "\n";

    exit($result['success'] ? 0 : 1);

} catch (Exception $e) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "❌ FATAL ERROR\n";
    echo str_repeat('=', 60) . "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo str_repeat('=', 60) . "\n";

    Logger::critical('PHP scraper fatal error', [
        'phrase_id' => $phraseId,
        'scan_id' => $scanId,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], 'SCRAPER');

    exit(1);
}
