<?php
/**
 * GridGenerator Class
 *
 * Generates geographic grid points for scanning
 */

class GridGenerator {

    /**
     * Generate grid points around a center location
     *
     * @param float $centerLat Center latitude
     * @param float $centerLng Center longitude
     * @param float $radiusKm Radius in kilometers
     * @param string $gridSize Grid size (5x5, 7x7, 9x9)
     * @return array Array of grid points with lat/lng
     */
    public static function generate($centerLat, $centerLng, $radiusKm, $gridSize = '5x5') {
        // Parse grid size
        $size = (int)explode('x', $gridSize)[0];

        // 1 degree of latitude ≈ 111 km
        // 1 degree of longitude varies by latitude: ≈ 111 * cos(latitude) km
        $latDegreeKm = 111.0;
        $lngDegreeKm = 111.0 * cos(deg2rad($centerLat));

        // Calculate step size in degrees
        $latStep = ($radiusKm * 2) / ($size * $latDegreeKm);
        $lngStep = ($radiusKm * 2) / ($size * $lngDegreeKm);

        // Calculate starting point (top-left corner of grid)
        $startLat = $centerLat - ($latStep * ($size - 1) / 2);
        $startLng = $centerLng - ($lngStep * ($size - 1) / 2);

        // Generate grid points
        $points = [];
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j < $size; $j++) {
                $lat = $startLat + ($i * $latStep);
                $lng = $startLng + ($j * $lngStep);

                // Round to 8 decimal places (≈ 1mm precision)
                $points[] = [
                    'lat' => round($lat, 8),
                    'lng' => round($lng, 8),
                    'row' => $i,
                    'col' => $j
                ];
            }
        }

        Logger::debug('Grid generated', [
            'center' => [$centerLat, $centerLng],
            'radius_km' => $radiusKm,
            'grid_size' => $gridSize,
            'points_count' => count($points)
        ]);

        return $points;
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     *
     * @param float $lat1 First latitude
     * @param float $lng1 First longitude
     * @param float $lat2 Second latitude
     * @param float $lng2 Second longitude
     * @return float Distance in kilometers
     */
    public static function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if a point is within radius of center
     *
     * @param float $centerLat Center latitude
     * @param float $centerLng Center longitude
     * @param float $pointLat Point latitude
     * @param float $pointLng Point longitude
     * @param float $radiusKm Radius in kilometers
     * @return bool True if point is within radius
     */
    public static function isWithinRadius($centerLat, $centerLng, $pointLat, $pointLng, $radiusKm) {
        $distance = self::calculateDistance($centerLat, $centerLng, $pointLat, $pointLng);
        return $distance <= $radiusKm;
    }

    /**
     * Get grid coverage area in square kilometers
     *
     * @param float $radiusKm Radius in kilometers
     * @param string $gridSize Grid size
     * @return float Area in square kilometers
     */
    public static function getCoverageArea($radiusKm, $gridSize = '5x5') {
        $size = (int)explode('x', $gridSize)[0];
        $diameter = $radiusKm * 2;
        return $diameter * $diameter; // Approximate square area
    }

    /**
     * Generate grid preview data for visualization
     *
     * @param float $centerLat Center latitude
     * @param float $centerLng Center longitude
     * @param float $radiusKm Radius in kilometers
     * @param string $gridSize Grid size
     * @return array Preview data with center, radius, and grid points
     */
    public static function getPreview($centerLat, $centerLng, $radiusKm, $gridSize = '5x5') {
        $points = self::generate($centerLat, $centerLng, $radiusKm, $gridSize);

        return [
            'center' => [
                'lat' => $centerLat,
                'lng' => $centerLng
            ],
            'radius_km' => $radiusKm,
            'grid_size' => $gridSize,
            'total_points' => count($points),
            'points' => $points,
            'coverage_area_km2' => self::getCoverageArea($radiusKm, $gridSize)
        ];
    }

    /**
     * Optimize grid size based on radius
     *
     * @param float $radiusKm Radius in kilometers
     * @return string Recommended grid size
     */
    public static function recommendGridSize($radiusKm) {
        if ($radiusKm <= 5) {
            return '5x5';
        } elseif ($radiusKm <= 15) {
            return '7x7';
        } else {
            return '9x9';
        }
    }

    /**
     * Get grid statistics
     *
     * @param string $gridSize Grid size
     * @return array Statistics about the grid
     */
    public static function getGridStats($gridSize = '5x5') {
        $size = (int)explode('x', $gridSize)[0];

        return [
            'grid_size' => $gridSize,
            'total_points' => $size * $size,
            'rows' => $size,
            'columns' => $size
        ];
    }
}
