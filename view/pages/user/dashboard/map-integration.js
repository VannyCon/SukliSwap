/**
 * SukliSwap Map Integration JavaScript
 * Handles MapLibre integration for location-based matching and visualization
 */
class MapIntegrationManager {
    constructor() {
        this.map = null;
        this.markers = [];
        this.userLocation = null;
        this.authManager = window.authManager;
        coinExchangeAPI = this.authManager.API_CONFIG.baseURL + 'coin_exchange.php';
        headerAPI = this.authManager.API_CONFIG.getHeaders();
        formHeaderAPI = this.authManager.API_CONFIG.getFormHeaders();
        this.mapConfig = {
            container: 'mapContainer',
            style: 'https://demotiles.maplibre.org/style.json', // Default style
            center: [120.9842, 14.5995], // Manila, Philippines
            zoom: 11,
            pitch: 0,
            bearing: 0
        };
        this.safePlaces = [];
        this.map = null;
        this.markers = [];
        this.init();
    }

    async init() {
        try {
            // Check if MapLibre is loaded
            if (typeof maplibregl === 'undefined') {
                console.error('MapLibre GL JS is not loaded');
                this.showMapError('Map library not loaded');
                return;
            }

            // Initialize map
            await this.initializeMap();
            
            // Load map data
            await this.loadMapData();
            
            // Initialize event listeners
            this.initEventListeners();
            
        } catch (error) {
            console.error('Failed to initialize MapIntegrationManager:', error);
            this.showMapError('Failed to initialize map');
        }
    }

    async initializeMap() {
        try {
            this.map = new maplibregl.Map({
                container: this.mapConfig.container,
                style: this.mapConfig.style,
                center: this.mapConfig.center,
                zoom: this.mapConfig.zoom,
                pitch: this.mapConfig.pitch,
                bearing: this.mapConfig.bearing
            });

            // Add navigation controls
            this.map.addControl(new maplibregl.NavigationControl(), 'top-right');
            
            // Add geolocate control
            this.map.addControl(new maplibregl.GeolocateControl({
                positionOptions: {
                    enableHighAccuracy: true
                },
                trackUserLocation: true,
                showUserHeading: true
            }), 'top-right');

            // Add scale control
            this.map.addControl(new maplibregl.ScaleControl({
                maxWidth: 100,
                unit: 'metric'
            }), 'bottom-left');

            // Wait for map to load
            this.map.on('load', () => {
                console.log('Map loaded successfully');
                this.onMapLoaded();
            });
            
            // Handle map errors
            this.map.on('error', (e) => {
                console.error('Map error:', e);
                this.showMapError('Map loading error');
            });

        } catch (error) {
            console.error('Failed to initialize map:', error);
            this.showMapError('Failed to initialize map');
        }
    }

    onMapLoaded() {
        // Map is ready, load data
        this.loadMapData();
        
        // Add click handler for map
        this.map.on('click', (e) => {
            this.handleMapClick(e);
        });
    }

    initEventListeners() {
        // Location button
        const locationBtn = document.getElementById('getCurrentLocationBtn');
        if (locationBtn) {
            locationBtn.addEventListener('click', () => this.getCurrentLocation());
        }

        // Map type selector
        const mapTypeSelect = document.getElementById('mapTypeSelect');
        if (mapTypeSelect) {
            mapTypeSelect.addEventListener('change', (e) => this.changeMapStyle(e.target.value));
        }

        // Radius slider
        const radiusSlider = document.getElementById('radiusSlider');
        if (radiusSlider) {
            radiusSlider.addEventListener('input', (e) => this.updateRadiusCircle(e.target.value));
        }
    }

    // ============ DATA LOADING ============

    async loadMapData() {
        try {
            const response = await axios.get(`${coinExchangeAPI}?action=getMapData`, {
                headers: headerAPI
            });

            if (response.data.success) {
                const data = response.data.data;
                this.renderMapData(data);
            }
        } catch (error) {
            console.error('Failed to load map data:', error);
        }
    }

    renderMapData(data) {
        // Clear existing markers
        this.clearMarkers();

        // Add request markers
        if (data.requests) {
            data.requests.forEach(request => {
                if (request.meeting_latitude && request.meeting_longitude) {
                    this.addRequestMarker(request);
                }
            });
        }

        // Add offer markers
        if (data.offers) {
            data.offers.forEach(offer => {
                if (offer.meeting_latitude && offer.meeting_longitude) {
                    this.addOfferMarker(offer);
                }
            });
        }

        // Fit map to show all markers
        this.fitMapToMarkers();
    }

    // ============ MARKER MANAGEMENT ============

    addRequestMarker(request) {
        if (!this.map) return;

        const marker = new maplibregl.Marker({
            color: '#28a745',
            scale: 0.8
        })
        .setLngLat([request.meeting_longitude, request.meeting_latitude])
        .setPopup(new maplibregl.Popup({
            offset: 25,
            closeButton: true,
            closeOnClick: false
        }).setHTML(this.createRequestPopupHTML(request)))
        .addTo(this.map);

        this.markers.push(marker);
    }

    addOfferMarker(offer) {
        if (!this.map) return;

        const marker = new maplibregl.Marker({
            color: '#007bff',
            scale: 0.8
        })
        .setLngLat([offer.meeting_longitude, offer.meeting_latitude])
        .setPopup(new maplibregl.Popup({
            offset: 25,
            closeButton: true,
            closeOnClick: false
        }).setHTML(this.createOfferPopupHTML(offer)))
        .addTo(this.map);

        this.markers.push(marker);
    }

    addUserLocationMarker(lat, lng) {
        if (!this.map) return;

        // Remove existing user location marker
        this.removeUserLocationMarker();

        const marker = new maplibregl.Marker({
            color: '#dc3545',
            scale: 1.2
        })
        .setLngLat([lng, lat])
        .setPopup(new maplibregl.Popup({
            offset: 25,
            closeButton: true,
            closeOnClick: false
        }).setHTML('<div class="text-center"><strong>Your Location</strong></div>'))
        .addTo(this.map);

        this.userLocation = marker;
        this.markers.push(marker);
    }

    removeUserLocationMarker() {
        if (this.userLocation) {
            this.userLocation.remove();
            this.markers = this.markers.filter(marker => marker !== this.userLocation);
            this.userLocation = null;
        }
    }

    clearMarkers() {
        this.markers.forEach(marker => marker.remove());
        this.markers = [];
        this.userLocation = null;
    }

    // ============ POPUP CONTENT ============

    createRequestPopupHTML(request) {
        return `
            <div class="map-popup">
                <h6 class="popup-title">₱${request.denomination} Coin Request</h6>
                <div class="popup-content">
                    <p><strong>From:</strong> ${request.username}</p>
                    <p><strong>Quantity:</strong> ${request.quantity}</p>
                    <p><strong>Location:</strong> ${request.preferred_meeting_location || 'Not specified'}</p>
                    <p><strong>Business:</strong> ${request.business_name || 'N/A'}</p>
                    <p><strong>Created:</strong> ${this.formatDate(request.created_at)}</p>
                </div>
                <div class="popup-actions">
                    <button class="btn btn-sm btn-primary" onclick="mapIntegrationManager.viewRequestDetails(${request.id})">
                        View Details
                    </button>
                </div>
            </div>
        `;
    }

    createOfferPopupHTML(offer) {
        return `
            <div class="map-popup">
                <h6 class="popup-title">₱${offer.denomination} Coin Offer</h6>
                <div class="popup-content">
                    <p><strong>From:</strong> ${offer.username}</p>
                    <p><strong>Quantity:</strong> ${offer.quantity}</p>
                    <p><strong>Location:</strong> ${offer.preferred_meeting_location || 'Not specified'}</p>
                    <p><strong>Business:</strong> ${offer.business_name || 'N/A'}</p>
                    <p><strong>Created:</strong> ${this.formatDate(offer.created_at)}</p>
                </div>
                <div class="popup-actions">
                    <button class="btn btn-sm btn-primary" onclick="mapIntegrationManager.viewOfferDetails(${offer.id})">
                        View Details
                    </button>
                </div>
            </div>
        `;
    }

    // ============ LOCATION SERVICES ============

    getCurrentLocation() {
        if (!navigator.geolocation) {
            this.showToast('Geolocation is not supported by this browser', 'error');
            return;
        }

        const locationBtn = document.getElementById('getCurrentLocationBtn');
        if (locationBtn) {
            locationBtn.disabled = true;
            locationBtn.className = 'btn btn-outline-warning';
            locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Add user location marker
                this.addUserLocationMarker(lat, lng);
                
                // Center map on user location
                this.map.flyTo({
                    center: [lng, lat],
                    zoom: 15,
                    essential: true
                });
                
                // Update form fields if they exist
                this.updateLocationFields(lat, lng);
                
                CustomToast.show('success', 'Location updated successfully!');
                if (locationBtn) {
                    locationBtn.disabled = false;
                    locationBtn.className = 'btn btn-outline-success';
                    locationBtn.innerHTML = '<i class="fas fa-check-circle"></i> Location Retrieved';
                }
            },
            (error) => {
                console.error('Geolocation error:', error);
                this.showToast('Failed to get location', 'error');
                
                if (locationBtn) {
                    locationBtn.disabled = false;
                    locationBtn.className = 'btn btn-outline-secondary';
                    locationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get Current Location';
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    }

    updateLocationFields(lat, lng) {
        const latInput = document.getElementById('meeting_latitude');
        const lngInput = document.getElementById('meeting_longitude');
        
        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;
    }

    // ============ MAP CONTROLS ============

    changeMapStyle(style) {
        if (!this.map) return;

        const styleMap = {
            'streets': 'https://demotiles.maplibre.org/style.json',
            'satellite': 'https://api.maptiler.com/maps/satellite/style.json?key=get_your_own_OpIi9ZULNHzrESv6T2vL',
            'terrain': 'https://api.maptiler.com/maps/terrain/style.json?key=get_your_own_OpIi9ZULNHzrESv6T2vL'
        };

        if (styleMap[style]) {
            this.map.setStyle(styleMap[style]);
        }
    }

    updateRadiusCircle(radius) {
        if (!this.map) return;

        // Remove existing radius circle
        if (this.map.getSource('radius-circle')) {
            this.map.removeLayer('radius-circle');
            this.map.removeSource('radius-circle');
        }

        // Add radius circle if user location is available
        if (this.userLocation) {
            const center = this.userLocation.getLngLat();
            const radiusInKm = radius / 1000;

            this.map.addSource('radius-circle', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    geometry: {
                        type: 'Polygon',
                        coordinates: [this.createCircle(center.lng, center.lat, radiusInKm)]
                    }
                }
            });

            this.map.addLayer({
                id: 'radius-circle',
                type: 'fill',
                source: 'radius-circle',
                paint: {
                    'fill-color': '#007bff',
                    'fill-opacity': 0.1
                }
            });

            this.map.addLayer({
                id: 'radius-circle-border',
                type: 'line',
                source: 'radius-circle',
                paint: {
                    'line-color': '#007bff',
                    'line-width': 2
                }
            });
        }
    }

    createCircle(lng, lat, radiusInKm) {
        const points = 64;
        const coords = [];
        
        for (let i = 0; i < points; i++) {
            const angle = (i * 360) / points;
            const dx = radiusInKm * Math.cos(angle * Math.PI / 180);
            const dy = radiusInKm * Math.sin(angle * Math.PI / 180);
            
            const x = lng + (dx / 111.32);
            const y = lat + (dy / 111.32);
            
            coords.push([x, y]);
        }
        
        coords.push(coords[0]); // Close the circle
        return coords;
    }

    // ============ MAP INTERACTIONS ============

    handleMapClick(e) {
        const lng = e.lngLat.lng;
        const lat = e.lngLat.lat;
        
        // Update location fields if they exist
        this.updateLocationFields(lat, lng);
        
        // Add temporary marker
        this.addTemporaryMarker(lat, lng);
        
        this.showToast(`Location selected: ${lat.toFixed(6)}, ${lng.toFixed(6)}`, 'info');
    }

    addTemporaryMarker(lat, lng) {
        if (!this.map) return;

        // Remove existing temporary marker
        this.removeTemporaryMarker();

        const marker = new maplibregl.Marker({
            color: '#ffc107',
            scale: 1.0
        })
        .setLngLat([lng, lat])
        .setPopup(new maplibregl.Popup({
            offset: 25,
            closeButton: true,
            closeOnClick: false
        }).setHTML('<div class="text-center"><strong>Selected Location</strong></div>'))
        .addTo(this.map);

        this.temporaryMarker = marker;
    }

    removeTemporaryMarker() {
        if (this.temporaryMarker) {
            this.temporaryMarker.remove();
            this.temporaryMarker = null;
        }
    }

    // ============ UTILITY METHODS ============

    fitMapToMarkers() {
        if (!this.map || this.markers.length === 0) return;

        const bounds = new maplibregl.LngLatBounds();
        
        this.markers.forEach(marker => {
            bounds.extend(marker.getLngLat());
        });

        this.map.fitBounds(bounds, {
            padding: 50,
            maxZoom: 15
        });
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    showToast(message, type = 'info') {
        console.log(`Toast [${type}]: ${message}`);
        
        if (typeof showToast === 'function') {
            showToast(message, type);
        }
    }

    showMapError(message) {
        const container = document.getElementById(this.mapConfig.container);
        if (container) {
            container.innerHTML = `
                <div class="map-error d-flex align-items-center justify-content-center h-100">
                    <div class="text-center">
                        <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">${message}</h5>
                        <button class="btn btn-primary" onclick="mapIntegrationManager.init()">
                            Retry
                        </button>
                    </div>
                </div>
            `;
        }
    }

    // ============ DETAIL VIEWS ============

    viewRequestDetails(requestId) {
        // Implement view request details
        console.log('View request details:', requestId);
        
        // You can open a modal or navigate to a details page
        // For now, just show a toast
        this.showToast('Request details feature coming soon!', 'info');
    }

    viewOfferDetails(offerId) {
        // Implement view offer details
        console.log('View offer details:', offerId);
        
        // You can open a modal or navigate to a details page
        // For now, just show a toast
        this.showToast('Offer details feature coming soon!', 'info');
    }

    // ============ PUBLIC METHODS ============

    updateMapData() {
        this.loadMapData();
    }

    centerOnLocation(lat, lng, zoom = 15) {
        if (this.map) {
            this.map.flyTo({
                center: [lng, lat],
                zoom: zoom,
                essential: true
            });
        }
    }

    addCustomMarker(lat, lng, color = '#007bff', popupHTML = '') {
        if (!this.map) return null;

        const marker = new maplibregl.Marker({
            color: color,
            scale: 1.0
        })
        .setLngLat([lng, lat]);

        if (popupHTML) {
            marker.setPopup(new maplibregl.Popup({
                offset: 25,
                closeButton: true,
                closeOnClick: false
            }).setHTML(popupHTML));
        }

        marker.addTo(this.map);
        this.markers.push(marker);
        
        return marker;
    }

    removeMarker(marker) {
        if (marker) {
            marker.remove();
            this.markers = this.markers.filter(m => m !== marker);
        }
    }

    getMapBounds() {
        if (this.map) {
            return this.map.getBounds();
        }
        return null;
    }

    getMapCenter() {
        if (this.map) {
            return this.map.getCenter();
        }
        return null;
    }

    getMapZoom() {
        if (this.map) {
            return this.map.getZoom();
        }
        return null;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Wait for authManager to be available
    if (window.authManager) {
        window.mapIntegrationManager = new MapIntegrationManager();
    } else {
        // Retry after a short delay if authManager is not ready
        setTimeout(() => {
            if (window.authManager) {
                window.mapIntegrationManager = new MapIntegrationManager();
            } else {
                console.error('AuthManager not available for MapIntegrationManager initialization');
            }
        }, 100);
    }
});
