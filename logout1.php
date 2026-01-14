<?php
// logout.php
// ===========================
// SECURE LOGOUT SCRIPT
// ===========================

// Start session
session_start();

// Clear all session variables
$_SESSION = array();

// If session cookie exists, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session completely
session_destroy();

// Clear any existing output buffer
if (ob_get_length()) {
    ob_end_clean();
}

// Add security headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Set logout message for login page
$_SESSION['logout_message'] = "You have been successfully logged out. Please login again.";

// JavaScript for client-side cleanup (optional)
echo '<!DOCTYPE html>
<html>
<head>
    <title>Logging Out...</title>
    <script>
        // Clear localStorage and sessionStorage
        localStorage.clear();
        sessionStorage.clear();
        
        // Clear browser cache
        if (window.performance && window.performance.navigation.type === 2) {
            location.reload(true); // Force reload if came from back button
        }
        
        // Redirect to login page after cleanup
        setTimeout(function() {
            window.location.href = "../index.php";
        }, 500);
    </script>
</head>
<body>
    <div style="text-align: center; margin-top: 100px;">
        <h2>Logging you out...</h2>
        <p>Please wait while we secure your session.</p>
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
</body>
</html>';

exit();
?>