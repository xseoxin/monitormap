<?php
/**
 * Validator Class
 *
 * Handles input validation and sanitization
 */

class Validator {

    /**
     * Validate email
     */
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate username
     */
    public static function username($username) {
        // 3-50 characters, alphanumeric and underscores
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username) === 1;
    }

    /**
     * Validate password
     */
    public static function password($password) {
        $config = require APP_ROOT . '/config/app.php';
        return strlen($password) >= $config['password_min_length'];
    }

    /**
     * Validate latitude
     */
    public static function latitude($lat) {
        return is_numeric($lat) && $lat >= -90 && $lat <= 90;
    }

    /**
     * Validate longitude
     */
    public static function longitude($lng) {
        return is_numeric($lng) && $lng >= -180 && $lng <= 180;
    }

    /**
     * Validate coordinates
     */
    public static function coordinates($lat, $lng) {
        return self::latitude($lat) && self::longitude($lng);
    }

    /**
     * Validate radius (1-50 km)
     */
    public static function radius($radius) {
        return is_numeric($radius) && $radius >= 1 && $radius <= 50;
    }

    /**
     * Validate grid size
     */
    public static function gridSize($gridSize) {
        return in_array($gridSize, ['5x5', '7x7', '9x9']);
    }

    /**
     * Validate phrase
     */
    public static function phrase($phrase) {
        return !empty(trim($phrase)) && strlen($phrase) <= 255;
    }

    /**
     * Validate priority (1-10)
     */
    public static function priority($priority) {
        return is_numeric($priority) && $priority >= 1 && $priority <= 10;
    }

    /**
     * Validate URL
     */
    public static function url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate proxy URL format
     */
    public static function proxyUrl($proxyUrl) {
        // Format: protocol://[user:pass@]host:port
        $pattern = '/^(https?|socks5):\/\/([^:@]+:[^@]+@)?[\w\.\-]+:\d+$/';
        return preg_match($pattern, $proxyUrl) === 1;
    }

    /**
     * Validate proxy type
     */
    public static function proxyType($type) {
        return in_array($type, ['http', 'https', 'socks5']);
    }

    /**
     * Validate date format
     */
    public static function date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate datetime format
     */
    public static function datetime($datetime, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $datetime);
        return $d && $d->format($format) === $datetime;
    }

    /**
     * Validate integer
     */
    public static function integer($value, $min = null, $max = null) {
        if (!is_numeric($value) || intval($value) != $value) {
            return false;
        }

        $value = intval($value);

        if ($min !== null && $value < $min) {
            return false;
        }

        if ($max !== null && $value > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validate positive integer
     */
    public static function positiveInteger($value) {
        return self::integer($value, 1);
    }

    /**
     * Validate rating (0-5)
     */
    public static function rating($rating) {
        return is_numeric($rating) && $rating >= 0 && $rating <= 5;
    }

    /**
     * Sanitize string
     */
    public static function sanitizeString($string) {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize filename
     */
    public static function sanitizeFilename($filename) {
        // Remove any path separators and dangerous characters
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        return $filename;
    }

    /**
     * Validate export format
     */
    public static function exportFormat($format) {
        return in_array(strtolower($format), ['csv', 'json', 'pdf']);
    }

    /**
     * Validate scan frequency
     */
    public static function scanFrequency($frequency) {
        return in_array($frequency, ['daily', 'weekly', 'monthly', 'manual']);
    }

    /**
     * Validate scan status
     */
    public static function scanStatus($status) {
        return in_array($status, ['pending', 'running', 'completed', 'failed']);
    }

    /**
     * Validate log level
     */
    public static function logLevel($level) {
        return in_array($level, ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL']);
    }

    /**
     * Validate log category
     */
    public static function logCategory($category) {
        return in_array($category, ['AUTH', 'SCRAPER', 'PROXY', 'API', 'CRON', 'SYSTEM', 'EXPORT', 'SECURITY']);
    }

    /**
     * Validate role
     */
    public static function role($role) {
        return in_array($role, ['admin', 'user']);
    }

    /**
     * Check if array contains only valid values
     */
    public static function inArray($value, array $validValues) {
        return in_array($value, $validValues);
    }

    /**
     * Validate phone number (basic)
     */
    public static function phone($phone) {
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        // Check if it contains only digits and + (for international)
        return preg_match('/^\+?\d{6,15}$/', $phone) === 1;
    }

    /**
     * Validate timezone
     */
    public static function timezone($timezone) {
        return in_array($timezone, DateTimeZone::listIdentifiers());
    }

    /**
     * Check if string is JSON
     */
    public static function isJson($string) {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate required fields
     */
    public static function required(array $data, array $requiredFields) {
        $errors = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[] = "Field '{$field}' is required";
            }
        }

        return empty($errors) ? true : $errors;
    }
}
