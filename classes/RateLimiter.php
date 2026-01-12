<?php
/**
 * RateLimiter Class
 *
 * Handles rate limiting for API, AJAX, and manual scans
 */

class RateLimiter {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Check if request is allowed
     *
     * @param string $key Unique key for the rate limit (e.g., 'api:user_id:123')
     * @param int $maxRequests Maximum number of requests allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if request is allowed
     */
    public function check($key, $maxRequests, $timeWindow) {
        $cacheFile = $this->getCacheFile($key);
        $now = time();

        // Load existing data
        $data = $this->loadData($cacheFile);

        // Clean old requests outside time window
        $data = array_filter($data, function($timestamp) use ($now, $timeWindow) {
            return $timestamp > ($now - $timeWindow);
        });

        // Check if limit exceeded
        if (count($data) >= $maxRequests) {
            Logger::debug('Rate limit exceeded', [
                'key' => $key,
                'requests' => count($data),
                'max_requests' => $maxRequests,
                'time_window' => $timeWindow
            ], 'SECURITY');

            return false;
        }

        // Add current request
        $data[] = $now;

        // Save data
        $this->saveData($cacheFile, $data);

        return true;
    }

    /**
     * Check API rate limit
     *
     * @param string $apiToken API token
     * @return bool True if request is allowed
     */
    public function checkApi($apiToken) {
        $config = Config::load('app');
        $maxRequests = $config['rate_limit_api'];
        $timeWindow = 3600; // 1 hour

        $key = 'api:' . md5($apiToken);

        return $this->check($key, $maxRequests, $timeWindow);
    }

    /**
     * Check AJAX rate limit
     *
     * @param string $sessionId Session ID
     * @return bool True if request is allowed
     */
    public function checkAjax($sessionId) {
        $config = Config::load('app');
        $maxRequests = $config['rate_limit_ajax'];
        $timeWindow = 60; // 1 minute

        $key = 'ajax:' . md5($sessionId);

        return $this->check($key, $maxRequests, $timeWindow);
    }

    /**
     * Check manual scan rate limit
     *
     * @param int $userId User ID
     * @return bool True if request is allowed
     */
    public function checkManualScan($userId) {
        $config = Config::load('app');
        $maxRequests = $config['rate_limit_scan_manual'];
        $timeWindow = 86400; // 1 day

        $key = 'scan:' . $userId;

        return $this->check($key, $maxRequests, $timeWindow);
    }

    /**
     * Get remaining requests
     *
     * @param string $key Unique key for the rate limit
     * @param int $maxRequests Maximum number of requests allowed
     * @param int $timeWindow Time window in seconds
     * @return int Number of remaining requests
     */
    public function getRemaining($key, $maxRequests, $timeWindow) {
        $cacheFile = $this->getCacheFile($key);
        $now = time();

        $data = $this->loadData($cacheFile);

        // Clean old requests
        $data = array_filter($data, function($timestamp) use ($now, $timeWindow) {
            return $timestamp > ($now - $timeWindow);
        });

        return max(0, $maxRequests - count($data));
    }

    /**
     * Get time until rate limit resets
     *
     * @param string $key Unique key for the rate limit
     * @param int $timeWindow Time window in seconds
     * @return int Seconds until reset
     */
    public function getResetTime($key, $timeWindow) {
        $cacheFile = $this->getCacheFile($key);
        $now = time();

        $data = $this->loadData($cacheFile);

        if (empty($data)) {
            return 0;
        }

        $oldestRequest = min($data);
        $resetTime = $oldestRequest + $timeWindow - $now;

        return max(0, $resetTime);
    }

    /**
     * Reset rate limit for a key
     *
     * @param string $key Unique key for the rate limit
     */
    public function reset($key) {
        $cacheFile = $this->getCacheFile($key);

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        Logger::debug('Rate limit reset', ['key' => $key], 'SYSTEM');
    }

    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        $cacheDir = APP_ROOT . '/storage/temp/ratelimit';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        return $cacheDir . '/' . md5($key) . '.json';
    }

    /**
     * Load data from cache file
     */
    private function loadData($cacheFile) {
        if (!file_exists($cacheFile)) {
            return [];
        }

        $content = file_get_contents($cacheFile);
        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Save data to cache file
     */
    private function saveData($cacheFile, $data) {
        file_put_contents($cacheFile, json_encode($data), LOCK_EX);
    }

    /**
     * Clean old rate limit files
     */
    public static function cleanup() {
        $cacheDir = APP_ROOT . '/storage/temp/ratelimit';

        if (!is_dir($cacheDir)) {
            return 0;
        }

        $files = glob($cacheDir . '/*.json');
        $deleted = 0;
        $now = time();

        foreach ($files as $file) {
            // Delete files older than 7 days
            if ($now - filemtime($file) > 604800) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get rate limit info
     *
     * @param string $key Unique key for the rate limit
     * @param int $maxRequests Maximum number of requests allowed
     * @param int $timeWindow Time window in seconds
     * @return array Rate limit information
     */
    public function getInfo($key, $maxRequests, $timeWindow) {
        $cacheFile = $this->getCacheFile($key);
        $now = time();

        $data = $this->loadData($cacheFile);

        // Clean old requests
        $data = array_filter($data, function($timestamp) use ($now, $timeWindow) {
            return $timestamp > ($now - $timeWindow);
        });

        $used = count($data);
        $remaining = max(0, $maxRequests - $used);
        $resetTime = empty($data) ? 0 : min($data) + $timeWindow - $now;

        return [
            'limit' => $maxRequests,
            'used' => $used,
            'remaining' => $remaining,
            'reset_in' => max(0, $resetTime),
            'time_window' => $timeWindow
        ];
    }
}
