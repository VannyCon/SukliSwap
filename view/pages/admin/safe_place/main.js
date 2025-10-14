/**
 * SukliSwap Safe Place JavaScript
 * Handles safe place CRUD operations for admin dashboard
 */
let safePlaceAPI = null;
let headerAPI = null;
let formHeaderAPI = null;
let currentUser = null;

class SafePlaceManager {
    constructor() {
        const authManager = new AuthManager();
        this.authManager = authManager;
        safePlaceAPI = authManager.API_CONFIG.baseURL + 'safe_places.php';
        headerAPI = authManager.API_CONFIG.getHeaders();
        formHeaderAPI = authManager.API_CONFIG.getFormHeaders();
        this.currentUser = authManager.getUser();

        if (!authManager.isAuthenticated()) {
            window.location.href = 'auth/login.php';
            return;
        }
        
        // Check if user is admin
        if (this.currentUser.role !== 'admin') {
            window.location.href = 'user/dashboard.php';
            return;
        }
        
        this.safePlaces = [];
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.totalPages = 0;
        this.isEditing = false;
        this.editingId = null;
        this.stats = null;
        this.map = null;
        this.markers = [];
        this.temporaryMarker = null;
        this.currentView = 'map'; // 'map' or 'list'
        
        this.init();
    }

    async init() {
        try {
            // Initialize map first
            this.initMap();

            // Load initial data
            await this.loadSafePlacesStats();
            await this.loadSafePlaces();
            
            // Initialize event listeners
            this.initEventListeners();
            
        } catch (error) {
            console.error('Failed to initialize SafePlaceManager:', error);
            this.showToast('Failed to initialize safe place manager', 'error');
        }
    }

    initEventListeners() {
        // Safe place form submission
        const safePlaceForm = document.getElementById('safePlaceForm');
        if (safePlaceForm) {
            safePlaceForm.addEventListener('submit', (e) => this.handleSafePlaceSubmit(e));
        }

        // Search functionality
        const searchInput = document.getElementById('safePlaceSearchInput');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.searchSafePlaces();
                }
            });
        }

        // Filter changes
        const activeFilter = document.getElementById('activeFilter');
        if (activeFilter) {
            activeFilter.addEventListener('change', () => {
                this.loadSafePlaces();
            });
        }
    }

    // ============ MAP INITIALIZATION ============

    initMap() {
        try {
            // Check if MapLibre is loaded
            if (typeof maplibregl === 'undefined') {
                console.error('MapLibre GL JS is not loaded');
                return;
            }

            this.map = new maplibregl.Map({
                container: 'map',
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
                center: [123.33649484, 10.94875323], // Cadiz City coordinates
                zoom: 12
            });

            // Add navigation controls
            this.map.addControl(new maplibregl.NavigationControl());
            
            // Add fullscreen control
            this.map.addControl(new maplibregl.FullscreenControl());

            this.map.on('load', () => {
                console.log('Map loaded successfully');
            });

            // Add click event to map for creating new safe places
            this.map.on('click', (e) => {
                this.openCreateModalAtLocation(e.lngLat.lat, e.lngLat.lng);
            });

            // Change cursor to pointer to indicate clickable
            this.map.getCanvas().style.cursor = 'pointer';

        } catch (error) {
            console.error('Failed to initialize map:', error);
        }
    }

    updateMapWithSafePlaces() {
        if (!this.map) return;

        // Clear existing markers
        this.markers.forEach(marker => marker.remove());
        this.markers = [];

        // Add markers for each safe place
        this.safePlaces.forEach(safePlace => {
            if (safePlace.lat && safePlace.long) {
                // Create popup content
                const popupContent = `
                    <div class="map-popup">
                        <h6><strong>${safePlace.location_name}</strong></h6>
                        <p class="mb-1"><i class="fas fa-map-marker-alt"></i> ${safePlace.lat}, ${safePlace.long}</p>
                        ${safePlace.description ? `<p class="mb-2">${safePlace.description}</p>` : ''}
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="safePlaceManager.editSafePlace(${safePlace.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            ${safePlace.is_active == 1 
                                ? `<button class="btn btn-sm btn-danger" onclick="safePlaceManager.deleteSafePlace(${safePlace.id})">
                                     <i class="fas fa-trash"></i> Delete
                                   </button>`
                                : ``
                            }
                        </div>
                    </div>
                `;

                // Create marker
                const marker = new maplibregl.Marker({
                    color: safePlace.is_active == 1 ? '#007cba' : '#6c757d'
                })
                .setLngLat([parseFloat(safePlace.long), parseFloat(safePlace.lat)])
                .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(popupContent))
                .addTo(this.map);

                // Add click event to marker to prevent map click event
                marker.getElement().addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Toggle the popup when marker is clicked
                    if (marker.getPopup().isOpen()) {
                        marker.getPopup().remove();
                    } else {
                        marker.getPopup().addTo(this.map);
                    }
                });

                this.markers.push(marker);
            }
        });

        // Fit map to show all markers if there are any
        if (this.markers.length > 0) {
            const bounds = new maplibregl.LngLatBounds();
            this.markers.forEach(marker => {
                bounds.extend(marker.getLngLat());
            });
            this.map.fitBounds(bounds, { padding: 50 });
        }
    }

    // ============ DATA LOADING ============

    async loadSafePlacesStats() {
        try {
            const response = await axios.get(`${safePlaceAPI}?action=getSafePlaceStats`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.stats = response.data.data;
                this.updateStatsDisplay();
            }
        } catch (error) {
            console.error('Failed to load safe places stats:', error);
        }
    }

    async loadSafePlaces(page = 1) {
        try {
            const activeOnly = document.getElementById('activeFilter')?.value === 'true';
            
            // For map view, load all safe places without pagination
            if (this.currentView === 'map') {
                const response = await axios.get(`${safePlaceAPI}?action=getSafePlacesForMapLibre`, {
                    headers: headerAPI
                });

                if (response.data.success) {
                    const result = response.data.data;
                    // Convert GeoJSON features to safe places format
                    this.safePlaces = result.features.map(feature => ({
                        id: feature.properties.id,
                        lat: feature.geometry.coordinates[1], // GeoJSON uses [lng, lat]
                        long: feature.geometry.coordinates[0],
                        location_name: feature.properties.name,
                        description: feature.properties.description,
                        created_by: feature.properties.created_by,
                        is_active: feature.properties.is_active,
                        created_at: feature.properties.created_at,
                        updated_at: feature.properties.updated_at,
                        created_by_username: feature.properties.created_by_username
                    }));
                    this.updateMapWithSafePlaces();
                }
            } else {
                // For list view, use pagination
                const response = await axios.get(`${safePlaceAPI}?action=getSafePlacesPaginated&page=${page}&limit=${this.itemsPerPage}&active_only=${activeOnly}`, {
                    headers: headerAPI
                });

                if (response.data.success) {
                    const result = response.data.data;
                    this.safePlaces = result.data;
                    this.currentPage = result.pagination.current_page;
                    this.totalPages = result.pagination.total_pages;
                    this.updateSafePlacesDisplay();
                    this.updatePagination();
                }
            }
        } catch (error) {
            console.error('Failed to load safe places:', error);
            this.showToast('Failed to load safe places', 'error');
        }
    }

    async searchSafePlaces() {
        try {
            const searchTerm = document.getElementById('safePlaceSearchInput').value.trim();
            const activeOnly = document.getElementById('activeFilter')?.value === 'true';
            
            if (!searchTerm) {
                this.loadSafePlaces();
                return;
            }

            const response = await axios.get(`${safePlaceAPI}?action=searchSafePlaces&search=${encodeURIComponent(searchTerm)}&active_only=${activeOnly}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.safePlaces = response.data.data;
                
                if (this.currentView === 'map') {
                    this.updateMapWithSafePlaces();
                } else {
                    this.updateSafePlacesDisplay();
                    // Hide pagination during search
                    const paginationContainer = document.getElementById('paginationContainer');
                    if (paginationContainer) paginationContainer.style.display = 'none';
                }
                
                this.showToast(`Found ${this.safePlaces.length} safe places matching "${searchTerm}"`, 'success');
            }
        } catch (error) {
            console.error('Failed to search safe places:', error);
            this.showToast('Failed to search safe places', 'error');
        }
    }

    // ============ CRUD OPERATIONS ============

    async handleSafePlaceSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', this.isEditing ? 'updateSafePlace' : 'createSafePlace');
        
        // Add form fields
        const formElements = e.target.elements;
        for (let el of formElements) {
            if (el.name) {
                formData.append(el.name, el.value);
            }
        }
        let actionType = 'createSafePlace';
        // Add ID for updates
        if (this.isEditing) {
            formData.append('id', this.editingId);
            actionType = 'updateSafePlace';
        }
        
        try {
            const response = await axios.post(`${safePlaceAPI}?action=${actionType}`, formData, {
                headers: formHeaderAPI
            });

            if (response.data.success) {
                this.showToast(response.data.message, 'success');
                
                // Clean up temporary marker
                if (this.temporaryMarker) {
                    this.temporaryMarker.remove();
                    this.temporaryMarker = null;
                }
                
                this.resetForm();
                await this.loadSafePlaces();
                await this.loadSafePlacesStats();
            } else {
                this.showToast(response.data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to save safe place:', error);
            this.showToast('Failed to save safe place', 'error');
        }
    }

    async deleteSafePlace(id) {
        if (!confirm('Are you sure you want to delete this safe place?')) {
            return;
        }

        try {
            const response = await axios.post(`${safePlaceAPI}?action=deleteSafePlace`, {
                id: id
            }, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.showToast(response.data.message, 'success');
                await this.loadSafePlaces();
                await this.loadSafePlacesStats();
            } else {
                this.showToast(response.data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to delete safe place:', error);
            this.showToast('Failed to delete safe place', 'error');
        }
    }

    async restoreSafePlace(id) {
        try {
            const response = await axios.post(`${safePlaceAPI}?action=restoreSafePlace`, {
                id: id
            }, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.showToast(response.data.message, 'success');
                await this.loadSafePlaces();
                await this.loadSafePlacesStats();
            } else {
                this.showToast(response.data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to restore safe place:', error);
            this.showToast('Failed to restore safe place', 'error');
        }
    }

    editSafePlace(id) {
        const safePlace = this.safePlaces.find(sp => sp.id == id);
        if (!safePlace) return;

        // Populate form with existing data
        document.getElementById('location_name').value = safePlace.location_name;
        document.getElementById('lat').value = safePlace.lat;
        document.getElementById('long').value = safePlace.long;
        document.getElementById('description').value = safePlace.description || '';

        // Update form state
        this.isEditing = true;
        this.editingId = id;
        
        // Update form title and button
        document.querySelector('#safePlaceModal .modal-title').textContent = 'Edit Safe Place';
        document.querySelector('#safePlaceModal .btn-primary').textContent = 'Update Safe Place';
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('safePlaceModal'));
        modal.show();
    }

    resetForm() {
        document.getElementById('safePlaceForm').reset();
        this.isEditing = false;
        this.editingId = null;
        
        // Remove temporary marker if it exists
        if (this.temporaryMarker) {
            this.temporaryMarker.remove();
            this.temporaryMarker = null;
        }
        
        // Hide coordinates alert
        document.getElementById('coordinatesAlert').style.display = 'none';
        
        // Reset form title and button
        document.querySelector('#safePlaceModal .modal-title').textContent = 'Add New Safe Place';
        document.querySelector('#safePlaceModal .btn-primary').textContent = 'Create Safe Place';
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('safePlaceModal'));
        if (modal) {
            modal.hide();
        }
    }

    // ============ DISPLAY UPDATES ============

    updateStatsDisplay() {
        if (!this.stats) return;

        const statsElements = {
            totalSafePlaces: this.stats.total_safe_places || 0,
            activeSafePlaces: this.stats.active_safe_places || 0,
            inactiveSafePlaces: this.stats.inactive_safe_places || 0,
            uniqueCreators: this.stats.unique_creators || 0
        };

        Object.entries(statsElements).forEach(([key, value]) => {
            const element = document.getElementById(key);
            if (element) {
                element.textContent = value;
            }
        });
    }

    updateSafePlacesDisplay() {
        const container = document.getElementById('safePlacesContainer');
        if (!container) return;

        if (this.safePlaces.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                    <p>No safe places found</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.safePlaces.map(safePlace => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="card-title">${safePlace.location_name}</h5>
                            <p class="card-text text-muted">
                                <i class="fas fa-map-marker-alt"></i> 
                                ${safePlace.lat}, ${safePlace.long}
                            </p>
                            ${safePlace.description ? `<p class="card-text">${safePlace.description}</p>` : ''}
                            <small class="text-muted">
                                Created: ${new Date(safePlace.created_at).toLocaleDateString()}
                                ${safePlace.created_by_username ? ` by ${safePlace.created_by_username}` : ''}
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="mb-2">
                                <span class="badge ${safePlace.is_active == 1 ? 'bg-success' : 'bg-secondary'}">
                                    ${safePlace.is_active == 1 ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" onclick="safePlaceManager.editSafePlace(${safePlace.id})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                ${safePlace.is_active == 1 
                                    ? `<button class="btn btn-sm btn-outline-danger" onclick="safePlaceManager.deleteSafePlace(${safePlace.id})">
                                         <i class="fas fa-trash"></i> Delete
                                       </button>`
                                    : ``
                                }
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    updatePagination() {
        const paginationContainer = document.getElementById('paginationContainer');
        if (!paginationContainer || this.totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let paginationHTML = '<nav><ul class="pagination justify-content-center">';
        
        // Previous button
        paginationHTML += `
            <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="safePlaceManager.loadSafePlaces(${this.currentPage - 1}); return false;">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(this.totalPages, this.currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="safePlaceManager.loadSafePlaces(${i}); return false;">${i}</a>
                </li>
            `;
        }

        // Next button
        paginationHTML += `
            <li class="page-item ${this.currentPage === this.totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="safePlaceManager.loadSafePlaces(${this.currentPage + 1}); return false;">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;

        paginationHTML += '</ul></nav>';
        paginationContainer.innerHTML = paginationHTML;
    }

    // ============ UTILITY METHODS ============

    showToast(message, type = 'info') {
        // Create toast element
        const toastHTML = `
            <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        // Add to toast container
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }

        toastContainer.insertAdjacentHTML('beforeend', toastHTML);

        // Show toast
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();

        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    // ============ PUBLIC METHODS ============

    async refreshData() {
        try {
            await this.loadSafePlacesStats();
            await this.loadSafePlaces(this.currentPage);
            this.showToast('Data refreshed successfully!', 'success');
        } catch (error) {
            console.error('Failed to refresh data:', error);
            this.showToast('Failed to refresh data', 'error');
        }
    }

    openAddModal() {
        this.resetForm();
        const modal = new bootstrap.Modal(document.getElementById('safePlaceModal'));
        modal.show();
    }

    openCreateModalAtLocation(lat, lng) {
        // Reset form and populate coordinates
        this.resetForm();
        document.getElementById('lat').value = lat;
        document.getElementById('long').value = lng;
        
        // Clear location name so user must enter it
        document.getElementById('location_name').value = '';
        
        // Show coordinates alert
        document.getElementById('coordinatesAlert').style.display = 'block';
        
        // Update form title to indicate it's a new location
        document.querySelector('#safePlaceModal .modal-title').textContent = 'Add Safe Place at Selected Location';
        document.querySelector('#safePlaceModal .btn-primary').textContent = 'Create Safe Place';
        
        // Update map preview
        setTimeout(() => {
            updateMapPreview();
        }, 300);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('safePlaceModal'));
        modal.show();
        
        // Focus on location name field after modal is shown
        setTimeout(() => {
            document.getElementById('location_name').focus();
        }, 500);
        
        // Show a temporary marker at the clicked location
        this.showTemporaryMarker(lat, lng);
    }

    showTemporaryMarker(lat, lng) {
        // Remove any existing temporary marker
        if (this.temporaryMarker) {
            this.temporaryMarker.remove();
        }

        // Create a temporary marker
        this.temporaryMarker = new maplibregl.Marker({
            color: '#ff6b6b',
            className: 'temporary-marker'
        })
        .setLngLat([lng, lat])
        .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(`
            <div class="text-center">
                <h6><strong>New Safe Place Location</strong></h6>
                <p class="mb-1">${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
                <small class="text-muted">Click to add safe place name</small>
            </div>
        `))
        .addTo(this.map);

        // Open the popup
        this.temporaryMarker.getPopup().addTo(this.map);
    }

    async toggleView() {
        this.currentView = this.currentView === 'map' ? 'list' : 'map';
        this.updateViewDisplay();
        // Reload data with appropriate method based on view
        await this.loadSafePlaces(this.currentPage);
    }

    updateViewDisplay() {
        const mapContainer = document.getElementById('mapContainer');
        const safePlacesContainer = document.getElementById('safePlacesContainer');
        const paginationContainer = document.getElementById('paginationContainer');
        const viewToggleIcon = document.getElementById('viewToggleIcon');
        const viewToggleText = document.getElementById('viewToggleText');

        if (this.currentView === 'map') {
            // Show map, hide list
            if (mapContainer) mapContainer.style.display = 'block';
            if (safePlacesContainer) safePlacesContainer.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-map-marked-alt fa-3x mb-3"></i><p>Map view active - All safe places are shown as markers</p></div>';
            // Hide pagination for map view
            if (paginationContainer) paginationContainer.style.display = 'none';
            if (viewToggleIcon) viewToggleIcon.className = 'fas fa-list';
            if (viewToggleText) viewToggleText.textContent = 'List View';
        } else {
            // Show list, hide map
            if (mapContainer) mapContainer.style.display = 'none';
            this.updateSafePlacesDisplay();
            this.updatePagination();
            // Show pagination for list view
            if (paginationContainer) paginationContainer.style.display = 'block';
            if (viewToggleIcon) viewToggleIcon.className = 'fas fa-map';
            if (viewToggleText) viewToggleText.textContent = 'Map View';
        }
    }
}

// Global functions for button onclick events
function refreshData() {
    if (window.safePlaceManager) {
        window.safePlaceManager.refreshData();
    }
}

function openAddSafePlaceModal() {
    if (window.safePlaceManager) {
        window.safePlaceManager.openAddModal();
    }
}

function toggleView() {
    if (window.safePlaceManager) {
        window.safePlaceManager.toggleView();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.safePlaceManager = new SafePlaceManager();
});
