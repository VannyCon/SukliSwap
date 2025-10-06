/**
 * Offers Manager - Handles coin offer operations
 */
let coinExchangeAPI = null;
let coinOffersAPI = null;
let headerAPI = null;
let formHeaderAPI = null;
class OffersManager {
    constructor() {
        this.offers = [];
        this.coinTypes = [];
        const authManager = new AuthManager();
        coinExchangeAPI = authManager.API_CONFIG.baseURL + 'coin_exchange.php';
        coinOffersAPI = authManager.API_CONFIG.baseURL + 'user_offers.php';
        headerAPI = authManager.API_CONFIG.getHeaders();
        formHeaderAPI = authManager.API_CONFIG.getFormHeaders();
        this.filters = {
            status: '',
            coin_type_id: '',
            search: ''
        };
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

            const response = await axios.get(`${coinOffersAPI}?action=getActiveOffers&${params}`, {
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
                        <div class="col-md-1">
                            <img src="${offer.coin_image || '/assets/images/default-coin.png'}" 
                                 alt="${offer.coin_description}" class="img-thumbnail" style="width: 50px; height: 50px;">
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">${offer.coin_description}</h6>
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
                                <button class="btn btn-outline-secondary" onclick="offersManager.viewOffer(${offer.id})">
                                    <i class="fas fa-eye"></i>
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
                    }
                });

                window.__locationPickerMap = map;
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
