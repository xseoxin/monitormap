<?php
/**
 * Proxy Class
 *
 * Handles proxy operations
 */

class Proxy {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new proxy
     */
    public function create($data) {
        $required = ['proxy_url', 'type'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Check if proxy already exists
        $existing = $this->getByUrl($data['proxy_url']);
        if ($existing) {
            throw new Exception("Proxy already exists");
        }

        $sql = "INSERT INTO proxies
                (proxy_url, type, country, is_active, notes, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";

        $proxyId = $this->db->insert($sql, [
            $data['proxy_url'],
            $data['type'],
            $data['country'] ?? null,
            $data['is_active'] ?? 1,
            $data['notes'] ?? null
        ]);

        Logger::info('Proxy added', [
            'proxy_id' => $proxyId,
            'type' => $data['type']
        ], 'PROXY');

        return $proxyId;
    }

    /**
     * Get proxy by ID
     */
    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM proxies WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get proxy by URL
     */
    public function getByUrl($url) {
        return $this->db->fetchOne(
            "SELECT * FROM proxies WHERE proxy_url = ?",
            [$url]
        );
    }

    /**
     * Get all proxies
     */
    public function getAll($activeOnly = false) {
        $sql = "SELECT * FROM proxies";

        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }

        $sql .= " ORDER BY success_rate DESC, response_time_ms ASC";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get active proxies
     */
    public function getActive() {
        return $this->getAll(true);
    }

    /**
     * Update proxy
     */
    public function update($id, $data) {
        $allowedFields = ['proxy_url', 'type', 'country', 'is_active', 'notes'];
        $updates = [];
        $values = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $values[] = $id;

        $sql = "UPDATE proxies SET " . implode(', ', $updates) . " WHERE id = ?";
        return $this->db->execute($sql, $values) > 0;
    }

    /**
     * Update proxy statistics
     */
    public function updateStats($id, $responseTime, $success) {
        $sql = "UPDATE proxies SET
                    response_time_ms = ?,
                    total_requests = total_requests + 1,
                    successful_requests = successful_requests + ?,
                    success_rate = (successful_requests + ?) * 100.0 / (total_requests + 1),
                    last_used_at = NOW(),
                    last_checked_at = NOW(),
                    failed_attempts = CASE WHEN ? = 1 THEN 0 ELSE failed_attempts + 1 END
                WHERE id = ?";

        $successInt = $success ? 1 : 0;

        return $this->db->execute($sql, [
            $responseTime,
            $successInt,
            $successInt,
            $successInt,
            $id
        ]) > 0;
    }

    /**
     * Mark proxy as failed
     */
    public function markFailed($id) {
        $proxy = $this->getById($id);
        if (!$proxy) {
            return false;
        }

        $this->updateStats($id, null, false);

        // Deactivate if too many failures
        $config = Config::load('proxy');
        if ($proxy['failed_attempts'] + 1 >= $config['max_failed_attempts']) {
            $this->deactivate($id);
            Logger::warning('Proxy deactivated due to failures', [
                'proxy_id' => $id,
                'failed_attempts' => $proxy['failed_attempts'] + 1
            ], 'PROXY');
        }

        return true;
    }

    /**
     * Mark proxy as successful
     */
    public function markSuccess($id, $responseTime) {
        return $this->updateStats($id, $responseTime, true);
    }

    /**
     * Deactivate proxy
     */
    public function deactivate($id) {
        return $this->db->execute(
            "UPDATE proxies SET is_active = 0 WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Activate proxy
     */
    public function activate($id) {
        return $this->db->execute(
            "UPDATE proxies SET is_active = 1, failed_attempts = 0 WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Delete proxy
     */
    public function delete($id) {
        Logger::info('Proxy deleted', ['proxy_id' => $id], 'PROXY');

        return $this->db->execute(
            "DELETE FROM proxies WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Import proxies from CSV
     */
    public function importFromCsv($csvData) {
        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($csvData as $index => $row) {
            try {
                // Expected format: proxy_url, type, country, notes
                $data = [
                    'proxy_url' => $row[0] ?? null,
                    'type' => $row[1] ?? 'http',
                    'country' => $row[2] ?? null,
                    'notes' => $row[3] ?? null
                ];

                if (empty($data['proxy_url'])) {
                    throw new Exception("Empty proxy URL");
                }

                $this->create($data);
                $imported++;
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Get proxy statistics
     */
    public function getStatistics() {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*) as total_proxies,
                SUM(is_active) as active_proxies,
                AVG(success_rate) as avg_success_rate,
                AVG(response_time_ms) as avg_response_time,
                MIN(response_time_ms) as min_response_time,
                MAX(response_time_ms) as max_response_time
            FROM proxies"
        );
    }

    /**
     * Get proxies by country
     */
    public function getByCountry($country) {
        return $this->db->fetchAll(
            "SELECT * FROM proxies WHERE country = ? AND is_active = 1
             ORDER BY success_rate DESC",
            [$country]
        );
    }

    /**
     * Get fastest proxies
     */
    public function getFastest($limit = 10) {
        return $this->db->fetchAll(
            "SELECT * FROM proxies
             WHERE is_active = 1 AND response_time_ms IS NOT NULL
             ORDER BY response_time_ms ASC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Reset failed attempts
     */
    public function resetFailedAttempts($id) {
        return $this->db->execute(
            "UPDATE proxies SET failed_attempts = 0 WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Get proxy count
     */
    public function getCount($activeOnly = false) {
        $sql = "SELECT COUNT(*) FROM proxies";

        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }

        return $this->db->fetchValue($sql);
    }
}
