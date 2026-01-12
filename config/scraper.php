<?php
/**
 * Scraper Configuration
 *
 * Configuration for both Node.js and PHP scrapers
 * Supports auto-detection and manual selection
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

return [
    // Scraper type: 'auto', 'nodejs', 'php'
    // - auto: Automatically detects available scraper (Node.js preferred, PHP fallback)
    // - nodejs: Force use of Node.js Puppeteer scraper (requires Node.js + npm)
    // - php: Force use of PHP cURL scraper (no dependencies, works on shared hosting)
    'type' => env('SCRAPER_TYPE', 'auto'),

    // Node.js Scraper Configuration
    'nodejs' => [
        'scraper_path' => env('SCRAPER_PATH', APP_ROOT . '/scraper/scraper.js'),
        'node_path' => env('NODE_PATH', '/usr/bin/node'),
        'headless' => filter_var(env('SCRAPER_HEADLESS', 'true'), FILTER_VALIDATE_BOOLEAN),
    ],

    // PHP Scraper Configuration
    'php' => [
        'scraper_path' => env('SCRAPER_PHP_PATH', APP_ROOT . '/scraper/scraper.php'),
    ],

    // Common Settings (both scrapers)
    'timeout' => (int)env('SCRAPER_TIMEOUT', 300), // 5 minutes per scan
    'page_timeout' => 10000, // 10 seconds per page load
    'max_retries' => (int)env('MAX_RETRIES', 3),
    'retry_delays' => [5000, 15000, 30000], // milliseconds: 5s, 15s, 30s
    'request_delay_min' => (int)env('REQUEST_DELAY_MIN', 2000), // 2 seconds
    'request_delay_max' => (int)env('REQUEST_DELAY_MAX', 5000), // 5 seconds
    'max_results_per_point' => (int)env('MAX_RESULTS_PER_POINT', 20),
    'max_concurrent_browsers' => 1, // Keep low to avoid detection

    // Screenshot on error (Node.js only)
    'screenshot_on_error' => true,
    'screenshot_path' => APP_ROOT . '/storage/screenshots',

    // Google Maps settings
    'maps_url' => 'https://www.google.com/maps/search/{phrase}/@{lat},{lng},15z',
    'maps_selector_results' => 'div[role="feed"] > div > a',
    'maps_wait_timeout' => 10000, // 10 seconds

    // Anti-detection
    'user_agents' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ],
    'viewports' => [
        ['width' => 1920, 'height' => 1080],
        ['width' => 1366, 'height' => 768],
        ['width' => 1536, 'height' => 864],
        ['width' => 1440, 'height' => 900],
    ],
];
