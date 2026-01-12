<?php
/**
 * Phrases List Page
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentUser = Auth::user();
$userId = $currentUser['id'];

$phraseModel = new Phrase();

// Get phrases with pagination
$page = max(1, (int)input('page', 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$phrases = $phraseModel->getByUser($userId, false, $perPage, $offset);
$totalPhrases = $phraseModel->getCountByUser($userId);
$pagination = paginate($totalPhrases, $page, $perPage);

$pageTitle = 'Phrases';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>My Phrases</h1>
    <div class="page-actions">
        <a href="/phrase_add.php" class="btn btn-primary">➕ Add Phrase</a>
    </div>
</div>

<div class="table-responsive">
    <table class="table" id="phrases-table">
        <thead>
            <tr>
                <th>Phrase</th>
                <th>Location</th>
                <th>Radius</th>
                <th>Grid</th>
                <th>Status</th>
                <th>Next Scan</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($phrases as $phrase): ?>
            <tr>
                <td><strong><?= e($phrase['phrase']) ?></strong></td>
                <td><?= formatCoordinates($phrase['location_lat'], $phrase['location_lng']) ?></td>
                <td><?= $phrase['radius_km'] ?>km</td>
                <td><?= $phrase['grid_size'] ?></td>
                <td>
                    <?php if ($phrase['is_active']): ?>
                    <span class="badge badge-success">Active</span>
                    <?php else: ?>
                    <span class="badge badge-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td><?= $phrase['next_scan_at'] ? timeAgo($phrase['next_scan_at']) : 'Manual' ?></td>
                <td>
                    <a href="/phrase_edit.php?id=<?= $phrase['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                    <a href="/phrase_scan_now.php?id=<?= $phrase['id'] ?>&csrf_token=<?= Auth::generateCsrfToken() ?>" class="btn btn-sm btn-primary" onclick="return confirm('Start scan?')">Scan</a>
                    <a href="/phrase_delete.php?id=<?= $phrase['id'] ?>&csrf_token=<?= Auth::generateCsrfToken() ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this phrase?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= paginationLinks($pagination, '/phrases.php') ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
