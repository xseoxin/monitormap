/**
 * Map Visualizer
 */

const MapVisualizer = {
    map: null,
    markers: [],
    markerCluster: null,

    // Initialize map
    init: function(containerId, centerLat, centerLng, zoom = 10) {
        this.map = L.map(containerId).setView([centerLat, centerLng], zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);

        // Initialize marker cluster group
        if (L.markerClusterGroup) {
            this.markerCluster = L.markerClusterGroup();
            this.map.addLayer(this.markerCluster);
        }

        return this.map;
    },

    // Add marker
    addMarker: function(lat, lng, options = {}) {
        const markerOptions = {
            title: options.title || ''
        };

        const marker = L.marker([lat, lng], markerOptions);

        if (options.popup) {
            marker.bindPopup(options.popup);
        }

        if (this.markerCluster) {
            this.markerCluster.addLayer(marker);
        } else {
            marker.addTo(this.map);
        }

        this.markers.push(marker);
        return marker;
    },

    // Add circle marker
    addCircleMarker: function(lat, lng, options = {}) {
        const markerOptions = {
            radius: options.radius || 6,
            fillColor: options.color || '#4CAF50',
            color: '#fff',
            weight: 1,
            opacity: 1,
            fillOpacity: 0.8
        };

        const marker = L.circleMarker([lat, lng], markerOptions);

        if (options.popup) {
            marker.bindPopup(options.popup);
        }

        marker.addTo(this.map);
        this.markers.push(marker);
        return marker;
    },

    // Clear all markers
    clearMarkers: function() {
        if (this.markerCluster) {
            this.markerCluster.clearLayers();
        }

        this.markers.forEach(marker => {
            this.map.removeLayer(marker);
        });

        this.markers = [];
    },

    // Fit bounds to markers
    fitBounds: function() {
        if (this.markers.length > 0) {
            const bounds = L.latLngBounds(this.markers.map(m => m.getLatLng()));
            this.map.fitBounds(bounds);
        }
    },

    // Get marker color by rating
    getRatingColor: function(rating) {
        if (rating >= 4) return 'green';
        if (rating >= 3) return 'orange';
        return 'red';
    }
};
