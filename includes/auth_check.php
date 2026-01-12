<?php
/**
 * Auth Check
 *
 * Protect pages that require authentication
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Check if user is authenticated
if (!Auth::check()) {
    // Save intended URL for redirect after login
    $_SESSION['intended_url'] = currentUrl();

    // Redirect to login
    if (isAjaxRequest()) {
        errorResponse(MSG_ERROR_UNAUTHORIZED, 401);
    } else {
        setFlash('error', 'Please log in to continue');
        redirect('/login.php');
    }
}

// Check if user session is still valid
$user = (new User())->getById($_SESSION['user_id']);

if (!$user || !$user['is_active']) {
    $auth = new Auth();
    $auth->logout();

    if (isAjaxRequest()) {
        errorResponse(MSG_ERROR_UNAUTHORIZED, 401);
    } else {
        setFlash('error', 'Your account has been deactivated');
        redirect('/login.php');
    }
}
