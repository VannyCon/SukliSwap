/**
 * Offers Manager - Handles coin offer operations
 */
let coinExchangeAPI = null;
let coinOffersAPI = null;
let trCoinOfferAPI = null;
let safePlaceAPI = null;
let headerAPI = null;
let formHeaderAPI = null;
class OffersManager {
    constructor() {
        this.offers = [];
        this.coinTypes = [];
        const authManager = new AuthManager();
        coinExchangeAPI = authManager.API_CONFIG.baseURL + 'coin_exchange.php';
        coinOffersAPI = authManager.API_CONFIG.baseURL + 'user_offers.php';
        trCoinOfferAPI = authManager.API_CONFIG.baseURL + 'tr_coin_offer.php';
        safePlaceAPI = authManager.API_CONFIG.baseURL + 'safe_places.php';
        headerAPI = authManager.API_CONFIG.getHeaders();
        formHeaderAPI = authManager.API_CONFIG.getFormHeaders();
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
        this.loadOffers();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Create offer form
        document.getElementById('coinOfferForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.createOffer();
        });

        // Edit offer form
        document.getElementById('editOfferForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateOffer();
        });

        // Location picker: open map and let user click to select
        document.getElementById('getLocationBtn')?.addEventListener('click', () => {
            this.openLocationPicker('offer');
        });

        document.getElementById('getEditLocationBtn')?.addEventListener('click', () => {
            this.openLocationPicker('edit_offer');
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

    async loadOffers() {
        try {
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params.append(key, this.filters[key]);
                }
            });

            const response = await axios.get(`${coinOffersAPI}?action=getMyOffers&${params}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.offers = response.data.data;
                this.renderOffers();
                this.updateStats();
            }
            
        } catch (error) {
            console.error('Error loading offers:', error);
            CustomToast.show('error', 'Failed to load offers');
        }
    }

    renderOffers() {
        const container = document.getElementById('offersContainer');
        if (!container) return;

        if (this.offers.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-coins fa-3x mb-3"></i>
                    <p>No offers found</p>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createOfferModal">
                        <i class="fas fa-plus"></i> Create Your First Offer
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = this.offers.map(offer => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1 justify-content-center text-center">
                            <img src="../../../../${offer.coin_image_path || '/assets/images/default-coin.png'}" 
                                 alt="${offer.description}" class=""s style="width: 50px; height: 50px;">
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">${offer.description}</h6>
                            <small class="text-muted">Value: ₱${offer.denomination}</small>
                        </div>
                        <div class="col-md-2">
                            <strong>${offer.quantity}</strong> pieces
                        </div>
                        <div class="col-md-2">
                            <span class="badge bg-${this.getStatusColor(offer.status)}">${offer.status}</span>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">${new Date(offer.created_at).toLocaleDateString()}</small>
                        </div>
                        <div class="col-md-2">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="offersManager.editOffer(${offer.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${offer.status === 'active' ? `
                                    <button class="btn btn-outline-danger" onclick="offersManager.cancelOffer(${offer.id})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                ` : ''}
                                <button class="btn btn-outline-info" title="View Requests" onclick="offersManager.showTargetedRequests(${offer.id})">
                                    <i class="fas fa-users"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    ${offer.notes ? `
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">
                                    <strong>Notes:</strong> ${offer.notes}
                                </small>
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    getStatusColor(status) {
        const colors = {
            'active': 'success',
            'matched': 'warning',
            'completed': 'info',
            'cancelled': 'secondary'
        };
        return colors[status] || 'secondary';
    }

    updateStats() {
        const stats = {
            total_offers: this.offers.length,
            active_offers: this.offers.filter(o => o.status === 'active').length,
            matched_offers: this.offers.filter(o => o.status === 'matched').length,
            completed_offers: this.offers.filter(o => o.status === 'completed').length
        };

        Object.keys(stats).forEach(key => {
            const element = document.getElementById(key.replace('_offers', 'OffersCount'));
            if (element) {
                element.textContent = stats[key];
            }
        });
    }

    async createOffer() {
        const form = document.getElementById('coinOfferForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Convert to URL-encoded string for PHP $_POST compatibility
        const urlEncodedData = new URLSearchParams(data).toString();
        console.log('URL encoded create data:', urlEncodedData);

        try {
            const response = await axios.post(`${coinOffersAPI}?action=createOffer`, urlEncodedData, {
                headers: formHeaderAPI
            });

            const result = response.data;
            
            if (result.success) {
                CustomToast.show('success', 'Offer created successfully');
                form.reset();
                const modal = bootstrap.Modal.getInstance(document.getElementById('createOfferModal'));
                if (modal) {
                    modal.hide();
                }
                this.loadOffers();
            } else {
                CustomToast.show('error', result.message);
            }
        } catch (error) {
            console.error('Error creating offer:', error);
            CustomToast.show('error', 'Failed to create offer');
        }
    }

    async updateOffer() {
        const form = document.getElementById('editOfferForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const offerId = data.offer_id;
        console.log('Form data being sent:', data);
        
        // Convert to URL-encoded string for PHP $_POST compatibility
        const urlEncodedData = new URLSearchParams(data).toString();
        console.log('URL encoded data:', urlEncodedData);
        
        try {
            const response = await axios.post(`${coinOffersAPI}?action=updateOffer&offer_id=${offerId}`, urlEncodedData, {
                headers: formHeaderAPI
            });

            const result = response.data;
            
            if (result.success) {
                CustomToast.show('success', 'Offer updated successfully');
                const modal = bootstrap.Modal.getInstance(document.getElementById('editOfferModal'));
                if (modal) {
                    modal.hide();
                }
                this.loadOffers();
            } else {
                CustomToast.show('error', result.message);
            }
        } catch (error) {
            console.error('Error updating offer:', error);
            CustomToast.show('error', 'Failed to update offer');
        }
    }

    editOffer(offerId) {
        
        // Convert offerId to both number and string for comparison
        const numericId = parseInt(offerId);
        const stringId = String(offerId);
        
        // Try multiple comparison methods to handle type mismatches
        const offer = this.offers.find(o => {
            const oId = o.id;
            const oIdNum = parseInt(oId);
            const oIdStr = String(oId);
            
            const matches = oId === offerId || 
                   oId === numericId || 
                   oId === stringId ||
                   oIdNum === offerId ||
                   oIdNum === numericId ||
                   oIdStr === stringId;
                   
            if (matches) {
                console.log('MATCH FOUND!', o);
            }
            
            return matches;
        });
        
        if (!offer) {
        
            CustomToast.show('error', `Offer with ID ${offerId} not found`);
            return;
        }

        console.log('Found offer:', offer);

        // Populate edit form
        document.getElementById('edit_offer_id').value = offer.id;
        document.getElementById('edit_offer_coin_type').value = offer.coin_type_id;
        document.getElementById('edit_offer_quantity').value = offer.quantity;
        document.getElementById('edit_offer_location').value = offer.preferred_meeting_location || '';
        document.getElementById('edit_offer_meeting_longitude').value = offer.meeting_longitude || '';
        document.getElementById('edit_offer_meeting_latitude').value = offer.meeting_latitude || '';
        document.getElementById('edit_offer_notes').value = offer.notes || '';

        console.log('Form populated successfully');

        // Show modal with proper cleanup and error handling
        const modalElement = document.getElementById('editOfferModal');
        console.log('Modal element:', modalElement);
        
        if (modalElement) {
            // Clean up any existing modal instances
            const existingModal = bootstrap.Modal.getInstance(modalElement);
            if (existingModal) {
                console.log('Disposing existing modal instance');
                existingModal.dispose();
            }
            
            // Clean up any modal backdrops
            this.cleanupModalBackdrops();
            
            // Create and show new modal
            try {
                console.log('Creating new modal instance');
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

    async cancelOffer(offerId) {

        try {
            const response = await axios.post(`${coinOffersAPI}?action=cancelOffer&offer_id=${offerId}`, {}, {
                headers: formHeaderAPI
            });

            const result = response.data;
            
            if (result.success) {
                CustomToast.show('success', 'Offer cancelled successfully');
                this.loadOffers();
            } else {
                CustomToast.show('error', result.message);
            }
        } catch (error) {
            console.error('Error cancelling offer:', error);
            CustomToast.show('error', 'Failed to cancel offer');
        }
    }

    viewOffer(offerId) {
        const offer = this.offers.find(o => o.id === offerId);
        if (!offer) return;

        // Show offer details in a modal or redirect to details page
        alert(`Offer Details:\n\nCoin: ${offer.coin_description}\nValue: ₱${offer.denomination}\nQuantity: ${offer.quantity}\nStatus: ${offer.status}\nLocation: ${offer.preferred_meeting_location || 'Not specified'}\nNotes: ${offer.notes || 'None'}`);
    }

    async showTargetedRequests(offerId) {
        const tbody = document.getElementById('targetedRequestsTbody');
        const countEl = document.getElementById('targetedRequestsCount');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
        }
        try {
            const resp = await axios.get(`${trCoinOfferAPI}?action=listByPostOffer&post_offer_id=${offerId}`, { headers: headerAPI });
            const items = resp.data?.data || [];
            if (!tbody) return;
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No requests found</td></tr>';
            } else {
                tbody.innerHTML = items.map(r => `
                    <tr>
                        <td>${r.username || (r.first_name ? r.first_name + ' ' + (r.last_name||'') : 'User '+r.requestor_id)}</td>
                        <td>${r.offered_quantity}</td>
                        <td>${r.message || ''}</td>
                        <td><span class="badge bg-${r.status == 'pending' ? 'warning' : r.status == 'accepted' ? 'success' : 'danger'}">${r.status}</span></td>
                        <td>
                            ${r.status === 'pending' ? `
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-success" onclick="offersManager.acceptTargetedRequest(${r.id})">Accept</button>
                                <button class="btn btn-outline-danger" onclick="offersManager.rejectTargetedRequest(${r.id})">Reject</button>
                            </div>` : ''}
                        </td>
                    </tr>
                `).join('');
            }
            if (countEl) countEl.textContent = `${items.length} request(s)`;
            const modalEl = document.getElementById('targetedRequestsModal');
            if (modalEl) new bootstrap.Modal(modalEl).show();
        } catch (e) {
            console.error('Failed to load targeted requests', e);
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-danger text-center">Failed to load</td></tr>';
        }
    }

    acceptTargetedRequest(id) {
        // Set the request ID and show the meeting schedule modal
        document.getElementById('schedule_request_id').value = id;
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
        
        // Debug logging
        console.log('Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
        
        await this.acceptTargetedRequestWithSchedule(formData);
    }

    async acceptTargetedRequestWithSchedule(formData) {
        try {
            const resp = await axios.post(`${trCoinOfferAPI}?action=accept`, formData, { headers: formHeaderAPI });
            if (resp.data?.success) {
                CustomToast.show('success', 'Request accepted, transaction created and meeting scheduled');
                // Close both modals
                bootstrap.Modal.getInstance(document.getElementById('meetingScheduleModal'))?.hide();
                const modalEl = document.getElementById('targetedRequestsModal');
                if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
                this.loadOffers();
            } else {
                CustomToast.show('error', resp.data?.message || 'Failed to accept');
            }
        } catch (e) {
            console.error('Accept failed', e);
            CustomToast.show('error', 'Accept failed');
        }
    }

    async rejectTargetedRequest(id) {
        try {
            const form = new URLSearchParams({ id: String(id) }).toString();
            const resp = await axios.post(`${trCoinOfferAPI}?action=reject`, form, { headers: formHeaderAPI });
            if (resp.data?.success) {
                CustomToast.show('success', 'Request rejected');
                // refresh modal content by triggering the last opened offer if tracked
                // keep simple: close modal and reload offers
                const modalEl = document.getElementById('targetedRequestsModal');
                if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
                this.loadOffers();
            } else {
                CustomToast.show('error', resp.data?.message || 'Failed to reject');
            }
        } catch (e) {
            console.error('Reject failed', e);
            CustomToast.show('error', 'Reject failed');
        }
    }

    filterOffers() {
        this.filters.status = document.getElementById('statusFilter').value;
        this.filters.coin_type_id = document.getElementById('coinTypeFilter').value;
        this.loadOffers();
    }

    searchOffers() {
        this.filters.search = document.getElementById('searchOffers').value;
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.loadOffers();
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
        document.getElementById('searchOffers').value = '';
        
        this.loadOffers();
    }

    openLocationPicker(prefix) {
		const triggerBtn = document.getElementById(prefix === 'offer' ? 'getLocationBtn' : 'getEditLocationBtn');
		const modalEl = document.getElementById('locationPickerModal');
		const parentModalEl = document.getElementById(prefix === 'offer' ? 'createOfferModal' : 'editOfferModal');
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
                    // const ok = confirm(`Use this meeting place?\nLatitude: ${lngLat[1].toFixed(6)}\nLongitude: ${lngLat[0].toFixed(6)}`);
                    // if (ok) {
                    // }
                    const latInputId = prefix === 'offer' ? 'offer_meeting_latitude' : 'edit_offer_meeting_latitude';
                    const lngInputId = prefix === 'offer' ? 'offer_meeting_longitude' : 'edit_offer_meeting_longitude';
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

    // Test function to verify modal functionality
    testModal() {
        console.log('Testing modal functionality...');
        const modalElement = document.getElementById('editOfferModal');
        console.log('Modal element found:', !!modalElement);
        if (modalElement) {
            console.log('Modal element:', modalElement);
            console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
            if (typeof bootstrap !== 'undefined') {
                console.log('Bootstrap.Modal available:', typeof bootstrap.Modal !== 'undefined');
            }
        }
    }

    // Debug function to check offers data
    debugOffers() {
        console.log('=== OFFERS DEBUG INFO ===');
        console.log('Offers array:', this.offers);
        console.log('Offers count:', this.offers.length);
        console.log('Offer IDs and types:', this.offers.map(o => ({ id: o.id, type: typeof o.id })));
        console.log('OffersManager instance:', this);
        console.log('========================');
    }

    // Test function to manually test offer lookup
    testOfferLookup(id) {
        console.log('Testing offer lookup for ID:', id);
        console.log('Available offers:', this.offers.map(o => ({ id: o.id, type: typeof o.id })));
        
        const numericId = parseInt(id);
        const stringId = String(id);
        
        const offer = this.offers.find(o => {
            const oId = o.id;
            const oIdNum = parseInt(oId);
            const oIdStr = String(oId);
            
            return oId === id || 
                   oId === numericId || 
                   oId === stringId ||
                   oIdNum === id ||
                   oIdNum === numericId ||
                   oIdStr === stringId;
        });
        
        console.log('Found offer:', offer);
        return offer;
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
    window.offersManager = new OffersManager();
    
    // Make test function globally available for debugging
    window.testOffersModal = () => {
        if (window.offersManager) {
            window.offersManager.testModal();
        } else {
            console.error('OffersManager not initialized');
        }
    };

    // Make debug function globally available
    window.debugOffers = () => {
        if (window.offersManager) {
            window.offersManager.debugOffers();
        } else {
            console.error('OffersManager not initialized');
        }
    };

    // Make test lookup function globally available
    window.testOfferLookup = (id) => {
        if (window.offersManager) {
            return window.offersManager.testOfferLookup(id);
        } else {
            console.error('OffersManager not initialized');
        }
    };
});
