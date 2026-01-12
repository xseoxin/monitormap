<?php
/**
 * Header Template
 *
 * Common header for all pages
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

$pageTitle = $pageTitle ?? Config::get('name', 'Google Maps Monitor', 'app');
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($pageTitle) ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/forms.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/tables.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/map.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/responsive.css') ?>">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= asset('assets/images/logo.png') ?>">
</head>
<body>
    <div class="app-container">
        <?php if (Auth::check()): ?>
        <!-- Navigation -->
        <nav class="navbar">
            <div class="navbar-brand">
                <a href="/" class="brand-link">
                    <img src="<?= asset('assets/images/logo.png') ?>" alt="Logo" class="brand-logo">
                    <?= e(Config::get('name', 'Google Maps Monitor', 'app')) ?>
                </a>
            </div>

            <div class="navbar-menu">
                <a href="/index.php" class="nav-link <?= isPage('index.php') ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="/phrases.php" class="nav-link <?= isPage('phrases.php') || isPage('phrase_add.php') || isPage('phrase_edit.php') ? 'active' : '' ?>">
                    <span class="nav-icon">🔍</span> Phrases
                </a>
                <a href="/results.php" class="nav-link <?= isPage('results.php') ? 'active' : '' ?>">
                    <span class="nav-icon">📍</span> Results
                </a>
                <a href="/results_map.php" class="nav-link <?= isPage('results_map.php') ? 'active' : '' ?>">
                    <span class="nav-icon">🗺️</span> Map
                </a>
                <a href="/history.php" class="nav-link <?= isPage('history.php') ? 'active' : '' ?>">
                    <span class="nav-icon">📜</span> History
                </a>
                <a href="/proxies.php" class="nav-link <?= isPage('proxies.php') ? 'active' : '' ?>">
                    <span class="nav-icon">🌐</span> Proxies
                </a>
                <?php if (Auth::isAdmin()): ?>
                <a href="/settings.php" class="nav-link <?= isPage('settings.php') ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span> Settings
                </a>
                <?php endif; ?>
            </div>

            <div class="navbar-user">
                <div class="user-dropdown">
                    <button class="user-button">
                        <span class="user-icon">👤</span>
                        <?= e($currentUser['username']) ?>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="user-menu">
                        <div class="user-info">
                            <div class="user-name"><?= e($currentUser['username']) ?></div>
                            <div class="user-email"><?= e($currentUser['email']) ?></div>
                            <div class="user-role"><?= e(ucfirst($currentUser['role'])) ?></div>
                        </div>
                        <div class="menu-divider"></div>
                        <a href="/settings.php" class="menu-item">
                            <span class="menu-icon">⚙️</span> Settings
                        </a>
                        <a href="/logout.php" class="menu-item">
                            <span class="menu-icon">🚪</span> Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        <?php endif; ?>

        <!-- Flash Messages -->
        <?php if (hasFlash()): ?>
        <div class="flash-messages">
            <?php foreach (getFlash() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
                <?= e($flash['message']) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="main-content">
