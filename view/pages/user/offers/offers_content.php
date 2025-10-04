<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">My Coin Offers</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#createOfferModal">
                    <i class="fas fa-plus"></i> New Offer
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="offersManager.loadOffers()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Status Filter</label>
                            <select class="form-select" id="statusFilter" onchange="offersManager.filterOffers()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="matched">Matched</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="coinTypeFilter" class="form-label">Coin Type</label>
                            <select class="form-select" id="coinTypeFilter" onchange="offersManager.filterOffers()">
                                <option value="">All Coin Types</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="searchOffers" class="form-label">Search</label>
                            <input type="text" class="form-control" id="searchOffers" placeholder="Search by location, notes..." onkeyup="offersManager.searchOffers()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button class="btn btn-outline-secondary" onclick="offersManager.clearFilters()">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="totalOffersCount">0</h4>
                            <p class="card-text">Total Offers</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-coins fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="activeOffersCount">0</h4>
                            <p class="card-text">Active</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-play fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="matchedOffersCount">0</h4>
                            <p class="card-text">Matched</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-handshake fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="completedOffersCount">0</h4>
                            <p class="card-text">Completed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Offers List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Offers</h5>
                </div>
                <div class="card-body">
                    <div id="offersContainer">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading offers...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Offer Modal -->
<div class="modal fade" id="createOfferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Coin Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="coinOfferForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="offer_coin_type" class="form-label">Coin Type</label>
                        <select class="form-control coin-type-select" id="offer_coin_type" name="coin_type_id" required>
                            <option value="">Select coin type</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="offer_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="offer_quantity" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="offer_location" class="form-label">Preferred Meeting Location</label>
                        <input type="text" class="form-control" id="offer_location" name="preferred_meeting_location" placeholder="e.g., SM Mall, Quezon City">
                    </div>
                    <input type="hidden" class="form-control" id="offer_meeting_longitude" name="meeting_longitude" step="any">
                    <input type="hidden" class="form-control" id="offer_meeting_latitude" name="meeting_latitude" step="any">
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary" id="getLocationBtn">
                            <i class="fas fa-location-arrow"></i> Get Current Location
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="offer_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="offer_notes" name="notes" rows="3" placeholder="Any additional information..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Offer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Offer Modal -->
<div class="modal fade" id="editOfferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Coin Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editOfferForm">
                <input type="hidden" id="edit_offer_id" name="offer_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_offer_coin_type" class="form-label">Coin Type</label>
                        <select class="form-control coin-type-select" id="edit_offer_coin_type" name="coin_type_id" required>
                            <option value="">Select coin type</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_offer_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="edit_offer_quantity" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_offer_location" class="form-label">Preferred Meeting Location</label>
                        <input type="text" class="form-control" id="edit_offer_location" name="preferred_meeting_location" placeholder="e.g., SM Mall, Quezon City">
                    </div>
                    <input type="hidden" class="form-control" id="edit_offer_meeting_longitude" name="meeting_longitude" step="any">
                    <input type="hidden" class="form-control" id="edit_offer_meeting_latitude" name="meeting_latitude" step="any">
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary" id="getEditLocationBtn">
                            <i class="fas fa-location-arrow"></i> Get Current Location
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="edit_offer_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="edit_offer_notes" name="notes" rows="3" placeholder="Any additional information..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Offer</button>
                </div>
            </form>
        </div>
    </div>
</div>
