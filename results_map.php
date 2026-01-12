<?php
/**
 * Results Map Page
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentUser = Auth::user();
$userId = $currentUser['id'];

$resultModel = new Result();

// Build filters
$filters = ['user_id' => $userId, 'limit' => 1000];

if (input('phrase_id')) {
    $filters['phrase_id'] = (int)input('phrase_id');
}
if (input('date_from')) {
    $filters['date_from'] = input('date_from');
}
if (input('rating_min')) {
    $filters['rating_min'] = (float)input('rating_min');
}

$results = $resultModel->getForMap($filters);

// Get user phrases for filter
$phraseModel = new Phrase();
$userPhrases = $phraseModel->getByUser($userId);

$pageTitle = 'Results Map';
include __DIR__ . '/includes/header.php';
?>

<style>
    #map-container {
        height: calc(100vh - 200px);
        min-height: 600px;
        border-radius: 8px;
        overflow: hidden;
    }
    .map-filters {
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        gap: 15px;
        align-items: end;
    }
    .map-filters .form-group {
        margin-bottom: 0;
        flex: 1;
    }
</style>

<div class="page-header">
    <h1>Results Map</h1>
    <div class="page-actions">
        <a href="/results.php" class="btn btn-secondary">📋 View Table</a>
    </div>
</div>

<!-- Filters -->
<div class="map-filters">
    <form method="GET" action="/results_map.php" style="display: flex; gap: 15px; width: 100%; align-items: end;">
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
            <label>Min Rating</label>
            <select name="rating_min" class="form-control">
                <option value="">All Ratings</option>
                <option value="4" <?= input('rating_min') == '4' ? 'selected' : '' ?>>4+ Stars</option>
                <option value="3" <?= input('rating_min') == '3' ? 'selected' : '' ?>>3+ Stars</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="/results_map.php" class="btn btn-secondary">Clear</a>
    </form>
</div>

<div id="map-container"></div>

<script>
// Initialize map
const mapData = <?= json_encode($results) ?>;

const map = L.map('map-container').setView([52.2297, 21.0122], 6);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
}).addTo(map);

// Add marker cluster group
const markers = L.markerClusterGroup({
    chunkedLoading: true,
    maxClusterRadius: 50
});

// Add markers
mapData.forEach(function(result) {
    const rating = parseFloat(result.rating) || 0;
    let markerColor = 'red';

    if (rating >= 4) {
        markerColor = 'green';
    } else if (rating >= 3) {
        markerColor = 'orange';
    }

    const marker = L.circleMarker([result.latitude, result.longitude], {
        radius: 8,
        fillColor: markerColor,
        color: '#fff',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.8
    });

    const popupContent = `
        <div style="min-width: 250px;">
            <h4 style="margin: 0 0 10px 0;">${result.place_name}</h4>
            ${result.address ? '<p style="margin: 5px 0;"><strong>Address:</strong> ' + result.address + '</p>' : ''}
            ${result.rating ? '<p style="margin: 5px 0;"><strong>Rating:</strong> ' + result.rating + ' (' + result.reviews_count + ' reviews)</p>' : ''}
            ${result.phone ? '<p style="margin: 5px 0;"><strong>Phone:</strong> ' + result.phone + '</p>' : ''}
            ${result.website ? '<p style="margin: 5px 0;"><strong>Website:</strong> <a href="' + result.website + '" target="_blank">Link</a></p>' : ''}
            ${result.place_url ? '<p style="margin: 5px 0;"><a href="' + result.place_url + '" target="_blank" class="btn btn-sm btn-primary">View on Google Maps</a></p>' : ''}
            <p style="margin: 5px 0; font-size: 12px; color: #666;"><strong>Phrase:</strong> ${result.phrase}</p>
        </div>
    `;

    marker.bindPopup(popupContent);
    markers.addLayer(marker);
});

map.addLayer(markers);

// Fit bounds to show all markers
if (mapData.length > 0) {
    const bounds = L.latLngBounds(mapData.map(r => [r.latitude, r.longitude]));
    map.fitBounds(bounds);
}
</script>

<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
