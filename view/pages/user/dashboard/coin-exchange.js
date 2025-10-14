/**
 * SukliSwap Coin Exchange JavaScript
 * Handles coin exchange functionality including requests, offers, matches, and transactions
 */
let coinExchangeAPI = null;
let userProfileAPI = null;
let adminAPI = null;
let trCoinOfferAPI = null;
let trCoinRequestAPI = null;
let headerAPI = null;
let safePlaceAPI = null;
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
        trCoinOfferAPI = this.authManager.API_CONFIG.baseURL + 'tr_coin_offer.php';
        trCoinRequestAPI = this.authManager.API_CONFIG.baseURL + 'tr_coin_request.php';
        safePlaceAPI = this.authManager.API_CONFIG.baseURL + 'safe_places.php';
        this.currentUser = null;
        this.coinTypes = [];
        this.activeRequests = [];
        this.activeOffers = [];
        this.userMatches = [];
        this.userTransactions = [];
        this.safePlaces = [];
        this.map = null;
        this.markers = [];
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

        // Targeted modals submit
        document.getElementById('sendTargetedOfferForm')?.addEventListener('submit', (e) => this.submitTargetedOffer(e));
        document.getElementById('sendTargetedRequestForm')?.addEventListener('submit', (e) => this.submitTargetedRequest(e));

        // Match actions
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('accept-match-btn')) {
                this.handleAcceptMatch(e.target.dataset.matchId);
            }
            if (e.target.classList.contains('complete-transaction-btn')) {
                this.handleCompleteTransaction(e.target.dataset.qrCode);
            }
            if (e.target.classList.contains('send-offer-btn')) {
                e.preventDefault();
                const currentModal = e.target.closest('.modal');
                if (currentModal) {
                    try { bootstrap.Modal.getInstance(currentModal)?.hide(); } catch (_) {}
                }
                // Wait a tick for backdrop removal then open target modal
                setTimeout(() => {
                    // Clean any stale backdrops
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    this.openSendOfferModal(e.target.dataset.postOfferId, e.target.dataset.coinTypeId);
                }, 150);
            }
            if (e.target.classList.contains('send-request-btn')) {
                e.preventDefault();
                const currentModal = e.target.closest('.modal');
                if (currentModal) {
                    try { bootstrap.Modal.getInstance(currentModal)?.hide(); } catch (_) {}
                }
                setTimeout(() => {
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    this.openSendRequestModal(e.target.dataset.postRequestId, e.target.dataset.coinTypeId);
                }, 150);
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
            const response = await axios.get(`${coinExchangeAPI}?action=getAvailableRequests`, {
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
            const response = await axios.get(`${coinExchangeAPI}?action=getAvailableOffers`, {
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

    // ===== Targeted interactions (send offer/request) =====
    openSendOfferModal(postOfferId, coinTypeId) {
        const coinTypeEl = document.getElementById('tro_coin_type_id');
        const latEl = document.getElementById('tro_my_latitude');
        const lngEl = document.getElementById('tro_my_longitude');
        const postOfferIdEl = document.getElementById('tro_post_request_id');
        if (coinTypeEl) coinTypeEl.value = coinTypeId || '';
        if (postOfferIdEl) postOfferIdEl.value = postOfferId;
        if (navigator.geolocation && latEl && lngEl) {
            navigator.geolocation.getCurrentPosition((pos) => {
                latEl.value = pos.coords.latitude;
                lngEl.value = pos.coords.longitude;
            }, () => {
                latEl.value = '';
                lngEl.value = '';
            }, { enableHighAccuracy: true, timeout: 8000 });
        }
        const modalEl = document.getElementById('sendTargetedOfferModal');
        if (modalEl) new bootstrap.Modal(modalEl).show();
    }

    openSendRequestModal(postRequestId, coinTypeId) {
        const coinTypeEl = document.getElementById('trr_coin_type_id');
        const latEl = document.getElementById('trr_my_latitude');
        const lngEl = document.getElementById('trr_my_longitude');
        const postRequestIdEl = document.getElementById('trr_post_offer_id');
        if (coinTypeEl) coinTypeEl.value = coinTypeId || '';
        if (postRequestIdEl) postRequestIdEl.value = postRequestId;
        if (navigator.geolocation && latEl && lngEl) {
            navigator.geolocation.getCurrentPosition((pos) => {
                latEl.value = pos.coords.latitude;
                lngEl.value = pos.coords.longitude;
            }, () => {
                latEl.value = '';
                lngEl.value = '';
            }, { enableHighAccuracy: true, timeout: 8000 });
        }
        const modalEl = document.getElementById('sendTargetedRequestModal');
        if (modalEl) new bootstrap.Modal(modalEl).show();
    }

    async submitTargetedOffer(e) {
        e.preventDefault();
        const form = document.getElementById('sendTargetedOfferForm');
        const data = new URLSearchParams(new FormData(form)).toString();
        try {
            const resp = await axios.post(`${trCoinRequestAPI}?action=send`, data, { headers: formHeaderAPI });
            if (resp.data?.success) {
                CustomToast?.show?.('success', 'Offer sent');
                bootstrap.Modal.getInstance(document.getElementById('sendTargetedOfferModal'))?.hide();
                form.reset();
            } else {
                CustomToast?.show?.('error', resp.data?.message || 'Failed to send');
            }
        } catch (err) {
            console.error('submitTargetedOffer failed', err);
            CustomToast?.show?.('error', 'Failed to send');
        }
    }

    async submitTargetedRequest(e) {
        e.preventDefault();
        const form = document.getElementById('sendTargetedRequestForm');
        const data = new URLSearchParams(new FormData(form)).toString();
        try {
            const resp = await axios.post(`${trCoinOfferAPI}?action=send`, data, { headers: formHeaderAPI });
            if (resp.data?.success) {
                CustomToast?.show?.('success', 'Request sent');
                bootstrap.Modal.getInstance(document.getElementById('sendTargetedRequestModal'))?.hide();
                form.reset();
            } else {
                CustomToast?.show?.('error', resp.data?.message || 'Failed to send');
            }
        } catch (err) {
            console.error('submitTargetedRequest failed', err);
            CustomToast?.show?.('error', 'Failed to send');
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

    // ============ DETAILS MODALS ============

    async viewRequestDetails(requestId) {
        try {
            let request = null;
            // Try caches first
            request = (this.activeRequests || []).find(r => Number(r.id) === Number(requestId))
                || (this.userRequests || []).find(r => Number(r.id) === Number(requestId));

            // Fallback: fetch latest active requests and search
            if (!request) {
                const response = await axios.get(`${coinExchangeAPI}?action=getActiveRequests&size=100`, {
                    headers: headerAPI
                });
                if (response.data && response.data.success) {
                    const list = response.data.data || [];
                    request = list.find(r => Number(r.id) === Number(requestId));
                }
            }

            if (!request) {
                CustomToast?.show?.('error', 'Request not found');
                return;
            }

            const bodyHtml = `
                <div class="mb-2">
                    <div><strong>Denomination:</strong> ₱${request.denomination ?? ''}</div>
                    <div><strong>Quantity:</strong> ${request.quantity ?? ''}</div>
                    <div><strong>From:</strong> ${request.username ?? request.requestor_username ?? 'N/A'}</div>
                    <div><strong>Business:</strong> ${request.business_name ?? 'N/A'}</div>
                    <div><strong>Status:</strong> ${request.status ?? 'active'}</div>
                    <div><strong>Location:</strong> ${request.preferred_meeting_location || 'Not specified'}</div>
                    ${request.notes ? `<div class="mt-2"><strong>Notes:</strong><br>${request.notes}</div>` : ''}
                    ${request.meeting_latitude && request.meeting_longitude ?
                        `<div class=\"mt-2\"><strong>Coordinates:</strong> ${request.meeting_latitude}, ${request.meeting_longitude}</div>
                         <div id=\"request-map-${request.id}\" style=\"height: 240px; border-radius: 8px; overflow: hidden; margin-top: 8px;\"></div>` : ''}
                    <div class="text-muted mt-2">${this.formatDate(request.created_at)}</div>
                </div>
            `;

            const footerHtml = `
                <button type="button" class="btn btn-success send-offer-btn" data-post-offer-id="${request.id}" data-coin-type-id="${request.coin_type_id}">Send Offer</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            `;

            this.showDetailsModal('Request Details', bodyHtml, footerHtml);

            // Initialize a small map if coordinates are available
            if (request.meeting_latitude && request.meeting_longitude && typeof maplibregl !== 'undefined') {
                const containerId = `request-map-${request.id}`;
                const lat = Number(request.meeting_latitude);
                const lng = Number(request.meeting_longitude);
                // Delay until modal has rendered
                setTimeout(() => { this.initMiniMap(containerId, lat, lng, 19); }, 100);
            }
        } catch (err) {
            console.error('Failed to show request details:', err);
            CustomToast?.show?.('error', 'Failed to load request details');
        }
    }

    async viewOfferDetails(offerId) {
        try {
            let offer = null;
            // Try caches first
            offer = (this.activeOffers || []).find(o => Number(o.id) === Number(offerId))
                || (this.userOffers || []).find(o => Number(o.id) === Number(offerId));

            // Fallback: fetch latest active offers and search
            if (!offer) {
                const response = await axios.get(`${coinExchangeAPI}?action=getActiveOffers&size=100`, {
                    headers: headerAPI
                });
                if (response.data && response.data.success) {
                    const list = response.data.data || [];
                    offer = list.find(o => Number(o.id) === Number(offerId));
                }
            }

            if (!offer) {
                CustomToast?.show?.('error', 'Offer not found');
                return;
            }

            const bodyHtml = `
                <div class="mb-2">
                    <div><strong>Denomination:</strong> ₱${offer.denomination ?? ''}</div>
                    <div><strong>Quantity:</strong> ${offer.quantity ?? ''}</div>
                    <div><strong>From:</strong> ${offer.username ?? offer.offeror_username ?? 'N/A'}</div>
                    <div><strong>Business:</strong> ${offer.business_name ?? 'N/A'}</div>
                    <div><strong>Status:</strong> ${offer.status ?? 'active'}</div>
                    <div><strong>Location:</strong> ${offer.preferred_meeting_location || 'Not specified'}</div>
                    ${offer.notes ? `<div class="mt-2"><strong>Notes:</strong><br>${offer.notes}</div>` : ''}
                    ${offer.meeting_latitude && offer.meeting_longitude ?
                        `<div class=\"mt-2\"><strong>Coordinates:</strong> ${offer.meeting_latitude}, ${offer.meeting_longitude}</div>
                         <div id=\"offer-map-${offer.id}\" style=\"height: 240px; border-radius: 8px; overflow: hidden; margin-top: 8px;\"></div>` : ''}
                    <div class="text-muted mt-2">${this.formatDate(offer.created_at)}</div>
                </div>
            `;

            const footerHtml = `
                <button type="button" class="btn btn-success send-request-btn" data-post-request-id="${offer.id}" data-coin-type-id="${offer.coin_type_id}">Send Request</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            `;

            this.showDetailsModal('Offer Details', bodyHtml, footerHtml);

            // Initialize a small map if coordinates are available
            if (offer.meeting_latitude && offer.meeting_longitude && typeof maplibregl !== 'undefined') {
                const containerId = `offer-map-${offer.id}`;
                const lat = Number(offer.meeting_latitude);
                const lng = Number(offer.meeting_longitude);
                // Delay until modal has rendered
                setTimeout(() => { this.initMiniMap(containerId, lat, lng, 19); }, 100);
            }
        } catch (err) {
            console.error('Failed to show offer details:', err);
            CustomToast?.show?.('error', 'Failed to load offer details');
        }
    }

    async findMatchesForRequest(requestId) {
        try {
            const response = await axios.get(`${coinExchangeAPI}?action=findMatches&request_id=${requestId}`, {
                headers: headerAPI
            });
            if (response.data && response.data.success) {
                const count = response.data.matches_count ?? 0;
                CustomToast?.show?.('success', `Found ${count} matches`);
                await this.loadUserMatches();
            } else {
                CustomToast?.show?.('error', response.data?.message || 'Failed to find matches');
            }
        } catch (err) {
            console.error('findMatchesForRequest failed:', err);
            CustomToast?.show?.('error', 'Failed to find matches');
        }
    }

    showDetailsModal(title, bodyHtml, footerHtml = '') {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">${bodyHtml}</div>
                    <div class="modal-footer">${footerHtml}</div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        // Ensure no hidden backdrop keeps next modal behind
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        const bsModal = new bootstrap.Modal(modal, { backdrop: true, focus: true });
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
        bsModal.show();
    }

    initMiniMap(containerId, lat, lng, zoom = 19) {
        try {
            const container = document.getElementById(containerId);
            if (!container || typeof maplibregl === 'undefined') return;
            const map = new maplibregl.Map({
                container: containerId,
                style: 'https://tiles.openfreemap.org/styles/bright',
                center: [lng, lat],
                zoom: zoom
            });
            map.addControl(new maplibregl.NavigationControl(), 'top-right');
            map.on('load', () => {
                new maplibregl.Marker({ color: '#e11d48' }).setLngLat([lng, lat]).addTo(map);
                // Load safe places after map is ready
                this.loadSafePlaces(map);
            });
            // Resize map when modal layout settles
            setTimeout(() => map.resize(), 300);
        } catch (e) {
            console.warn('Mini map init failed', e);
        }
    }
    async loadSafePlaces(mapInstance = null) {
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
                this.updateMapWithSafePlaces(mapInstance);
            }
            
        } catch (error) {
            console.error('Failed to load safe places:', error);
        }
    }

    updateMapWithSafePlaces(mapInstance = null) {
        // Use provided map instance or fall back to this.map
        const targetMap = mapInstance || this.map;
        
        if (!targetMap) {
            console.warn('Map not initialized yet, cannot add safe place markers');
            return;
        }

        // Don't clear all markers - only add safe place markers to preserve user/offer/request markers
        
        // Add markers for each safe place
        this.safePlaces.forEach(safePlace => {
            if (safePlace.lat && safePlace.long) {
                // Create popup content
                const popupContent = `
                    <div class="map-popup">
                        <h6><strong><i class="fas fa-map-marker-alt"></i> ${safePlace.location_name}</strong></h6>
                        ${safePlace.description ? `<p class="mb-2">${safePlace.description}</p>` : ''}
                    </div>
                `;

                // Create marker
                const marker = new maplibregl.Marker({
                    color: safePlace.is_active == 1 ? '#007cba' : '#6c757d'
                })
                .setLngLat([parseFloat(safePlace.long), parseFloat(safePlace.lat)])
                .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(popupContent))
                .addTo(targetMap);

                // Add click event to marker to prevent map click event
                marker.getElement().addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Toggle the popup when marker is clicked
                    if (marker.getPopup().isOpen()) {
                        marker.getPopup().remove();
                    } else {
                        marker.getPopup().addTo(targetMap);
                    }
                });

                // Only track markers if using the main map (not mini maps)
                if (!mapInstance) {
                    this.markers.push(marker);
                }
            }
        });

        // Fit map to show all markers if there are any (only for main map)
        if (!mapInstance && this.markers.length > 0) {
            const bounds = new maplibregl.LngLatBounds();
            this.markers.forEach(marker => {
                bounds.extend(marker.getLngLat());
            });
            targetMap.fitBounds(bounds, { padding: 50 });
        }
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

//  * Find matches for a request
//  * @param {number} requestId - The ID of the request to find matches for
//  * @returns {Promise<void>}
//  */
window.findMatchesForRequest = function(requestId) {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.findMatchesForRequest(requestId);
    }
};

window.loadActiveRequests = function() {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.loadActiveRequests();
    }
};

window.loadActiveOffers = function() {
    if (window.coinExchangeManager) {
        return window.coinExchangeManager.loadActiveOffers();
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
