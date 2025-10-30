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
                                <!-- <option value="disputed">Disputed</option> -->
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
        <!-- <div class="col-md-2">
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
        </div> -->
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

<!-- Messaging Modal -->
<div class="modal fade" id="messagingModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-comments"></i> Direct Message
                    <!-- <span id="connectionStatus" class="badge bg-secondary ms-2" style="font-size: 0.7em;">
                        <i class="fas fa-circle"></i> Connecting...
                    </span> -->
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="d-flex flex-column h-100">
                    <!-- Messages Container -->
                    <div id="messagesContainer" class="flex-grow-1 p-3" style="height: 400px; overflow-y: auto; background-color: #f8f9fa;">
                        <!-- Messages will be loaded here -->
                    </div>
                    
                    <!-- Message Input Area -->
                    <div class="border-top p-3">
                        <form id="messageForm" class="d-flex align-items-end">
                            <div class="flex-grow-1 me-2">
                                <div class="position-relative">
                                    <textarea id="messageInput" class="form-control" rows="2" placeholder="Type your message..." style="resize: none;"></textarea>
                                    <div id="attachmentPreview" class="mt-2 d-none"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="btn-group btn-group-sm">
                                        <input type="file" id="messageAttachment" accept="image/*" class="d-none">
                                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('messageAttachment').click()">
                                            <i class="fas fa-paperclip"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="emojiPicker">
                                            <i class="fas fa-smile"></i>
                                        </button>
                                        <div id="emojiPickerDropdown" class="position-absolute bg-white border rounded p-2 shadow" style="z-index: 1000; max-height: 200px; overflow-y: auto; display: none;"></div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Send
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
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

/* Messaging Styles */
.message-item {
    margin-bottom: 15px;
    display: flex;
}

.message-own {
    justify-content: flex-end;
}

.message-other {
    justify-content: flex-start;
}

.message-content {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
}

.message-own .message-content {
    background-color: #007bff;
    color: white;
    border-bottom-right-radius: 5px;
}

.message-other .message-content {
    background-color: #e9ecef;
    color: #333;
    border-bottom-left-radius: 5px;
}

.message-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 5px;
}

.message-own .message-meta {
    color: rgba(255, 255, 255, 0.8);
}

.message-other .message-meta {
    color: #6c757d;
}

.message-actions {
    margin-left: 10px;
}

.message-actions .btn {
    padding: 2px 6px;
    font-size: 0.75rem;
}

.message-image img {
    border-radius: 10px;
    cursor: pointer;
    transition: transform 0.2s;
}

.message-image img:hover {
    transform: scale(1.05);
}

.typing-indicator {
    padding: 10px 15px;
    color: #6c757d;
    font-style: italic;
}

.typing-indicator i {
    color: #007bff;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.attachment-preview {
    text-align: center;
}

#emojiPickerDropdown {
    position: absolute;
    bottom: 100%;
    left: 0;
    width: 300px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
}

#emojiPickerDropdown.show {
    display: block !important;
}

/* Scrollbar styling for messages container */
#messagesContainer::-webkit-scrollbar {
    width: 6px;
}

#messagesContainer::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#messagesContainer::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#messagesContainer::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
