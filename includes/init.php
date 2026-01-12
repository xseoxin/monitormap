<?php
/**
 * Initialization File
 *
 * Bootstrap the application
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Load environment variables from .env file
if (file_exists(APP_ROOT . '/.env')) {
    $lines = file(APP_ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            if (!array_key_exists($name, $_ENV) && !array_key_exists($name, $_SERVER)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Error reporting
$debug = filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN);
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Set timezone
date_default_timezone_set(getenv('TIMEZONE') ?: 'Europe/Warsaw');

// Autoload classes
spl_autoload_register(function ($className) {
    $classFile = APP_ROOT . '/classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Load constants
require_once __DIR__ . '/constants.php';

// Load helper functions
require_once __DIR__ . '/functions.php';

// Check session activity for logged in users
if (Auth::check()) {
    $config = Config::load('app');
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    $timeout = $config['session_lifetime'];

    if (time() - $lastActivity > $timeout) {
        // Session expired
        $auth = new Auth();
        $auth->logout();
        header('Location: /login.php?expired=1');
        exit;
    }

    $_SESSION['last_activity'] = time();

    // Check IP address change (security)
    $currentIp = $_SERVER['REMOTE_ADDR'];
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $currentIp) {
        Logger::warning('IP address changed during session', [
            'old_ip' => $_SESSION['ip_address'],
            'new_ip' => $currentIp
        ], 'SECURITY');

        // Optionally logout user
        // $auth = new Auth();
        // $auth->logout();
        // header('Location: /login.php?security=1');
        // exit;
    }
}
