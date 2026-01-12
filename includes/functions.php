<?php
/**
 * Helper Functions
 *
 * Global utility functions
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Redirect to a URL
 */
function redirect($url, $statusCode = 302) {
    header("Location: {$url}", true, $statusCode);
    exit;
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash messages
 */
function getFlash() {
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Check if flash message exists
 */
function hasFlash() {
    return !empty($_SESSION['flash']);
}

/**
 * Escape HTML output
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get current URL
 */
function currentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Check if current page matches
 */
function isPage($page) {
    return basename($_SERVER['PHP_SELF']) === $page;
}

/**
 * Format file size
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Format duration in seconds
 */
function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }
}

/**
 * Format relative time
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

/**
 * Generate random string
 */
function randomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get rating stars HTML
 */
function ratingStars($rating, $maxStars = 5) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = $maxStars - $fullStars - $halfStar;

    $html = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="star star-full">★</i>';
    }
    if ($halfStar) {
        $html .= '<i class="star star-half">★</i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="star star-empty">☆</i>';
    }

    return $html;
}

/**
 * Get rating color class
 */
function ratingColor($rating) {
    if ($rating >= 4.5) {
        return 'rating-excellent';
    } elseif ($rating >= 4.0) {
        return 'rating-good';
    } elseif ($rating >= 3.0) {
        return 'rating-average';
    } else {
        return 'rating-poor';
    }
}

/**
 * Get status badge HTML
 */
function statusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'running' => '<span class="badge badge-info">Running</span>',
        'completed' => '<span class="badge badge-success">Completed</span>',
        'failed' => '<span class="badge badge-danger">Failed</span>'
    ];

    return $badges[$status] ?? '<span class="badge badge-secondary">' . e($status) . '</span>';
}

/**
 * JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Error response
 */
function errorResponse($message, $statusCode = 400) {
    jsonResponse([
        'success' => false,
        'error' => $message
    ], $statusCode);
}

/**
 * Success response
 */
function successResponse($data = [], $message = null) {
    $response = ['success' => true];

    if ($message) {
        $response['message'] = $message;
    }

    if (!empty($data)) {
        $response['data'] = $data;
    }

    jsonResponse($response);
}

/**
 * Validate CSRF token
 */
function validateCsrf() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

    if (!Auth::verifyCsrfToken($token)) {
        if (isAjaxRequest()) {
            errorResponse(MSG_ERROR_CSRF, 403);
        } else {
            setFlash('error', MSG_ERROR_CSRF);
            redirect('/');
        }
    }
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Check if request is POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Get request input
 */
function input($key, $default = null) {
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/**
 * Get all request inputs
 */
function inputs() {
    return array_merge($_GET, $_POST);
}

/**
 * Get current application URL (auto-detect)
 */
function appUrl() {
    static $cachedUrl = null;

    if ($cachedUrl !== null) {
        return $cachedUrl;
    }

    // Try to get from config first
    $configUrl = Config::get('url', null, 'app');
    if ($configUrl && $configUrl !== 'http://localhost') {
        $cachedUrl = rtrim($configUrl, '/');
        return $cachedUrl;
    }

    // Auto-detect from server variables
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                 (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                 (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                 ? 'https' : 'http';

    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

    // Remove port if it's default
    if (($protocol === 'http' && strpos($host, ':80') !== false) ||
        ($protocol === 'https' && strpos($host, ':443') !== false)) {
        $host = preg_replace('/:\d+$/', '', $host);
    }

    $cachedUrl = $protocol . '://' . $host;
    return $cachedUrl;
}

/**
 * Asset URL helper
 */
function asset($path) {
    // Use relative path for assets (works everywhere)
    return '/' . ltrim($path, '/');
}

/**
 * URL helper
 */
function url($path = '') {
    // Use relative path
    return '/' . ltrim($path, '/');
}

/**
 * Truncate string
 */
function truncate($string, $length = 100, $append = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }

    return substr($string, 0, $length) . $append;
}

/**
 * Format number
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, '.', ',');
}

/**
 * Format coordinates
 */
function formatCoordinates($lat, $lng) {
    return number_format($lat, 6) . ', ' . number_format($lng, 6);
}

/**
 * Get pagination data
 */
function paginate($total, $currentPage = 1, $perPage = 50) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));

    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'from' => ($currentPage - 1) * $perPage + 1,
        'to' => min($currentPage * $perPage, $total)
    ];
}

/**
 * Generate pagination HTML
 */
function paginationLinks($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }

    $html = '<div class="pagination">';

    // Previous button
    if ($pagination['current_page'] > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($pagination['current_page'] - 1) . '" class="page-link">&laquo; Previous</a>';
    }

    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);

    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . '?page=1" class="page-link">1</a>';
        if ($start > 2) {
            $html .= '<span class="page-ellipsis">...</span>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $pagination['current_page'] ? ' active' : '';
        $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="page-link' . $active . '">' . $i . '</a>';
    }

    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) {
            $html .= '<span class="page-ellipsis">...</span>';
        }
        $html .= '<a href="' . $baseUrl . '?page=' . $pagination['total_pages'] . '" class="page-link">' . $pagination['total_pages'] . '</a>';
    }

    // Next button
    if ($pagination['current_page'] < $pagination['total_pages']) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($pagination['current_page'] + 1) . '" class="page-link">Next &raquo;</a>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * Debug helper
 */
function dd(...$vars) {
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die();
}

/**
 * Dump variable
 */
function dump(...$vars) {
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
}
