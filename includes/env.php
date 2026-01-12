<?php
/**
 * Environment Variable Helper
 *
 * Works even when putenv() is disabled by hosting sandbox
 */

if (!function_exists('env')) {
    /**
     * Get environment variable from multiple sources
     *
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function env($key, $default = null) {
        // Try $_ENV first (set by install.php and init.php)
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        // Try $_SERVER
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        // Try getenv() as fallback (works if putenv() wasn't disabled)
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        return $default;
    }
}
