<?php
/**
 * Config Class
 *
 * Centralized configuration management
 */

class Config {
    private static $config = [];

    /**
     * Load configuration file
     */
    public static function load($file) {
        if (!isset(self::$config[$file])) {
            $configPath = APP_ROOT . '/config/' . $file . '.php';
            if (file_exists($configPath)) {
                self::$config[$file] = require $configPath;
            } else {
                throw new Exception("Configuration file not found: {$file}");
            }
        }
        return self::$config[$file];
    }

    /**
     * Get configuration value
     */
    public static function get($key, $default = null, $file = 'app') {
        $config = self::load($file);

        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $config;

            foreach ($keys as $k) {
                if (!isset($value[$k])) {
                    return $default;
                }
                $value = $value[$k];
            }

            return $value;
        }

        return $config[$key] ?? $default;
    }

    /**
     * Get all configuration
     */
    public static function all($file = 'app') {
        return self::load($file);
    }
}
