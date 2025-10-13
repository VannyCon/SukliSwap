<?php require_once '../../components/toast.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Declined - SukliSwap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .decline-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .decline-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .decline-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .decline-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: shake 2s infinite;
        }
        
        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-5px);
            }
            75% {
                transform: translateX(5px);
            }
        }
        
        .decline-content {
            padding: 2.5rem;
            text-align: center;
        }
        
        .status-badge {
            display: inline-block;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-logout {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            color: white;
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(108, 117, 125, 0.4);
            background: linear-gradient(135deg, #5a6268 0%, #343a40 100%);
            color: white;
        }
        
        .btn-check-status {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            color: white;
        }
        
        .btn-check-status:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(23, 162, 184, 0.4);
            background: linear-gradient(135deg, #138496 0%, #0c5460 100%);
            color: white;
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
        
        .info-box {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .info-box h6 {
            color: #dc3545;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .info-box p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="decline-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="decline-container">
                        <div class="decline-header">
                            <div class="decline-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h2>Account Declined</h2>
                            <p class="mb-0">Application Not Approved</p>
                        </div>
                    
                        <div class="decline-content">
                            <div class="status-badge">
                                <i class="fas fa-exclamation-triangle me-2"></i>Declined
                            </div>
                            
                            <h4 class="mb-3">Your account application has been declined</h4>
                            <p class="text-muted mb-4">
                                We're sorry, but your account application has not been approved at this time. 
                                This decision was made after careful review by our administrators.
                            </p>
                            
                            <div class="info-box">
                                <h6><i class="fas fa-info-circle me-2"></i>Why was my account declined?</h6>
                                <p>
                                    Account applications may be declined for various reasons including incomplete information, 
                                    policy violations, or other administrative considerations.
                                </p>
                            </div>
                            
                            <div class="info-box">
                                <h6><i class="fas fa-phone me-2"></i>Need Help?</h6>
                                <p>
                                    If you believe this is an error or need more information, please contact our support team 
                                    for assistance with your application.
                                </p>
                            </div>
                            
                            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                                <button class="btn btn-logout" onclick="logout()">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Check verification status
        
        // Logout function
        function logout() {
            // Clear local storage
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_data');
            
            CustomToast.success('Logged out successfully');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1000);
        }
        // Auto-refresh status every 30 seconds to check if status changed
        setInterval(() => {
            const token = localStorage.getItem('auth_token');
            if (token) {
                fetch('../../auth/auth.php?action=check', {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.authenticated) {
                        const user = result.data.user;
                        
                        // If user status changed from declined, redirect appropriately
                        if (user.is_verified == 1) {
                            CustomToast.success('Account verified! Redirecting...');
                            setTimeout(() => {
                                if (user.role === 'admin') {
                                    window.location.href = '../admin/dashboard/';
                                } else {
                                    window.location.href = '../user/dashboard/';
                                }
                            }, 1500);
                        } else if (user.is_verified == 0 && user.is_active == 1) {
                            // Account is now pending instead of declined
                            CustomToast.info('Account status updated. Redirecting...');
                            setTimeout(() => {
                                window.location.href = 'pending.php';
                            }, 1500);
                        }
                    }
                })
                .catch(error => {
                    console.error('Auto-refresh error:', error);
                });
            }
        }, 30000); // Check every 30 seconds
    </script>
</body>
</html>
