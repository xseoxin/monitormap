<?php
/**
 * Database Class
 *
 * Handles database connections and queries using PDO
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $config = null;

    /**
     * Private constructor - Singleton pattern
     */
    private function __construct() {
        $this->config = require APP_ROOT . '/config/database.php';
        $this->connect();
    }

    /**
     * Get database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Connect to database
     */
    private function connect() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            Logger::critical('Database connection failed', [
                'error' => $e->getMessage(),
                'host' => $this->config['host']
            ]);
            throw new Exception('Database connection failed');
        }
    }

    /**
     * Get PDO connection
     */
    public function getConnection() {
        // Check if connection is alive
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Execute a query with parameters
     */
    public function query($sql, $params = []) {
        $startTime = microtime(true);

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);

            $executionTime = microtime(true) - $startTime;

            // Log slow queries
            if ($executionTime > Config::get('slow_query_threshold', 1.0)) {
                Logger::warning('Slow query detected', [
                    'sql' => $sql,
                    'params' => $params,
                    'execution_time' => $executionTime
                ]);
            }

            return $stmt;
        } catch (PDOException $e) {
            Logger::error('Query failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch single value
     */
    public function fetchValue($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ? reset($result) : null;
    }

    /**
     * Execute INSERT/UPDATE/DELETE query
     */
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Insert and return last insert ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }

    /**
     * Check if in transaction
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
