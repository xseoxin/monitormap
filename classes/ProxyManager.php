<?php
/**
 * ProxyManager Class
 *
 * Manages proxy rotation and health checking
 */

class ProxyManager {
    private $db;
    private $proxy;
    private $config;
    private $currentIndex = 0;
    private $proxies = [];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->proxy = new Proxy();
        $this->config = Config::load('proxy');
        $this->loadProxies();
    }

    /**
     * Load active proxies
     */
    private function loadProxies() {
        $this->proxies = $this->proxy->getActive();

        if (empty($this->proxies)) {
            Logger::warning('No active proxies available', [], 'PROXY');
        } else {
            Logger::debug('Loaded active proxies', [
                'count' => count($this->proxies)
            ], 'PROXY');
        }
    }

    /**
     * Get next proxy based on rotation strategy
     */
    public function getNextProxy() {
        if (empty($this->proxies)) {
            $this->loadProxies();
            if (empty($this->proxies)) {
                return null;
            }
        }

        $proxy = null;

        switch ($this->config['rotation_strategy']) {
            case 'round-robin':
                $proxy = $this->getRoundRobinProxy();
                break;

            case 'random':
                $proxy = $this->getRandomProxy();
                break;

            case 'fastest':
                $proxy = $this->getFastestProxy();
                break;

            default:
                $proxy = $this->getRoundRobinProxy();
        }

        if ($proxy) {
            Logger::debug('Proxy selected', [
                'proxy_id' => $proxy['id'],
                'strategy' => $this->config['rotation_strategy']
            ], 'PROXY');
        }

        return $proxy;
    }

    /**
     * Round-robin proxy selection
     */
    private function getRoundRobinProxy() {
        if (empty($this->proxies)) {
            return null;
        }

        $proxy = $this->proxies[$this->currentIndex];
        $this->currentIndex = ($this->currentIndex + 1) % count($this->proxies);

        return $proxy;
    }

    /**
     * Random proxy selection
     */
    private function getRandomProxy() {
        if (empty($this->proxies)) {
            return null;
        }

        return $this->proxies[array_rand($this->proxies)];
    }

    /**
     * Select fastest proxy
     */
    private function getFastestProxy() {
        if (empty($this->proxies)) {
            return null;
        }

        // Sort by response time
        usort($this->proxies, function($a, $b) {
            $timeA = $a['response_time_ms'] ?? PHP_INT_MAX;
            $timeB = $b['response_time_ms'] ?? PHP_INT_MAX;
            return $timeA - $timeB;
        });

        return $this->proxies[0];
    }

    /**
     * Test a proxy
     */
    public function testProxy($proxyId) {
        $proxy = $this->proxy->getById($proxyId);
        if (!$proxy) {
            return ['success' => false, 'error' => 'Proxy not found'];
        }

        $startTime = microtime(true);
        $testUrl = $this->config['test_url'];

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $testUrl,
                CURLOPT_PROXY => $proxy['proxy_url'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->config['timeout'] / 1000,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $responseTime = (microtime(true) - $startTime) * 1000; // ms

            if ($response !== false && $httpCode >= 200 && $httpCode < 400) {
                $this->proxy->markSuccess($proxyId, (int)$responseTime);

                Logger::info('Proxy test successful', [
                    'proxy_id' => $proxyId,
                    'response_time_ms' => $responseTime,
                    'http_code' => $httpCode
                ], 'PROXY');

                return [
                    'success' => true,
                    'response_time_ms' => $responseTime,
                    'http_code' => $httpCode
                ];
            } else {
                $this->proxy->markFailed($proxyId);

                Logger::warning('Proxy test failed', [
                    'proxy_id' => $proxyId,
                    'error' => $error,
                    'http_code' => $httpCode
                ], 'PROXY');

                return [
                    'success' => false,
                    'error' => $error ?: 'HTTP ' . $httpCode,
                    'response_time_ms' => $responseTime
                ];
            }
        } catch (Exception $e) {
            $this->proxy->markFailed($proxyId);

            Logger::error('Proxy test exception', [
                'proxy_id' => $proxyId,
                'error' => $e->getMessage()
            ], 'PROXY');

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Health check all active proxies
     */
    public function healthCheckAll() {
        $this->loadProxies();

        $results = [
            'total' => count($this->proxies),
            'tested' => 0,
            'passed' => 0,
            'failed' => 0,
            'deactivated' => 0
        ];

        foreach ($this->proxies as $proxy) {
            $result = $this->testProxy($proxy['id']);
            $results['tested']++;

            if ($result['success']) {
                $results['passed']++;
            } else {
                $results['failed']++;

                // Check if proxy should be deactivated
                $updatedProxy = $this->proxy->getById($proxy['id']);
                if (!$updatedProxy['is_active']) {
                    $results['deactivated']++;
                }
            }

            // Small delay between tests
            usleep(100000); // 100ms
        }

        Logger::info('Proxy health check completed', $results, 'PROXY');

        return $results;
    }

    /**
     * Get proxy statistics
     */
    public function getStatistics() {
        return $this->proxy->getStatistics();
    }

    /**
     * Reload proxies (useful after adding new proxies)
     */
    public function reload() {
        $this->loadProxies();
        $this->currentIndex = 0;
    }

    /**
     * Get proxy for specific country
     */
    public function getProxyByCountry($country) {
        $proxies = $this->proxy->getByCountry($country);

        if (empty($proxies)) {
            return null;
        }

        return $proxies[array_rand($proxies)];
    }

    /**
     * Parse proxy URL components
     */
    public static function parseProxyUrl($proxyUrl) {
        // Format: protocol://[user:pass@]host:port
        $pattern = '/^(https?|socks5):\/\/(?:([^:@]+):([^@]+)@)?([\w\.\-]+):(\d+)$/';

        if (!preg_match($pattern, $proxyUrl, $matches)) {
            return null;
        }

        return [
            'protocol' => $matches[1],
            'username' => $matches[2] ?? null,
            'password' => $matches[3] ?? null,
            'host' => $matches[4],
            'port' => (int)$matches[5],
            'full_url' => $proxyUrl
        ];
    }

    /**
     * Format proxy for Puppeteer
     */
    public static function formatForPuppeteer($proxy) {
        $parsed = self::parseProxyUrl($proxy['proxy_url']);

        if (!$parsed) {
            return null;
        }

        return [
            'server' => "{$parsed['protocol']}://{$parsed['host']}:{$parsed['port']}",
            'username' => $parsed['username'],
            'password' => $parsed['password']
        ];
    }
}
