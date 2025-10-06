/**
 * Profile Manager - Handles user profile operations
 */
class ProfileManager {
    constructor() {
        this.userProfile = null;
        this.userStats = null;
        this.recentActivity = [];
        this.init();
    }

    init() {
        this.loadProfile();
        this.loadUserStats();
        this.loadUserActivity();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Profile form
        document.getElementById('profileForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateProfile();
        });

        // Password change form
        document.getElementById('passwordChangeForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.changePassword();
        });

        // Location button
        document.getElementById('getCurrentLocationBtn')?.addEventListener('click', () => {
            this.getCurrentLocation();
        });

        // Profile picture upload
        document.getElementById('profilePicture')?.addEventListener('change', (e) => {
            this.handleProfilePictureChange(e);
        });

        // Delete account confirmation
        document.getElementById('deleteConfirmation')?.addEventListener('input', (e) => {
            const confirmBtn = document.getElementById('confirmDeleteAccount');
            confirmBtn.disabled = e.target.value !== 'DELETE';
        });

        document.getElementById('confirmDeleteAccount')?.addEventListener('click', () => {
            this.deleteAccount();
        });
    }

    async loadProfile() {
        try {
            const response = await fetch('/api/user_profile.php', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.userProfile = data.data;
                    this.populateProfileForm();
                    this.updateProfileDisplay();
                } else {
                    this.showError(data.message);
                }
            }
        } catch (error) {
            console.error('Error loading profile:', error);
            this.showError('Failed to load profile');
        }
    }

    populateProfileForm() {
        if (!this.userProfile) return;

        // Populate form fields
        document.getElementById('business_name').value = this.userProfile.business_name || '';
        document.getElementById('business_type').value = this.userProfile.business_type || '';
        document.getElementById('contact_number').value = this.userProfile.contact_number || '';
        document.getElementById('email').value = this.userProfile.email || '';
        document.getElementById('address').value = this.userProfile.address || '';
        document.getElementById('bio').value = this.userProfile.bio || '';
        document.getElementById('location').value = this.userProfile.location || '';
        document.getElementById('longitude').value = this.userProfile.longitude || '';
        document.getElementById('latitude').value = this.userProfile.latitude || '';

        // Update profile image
        if (this.userProfile.profile_image) {
            document.getElementById('profileImage').src = this.userProfile.profile_image;
        }
    }

    updateProfileDisplay() {
        if (!this.userProfile) return;

        // Update account status
        document.getElementById('accountStatus').textContent = this.userProfile.status || 'Active';
        document.getElementById('memberSince').textContent = new Date(this.userProfile.created_at).toLocaleDateString();
        document.getElementById('lastLogin').textContent = this.userProfile.last_login ? 
            new Date(this.userProfile.last_login).toLocaleString() : 'Never';

        // Update profile completeness
        const completeness = this.calculateProfileCompleteness();
        document.getElementById('profileCompleteness').style.width = `${completeness}%`;
    }

    calculateProfileCompleteness() {
        if (!this.userProfile) return 0;

        const fields = [
            'business_name', 'business_type', 'contact_number', 
            'email', 'address', 'bio', 'location'
        ];

        let completedFields = 0;
        fields.forEach(field => {
            if (this.userProfile[field] && this.userProfile[field].trim() !== '') {
                completedFields++;
            }
        });

        return Math.round((completedFields / fields.length) * 100);
    }

    async loadUserStats() {
        try {
            const response = await fetch('/api/user_profile.php/stats', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.userStats = data.data;
                    this.renderUserStats();
                }
            }
        } catch (error) {
            console.error('Error loading user stats:', error);
        }
    }

    renderUserStats() {
        const container = document.getElementById('userStatsContainer');
        if (!container || !this.userStats) return;

        container.innerHTML = `
            <div class="stats-item">
                <div class="d-flex justify-content-between">
                    <span>Total Requests:</span>
                    <strong>${this.userStats.total_requests || 0}</strong>
                </div>
            </div>
            <div class="stats-item">
                <div class="d-flex justify-content-between">
                    <span>Total Offers:</span>
                    <strong>${this.userStats.total_offers || 0}</strong>
                </div>
            </div>
            <div class="stats-item">
                <div class="d-flex justify-content-between">
                    <span>Total Transactions:</span>
                    <strong>${this.userStats.total_transactions || 0}</strong>
                </div>
            </div>
            <div class="stats-item">
                <div class="d-flex justify-content-between">
                    <span>Completed Transactions:</span>
                    <strong>${this.userStats.completed_transactions || 0}</strong>
                </div>
            </div>
            <div class="stats-item">
                <div class="d-flex justify-content-between">
                    <span>Average Rating:</span>
                    <strong>${this.userStats.average_rating ? this.userStats.average_rating.toFixed(1) : 'N/A'}</strong>
                </div>
            </div>
        `;
    }

    async loadUserActivity() {
        try {
            const response = await fetch('/api/user_profile.php/activity?limit=5', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.recentActivity = data.data;
                    this.renderRecentActivity();
                }
            }
        } catch (error) {
            console.error('Error loading user activity:', error);
        }
    }

    renderRecentActivity() {
        const container = document.getElementById('recentActivityContainer');
        if (!container) return;

        if (this.recentActivity.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-history fa-2x mb-2"></i>
                    <p>No recent activity</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.recentActivity.map(activity => `
            <div class="activity-item">
                <div class="d-flex justify-content-between">
                    <span>${activity.description}</span>
                    <small class="activity-time">${new Date(activity.created_at).toLocaleDateString()}</small>
                </div>
            </div>
        `).join('');
    }

    async updateProfile() {
        const form = document.getElementById('profileForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('/api/user_profile.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('Profile updated successfully');
                this.loadProfile();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            this.showError('Failed to update profile');
        }
    }

    async changePassword() {
        const form = document.getElementById('passwordChangeForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        if (data.new_password !== data.confirm_password) {
            this.showError('New passwords do not match');
            return;
        }

        if (data.new_password.length < 6) {
            this.showError('New password must be at least 6 characters long');
            return;
        }

        try {
            const response = await fetch('/api/user_profile.php/password', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({
                    current_password: data.current_password,
                    new_password: data.new_password
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('Password changed successfully');
                form.reset();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error changing password:', error);
            this.showError('Failed to change password');
        }
    }

    getCurrentLocation() {
        if (!navigator.geolocation) {
            this.showError('Geolocation is not supported by this browser');
            return;
        }

        const locationBtn = document.getElementById('getCurrentLocationBtn');
        locationBtn.disabled = true;
        locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lng;
                
                // Get address from coordinates (you might want to use a geocoding service)
                document.getElementById('location').value = `${lat}, ${lng}`;
                
                locationBtn.disabled = false;
                locationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get Current Location';
                
                CustomToast.show('success', 'Location updated successfully!');
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

    handleProfilePictureChange(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image/')) {
            this.showError('Please select a valid image file');
            event.target.value = '';
            return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            this.showError('Image file size must be less than 5MB');
            event.target.value = '';
            return;
        }

        // Preview image
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('profileImage').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    async uploadProfilePicture() {
        const fileInput = document.getElementById('profilePicture');
        const file = fileInput.files[0];

        if (!file) {
            this.showError('Please select an image file');
            return;
        }

        const formData = new FormData();
        formData.append('profile_picture', file);

        try {
            const response = await fetch('/api/user_profile.php/upload-picture', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('Profile picture uploaded successfully');
                fileInput.value = '';
                this.loadProfile();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error uploading profile picture:', error);
            this.showError('Failed to upload profile picture');
        }
    }

    async deleteAccount() {
        if (!confirm('Are you absolutely sure you want to delete your account? This action cannot be undone!')) {
            return;
        }

        try {
            const response = await fetch('/api/user_profile.php/account', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({
                    confirmation: 'DELETE'
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('Account deleted successfully');
                // Redirect to logout or home page
                setTimeout(() => {
                    window.location.href = '/auth/logout.php';
                }, 2000);
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error deleting account:', error);
            this.showError('Failed to delete account');
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
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.profileManager = new ProfileManager();
});
