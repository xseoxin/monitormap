<?php
/**
 * Register Page
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if (Auth::check()) {
    redirect('/index.php');
}

$auth = new Auth();
$errors = [];
$success = false;

// Handle registration form submission
if (isPost()) {
    validateCsrf();

    $username = input('username');
    $email = input('email');
    $password = input('password');
    $passwordConfirm = input('password_confirm');

    // Validate password confirmation
    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match';
    }

    if (empty($errors)) {
        $result = $auth->register($username, $email, $password);

        if ($result['success']) {
            $success = true;

            // Send notification to admins
            $notification = new Notification();
            $notification->userRegistered($result['user_id']);
        } else {
            $errors = $result['errors'];
        }
    }
}

$pageTitle = 'Register';
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
                <p>Create your account</p>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> Your account has been created. <a href="/login.php">Sign in now</a>
            </div>
            <?php else: ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" action="/register.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus value="<?= e(input('username', '')) ?>" pattern="[a-zA-Z0-9_]{3,50}" title="3-50 characters, alphanumeric and underscores only">
                    <small>3-50 characters, alphanumeric and underscores only</small>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?= e(input('email', '')) ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="<?= Config::get('password_min_length', 8, 'app') ?>">
                    <small>At least <?= Config::get('password_min_length', 8, 'app') ?> characters</small>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>

            <?php endif; ?>

            <div class="auth-footer">
                <p>Already have an account? <a href="/login.php">Sign in</a></p>
            </div>
        </div>
    </div>
</body>
</html>
