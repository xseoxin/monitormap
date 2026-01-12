<?php
/**
 * ScanHistory Class
 *
 * Handles scan history operations
 */

class ScanHistory {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new scan record
     */
    public function create($phraseId, $userId, $totalGridPoints = 0) {
        $sql = "INSERT INTO scan_history
                (phrase_id, user_id, started_at, total_grid_points, status)
                VALUES (?, ?, NOW(), ?, 'pending')";

        $scanId = $this->db->insert($sql, [$phraseId, $userId, $totalGridPoints]);

        Logger::info('Scan created', [
            'scan_id' => $scanId,
            'phrase_id' => $phraseId,
            'total_grid_points' => $totalGridPoints
        ], 'SCRAPER');

        return $scanId;
    }

    /**
     * Get scan by ID
     */
    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT sh.*, p.phrase, p.location_lat, p.location_lng, p.radius_km, p.grid_size
             FROM scan_history sh
             INNER JOIN phrases p ON sh.phrase_id = p.id
             WHERE sh.id = ?",
            [$id]
        );
    }

    /**
     * Get scans by phrase
     */
    public function getByPhrase($phraseId, $limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT * FROM scan_history
             WHERE phrase_id = ?
             ORDER BY started_at DESC
             LIMIT ? OFFSET ?",
            [$phraseId, $limit, $offset]
        );
    }

    /**
     * Get scans by user
     */
    public function getByUser($userId, $limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT sh.*, p.phrase
             FROM scan_history sh
             INNER JOIN phrases p ON sh.phrase_id = p.id
             WHERE sh.user_id = ?
             ORDER BY sh.started_at DESC
             LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    /**
     * Get recent scans
     */
    public function getRecent($limit = 10, $userId = null) {
        $sql = "SELECT sh.*, p.phrase, u.username
                FROM scan_history sh
                INNER JOIN phrases p ON sh.phrase_id = p.id
                INNER JOIN users u ON sh.user_id = u.id";

        $params = [];

        if ($userId !== null) {
            $sql .= " WHERE sh.user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY sh.started_at DESC LIMIT ?";
        $params[] = $limit;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Update scan status
     */
    public function updateStatus($scanId, $status, $errorMessage = null) {
        $sql = "UPDATE scan_history SET status = ?";
        $params = [$status];

        if ($status === 'completed') {
            $sql .= ", completed_at = NOW(),
                       duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW())";
        }

        if ($errorMessage !== null) {
            $sql .= ", error_message = ?";
            $params[] = $errorMessage;
        }

        $sql .= " WHERE id = ?";
        $params[] = $scanId;

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Update scan progress
     */
    public function updateProgress($scanId, $scannedPoints, $failedPoints = 0, $resultsFound = 0) {
        $sql = "UPDATE scan_history
                SET scanned_points = ?, failed_points = ?, results_found = ?
                WHERE id = ?";

        return $this->db->execute($sql, [
            $scannedPoints,
            $failedPoints,
            $resultsFound,
            $scanId
        ]) > 0;
    }

    /**
     * Mark scan as running
     */
    public function markAsRunning($scanId) {
        return $this->updateStatus($scanId, 'running');
    }

    /**
     * Mark scan as completed
     */
    public function markAsCompleted($scanId) {
        return $this->updateStatus($scanId, 'completed');
    }

    /**
     * Mark scan as failed
     */
    public function markAsFailed($scanId, $errorMessage) {
        return $this->updateStatus($scanId, 'failed', $errorMessage);
    }

    /**
     * Get running scans
     */
    public function getRunning() {
        return $this->db->fetchAll(
            "SELECT * FROM scan_history
             WHERE status IN ('pending', 'running')
             ORDER BY started_at ASC"
        );
    }

    /**
     * Get scan statistics
     */
    public function getStatistics($userId = null, $phraseId = null, $days = 30) {
        $sql = "SELECT
                    COUNT(*) as total_scans,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_scans,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_scans,
                    SUM(CASE WHEN status IN ('pending', 'running') THEN 1 ELSE 0 END) as running_scans,
                    SUM(results_found) as total_results,
                    AVG(duration_seconds) as avg_duration,
                    AVG(CASE WHEN status = 'completed' THEN
                        (scanned_points * 100.0 / NULLIF(total_grid_points, 0))
                    ELSE NULL END) as avg_completion_rate
                FROM scan_history
                WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";

        $params = [$days];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        if ($phraseId !== null) {
            $sql .= " AND phrase_id = ?";
            $params[] = $phraseId;
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Get scan timeline (for visualization)
     */
    public function getTimeline($scanId) {
        // This would contain detailed step-by-step progress
        // For now, return the basic scan info with results grouped by grid point
        $sql = "SELECT
                    r.grid_point_lat,
                    r.grid_point_lng,
                    COUNT(*) as results_count
                FROM results r
                WHERE r.scan_id = ?
                GROUP BY r.grid_point_lat, r.grid_point_lng";

        return $this->db->fetchAll($sql, [$scanId]);
    }

    /**
     * Delete old scan history
     */
    public function deleteOld($days = 90) {
        $sql = "DELETE FROM scan_history
                WHERE started_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND status IN ('completed', 'failed')";

        $deleted = $this->db->execute($sql, [$days]);

        Logger::info('Old scan history deleted', ['deleted_count' => $deleted], 'CRON');

        return $deleted;
    }

    /**
     * Get scan success rate
     */
    public function getSuccessRate($userId = null, $days = 30) {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM scan_history
                WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";

        $params = [$days];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $result = $this->db->fetchOne($sql, $params);

        if ($result['total'] == 0) {
            return 0;
        }

        return ($result['completed'] / $result['total']) * 100;
    }

    /**
     * Get scans grouped by day
     */
    public function getByDay($userId = null, $days = 30) {
        $sql = "SELECT
                    DATE(started_at) as scan_date,
                    COUNT(*) as total_scans,
                    SUM(results_found) as total_results,
                    AVG(duration_seconds) as avg_duration
                FROM scan_history
                WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";

        $params = [$days];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $sql .= " GROUP BY DATE(started_at) ORDER BY scan_date DESC";

        return $this->db->fetchAll($sql, $params);
    }
}
