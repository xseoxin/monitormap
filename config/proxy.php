<?php
/**
 * Proxy Configuration
 *
 * Proxy management settings
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

return [
    // Proxy rotation strategy: 'round-robin', 'random', 'fastest'
    'rotation_strategy' => getenv('PROXY_ROTATION') ?: 'round-robin',

    // Health check interval in seconds
    'health_check_interval' => (int)(getenv('PROXY_HEALTH_CHECK_INTERVAL') ?: 3600), // 1 hour

    // Maximum failed attempts before deactivation
    'max_failed_attempts' => (int)(getenv('PROXY_MAX_FAILED_ATTEMPTS') ?: 3),

    // Proxy timeout in milliseconds
    'timeout' => (int)(getenv('PROXY_TIMEOUT') ?: 10000), // 10 seconds

    // Test URL for proxy health checks
    'test_url' => 'https://www.google.com/maps',

    // Proxy types
    'allowed_types' => ['http', 'https', 'socks5'],

    // Success rate threshold (below this, proxy gets warnings)
    'success_rate_threshold' => 70.0, // percentage

    // Response time threshold in ms (above this, proxy is considered slow)
    'response_time_threshold' => 5000, // 5 seconds
];
