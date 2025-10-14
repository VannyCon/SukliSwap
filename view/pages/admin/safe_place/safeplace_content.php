<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Safe Places Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="openAddSafePlaceModal()">
                    <i class="fas fa-plus"></i> Add Safe Place
                </button>
                <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleView()">
                    <i class="fas fa-list" id="viewToggleIcon"></i> <span id="viewToggleText">List View</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="totalSafePlaces">0</h4>
                            <p class="card-text">Total Safe Places</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-map-marker-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="activeSafePlaces">0</h4>
                            <p class="card-text">Active Safe Places</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="inactiveSafePlaces">0</h4>
                            <p class="card-text">Inactive Safe Places</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="uniqueCreators">0</h4>
                            <p class="card-text">Unique Creators</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Controls -->
    <!-- <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <input type="text" class="form-control" id="safePlaceSearchInput" placeholder="Search safe places by name or description...">
                <button class="btn btn-outline-secondary" type="button" onclick="safePlaceManager.searchSafePlaces()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-control" id="activeFilter">
                <option value="true">Active Only</option>
                <option value="false">All Safe Places</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary" onclick="safePlaceManager.loadSafePlaces()">
                <i class="fas fa-filter"></i> Apply Filter
            </button>
        </div>
    </div> -->

    <!-- Safe Places List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-map-marker-alt"></i> Safe Places
                <span class="badge bg-secondary ms-2" id="safePlacesCount">0</span>
            </h5>
        </div>
        <div class="card-body">
            <!-- Map Container -->
            <div id="mapContainer" style="height: 500px; width: 100%; border-radius: 0.375rem; margin-bottom: 20px; position: relative;">
                <div id="map" style="height: 100%; width: 100%; border-radius: 0.375rem;"></div>
                <div class="map-instructions" style="position: absolute; top: 10px; left: 10px; background: rgba(255,255,255,0.9); padding: 8px 12px; border-radius: 4px; font-size: 0.875em; box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index: 1000;">
                    <i class="fas fa-crosshairs text-primary"></i> Click anywhere on the map to add a new safe place
                </div>
            </div>
            
            <!-- Safe Places List -->
            <div id="safePlacesContainer">
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin"></i> Loading safe places...
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="row mt-3">
        <div class="col-12">
            <div id="paginationContainer"></div>
        </div>
    </div>
</div>

<!-- Add/Edit Safe Place Modal -->
<div class="modal fade" id="safePlaceModal" tabindex="-1" aria-labelledby="safePlaceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="safePlaceModalLabel">Add New Safe Place</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="safePlaceForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="location_name" class="form-label">Location Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="location_name" name="location_name" required>
                            <div class="form-text">Enter a descriptive name for this safe place (e.g., "Cadiz City Police Station")</div>
                        </div>
                    </div>
                    
                    <div class="row" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="lat" class="form-label">Latitude <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="lat" name="lat" step="any" required>
                            <div class="form-text">Enter latitude coordinate (e.g., 10.94875323)</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="long" class="form-label">Longitude <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="long" name="long" step="any" required>
                            <div class="form-text">Enter longitude coordinate (e.g., 123.33649484)</div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="alert alert-info" id="coordinatesAlert" style="display: none;">
                                <i class="fas fa-info-circle"></i>
                                <strong>Coordinates selected from map:</strong> The latitude and longitude have been automatically filled from your map click.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            <div class="form-text">Optional description of the safe place and why it's suitable for coin exchanges</div>
                        </div>
                    </div>
                    
                    <!-- Map Preview Section -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-map"></i> Location Preview
                                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="updateMapPreview()">
                                            <i class="fas fa-sync"></i> Update Preview
                                        </button>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="mapPreview" style="height: 200px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                            <div class="text-center">
                                                <i class="fas fa-map-marked-alt fa-2x mb-2"></i>
                                                <p>Enter coordinates to preview location</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Safe Place</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Add Modal for Common Locations -->
<div class="modal fade" id="quickAddModal" tabindex="-1" aria-labelledby="quickAddModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickAddModalLabel">Quick Add Common Locations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-outline-primary w-100" onclick="quickAddLocation('Police Station', 10.94875323, 123.33649484)">
                            <i class="fas fa-shield-alt"></i> Police Station
                        </button>
                    </div>
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-outline-primary w-100" onclick="quickAddLocation('Shopping Mall', 10.95875323, 123.34649484)">
                            <i class="fas fa-store"></i> Shopping Mall
                        </button>
                    </div>
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-outline-primary w-100" onclick="quickAddLocation('City Hall', 10.93875323, 123.32649484)">
                            <i class="fas fa-landmark"></i> City Hall
                        </button>
                    </div>
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-outline-primary w-100" onclick="quickAddLocation('Bank', 10.96875323, 123.35649484)">
                            <i class="fas fa-university"></i> Bank
                        </button>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> These are sample locations. Please update the coordinates to match actual locations in your area.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include MapLibre for preview functionality -->
<script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
<link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />

<script>
// Map preview functionality
let mapPreview = null;

function updateMapPreview() {
    const lat = document.getElementById('lat').value;
    const lng = document.getElementById('long').value;
    const name = document.getElementById('location_name').value;
    
    if (!lat || !lng) {
        alert('Please enter both latitude and longitude coordinates');
        return;
    }
    
    const container = document.getElementById('mapPreview');
    container.innerHTML = '';
    
    if (mapPreview) {
        mapPreview.remove();
    }
    
    mapPreview = new maplibregl.Map({
        container: 'mapPreview',
        style: {
            version: 8,
            sources: {
                'osm': {
                    type: 'raster',
                    tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                    tileSize: 256,
                    attribution: '© OpenStreetMap contributors'
                }
            },
            layers: [
                {
                    id: 'osm',
                    type: 'raster',
                    source: 'osm'
                }
            ]
        },
        center: [parseFloat(lng), parseFloat(lat)],
        zoom: 15
    });
    
    mapPreview.on('load', () => {
        // Add marker
        new maplibregl.Marker({ color: 'red' })
            .setLngLat([parseFloat(lng), parseFloat(lat)])
            .addTo(mapPreview);
            
        // Add popup
        new maplibregl.Popup({ closeOnClick: false })
            .setLngLat([parseFloat(lng), parseFloat(lat)])
            .setHTML(`<strong>${name || 'Safe Place'}</strong><br>${lat}, ${lng}`)
            .addTo(mapPreview);
    });
}

function quickAddLocation(name, lat, lng) {
    document.getElementById('location_name').value = name;
    document.getElementById('lat').value = lat;
    document.getElementById('long').value = lng;
    document.getElementById('description').value = `Safe location for coin exchanges - ${name}`;
    
    // Close quick add modal
    const quickAddModal = bootstrap.Modal.getInstance(document.getElementById('quickAddModal'));
    if (quickAddModal) {
        quickAddModal.hide();
    }
    
    // Open main modal
    const mainModal = new bootstrap.Modal(document.getElementById('safePlaceModal'));
    mainModal.show();
    
    // Update map preview
    setTimeout(() => {
        updateMapPreview();
    }, 500);
}

// Update safe places count display
function updateSafePlacesCount() {
    const count = document.querySelectorAll('#safePlacesContainer .card').length;
    const countElement = document.getElementById('safePlacesCount');
    if (countElement) {
        countElement.textContent = count;
    }
}

// Override the updateSafePlacesDisplay method to include count update
document.addEventListener('DOMContentLoaded', function() {
    // Wait for SafePlaceManager to be initialized
    setTimeout(() => {
        if (window.safePlaceManager) {
            const originalUpdate = window.safePlaceManager.updateSafePlacesDisplay;
            window.safePlaceManager.updateSafePlacesDisplay = function() {
                originalUpdate.call(this);
                updateSafePlacesCount();
            };
        }
    }, 1000);
});
</script>

<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.btn-group .btn {
    margin-right: 5px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.pagination {
    margin-bottom: 0;
}

#mapPreview {
    border-radius: 0.375rem;
}

.toast-container {
    z-index: 1055;
}

.modal-lg {
    max-width: 800px;
}

.form-text {
    font-size: 0.875em;
    color: #6c757d;
}

.text-danger {
    color: #dc3545 !important;
}

/* Map popup styles */
.map-popup {
    min-width: 200px;
}

.map-popup h6 {
    margin-bottom: 8px;
    color: #333;
}

.map-popup p {
    margin-bottom: 4px;
    font-size: 0.9em;
    color: #666;
}

.map-popup .btn {
    font-size: 0.8em;
    padding: 2px 6px;
}

/* Map container responsive */
@media (max-width: 768px) {
    #mapContainer {
        height: 300px !important;
    }
}

/* Map controls */
.maplibregl-ctrl-group {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 4px;
}

.maplibregl-popup-content {
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.maplibregl-popup-close-button {
    font-size: 18px;
    color: #666;
}

/* Map click instructions */
.map-instructions {
    font-weight: 500;
    color: #495057;
}

.map-instructions i {
    margin-right: 5px;
}

/* Map cursor styles */
.maplibregl-canvas-container {
    cursor: pointer !important;
}

/* Temporary marker animation */
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}

.temporary-marker {
    animation: pulse 2s infinite;
}
</style>