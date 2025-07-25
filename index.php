<?php
/*
 * Login Page
 * Handles user authentication and session creation
 */

// Start the session, this is needed for tracking logged in users
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['loggedin'])) {
    header('Location: dashboard.php');
    exit;
}

// Initialize error variable
$error = null;

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and clean form inputs
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // demo credentials
    $validUsername = 'admin';
    $validPassword = 'admin123'; 
    
    // Basic authentication check
    if ($username === $validUsername && $password === $validPassword) {
        // Set session variables
        $_SESSION['username'] = $username;
        $_SESSION['loggedin'] = true;
        
        // For security, regenerate session ID
        session_regenerate_id(true);
        
        // Redirect to dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        // error message in case of incorrect details
        $error = "Invalid username or password. Please try again.";
        // Log failed attempt 
        error_log("Failed login attempt for username: $username");
    }
}
?>

<!-- The HTML code and all the styling with all the basic bootstrap styling for the login UI -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Responsive viewport settings -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ModernTech HR System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        /* Custom styles for the login page */
        body.bg-light {
            background-color: #f8f9fa !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: none;
        }
        .card-header {
            background: transparent;
            border-bottom: none;
        }
        .input-group-text {
            background-color: #f8f9fa;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            padding: 10px;
            font-weight: 600;
        }
        .brand-logo {
            color: #4e73df;
            font-weight: 700;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 login-container">
                <!-- Login Card -->
                <div class="card shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <!-- Brand Header -->
                        <div class="text-center mb-4">
                            <h2 class="brand-logo mb-3">
                                <i class="bi bi-people-fill me-2"></i>
                                ModernTech HR
                            </h2>
                            <p class="text-muted">Please sign in to access your account</p>
                        </div>
                        
                        <!-- Error Message (if any) -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Login Form -->
                        <form method="POST" novalidate>
                            <!-- Username Field -->
                            <div class="mb-4">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person-fill"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" 
                                           name="username" required autofocus
                                           placeholder="Enter your username">
                                </div>
                            </div>
                            
                            <!-- Password Field -->
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" 
                                           name="password" required
                                           placeholder="Enter your password">
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Sign In
                            </button>
                            
                        </form>
                        
                        <hr class="my-4">
                        
                        <!-- Demo Credentials Note -->
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <div>
                                    <strong>Demo credentials:</strong><br>
                                    Username: <code>admin</code><br>
                                    Password: <code>admin123</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Copyright Notice just to make it look nicer-->
                <p class="text-center mt-4 text-muted">
                    <small>&copy; <?= date('Y') ?> ModernTech Solutions. All rights reserved.</small>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Small script to focus on username field by default -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>