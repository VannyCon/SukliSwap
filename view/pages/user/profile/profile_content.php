<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">My Profile</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="profileManager.loadProfile()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form id="profileForm">
                        <!-- <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_name" class="form-label">Business Name</label>
                                    <input type="text" class="form-control" id="business_name" name="business_name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_type" class="form-label">Business Type</label>
                                    <select class="form-control" id="business_type" name="business_type">
                                        <option value="">Select business type</option>
                                        <option value="store">Store</option>
                                        <option value="piso_wifi">PisoWiFi</option>
                                        <option value="restaurant">Restaurant</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div> -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_number" class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Tell us about yourself..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Current Location</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="location" name="location" readonly>
                                <button type="button" class="btn btn-outline-secondary" id="getCurrentLocationBtn">
                                    <i class="fas fa-location-arrow"></i> Get Location
                                </button>
                            </div>
                            <input type="hidden" id="longitude" name="longitude">
                            <input type="hidden" id="latitude" name="latitude">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Profile Picture Section -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile Picture</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="text-center">
                                <img id="profileImage" src="../../assets/images/default-avatar.png" alt="Profile Picture" class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="profilePicture" class="form-label">Upload New Picture</label>
                                <input type="file" class="form-control" id="profilePicture" accept="image/*">
                            </div>
                            <button type="button" class="btn btn-success" onclick="profileManager.uploadProfilePicture()">
                                <i class="fas fa-upload"></i> Upload Picture
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Security Settings</h5>
                </div>
                <div class="card-body">
                    <form id="passwordChangeForm">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Statistics and Activity -->
        <div class="col-md-4">
            <!-- User Statistics -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <div id="userStatsContainer">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading statistics...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Status -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Account Status:</span>
                            <span class="badge bg-success" id="accountStatus">Active</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Member Since:</span>
                            <span id="memberSince">-</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Last Login:</span>
                            <span id="lastLogin">-</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Profile Completeness:</span>
                            <div class="progress" style="width: 60%;">
                                <div class="progress-bar" id="profileCompleteness" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="window.location.href='../requests/'">
                            <i class="fas fa-hand-holding-usd"></i> My Requests
                        </button>
                        <button class="btn btn-success" onclick="window.location.href='../offers/'">
                            <i class="fas fa-coins"></i> My Offers
                        </button>
                        <button class="btn btn-info" onclick="window.location.href='../transactions/'">
                            <i class="fas fa-exchange-alt"></i> My Transactions
                        </button>
                        <button class="btn btn-warning" onclick="window.location.href='../dashboard/'">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div id="recentActivityContainer">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading activity...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action cannot be undone. All your data will be permanently deleted.
                </div>
                <p>Are you sure you want to delete your account? This will remove:</p>
                <ul>
                    <li>All your requests and offers</li>
                    <li>Transaction history</li>
                    <li>Profile information</li>
                    <li>All associated data</li>
                </ul>
                <div class="mb-3">
                    <label for="deleteConfirmation" class="form-label">Type "DELETE" to confirm</label>
                    <input type="text" class="form-control" id="deleteConfirmation" placeholder="Type DELETE here">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteAccount" disabled>
                    <i class="fas fa-trash"></i> Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.profile-image-container {
    position: relative;
    display: inline-block;
}

.profile-image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    border-radius: 0.375rem;
}

.profile-image-container:hover .profile-image-overlay {
    opacity: 1;
}

.stats-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #dee2e6;
}

.stats-item:last-child {
    border-bottom: none;
}

.activity-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
    font-size: 0.9rem;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-time {
    color: #6c757d;
    font-size: 0.8rem;
}
</style>
