<?php
/**
 * Add Phrase Page
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentUser = Auth::user();
$userId = $currentUser['id'];

$phraseModel = new Phrase();
$userModel = new User();
$errors = [];

// Check if user has reached phrase limit
if ($userModel->hasReachedPhraseLimit($userId)) {
    setFlash('error', MSG_ERROR_PHRASE_LIMIT);
    redirect('/phrases.php');
}

// Handle form submission
if (isPost()) {
    validateCsrf();

    $phrase = input('phrase');
    $lat = input('location_lat');
    $lng = input('location_lng');
    $radius = input('radius_km', 10);
    $gridSize = input('grid_size', '5x5');
    $frequency = input('scan_frequency', 'weekly');
    $priority = input('priority', 5);

    // Validate
    if (!Validator::phrase($phrase)) {
        $errors[] = 'Invalid phrase';
    }
    if (!Validator::coordinates($lat, $lng)) {
        $errors[] = 'Invalid coordinates';
    }
    if (!Validator::radius($radius)) {
        $errors[] = 'Invalid radius (1-50 km)';
    }
    if (!Validator::gridSize($gridSize)) {
        $errors[] = 'Invalid grid size';
    }

    if (empty($errors)) {
        try {
            $phraseId = $phraseModel->create([
                'user_id' => $userId,
                'phrase' => $phrase,
                'location_lat' => $lat,
                'location_lng' => $lng,
                'radius_km' => $radius,
                'grid_size' => $gridSize,
                'scan_frequency' => $frequency,
                'priority' => $priority
            ]);

            setFlash('success', MSG_SUCCESS_PHRASE_ADDED);
            redirect('/phrases.php');
        } catch (Exception $e) {
            $errors[] = 'Failed to add phrase: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Phrase';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Add New Phrase</h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul>
        <?php foreach ($errors as $error): ?>
        <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div style="max-width: 800px;">
    <form method="POST" id="phrase-form">
        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
        <input type="hidden" name="location_lat" id="location_lat" value="">
        <input type="hidden" name="location_lng" id="location_lng" value="">

        <div class="form-group">
            <label for="phrase">Search Phrase *</label>
            <input type="text" id="phrase" name="phrase" class="form-control" required
                   placeholder="e.g., coffee shop in Warsaw" value="<?= e(input('phrase', '')) ?>">
        </div>

        <div class="form-group">
            <label for="location">Location (search or click on map) *</label>
            <input type="text" id="location-search" class="form-control"
                   placeholder="Search for a location...">
            <small>Or click on the map below to set location</small>
        </div>

        <div class="form-group">
            <div id="map" style="height: 400px; border-radius: 8px;"></div>
        </div>

        <div class="form-group">
            <label for="radius_km">Radius (km) *</label>
            <input type="number" id="radius_km" name="radius_km" class="form-control"
                   min="1" max="50" value="<?= e(input('radius_km', 10)) ?>" required>
            <small>1-50 km</small>
        </div>

        <div class="form-group">
            <label for="grid_size">Grid Size *</label>
            <select id="grid_size" name="grid_size" class="form-control" required>
                <option value="5x5">5x5 (25 points)</option>
                <option value="7x7">7x7 (49 points)</option>
                <option value="9x9">9x9 (81 points)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="scan_frequency">Scan Frequency</label>
            <select id="scan_frequency" name="scan_frequency" class="form-control">
                <option value="manual">Manual only</option>
                <option value="daily">Daily</option>
                <option value="weekly" selected>Weekly</option>
                <option value="monthly">Monthly</option>
            </select>
        </div>

        <div class="form-group">
            <label for="priority">Priority</label>
            <input type="number" id="priority" name="priority" class="form-control"
                   min="1" max="10" value="5">
            <small>1-10 (higher = scanned first)</small>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">Add Phrase</button>
            <a href="/phrases.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
// Initialize map
const map = L.map('map').setView([52.2297, 21.0122], 10); // Warsaw default

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);

let marker = null;

// Map click handler
map.on('click', function(e) {
    if (marker) {
        map.removeLayer(marker);
    }
    marker = L.marker(e.latlng).addTo(map);
    document.getElementById('location_lat').value = e.latlng.lat.toFixed(8);
    document.getElementById('location_lng').value = e.latlng.lng.toFixed(8);
});

// Simple location search
document.getElementById('location-search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const query = this.value;
        if (query) {
            // Use Nominatim for geocoding
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        map.setView([lat, lng], 12);
                        if (marker) {
                            map.removeLayer(marker);
                        }
                        marker = L.marker([lat, lng]).addTo(map);
                        document.getElementById('location_lat').value = lat.toFixed(8);
                        document.getElementById('location_lng').value = lng.toFixed(8);
                    }
                });
        }
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
