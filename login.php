<?php
/**
 * Login Page
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if (Auth::check()) {
    redirect('/index.php');
}

$auth = new Auth();
$error = null;
$expired = isset($_GET['expired']);

// Handle login form submission
if (isPost()) {
    validateCsrf();

    $username = input('username');
    $password = input('password');
    $rememberMe = input('remember_me') === '1';

    $result = $auth->login($username, $password, $rememberMe);

    if ($result['success']) {
        // Redirect to intended URL or dashboard
        $redirectUrl = $_SESSION['intended_url'] ?? '/index.php';
        unset($_SESSION['intended_url']);
        redirect($redirectUrl);
    } else {
        $error = $result['error'];
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(Config::get('name', 'Google Maps Monitor', 'app')) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/forms.css') ?>">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1><?= e(Config::get('name', 'Google Maps Monitor', 'app')) ?></h1>
                <p>Sign in to your account</p>
            </div>

            <?php if ($expired): ?>
            <div class="alert alert-warning">
                Your session has expired. Please log in again.
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/login.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus value="<?= e(input('username', '')) ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" id="remember_me" name="remember_me" value="1">
                    <label for="remember_me">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="/register.php">Sign up</a></p>
            </div>
        </div>
    </div>
</body>
</html>
