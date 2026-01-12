<?php
/**
 * Application Constants
 *
 * Define global constants
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Application version
define('APP_VERSION', '1.0.0');

// Status constants
define('STATUS_PENDING', 'pending');
define('STATUS_RUNNING', 'running');
define('STATUS_COMPLETED', 'completed');
define('STATUS_FAILED', 'failed');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

// Log levels
define('LOG_DEBUG', 'DEBUG');
define('LOG_INFO', 'INFO');
define('LOG_WARNING', 'WARNING');
define('LOG_ERROR', 'ERROR');
define('LOG_CRITICAL', 'CRITICAL');

// Log categories
define('LOG_AUTH', 'AUTH');
define('LOG_SCRAPER', 'SCRAPER');
define('LOG_PROXY', 'PROXY');
define('LOG_API', 'API');
define('LOG_CRON', 'CRON');
define('LOG_SYSTEM', 'SYSTEM');
define('LOG_EXPORT', 'EXPORT');
define('LOG_SECURITY', 'SECURITY');

// Proxy types
define('PROXY_HTTP', 'http');
define('PROXY_HTTPS', 'https');
define('PROXY_SOCKS5', 'socks5');

// Scan frequencies
define('FREQ_DAILY', 'daily');
define('FREQ_WEEKLY', 'weekly');
define('FREQ_MONTHLY', 'monthly');
define('FREQ_MANUAL', 'manual');

// Export formats
define('EXPORT_CSV', 'csv');
define('EXPORT_JSON', 'json');
define('EXPORT_PDF', 'pdf');

// Grid sizes
define('GRID_5X5', '5x5');
define('GRID_7X7', '7x7');
define('GRID_9X9', '9x9');

// Date formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('TIME_FORMAT', 'H:i:s');

// Pagination
define('DEFAULT_PAGE_SIZE', 50);
define('MAX_PAGE_SIZE', 100);

// File upload limits
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
define('MAX_IMPORT_ROWS', 1000);

// Success messages
define('MSG_SUCCESS_LOGIN', 'Logged in successfully');
define('MSG_SUCCESS_LOGOUT', 'Logged out successfully');
define('MSG_SUCCESS_REGISTER', 'Registration successful');
define('MSG_SUCCESS_PHRASE_ADDED', 'Phrase added successfully');
define('MSG_SUCCESS_PHRASE_UPDATED', 'Phrase updated successfully');
define('MSG_SUCCESS_PHRASE_DELETED', 'Phrase deleted successfully');
define('MSG_SUCCESS_PROXY_ADDED', 'Proxy added successfully');
define('MSG_SUCCESS_PROXY_UPDATED', 'Proxy updated successfully');
define('MSG_SUCCESS_PROXY_DELETED', 'Proxy deleted successfully');
define('MSG_SUCCESS_SCAN_STARTED', 'Scan started successfully');

// Error messages
define('MSG_ERROR_LOGIN', 'Invalid username or password');
define('MSG_ERROR_REGISTER', 'Registration failed');
define('MSG_ERROR_UNAUTHORIZED', 'Unauthorized access');
define('MSG_ERROR_CSRF', 'Invalid CSRF token');
define('MSG_ERROR_VALIDATION', 'Validation failed');
define('MSG_ERROR_DATABASE', 'Database error occurred');
define('MSG_ERROR_NOT_FOUND', 'Resource not found');
define('MSG_ERROR_RATE_LIMIT', 'Rate limit exceeded');
define('MSG_ERROR_PHRASE_LIMIT', 'Phrase limit reached');

// API error codes
define('API_ERROR_UNAUTHORIZED', 401);
define('API_ERROR_FORBIDDEN', 403);
define('API_ERROR_NOT_FOUND', 404);
define('API_ERROR_VALIDATION', 422);
define('API_ERROR_RATE_LIMIT', 429);
define('API_ERROR_SERVER', 500);
