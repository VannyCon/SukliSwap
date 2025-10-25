/**
 * Requests Manager - Handles coin request operations
 */
let coinExchangeAPI = null;
let headerAPI = null;
let formHeaderAPI = null;
let userRequestsAPI = null;
let trCoinRequestAPI = null;
let safePlaceAPI = null;
let coinTypes = [];
let requests = [];
class RequestsManager {
    constructor() {
        const authManager = new AuthManager();
        coinExchangeAPI = authManager.API_CONFIG.baseURL + 'coin_exchange.php';
        userRequestsAPI = authManager.API_CONFIG.baseURL + 'user_requests.php';
        trCoinRequestAPI = authManager.API_CONFIG.baseURL + 'tr_coin_request.php';
        safePlaceAPI = authManager.API_CONFIG.baseURL + 'safe_places.php';
        headerAPI = authManager.API_CONFIG.getHeaders();
        formHeaderAPI = authManager.API_CONFIG.getFormHeaders();
        this.authManager = authManager;
        this.requests = [];
        this.coinTypes = [];
        this.filters = {
            status: '',
            coin_type_id: '',
            search: ''
        };
        this.safePlaces = [];
        this.map = null;
        this.markers = [];
        this.init();
    }

    init() {
        this.loadCoinTypes();
        this.loadRequests();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Create request form
        document.getElementById('coinRequestForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.createRequest();
        });

        // Edit request form
        document.getElementById('editRequestForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateRequest();
        });

        // Location buttons
        document.getElementById('getLocationBtn')?.addEventListener('click', () => {
            this.openLocationPicker('request');
        });

        document.getElementById('getEditLocationBtn')?.addEventListener('click', () => {
            this.openLocationPicker('edit_request');
        });

        // Meeting schedule form
        document.getElementById('meetingScheduleForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleMeetingSchedule();
        });

        // Set minimum date to today when modal opens
        document.getElementById('meetingScheduleModal')?.addEventListener('show.bs.modal', () => {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('meeting_date').min = today;
        });
    }

    async loadCoinTypes() {
        try {
            const response = await axios.get(`${coinExchangeAPI}?action=getCoinTypes`, {
                headers: headerAPI
            });
            
            if (response.data.success) {
                this.coinTypes = response.data.data;
                this.populateCoinTypeSelects();
            
            }
        } catch (error) {
            console.error('Error loading coin types:', error);
        }
    }

    populateCoinTypeSelects() {
        const selects = document.querySelectorAll('.coin-type-select');
        selects.forEach(select => {
            select.innerHTML = '<option value="">Select coin type</option>';
            this.coinTypes.forEach(coinType => {
                const option = document.createElement('option');
                option.value = coinType.id;
                // Use the correct field names from API response
                option.textContent = `${coinType.description} (₱${coinType.denomination})`;
                select.appendChild(option);
            });
        });

        // Populate filter dropdown
        const filterSelect = document.getElementById('coinTypeFilter');
        if (filterSelect) {
            filterSelect.innerHTML = '<option value="">All Coin Types</option>';
            this.coinTypes.forEach(coinType => {
                const option = document.createElement('option');
                option.value = coinType.id;
                // Use the correct field names from API response
                option.textContent = `${coinType.description} (₱${coinType.denomination})`;
                filterSelect.appendChild(option);
            });
        }
    }

    async loadRequests() {
        try {
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params.append(key, this.filters[key]);
                }
            });

            const response = await axios.get(`${coinExchangeAPI}?action=getMyRequests&${params}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.requests = response.data.data;
                this.renderRequests();
                this.updateStats();
            }
        } catch (error) {
            console.error('Error loading requests:', error);
            CustomToast.show('error', 'Failed to load requests');
        }
    }

    renderRequests() {
        const container = document.getElementById('requestsContainer');
        if (!container) return;

        if (this.requests.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No requests found</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                        <i class="fas fa-plus"></i> Create Your First Request
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = this.requests.map(request => {
            return `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1 justify-content-center text-center">
                            <img src="../../../../${request.coin_image_path || '/assets/images/default-coin.png'}" 
                                 alt="${request.description}" class=""s style="width: 50px; height: 50px;">
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">${request.coin_description}</h6>
                            <small class="text-muted">Value: ₱${request.denomination}</small>
                        </div>
                        <div class="col-md-2">
                            <strong>${request.quantity}</strong> pieces
                        </div>
                        <div class="col-md-2">
                            <span class="badge bg-${this.getStatusColor(request.status)}">${request.status}</span>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">${new Date(request.created_at).toLocaleDateString()}</small>
                        </div>
                        <div class="col-md-2">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="requestsManager.editRequest(${request.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${request.status === 'active' ? `
                                    <button class="btn btn-outline-danger" onclick="requestsManager.cancelRequest(${request.id})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                ` : ''}
                                <button class="btn btn-outline-info" title="View Offers" onclick="requestsManager.showTargetedOffers(${request.id})">
                                    <i class="fas fa-users"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    ${request.notes ? `
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">
                                    <strong>Notes:</strong> ${request.notes}
                                </small>
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `}).join('');
    }

    getStatusColor(status) {
        const colors = {
            'active': 'primary',
            'matched': 'warning',
            'completed': 'success',
            'cancelled': 'secondary'
        };
        return colors[status] || 'secondary';
    }

    updateStats() {
        const stats = {
            total_requests: this.requests.length,
            active_requests: this.requests.filter(r => r.status === 'active').length,
            matched_requests: this.requests.filter(r => r.status === 'matched').length,
            completed_requests: this.requests.filter(r => r.status === 'completed').length
        };

        Object.keys(stats).forEach(key => {
            const element = document.getElementById(key.replace('_requests', 'RequestsCount'));
            if (element) {
                element.textContent = stats[key];
            }
        });
    }

    async createRequest() {
        const form = document.getElementById('coinRequestForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Convert to URL-encoded string for PHP $_POST compatibility
        const urlEncodedData = new URLSearchParams(data).toString();

        try {
            const response = await axios.post(`${userRequestsAPI}?action=createRequest`, urlEncodedData, {
                headers: formHeaderAPI
            });

            const result = response.data;
            
            if (result.success) {
                CustomToast.show('success', 'Request created successfully');
                form.reset();
                // Hide modal with proper cleanup
                const modalElement = document.getElementById('createRequestModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                }
                this.loadRequests();
            } else {
                CustomToast.show('error', result.message);
            }
        } catch (error) {
            console.error('Error creating request:', error);
            CustomToast.show('error', 'Failed to create request');
        }
    }

    async updateRequest() {
        const form = document.getElementById('editRequestForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const requestId = data.request_id;

        // Convert to URL-encoded string for PHP $_POST compatibility
        const urlEncodedData = new URLSearchParams(data).toString();

        try {
            const response = await axios.post(`${userRequestsAPI}?action=updateRequest&request_id=${requestId}`, urlEncodedData, {
                headers: formHeaderAPI
            });

            const result = response.data;
            
            if (result.success) {
                CustomToast.show('success', 'Request updated successfully');
                // Hide modal with proper cleanup
                const modalElement = document.getElementById('editRequestModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                }
                this.loadRequests();
            } else {
                CustomToast.show('error', result.message);
            }
        } catch (error) {
            console.error('Error updating request:', error);
            CustomToast.show('error', 'Failed to update request');
        }
    }

    editRequest(requestId) {
        
        // Convert requestId to both number and string for comparison
        const numericId = parseInt(requestId);
        const stringId = String(requestId);
        
        // Try multiple comparison methods to handle type mismatches
        const request = this.requests.find(r => {
            const rId = r.id;
            const rIdNum = parseInt(rId);
            const rIdStr = String(rId);
            
            const matches = rId === requestId || 
                   rId === numericId || 
                   rId === stringId ||
                   rIdNum === requestId ||
                   rIdNum === numericId ||
                   rIdStr === stringId;
                   
            
            return matches;
        });
        
        if (!request) {
            CustomToast.show('error', `Request with ID ${requestId} not found`);
            return;
        }


        // Populate edit form
        document.getElementById('edit_request_id').value = request.id;
        document.getElementById('edit_request_coin_type').value = request.coin_type_id;
        document.getElementById('edit_request_quantity').value = request.quantity;
        document.getElementById('edit_request_location').value = request.preferred_meeting_location || '';
        document.getElementById('edit_request_meeting_longitude').value = request.meeting_longitude || '';
        document.getElementById('edit_request_meeting_latitude').value = request.meeting_latitude || '';
        document.getElementById('edit_request_notes').value = request.notes || '';

        // Show modal with proper cleanup and error handling
        const modalElement = document.getElementById('editRequestModal');
        
        if (modalElement) {
            // Clean up any existing modal instances
            const existingModal = bootstrap.Modal.getInstance(modalElement);
            if (existingModal) {
                existingModal.dispose();
            }
            
            // Clean up any modal backdrops
            this.cleanupModalBackdrops();
            
            // Create and show new modal
            try {
                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                console.log('Showing modal');
                modal.show();
                console.log('Modal show() called successfully');
            } catch (error) {
                console.error('Error showing edit modal:', error);
                CustomToast.show('error', 'Failed to open edit modal');
            }
        } else {
            console.error('Edit modal element not found');
            CustomToast.show('error', 'Edit modal not found');
        }
    }

    async cancelRequest(requestId) {
        if (!confirm('Are you sure you want to cancel this request?')) return;

        try {
            const response = await axios.post(`${userRequestsAPI}?action=cancelRequest&request_id=${requestId}`, {}, {
                headers: formHeaderAPI
            });

            const result = response.data;
            
            if (result.success) {
                CustomToast.show('success', 'Request cancelled successfully');
                this.loadRequests();
            } else {
                CustomToast.show('error', result.message);
            }
        } catch (error) {
            console.error('Error cancelling request:', error);
            CustomToast.show('error', 'Failed to cancel request');
        }
    }

    viewRequest(requestId) {
        const request = this.requests.find(r => r.id === requestId);
        if (!request) return;

        // Show request details in a modal or redirect to details page
        alert(`Request Details:\n\nCoin: ${request.coin_description}\nValue: ₱${request.denomination}\nQuantity: ${request.quantity}\nStatus: ${request.status}\nLocation: ${request.preferred_meeting_location || 'Not specified'}\nNotes: ${request.notes || 'None'}`);
    }

    async showTargetedOffers(requestId) {
        const tbody = document.getElementById('targetedOffersTbody');
        const countEl = document.getElementById('targetedOffersCount');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
        }
        try {
            const resp = await axios.get(`${trCoinRequestAPI}?action=listByPostRequest&post_request_id=${requestId}`, { headers: headerAPI });
            const items = resp.data?.data || [];
            if (!tbody) return;
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No offers found</td></tr>';
            } else {
                tbody.innerHTML = items.map(o => `
                    <tr>
                        <td>${o.username || (o.first_name ? o.first_name + ' ' + (o.last_name||'') : 'User '+o.offeror_id)}</td>
                        <td>${o.requested_quantity}</td>
                        <td>${o.message || ''}</td>
                        <td><span class="badge bg-${o.status == 'pending' ? 'warning' : o.status == 'accepted' ? 'success' : 'danger'}">${o.status}</span></td>
                        <td>
                            ${o.status === 'pending' ? `
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-success" onclick="requestsManager.acceptTargetedOffer(${o.id}, ${o.requested_quantity})">Accept</button>
                                <button class="btn btn-outline-danger" onclick="requestsManager.rejectTargetedOffer(${o.id})">Reject</button>
                            </div>` : ''}
                        </td>
                    </tr>
                `).join('');
            }
            if (countEl) countEl.textContent = `${items.length} offer(s)`;
            const modalEl = document.getElementById('targetedOffersModal');
            if (modalEl) new bootstrap.Modal(modalEl).show();
        } catch (e) {
            console.error('Failed to load targeted offers', e);
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-danger text-center">Failed to load</td></tr>';
        }
    }

    acceptTargetedOffer(id, requestedQuantity) {
        // Set the request ID and show the meeting schedule modal
        document.getElementById('schedule_request_id').value = id;
        document.getElementById('meeting_quantity').value = requestedQuantity;
        new bootstrap.Modal(document.getElementById('meetingScheduleModal')).show();
    }

    async handleMeetingSchedule() {
        const form = document.getElementById('meetingScheduleForm');
        const formData = new FormData(form);
        
        // Combine date and time into a single datetime string
        const date = formData.get('meeting_date');
        const time = formData.get('meeting_time');
        const datetime = `${date} ${time}:00`;
        
        // Add the combined datetime to formData
        formData.set('scheduled_meeting_time', datetime);
        formData.set('meeting_quantity', formData.get('meeting_quantity'));
        // Debug logging
        console.log('Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
        
        await this.acceptTargetedOfferWithSchedule(formData);
    }

    async acceptTargetedOfferWithSchedule(formData) {
        try {
            const resp = await axios.post(`${trCoinRequestAPI}?action=accept`, formData, { headers: formHeaderAPI });
            if (resp.data?.success) {
                CustomToast.show('success', 'Offer accepted, transaction created and meeting scheduled');
                // Close both modals
                bootstrap.Modal.getInstance(document.getElementById('meetingScheduleModal'))?.hide();
                const modalEl = document.getElementById('targetedOffersModal');
                if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
                this.loadRequests();
            } else {
                CustomToast.show('error', resp.data?.message || 'Failed to accept');
            }
        } catch (e) {
            console.error('Accept failed', e);
            CustomToast.show('error', 'Accept failed');
        }
    }

    async rejectTargetedOffer(id) {
        try {
            const form = new URLSearchParams({ id: String(id) }).toString();
            const resp = await axios.post(`${trCoinRequestAPI}?action=reject`, form, { headers: formHeaderAPI });
            if (resp.data?.success) {
                CustomToast.show('success', 'Offer rejected');
                const modalEl = document.getElementById('targetedOffersModal');
                if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
                this.loadRequests();
            } else {
                CustomToast.show('error', resp.data?.message || 'Failed to reject');
            }
        } catch (e) {
            console.error('Reject failed', e);
            CustomToast.show('error', 'Reject failed');
        }
    }

    filterRequests() {
        this.filters.status = document.getElementById('statusFilter').value;
        this.filters.coin_type_id = document.getElementById('coinTypeFilter').value;
        this.loadRequests();
    }

    searchRequests() {
        this.filters.search = document.getElementById('searchRequests').value;
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.loadRequests();
        }, 500);
    }

    clearFilters() {
        this.filters = {
            status: '',
            coin_type_id: '',
            search: ''
        };
        
        document.getElementById('statusFilter').value = '';
        document.getElementById('coinTypeFilter').value = '';
        document.getElementById('searchRequests').value = '';
        
        this.loadRequests();
    }
    openLocationPicker(prefix) {
		const triggerBtn = document.getElementById(prefix === 'request' ? 'getLocationBtn' : 'getEditLocationBtn');
		const modalEl = document.getElementById('locationPickerModal');
		const parentModalEl = document.getElementById(prefix === 'request' ? 'createRequestModal' : 'editRequestModal');
		if (!modalEl) {
            this.showError('Location picker is unavailable');
            return;
        }
          // Function to close location picker and return to parent modal
		const closeLocationPicker = () => {
			modalEl.classList.remove('show');
			modalEl.style.display = 'none';
			if (parentModalEl) {
				parentModalEl.style.display = 'block';
				parentModalEl.classList.add('show');
			}
			// Clean up map
			if (this.map) {
				this.map.remove();
				this.map = null;
			}
			if (window.__locationPickerMap) {
				window.__locationPickerMap = null;
			}
		};
		
		// Attach close handlers to close and cancel buttons
		const closeBtn = modalEl.querySelector('.btn-close');
		const cancelBtn = modalEl.querySelector('.btn-secondary[data-bs-dismiss="modal"]');
		
		if (closeBtn) {
			closeBtn.onclick = (e) => {
				e.preventDefault();
				closeLocationPicker();
			};
		}
		
		if (cancelBtn) {
			cancelBtn.onclick = (e) => {
				e.preventDefault();
				closeLocationPicker();
			};
		}
		// Follow requested behavior: manually toggle displays and 'show' class to bypass .fade opacity
		if (parentModalEl) {
			parentModalEl.classList.remove('show');
			parentModalEl.style.display = 'none';
		}
		modalEl.style.display = 'block';
		modalEl.classList.add('show');
		// Ensure a backdrop exists
		let backdrop = document.querySelector('.modal-backdrop');
		if (!backdrop) {
			backdrop = document.createElement('div');
			backdrop.className = 'modal-backdrop fade show';
			document.body.appendChild(backdrop);
		}
		document.body.classList.add('modal-open');

        const mapContainerId = 'locationPickerMap';
        const initMap = (center) => {   
            try {
                if (window.__locationPickerMap) {
                    window.__locationPickerMap.remove();
                    window.__locationPickerMap = null;
                }
                const map = new maplibregl.Map({
                    container: mapContainerId,
                    style: 'https://tiles.openfreemap.org/styles/bright',
                    center: center,
                    zoom: 19
                });
                map.addControl(new maplibregl.NavigationControl(), 'top-right');

                let marker = null;
                const placeMarker = (lngLat) => {
                    if (marker) marker.remove();
                    marker = new maplibregl.Marker({ color: '#e11d48' }).setLngLat(lngLat).addTo(map);
                };

                map.on('click', (e) => {
                    const lngLat = [e.lngLat.lng, e.lngLat.lat];
                    placeMarker(lngLat);
                    // Confirm selection
                    const ok = confirm(`Use this meeting place?\nLatitude: ${lngLat[1].toFixed(6)}\nLongitude: ${lngLat[0].toFixed(6)}`);
					if (ok) {
						const latInputId = prefix === 'request' ? 'request_meeting_latitude' : 'edit_request_meeting_latitude';
						const lngInputId = prefix === 'request' ? 'request_meeting_longitude' : 'edit_request_meeting_longitude';
						document.getElementById(latInputId).value = lngLat[1];
						document.getElementById(lngInputId).value = lngLat[0];
						// Close second modal and reopen first (per requested behavior)
						modalEl.classList.remove('show');
						modalEl.style.display = 'none';
						if (parentModalEl) {
							parentModalEl.style.display = 'block';
							parentModalEl.classList.add('show');
						}
                        CustomToast.show('success', 'Meeting location set');
                    }
                });

                window.__locationPickerMap = map;
                // Set the map instance and load safe places AFTER map is initialized
                this.map = map;
                this.loadSafePlaces();
                setTimeout(() => map.resize(), 200);
            } catch (e) {
                console.error('Failed to initialize location picker map', e);
                this.showError('Failed to initialize map');
            }
        };

		// Try to center at user's current position; fallback to default center
		if (navigator.geolocation) {
			if (triggerBtn) {
				triggerBtn.disabled = true;
				triggerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Locating...';
			}
            navigator.geolocation.getCurrentPosition(
                (pos) => {
					if (triggerBtn) {
						triggerBtn.disabled = false;
						triggerBtn.innerHTML = '<i class=\"fas fa-location-arrow\"></i> Pick Meeting Location';
					}
                    initMap([pos.coords.longitude, pos.coords.latitude]);
                },
                () => {
					if (triggerBtn) {
						triggerBtn.disabled = false;
						triggerBtn.innerHTML = '<i class=\"fas fa-location-arrow\"></i> Pick Meeting Location';
					}
                    initMap([120.9842, 14.5995]);
                },
                { enableHighAccuracy: true, timeout: 8000 }
            );
        } else {
            initMap([120.9842, 14.5995]);
        }
    }

    getCurrentLocation(prefix) {
        if (!navigator.geolocation) {
            CustomToast.show('error', 'Geolocation is not supported by this browser');
            return;
        }

        const locationBtn = document.getElementById(prefix === 'request' ? 'getLocationBtn' : 'getEditLocationBtn');
        locationBtn.disabled = true;
        locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                document.getElementById(`${prefix}_meeting_latitude`).value = lat;
                document.getElementById(`${prefix}_meeting_longitude`).value = lng;
                
                // Get address from coordinates (you might want to use a geocoding service)
                // document.getElementById(`${prefix}_location`).value = `${lat}, ${lng}`;
                
                locationBtn.disabled = false;
                locationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get Current Location';
                
                CustomToast.show('success', 'Location updated successfully');
            },
            (error) => {
                locationBtn.disabled = false;
                locationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get Current Location';
                
                let message = 'Failed to get location';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = 'Location access denied by user';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = 'Location information unavailable';
                        break;
                    case error.TIMEOUT:
                        message = 'Location request timed out';
                        break;
                }
                CustomToast.show('error', message);
            }
        );
    }

    showSuccess(message) {
        // You can implement a toast notification system here
        alert(message);
    }

    showError(message) {
        // You can implement a toast notification system here
        alert('Error: ' + message);
    }

    // Helper function to clean up modal backdrops
    cleanupModalBackdrops() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    // Test function to manually test request lookup
    testRequestLookup(id) {
        console.log('Testing request lookup for ID:', id);
        console.log('Available requests:', this.requests.map(r => ({ id: r.id, type: typeof r.id })));
        
        const numericId = parseInt(id);
        const stringId = String(id);
        
        const request = this.requests.find(r => {
            const rId = r.id;
            const rIdNum = parseInt(rId);
            const rIdStr = String(rId);
            
            return rId === id || 
                   rId === numericId || 
                   rId === stringId ||
                   rIdNum === id ||
                   rIdNum === numericId ||
                   rIdStr === stringId;
        });
        
        console.log('Found request:', request);
        return request;
    }
    async loadSafePlaces() {
        try {
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
            
        } catch (error) {
            console.error('Failed to load safe places:', error);
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
                        <h6><strong><i class="fas fa-map-marker-alt"></i>${safePlace.location_name}</strong></h6>
                        ${safePlace.description ? `<p class="mb-2">${safePlace.description}</p>` : ''}
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
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.requestsManager = new RequestsManager();
    // Make test lookup function globally available
    window.testRequestLookup = (id) => {
        if (window.requestsManager) {
            return window.requestsManager.testRequestLookup(id);
        } else {
            console.error('RequestsManager not initialized');
        }
    };
});
