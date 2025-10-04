<?php 
// Start output buffering to capture the page content
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cemetery Locator & Management System</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Axios for HTTP requests -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link  href="../../css/boxicons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome/css/all.min.css">
    <link rel="icon" href="../../../assets/images/logo.png" type="image/x-icon" />
    <link rel='stylesheet' href='https://unpkg.com/maplibre-gl@5.7.3/dist/maplibre-gl.css' />
    <script src='https://unpkg.com/maplibre-gl@5.7.3/dist/maplibre-gl.js'></script>
    
    <!-- Mapbox GL Draw for professional drawing tools -->
    <script src="https://www.unpkg.com/@mapbox/mapbox-gl-draw@1.5.0/dist/mapbox-gl-draw.js"></script>
    <link rel="stylesheet" href="https://www.unpkg.com/@mapbox/mapbox-gl-draw@1.5.0/dist/mapbox-gl-draw.css" />
    
    <!-- Turf.js for area calculations -->
    <script type="module">
        import * as turf from 'https://esm.sh/@turf/turf@7.1.0';
        window.turf = turf;
    </script>
    <style>
        .leaflet-draw-toolbar {
            display: none !important;
        }
        .custom-div-icon {
            background: transparent;
            border: none;
        }
        .leaflet-popup-content {
            margin: 8px 12px;
            line-height: 1.4;
        }
        .leaflet-popup-content .btn {
            margin: 2px;
        }
    </style>
</head>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-family: 'Poppins', sans-serif;
}

.navbar-brand {
    font-weight: bold;
}

.dashboard-card {
    background-color: white;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.stat-number a {
    text-decoration: none;
    color: inherit;
}

.stat-number {
    font-size: 3rem;
    font-weight: bold;
    color: #8B4513;
}

.stat-label {
    color: #6c757d;
}

.action-button {
    background-color: #8B4513;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 5px;
    width: 100%;
    margin-top: 10px;
}

.recent-activities {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.stats-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.stats-row {
    display: flex;
    flex: 1;
}


.stats-col {
    flex: 1 1 calc(25% - 15px); /* Adjust card size for responsiveness */
    max-width: calc(25% - 15px);
}

@media (max-width: 768px) {
    .stats-col {
        flex: 1 1 calc(50% - 15px); /* Adjust card size for smaller screens */
        max-width: calc(50% - 15px);
    }
}

@media (max-width: 576px) {
    .stats-col {
        flex: 1 1 100%; /* Full width for very small screens */
        max-width: 100%;
    }
}

.stats-card {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.modal.fade .modal-dialog.modal-dialog-slideright {
    transform: translate(100%, 0);
    transition: transform 0.3s ease-out;
}

.modal.show .modal-dialog.modal-dialog-slideright {
    transform: translate(0, 0);
}

.confirmation-details {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.detail-item {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: #495057;
}

.detail-value {
    color: #212529;
}

.table-responsive {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
       
.floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
@keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

</style>
<div class="container-fluid">
    <div class="row min-vh-100">
        <!-- Left side - Branding -->
        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-primary">
            <div class="text-center text-white p-5">
                <div class="mb-4">
                    <i class="fas fa-coins fa-5x"></i>
                </div>
                <h1 class="display-4 fw-bold mb-3">SukliSwap</h1>
                <p class="lead mb-4">Connect with your community to exchange coins safely and efficiently</p>
                <div class="row text-center">
                    <div class="col-md-6 mb-3">
                        <i class="fas fa-handshake fa-2x mb-2"></i>
                        <h5>Community Exchange</h5>
                        <p class="small">Trade coins with local users</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                        <h5>Location-Based</h5>
                        <p class="small">Find nearby exchange partners</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <i class="fas fa-qrcode fa-2x mb-2"></i>
                        <h5>Secure Transactions</h5>
                        <p class="small">QR code verification system</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <i class="fas fa-star fa-2x mb-2"></i>
                        <h5>Trust & Safety</h5>
                        <p class="small">User rating and review system</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side - Registration Form -->
        <div class="col-lg-6 d-flex align-items-center justify-content-center p-5">
            <div class="w-100" style="max-width: 400px;">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-primary">Create Account</h2>
                    <p class="text-muted">Join SukliSwap and start exchanging coins today</p>
                </div>

                <form id="registerForm" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                            <div class="invalid-feedback">
                                Please provide your first name.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                            <div class="invalid-feedback">
                                Please provide your last name.
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="invalid-feedback">
                            Please choose a username.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">
                            Please provide a valid email address.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                        <div class="form-text">Optional - for better communication</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Password must be at least 6 characters long.
                        </div>
                        <div class="form-text">
                            <small>Password must be at least 6 characters long</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">
                            Passwords do not match.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="business_type" class="form-label">Business Type</label>
                        <select class="form-select" id="business_type" name="business_type">
                            <option value="">Select your business type (optional)</option>
                            <option value="store">Store</option>
                            <option value="piso_wifi">PisoWiFi</option>
                            <option value="restaurant">Restaurant</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="business_name" class="form-label">Business Name</label>
                        <input type="text" class="form-control" id="business_name" name="business_name">
                        <div class="form-text">Optional - your business or establishment name</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-primary">Terms of Service</a> and <a href="#" class="text-primary">Privacy Policy</a>
                            </label>
                            <div class="invalid-feedback">
                                You must agree to the terms and conditions.
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>

                    <div class="text-center">
                        <p class="mb-0">Already have an account? 
                            <a href="login.php" class="text-primary fw-semibold">Sign in here</a>
                        </p>
                    </div>
                </form>

                <!-- Success/Error Messages -->
                <div id="messageContainer" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Include required scripts -->
<script src="view/js/auth.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const messageContainer = document.getElementById('messageContainer');

    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        confirmPasswordField.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    // Real-time password confirmation validation
    confirmPasswordField.addEventListener('input', function() {
        if (this.value !== passwordField.value) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });

    // Form submission
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Clear previous messages
        messageContainer.innerHTML = '';
        
        // Validate form
        if (!registerForm.checkValidity()) {
            registerForm.classList.add('was-validated');
            return;
        }

        // Check password confirmation
        if (passwordField.value !== confirmPasswordField.value) {
            showMessage('Passwords do not match', 'error');
            return;
        }

        // Disable submit button
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';

        try {
            const formData = new FormData(this);
            
            // Prepare data as JSON
            const data = {};
            
            // Convert FormData to object
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }

            const response = await axios.post('../../../auth/auth.php?action=register', data, {
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (response.data.success) {
                showMessage('Account created successfully! Redirecting to login...', 'success');
                
                // Redirect to login page after 2 seconds
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                showMessage(response.data.message || 'Registration failed. Please try again.', 'error');
            }
        } catch (error) {
            console.error('Registration error:', error);
            showMessage('Registration failed. Please check your connection and try again.', 'error');
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    function showMessage(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        messageContainer.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${icon} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }

    // Real-time validation
    const inputs = registerForm.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });

        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });
});
</script>

<style>
.min-vh-100 {
    min-height: 100vh;
}

.bg-primary {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.form-control:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.btn-primary {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-primary:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.text-primary {
    color: #28a745 !important;
}

.is-valid {
    border-color: #28a745;
}

.is-invalid {
    border-color: #dc3545;
}

.alert {
    border-radius: 0.5rem;
}

@media (max-width: 991.98px) {
    .col-lg-6:first-child {
        display: none !important;
    }
    
    .col-lg-6:last-child {
        padding: 2rem 1rem;
    }
}
</style>