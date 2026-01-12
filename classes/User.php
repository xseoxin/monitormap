<?php
/**
 * User Class
 *
 * Handles user-related operations
 */

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get user by username
     */
    public function getByUsername($username) {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ?",
            [$username]
        );
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }

    /**
     * Get user by API token
     */
    public function getByApiToken($token) {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE api_token = ?",
            [$token]
        );
    }

    /**
     * Get all users
     */
    public function getAll($limit = 100, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT id, username, email, role, is_active, max_phrases, created_at, last_login
             FROM users
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        $allowedFields = ['username', 'email', 'role', 'is_active', 'max_phrases', 'timezone'];
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

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        return $this->db->execute($sql, $values) > 0;
    }

    /**
     * Update password
     */
    public function updatePassword($id, $newPassword) {
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

        return $this->db->execute(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [$passwordHash, $id]
        ) > 0;
    }

    /**
     * Regenerate API token
     */
    public function regenerateApiToken($id) {
        $apiToken = bin2hex(random_bytes(32));

        $this->db->execute(
            "UPDATE users SET api_token = ? WHERE id = ?",
            [$apiToken, $id]
        );

        return $apiToken;
    }

    /**
     * Get user statistics
     */
    public function getStats($userId) {
        $stats = [];

        // Total phrases
        $stats['total_phrases'] = $this->db->fetchValue(
            "SELECT COUNT(*) FROM phrases WHERE user_id = ?",
            [$userId]
        );

        // Active phrases
        $stats['active_phrases'] = $this->db->fetchValue(
            "SELECT COUNT(*) FROM phrases WHERE user_id = ? AND is_active = 1",
            [$userId]
        );

        // Total scans
        $stats['total_scans'] = $this->db->fetchValue(
            "SELECT COUNT(*) FROM scan_history WHERE user_id = ?",
            [$userId]
        );

        // Total results
        $stats['total_results'] = $this->db->fetchValue(
            "SELECT COUNT(*) FROM results r
             INNER JOIN phrases p ON r.phrase_id = p.id
             WHERE p.user_id = ?",
            [$userId]
        );

        // Last scan
        $stats['last_scan'] = $this->db->fetchOne(
            "SELECT * FROM scan_history
             WHERE user_id = ?
             ORDER BY started_at DESC
             LIMIT 1",
            [$userId]
        );

        return $stats;
    }

    /**
     * Delete user
     */
    public function delete($id) {
        // This will cascade delete phrases, scans, and results due to foreign keys
        return $this->db->execute(
            "DELETE FROM users WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Deactivate user
     */
    public function deactivate($id) {
        return $this->db->execute(
            "UPDATE users SET is_active = 0 WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Activate user
     */
    public function activate($id) {
        return $this->db->execute(
            "UPDATE users SET is_active = 1 WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Check if user has reached phrase limit
     */
    public function hasReachedPhraseLimit($userId) {
        $user = $this->getById($userId);
        if (!$user) {
            return true;
        }

        $currentCount = $this->db->fetchValue(
            "SELECT COUNT(*) FROM phrases WHERE user_id = ?",
            [$userId]
        );

        return $currentCount >= $user['max_phrases'];
    }

    /**
     * Get user count
     */
    public function getCount() {
        return $this->db->fetchValue("SELECT COUNT(*) FROM users");
    }
}
