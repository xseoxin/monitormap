<?php
/**
 * Phrase Class
 *
 * Handles phrase (search query) operations
 */

class Phrase {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new phrase
     */
    public function create($data) {
        // Validate required fields
        $required = ['user_id', 'phrase', 'location_lat', 'location_lng', 'radius_km', 'grid_size'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Set defaults
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['priority'] = $data['priority'] ?? 5;
        $data['scan_frequency'] = $data['scan_frequency'] ?? 'weekly';

        // Calculate next scan time
        $data['next_scan_at'] = $this->calculateNextScanTime($data['scan_frequency']);

        $sql = "INSERT INTO phrases
                (user_id, phrase, location_lat, location_lng, radius_km, grid_size,
                 is_active, priority, scan_frequency, next_scan_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $phraseId = $this->db->insert($sql, [
            $data['user_id'],
            $data['phrase'],
            $data['location_lat'],
            $data['location_lng'],
            $data['radius_km'],
            $data['grid_size'],
            $data['is_active'],
            $data['priority'],
            $data['scan_frequency'],
            $data['next_scan_at']
        ]);

        Logger::info('Phrase created', [
            'phrase_id' => $phraseId,
            'phrase' => $data['phrase'],
            'user_id' => $data['user_id']
        ], 'SYSTEM');

        return $phraseId;
    }

    /**
     * Get phrase by ID
     */
    public function getById($id, $userId = null) {
        $sql = "SELECT * FROM phrases WHERE id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Get all phrases for a user
     */
    public function getByUser($userId, $activeOnly = false, $limit = 100, $offset = 0) {
        $sql = "SELECT * FROM phrases WHERE user_id = ?";
        $params = [$userId];

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY priority DESC, created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get phrases ready for scanning
     */
    public function getReadyForScan() {
        return $this->db->fetchAll(
            "SELECT * FROM phrases
             WHERE is_active = 1
             AND (next_scan_at IS NULL OR next_scan_at <= NOW())
             ORDER BY priority DESC, next_scan_at ASC"
        );
    }

    /**
     * Update phrase
     */
    public function update($id, $data, $userId = null) {
        $allowedFields = ['phrase', 'location_lat', 'location_lng', 'radius_km', 'grid_size',
                          'is_active', 'priority', 'scan_frequency'];
        $updates = [];
        $values = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        // Recalculate next scan if frequency changed
        if (isset($data['scan_frequency'])) {
            $updates[] = "next_scan_at = ?";
            $values[] = $this->calculateNextScanTime($data['scan_frequency']);
        }

        if (empty($updates)) {
            return false;
        }

        $values[] = $id;
        if ($userId !== null) {
            $values[] = $userId;
        }

        $sql = "UPDATE phrases SET " . implode(', ', $updates) . " WHERE id = ?";
        if ($userId !== null) {
            $sql .= " AND user_id = ?";
        }

        return $this->db->execute($sql, $values) > 0;
    }

    /**
     * Delete phrase
     */
    public function delete($id, $userId = null) {
        $sql = "DELETE FROM phrases WHERE id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        Logger::info('Phrase deleted', ['phrase_id' => $id], 'SYSTEM');

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Update last scanned time
     */
    public function updateLastScanned($id) {
        $phrase = $this->getById($id);
        if (!$phrase) {
            return false;
        }

        $nextScanAt = $this->calculateNextScanTime($phrase['scan_frequency']);

        return $this->db->execute(
            "UPDATE phrases SET last_scanned_at = NOW(), next_scan_at = ? WHERE id = ?",
            [$nextScanAt, $id]
        ) > 0;
    }

    /**
     * Calculate next scan time based on frequency
     */
    private function calculateNextScanTime($frequency) {
        $now = new DateTime();

        switch ($frequency) {
            case 'daily':
                $now->modify('+1 day');
                break;
            case 'weekly':
                $now->modify('+7 days');
                break;
            case 'monthly':
                $now->modify('+30 days');
                break;
            case 'manual':
                return null;
            default:
                $now->modify('+7 days');
        }

        return $now->format('Y-m-d H:i:s');
    }

    /**
     * Get phrase count for user
     */
    public function getCountByUser($userId) {
        return $this->db->fetchValue(
            "SELECT COUNT(*) FROM phrases WHERE user_id = ?",
            [$userId]
        );
    }

    /**
     * Search phrases
     */
    public function search($query, $userId = null) {
        $sql = "SELECT * FROM phrases WHERE phrase LIKE ?";
        $params = ['%' . $query . '%'];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY priority DESC LIMIT 50";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get all phrases (admin only)
     */
    public function getAll($limit = 100, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT p.*, u.username, u.email
             FROM phrases p
             INNER JOIN users u ON p.user_id = u.id
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Get phrase statistics
     */
    public function getStats($phraseId) {
        $stats = [];

        // Total scans
        $stats['total_scans'] = $this->db->fetchValue(
            "SELECT COUNT(*) FROM scan_history WHERE phrase_id = ?",
            [$phraseId]
        );

        // Completed scans
        $stats['completed_scans'] = $this->db->fetchValue(
            "SELECT COUNT(*) FROM scan_history WHERE phrase_id = ? AND status = 'completed'",
            [$phraseId]
        );

        // Total results
        $stats['total_results'] = $this->db->fetchValue(
            "SELECT COUNT(*) FROM results WHERE phrase_id = ?",
            [$phraseId]
        );

        // Average rating
        $stats['avg_rating'] = $this->db->fetchValue(
            "SELECT AVG(rating) FROM results WHERE phrase_id = ? AND rating IS NOT NULL",
            [$phraseId]
        );

        // Last scan
        $stats['last_scan'] = $this->db->fetchOne(
            "SELECT * FROM scan_history WHERE phrase_id = ? ORDER BY started_at DESC LIMIT 1",
            [$phraseId]
        );

        return $stats;
    }
}
