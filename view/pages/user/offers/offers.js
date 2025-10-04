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

        // Location buttons
        document.getElementById('getLocationBtn')?.addEventListener('click', () => {
            this.getCurrentLocation('offer');
        });

        document.getElementById('getEditLocationBtn')?.addEventListener('click', () => {
            this.getCurrentLocation('edit_offer');
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
                option.textContent = `${coinType.coin_name} (${coinType.coin_value})`;
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
                option.textContent = `${coinType.coin_name} (${coinType.coin_value})`;
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
            this.showError('Failed to load offers');
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
                                 alt="${offer.coin_name}" class="img-thumbnail" style="width: 50px; height: 50px;">
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">${offer.coin_name}</h6>
                            <small class="text-muted">Value: ${offer.coin_value}</small>
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

        try {
            const response = await axios.post(`${coinOffersAPI}`, data, {
                headers: formHeaderAPI
            });

            const result = response.data.data;
            
            if (result.success) {
                CustomToast.success('Offer created successfully');
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('createOfferModal')).hide();
                this.loadOffers();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error creating offer:', error);
            this.showError('Failed to create offer');
        }
    }

    async updateOffer() {
        const form = document.getElementById('editOfferForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const offerId = data.offer_id;

        try {
            const response = await axios.put(`${coinOffersAPI}?action=updateOffer&offer_id=${offerId}`, data, {
                headers: formHeaderAPI
            });

            const result = response.data.data;
            
            if (result.success) {
                CustomToast.success('Offer updated successfully');
                bootstrap.Modal.getInstance(document.getElementById('editOfferModal')).hide();
                this.loadOffers();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error updating offer:', error);
            this.showError('Failed to update offer');
        }
    }

    editOffer(offerId) {
        const offer = this.offers.find(o => o.id === offerId);
        if (!offer) return;

        // Populate edit form
        document.getElementById('edit_offer_id').value = offer.id;
        document.getElementById('edit_offer_coin_type').value = offer.coin_type_id;
        document.getElementById('edit_offer_quantity').value = offer.quantity;
        document.getElementById('edit_offer_location').value = offer.preferred_meeting_location || '';
        document.getElementById('edit_offer_meeting_longitude').value = offer.meeting_longitude || '';
        document.getElementById('edit_offer_meeting_latitude').value = offer.meeting_latitude || '';
        document.getElementById('edit_offer_notes').value = offer.notes || '';

        // Show modal
        new bootstrap.Modal(document.getElementById('editOfferModal')).show();
    }

    async cancelOffer(offerId) {
        if (!confirm('Are you sure you want to cancel this offer?')) return;

        try {
            const response = await axios.delete(`${coinOffersAPI}?action=cancelOffer&offer_id=${offerId}`, {
                headers: formHeaderAPI
            });

            const result = response.data.data;
            
            if (result.success) {
                CustomToast.success('Offer cancelled successfully');
                this.loadOffers();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error cancelling offer:', error);
            this.showError('Failed to cancel offer');
        }
    }

    viewOffer(offerId) {
        const offer = this.offers.find(o => o.id === offerId);
        if (!offer) return;

        // Show offer details in a modal or redirect to details page
        alert(`Offer Details:\n\nCoin: ${offer.coin_name}\nQuantity: ${offer.quantity}\nStatus: ${offer.status}\nLocation: ${offer.preferred_meeting_location || 'Not specified'}\nNotes: ${offer.notes || 'None'}`);
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

    getCurrentLocation(prefix) {
        if (!navigator.geolocation) {
            this.showError('Geolocation is not supported by this browser');
            return;
        }

        const locationBtn = document.getElementById(prefix === 'offer' ? 'getLocationBtn' : 'getEditLocationBtn');
        locationBtn.disabled = true;
        locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                document.getElementById(`${prefix}_meeting_latitude`).value = lat;
                document.getElementById(`${prefix}_meeting_longitude`).value = lng;
                
                // Get address from coordinates (you might want to use a geocoding service)
                document.getElementById(`${prefix}_location`).value = `${lat}, ${lng}`;
                
                locationBtn.disabled = false;
                locationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get Current Location';
                
                this.showSuccess('Location updated successfully');
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
                this.showError(message);
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
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.offersManager = new OffersManager();
});
