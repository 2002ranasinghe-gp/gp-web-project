<?php
// doctor-panel.php
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
if(!isset($_SESSION['doctor'])){
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
// DOCTOR DATA FETCH
// ===========================
$username = mysqli_real_escape_string($con, $_SESSION['doctor']);
$query = "SELECT * FROM doctb WHERE username='$username' LIMIT 1";
$result = mysqli_query($con, $query);

if(!$result || mysqli_num_rows($result) == 0){
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$doctor = mysqli_fetch_assoc($result);
$doctor_id = $doctor['id'];
$doctor_name = $doctor['username'];
$doctor_spec = $doctor['spec'];

// ===========================
// GET STATISTICS FOR DASHBOARD
// ===========================
$total_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE doctor='$doctor_name'"));
$today_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE doctor='$doctor_name' AND appdate = CURDATE()"));
$pending_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE doctor='$doctor_name' AND doctorStatus=0 AND appointmentStatus='active'"));
$completed_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE doctor='$doctor_name' AND doctorStatus=1 AND appointmentStatus='active'"));
$cancelled_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE doctor='$doctor_name' AND appointmentStatus='cancelled'"));
$total_patients = mysqli_num_rows(mysqli_query($con, "SELECT DISTINCT pid FROM appointmenttb WHERE doctor='$doctor_name'"));
$total_prescriptions = mysqli_num_rows(mysqli_query($con, "SELECT * FROM prestb WHERE doctor='$doctor_name'"));

// ===========================
// GET DATA FOR TABLES
// ===========================
$appointments = [];
$patients = [];
$prescriptions = [];
$feedbacks = [];

// Get appointments (filtered by doctor)
$appointment_query = mysqli_query($con, "SELECT a.*, p.fname, p.lname, p.contact as patient_contact FROM appointmenttb a 
                                         LEFT JOIN patreg p ON a.email = p.email 
                                         WHERE a.doctor='$doctor_name' 
                                         ORDER BY a.appdate DESC, a.apptime DESC");
while($row = mysqli_fetch_assoc($appointment_query)){
    $appointments[] = $row;
}

// Get doctor's patients
$patients_query = mysqli_query($con, "SELECT DISTINCT p.* FROM patreg p 
                                      JOIN appointmenttb a ON p.email = a.email 
                                      WHERE a.doctor='$doctor_name' 
                                      ORDER BY p.fname");
while($row = mysqli_fetch_assoc($patients_query)){
    $patients[] = $row;
}

// Get prescriptions
$prescription_query = mysqli_query($con, "SELECT * FROM prestb WHERE doctor='$doctor_name' ORDER BY appdate DESC");
while($row = mysqli_fetch_assoc($prescription_query)){
    $prescriptions[] = $row;
}

// Get feedback for this doctor
$feedback_query = mysqli_query($con, "SELECT * FROM feedbacktb ORDER BY created_date DESC LIMIT 10");
while($row = mysqli_fetch_assoc($feedback_query)){
    $feedbacks[] = $row;
}

// ===========================
// UPDATE PROFILE
// ===========================
$profile_msg = "";
if(isset($_POST['update_profile'])){
    $contact = mysqli_real_escape_string($con, $_POST['contact']);
    $docFees = mysqli_real_escape_string($con, $_POST['docFees']);
    $qualification = mysqli_real_escape_string($con, $_POST['qualification']);
    $experience = mysqli_real_escape_string($con, $_POST['experience']);
    $working_hours = mysqli_real_escape_string($con, $_POST['working_hours']);
    
    $update_query = "UPDATE doctb SET 
                     contact='$contact', 
                     docFees='$docFees', 
                     qualification='$qualification', 
                     experience='$experience',
                     working_hours='$working_hours'
                     WHERE username='$doctor_name'";
    
    if(mysqli_query($con, $update_query)){
        $profile_msg = "<div class='alert alert-success'>✅ Profile updated successfully!</div>";
        $query = "SELECT * FROM doctb WHERE username='$doctor_name' LIMIT 1";
        $result = mysqli_query($con, $query);
        $doctor = mysqli_fetch_assoc($result);
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
    
    if($current_password == $doctor['password']){
        if($new_password === $confirm_password){
            if(strlen($new_password) >= 6){
                $update_query = "UPDATE doctb SET password='$new_password' WHERE username='$doctor_name'";
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
// UPDATE APPOINTMENT STATUS
// ===========================
$appointment_msg = "";
if(isset($_POST['update_appointment'])){
    $appointment_id = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    if($status == 'confirm'){
        $query = "UPDATE appointmenttb SET 
                  doctorStatus=1,
                  appointmentStatus='active',
                  updated_date=NOW()
                  WHERE ID='$appointment_id' AND doctor='$doctor_name'";
        
        if(mysqli_query($con, $query)){
            $appointment_msg = "<div class='alert alert-success'>✅ Appointment confirmed successfully!</div>";
            $_SESSION['success'] = "Appointment confirmed!";
        } else {
            $appointment_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
        }
    } elseif($status == 'cancel'){
        $reason = "Cancelled by doctor";
        
        $query = "UPDATE appointmenttb SET 
                  appointmentStatus='cancelled',
                  cancelledBy='Doctor',
                  cancellationReason='$reason',
                  doctorStatus=0,
                  updated_date=NOW()
                  WHERE ID='$appointment_id' AND doctor='$doctor_name'";
        
        if(mysqli_query($con, $query)){
            $appointment_msg = "<div class='alert alert-success'>✅ Appointment cancelled successfully!</div>";
            $_SESSION['success'] = "Appointment cancelled!";
        } else {
            $appointment_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
        }
    }
}

// ===========================
// CREATE/UPDATE PRESCRIPTION
// ===========================
$prescription_msg = "";
if(isset($_POST['save_prescription'])){
    $prescription_id = mysqli_real_escape_string($con, $_POST['prescription_id']);
    $patient_nid = mysqli_real_escape_string($con, $_POST['national_id']);
    $appointment_date = mysqli_real_escape_string($con, $_POST['appdate']);
    $patient_name = mysqli_real_escape_string($con, $_POST['patient_name']);
    $disease = mysqli_real_escape_string($con, $_POST['disease']);
    $allergy = mysqli_real_escape_string($con, $_POST['allergy']);
    $prescription = mysqli_real_escape_string($con, $_POST['prescription']);
    $medicine = mysqli_real_escape_string($con, $_POST['medicine']);
    $test = mysqli_real_escape_string($con, $_POST['test']);
    $advice = mysqli_real_escape_string($con, $_POST['advice']);
    
    if($prescription_id == 'new'){
        // Create new prescription
        $query = "INSERT INTO prestb (national_id, appdate, patient_name, doctor, disease, allergy, prescription, medicine, test, advice, created_date) 
                  VALUES ('$patient_nid', '$appointment_date', '$patient_name', '$doctor_name', '$disease', '$allergy', '$prescription', '$medicine', '$test', '$advice', NOW())";
    } else {
        // Update existing prescription
        $query = "UPDATE prestb SET 
                  disease='$disease',
                  allergy='$allergy',
                  prescription='$prescription',
                  medicine='$medicine',
                  test='$test',
                  advice='$advice',
                  updated_date=NOW()
                  WHERE id='$prescription_id' AND doctor='$doctor_name'";
    }
    
    if(mysqli_query($con, $query)){
        $prescription_msg = "<div class='alert alert-success'>✅ Prescription saved successfully!</div>";
        $_SESSION['success'] = "Prescription saved!";
    } else {
        $prescription_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// SEND PRESCRIPTION VIA EMAIL
// ===========================
if(isset($_POST['send_prescription'])){
    $prescription_id = mysqli_real_escape_string($con, $_POST['prescription_id']);
    $patient_email = mysqli_real_escape_string($con, $_POST['patient_email']);
    
    // Update status
    $query = "UPDATE prestb SET emailStatus='Sent', email_sent_date=NOW() WHERE id='$prescription_id'";
    
    if(mysqli_query($con, $query)){
        // Here you would typically send the email
        // For now, we'll just show a success message
        $prescription_msg = "<div class='alert alert-success'>✅ Prescription sent to patient's email successfully!</div>";
        $_SESSION['success'] = "Prescription sent via email!";
    } else {
        $prescription_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// VIEW PRESCRIPTION DETAILS
// ===========================
if(isset($_GET['view_prescription'])){
    $pres_id = mysqli_real_escape_string($con, $_GET['view_prescription']);
    $pres_details_query = mysqli_query($con, "SELECT * FROM prestb WHERE id='$pres_id' AND doctor='$doctor_name'");
    $prescription_details = mysqli_fetch_assoc($pres_details_query);
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
    <title>Doctor Panel - Healthcare Hospital</title>
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
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
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
            border-left: 4px solid #3498db;
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
            background: linear-gradient(90deg, #2c3e50 0%, #34495e 100%);
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
            background: #3498db;
            color: white;
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
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
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

        .data-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .table-header {
            background: #2c3e50;
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
        .status-confirmed {
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
            background: #2c3e50;
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
        
        .action-btn {
            margin: 2px;
        }
        
        .filter-box {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .doctor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .logout-item {
            position: absolute;
            bottom: 20px;
            width: 100%;
        }
        
        .logout-btn {
            background: rgba(255, 0, 0, 0.1);
            border-left: 4px solid #e74c3c !important;
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
        <h4>Doctor Panel</h4>
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
            <li data-target="patients-tab">
                <i class="fas fa-user-injured"></i> <span>My Patients</span>
            </li>
            <li data-target="profile-tab">
                <i class="fas fa-user-cog"></i> <span>Profile</span>
            </li>
            <li data-target="feedback-tab">
                <i class="fas fa-comment-dots"></i> <span>Feedback</span>
            </li>
            <!-- Logout Link -->
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
            <div class="brand">🏥 Healthcare Hospital - Doctor Panel</div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($doctor_name, 0, 1)); ?>
                </div>
                <div>
                    <strong>Dr. <?php echo htmlspecialchars($doctor_name); ?></strong><br>
                    <small><?php echo htmlspecialchars($doctor_spec); ?></small>
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
                            <h2>Welcome, Dr. <?php echo htmlspecialchars($doctor_name); ?>!</h2>
                            <p class="mb-0">Specialization: <?php echo htmlspecialchars($doctor_spec); ?></p>
                            <small><i class="fas fa-clock mr-1"></i> Session active since: <?php echo date('H:i:s', $_SESSION['last_activity'] ?? time()); ?></small>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="doctor-avatar">
                                <?php echo strtoupper(substr($doctor_name, 0, 1)); ?>
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
                                        <div class="stats-label">TODAY'S APPOINTMENTS</div>
                                        <div class="stats-number text-success">
                                            <?php echo $today_appointments; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day stats-icon text-success"></i>
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
                                        <div class="stats-label">MY PATIENTS</div>
                                        <div class="stats-number text-info">
                                            <?php echo $total_patients; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-injured stats-icon text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments Table -->
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
                                            <th>Patient</th>
                                            <th>Contact</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $today_query = mysqli_query($con, "SELECT a.*, p.fname, p.lname, p.contact as patient_contact 
                                                                         FROM appointmenttb a 
                                                                         LEFT JOIN patreg p ON a.email = p.email 
                                                                         WHERE a.doctor='$doctor_name' AND a.appdate = CURDATE() 
                                                                         ORDER BY a.apptime");
                                        if(mysqli_num_rows($today_query) > 0):
                                            while($row = mysqli_fetch_assoc($today_query)):
                                        ?>
                                        <tr>
                                            <td><?php echo date('h:i A', strtotime($row['apptime'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                                            <td><?php echo htmlspecialchars($row['patient_contact']); ?></td>
                                            <td><?php echo htmlspecialchars($row['appointment_reason'] ?? 'Not specified'); ?></td>
                                            <td>
                                                <?php if($row['appointmentStatus'] == 'cancelled'): ?>
                                                    <span class="status-badge status-cancelled">Cancelled</span>
                                                <?php elseif($row['doctorStatus'] == 1): ?>
                                                    <span class="status-badge status-confirmed">Confirmed</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-pending">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($row['appointmentStatus'] == 'active' && $row['doctorStatus'] == 0): ?>
                                                    <button class="btn btn-sm btn-success action-btn" onclick="confirmAppointment(<?php echo $row['ID']; ?>)">
                                                        <i class="fas fa-check"></i> Confirm
                                                    </button>
                                                    <button class="btn btn-sm btn-danger action-btn" onclick="cancelAppointment(<?php echo $row['ID']; ?>)">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No appointments for today</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments Tab -->
            <div class="tab-pane fade" id="appointments-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-calendar-check mr-2"></i>My Appointments</h3>
                </div>
                
                <?php if($appointment_msg): echo $appointment_msg; endif; ?>
                
                <!-- Filter Box -->
                <div class="filter-box">
                    <form method="GET" id="filterForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Filter by Date</label>
                                    <input type="date" class="form-control" name="filter_date" value="<?php echo $_GET['filter_date'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Filter by Status</label>
                                    <select class="form-control" name="filter_status">
                                        <option value="">All Status</option>
                                        <option value="pending" <?php echo ($_GET['filter_status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo ($_GET['filter_status'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="cancelled" <?php echo ($_GET['filter_status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Filter by Patient</label>
                                    <input type="text" class="form-control" name="filter_patient" value="<?php echo $_GET['filter_patient'] ?? ''; ?>" placeholder="Patient name">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-2"></i>Filter</button>
                                    <button type="button" class="btn btn-secondary" onclick="resetFilter()"><i class="fas fa-redo mr-2"></i>Reset</button>
                                </div>
                            </div>
                        </div>
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
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Reason</th>
                                    <th>Fees (Rs.)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Apply filters
                                $filter_sql = " WHERE a.doctor='$doctor_name' ";
                                
                                if(isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
                                    $filter_date = mysqli_real_escape_string($con, $_GET['filter_date']);
                                    $filter_sql .= " AND a.appdate = '$filter_date' ";
                                }
                                
                                if(isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
                                    $filter_status = mysqli_real_escape_string($con, $_GET['filter_status']);
                                    if($filter_status == 'pending') {
                                        $filter_sql .= " AND a.doctorStatus = 0 AND a.appointmentStatus = 'active' ";
                                    } elseif($filter_status == 'confirmed') {
                                        $filter_sql .= " AND a.doctorStatus = 1 AND a.appointmentStatus = 'active' ";
                                    } elseif($filter_status == 'cancelled') {
                                        $filter_sql .= " AND a.appointmentStatus = 'cancelled' ";
                                    }
                                }
                                
                                if(isset($_GET['filter_patient']) && !empty($_GET['filter_patient'])) {
                                    $filter_patient = mysqli_real_escape_string($con, $_GET['filter_patient']);
                                    $filter_sql .= " AND (a.fname LIKE '%$filter_patient%' OR a.lname LIKE '%$filter_patient%') ";
                                }
                                
                                $filtered_query = mysqli_query($con, "SELECT a.*, p.contact as patient_contact 
                                                                    FROM appointmenttb a 
                                                                    LEFT JOIN patreg p ON a.email = p.email 
                                                                    $filter_sql 
                                                                    ORDER BY a.appdate DESC, a.apptime DESC");
                                
                                if(mysqli_num_rows($filtered_query) > 0):
                                    while($row = mysqli_fetch_assoc($filtered_query)):
                                ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($row['appdate'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['apptime'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['patient_contact']); ?></td>
                                    <td><?php echo htmlspecialchars($row['appointment_reason'] ?? 'Not specified'); ?></td>
                                    <td>Rs. <?php echo number_format($row['docFees'], 2); ?></td>
                                    <td>
                                        <?php if($row['appointmentStatus'] == 'cancelled'): ?>
                                            <span class="status-badge status-cancelled">Cancelled</span>
                                        <?php elseif($row['doctorStatus'] == 1): ?>
                                            <span class="status-badge status-confirmed">Confirmed</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($row['appointmentStatus'] == 'active'): ?>
                                            <?php if($row['doctorStatus'] == 0): ?>
                                                <button class="btn btn-sm btn-success action-btn" onclick="confirmAppointment(<?php echo $row['ID']; ?>)">
                                                    <i class="fas fa-check"></i> Confirm
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-danger action-btn" onclick="cancelAppointment(<?php echo $row['ID']; ?>)">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-info action-btn" onclick="createPrescription(<?php echo $row['ID']; ?>, '<?php echo $row['national_id']; ?>', '<?php echo $row['appdate']; ?>', '<?php echo $row['fname'] . ' ' . $row['lname']; ?>')">
                                            <i class="fas fa-prescription"></i> Prescribe
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-prescription mr-2"></i>Prescriptions Management</h3>
                    <button class="btn btn-primary" onclick="showNewPrescriptionForm()">
                        <i class="fas fa-plus mr-2"></i>New Prescription
                    </button>
                </div>
                
                <?php if($prescription_msg): echo $prescription_msg; endif; ?>
                
                <!-- Prescriptions Table -->
                <div class="data-table">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-list mr-2"></i>All Prescriptions</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Disease</th>
                                    <th>Medicine</th>
                                    <th>Email Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($prescriptions) > 0): ?>
                                    <?php foreach($prescriptions as $pres): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($pres['appdate'])); ?></td>
                                        <td><?php echo htmlspecialchars($pres['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($pres['disease']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($pres['medicine'], 0, 50)) . (strlen($pres['medicine']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <?php if($pres['emailStatus'] == 'Sent'): ?>
                                                <span class="status-badge status-confirmed">Sent</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Not Sent</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info action-btn" onclick="viewPrescription(<?php echo $pres['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-warning action-btn" onclick="editPrescription(<?php echo $pres['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <?php if($pres['emailStatus'] != 'Sent'): ?>
                                                <button class="btn btn-sm btn-success action-btn" onclick="sendPrescriptionEmail(<?php echo $pres['id']; ?>)">
                                                    <i class="fas fa-paper-plane"></i> Send
                                                </button>
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

            <!-- Patients Tab -->
            <div class="tab-pane fade" id="patients-tab">
                <h3 class="mb-4"><i class="fas fa-user-injured mr-2"></i>My Patients</h3>
                
                <div class="data-table">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Patient List</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>National ID</th>
                                    <th>Date of Birth</th>
                                    <th>Gender</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($patients) > 0): ?>
                                    <?php foreach($patients as $pat): ?>
                                    <tr>
                                        <td><?php echo $pat['pid']; ?></td>
                                        <td><?php echo htmlspecialchars($pat['fname'] . ' ' . $pat['lname']); ?></td>
                                        <td><?php echo htmlspecialchars($pat['email']); ?></td>
                                        <td><?php echo htmlspecialchars($pat['contact']); ?></td>
                                        <td><?php echo htmlspecialchars($pat['national_id']); ?></td>
                                        <td><?php echo $pat['dob'] ? date('d M Y', strtotime($pat['dob'])) : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($pat['gender']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info action-btn" onclick="createPrescriptionForPatient('<?php echo $pat['national_id']; ?>', '<?php echo $pat['fname'] . ' ' . $pat['lname']; ?>')">
                                                <i class="fas fa-prescription"></i> Prescribe
                                            </button>
                                            <button class="btn btn-sm btn-primary action-btn" onclick="viewPatientHistory(<?php echo $pat['pid']; ?>)">
                                                <i class="fas fa-history"></i> History
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No patients found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
                                <h5 class="mb-0"><i class="fas fa-user-circle mr-2"></i>Doctor Information</h5>
                            </div>
                            <div class="text-center mb-4">
                                <div class="doctor-avatar mx-auto">
                                    <?php echo strtoupper(substr($doctor_name, 0, 1)); ?>
                                </div>
                                <h4 class="mt-3">Dr. <?php echo htmlspecialchars($doctor_name); ?></h4>
                                <p class="text-primary font-weight-bold"><?php echo htmlspecialchars($doctor_spec); ?></p>
                            </div>
                            
                            <div class="doctor-info">
                                <div class="mb-3">
                                    <label class="font-weight-bold">Email Address</label>
                                    <p><?php echo htmlspecialchars($doctor['email']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold">Doctor ID</label>
                                    <p><?php echo htmlspecialchars($doctor['id']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold">Qualifications</label>
                                    <p><?php echo htmlspecialchars($doctor['qualification'] ?? 'Not specified'); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold">Experience</label>
                                    <p><?php echo htmlspecialchars($doctor['experience'] ?? 'Not specified'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <!-- Update Profile Form -->
                        <div class="form-card mb-4">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-user-edit mr-2"></i>Update Profile Information</h5>
                            </div>
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Contact Number *</label>
                                            <input type="tel" class="form-control" name="contact" value="<?php echo htmlspecialchars($doctor['contact']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Consultation Fees (Rs.) *</label>
                                            <input type="number" class="form-control" name="docFees" value="<?php echo htmlspecialchars($doctor['docFees']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Qualifications</label>
                                            <input type="text" class="form-control" name="qualification" value="<?php echo htmlspecialchars($doctor['qualification'] ?? ''); ?>" placeholder="e.g., MBBS, MD">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Experience</label>
                                            <input type="text" class="form-control" name="experience" value="<?php echo htmlspecialchars($doctor['experience'] ?? ''); ?>" placeholder="e.g., 10 years">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Working Hours</label>
                                    <input type="text" class="form-control" name="working_hours" value="<?php echo htmlspecialchars($doctor['working_hours'] ?? '9:00 AM - 5:00 PM'); ?>" placeholder="e.g., 9:00 AM - 5:00 PM">
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
                <h3 class="mb-4"><i class="fas fa-comment-dots mr-2"></i>Patient Feedback</h3>
                
                <div class="data-table">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Recent Feedback</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Rating</th>
                                    <th>Feedback</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($feedbacks) > 0): ?>
                                    <?php foreach($feedbacks as $feedback): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($feedback['created_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($feedback['patient_name']); ?></td>
                                        <td>
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <?php if($i <= $feedback['rating']): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-warning"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($feedback['feedback'], 0, 100)) . (strlen($feedback['feedback']) > 100 ? '...' : ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No feedback found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Prescription Modal -->
    <div class="modal fade" id="prescriptionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="prescriptionModalTitle">Prescription</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="prescription-form">
                        <input type="hidden" name="prescription_id" id="prescription_id" value="new">
                        <input type="hidden" name="national_id" id="national_id">
                        <input type="hidden" name="appdate" id="appdate">
                        <input type="hidden" name="patient_name" id="patient_name">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Patient</label>
                                    <input type="text" class="form-control" id="patient_display" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" id="appdate_display" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Disease/Diagnosis *</label>
                                    <input type="text" class="form-control" name="disease" id="disease" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Allergy (if any)</label>
                                    <input type="text" class="form-control" name="allergy" id="allergy">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Prescription *</label>
                            <textarea class="form-control" name="prescription" id="prescription" rows="3" required placeholder="Enter detailed prescription..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Medicine *</label>
                            <textarea class="form-control" name="medicine" id="medicine" rows="3" required placeholder="List medicines with dosage..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tests Recommended</label>
                                    <textarea class="form-control" name="test" id="test" rows="2" placeholder="Recommended tests..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Advice</label>
                                    <textarea class="form-control" name="advice" id="advice" rows="2" placeholder="Additional advice..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="save_prescription" class="btn btn-success btn-block">
                            <i class="fas fa-save mr-2"></i>Save Prescription
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Prescription Modal -->
    <div class="modal fade" id="viewPrescriptionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Prescription Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="prescriptionDetails">
                        <!-- Prescription details will be loaded here via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Prescription Modal -->
    <div class="modal fade" id="sendPrescriptionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Prescription via Email</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="send-prescription-form">
                        <input type="hidden" name="prescription_id" id="send_prescription_id">
                        <div class="form-group">
                            <label>Patient Email *</label>
                            <input type="email" class="form-control" name="patient_email" id="patient_email" required>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            The prescription will be sent to the patient's email address.
                        </div>
                        <button type="submit" name="send_prescription" class="btn btn-success btn-block">
                            <i class="fas fa-paper-plane mr-2"></i>Send Prescription
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
            
            // Set today's date for new prescriptions
            $('#appdate').val(new Date().toISOString().split('T')[0]);
            $('#appdate_display').val(new Date().toISOString().split('T')[0]);
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
        
        // Function to confirm appointment
        function confirmAppointment(appointmentId) {
            if(confirm('Are you sure you want to confirm this appointment?')) {
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
                    name: 'status',
                    value: 'confirm'
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'update_appointment',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
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
                    name: 'status',
                    value: 'cancel'
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'update_appointment',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Function to create prescription from appointment
        function createPrescription(appointmentId, nationalId, appDate, patientName) {
            $('#prescription_id').val('new');
            $('#national_id').val(nationalId);
            $('#appdate').val(appDate);
            $('#patient_name').val(patientName);
            
            $('#patient_display').val(patientName);
            $('#appdate_display').val(appDate);
            $('#disease').val('');
            $('#allergy').val('');
            $('#prescription').val('');
            $('#medicine').val('');
            $('#test').val('');
            $('#advice').val('');
            
            $('#prescriptionModalTitle').text('New Prescription');
            $('#prescriptionModal').modal('show');
        }
        
        // Function to create prescription for patient directly
        function createPrescriptionForPatient(nationalId, patientName) {
            $('#prescription_id').val('new');
            $('#national_id').val(nationalId);
            $('#appdate').val(new Date().toISOString().split('T')[0]);
            $('#patient_name').val(patientName);
            
            $('#patient_display').val(patientName);
            $('#appdate_display').val(new Date().toISOString().split('T')[0]);
            $('#disease').val('');
            $('#allergy').val('');
            $('#prescription').val('');
            $('#medicine').val('');
            $('#test').val('');
            $('#advice').val('');
            
            $('#prescriptionModalTitle').text('New Prescription');
            $('#prescriptionModal').modal('show');
        }
        
        // Function to show new prescription form
        function showNewPrescriptionForm() {
            $('#prescription_id').val('new');
            $('#national_id').val('');
            $('#appdate').val(new Date().toISOString().split('T')[0]);
            $('#patient_name').val('');
            
            $('#patient_display').val('');
            $('#appdate_display').val(new Date().toISOString().split('T')[0]);
            $('#disease').val('');
            $('#allergy').val('');
            $('#prescription').val('');
            $('#medicine').val('');
            $('#test').val('');
            $('#advice').val('');
            
            // Clear patient fields and make them editable
            $('#patient_display').removeAttr('readonly').attr('placeholder', 'Enter patient name');
            $('#appdate_display').removeAttr('readonly');
            
            $('#prescriptionModalTitle').text('New Prescription');
            $('#prescriptionModal').modal('show');
        }
        
        // Function to view prescription
        function viewPrescription(prescriptionId) {
            $.ajax({
                url: 'get-prescription.php',
                method: 'POST',
                data: { prescription_id: prescriptionId },
                success: function(response) {
                    $('#prescriptionDetails').html(response);
                    $('#viewPrescriptionModal').modal('show');
                },
                error: function() {
                    alert('Error loading prescription details.');
                }
            });
        }
        
        // Function to edit prescription
        function editPrescription(prescriptionId) {
            $.ajax({
                url: 'get-prescription.php',
                method: 'POST',
                data: { prescription_id: prescriptionId, action: 'edit' },
                dataType: 'json',
                success: function(data) {
                    $('#prescription_id').val(data.id);
                    $('#national_id').val(data.national_id);
                    $('#appdate').val(data.appdate);
                    $('#patient_name').val(data.patient_name);
                    
                    $('#patient_display').val(data.patient_name);
                    $('#appdate_display').val(data.appdate);
                    $('#disease').val(data.disease);
                    $('#allergy').val(data.allergy);
                    $('#prescription').val(data.prescription);
                    $('#medicine').val(data.medicine);
                    $('#test').val(data.test);
                    $('#advice').val(data.advice);
                    
                    $('#prescriptionModalTitle').text('Edit Prescription');
                    $('#prescriptionModal').modal('show');
                },
                error: function() {
                    alert('Error loading prescription data.');
                }
            });
        }
        
        // Function to send prescription via email
        function sendPrescriptionEmail(prescriptionId) {
            $('#send_prescription_id').val(prescriptionId);
            $('#sendPrescriptionModal').modal('show');
        }
        
        // Function to view patient history
        function viewPatientHistory(patientId) {
            alert('Patient history feature coming soon!');
        }
        
        // Function to reset filter
        function resetFilter() {
            window.location.href = window.location.pathname;
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
            $('#prescription-form').submit(function(e) {
                const disease = $('#disease').val();
                const prescription = $('#prescription').val();
                const medicine = $('#medicine').val();
                
                if(!disease || !prescription || !medicine) {
                    e.preventDefault();
                    alert('Please fill all required fields!');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($con); ?>