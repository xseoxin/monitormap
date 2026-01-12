<?php
/**
 * Logging Configuration
 *
 * System logging settings
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

return [
    // Log level: DEBUG, INFO, WARNING, ERROR, CRITICAL
    'level' => getenv('LOG_LEVEL') ?: 'INFO',

    // Log to database in addition to files
    'log_to_database' => true,

    // Maximum number of log files to keep
    'max_files' => (int)(getenv('LOG_MAX_FILES') ?: 90),

    // Compress log files after X days
    'compress_after_days' => (int)(getenv('LOG_COMPRESS_AFTER') ?: 7),

    // Log file format
    'date_format' => 'Y-m-d H:i:s',
    'file_format' => '[{datetime}] [{level}] [{category}] {message}' . PHP_EOL . 'Context: {context}' . PHP_EOL . 'User: {user}' . PHP_EOL . 'IP: {ip}' . PHP_EOL . 'User-Agent: {user_agent}' . PHP_EOL . PHP_EOL,

    // Log categories
    'categories' => [
        'AUTH' => APP_ROOT . '/logs/auth',
        'SCRAPER' => APP_ROOT . '/logs/scraper',
        'PROXY' => APP_ROOT . '/logs/proxy',
        'API' => APP_ROOT . '/logs/api',
        'CRON' => APP_ROOT . '/logs/cron',
        'SYSTEM' => APP_ROOT . '/logs/app',
        'EXPORT' => APP_ROOT . '/logs/app',
        'SECURITY' => APP_ROOT . '/logs/errors',
    ],

    // What to log
    'log_queries' => false, // Set to true for debugging slow queries
    'log_api_requests' => true,
    'log_auth_attempts' => true,
    'log_scraper_details' => true,
    'log_proxy_rotation' => false, // Can be very verbose

    // Slow query threshold in seconds
    'slow_query_threshold' => 1.0,
];
