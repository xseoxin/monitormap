<?php
/**
 * Dashboard Page
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentUser = Auth::user();
$userId = $currentUser['id'];

// Get statistics
$phraseModel = new Phrase();
$resultModel = new Result();
$scanHistory = new ScanHistory();

// Total phrases
$totalPhrases = $phraseModel->getCountByUser($userId);
$activePhrases = count($phraseModel->getByUser($userId, true));

// Recent scans
$recentScans = $scanHistory->getRecent(10, $userId);

// Total results (last 7 days)
$totalResults = $resultModel->getCount([
    'user_id' => $userId,
    'date_from' => date('Y-m-d', strtotime('-7 days'))
]);

// Average rating
$stats = $resultModel->getStatistics($userId);
$avgRating = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 0;

// Recent results for map
$recentResults = $resultModel->getForMap([
    'user_id' => $userId,
    'limit' => 100
]);

// Active scans
$activeScans = $scanHistory->getRunning();
$userActiveScans = array_filter($activeScans, function($scan) use ($userId) {
    return $scan['user_id'] == $userId;
});

// Get active phrases with next scan countdown
$activePhrasesList = $phraseModel->getByUser($userId, true, 10);

$pageTitle = 'Dashboard';
$pageScripts = ['assets/js/map-visualizer.js', 'assets/js/charts.js'];

include __DIR__ . '/includes/header.php';
?>

<div class="dashboard">
    <div class="page-header">
        <h1>Dashboard</h1>
        <div class="page-actions">
            <a href="/phrase_add.php" class="btn btn-primary">
                <span class="btn-icon">➕</span> Add Phrase
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🔍</div>
            <div class="stat-content">
                <div class="stat-label">Total Phrases</div>
                <div class="stat-value"><?= formatNumber($totalPhrases) ?></div>
                <div class="stat-sublabel"><?= $activePhrases ?> active</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-label">Active Scans</div>
                <div class="stat-value"><?= count($userActiveScans) ?></div>
                <div class="stat-sublabel">Currently running</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📍</div>
            <div class="stat-content">
                <div class="stat-label">Results (7 days)</div>
                <div class="stat-value"><?= formatNumber($totalResults) ?></div>
                <div class="stat-sublabel">New findings</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-content">
                <div class="stat-label">Avg Rating</div>
                <div class="stat-value"><?= $avgRating ?></div>
                <div class="stat-sublabel"><?= ratingStars($avgRating) ?></div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">
        <!-- Map Overview -->
        <div class="dashboard-section map-section">
            <div class="section-header">
                <h2>Recent Results Map</h2>
                <a href="/results_map.php" class="section-link">View Full Map →</a>
            </div>
            <div id="dashboard-map" style="height: 400px;"></div>
        </div>

        <!-- Recent Scans -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Scans</h2>
                <a href="/history.php" class="section-link">View All →</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Phrase</th>
                            <th>Status</th>
                            <th>Results</th>
                            <th>Duration</th>
                            <th>Started</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentScans)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No scans yet</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentScans as $scan): ?>
                        <tr>
                            <td>
                                <a href="/history_details.php?id=<?= $scan['id'] ?>">
                                    <?= e($scan['phrase']) ?>
                                </a>
                            </td>
                            <td><?= statusBadge($scan['status']) ?></td>
                            <td><?= formatNumber($scan['results_found']) ?></td>
                            <td><?= $scan['duration_seconds'] ? formatDuration($scan['duration_seconds']) : '-' ?></td>
                            <td><?= timeAgo($scan['started_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Active Phrases -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Active Phrases</h2>
                <a href="/phrases.php" class="section-link">View All →</a>
            </div>
            <div class="phrases-list">
                <?php if (empty($activePhrasesList)): ?>
                <div class="empty-state">
                    <p>No active phrases</p>
                    <a href="/phrase_add.php" class="btn btn-sm btn-primary">Add Your First Phrase</a>
                </div>
                <?php else: ?>
                <?php foreach ($activePhrasesList as $phrase): ?>
                <div class="phrase-item">
                    <div class="phrase-content">
                        <div class="phrase-title"><?= e($phrase['phrase']) ?></div>
                        <div class="phrase-meta">
                            📍 <?= formatCoordinates($phrase['location_lat'], $phrase['location_lng']) ?> •
                            🎯 <?= $phrase['radius_km'] ?>km •
                            📊 <?= $phrase['grid_size'] ?>
                        </div>
                    </div>
                    <div class="phrase-actions">
                        <?php if ($phrase['next_scan_at']): ?>
                        <div class="next-scan">
                            Next scan: <?= timeAgo($phrase['next_scan_at']) ?>
                        </div>
                        <?php endif; ?>
                        <a href="/phrase_scan_now.php?id=<?= $phrase['id'] ?>&csrf_token=<?= Auth::generateCsrfToken() ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Start scan now?')">
                            Scan Now
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Map initialization
document.addEventListener('DOMContentLoaded', function() {
    const mapData = <?= json_encode($recentResults) ?>;

    if (mapData.length > 0) {
        const map = L.map('dashboard-map').setView([mapData[0].latitude, mapData[0].longitude], 10);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add markers
        mapData.forEach(function(result) {
            const rating = parseFloat(result.rating) || 0;
            const markerColor = rating >= 4 ? 'green' : (rating >= 3 ? 'orange' : 'red');

            const marker = L.circleMarker([result.latitude, result.longitude], {
                radius: 6,
                fillColor: markerColor,
                color: '#fff',
                weight: 1,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map);

            marker.bindPopup(`
                <strong>${result.place_name}</strong><br>
                ${result.address || ''}<br>
                Rating: ${result.rating || 'N/A'} (${result.reviews_count || 0} reviews)
            `);
        });

        // Fit bounds to show all markers
        const bounds = L.latLngBounds(mapData.map(r => [r.latitude, r.longitude]));
        map.fitBounds(bounds);
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
