<?php
// patient-dashboard.php
// ===========================
// SECURITY HEADERS & CACHE CONTROL
// ===========================
ob_start(); // Start output buffering
session_start();

// Prevent caching of secure pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// ===========================
// DATABASE CONNECTION
// ===========================
$con = mysqli_connect("localhost", "root", "", "myhmsdb");

if(!$con){
    die("Database connection failed: " . mysqli_connect_error());
}

// ===========================
// SESSION VALIDATION
// ===========================
if(!isset($_SESSION['patient'])){
    $_SESSION['error'] = "Session expired. Please login again.";
    header("Location: ../index.php");
    exit();
}

// Validate session timeout (30 minutes)
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    $_SESSION['error'] = "Session expired. Please login again.";
    header("Location: ../index.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// ===========================
// PATIENT DATA FETCH
// ===========================
$email = mysqli_real_escape_string($con, $_SESSION['patient']);
$query = "SELECT * FROM patreg WHERE email='$email' LIMIT 1";
$result = mysqli_query($con, $query);

if(!$result || mysqli_num_rows($result) == 0){
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$patient = mysqli_fetch_assoc($result);
$patient_id = $patient['pid'];
$patient_name = $patient['fname'] . ' ' . $patient['lname'];
$national_id = $patient['national_id'];

// ===========================
// GET STATISTICS FOR DASHBOARD
// ===========================
$total_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email'"));
$confirmed_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email' AND doctorStatus=1"));
$pending_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email' AND doctorStatus=0"));
$total_prescriptions = mysqli_num_rows(mysqli_query($con, "SELECT * FROM prestb WHERE national_id='$national_id'"));
$pending_payments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM paymenttb WHERE national_id='$national_id' AND pay_status='Pending'"));
$completed_payments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM paymenttb WHERE national_id='$national_id' AND pay_status='Paid'"));
$today_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email' AND appdate = CURDATE()"));

// ===========================
// GET DATA FOR TABLES
// ===========================
$appointments = [];
$prescriptions = [];
$payments = [];
$doctors = [];

// Get appointments
$appointment_query = mysqli_query($con, "SELECT a.*, d.spec FROM appointmenttb a LEFT JOIN doctb d ON a.doctor = d.username WHERE a.email='$email' ORDER BY a.appdate DESC");
while($row = mysqli_fetch_assoc($appointment_query)){
    $appointments[] = $row;
}

// Get prescriptions
$prescription_query = mysqli_query($con, "SELECT * FROM prestb WHERE national_id='$national_id' ORDER BY appdate DESC");
while($row = mysqli_fetch_assoc($prescription_query)){
    $prescriptions[] = $row;
}

// Get payments
$payment_query = mysqli_query($con, "SELECT * FROM paymenttb WHERE national_id='$national_id' ORDER BY pay_date DESC");
while($row = mysqli_fetch_assoc($payment_query)){
    $payments[] = $row;
}

// Get doctors
$doctor_query = mysqli_query($con, "SELECT * FROM doctb ORDER BY username");
while($row = mysqli_fetch_assoc($doctor_query)){
    $doctors[] = $row;
}

// ===========================
// UPDATE PROFILE
// ===========================
$profile_msg = "";
if(isset($_POST['update_profile'])){
    $contact = mysqli_real_escape_string($con, $_POST['contact']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $emergencyContact = mysqli_real_escape_string($con, $_POST['emergencyContact']);
    
    $update_query = "UPDATE patreg SET contact='$contact', address='$address', emergencyContact='$emergencyContact' WHERE pid='$patient_id'";
    
    if(mysqli_query($con, $update_query)){
        $profile_msg = "<div class='alert alert-success'>✅ Profile updated successfully!</div>";
        $query = "SELECT * FROM patreg WHERE email='$email' LIMIT 1";
        $result = mysqli_query($con, $query);
        $patient = mysqli_fetch_assoc($result);
        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $profile_msg = "<div class='alert alert-danger'>❌ Error updating profile: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// CHANGE PASSWORD
// ===========================
$password_msg = "";
if(isset($_POST['change_password'])){
    $current_password = mysqli_real_escape_string($con, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($con, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);
    
    if($current_password == $patient['password']){
        if($new_password === $confirm_password){
            if(strlen($new_password) >= 6){
                $update_query = "UPDATE patreg SET password='$new_password' WHERE pid='$patient_id'";
                if(mysqli_query($con, $update_query)){
                    $password_msg = "<div class='alert alert-success'>✅ Password changed successfully!</div>";
                    $_SESSION['success'] = "Password changed successfully!";
                } else {
                    $password_msg = "<div class='alert alert-danger'>❌ Error updating password: " . mysqli_error($con) . "</div>";
                }
            } else {
                $password_msg = "<div class='alert alert-danger'>❌ Password must be at least 6 characters!</div>";
            }
        } else {
            $password_msg = "<div class='alert alert-danger'>❌ New passwords do not match!</div>";
        }
    } else {
        $password_msg = "<div class='alert alert-danger'>❌ Current password is incorrect!</div>";
    }
}

// ===========================
// CANCEL APPOINTMENT
// ===========================
$appointment_msg = "";
if(isset($_POST['cancel_appointment'])){
    $appointment_id = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $reason = "Cancelled by patient";
    
    $query = "UPDATE appointmenttb SET 
              appointmentStatus='cancelled',
              cancelledBy='Patient',
              cancellationReason='$reason',
              userStatus=0 
              WHERE ID='$appointment_id' AND email='$email'";
    
    if(mysqli_query($con, $query)){
        $appointment_msg = "<div class='alert alert-success'>✅ Appointment cancelled successfully!</div>";
        $_SESSION['success'] = "Appointment cancelled!";
    } else {
        $appointment_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// BOOK APPOINTMENT
// ===========================
$book_msg = "";
if(isset($_POST['book_appointment'])){
    $doctor = mysqli_real_escape_string($con, $_POST['doctor']);
    $appdate = mysqli_real_escape_string($con, $_POST['appdate']);
    $apptime = mysqli_real_escape_string($con, $_POST['apptime']);
    $reason = mysqli_real_escape_string($con, $_POST['reason'] ?? '');
    
    $check_appointment = mysqli_query($con, "SELECT * FROM appointmenttb WHERE doctor='$doctor' AND appdate='$appdate' AND apptime='$apptime'");
    
    if(mysqli_num_rows($check_appointment) > 0){
        $book_msg = "<div class='alert alert-warning'>⚠️ This time slot is already booked. Please choose another time.</div>";
    } else {
        $doctor_query = mysqli_query($con, "SELECT * FROM doctb WHERE username='$doctor'");
        if(mysqli_num_rows($doctor_query) > 0){
            $doctor_data = mysqli_fetch_assoc($doctor_query);
            $docFees = $doctor_data['docFees'];
            
            $query = "INSERT INTO appointmenttb (pid, national_id, fname, lname, gender, email, contact, doctor, docFees, appdate, apptime, appointment_reason) 
                      VALUES ('{$patient['pid']}', '{$patient['national_id']}', '{$patient['fname']}', '{$patient['lname']}', 
                              '{$patient['gender']}', '{$patient['email']}', '{$patient['contact']}', 
                              '$doctor', '$docFees', '$appdate', '$apptime', '$reason')";
            
            if(mysqli_query($con, $query)){
                $appointment_id = mysqli_insert_id($con);
                
                $payment_query = "INSERT INTO paymenttb (pid, appointment_id, national_id, patient_name, doctor, fees, pay_date) 
                                  VALUES ('{$patient['pid']}', '$appointment_id', '{$patient['national_id']}', 
                                          '{$patient['fname']} {$patient['lname']}', '$doctor', '$docFees', '$appdate')";
                mysqli_query($con, $payment_query);
                
                $book_msg = "<div class='alert alert-success'>✅ Appointment booked successfully!<br>
                            Appointment ID: APT$appointment_id<br>
                            Date: $appdate at $apptime<br>
                            Doctor: Dr. $doctor<br>
                            Please arrive 15 minutes before your appointment time.</div>";
                $_SESSION['success'] = "Appointment booked successfully!";
            } else {
                $book_msg = "<div class='alert alert-danger'>❌ Error booking appointment: " . mysqli_error($con) . "</div>";
            }
        } else {
            $book_msg = "<div class='alert alert-danger'>❌ Doctor not found!</div>";
        }
    }
}

// ===========================
// MAKE PAYMENT
// ===========================
$payment_msg = "";
if(isset($_POST['make_payment'])){
    $payment_id = mysqli_real_escape_string($con, $_POST['payment_id']);
    $method = mysqli_real_escape_string($con, $_POST['method']);
    
    $receipt_no = 'REC' . date('Ymd') . str_pad($payment_id, 3, '0', STR_PAD_LEFT);
    
    $query = "UPDATE paymenttb SET 
              pay_status='Paid',
              payment_method='$method',
              receipt_no='$receipt_no'
              WHERE id='$payment_id' AND national_id='$national_id'";
    
    if(mysqli_query($con, $query)){
        $payment_msg = "<div class='alert alert-success'>✅ Payment completed successfully! Receipt No: $receipt_no</div>";
        $_SESSION['success'] = "Payment completed!";
    } else {
        $payment_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// SEND FEEDBACK
// ===========================
$feedback_msg = "";
if(isset($_POST['send_feedback'])){
    $feedback = mysqli_real_escape_string($con, $_POST['feedback']);
    $rating = mysqli_real_escape_string($con, $_POST['rating']);
    
    $query = "INSERT INTO feedbacktb (patient_id, patient_name, email, feedback, rating, created_date) 
              VALUES ('$patient_id', '$patient_name', '$email', '$feedback', '$rating', NOW())";
    
    if(mysqli_query($con, $query)){
        $feedback_msg = "<div class='alert alert-success'>✅ Thank you for your feedback!</div>";
        $_SESSION['success'] = "Feedback submitted!";
    } else {
        $feedback_msg = "<div class='alert alert-danger'>❌ Error submitting feedback: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// CHECK SESSION MESSAGES
// ===========================
if(isset($_SESSION['success'])){
    $success_msg = $_SESSION['success'];
    unset($_SESSION['success']);
} else {
    $success_msg = "";
}

if(isset($_SESSION['error'])){
    $error_msg = $_SESSION['error'];
    unset($_SESSION['error']);
} else {
    $error_msg = "";
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Healthcare Hospital</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <style>
        body { 
            background: #f8f9fa; 
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #0077b6 0%, #0096c7 100%);
            color: white;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,.1);
            z-index: 1000;
        }
        .sidebar .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            object-fit: contain;
            margin: 0 auto 20px;
            display: block;
            padding: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .sidebar h4 { 
            text-align: center; 
            font-weight: 700; 
            font-size: 22px; 
            margin-bottom: 30px; 
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }
        .sidebar ul { 
            list-style: none; 
            padding-left: 0; 
        }
        .sidebar ul li {
            padding: 12px 20px;
            cursor: pointer;
            transition: all .3s;
            border-left: 4px solid transparent;
            font-size: 15px;
        }
        .sidebar ul li:hover, 
        .sidebar ul li.active {
            background: rgba(255,255,255,0.1);
            border-left: 4px solid #fff;
        }
        .sidebar ul li i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }
        .main-content { 
            margin-left: 250px; 
            width: calc(100% - 250px); 
        }
        
        .topbar {
            background: linear-gradient(90deg, #0077b6 0%, #0096c7 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .brand { 
            font-weight: 700; 
            font-size: 24px; 
            letter-spacing: 1px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            color: #0077b6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .stats-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
            overflow: hidden;
            position: relative;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .stats-icon {
            font-size: 40px;
            opacity: 0.8;
            position: relative;
            z-index: 2;
        }
        .stats-number {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 10px 0;
            position: relative;
            z-index: 2;
        }
        .stats-label {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            position: relative;
            z-index: 2;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #0077b6 0%, #0096c7 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .welcome-banner:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }
        .welcome-banner:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(-50%, 50%);
        }

        .image-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
            border: 2px solid transparent;
        }
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #0077b6;
        }
        .card-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .card-overlay-text {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 119, 182, 0.85);
            color: white;
            padding: 10px 15px;
        }
        .card-content {
            padding: 20px;
        }
        
        .data-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .table-header {
            background: #0077b6;
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }

        .tab-content {
            padding: 30px;
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }

        .form-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .form-card-header {
            background: #0077b6;
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -25px -25px 20px -25px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar h4, .sidebar ul li span {
                display: none;
            }
            .sidebar ul li i {
                margin-right: 0;
                font-size: 20px;
            }
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .btn {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .emergency-contact {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        /* Logout button styling */
        .logout-item {
            position: absolute;
            bottom: 20px;
            width: 100%;
        }
        
        .logout-btn {
            background: rgba(255, 0, 0, 0.1);
            border-left: 4px solid #ff4444 !important;
        }
        
        .logout-btn:hover {
            background: rgba(255, 0, 0, 0.2);
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img class="logo" src="images/medical-logo.jpg" alt="Hospital Logo">
        <h4>Patient Portal</h4>
        <ul>
            <li data-target="dashboard-tab" class="active">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </li>
            <li data-target="appointments-tab">
                <i class="fas fa-calendar-check"></i> <span>Appointments</span>
            </li>
            <li data-target="prescriptions-tab">
                <i class="fas fa-prescription"></i> <span>Prescriptions</span>
            </li>
            <li data-target="payments-tab">
                <i class="fas fa-credit-card"></i> <span>Payments</span>
            </li>
            <li data-target="doctors-tab">
                <i class="fas fa-user-md"></i> <span>Our Doctors</span>
            </li>
            <li data-target="profile-tab">
                <i class="fas fa-user-cog"></i> <span>Profile</span>
            </li>
            <li data-target="feedback-tab">
                <i class="fas fa-comment-dots"></i> <span>Feedback</span>
            </li>
            <!-- Logout Link with confirmation -->
            <li class="logout-item">
                <a href="logout.php" onclick="return confirmLogout()" style="color: white; text-decoration: none; display: block;" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="brand">🏥 Healthcare Hospital - Patient Portal</div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($patient['fname'], 0, 1)); ?>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($patient_name); ?></strong><br>
                    <small>Patient ID: <?php echo htmlspecialchars($patient['pid']); ?></small>
                </div>
                <button class="btn btn-sm btn-outline-light ml-3" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard-tab">
                <?php if($success_msg): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_msg; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if($error_msg): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_msg; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2>Welcome back, <?php echo htmlspecialchars($patient['fname']); ?>!</h2>
                            <p class="mb-0">Your health is our priority. Access all your medical services in one place.</p>
                            <small><i class="fas fa-clock mr-1"></i> Session active since: <?php echo date('H:i:s', $_SESSION['last_activity'] ?? time()); ?></small>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="user-avatar" style="width: 80px; height: 80px; font-size: 36px; margin-left: auto;">
                                <?php echo strtoupper(substr($patient['fname'], 0, 1)); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-label">TOTAL APPOINTMENTS</div>
                                        <div class="stats-number text-primary">
                                            <?php echo $total_appointments; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-alt stats-icon text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-label">CONFIRMED APPOINTMENTS</div>
                                        <div class="stats-number text-success">
                                            <?php echo $confirmed_appointments; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle stats-icon text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-label">PENDING APPOINTMENTS</div>
                                        <div class="stats-number text-warning">
                                            <?php echo $pending_appointments; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock stats-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-label">PRESCRIPTIONS</div>
                                        <div class="stats-number text-info">
                                            <?php echo $total_prescriptions; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-prescription-bottle-alt stats-icon text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Services -->
                <div class="row mt-4">
                    <div class="col-12 mb-3">
                        <h4>Quick Services</h4>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="image-card" onclick="showTab('appointments-tab')">
                            <div class="card-image" style="background: linear-gradient(135deg, #0077b6, #0096c7);">
                                <div class="card-overlay-text">
                                    <h5 class="mb-0"><i class="fas fa-calendar-plus mr-2"></i>Book Appointment</h5>
                                </div>
                            </div>
                            <div class="card-content">
                                <p>Schedule a new appointment with our specialists</p>
                                <button class="btn btn-primary btn-sm">Book Now</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="image-card" onclick="showTab('prescriptions-tab')">
                            <div class="card-image" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                <div class="card-overlay-text">
                                    <h5 class="mb-0"><i class="fas fa-prescription mr-2"></i>View Prescriptions</h5>
                                </div>
                            </div>
                            <div class="card-content">
                                <p>Check your medical prescriptions online</p>
                                <button class="btn btn-info btn-sm">View All</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="image-card" onclick="showTab('payments-tab')">
                            <div class="card-image" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                                <div class="card-overlay-text">
                                    <h5 class="mb-0"><i class="fas fa-credit-card mr-2"></i>Make Payment</h5>
                                </div>
                            </div>
                            <div class="card-content">
                                <p>Pay your medical bills securely online</p>
                                <button class="btn btn-success btn-sm">Pay Now</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="image-card" onclick="showTab('doctors-tab')">
                            <div class="card-image" style="background: linear-gradient(135deg, #6f42c1, #6610f2);">
                                <div class="card-overlay-text">
                                    <h5 class="mb-0"><i class="fas fa-user-md mr-2"></i>Our Doctors</h5>
                                </div>
                            </div>
                            <div class="card-content">
                                <p>Meet our team of experienced specialists</p>
                                <button class="btn btn-secondary btn-sm">View Doctors</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="table-header">
                                <h5 class="mb-0"><i class="fas fa-calendar-day mr-2"></i>Today's Appointments</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Time</th>
                                            <th>Doctor</th>
                                            <th>Specialization</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $today_query = mysqli_query($con, "SELECT a.*, d.spec FROM appointmenttb a LEFT JOIN doctb d ON a.doctor = d.username WHERE a.email='$email' AND a.appdate = CURDATE() ORDER BY a.apptime");
                                        if(mysqli_num_rows($today_query) > 0):
                                            while($row = mysqli_fetch_assoc($today_query)):
                                        ?>
                                        <tr>
                                            <td><?php echo date('h:i A', strtotime($row['apptime'])); ?></td>
                                            <td>Dr. <?php echo htmlspecialchars($row['doctor']); ?></td>
                                            <td><?php echo htmlspecialchars($row['spec']); ?></td>
                                            <td>
                                                <?php if($row['doctorStatus'] == 1): ?>
                                                    <span class="status-badge status-active">Confirmed</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-pending">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($row['appointmentStatus'] == 'active' && $row['doctorStatus'] == 1): ?>
                                                    <button class="btn btn-sm btn-danger action-btn" onclick="cancelAppointment(<?php echo $row['ID']; ?>)">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No appointments for today</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="emergency-contact">
                    <h4><i class="fas fa-phone-alt mr-2"></i>Emergency Contact</h4>
                    <h2>011-234-5678</h2>
                    <p>Available 24/7 for emergencies</p>
                </div>
            </div>

            <!-- Appointments Tab -->
            <div class="tab-pane fade" id="appointments-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-calendar-check mr-2"></i>My Appointments</h3>
                    <button class="btn btn-primary" data-toggle="collapse" data-target="#bookAppointmentForm">
                        <i class="fas fa-plus mr-2"></i>Book New Appointment
                    </button>
                </div>
                
                <?php if($appointment_msg): echo $appointment_msg; endif; ?>
                <?php if($book_msg): echo $book_msg; endif; ?>
                
                <!-- Book Appointment Form -->
                <div class="form-card mb-4 collapse show" id="bookAppointmentForm">
                    <div class="form-card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-plus mr-2"></i>Book New Appointment</h5>
                    </div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Select Doctor *</label>
                                    <select class="form-control" name="doctor" required>
                                        <option value="">Select Doctor</option>
                                        <?php foreach($doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['username']; ?>">
                                            Dr. <?php echo $doctor['username']; ?> (<?php echo $doctor['spec']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Reason for Visit</label>
                                    <input type="text" class="form-control" name="reason" placeholder="Optional - e.g., Routine Checkup">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Appointment Date *</label>
                                    <input type="date" class="form-control" name="appdate" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Appointment Time *</label>
                                    <input type="time" class="form-control" name="apptime" required>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            Please arrive 15 minutes before your appointment time. Bring your medical records if any.
                        </div>
                        <button type="submit" name="book_appointment" class="btn btn-success btn-block">
                            <i class="fas fa-calendar-plus mr-2"></i>Book Appointment
                        </button>
                    </form>
                </div>
                
                <!-- Appointments Table -->
                <div class="data-table">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-list mr-2"></i>All Appointments</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Fees (Rs.)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($appointments) > 0): ?>
                                    <?php foreach($appointments as $app): ?>
                                    <tr>
                                        <td>APT<?php echo str_pad($app['ID'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($app['appdate'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($app['apptime'])); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($app['doctor']); ?></td>
                                        <td><?php echo htmlspecialchars($app['spec']); ?></td>
                                        <td>Rs. <?php echo number_format($app['docFees'], 2); ?></td>
                                        <td>
                                            <?php if($app['appointmentStatus'] == 'cancelled'): ?>
                                                <span class="status-badge status-cancelled">Cancelled</span>
                                            <?php elseif($app['doctorStatus'] == 1): ?>
                                                <span class="status-badge status-active">Confirmed</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($app['appointmentStatus'] == 'active' && $app['doctorStatus'] == 1): ?>
                                                <button class="btn btn-sm btn-danger action-btn" onclick="cancelAppointment(<?php echo $app['ID']; ?>)">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No appointments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Prescriptions Tab -->
            <div class="tab-pane fade" id="prescriptions-tab">
                <h3 class="mb-4"><i class="fas fa-prescription mr-2"></i>My Prescriptions</h3>
                
                <div class="data-table">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-list mr-2"></i>All Prescriptions</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Disease</th>
                                    <th>Allergy</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($prescriptions) > 0): ?>
                                    <?php foreach($prescriptions as $pres): ?>
                                    <tr>
                                        <td>PRE<?php echo str_pad($pres['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($pres['appdate'])); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($pres['doctor']); ?></td>
                                        <td><?php echo htmlspecialchars($pres['disease']); ?></td>
                                        <td><?php echo htmlspecialchars($pres['allergy']); ?></td>
                                        <td>
                                            <?php if($pres['emailStatus'] == 'Sent'): ?>
                                                <span class="status-badge status-active">Sent</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Not Sent</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No prescriptions found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payments Tab -->
            <div class="tab-pane fade" id="payments-tab">
                <h3 class="mb-4"><i class="fas fa-credit-card mr-2"></i>Payment History</h3>
                
                <?php if($payment_msg): echo $payment_msg; endif; ?>
                
                <div class="data-table">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-list mr-2"></i>All Payments</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Amount (Rs.)</th>
                                    <th>Method</th>
                                    <th>Receipt No</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($payments) > 0): ?>
                                    <?php foreach($payments as $pay): ?>
                                    <tr>
                                        <td>PAY<?php echo str_pad($pay['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($pay['pay_date'])); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($pay['doctor']); ?></td>
                                        <td>Rs. <?php echo number_format($pay['fees'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($pay['payment_method'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($pay['receipt_no'] ?: 'N/A'); ?></td>
                                        <td>
                                            <?php if($pay['pay_status'] == 'Paid'): ?>
                                                <span class="status-badge status-paid">Paid</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($pay['pay_status'] == 'Pending'): ?>
                                                <button class="btn btn-sm btn-success action-btn" onclick="makePayment(<?php echo $pay['id']; ?>)">
                                                    <i class="fas fa-money-bill-wave"></i> Pay Now
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No payments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Doctors Tab -->
            <div class="tab-pane fade" id="doctors-tab">
                <h3 class="mb-4"><i class="fas fa-user-md mr-2"></i>Our Doctors</h3>
                
                <div class="row">
                    <?php if(count($doctors) > 0): ?>
                        <?php foreach($doctors as $doc): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card doctor-card">
                                <div class="card-body text-center">
                                    <div class="doctor-avatar mb-3">
                                        <?php echo strtoupper(substr($doc['username'], 0, 1)); ?>
                                    </div>
                                    <h5>Dr. <?php echo htmlspecialchars($doc['username']); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($doc['spec']); ?></p>
                                    <div class="doctor-info">
                                        <p><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($doc['email']); ?></p>
                                        <p><i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($doc['contact']); ?></p>
                                        <p class="text-primary font-weight-bold">
                                            <i class="fas fa-money-bill-wave mr-2"></i>
                                            Fee: Rs. <?php echo number_format($doc['docFees'], 2); ?>
                                        </p>
                                    </div>
                                    <button class="btn btn-primary btn-block mt-3" onclick="bookDoctor('<?php echo $doc['username']; ?>')">
                                        <i class="fas fa-calendar-plus mr-2"></i>Book Appointment
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                No doctors available at the moment.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Tab -->
            <div class="tab-pane fade" id="profile-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-user-cog mr-2"></i>My Profile</h3>
                </div>
                
                <?php if($profile_msg): echo $profile_msg; endif; ?>
                <?php if($password_msg): echo $password_msg; endif; ?>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-user-circle mr-2"></i>Profile Information</h5>
                            </div>
                            <div class="text-center mb-4">
                                <div class="user-avatar mx-auto" style="width: 100px; height: 100px; font-size: 48px;">
                                    <?php echo strtoupper(substr($patient['fname'], 0, 1)); ?>
                                </div>
                                <h4 class="mt-3"><?php echo htmlspecialchars($patient_name); ?></h4>
                                <p class="text-muted">Patient ID: <?php echo htmlspecialchars($patient['pid']); ?></p>
                            </div>
                            
                            <div class="patient-info">
                                <div class="mb-3">
                                    <label class="font-weight-bold">Email Address</label>
                                    <p><?php echo htmlspecialchars($patient['email']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold">National ID</label>
                                    <p><?php echo htmlspecialchars($patient['national_id']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold">Date of Birth</label>
                                    <p><?php echo $patient['dob'] ? date('d M Y', strtotime($patient['dob'])) : 'Not specified'; ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold">Gender</label>
                                    <p><?php echo htmlspecialchars($patient['gender']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <!-- Update Profile Form -->
                        <div class="form-card mb-4">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-user-edit mr-2"></i>Update Contact Information</h5>
                            </div>
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Contact Number *</label>
                                            <input type="tel" class="form-control" name="contact" value="<?php echo htmlspecialchars($patient['contact']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Emergency Contact</label>
                                            <input type="tel" class="form-control" name="emergencyContact" value="<?php echo htmlspecialchars($patient['emergencyContact'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                        
                        <!-- Change Password Form -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-key mr-2"></i>Change Password</h5>
                            </div>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Current Password *</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>New Password *</label>
                                            <input type="password" class="form-control" name="new_password" required minlength="6">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Confirm New Password *</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary btn-block">
                                    <i class="fas fa-save mr-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Tab -->
            <div class="tab-pane fade" id="feedback-tab">
                <h3 class="mb-4"><i class="fas fa-comment-dots mr-2"></i>Send Feedback</h3>
                
                <?php if($feedback_msg): echo $feedback_msg; endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-star mr-2"></i>Share Your Experience</h5>
                            </div>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Your Feedback *</label>
                                    <textarea class="form-control" name="feedback" rows="4" required placeholder="Share your experience with us..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Rate Your Experience *</label>
                                    <div class="rating-stars" id="rating-stars">
                                        <span class="star" data-value="1"><i class="far fa-star"></i></span>
                                        <span class="star" data-value="2"><i class="far fa-star"></i></span>
                                        <span class="star" data-value="3"><i class="far fa-star"></i></span>
                                        <span class="star" data-value="4"><i class="far fa-star"></i></span>
                                        <span class="star" data-value="5"><i class="far fa-star"></i></span>
                                    </div>
                                    <input type="hidden" name="rating" id="rating-value" value="0" required>
                                </div>
                                <button type="submit" name="send_feedback" class="btn btn-primary">
                                    <i class="fas fa-paper-plane mr-2"></i>Submit Feedback
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Why Feedback Matters</h5>
                            </div>
                            <div class="mt-3">
                                <p><i class="fas fa-check text-success mr-2"></i> Helps us improve services</p>
                                <p><i class="fas fa-check text-success mr-2"></i> Better patient experience</p>
                                <p><i class="fas fa-check text-success mr-2"></i> Quality healthcare</p>
                                <p><i class="fas fa-check text-success mr-2"></i> Personalized service</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Make Payment Modal -->
    <div class="modal fade" id="makePaymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Make Payment</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="payment-form">
                        <input type="hidden" name="payment_id" id="payment_id">
                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select class="form-control" name="method" required>
                                <option value="Cash">Cash</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Debit Card">Debit Card</option>
                                <option value="Online Banking">Online Banking</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            You will receive a receipt number after payment confirmation.
                        </div>
                        <button type="submit" name="make_payment" class="btn btn-success btn-block">
                            <i class="fas fa-money-check-alt mr-2"></i>Confirm Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-sign-out-alt mr-2"></i>Confirm Logout</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to logout?</p>
                    <p class="text-muted"><small>You will need to login again to access your dashboard.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-danger">Yes, Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize on page load
        $(document).ready(function() {
            // Set up sidebar navigation
            $('.sidebar ul li[data-target]').click(function() {
                const target = $(this).data('target');
                showTab(target);
                
                // Update active state
                $('.sidebar ul li').removeClass('active');
                $(this).addClass('active');
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Set default date for appointment booking
            $('input[name="appdate"]').val(new Date().toISOString().split('T')[0]);
            
            // Set default time (next hour)
            const nextHour = new Date();
            nextHour.setHours(nextHour.getHours() + 1);
            nextHour.setMinutes(0);
            $('input[name="apptime"]').val(nextHour.toTimeString().slice(0,5));
            
            // Rating stars functionality
            $('.rating-stars .star').click(function() {
                const value = $(this).data('value');
                currentRating = value;
                $('#rating-value').val(value);
                
                // Update stars
                $('.rating-stars .star').each(function() {
                    const starValue = $(this).data('value');
                    if (starValue <= value) {
                        $(this).html('<i class="fas fa-star"></i>');
                        $(this).css('color', '#ffc107');
                    } else {
                        $(this).html('<i class="far fa-star"></i>');
                        $(this).css('color', '#ffc107');
                    }
                });
            });
            
            // Session timeout warning
            let warningTimeout;
            function startSessionTimer() {
                // Show warning 2 minutes before timeout (28 minutes)
                warningTimeout = setTimeout(function() {
                    if(confirm('Your session will expire in 2 minutes. Do you want to extend your session?')) {
                        // AJAX call to extend session
                        $.ajax({
                            url: 'extend_session.php',
                            method: 'POST',
                            success: function() {
                                alert('Session extended!');
                                startSessionTimer();
                            }
                        });
                    }
                }, 1680000); // 28 minutes
            }
            
            // Start session timer
            startSessionTimer();
            
            // Clear timer on page unload
            $(window).on('beforeunload', function() {
                clearTimeout(warningTimeout);
            });
        });
        
        // Function to show tab
        function showTab(tabId) {
            // Hide all tab panes
            $('.tab-pane').removeClass('show active');
            
            // Show selected tab
            $('#' + tabId).addClass('show active');
            
            // Update URL hash
            window.location.hash = tabId;
        }
        
        // Function to cancel appointment
        function cancelAppointment(appointmentId) {
            if(confirm('Are you sure you want to cancel this appointment?')) {
                const form = $('<form>').attr({
                    method: 'POST',
                    style: 'display: none;'
                });
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'appointment_id',
                    value: appointmentId
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'cancel_appointment',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Function to make payment
        function makePayment(paymentId) {
            $('#payment_id').val(paymentId);
            $('#makePaymentModal').modal('show');
        }
        
        // Function to book appointment with specific doctor
        function bookDoctor(doctorName) {
            showTab('appointments-tab');
            $('#bookAppointmentForm').collapse('show');
            $('select[name="doctor"]').val(doctorName);
            $('html, body').animate({
                scrollTop: $('#bookAppointmentForm').offset().top - 100
            }, 500);
        }
        
        // Function to confirm logout
        function confirmLogout() {
            $('#logoutModal').modal('show');
            return false;
        }
        
        // Prevent back button after logout
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function() {
            history.pushState(null, null, document.URL);
        });
        
        // Form validation
        $(document).ready(function() {
            $('form[name="change_password"]').submit(function(e) {
                const newPassword = $('input[name="new_password"]').val();
                const confirmPassword = $('input[name="confirm_password"]').val();
                
                if(newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match!');
                    return false;
                }
                
                if(newPassword.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long!');
                    return false;
                }
                
                return true;
            });
            
            // Feedback form validation
            $('form[name="send_feedback"]').submit(function(e) {
                const rating = $('#rating-value').val();
                const feedback = $('textarea[name="feedback"]').val();
                
                if(rating == 0) {
                    e.preventDefault();
                    alert('Please select a rating!');
                    return false;
                }
                
                if(feedback.trim().length < 10) {
                    e.preventDefault();
                    alert('Please provide meaningful feedback (at least 10 characters)!');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($con); ?>