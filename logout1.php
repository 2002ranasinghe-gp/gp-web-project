<?php
// logout1.php - Admin Panel Logout Script
// ===========================
// SECURE ADMIN LOGOUT WITH CACHE CONTROL
// ===========================

// Start session
session_start();

// Log logout activity
$admin_name = $_SESSION['admin_name'] ?? 'Unknown';
$logout_time = date('Y-m-d H:i:s');

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear output buffer
if (ob_get_length()) {
    ob_end_clean();
}

// Security headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Set logout message for login page
$_SESSION['logout_success'] = "Successfully logged out. Please login again.";

// Redirect with JavaScript for better security
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Logout - Healthcare Hospital</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
    <style>
        body {
            background: linear-gradient(135deg, #0077b6 0%, #0096c7 100%);
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .logout-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
            animation: fadeIn 0.5s ease-in;
        }
        
        .logout-icon {
            font-size: 80px;
            color: #0077b6;
            margin-bottom: 20px;
            animation: bounce 1s infinite alternate;
        }
        
        @keyframes bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-10px); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        h2 {
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        p {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #0077b6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid #0077b6;
        }
        
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #555;
        }
        
        .progress {
            height: 6px;
            margin-top: 20px;
        }
        
        .progress-bar {
            background-color: #0077b6;
            animation: progress 2s ease-in-out;
        }
        
        @keyframes progress {
            from { width: 0%; }
            to { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h2>Admin Logout</h2>
        <p>You are being securely logged out from the Admin Panel.<br>Please wait while we clear your session...</p>
        
        <div class="spinner"></div>
        
        <div class="progress">
            <div class="progress-bar" role="progressbar"></div>
        </div>
        
        <div class="info-box mt-4">
            <p><i class="fas fa-shield-alt mr-2"></i> For security reasons, your session has been terminated.</p>
            <p><i class="fas fa-clock mr-2"></i> Logout Time: <?php echo date('h:i:s A'); ?></p>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Clear all browser storage
        localStorage.clear();
        sessionStorage.clear();
        
        // Clear form data
        if (window.FormData) {
            new FormData();
        }
        
        // Clear browser cache
        if ('caches' in window) {
            caches.keys().then(function(names) {
                for (let name of names) {
                    caches.delete(name);
                }
            });
        }
        
        // Prevent back button after logout
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            history.pushState(null, null, document.URL);
        });
        
        // Disable forward/back navigation
        window.history.replaceState(null, null, window.location.href);
        window.onpopstate = function(event) {
            window.history.go(1);
        };
        
        // Redirect after 3 seconds
        let count = 3;
        const countdown = setInterval(function() {
            if (count <= 0) {
                clearInterval(countdown);
                // Force page reload to clear any cached data
                window.location.replace('../index.php');
            } else {
                count--;
            }
        }, 1000);
        
        // Alternative: Redirect if user tries to go back
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.replace('../index.php');
            }
        });
    </script>
</body>
</html>
<?php exit(); ?>