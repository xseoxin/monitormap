<?php
/**
 * Logger Class
 *
 * Handles logging to files and database
 */

class Logger {
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';

    private static $config = null;
    private static $levels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];

    /**
     * Initialize logger
     */
    private static function init() {
        if (self::$config === null) {
            self::$config = require APP_ROOT . '/config/logging.php';
        }
    }

    /**
     * Log debug message
     */
    public static function debug($message, $context = [], $category = 'SYSTEM') {
        self::log(self::DEBUG, $message, $context, $category);
    }

    /**
     * Log info message
     */
    public static function info($message, $context = [], $category = 'SYSTEM') {
        self::log(self::INFO, $message, $context, $category);
    }

    /**
     * Log warning message
     */
    public static function warning($message, $context = [], $category = 'SYSTEM') {
        self::log(self::WARNING, $message, $context, $category);
    }

    /**
     * Log error message
     */
    public static function error($message, $context = [], $category = 'SYSTEM') {
        self::log(self::ERROR, $message, $context, $category);
    }

    /**
     * Log critical message
     */
    public static function critical($message, $context = [], $category = 'SYSTEM') {
        self::log(self::CRITICAL, $message, $context, $category);
    }

    /**
     * Main logging method
     */
    private static function log($level, $message, $context = [], $category = 'SYSTEM') {
        self::init();

        // Check if this level should be logged
        if (self::$levels[$level] < self::$levels[self::$config['level']]) {
            return;
        }

        // Prepare log data
        $logData = [
            'datetime' => date(self::$config['date_format']),
            'level' => $level,
            'category' => $category,
            'message' => $message,
            'context' => !empty($context) ? json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}',
            'user' => self::getUserInfo(),
            'ip' => self::getIpAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'
        ];

        // Log to file
        self::logToFile($logData);

        // Log to database
        if (self::$config['log_to_database']) {
            self::logToDatabase($logData);
        }
    }

    /**
     * Log to file
     */
    private static function logToFile($logData) {
        try {
            // Determine log directory
            $logDir = self::$config['categories'][$logData['category']] ?? APP_ROOT . '/logs/app';

            // Create directory if not exists
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            // Log file path with date
            $logFile = $logDir . '/' . date('Y-m-d') . '.log';

            // Format message
            $message = str_replace(
                ['{datetime}', '{level}', '{category}', '{message}', '{context}', '{user}', '{ip}', '{user_agent}'],
                [$logData['datetime'], $logData['level'], $logData['category'], $logData['message'], $logData['context'], $logData['user'], $logData['ip'], $logData['user_agent']],
                self::$config['file_format']
            );

            // Write to file
            file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // If logging fails, write to PHP error log
            error_log("Logger failed: " . $e->getMessage());
        }
    }

    /**
     * Log to database
     */
    private static function logToDatabase($logData) {
        try {
            $db = Database::getInstance();

            $sql = "INSERT INTO logs (level, category, message, context, user_id, ip_address, user_agent, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

            $db->execute($sql, [
                $logData['level'],
                $logData['category'],
                $logData['message'],
                $logData['context'],
                $userId,
                $logData['ip'],
                $logData['user_agent'],
                $logData['datetime']
            ]);
        } catch (Exception $e) {
            // If database logging fails, don't break the application
            error_log("Database logging failed: " . $e->getMessage());
        }
    }

    /**
     * Get user info for logging
     */
    private static function getUserInfo() {
        if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
            return $_SESSION['email'] . ' (ID: ' . $_SESSION['user_id'] . ')';
        }
        return 'Guest';
    }

    /**
     * Get client IP address
     */
    private static function getIpAddress() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Clean old log files
     */
    public static function cleanup() {
        self::init();

        $deletedCount = 0;
        $compressedCount = 0;

        $logDirs = array_unique(array_values(self::$config['categories']));

        foreach ($logDirs as $logDir) {
            if (!is_dir($logDir)) continue;

            $files = glob($logDir . '/*.log');

            foreach ($files as $file) {
                $fileAge = (time() - filemtime($file)) / 86400; // days

                // Delete old files
                if ($fileAge > self::$config['max_files']) {
                    unlink($file);
                    $deletedCount++;
                }
                // Compress old files
                elseif ($fileAge > self::$config['compress_after_days'] && !file_exists($file . '.gz')) {
                    $gzFile = gzopen($file . '.gz', 'wb9');
                    gzwrite($gzFile, file_get_contents($file));
                    gzclose($gzFile);
                    unlink($file);
                    $compressedCount++;
                }
            }
        }

        return [
            'deleted' => $deletedCount,
            'compressed' => $compressedCount
        ];
    }
}
