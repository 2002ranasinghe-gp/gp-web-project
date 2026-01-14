<?php
// login.php - Admin Login Page
// ===========================
session_start();

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin-dashboard.php");
    exit();
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection (update with your credentials)
    $host = 'localhost';
    $dbname = 'hospital_db';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $email = $_POST['email'];
        $password_input = $_POST['password'];
        
        // Query admin (in real application, use prepared statements)
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password_input, $admin['password'])) {
            // Set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['login_time'] = time();
            
            // Log login activity
            $log_stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, activity, ip_address) VALUES (?, 'Login', ?)");
            $log_stmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR']]);
            
            // Redirect to dashboard
            header("Location: admin-dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Healthcare Hospital</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0077b6 0%, #0096c7 100%);
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            animation: slideIn 0.6s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-icon {
            font-size: 50px;
            color: #0077b6;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #0077b6;
            box-shadow: 0 0 0 0.2rem rgba(0,119,182,0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #0077b6, #0096c7);
            border: none;
            border-radius: 10px;
            padding: 12px;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,119,182,0.3);
        }
        
        .forgot-link {
            color: #0077b6;
            text-decoration: none;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h2>Admin Login</h2>
            <p class="text-muted">Healthcare Hospital Management System</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['logout_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['logout_success']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['logout_success']); ?>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope mr-2"></i>Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="admin@hospital.com">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock mr-2"></i>Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                <small class="form-text text-muted">
                    <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                </small>
            </div>
            
            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            
            <button type="submit" class="btn btn-login btn-block">
                <i class="fas fa-sign-in-alt mr-2"></i> Login
            </button>
        </form>
        
        <hr class="my-4">
        
        <div class="text-center">
            <p class="text-muted small">
                <i class="fas fa-shield-alt mr-2"></i> Secure login with session timeout
                <br>
                <i class="fas fa-clock mr-2"></i> Session valid for 30 minutes
            </p>
            <p class="small text-muted mt-3">
                &copy; <?php echo date('Y'); ?> Healthcare Hospital. All rights reserved.
            </p>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Show/hide password
        document.getElementById('password').addEventListener('input', function() {
            var toggle = document.createElement('span');
            toggle.className = 'password-toggle';
            toggle.innerHTML = '👁️';
            toggle.style.cursor = 'pointer';
            toggle.style.position = 'absolute';
            toggle.style.right = '10px';
            toggle.style.top = '50%';
            toggle.style.transform = 'translateY(-50%)';
            
            if (!this.parentNode.querySelector('.password-toggle')) {
                this.parentNode.style.position = 'relative';
                this.parentNode.appendChild(toggle);
                
                toggle.addEventListener('click', function() {
                    var input = this.parentNode.querySelector('input');
                    if (input.type === 'password') {
                        input.type = 'text';
                        this.innerHTML = '👁️‍🗨️';
                    } else {
                        input.type = 'password';
                        this.innerHTML = '👁️';
                    }
                });
            }
        });
        
        // Enter key to submit
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.target.matches('button')) {
                document.querySelector('form').submit();
            }
        });
    </script>
</body>
</html>