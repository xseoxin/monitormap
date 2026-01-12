<?php
/**
 * Scan History Page
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentUser = Auth::user();
$userId = $currentUser['id'];

$scanHistory = new ScanHistory();

$page = max(1, (int)input('page', 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$scans = $scanHistory->getByUser($userId, $perPage, $offset);

$pageTitle = 'Scan History';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Scan History</h1>
</div>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Phrase</th>
                <th>Status</th>
                <th>Started</th>
                <th>Completed</th>
                <th>Duration</th>
                <th>Grid Points</th>
                <th>Results Found</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($scans)): ?>
            <tr>
                <td colspan="9" class="text-center">No scan history</td>
            </tr>
            <?php else: ?>
            <?php foreach ($scans as $scan): ?>
            <tr>
                <td>#<?= $scan['id'] ?></td>
                <td><strong><?= e($scan['phrase']) ?></strong></td>
                <td><?= statusBadge($scan['status']) ?></td>
                <td><?= date('Y-m-d H:i', strtotime($scan['started_at'])) ?></td>
                <td><?= $scan['completed_at'] ? date('Y-m-d H:i', strtotime($scan['completed_at'])) : '-' ?></td>
                <td><?= $scan['duration_seconds'] ? formatDuration($scan['duration_seconds']) : '-' ?></td>
                <td><?= $scan['scanned_points'] ?> / <?= $scan['total_grid_points'] ?></td>
                <td><?= formatNumber($scan['results_found']) ?></td>
                <td>
                    <a href="/history_details.php?id=<?= $scan['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
