<?php
/**
 * Scan History Details Page
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentUser = Auth::user();
$userId = $currentUser['id'];

$scanId = (int)input('id');

$scanHistory = new ScanHistory();
$resultModel = new Result();

$scan = $scanHistory->getById($scanId);

if (!$scan || $scan['user_id'] != $userId) {
    setFlash('error', 'Scan not found');
    redirect('/history.php');
}

$results = $resultModel->getByScan($scanId);

$pageTitle = 'Scan Details #' . $scanId;
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Scan Details #<?= $scanId ?></h1>
    <div class="page-actions">
        <a href="/history.php" class="btn btn-secondary">← Back to History</a>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon">🔍</div>
        <div class="stat-content">
            <div class="stat-label">Phrase</div>
            <div class="stat-value" style="font-size: 18px;"><?= e($scan['phrase']) ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">⏱️</div>
        <div class="stat-content">
            <div class="stat-label">Duration</div>
            <div class="stat-value"><?= $scan['duration_seconds'] ? formatDuration($scan['duration_seconds']) : '-' ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📍</div>
        <div class="stat-content">
            <div class="stat-label">Results Found</div>
            <div class="stat-value"><?= formatNumber($scan['results_found']) ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
            <div class="stat-label">Grid Progress</div>
            <div class="stat-value"><?= $scan['scanned_points'] ?> / <?= $scan['total_grid_points'] ?></div>
        </div>
    </div>
</div>

<!-- Scan Info -->
<div style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <h3>Scan Information</h3>
    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <td style="padding: 8px;"><strong>Status:</strong></td>
            <td style="padding: 8px;"><?= statusBadge($scan['status']) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px;"><strong>Started:</strong></td>
            <td style="padding: 8px;"><?= date('Y-m-d H:i:s', strtotime($scan['started_at'])) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px;"><strong>Completed:</strong></td>
            <td style="padding: 8px;"><?= $scan['completed_at'] ? date('Y-m-d H:i:s', strtotime($scan['completed_at'])) : '-' ?></td>
        </tr>
        <tr>
            <td style="padding: 8px;"><strong>Location:</strong></td>
            <td style="padding: 8px;"><?= formatCoordinates($scan['location_lat'], $scan['location_lng']) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px;"><strong>Radius:</strong></td>
            <td style="padding: 8px;"><?= $scan['radius_km'] ?> km</td>
        </tr>
        <tr>
            <td style="padding: 8px;"><strong>Grid Size:</strong></td>
            <td style="padding: 8px;"><?= $scan['grid_size'] ?> (<?= $scan['total_grid_points'] ?> points)</td>
        </tr>
        <?php if ($scan['error_message']): ?>
        <tr>
            <td style="padding: 8px;"><strong>Error:</strong></td>
            <td style="padding: 8px; color: red;"><?= e($scan['error_message']) ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<!-- Results -->
<h3>Results (<?= count($results) ?>)</h3>
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Place Name</th>
                <th>Address</th>
                <th>Rating</th>
                <th>Reviews</th>
                <th>Phone</th>
                <th>Website</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
            <tr>
                <td colspan="6" class="text-center">No results found in this scan</td>
            </tr>
            <?php else: ?>
            <?php foreach ($results as $result): ?>
            <tr>
                <td><strong><?= e($result['place_name']) ?></strong></td>
                <td><?= e($result['address'] ?: '-') ?></td>
                <td>
                    <?php if ($result['rating']): ?>
                        <?= ratingStars($result['rating']) ?>
                        <?= number_format($result['rating'], 1) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?= formatNumber($result['reviews_count']) ?></td>
                <td><?= e($result['phone'] ?: '-') ?></td>
                <td>
                    <?php if ($result['website']): ?>
                    <a href="<?= e($result['website']) ?>" target="_blank">Link</a>
                    <?php else: ?>
                    -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
