<?php
/**
 * PhpScraper Class
 *
 * PHP-based Google Maps scraper using cURL
 * Alternative to Node.js Puppeteer scraper for shared hosting
 */

class PhpScraper {
    private $db;
    private $proxyManager;
    private $config;
    private $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->proxyManager = new ProxyManager();
        $this->config = Config::load('scraper');
    }

    /**
     * Run scan for a phrase
     *
     * @param int $phraseId Phrase ID
     * @param int $scanId Scan ID
     * @return array Scan results
     */
    public function runScan($phraseId, $scanId) {
        $totalResults = 0;
        $scannedPoints = 0;
        $failedPoints = 0;

        try {
            // Get phrase data
            $phraseModel = new Phrase();
            $phrase = $phraseModel->getById($phraseId);

            if (!$phrase) {
                throw new Exception('Phrase not found');
            }

            // Generate grid
            $gridPoints = GridGenerator::generate(
                $phrase['location_lat'],
                $phrase['location_lng'],
                $phrase['radius_km'],
                $phrase['grid_size']
            );

            Logger::info('Starting PHP scraper scan', [
                'phrase_id' => $phraseId,
                'scan_id' => $scanId,
                'phrase' => $phrase['phrase'],
                'grid_points' => count($gridPoints)
            ], 'SCRAPER');

            // Update scan status
            $scanHistory = new ScanHistory();
            $scanHistory->updateStatus($scanId, 'running', [
                'total_grid_points' => count($gridPoints)
            ]);

            // Scan each grid point
            foreach ($gridPoints as $gridPoint) {
                $success = false;
                $attempts = 0;
                $maxRetries = $this->config['max_retries'] ?? 3;

                while (!$success && $attempts < $maxRetries) {
                    $attempts++;

                    try {
                        $resultsCount = $this->scrapeGridPoint(
                            $phrase['phrase'],
                            $gridPoint,
                            $scanId,
                            $phraseId
                        );

                        $totalResults += $resultsCount;
                        $scannedPoints++;
                        $success = true;

                        // Update progress
                        $scanHistory->updateProgress($scanId, $scannedPoints, $totalResults);

                        Logger::debug('Grid point scraped', [
                            'grid_point' => $gridPoint,
                            'results_found' => $resultsCount,
                            'attempt' => $attempts
                        ], 'SCRAPER');

                    } catch (Exception $e) {
                        Logger::warning('Grid point scraping failed, retrying', [
                            'grid_point' => $gridPoint,
                            'attempt' => $attempts,
                            'max_retries' => $maxRetries,
                            'error' => $e->getMessage()
                        ], 'SCRAPER');

                        if ($attempts >= $maxRetries) {
                            $failedPoints++;
                            break;
                        }

                        // Wait before retry
                        sleep(rand(5, 10));
                    }
                }

                // Random delay between grid points
                $delayMin = $this->config['request_delay_min'] ?? 2000;
                $delayMax = $this->config['request_delay_max'] ?? 5000;
                usleep(rand($delayMin, $delayMax) * 1000);
            }

            // Mark scan as completed
            $scanHistory->complete($scanId, [
                'scanned_points' => $scannedPoints,
                'failed_points' => $failedPoints,
                'results_found' => $totalResults
            ]);

            // Update phrase last scanned
            $phraseModel->updateLastScanned($phraseId);

            Logger::info('PHP scraper scan completed', [
                'phrase_id' => $phraseId,
                'scan_id' => $scanId,
                'total_results' => $totalResults,
                'scanned_points' => $scannedPoints,
                'failed_points' => $failedPoints
            ], 'SCRAPER');

            return [
                'success' => true,
                'total_results' => $totalResults,
                'scanned_points' => $scannedPoints,
                'failed_points' => $failedPoints
            ];

        } catch (Exception $e) {
            Logger::critical('PHP scraper scan failed', [
                'phrase_id' => $phraseId,
                'scan_id' => $scanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'SCRAPER');

            // Mark scan as failed
            $scanHistory = new ScanHistory();
            $scanHistory->fail($scanId, $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Scrape a single grid point
     *
     * @param string $phrase Search phrase
     * @param array $gridPoint Grid point coordinates
     * @param int $scanId Scan ID
     * @param int $phraseId Phrase ID
     * @return int Number of results found
     */
    private function scrapeGridPoint($phrase, $gridPoint, $scanId, $phraseId) {
        // Build Google Maps search URL
        $url = $this->buildSearchUrl($phrase, $gridPoint);

        // Get proxy
        $proxy = $this->proxyManager->getNextProxy();

        // Fetch page
        $html = $this->fetchPage($url, $proxy);

        if (!$html) {
            throw new Exception('Failed to fetch page');
        }

        // Parse results
        $places = $this->parseResults($html, $gridPoint);

        // Save results to database
        $resultModel = new Result();
        $savedCount = 0;

        foreach ($places as $place) {
            $resultData = [
                'phrase_id' => $phraseId,
                'scan_id' => $scanId,
                'place_name' => $place['name'],
                'rating' => $place['rating'],
                'reviews_count' => $place['reviews_count'],
                'latitude' => $place['lat'],
                'longitude' => $place['lng'],
                'place_url' => $place['url'],
                'grid_point_lat' => $gridPoint['lat'],
                'grid_point_lng' => $gridPoint['lng']
            ];

            try {
                $resultModel->create($resultData);
                $savedCount++;
            } catch (Exception $e) {
                Logger::warning('Failed to save result', [
                    'place' => $place,
                    'error' => $e->getMessage()
                ], 'SCRAPER');
            }
        }

        return $savedCount;
    }

    /**
     * Build Google Maps search URL
     *
     * @param string $phrase Search phrase
     * @param array $gridPoint Grid point
     * @return string URL
     */
    private function buildSearchUrl($phrase, $gridPoint) {
        // Use mobile Google Maps URL for better HTML parsing
        $query = urlencode($phrase);
        $lat = $gridPoint['lat'];
        $lng = $gridPoint['lng'];

        // Using mobile version which has simpler HTML structure
        return "https://www.google.com/maps/search/{$query}/@{$lat},{$lng},15z";
    }

    /**
     * Fetch page content using cURL
     *
     * @param string $url URL to fetch
     * @param array|null $proxy Proxy configuration
     * @return string|false Page content or false on failure
     */
    private function fetchPage($url, $proxy = null) {
        $ch = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_USERAGENT => $this->getRandomUserAgent(),
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,pl;q=0.8',
                'Accept-Encoding: gzip, deflate, br',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Upgrade-Insecure-Requests: 1'
            ]
        ];

        // Add proxy if available
        if ($proxy) {
            $options[CURLOPT_PROXY] = $proxy['proxy_url'];

            // Parse proxy for authentication
            $parsed = ProxyManager::parseProxyUrl($proxy['proxy_url']);
            if ($parsed && $parsed['username'] && $parsed['password']) {
                $options[CURLOPT_PROXYUSERPWD] = $parsed['username'] . ':' . $parsed['password'];
            }
        }

        curl_setopt_array($ch, $options);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $responseTime = (microtime(true) - $startTime) * 1000;

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        // Log request
        Logger::debug('Page fetch completed', [
            'url' => $url,
            'http_code' => $httpCode,
            'response_time_ms' => round($responseTime),
            'proxy_used' => $proxy ? true : false,
            'content_length' => strlen($response)
        ], 'SCRAPER');

        if ($response === false || $httpCode !== 200) {
            Logger::warning('Page fetch failed', [
                'url' => $url,
                'http_code' => $httpCode,
                'error' => $error
            ], 'SCRAPER');

            // Mark proxy as failed if used
            if ($proxy) {
                $proxyModel = new Proxy();
                $proxyModel->markFailed($proxy['id']);
            }

            return false;
        }

        // Mark proxy as successful if used
        if ($proxy) {
            $proxyModel = new Proxy();
            $proxyModel->markSuccess($proxy['id'], (int)$responseTime);
        }

        return $response;
    }

    /**
     * Parse results from HTML
     *
     * @param string $html HTML content
     * @param array $gridPoint Grid point
     * @return array Array of places
     */
    private function parseResults($html, $gridPoint) {
        $places = [];

        try {
            // Use DOMDocument to parse HTML
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($html);
            libxml_clear_errors();

            // Try multiple parsing strategies

            // Strategy 1: Look for JSON-LD structured data
            $jsonPlaces = $this->extractJsonLdData($html);
            if (!empty($jsonPlaces)) {
                $places = array_merge($places, $jsonPlaces);
            }

            // Strategy 2: Parse from script tags containing place data
            $scriptPlaces = $this->extractFromScriptTags($html);
            if (!empty($scriptPlaces)) {
                $places = array_merge($places, $scriptPlaces);
            }

            // Strategy 3: Parse HTML elements (fallback)
            $htmlPlaces = $this->extractFromHtmlElements($dom);
            if (!empty($htmlPlaces)) {
                $places = array_merge($places, $htmlPlaces);
            }

            // Remove duplicates based on coordinates
            $places = $this->removeDuplicatePlaces($places);

            // Limit results
            $maxResults = $this->config['max_results_per_point'] ?? 20;
            $places = array_slice($places, 0, $maxResults);

            Logger::debug('Results parsed', [
                'grid_point' => $gridPoint,
                'places_found' => count($places)
            ], 'SCRAPER');

        } catch (Exception $e) {
            Logger::error('Failed to parse results', [
                'grid_point' => $gridPoint,
                'error' => $e->getMessage()
            ], 'SCRAPER');
        }

        return $places;
    }

    /**
     * Extract JSON-LD structured data from HTML
     *
     * @param string $html HTML content
     * @return array Places
     */
    private function extractJsonLdData($html) {
        $places = [];

        // Find JSON-LD script tags
        preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches);

        foreach ($matches[1] as $json) {
            try {
                $data = json_decode($json, true);

                if (isset($data['@type']) && $data['@type'] === 'LocalBusiness') {
                    $place = $this->extractPlaceFromJsonLd($data);
                    if ($place) {
                        $places[] = $place;
                    }
                }
            } catch (Exception $e) {
                // Skip invalid JSON
            }
        }

        return $places;
    }

    /**
     * Extract place from JSON-LD data
     *
     * @param array $data JSON-LD data
     * @return array|null Place data
     */
    private function extractPlaceFromJsonLd($data) {
        if (!isset($data['name'])) {
            return null;
        }

        $lat = null;
        $lng = null;

        if (isset($data['geo']['latitude']) && isset($data['geo']['longitude'])) {
            $lat = (float)$data['geo']['latitude'];
            $lng = (float)$data['geo']['longitude'];
        }

        return [
            'name' => $data['name'],
            'rating' => isset($data['aggregateRating']['ratingValue']) ? (float)$data['aggregateRating']['ratingValue'] : null,
            'reviews_count' => isset($data['aggregateRating']['reviewCount']) ? (int)$data['aggregateRating']['reviewCount'] : 0,
            'lat' => $lat,
            'lng' => $lng,
            'url' => $data['url'] ?? ''
        ];
    }

    /**
     * Extract places from script tags
     *
     * @param string $html HTML content
     * @return array Places
     */
    private function extractFromScriptTags($html) {
        $places = [];

        // Google Maps often embeds data in JavaScript
        // Look for patterns like: null,null,[[null,null,lat,lng],"Place Name"
        preg_match_all('/\[null,null,\[([-\d\.]+),([-\d\.]+)\][^\]]*?"([^"]+)"/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (count($match) >= 4) {
                $places[] = [
                    'name' => $match[3],
                    'rating' => null,
                    'reviews_count' => 0,
                    'lat' => (float)$match[1],
                    'lng' => (float)$match[2],
                    'url' => ''
                ];
            }
        }

        // Try another pattern for place data
        preg_match_all('/"([^"]+)","([^"]+)".*?\[([-\d\.]+),([-\d\.]+)\]/i', $html, $matches2, PREG_SET_ORDER);

        foreach ($matches2 as $match) {
            if (count($match) >= 5) {
                $places[] = [
                    'name' => $match[1],
                    'rating' => null,
                    'reviews_count' => 0,
                    'lat' => (float)$match[3],
                    'lng' => (float)$match[4],
                    'url' => ''
                ];
            }
        }

        return $places;
    }

    /**
     * Extract places from HTML elements
     *
     * @param DOMDocument $dom DOM document
     * @return array Places
     */
    private function extractFromHtmlElements($dom) {
        $places = [];

        // This is a fallback method and may need adjustments based on Google's HTML structure
        // Google Maps frequently changes its HTML structure, so this is best-effort

        return $places;
    }

    /**
     * Remove duplicate places based on coordinates
     *
     * @param array $places Places array
     * @return array Unique places
     */
    private function removeDuplicatePlaces($places) {
        $unique = [];
        $seen = [];

        foreach ($places as $place) {
            if (!$place['lat'] || !$place['lng']) {
                continue;
            }

            $key = round($place['lat'], 6) . '_' . round($place['lng'], 6);

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $place;
            }
        }

        return $unique;
    }

    /**
     * Get random user agent
     *
     * @return string User agent
     */
    private function getRandomUserAgent() {
        return $this->userAgents[array_rand($this->userAgents)];
    }
}
