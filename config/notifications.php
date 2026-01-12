<?php
/**
 * Notifications Configuration
 *
 * Email and Telegram notification settings
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

return [
    // Email settings
    'email' => [
        'enabled' => !empty(getenv('SMTP_HOST')),
        'smtp_host' => getenv('SMTP_HOST') ?: '',
        'smtp_port' => (int)(getenv('SMTP_PORT') ?: 587),
        'smtp_user' => getenv('SMTP_USER') ?: '',
        'smtp_pass' => getenv('SMTP_PASS') ?: '',
        'smtp_encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls', // tls or ssl
        'from_email' => getenv('SMTP_FROM') ?: 'noreply@example.com',
        'from_name' => getenv('APP_NAME') ?: 'Google Maps Monitor',
    ],

    // Telegram settings
    'telegram' => [
        'enabled' => !empty(getenv('TELEGRAM_BOT_TOKEN')),
        'bot_token' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
        'chat_id' => getenv('TELEGRAM_CHAT_ID') ?: '',
    ],

    // Notification triggers
    'notify_on' => [
        'scan_completed' => true,
        'scan_failed' => true,
        'proxy_deactivated' => false,
        'new_user_registered' => true,
        'system_error' => true,
    ],

    // Admin email addresses (comma-separated in .env)
    'admin_emails' => array_filter(array_map('trim', explode(',', getenv('ADMIN_EMAILS') ?: ''))),
];
