<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">My Transactions</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="transactionsManager.loadTransactions()">
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
                        <div class="col-md-2">
                            <label for="statusFilter" class="form-label">Status Filter</label>
                            <select class="form-select" id="statusFilter" onchange="transactionsManager.filterTransactions()">
                                <option value="">All Status</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="disputed">Disputed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="typeFilter" class="form-label">Type</label>
                            <select class="form-select" id="typeFilter" onchange="transactionsManager.filterTransactions()">
                                <option value="">All Types</option>
                                <option value="request">Request</option>
                                <option value="offer">Offer</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="dateFromFilter" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="dateFromFilter" onchange="transactionsManager.filterTransactions()">
                        </div>
                        <div class="col-md-2">
                            <label for="dateToFilter" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="dateToFilter" onchange="transactionsManager.filterTransactions()">
                        </div>
                        <div class="col-md-3">
                            <label for="searchTransactions" class="form-label">Search</label>
                            <input type="text" class="form-control" id="searchTransactions" placeholder="Search by location, notes..." onkeyup="transactionsManager.searchTransactions()">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button class="btn btn-outline-secondary" onclick="transactionsManager.clearFilters()">
                                    <i class="fas fa-times"></i>
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
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="totalTransactionsCount">0</h4>
                            <p class="card-text">Total</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exchange-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="scheduledTransactionsCount">0</h4>
                            <p class="card-text">Scheduled</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="inProgressTransactionsCount">0</h4>
                            <p class="card-text">In Progress</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-play fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="completedTransactionsCount">0</h4>
                            <p class="card-text">Completed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="cancelledTransactionsCount">0</h4>
                            <p class="card-text">Cancelled</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="disputedTransactionsCount">0</h4>
                            <p class="card-text">Disputed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Transaction History</h5>
                </div>
                <div class="card-body">
                    <div id="transactionsContainer">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading transactions...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="transactionDetailsContent">
                <!-- Transaction details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Complete Transaction Modal -->
<div class="modal fade" id="completeTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="completeTransactionForm">
                <input type="hidden" id="complete_transaction_id" name="transaction_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Please confirm that you have completed the coin exchange transaction.
                    </div>
                    <div class="mb-3">
                        <label for="completion_notes" class="form-label">Completion Notes (Optional)</label>
                        <textarea class="form-control" id="completion_notes" name="completion_notes" rows="3" placeholder="Any additional information about the transaction..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="rating" class="form-label">Rate Your Experience</label>
                        <div class="rating">
                            <input type="radio" id="star5" name="rating" value="5">
                            <label for="star5" title="Excellent">&#9733;</label>
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4" title="Good">&#9733;</label>
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3" title="Average">&#9733;</label>
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2" title="Poor">&#9733;</label>
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1" title="Very Poor">&#9733;</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Complete Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Dispute Transaction Modal -->
<div class="modal fade" id="disputeTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report Transaction Dispute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="disputeTransactionForm">
                <input type="hidden" id="dispute_transaction_id" name="transaction_id">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Please provide details about the issue with this transaction. Our support team will review your report.
                    </div>
                    <div class="mb-3">
                        <label for="dispute_reason" class="form-label">Reason for Dispute</label>
                        <select class="form-control" id="dispute_reason" name="dispute_reason" required>
                            <option value="">Select a reason</option>
                            <option value="no_show">Counterparty didn't show up</option>
                            <option value="wrong_coins">Wrong coin type or quantity</option>
                            <option value="poor_condition">Coins in poor condition</option>
                            <option value="fraud">Suspected fraud</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="dispute_description" class="form-label">Description</label>
                        <textarea class="form-control" id="dispute_description" name="dispute_description" rows="4" placeholder="Please provide detailed information about the issue..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Report Dispute</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Start/Verify Transaction Modal (QR) -->
<div class="modal fade" id="startTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verify Meeting via QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="qrRoleNotice" class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i>
                    Please follow the instructions below to verify the meeting.
                </div>

                <div id="qrScannerSection" class="d-none">
                    <div class="mb-2"><strong>Scan the presenter's QR code</strong></div>
                    <div id="qrReader" style="width: 100%; max-width: 480px"></div>
                    <div id="qrScanStatus" class="small text-muted mt-2"></div>
                </div>

                <div id="qrPresentSection" class="d-none">
                    <div class="mb-2"><strong>Show this QR code to the scanner</strong></div>
                    <div id="qrCodeCanvas" class="d-flex justify-content-center mb-2"></div>
                    <div class="text-center">
                        <code id="qrCodeText" class="small"></code>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="qrSuccessModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verification Successful</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Meeting verified. Transaction marked completed.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- External libs for QR (loaded only on this page) -->
<script src="https://unpkg.com/html5-qrcode" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>

<style>
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.rating input {
    display: none;
}

.rating label {
    font-size: 2em;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.rating label:hover,
.rating label:hover ~ label,
.rating input:checked ~ label {
    color: #ffc107;
}
</style>
