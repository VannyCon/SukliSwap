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
        // Admin view is read-only, no event listeners needed for forms
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

            // Admin view: load all offers from all users
            const response = await axios.get(`${coinOffersAPI}?action=getAllOffers&${params}`, {
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
                        <div class="col-md-2">
                            <h6 class="mb-1">${offer.description}</h6>
                            <small class="text-muted">Value: ₱${offer.denomination}</small>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted"><strong>User:</strong> ${offer.username || offer.first_name || 'N/A'}</small><br>
                            <small class="text-muted">${offer.business_name || ''}</small>
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
                        <div class="col-md-1">
                            <button class="btn btn-outline-info btn-sm" title="View Details" onclick="offersManager.viewOffer(${offer.id})">
                                <i class="fas fa-eye"></i>
                                </button>
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
                    ${offer.preferred_meeting_location ? `
                        <div class="row mt-1">
                            <div class="col-12">
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> ${offer.preferred_meeting_location}
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

    viewOffer(offerId) {
        const offer = this.offers.find(o => o.id == offerId);
        if (!offer) {
            CustomToast.show('error', 'Offer not found');
            return;
        }

        // Build detailed view
        const details = `
            <div class="offer-details">
                <div class="row mb-3">
                    <div class="col-6"><strong>User:</strong></div>
                    <div class="col-6">${offer.username || offer.first_name || 'N/A'}</div>
                </div>
                ${offer.business_name ? `
                    <div class="row mb-3">
                        <div class="col-6"><strong>Business:</strong></div>
                        <div class="col-6">${offer.business_name}</div>
                    </div>
                ` : ''}
                <div class="row mb-3">
                    <div class="col-6"><strong>Coin Type:</strong></div>
                    <div class="col-6">${offer.description}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Denomination:</strong></div>
                    <div class="col-6">₱${offer.denomination}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Quantity:</strong></div>
                    <div class="col-6">${offer.quantity} pieces</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Status:</strong></div>
                    <div class="col-6"><span class="badge bg-${this.getStatusColor(offer.status)}">${offer.status}</span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Meeting Location:</strong></div>
                    <div class="col-6">${offer.preferred_meeting_location || 'Not specified'}</div>
                </div>
                ${offer.notes ? `
                    <div class="row mb-3">
                        <div class="col-6"><strong>Notes:</strong></div>
                        <div class="col-6">${offer.notes}</div>
                    </div>
                ` : ''}
                <div class="row mb-3">
                    <div class="col-6"><strong>Created:</strong></div>
                    <div class="col-6">${new Date(offer.created_at).toLocaleString()}</div>
                </div>
            </div>
        `;

        // Show in a custom modal
        this.showDetailsModal('Offer Details', details);
    }

    showDetailsModal(title, content) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${content}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
        bsModal.show();
    }

    // Admin view is read-only - no create, update, delete, or accept/reject functionality

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

    // Removed location picker and other edit functionality - admin view is read-only
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.offersManager = new OffersManager();
});
