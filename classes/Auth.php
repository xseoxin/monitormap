<?php
/**
 * Auth Class
 *
 * Handles user authentication and authorization
 */

class Auth {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require APP_ROOT . '/config/app.php';
    }

    /**
     * Register a new user
     */
    public function register($username, $email, $password, $role = 'user') {
        // Validate input
        $errors = [];

        if (!Validator::username($username)) {
            $errors[] = 'Invalid username format';
        }

        if (!Validator::email($email)) {
            $errors[] = 'Invalid email format';
        }

        if (!Validator::password($password)) {
            $errors[] = 'Password must be at least ' . $this->config['password_min_length'] . ' characters';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if username exists
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );

        if ($existingUser) {
            Logger::warning('Registration attempt with existing credentials', [
                'username' => $username,
                'email' => $email
            ], 'AUTH');
            return ['success' => false, 'errors' => ['Username or email already exists']];
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Generate API token
        $apiToken = bin2hex(random_bytes(32));

        // Insert user
        try {
            $userId = $this->db->insert(
                "INSERT INTO users (username, email, password_hash, role, api_token, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$username, $email, $passwordHash, $role, $apiToken]
            );

            Logger::info('User registered successfully', [
                'user_id' => $userId,
                'username' => $username,
                'email' => $email,
                'role' => $role
            ], 'AUTH');

            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            Logger::error('Registration failed', [
                'username' => $username,
                'error' => $e->getMessage()
            ], 'AUTH');
            return ['success' => false, 'errors' => ['Registration failed']];
        }
    }

    /**
     * Login user
     */
    public function login($username, $password, $rememberMe = false) {
        // Check rate limiting
        if (!$this->checkRateLimit($username)) {
            Logger::warning('Login rate limit exceeded', ['username' => $username], 'SECURITY');
            return ['success' => false, 'error' => 'Too many login attempts. Please try again later.'];
        }

        // Get user
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordFailedLogin($username);
            Logger::warning('Failed login attempt', ['username' => $username], 'AUTH');
            return ['success' => false, 'error' => 'Invalid username or password'];
        }

        // Clear failed login attempts
        $this->clearFailedLogins($username);

        // Update last login
        $this->db->execute(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$user['id']]
        );

        // Create session
        $this->createSession($user);

        Logger::info('User logged in successfully', [
            'user_id' => $user['id'],
            'username' => $user['username']
        ], 'AUTH');

        return ['success' => true, 'user' => $user];
    }

    /**
     * Logout user
     */
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;

        Logger::info('User logged out', ['user_id' => $userId], 'AUTH');

        session_unset();
        session_destroy();

        // Start new session for flash messages
        session_start();
    }

    /**
     * Check if user is authenticated
     */
    public static function check() {
        return isset($_SESSION['user_id']) && isset($_SESSION['authenticated']);
    }

    /**
     * Get current user
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        ];
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return self::check() && $_SESSION['role'] === 'admin';
    }

    /**
     * Check rate limiting
     */
    private function checkRateLimit($username) {
        $cacheKey = 'login_attempts_' . md5($username . '_' . $this->getIpAddress());
        $attemptsFile = APP_ROOT . '/storage/temp/' . $cacheKey;

        if (!file_exists($attemptsFile)) {
            return true;
        }

        $data = json_decode(file_get_contents($attemptsFile), true);
        $attempts = $data['attempts'] ?? 0;
        $lastAttempt = $data['last_attempt'] ?? 0;

        // Reset if timeout passed
        if (time() - $lastAttempt > $this->config['login_timeout']) {
            unlink($attemptsFile);
            return true;
        }

        return $attempts < $this->config['max_login_attempts'];
    }

    /**
     * Record failed login
     */
    private function recordFailedLogin($username) {
        $cacheKey = 'login_attempts_' . md5($username . '_' . $this->getIpAddress());
        $attemptsFile = APP_ROOT . '/storage/temp/' . $cacheKey;

        $attempts = 1;
        if (file_exists($attemptsFile)) {
            $data = json_decode(file_get_contents($attemptsFile), true);
            $attempts = ($data['attempts'] ?? 0) + 1;
        }

        file_put_contents($attemptsFile, json_encode([
            'attempts' => $attempts,
            'last_attempt' => time()
        ]));
    }

    /**
     * Clear failed logins
     */
    private function clearFailedLogins($username) {
        $cacheKey = 'login_attempts_' . md5($username . '_' . $this->getIpAddress());
        $attemptsFile = APP_ROOT . '/storage/temp/' . $cacheKey;

        if (file_exists($attemptsFile)) {
            unlink($attemptsFile);
        }
    }

    /**
     * Create user session
     */
    private function createSession($user) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $this->getIpAddress();
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token']) || time() - ($_SESSION['csrf_token_time'] ?? 0) > 3600) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get IP address
     */
    private function getIpAddress() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Authenticate API request
     */
    public static function authenticateApi($token) {
        if (empty($token)) {
            return null;
        }

        $db = Database::getInstance();
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE api_token = ? AND is_active = 1",
            [$token]
        );

        if ($user) {
            Logger::debug('API request authenticated', [
                'user_id' => $user['id'],
                'username' => $user['username']
            ], 'API');
        }

        return $user;
    }
}
