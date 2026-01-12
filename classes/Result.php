<?php
/**
 * Result Class
 *
 * Handles scan result operations
 */

class Result {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new result
     */
    public function create($data) {
        $required = ['phrase_id', 'scan_id', 'place_name', 'latitude', 'longitude',
                     'grid_point_lat', 'grid_point_lng'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        $sql = "INSERT INTO results
                (phrase_id, scan_id, place_name, address, rating, reviews_count,
                 latitude, longitude, phone, website, place_url,
                 grid_point_lat, grid_point_lng, found_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        return $this->db->insert($sql, [
            $data['phrase_id'],
            $data['scan_id'],
            $data['place_name'],
            $data['address'] ?? null,
            $data['rating'] ?? null,
            $data['reviews_count'] ?? 0,
            $data['latitude'],
            $data['longitude'],
            $data['phone'] ?? null,
            $data['website'] ?? null,
            $data['place_url'] ?? null,
            $data['grid_point_lat'],
            $data['grid_point_lng']
        ]);
    }

    /**
     * Get result by ID
     */
    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM results WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get results by phrase
     */
    public function getByPhrase($phraseId, $limit = 100, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT * FROM results
             WHERE phrase_id = ?
             ORDER BY found_at DESC
             LIMIT ? OFFSET ?",
            [$phraseId, $limit, $offset]
        );
    }

    /**
     * Get results by scan
     */
    public function getByScan($scanId, $limit = 1000, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT * FROM results
             WHERE scan_id = ?
             ORDER BY rating DESC, reviews_count DESC
             LIMIT ? OFFSET ?",
            [$scanId, $limit, $offset]
        );
    }

    /**
     * Search results with filters
     */
    public function search($filters = [], $limit = 100, $offset = 0) {
        $sql = "SELECT r.*, p.phrase, p.user_id
                FROM results r
                INNER JOIN phrases p ON r.phrase_id = p.id
                WHERE 1=1";
        $params = [];

        // Filter by phrase_id
        if (!empty($filters['phrase_id'])) {
            $sql .= " AND r.phrase_id = ?";
            $params[] = $filters['phrase_id'];
        }

        // Filter by user_id
        if (!empty($filters['user_id'])) {
            $sql .= " AND p.user_id = ?";
            $params[] = $filters['user_id'];
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $sql .= " AND r.found_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND r.found_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        // Filter by rating
        if (isset($filters['rating_min'])) {
            $sql .= " AND r.rating >= ?";
            $params[] = $filters['rating_min'];
        }

        if (isset($filters['rating_max'])) {
            $sql .= " AND r.rating <= ?";
            $params[] = $filters['rating_max'];
        }

        // Search by place name or address
        if (!empty($filters['search'])) {
            $sql .= " AND (r.place_name LIKE ? OR r.address LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'found_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $sql .= " ORDER BY r.{$orderBy} {$orderDir}";

        // Pagination
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get results count with filters
     */
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) FROM results r
                INNER JOIN phrases p ON r.phrase_id = p.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['phrase_id'])) {
            $sql .= " AND r.phrase_id = ?";
            $params[] = $filters['phrase_id'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND p.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND r.found_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND r.found_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (isset($filters['rating_min'])) {
            $sql .= " AND r.rating >= ?";
            $params[] = $filters['rating_min'];
        }

        if (isset($filters['rating_max'])) {
            $sql .= " AND r.rating <= ?";
            $params[] = $filters['rating_max'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (r.place_name LIKE ? OR r.address LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        return $this->db->fetchValue($sql, $params);
    }

    /**
     * Delete old results
     */
    public function deleteOld($days = 180) {
        $sql = "DELETE FROM results
                WHERE found_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND id NOT IN (
                    SELECT * FROM (
                        SELECT MIN(id) FROM results GROUP BY phrase_id
                    ) AS keep_results
                )";

        $deleted = $this->db->execute($sql, [$days]);

        Logger::info('Old results deleted', ['deleted_count' => $deleted], 'CRON');

        return $deleted;
    }

    /**
     * Compare two scans
     */
    public function compareScans($scanId1, $scanId2) {
        // Get results from both scans
        $results1 = $this->getByScan($scanId1, 10000);
        $results2 = $this->getByScan($scanId2, 10000);

        // Create arrays keyed by place_name for easy comparison
        $places1 = [];
        $places2 = [];

        foreach ($results1 as $result) {
            $places1[$result['place_name']] = $result;
        }

        foreach ($results2 as $result) {
            $places2[$result['place_name']] = $result;
        }

        // Find new places (in scan 2 but not in scan 1)
        $newPlaces = array_diff_key($places2, $places1);

        // Find removed places (in scan 1 but not in scan 2)
        $removedPlaces = array_diff_key($places1, $places2);

        // Find places with changed ratings
        $changedRatings = [];
        foreach ($places1 as $placeName => $place1) {
            if (isset($places2[$placeName])) {
                $place2 = $places2[$placeName];
                if ($place1['rating'] != $place2['rating']) {
                    $changedRatings[] = [
                        'place_name' => $placeName,
                        'old_rating' => $place1['rating'],
                        'new_rating' => $place2['rating'],
                        'change' => $place2['rating'] - $place1['rating']
                    ];
                }
            }
        }

        return [
            'new_places' => array_values($newPlaces),
            'removed_places' => array_values($removedPlaces),
            'changed_ratings' => $changedRatings,
            'total_scan1' => count($results1),
            'total_scan2' => count($results2)
        ];
    }

    /**
     * Get top results by rating
     */
    public function getTopByRating($phraseId = null, $limit = 10) {
        $sql = "SELECT * FROM results WHERE rating IS NOT NULL";
        $params = [];

        if ($phraseId !== null) {
            $sql .= " AND phrase_id = ?";
            $params[] = $phraseId;
        }

        $sql .= " ORDER BY rating DESC, reviews_count DESC LIMIT ?";
        $params[] = $limit;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get results for map visualization
     */
    public function getForMap($filters = []) {
        $sql = "SELECT r.id, r.place_name, r.address, r.rating, r.reviews_count,
                       r.latitude, r.longitude, r.phone, r.website, r.place_url,
                       p.phrase
                FROM results r
                INNER JOIN phrases p ON r.phrase_id = p.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['phrase_id'])) {
            $sql .= " AND r.phrase_id = ?";
            $params[] = $filters['phrase_id'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND p.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND r.found_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (isset($filters['rating_min'])) {
            $sql .= " AND r.rating >= ?";
            $params[] = $filters['rating_min'];
        }

        // Limit to recent results for performance
        $limit = $filters['limit'] ?? 1000;
        $sql .= " ORDER BY r.found_at DESC LIMIT ?";
        $params[] = $limit;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get statistics
     */
    public function getStatistics($userId = null, $phraseId = null) {
        $sql = "SELECT
                    COUNT(*) as total_results,
                    AVG(rating) as avg_rating,
                    MAX(rating) as max_rating,
                    MIN(rating) as min_rating,
                    COUNT(DISTINCT phrase_id) as unique_phrases
                FROM results r";

        $joins = [];
        $where = [];
        $params = [];

        if ($userId !== null) {
            $joins[] = "INNER JOIN phrases p ON r.phrase_id = p.id";
            $where[] = "p.user_id = ?";
            $params[] = $userId;
        }

        if ($phraseId !== null) {
            $where[] = "r.phrase_id = ?";
            $params[] = $phraseId;
        }

        if (!empty($joins)) {
            $sql .= " " . implode(" ", $joins);
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        return $this->db->fetchOne($sql, $params);
    }
}
