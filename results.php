<?php
/**
 * Results Page
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentUser = Auth::user();
$userId = $currentUser['id'];

$resultModel = new Result();

// Build filters
$filters = ['user_id' => $userId];

if (input('phrase_id')) {
    $filters['phrase_id'] = (int)input('phrase_id');
}
if (input('date_from')) {
    $filters['date_from'] = input('date_from');
}
if (input('date_to')) {
    $filters['date_to'] = input('date_to');
}
if (input('rating_min')) {
    $filters['rating_min'] = (float)input('rating_min');
}
if (input('search')) {
    $filters['search'] = input('search');
}

// Get results with pagination
$page = max(1, (int)input('page', 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$results = $resultModel->search($filters, $perPage, $offset);
$totalResults = $resultModel->getCount($filters);
$pagination = paginate($totalResults, $page, $perPage);

// Get user phrases for filter
$phraseModel = new Phrase();
$userPhrases = $phraseModel->getByUser($userId);

$pageTitle = 'Results';
$pageScripts = ['assets/js/results-table.js'];
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Scan Results</h1>
    <div class="page-actions">
        <a href="/results_map.php" class="btn btn-primary">🗺️ View Map</a>
        <a href="/results_export.php<?= !empty($filters) ? '?' . http_build_query($filters) : '' ?>" class="btn btn-secondary">📥 Export</a>
    </div>
</div>

<!-- Filters -->
<div class="filters" style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <form method="GET" action="/results.php">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label>Search</label>
                <input type="text" name="search" class="form-control" value="<?= e(input('search', '')) ?>" placeholder="Place name or address">
            </div>

            <div class="form-group">
                <label>Phrase</label>
                <select name="phrase_id" class="form-control">
                    <option value="">All Phrases</option>
                    <?php foreach ($userPhrases as $phrase): ?>
                    <option value="<?= $phrase['id'] ?>" <?= input('phrase_id') == $phrase['id'] ? 'selected' : '' ?>>
                        <?= e($phrase['phrase']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e(input('date_from', '')) ?>">
            </div>

            <div class="form-group">
                <label>Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?= e(input('date_to', '')) ?>">
            </div>

            <div class="form-group">
                <label>Min Rating</label>
                <select name="rating_min" class="form-control">
                    <option value="">All Ratings</option>
                    <option value="4" <?= input('rating_min') == '4' ? 'selected' : '' ?>>4+ Stars</option>
                    <option value="3" <?= input('rating_min') == '3' ? 'selected' : '' ?>>3+ Stars</option>
                    <option value="2" <?= input('rating_min') == '2' ? 'selected' : '' ?>>2+ Stars</option>
                </select>
            </div>

            <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="/results.php" class="btn btn-secondary">Clear</a>
            </div>
        </div>
    </form>
</div>

<!-- Results Table -->
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
                <th>Phrase</th>
                <th>Found</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
            <tr>
                <td colspan="8" class="text-center">No results found</td>
            </tr>
            <?php else: ?>
            <?php foreach ($results as $result): ?>
            <tr>
                <td><strong><?= e($result['place_name']) ?></strong></td>
                <td><?= e($result['address'] ?: '-') ?></td>
                <td>
                    <?php if ($result['rating']): ?>
                        <?= ratingStars($result['rating']) ?>
                        <span style="margin-left: 5px;"><?= number_format($result['rating'], 1) ?></span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?= formatNumber($result['reviews_count']) ?></td>
                <td><?= e($result['phone'] ?: '-') ?></td>
                <td>
                    <?php if ($result['website']): ?>
                    <a href="<?= e($result['website']) ?>" target="_blank" rel="noopener">Link</a>
                    <?php else: ?>
                    -
                    <?php endif; ?>
                </td>
                <td><?= e($result['phrase']) ?></td>
                <td><?= timeAgo($result['found_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= paginationLinks($pagination, '/results.php' . (http_build_query(array_filter(inputs())) ? '?' . http_build_query(array_filter(inputs())) : '')) ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
