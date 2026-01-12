<?php
/**
 * ScraperRunner Class
 *
 * Manages scraper selection and execution
 * Supports both Node.js Puppeteer and PHP cURL scrapers
 */

class ScraperRunner {
    private $config;
    private $scraperType;

    public function __construct() {
        $this->config = Config::load('scraper');
        $this->scraperType = $this->detectScraperType();
    }

    /**
     * Detect which scraper to use
     *
     * @return string 'nodejs' or 'php' or null if neither available
     */
    private function detectScraperType() {
        $configuredType = $this->config['type'] ?? 'auto';

        // If explicitly set, validate and use that
        if ($configuredType === 'nodejs') {
            if ($this->isNodeJsAvailable()) {
                return 'nodejs';
            } else {
                Logger::warning('Node.js scraper requested but not available, falling back to PHP', [], 'SCRAPER');
                if ($this->isPhpScraperAvailable()) {
                    return 'php';
                }
            }
        }

        if ($configuredType === 'php') {
            if ($this->isPhpScraperAvailable()) {
                return 'php';
            } else {
                Logger::error('PHP scraper requested but cURL not available', [], 'SCRAPER');
                return null;
            }
        }

        // Auto-detect: prefer Node.js if available, fallback to PHP
        if ($this->isNodeJsAvailable()) {
            Logger::info('Auto-detected Node.js scraper', [], 'SCRAPER');
            return 'nodejs';
        }

        if ($this->isPhpScraperAvailable()) {
            Logger::info('Auto-detected PHP scraper', [], 'SCRAPER');
            return 'php';
        }

        Logger::error('No scraper available (neither Node.js nor PHP)', [], 'SCRAPER');
        return null;
    }

    /**
     * Check if Node.js is available
     *
     * @return bool
     */
    private function isNodeJsAvailable() {
        $nodePath = $this->config['nodejs']['node_path'] ?? '/usr/bin/node';
        $scraperPath = $this->config['nodejs']['scraper_path'] ?? '';

        // Check if Node.js exists and is executable
        if (!file_exists($nodePath) || !is_executable($nodePath)) {
            return false;
        }

        // Check if scraper.js exists
        if (!file_exists($scraperPath)) {
            return false;
        }

        // Check if node_modules exists
        $nodeModules = dirname($scraperPath) . '/node_modules';
        if (!is_dir($nodeModules)) {
            return false;
        }

        return true;
    }

    /**
     * Check if PHP scraper is available
     *
     * @return bool
     */
    private function isPhpScraperAvailable() {
        // Check if cURL is available
        if (!function_exists('curl_init')) {
            return false;
        }

        // Check if scraper.php exists
        $scraperPath = $this->config['php']['scraper_path'] ?? '';
        if (!file_exists($scraperPath)) {
            return false;
        }

        return true;
    }

    /**
     * Get current scraper type
     *
     * @return string|null
     */
    public function getScraperType() {
        return $this->scraperType;
    }

    /**
     * Get scraper info
     *
     * @return array
     */
    public function getScraperInfo() {
        return [
            'type' => $this->scraperType,
            'nodejs_available' => $this->isNodeJsAvailable(),
            'php_available' => $this->isPhpScraperAvailable(),
            'configured_type' => $this->config['type'] ?? 'auto'
        ];
    }

    /**
     * Run a scan
     *
     * @param int $phraseId Phrase ID
     * @param int $scanId Scan ID
     * @return array Result
     */
    public function runScan($phraseId, $scanId) {
        if (!$this->scraperType) {
            throw new Exception('No scraper available. Please install Node.js or ensure cURL is enabled.');
        }

        Logger::info('Starting scan', [
            'phrase_id' => $phraseId,
            'scan_id' => $scanId,
            'scraper_type' => $this->scraperType
        ], 'SCRAPER');

        if ($this->scraperType === 'nodejs') {
            return $this->runNodeJsScan($phraseId, $scanId);
        } else if ($this->scraperType === 'php') {
            return $this->runPhpScan($phraseId, $scanId);
        }

        throw new Exception('Invalid scraper type: ' . $this->scraperType);
    }

    /**
     * Run Node.js Puppeteer scan
     *
     * @param int $phraseId
     * @param int $scanId
     * @return array
     */
    private function runNodeJsScan($phraseId, $scanId) {
        $nodePath = $this->config['nodejs']['node_path'];
        $scraperPath = $this->config['nodejs']['scraper_path'];

        // Build command
        $command = sprintf(
            '%s %s --phrase-id=%d --scan-id=%d 2>&1',
            escapeshellarg($nodePath),
            escapeshellarg($scraperPath),
            $phraseId,
            $scanId
        );

        Logger::debug('Executing Node.js scraper', ['command' => $command], 'SCRAPER');

        // Execute
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        $outputStr = implode("\n", $output);

        if ($returnCode === 0) {
            Logger::info('Node.js scan completed successfully', [
                'phrase_id' => $phraseId,
                'scan_id' => $scanId
            ], 'SCRAPER');

            return [
                'success' => true,
                'output' => $outputStr,
                'scraper_type' => 'nodejs'
            ];
        } else {
            Logger::error('Node.js scan failed', [
                'phrase_id' => $phraseId,
                'scan_id' => $scanId,
                'return_code' => $returnCode,
                'output' => $outputStr
            ], 'SCRAPER');

            throw new Exception('Node.js scan failed: ' . $outputStr);
        }
    }

    /**
     * Run PHP cURL scan
     *
     * @param int $phraseId
     * @param int $scanId
     * @return array
     */
    private function runPhpScan($phraseId, $scanId) {
        // Use PhpScraper class directly
        $scraper = new PhpScraper();
        $result = $scraper->runScan($phraseId, $scanId);

        if ($result['success']) {
            Logger::info('PHP scan completed successfully', [
                'phrase_id' => $phraseId,
                'scan_id' => $scanId,
                'total_results' => $result['total_results']
            ], 'SCRAPER');
        } else {
            Logger::error('PHP scan failed', [
                'phrase_id' => $phraseId,
                'scan_id' => $scanId,
                'error' => $result['error'] ?? 'Unknown error'
            ], 'SCRAPER');
        }

        $result['scraper_type'] = 'php';
        return $result;
    }

    /**
     * Run scan via CLI
     *
     * @param int $phraseId
     * @param int $scanId
     * @return array
     */
    public function runScanCli($phraseId, $scanId) {
        if (!$this->scraperType) {
            throw new Exception('No scraper available');
        }

        if ($this->scraperType === 'nodejs') {
            return $this->runNodeJsScan($phraseId, $scanId);
        }

        // For PHP, use CLI script
        $scraperPath = $this->config['php']['scraper_path'];

        $command = sprintf(
            'php %s --phrase-id=%d --scan-id=%d 2>&1',
            escapeshellarg($scraperPath),
            $phraseId,
            $scanId
        );

        Logger::debug('Executing PHP scraper CLI', ['command' => $command], 'SCRAPER');

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        $outputStr = implode("\n", $output);

        return [
            'success' => $returnCode === 0,
            'output' => $outputStr,
            'scraper_type' => 'php',
            'return_code' => $returnCode
        ];
    }

    /**
     * Run scan in background (non-blocking)
     *
     * @param int $phraseId
     * @param int $scanId
     * @return bool
     */
    public function runScanBackground($phraseId, $scanId) {
        if (!$this->scraperType) {
            Logger::error('Cannot run background scan: no scraper available', [], 'SCRAPER');
            return false;
        }

        if ($this->scraperType === 'nodejs') {
            $nodePath = $this->config['nodejs']['node_path'];
            $scraperPath = $this->config['nodejs']['scraper_path'];

            $command = sprintf(
                '%s %s --phrase-id=%d --scan-id=%d > /dev/null 2>&1 &',
                escapeshellarg($nodePath),
                escapeshellarg($scraperPath),
                $phraseId,
                $scanId
            );
        } else {
            $scraperPath = $this->config['php']['scraper_path'];

            $command = sprintf(
                'php %s --phrase-id=%d --scan-id=%d > /dev/null 2>&1 &',
                escapeshellarg($scraperPath),
                $phraseId,
                $scanId
            );
        }

        Logger::info('Starting background scan', [
            'phrase_id' => $phraseId,
            'scan_id' => $scanId,
            'scraper_type' => $this->scraperType,
            'command' => $command
        ], 'SCRAPER');

        // Execute in background
        exec($command);

        return true;
    }
}
