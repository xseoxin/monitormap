<?php
/**
 * Logout Page
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';

// Logout user
$auth = new Auth();
$auth->logout();

// Redirect to login page
setFlash('success', MSG_SUCCESS_LOGOUT);
redirect('/login.php');
