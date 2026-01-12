<?php
/**
 * Proxies Management Page
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentUser = Auth::user();
$isAdmin = Auth::isAdmin();

$proxyModel = new Proxy();
$proxies = $proxyModel->getAll();
$stats = $proxyModel->getStatistics();

$pageTitle = 'Proxy Management';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Proxy Management</h1>
    <?php if ($isAdmin): ?>
    <div class="page-actions">
        <a href="/proxy_add.php" class="btn btn-primary">➕ Add Proxy</a>
        <a href="/proxy_import.php" class="btn btn-secondary">📤 Import CSV</a>
    </div>
    <?php endif; ?>
</div>

<?php if (!$isAdmin): ?>
<div class="alert alert-info" style="margin-bottom: 20px;">
    <strong>Note:</strong> Proxy management is available for administrators. Contact your admin to add or modify proxies.
</div>
<?php endif; ?>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon">🌐</div>
        <div class="stat-content">
            <div class="stat-label">Total Proxies</div>
            <div class="stat-value"><?= $stats['total_proxies'] ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-content">
            <div class="stat-label">Active Proxies</div>
            <div class="stat-value"><?= $stats['active_proxies'] ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">⚡</div>
        <div class="stat-content">
            <div class="stat-label">Avg Response Time</div>
            <div class="stat-value"><?= $stats['avg_response_time'] ? round($stats['avg_response_time']) . 'ms' : 'N/A' ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
            <div class="stat-label">Avg Success Rate</div>
            <div class="stat-value"><?= $stats['avg_success_rate'] ? round($stats['avg_success_rate'], 1) . '%' : 'N/A' ?></div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Proxy URL</th>
                <th>Type</th>
                <th>Country</th>
                <th>Status</th>
                <th>Response Time</th>
                <th>Success Rate</th>
                <th>Last Used</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($proxies as $proxy): ?>
            <tr>
                <td><code><?= e($proxy['proxy_url']) ?></code></td>
                <td><?= e(strtoupper($proxy['type'])) ?></td>
                <td><?= e($proxy['country'] ?: '-') ?></td>
                <td>
                    <?php if ($proxy['is_active']): ?>
                    <span class="badge badge-success">Active</span>
                    <?php else: ?>
                    <span class="badge badge-danger">Inactive</span>
                    <?php endif; ?>
                </td>
                <td><?= $proxy['response_time_ms'] ? $proxy['response_time_ms'] . 'ms' : '-' ?></td>
                <td><?= $proxy['success_rate'] ? round($proxy['success_rate'], 1) . '%' : '-' ?></td>
                <td><?= $proxy['last_used_at'] ? timeAgo($proxy['last_used_at']) : 'Never' ?></td>
                <td>
                    <a href="/proxy_test.php?id=<?= $proxy['id'] ?>&csrf_token=<?= Auth::generateCsrfToken() ?>" class="btn btn-sm btn-secondary">Test</a>
                    <a href="/proxy_delete.php?id=<?= $proxy['id'] ?>&csrf_token=<?= Auth::generateCsrfToken() ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this proxy?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
