/**
 * SukliSwap Coin Exchange JavaScript
 * Handles coin exchange functionality including requests, offers, matches, and transactions
 */
let coinExchangeAPI = null;
let userProfileAPI = null;
let adminAPI = null;
let headerAPI = null;
let formHeaderAPI = null;
let user_id = null;
class CoinExchangeManager {
    constructor() {
        this.authManager = window.authManager;
        user_id = this.authManager.getUser().id;
        coinExchangeAPI = this.authManager.API_CONFIG.baseURL + 'coin_exchange.php';
        userProfileAPI = this.authManager.API_CONFIG.baseURL + 'user_profile.php';
        adminAPI = this.authManager.API_CONFIG.baseURL + 'admin.php';
        headerAPI = this.authManager.API_CONFIG.getHeaders();
        formHeaderAPI = this.authManager.API_CONFIG.getFormHeaders();
        this.currentUser = null;
        this.coinTypes = [];
        this.activeRequests = [];
        this.activeOffers = [];
        this.userMatches = [];
        this.userTransactions = [];
        this.init();
    }

    async init() {
        try {
           
            
            // Load initial data
            await this.loadCoinTypes();
            await this.loadUserData();
            
            // Initialize event listeners
            this.initEventListeners();
            
            // Start real-time updates
            this.startRealTimeUpdates();
            
        } catch (error) {
            console.error('Failed to initialize CoinExchangeManager:', error);
            CustomToast.show('error', 'Failed to initialize coin exchange system');
        }
    }

    initEventListeners() {
        // Request form
        const requestForm = document.getElementById('coinRequestForm');
        if (requestForm) {
            requestForm.addEventListener('submit', (e) => this.handleCreateRequest(e));
        }

        // Offer form
        const offerForm = document.getElementById('coinOfferForm');
        if (offerForm) {
            offerForm.addEventListener('submit', (e) => this.handleCreateOffer(e));
        }

        // Match actions
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('accept-match-btn')) {
                this.handleAcceptMatch(e.target.dataset.matchId);
            }
            if (e.target.classList.contains('complete-transaction-btn')) {
                this.handleCompleteTransaction(e.target.dataset.qrCode);
            }
        });

        // Filter and search
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        }

        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => this.handleStatusFilter(e.target.value));
        }

        // Location services
        if (navigator.geolocation) {
            const locationBtn = document.getElementById('getLocationBtn');
            if (locationBtn) {
                locationBtn.addEventListener('click', () => this.getCurrentLocation(1));
            }
        }
        if (navigator.geolocation) {
            const locationBtn = document.getElementById('getOfferLocationBtn');
            if (locationBtn) {
                locationBtn.addEventListener('click', () => this.getCurrentLocation(2));
            }
        }
    }

    // ============ DATA LOADING ============

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
            console.error('Failed to load coin types:', error);
        }
    }

    async loadUserData() {
        try {
            // Load user requests
            await this.loadUserRequests();
            
            // Load user offers
            await this.loadUserOffers();
            
            // Load user matches
            await this.loadUserMatches();
            
            // Load user transactions
            await this.loadUserTransactions();
            
        } catch (error) {
            console.error('Failed to load user data:', error);
        }
    }

    async loadUserRequests(status = '') {
        try {
            const response = await axios.get(`${coinExchangeAPI}?action=getMyRequests&status=${status}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.userRequests = response.data.data;
                this.renderUserRequests();
            }
        } catch (error) {
            console.error('Failed to load user requests:', error);
        }
    }

    async loadUserOffers(status = '') {
        try {
            const response = await axios.get(`${coinExchangeAPI}?action=getMyOffers&status=${status}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.userOffers = response.data.data;
                this.renderUserOffers();
            }
        } catch (error) {
            console.error('Failed to load user offers:', error);
        }
    }

    async loadUserMatches(status = '') {
        try {
            const response = await axios.get(`${coinExchangeAPI}?action=getMyMatches&status=${status}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.userMatches = response.data.data;
                this.renderUserMatches();
            }
        } catch (error) {
            console.error('Failed to load user matches:', error);
        }
    }

    async loadUserTransactions(status = '') {
        try {
            const response = await axios.get(`${coinExchangeAPI}?action=getMyTransactions&status=${status}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.userTransactions = response.data.data;
                this.renderUserTransactions();
            }
        } catch (error) {
            console.error('Failed to load user transactions:', error);
        }
    }

    async loadActiveRequests() {
        try {
            const response = await axios.get(`${coinExchangeAPI}?action=getActiveRequests`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.activeRequests = response.data.data;
                this.renderActiveRequests();
                this.showBrowseResults('Active Requests');
            }
        } catch (error) {
            console.error('Failed to load active requests:', error);
            CustomToast.show('error', 'Failed to load active requests');
        }
    }

    async loadActiveOffers() {
        try {
            const response = await axios.get(`${coinExchangeAPI}?action=getActiveOffers`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.activeOffers = response.data.data;
                this.renderActiveOffers();
                this.showBrowseResults('Active Offers');
            }
        } catch (error) {
            console.error('Failed to load active offers:', error);
            CustomToast.show('error', 'Failed to load active offers');
        }
    }

    // ============ FORM HANDLERS ============

    async handleCreateRequest(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        formData.append('action', 'createCoinRequest');
        
        try {
            const response = await axios.post(coinExchangeAPI, formData, {
                headers: formHeaderAPI
            });

            if (response.data.success) {
                CustomToast.show('success', 'Coin request created successfully!');
                e.target.reset();
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('createRequestModal'));
                if (modal) {
                    modal.hide();
                }
                await this.loadUserRequests();
                await this.loadActiveRequests();
            } else {
                CustomToast.show('error', response.data.message);
            }
        } catch (error) {
            console.error('Failed to create coin request:', error);
            CustomToast.show('error', 'Failed to create coin request');
        }
    }

    async handleCreateOffer(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        formData.append('action', 'createCoinOffer');
        
        try {
            const response = await axios.post(coinExchangeAPI, formData, {
                headers: formHeaderAPI
            });

            if (response.data.success) {
                CustomToast.show('success', 'Coin offer created successfully!');
                e.target.reset();
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('createOfferModal'));
                if (modal) {
                    modal.hide();
                }
                await this.loadUserOffers();
                await this.loadActiveOffers();
            } else {
                CustomToast.show('error', response.data.message);
            }
        } catch (error) {
            console.error('Failed to create coin offer:', error);
            CustomToast.show('error', 'Failed to create coin offer');
        }
    }

    async handleAcceptMatch(matchId) {
        if (!confirm('Are you sure you want to accept this match?')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'acceptMatch');
            formData.append('match_id', matchId);

            const response = await axios.post(coinExchangeAPI, formData, {
                headers: formHeaderAPI
            });

            if (response.data.success) {
                CustomToast.show('success', 'Match accepted successfully!');
                await this.loadUserMatches();
                await this.loadUserTransactions();
                
                // Show QR code if available
                if (response.data.qr_code) {
                    this.showQRCode(response.data.qr_code);
                }
            } else {
                CustomToast.show('error', response.data.message);
            }
        } catch (error) {
            console.error('Failed to accept match:', error);
            CustomToast.show('error', 'Failed to accept match');
        }
    }

    async handleCompleteTransaction(qrCode) {
        if (!confirm('Are you sure you want to complete this transaction?')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'completeTransaction');
            formData.append('qr_code', qrCode);

            const response = await axios.post(coinExchangeAPI, formData, {
                headers: formHeaderAPI
            });

            if (response.data.success) {
                CustomToast.show('success', 'Transaction completed successfully!');
                await this.loadUserTransactions();
            } else {
                CustomToast.show('error', response.data.message);
            }
        } catch (error) {
            console.error('Failed to complete transaction:', error);
            CustomToast.show('error', 'Failed to complete transaction');
        }
    }

    // ============ FILTER AND SEARCH ============

    handleSearch(searchTerm) {
        // Implement search functionality
        this.filterData('search', searchTerm);
    }

    handleStatusFilter(status) {
        // Implement status filtering
        this.filterData('status', status);
    }

    filterData(type, value) {
        // Filter data based on type and value
        // Implementation depends on current view
        console.log(`Filtering by ${type}: ${value}`);
    }

    // ============ LOCATION SERVICES ============

    getCurrentLocation(type) {
        if (!navigator.geolocation) {
            CustomToast.show('error', 'Geolocation is not supported by this browser', 'error');
            return;
        }

        if (type == 1) {
        const locationBtn = document.getElementById('getLocationBtn');
            if (locationBtn) {
                locationBtn.disabled = true;
                locationBtn.className = 'btn btn-outline-warning';
                locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';
            }
        }else if (type == 2) {
            const locationBtn = document.getElementById('getOfferLocationBtn');
            if (locationBtn) {
                locationBtn.disabled = true;
                locationBtn.className = 'btn btn-outline-warning';
                locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';
            }
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                if (type == 1) {
                // Update form fields
                const latInput = document.getElementById('request_meeting_latitude');
                const lngInput = document.getElementById('request_meeting_longitude');
                
                    if (latInput) latInput.value = lat;
                    if (lngInput) lngInput.value = lng;
                }else if (type == 2) {
                    const latInput = document.getElementById('offer_meeting_latitude');
                    const lngInput = document.getElementById('offer_meeting_longitude');
                    if (latInput) latInput.value = lat;
                    if (lngInput) lngInput.value = lng;
                }
                
                CustomToast.show('success', 'Location updated successfully!');
                
                // Update button styling to indicate success
                if (type == 1) {
                    const locationBtn = document.getElementById('getLocationBtn');
                    if (locationBtn) {
                        locationBtn.disabled = false;
                        locationBtn.textContent = 'Get Current Location';
                        locationBtn.innerHTML = '<i class="fas fa-check-circle"></i> Location Retrieved';
                        locationBtn.className = 'btn btn-outline-success';
                        locationBtn.innerHTML = '<i class="fas fa-check-circle"></i> Location Retrieved';
                    }
                } else if (type == 2) {
                    const locationBtn = document.getElementById('getOfferLocationBtn');
                    if (locationBtn) {
                        locationBtn.disabled = false;
                        locationBtn.textContent = 'Get Current Location';
                        locationBtn.className = 'btn btn-outline-success';
                        locationBtn.innerHTML = '<i class="fas fa-check-circle"></i> Location Retrieved';
                    }
                }
            },
            (error) => {
                console.error('Geolocation error:', error);
                CustomToast.show('error', 'Failed to get location', 'error');
                
                // Reset button styling on error
                if (type == 1) {
                    const locationBtn = document.getElementById('getLocationBtn');
                    if (locationBtn) {
                        locationBtn.disabled = false;
                        locationBtn.textContent = 'Get Current Location';
                        locationBtn.className = 'btn btn-outline-secondary';
                        locationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get Current Location';
                    }
                } else if (type == 2) {
                    const locationBtn = document.getElementById('getOfferLocationBtn');
                    if (locationBtn) {
                        locationBtn.disabled = false;
                        locationBtn.textContent = 'Get Current Location';
                        locationBtn.className = 'btn btn-outline-secondary';
                        locationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get Current Location';
                    }
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    }

    // ============ RENDERING METHODS ============

    populateCoinTypeSelects() {
        const selects = document.querySelectorAll('.coin-type-select');
        selects.forEach(select => {
            select.innerHTML = '<option value="">Select coin type</option>';
            this.coinTypes.forEach(coinType => {
                const option = document.createElement('option');
                option.value = coinType.id;
                option.textContent = `₱${coinType.denomination} - ${coinType.description}`;
                select.appendChild(option);
            });
        });
    }

    renderUserRequests() {
        const container = document.getElementById('userRequestsContainer');
        if (!container) return;

        if (this.userRequests.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">No requests found</div>';
            return;
        }

        const html = this.userRequests.map(request => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="card-title">₱${request.denomination} Coin Request</h6>
                            <p class="card-text">
                                <strong>Quantity:</strong> ${request.quantity}<br>
                                <strong>Location:</strong> ${request.preferred_meeting_location || 'Not specified'}<br>
                                <strong>Status:</strong> <span class="badge badge-${this.getStatusBadgeClass(request.status)}">${request.status}</span>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <small class="text-muted">${this.formatDate(request.created_at)}</small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="editRequest(${request.id})">Edit</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteRequest(${request.id})">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    renderUserOffers() {
        const container = document.getElementById('userOffersContainer');
        if (!container) return;

        if (this.userOffers.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">No offers found</div>';
            return;
        }

        const html = this.userOffers.map(offer => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="card-title">₱${offer.denomination} Coin Offer</h6>
                            <p class="card-text">
                                <strong>Quantity:</strong> ${offer.quantity}<br>
                                <strong>Location:</strong> ${offer.preferred_meeting_location || 'Not specified'}<br>
                                <strong>Status:</strong> <span class="badge badge-${this.getStatusBadgeClass(offer.status)}">${offer.status}</span>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <small class="text-muted">${this.formatDate(offer.created_at)}</small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="editOffer(${offer.id})">Edit</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteOffer(${offer.id})">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    renderUserMatches() {
        const container = document.getElementById('userMatchesContainer');
        if (!container) return;

        if (this.userMatches.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">No matches found</div>';
            return;
        }

        const html = this.userMatches.map(match => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="card-title">₱${match.denomination} Coin Match</h6>
                            <p class="card-text">
                                <strong>Requestor:</strong> ${match.requestor_username}<br>
                                <strong>Offeror:</strong> ${match.offeror_username}<br>
                                <strong>Match Score:</strong> ${match.match_score}%<br>
                                <strong>Distance:</strong> ${match.distance ? (match.distance / 1000).toFixed(1) + ' km' : 'N/A'}<br>
                                <strong>Status:</strong> <span class="badge badge-${this.getStatusBadgeClass(match.status)}">${match.status}</span>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <small class="text-muted">${this.formatDate(match.created_at)}</small>
                            <div class="mt-2">
                                ${match.status === 'pending' ? 
                                    `<button class="btn btn-sm btn-success accept-match-btn" data-match-id="${match.id}">Accept Match</button>` : 
                                    ''
                                }
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    renderUserTransactions() {
        const container = document.getElementById('userTransactionsContainer');
        if (!container) return;

        if (this.userTransactions.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">No transactions found</div>';
            return;
        }

        const html = this.userTransactions.map(transaction => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="card-title">₱${transaction.denomination} Transaction</h6>
                            <p class="card-text">
                                <strong>With:</strong> ${transaction.requestor_id == this.currentUser.id ? transaction.offeror_username : transaction.requestor_username}<br>
                                <strong>Quantity:</strong> ${transaction.quantity}<br>
                                <strong>QR Code:</strong> ${transaction.qr_code}<br>
                                <strong>Status:</strong> <span class="badge badge-${this.getStatusBadgeClass(transaction.status)}">${transaction.status}</span>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <small class="text-muted">${this.formatDate(transaction.created_at)}</small>
                            <div class="mt-2">
                                ${transaction.status === 'scheduled' ? 
                                    `<button class="btn btn-sm btn-primary complete-transaction-btn" data-qr-code="${transaction.qr_code}">Complete</button>` : 
                                    ''
                                }
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    renderActiveRequests() {
        const container = document.getElementById('activeRequestsContainer');
        const offersContainer = document.getElementById('activeOffersContainer');
        if (!container) return;

        // Clear offers container when showing requests
        if (offersContainer) {
            offersContainer.innerHTML = '';
        }

        if (this.activeRequests.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">No active requests found</div>';
            return;
        }

        const html = this.activeRequests.map(request => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="card-title">₱${request.denomination} Coin Request</h6>
                            <p class="card-text">
                                <strong>From:</strong> ${request.username}<br>
                                <strong>Quantity:</strong> ${request.quantity}<br>
                                <strong>Location:</strong> ${request.preferred_meeting_location || 'Not specified'}<br>
                                <strong>Business:</strong> ${request.business_name || 'N/A'}
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <small class="text-muted">${this.formatDate(request.created_at)}</small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewRequestDetails(${request.id})">View Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    renderActiveOffers() {
        const container = document.getElementById('activeOffersContainer');
        const requestsContainer = document.getElementById('activeRequestsContainer');
        if (!container) return;

        // Clear requests container when showing offers
        if (requestsContainer) {
            requestsContainer.innerHTML = '';
        }

        if (this.activeOffers.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">No active offers found</div>';
            return;
        }

        const html = this.activeOffers.map(offer => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="card-title">₱${offer.denomination} Coin Offer</h6>
                            <p class="card-text">
                                <strong>From:</strong> ${offer.username}<br>
                                <strong>Quantity:</strong> ${offer.quantity}<br>
                                <strong>Location:</strong> ${offer.preferred_meeting_location || 'Not specified'}<br>
                                <strong>Business:</strong> ${offer.business_name || 'N/A'}
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <small class="text-muted">${this.formatDate(offer.created_at)}</small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewOfferDetails(${offer.id})">View Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    // ============ UTILITY METHODS ============

    getStatusBadgeClass(status) {
        const statusClasses = {
            'active': 'success',
            'pending': 'warning',
            'matched': 'info',
            'completed': 'success',
            'cancelled': 'danger',
            'expired': 'secondary',
            'scheduled': 'primary',
            'in_progress': 'warning',
            'disputed': 'danger'
        };
        return statusClasses[status] || 'secondary';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    showToast(message, type = 'info') {
        // Implement toast notification
        console.log(`Toast [${type}]: ${message}`);
        
        // You can integrate with your existing toast system
        if (typeof showToast === 'function') {
            showToast(message, type);
        }
    }

    showBrowseResults(title) {
        const browseResultsSection = document.getElementById('browseResultsSection');
        const browseResultsTitle = document.getElementById('browseResultsTitle');
        
        if (browseResultsSection && browseResultsTitle) {
            browseResultsTitle.textContent = title;
            browseResultsSection.style.display = 'block';
            
            // Scroll to the results section
            browseResultsSection.scrollIntoView({ behavior: 'smooth' });
        }
    }

    showQRCode(qrCode) {
        // Show QR code in modal or popup
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Transaction QR Code</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="qr-code-container">
                            <div class="qr-code-placeholder" style="width: 200px; height: 200px; border: 2px dashed #ccc; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                                <span>QR Code: ${qrCode}</span>
                            </div>
                        </div>
                        <p class="mt-3">Show this QR code to complete the transaction</p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        $(modal).modal('show');
        
        // Remove modal after hiding
        $(modal).on('hidden.bs.modal', function() {
            modal.remove();
        });
    }

    // ============ REAL-TIME UPDATES ============

    startRealTimeUpdates() {
        // Update data every 30 seconds
        setInterval(async () => {
            try {
                await this.loadUserMatches();
                await this.loadUserTransactions();
            } catch (error) {
                console.error('Failed to update data:', error);
            }
        }, 30000);
    }

    // ============ EDIT AND DELETE METHODS ============

    async editRequest(requestId) {
        // Implement edit request functionality
        console.log('Edit request:', requestId);
    }

    async deleteRequest(requestId) {
        if (!confirm('Are you sure you want to delete this request?')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'deleteCoinRequest');
            formData.append('id', requestId);

            const response = await axios.post(this.apiConfig.coinExchangeAPI, formData, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.showToast('Request deleted successfully!', 'success');
                await this.loadUserRequests();
            } else {
                this.showToast(response.data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to delete request:', error);
            this.showToast('Failed to delete request', 'error');
        }
    }

    async editOffer(offerId) {
        // Implement edit offer functionality
        console.log('Edit offer:', offerId);
    }

    async deleteOffer(offerId) {
        if (!confirm('Are you sure you want to delete this offer?')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'deleteCoinOffer');
            formData.append('id', offerId);

            const response = await axios.post(this.apiConfig.coinExchangeAPI, formData, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.showToast('Offer deleted successfully!', 'success');
                await this.loadUserOffers();
            } else {
                this.showToast(response.data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to delete offer:', error);
            this.showToast('Failed to delete offer', 'error');
        }
    }

    viewRequestDetails(requestId) {
        // Implement view request details
        console.log('View request details:', requestId);
    }

    viewOfferDetails(offerId) {
        // Implement view offer details
        console.log('View offer details:', offerId);
    }
}
// Global wrapper functions for HTML onclick handlers
window.loadUserRequests = function(status = '') {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.loadUserRequests(status);
    }
};

window.loadUserOffers = function(status = '') {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.loadUserOffers(status);
    }
};

window.loadUserMatches = function(status = '') {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.loadUserMatches(status);
    }
};

window.loadUserTransactions = function(status = '') {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.loadUserTransactions(status);
    }
};

window.updateMapData = function() {
    if (window.mapIntegrationManager) {
        return window.mapIntegrationManager.updateMapData();
    }
};

window.editRequest = function(requestId) {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.editRequest(requestId);
    }
};

window.deleteRequest = function(requestId) {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.deleteRequest(requestId);
    }
};

window.editOffer = function(offerId) {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.editOffer(offerId);
    }
};

window.deleteOffer = function(offerId) {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.deleteOffer(offerId);
    }
};

window.viewRequestDetails = function(requestId) {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.viewRequestDetails(requestId);
    }
};

window.viewOfferDetails = function(offerId) {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.viewOfferDetails(offerId);
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Wait for authManager to be available
    if (window.authManager) {
        window.coinExchangeManager = new CoinExchangeManager();
    } else {
        // Retry after a short delay if authManager is not ready
        setTimeout(() => {
            if (window.authManager) {
                window.coinExchangeManager = new CoinExchangeManager();
            } else {
                console.error('AuthManager not available for CoinExchangeManager initialization');
            }
        }, 100);
    }
});
