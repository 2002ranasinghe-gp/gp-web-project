<?php
// ===========================
// SESSION AND DATABASE
// ===========================
session_start();

// Redirect if not logged in as doctor
if(!isset($_SESSION['doctor'])){
    header("Location: index.php");
    exit();
}

// Database connection
$con = mysqli_connect("localhost", "root", "", "myhmsdb");
if(!$con){
    die("Database connection failed: " . mysqli_connect_error());
}

// ===========================
// DOCTOR INFO
// ===========================
$doctor_username = $_SESSION['doctor'];
$doctor_email = $_SESSION['doctor_email'] ?? '';

// Get doctor details from database
$doctor_query = mysqli_query($con, "SELECT * FROM doctb WHERE username='$doctor_username'");
if($doctor_query && mysqli_num_rows($doctor_query) > 0){
    $doctor_data = mysqli_fetch_assoc($doctor_query);
    $doctor_name = $doctor_data['username'];
    $doctor_id = $doctor_data['id'] ?? '';
    $doctor_spec = $doctor_data['spec'] ?? '';
    $doctor_contact = $doctor_data['contact'] ?? '';
    $doctor_fees = $doctor_data['docFees'] ?? '0';
} else {
    header("Location: index.php");
    exit();
}

// ===========================
// MESSAGES VARIABLES
// ===========================
$appointment_msg = "";
$prescription_msg = "";
$settings_msg = "";
$profile_msg = "";

// ===========================
// STATISTICS
// ===========================
$total_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb WHERE doctor='$doctor_name'"))[0];
$today_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb WHERE doctor='$doctor_name' AND appdate = CURDATE() AND appointmentStatus='active'"))[0];
$total_prescriptions = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM prestb WHERE doctor='$doctor_name'"))[0];
$total_patients = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(DISTINCT pid) FROM appointmenttb WHERE doctor='$doctor_name'"))[0];
$upcoming_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb WHERE doctor='$doctor_name' AND appdate >= CURDATE() AND appointmentStatus='active'"))[0];
$completed_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb WHERE doctor='$doctor_name' AND appointmentStatus='completed'"))[0];
$pending_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb WHERE doctor='$doctor_name' AND appointmentStatus='pending'"))[0];
$total_earnings = mysqli_fetch_array(mysqli_query($con, "SELECT SUM(fees) FROM paymenttb WHERE doctor='$doctor_name' AND pay_status='Paid'"))[0];

// ===========================
// ADD PRESCRIPTION
// ===========================
if(isset($_POST['add_prescription'])){
    $patient_id = mysqli_real_escape_string($con, $_POST['patient_id']);
    $disease = mysqli_real_escape_string($con, $_POST['disease']);
    $prescription = mysqli_real_escape_string($con, $_POST['prescription']);
    $medicine = mysqli_real_escape_string($con, $_POST['medicine']);
    $dose = mysqli_real_escape_string($con, $_POST['dose']);
    $duration = mysqli_real_escape_string($con, $_POST['duration']);
    $notes = mysqli_real_escape_string($con, $_POST['notes']);
    
    // Get patient details
    $patient_query = mysqli_query($con, "SELECT * FROM patreg WHERE pid='$patient_id'");
    if(mysqli_num_rows($patient_query) > 0){
        $patient = mysqli_fetch_assoc($patient_query);
        
        // Insert prescription
        $query = "INSERT INTO prestb (pid, national_id, fname, lname, gender, email, contact, doctor, disease, prescription, medicine, dose, duration, notes, appdate) 
                  VALUES ('{$patient['pid']}', '{$patient['national_id']}', '{$patient['fname']}', '{$patient['lname']}', 
                          '{$patient['gender']}', '{$patient['email']}', '{$patient['contact']}', 
                          '$doctor_name', '$disease', '$prescription', '$medicine', '$dose', '$duration', '$notes', CURDATE())";
        
        if(mysqli_query($con, $query)){
            $prescription_id = mysqli_insert_id($con);
            $prescription_msg = "<div class='alert alert-success'>✅ Prescription added successfully! Prescription ID: $prescription_id</div>";
        } else {
            $prescription_msg = "<div class='alert alert-danger'>❌ Error adding prescription: " . mysqli_error($con) . "</div>";
        }
    } else {
        $prescription_msg = "<div class='alert alert-danger'>❌ Patient not found!</div>";
    }
}

// ===========================
// UPDATE APPOINTMENT STATUS
// ===========================
if(isset($_POST['update_appointment_status'])){
    $appointment_id = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $notes = mysqli_real_escape_string($con, $_POST['notes']);
    
    $query = "UPDATE appointmenttb SET 
              appointmentStatus='$status',
              doctorNotes='$notes'
              WHERE ID='$appointment_id' AND doctor='$doctor_name'";
    
    if(mysqli_query($con, $query)){
        $appointment_msg = "<div class='alert alert-success'>✅ Appointment status updated to $status successfully!</div>";
    } else {
        $appointment_msg = "<div class='alert alert-danger'>❌ Error updating appointment: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// UPDATE DOCTOR PROFILE
// ===========================
if(isset($_POST['update_profile'])){
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $contact = mysqli_real_escape_string($con, $_POST['contact']);
    $fees = mysqli_real_escape_string($con, $_POST['fees']);
    $experience = isset($_POST['experience']) ? mysqli_real_escape_string($con, $_POST['experience']) : '';
    $qualification = isset($_POST['qualification']) ? mysqli_real_escape_string($con, $_POST['qualification']) : '';
    $bio = isset($_POST['bio']) ? mysqli_real_escape_string($con, $_POST['bio']) : '';
    
    $query = "UPDATE doctb SET 
              email='$email',
              contact='$contact',
              docFees='$fees',
              experience='$experience',
              qualification='$qualification',
              bio='$bio'
              WHERE username='$doctor_name'";
    
    if(mysqli_query($con, $query)){
        $doctor_data['email'] = $email;
        $doctor_data['contact'] = $contact;
        $doctor_data['docFees'] = $fees;
        $doctor_data['experience'] = $experience;
        $doctor_data['qualification'] = $qualification;
        $doctor_data['bio'] = $bio;
        
        $profile_msg = "<div class='alert alert-success'>✅ Profile updated successfully!</div>";
    } else {
        $profile_msg = "<div class='alert alert-danger'>❌ Error updating profile: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// CHANGE DOCTOR PASSWORD
// ===========================
if(isset($_POST['change_password'])){
    $current_password = mysqli_real_escape_string($con, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($con, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);
    
    // Verify current password
    $check_password = mysqli_query($con, "SELECT * FROM doctb WHERE username='$doctor_name' AND password='$current_password'");
    if(mysqli_num_rows($check_password) == 0){
        $settings_msg = "<div class='alert alert-danger'>❌ Current password is incorrect!</div>";
    } elseif($new_password !== $confirm_password){
        $settings_msg = "<div class='alert alert-danger'>❌ New passwords do not match!</div>";
    } elseif(strlen($new_password) < 6){
        $settings_msg = "<div class='alert alert-danger'>❌ New password must be at least 6 characters!</div>";
    } else {
        // Update password
        $query = "UPDATE doctb SET password='$new_password' WHERE username='$doctor_name'";
        if(mysqli_query($con, $query)){
            $settings_msg = "<div class='alert alert-success'>✅ Password changed successfully!</div>";
        } else {
            $settings_msg = "<div class='alert alert-danger'>❌ Error changing password: " . mysqli_error($con) . "</div>";
        }
    }
}

// ===========================
// GET DATA FROM DATABASE
// ===========================
$my_appointments = [];
$my_prescriptions = [];
$my_patients = [];
$doctors_list = [];
$my_schedules = [];
$my_payments = [];

// Get doctor's appointments with filtering
$appointment_sql = "SELECT a.*, p.national_id as patient_nic 
                    FROM appointmenttb a 
                    LEFT JOIN patreg p ON a.pid = p.pid 
                    WHERE a.doctor='$doctor_name'";
                    
// Apply filters if set
if(isset($_GET['filter_status']) && $_GET['filter_status'] != 'all'){
    $status_filter = mysqli_real_escape_string($con, $_GET['filter_status']);
    $appointment_sql .= " AND a.appointmentStatus='$status_filter'";
}

if(isset($_GET['filter_date']) && !empty($_GET['filter_date'])){
    $date_filter = mysqli_real_escape_string($con, $_GET['filter_date']);
    $appointment_sql .= " AND a.appdate='$date_filter'";
}

$appointment_sql .= " ORDER BY a.appdate DESC, a.apptime DESC";

$appointment_result = mysqli_query($con, $appointment_sql);
if($appointment_result){
    while($row = mysqli_fetch_assoc($appointment_result)){
        $my_appointments[] = $row;
    }
}

// Get doctor's prescriptions
$prescription_result = mysqli_query($con, "SELECT * FROM prestb WHERE doctor='$doctor_name' ORDER BY appdate DESC");
if($prescription_result){
    while($row = mysqli_fetch_assoc($prescription_result)){
        $my_prescriptions[] = $row;
    }
}

// Get doctor's patients (unique patients who have appointments)
$patient_result = mysqli_query($con, "SELECT DISTINCT p.* FROM patreg p 
                                     JOIN appointmenttb a ON p.pid = a.pid 
                                     WHERE a.doctor='$doctor_name' 
                                     ORDER BY p.fname, p.lname");
if($patient_result){
    while($row = mysqli_fetch_assoc($patient_result)){
        $my_patients[] = $row;
    }
}

// Get all doctors for reference
$doctor_list_result = mysqli_query($con, "SELECT id, username, spec, email, docFees, contact, experience, qualification FROM doctb ORDER BY username");
if($doctor_list_result){
    while($row = mysqli_fetch_assoc($doctor_list_result)){
        $doctors_list[] = $row;
    }
}

// Get doctor's schedule with filtering
$schedule_sql = "SELECT * FROM scheduletb WHERE staff_name='$doctor_name'";
if(isset($_GET['filter_day']) && $_GET['filter_day'] != 'all'){
    $day_filter = mysqli_real_escape_string($con, $_GET['filter_day']);
    $schedule_sql .= " AND day='$day_filter'";
}
$schedule_sql .= " ORDER BY 
    CASE day 
        WHEN 'Monday' THEN 1
        WHEN 'Tuesday' THEN 2
        WHEN 'Wednesday' THEN 3
        WHEN 'Thursday' THEN 4
        WHEN 'Friday' THEN 5
        WHEN 'Saturday' THEN 6
        WHEN 'Sunday' THEN 7
    END, shift";

$schedule_result = mysqli_query($con, $schedule_sql);
if($schedule_result){
    while($row = mysqli_fetch_assoc($schedule_result)){
        $my_schedules[] = $row;
    }
}

// Get doctor's payments
$payment_result = mysqli_query($con, "SELECT * FROM paymenttb WHERE doctor='$doctor_name' ORDER BY pay_date DESC");
if($payment_result){
    while($row = mysqli_fetch_assoc($payment_result)){
        $my_payments[] = $row;
    }
}

// Get hospital settings
$hospital_settings = [];
$settings_result = mysqli_query($con, "SELECT * FROM hospital_settings");
if($settings_result){
    while($row = mysqli_fetch_assoc($settings_result)){
        $hospital_settings[$row['setting_key']] = $row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Panel - Healthcare Hospital</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <style>
        body { 
            background: #f8f9fa; 
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Sidebar - Blue Theme */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #007bff 0%, #0056b3 100%);
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

        /* Main content */
        .main-content { 
            margin-left: 250px; 
            width: calc(100% - 250px); 
        }
        
        .topbar {
            background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
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
            color: #007bff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        /* Dashboard Cards */
        .stats-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .stats-icon {
            font-size: 40px;
            opacity: 0.8;
        }
        
        /* Quick Actions */
        .quick-action-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
            border: 2px solid transparent;
        }
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            border-color: #007bff;
        }
        .quick-action-card i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #007bff;
        }

        /* Tables */
        .data-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .table-header {
            background: #007bff;
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }

        /* Tabs Content */
        .tab-content {
            padding: 30px;
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Search and Filter */
        .search-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .search-bar {
            position: relative;
        }
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        /* Status Badges */
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
            background: #d4edda;
            color: #155724;
        }
        .status-unpaid {
            background: #fff3cd;
            color: #856404;
        }
        .status-upcoming {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        /* Form Cards */
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .form-card-header {
            background: #007bff;
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -25px -25px 20px -25px;
        }

        /* Settings Page */
        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border-left: 5px solid #007bff;
        }

        /* Responsive */
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
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .btn {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
            border-color: #007bff;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
        
        .table th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            padding: 15px;
        }
        
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-top: 1px solid #eee;
        }
        
        .table tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .action-btn {
            margin: 2px;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 5px;
        }
        
        .user-avatar-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        /* Custom Blue Colors */
        .text-blue {
            color: #007bff !important;
        }
        
        .bg-blue {
            background-color: #007bff !important;
        }
        
        .border-left-blue {
            border-left-color: #007bff !important;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .filter-btn {
            padding: 8px 15px;
            border-radius: 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        /* Doctor Specialization Filter */
        .spec-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .spec-badge {
            padding: 8px 15px;
            border-radius: 20px;
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .spec-badge:hover, .spec-badge.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        /* Prescription Form */
        .prescription-form textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Doctor Card */
        .doctor-card {
            border: 1px solid #e3f2fd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .doctor-card:hover {
            box-shadow: 0 5px 20px rgba(0, 123, 255, 0.1);
            transform: translateY(-2px);
        }
        
        /* Earnings Card */
        .earnings-card {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        /* Appointment Card */
        .appointment-card {
            border-left: 5px solid #007bff;
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img class="logo" src="images/medical-logo.jpg" alt="Hospital Logo">
        <h4>Doctor Portal</h4>
        <ul>
            <li data-target="dash-tab" class="active">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </li>
            <li data-target="app-tab">
                <i class="fas fa-calendar-check"></i> <span>My Appointments</span>
            </li>
            <li data-target="pres-tab">
                <i class="fas fa-prescription"></i> <span>Prescriptions</span>
            </li>
            <li data-target="patients-tab">
                <i class="fas fa-user-injured"></i> <span>My Patients</span>
            </li>
            <li data-target="doctors-tab">
                <i class="fas fa-user-md"></i> <span>Doctors Directory</span>
            </li>
            <li data-target="sched-tab">
                <i class="fas fa-clock"></i> <span>My Schedule</span>
            </li>
            <li data-target="pay-tab">
                <i class="fas fa-credit-card"></i> <span>Earnings</span>
            </li>
            <li data-target="profile-tab">
                <i class="fas fa-user"></i> <span>My Profile</span>
            </li>
            <li data-target="settings-tab">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </li>
            <li>
                <a href="logout.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="brand">🏥 <?php echo isset($hospital_settings['hospital_name']) ? $hospital_settings['hospital_name'] : 'Healthcare Hospital'; ?> - Doctor Portal</div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-md"></i>
                </div>
                <div>
                    <strong>Dr. <?php echo htmlspecialchars($doctor_name); ?></strong><br>
                    <small><?php echo htmlspecialchars($doctor_spec); ?></small>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dash-tab">
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-primary" role="alert">
                            <h4 class="alert-heading">Welcome, Dr. <?php echo htmlspecialchars($doctor_name); ?>!</h4>
                            <p class="mb-0">Manage your appointments, write prescriptions, and view patient records from your doctor dashboard.</p>
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
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Today's Appointments
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $today_appointments; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day stats-icon text-primary"></i>
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
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Patients
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $total_patients; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-injured stats-icon text-success"></i>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Prescriptions Issued
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
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

                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Total Earnings
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            Rs. <?php echo number_format($total_earnings, 2); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-rupee-sign stats-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12 mb-3">
                        <h4>Quick Actions</h4>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showTab('app-tab')">
                            <i class="fas fa-calendar-plus"></i>
                            <h5>View Appointments</h5>
                            <p>Check today's schedule</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showTab('pres-tab')">
                            <i class="fas fa-prescription"></i>
                            <h5>Write Prescription</h5>
                            <p>Add new prescription</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showTab('patients-tab')">
                            <i class="fas fa-user-injured"></i>
                            <h5>My Patients</h5>
                            <p>View patient records</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showTab('sched-tab')">
                            <i class="fas fa-clock"></i>
                            <h5>My Schedule</h5>
                            <p>View working hours</p>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="table-header">
                                <h5 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Today's Appointments</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Time</th>
                                            <th>Patient</th>
                                            <th>NIC</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $today_query = mysqli_query($con, "SELECT * FROM appointmenttb WHERE doctor='$doctor_name' AND appdate = CURDATE() ORDER BY apptime");
                                        if(mysqli_num_rows($today_query) > 0):
                                            while($app = mysqli_fetch_assoc($today_query)):
                                        ?>
                                        <tr>
                                            <td><?php echo date('h:i A', strtotime($app['apptime'])); ?></td>
                                            <td><?php echo $app['fname'] . ' ' . $app['lname']; ?></td>
                                            <td><?php echo $app['national_id']; ?></td>
                                            <td><?php echo $app['contact']; ?></td>
                                            <td>
                                                <?php if($app['appointmentStatus'] == 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php elseif($app['appointmentStatus'] == 'completed'): ?>
                                                    <span class="badge badge-info">Completed</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning"><?php echo ucfirst($app['appointmentStatus']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary action-btn" onclick="showTab('app-tab')">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                        <?php 
                                            endwhile;
                                        else: ?>
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

                <!-- Recent Prescriptions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="table-header">
                                <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Recent Prescriptions</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Patient</th>
                                            <th>Disease</th>
                                            <th>Medicine</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $recent_pres = mysqli_query($con, "SELECT * FROM prestb WHERE doctor='$doctor_name' ORDER BY appdate DESC LIMIT 5");
                                        if(mysqli_num_rows($recent_pres) > 0):
                                            while($pres = mysqli_fetch_assoc($recent_pres)):
                                        ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d', strtotime($pres['appdate'])); ?></td>
                                            <td><?php echo $pres['fname'] . ' ' . $pres['lname']; ?></td>
                                            <td><?php echo substr($pres['disease'], 0, 30) . (strlen($pres['disease']) > 30 ? '...' : ''); ?></td>
                                            <td><?php echo substr($pres['medicine'], 0, 30) . (strlen($pres['medicine']) > 30 ? '...' : ''); ?></td>
                                            <td>
                                                <span class="badge badge-info">Issued</span>
                                            </td>
                                        </tr>
                                        <?php 
                                            endwhile;
                                        else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No prescriptions issued</td>
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
            <div class="tab-pane fade" id="app-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-calendar-check mr-2"></i>My Appointments</h3>
                </div>
                
                <?php if($appointment_msg): echo $appointment_msg; endif; ?>
                
                <!-- Appointment Filters -->
                <div class="filter-section">
                    <h5>Filter Appointments</h5>
                    <form method="GET" id="appointment-filters">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control" name="filter_status" onchange="this.form.submit()">
                                        <option value="all" <?php echo (!isset($_GET['filter_status']) || $_GET['filter_status'] == 'all') ? 'selected' : ''; ?>>All Status</option>
                                        <option value="active" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="pending" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="completed" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? $_GET['filter_date'] : ''; ?>" onchange="this.form.submit()">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Actions</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                        <a href="?" class="btn btn-secondary">Clear</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Patient</th>
                                    <th>NIC</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Fees (Rs.)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($my_appointments) > 0): ?>
                                    <?php foreach($my_appointments as $app): ?>
                                    <tr>
                                        <td><?php echo $app['ID']; ?></td>
                                        <td><?php echo $app['fname'] . ' ' . $app['lname']; ?></td>
                                        <td><?php echo $app['national_id']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($app['appdate'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($app['apptime'])); ?></td>
                                        <td>Rs. <?php echo number_format($app['docFees'], 2); ?></td>
                                        <td>
                                            <?php if($app['appointmentStatus'] == 'active'): ?>
                                                <span class="status-badge status-active">Active</span>
                                            <?php elseif($app['appointmentStatus'] == 'completed'): ?>
                                                <span class="status-badge status-completed">Completed</span>
                                            <?php elseif($app['appointmentStatus'] == 'cancelled'): ?>
                                                <span class="status-badge status-cancelled">Cancelled</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending"><?php echo ucfirst($app['appointmentStatus']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary action-btn" data-toggle="modal" data-target="#updateAppointmentModal" data-appointment-id="<?php echo $app['ID']; ?>" data-current-status="<?php echo $app['appointmentStatus']; ?>">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                            <?php if($app['appointmentStatus'] == 'active'): ?>
                                                <button class="btn btn-sm btn-info action-btn" data-toggle="modal" data-target="#addPrescriptionModal" data-patient-id="<?php echo $app['pid']; ?>" data-patient-name="<?php echo $app['fname'] . ' ' . $app['lname']; ?>">
                                                    <i class="fas fa-prescription"></i> Prescribe
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
            <div class="tab-pane fade" id="pres-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-prescription mr-2"></i>Prescriptions</h3>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addPrescriptionModal">
                        <i class="fas fa-plus mr-2"></i>Add New Prescription
                    </button>
                </div>
                
                <?php if($prescription_msg): echo $prescription_msg; endif; ?>
                
                <div class="search-container">
                    <div class="search-bar">
                        <input type="text" class="form-control" id="prescription-search" placeholder="Search prescriptions by patient name, disease, or medicine..." onkeyup="filterTable('prescription-search', 'prescriptions-table-body')">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Prescription ID</th>
                                    <th>Patient</th>
                                    <th>Disease</th>
                                    <th>Medicine</th>
                                    <th>Dose</th>
                                    <th>Duration</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="prescriptions-table-body">
                                <?php if(count($my_prescriptions) > 0): ?>
                                    <?php foreach($my_prescriptions as $pres): ?>
                                    <tr>
                                        <td><?php echo $pres['id']; ?></td>
                                        <td><?php echo $pres['fname'] . ' ' . $pres['lname']; ?></td>
                                        <td><?php echo $pres['disease']; ?></td>
                                        <td><?php echo substr($pres['medicine'], 0, 30) . (strlen($pres['medicine']) > 30 ? '...' : ''); ?></td>
                                        <td><?php echo $pres['dose']; ?></td>
                                        <td><?php echo $pres['duration']; ?> days</td>
                                        <td><?php echo date('Y-m-d', strtotime($pres['appdate'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info action-btn" data-toggle="modal" data-target="#viewPrescriptionModal" data-prescription='<?php echo json_encode($pres); ?>'>
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-warning action-btn" data-toggle="modal" data-target="#printPrescriptionModal" data-prescription-id="<?php echo $pres['id']; ?>">
                                                <i class="fas fa-print"></i> Print
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No prescriptions found</td>
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
                
                <div class="search-container">
                    <div class="search-bar">
                        <input type="text" class="form-control" id="patient-search" placeholder="Search patients by name, ID, NIC, or contact..." onkeyup="filterTable('patient-search', 'patients-table-body')">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient ID</th>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>NIC</th>
                                    <th>Last Visit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="patients-table-body">
                                <?php if(count($my_patients) > 0): ?>
                                    <?php foreach($my_patients as $pat): 
                                        // Get last appointment date
                                        $last_app = mysqli_query($con, "SELECT appdate FROM appointmenttb WHERE pid='{$pat['pid']}' AND doctor='$doctor_name' ORDER BY appdate DESC LIMIT 1");
                                        $last_visit = mysqli_fetch_assoc($last_app);
                                    ?>
                                    <tr>
                                        <td><?php echo $pat['pid']; ?></td>
                                        <td><?php echo $pat['fname'] . ' ' . $pat['lname']; ?></td>
                                        <td><?php echo $pat['gender']; ?></td>
                                        <td><?php echo $pat['email']; ?></td>
                                        <td><?php echo $pat['contact']; ?></td>
                                        <td><?php echo $pat['national_id']; ?></td>
                                        <td><?php echo $last_visit ? date('Y-m-d', strtotime($last_visit['appdate'])) : 'Never'; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary action-btn" data-toggle="modal" data-target="#addPrescriptionModal" data-patient-id="<?php echo $pat['pid']; ?>" data-patient-name="<?php echo $pat['fname'] . ' ' . $pat['lname']; ?>">
                                                <i class="fas fa-prescription"></i> Prescribe
                                            </button>
                                            <button class="btn btn-sm btn-info action-btn" data-toggle="modal" data-target="#patientHistoryModal" data-patient-id="<?php echo $pat['pid']; ?>">
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

            <!-- Doctors Directory Tab -->
            <div class="tab-pane fade" id="doctors-tab">
                <h3 class="mb-4"><i class="fas fa-user-md mr-2"></i>Doctors Directory</h3>
                
                <!-- Specialization Filter -->
                <div class="filter-section">
                    <h5>Filter by Specialization</h5>
                    <div class="spec-filter">
                        <div class="spec-badge all-spec active" onclick="filterDoctors('all')">All</div>
                        <?php 
                        $all_specs = mysqli_query($con, "SELECT DISTINCT spec FROM doctb ORDER BY spec");
                        while($spec = mysqli_fetch_assoc($all_specs)):
                        ?>
                        <div class="spec-badge" onclick="filterDoctors('<?php echo $spec['spec']; ?>')">
                            <?php echo $spec['spec']; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <div class="search-container">
                    <div class="search-bar">
                        <input type="text" class="form-control" id="doctor-search" placeholder="Search doctors by name, specialization, or contact..." onkeyup="filterTable('doctor-search', 'doctors-table-body')">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                
                <div class="row" id="doctors-container">
                    <?php if(count($doctors_list) > 0): ?>
                        <?php foreach($doctors_list as $doc): ?>
                        <div class="col-md-4 mb-4 doctor-card-item" data-spec="<?php echo strtolower($doc['spec']); ?>">
                            <div class="doctor-card">
                                <div class="text-center mb-3">
                                    <div class="user-avatar" style="width: 80px; height: 80px; margin: 0 auto;">
                                        <i class="fas fa-user-md" style="font-size: 40px;"></i>
                                    </div>
                                </div>
                                <h5 class="text-center">Dr. <?php echo $doc['username']; ?></h5>
                                <p class="text-center text-primary"><strong><?php echo $doc['spec']; ?></strong></p>
                                
                                <div class="doctor-info mt-3">
                                    <p><i class="fas fa-graduation-cap mr-2"></i> <?php echo $doc['qualification'] ?: 'MBBS, MD'; ?></p>
                                    <p><i class="fas fa-briefcase mr-2"></i> <?php echo $doc['experience'] ? $doc['experience'] . ' years experience' : 'Experienced'; ?></p>
                                    <p><i class="fas fa-rupee-sign mr-2"></i> Rs. <?php echo number_format($doc['docFees'], 2); ?> per consultation</p>
                                    <p><i class="fas fa-phone mr-2"></i> <?php echo $doc['contact']; ?></p>
                                    <p><i class="fas fa-envelope mr-2"></i> <?php echo $doc['email']; ?></p>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <button class="btn btn-sm btn-outline-primary" onclick="showDoctorSchedule('<?php echo $doc['username']; ?>')">
                                        <i class="fas fa-clock mr-1"></i> View Schedule
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">No doctors found in the directory.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Schedule Tab -->
            <div class="tab-pane fade" id="sched-tab">
                <h3 class="mb-4"><i class="fas fa-clock mr-2"></i>My Schedule</h3>
                
                <!-- Schedule Filters -->
                <div class="filter-section">
                    <h5>Filter Schedule</h5>
                    <form method="GET" id="schedule-filters">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Day</label>
                                    <select class="form-control" name="filter_day" onchange="this.form.submit()">
                                        <option value="all" <?php echo (!isset($_GET['filter_day']) || $_GET['filter_day'] == 'all') ? 'selected' : ''; ?>>All Days</option>
                                        <option value="Monday" <?php echo (isset($_GET['filter_day']) && $_GET['filter_day'] == 'Monday') ? 'selected' : ''; ?>>Monday</option>
                                        <option value="Tuesday" <?php echo (isset($_GET['filter_day']) && $_GET['filter_day'] == 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                                        <option value="Wednesday" <?php echo (isset($_GET['filter_day']) && $_GET['filter_day'] == 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                                        <option value="Thursday" <?php echo (isset($_GET['filter_day']) && $_GET['filter_day'] == 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                                        <option value="Friday" <?php echo (isset($_GET['filter_day']) && $_GET['filter_day'] == 'Friday') ? 'selected' : ''; ?>>Friday</option>
                                        <option value="Saturday" <?php echo (isset($_GET['filter_day']) && $_GET['filter_day'] == 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                                        <option value="Sunday" <?php echo (isset($_GET['filter_day']) && $_GET['filter_day'] == 'Sunday') ? 'selected' : ''; ?>>Sunday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Shift</label>
                                    <select class="form-control" onchange="filterScheduleByShift(this.value)">
                                        <option value="all">All Shifts</option>
                                        <option value="Morning">Morning</option>
                                        <option value="Afternoon">Afternoon</option>
                                        <option value="Evening">Evening</option>
                                        <option value="Night">Night</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Actions</label>
                                    <div>
                                        <a href="?" class="btn btn-secondary">Clear Filters</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Shift</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="schedule-table-body">
                                <?php if(count($my_schedules) > 0): ?>
                                    <?php foreach($my_schedules as $sched): ?>
                                    <tr>
                                        <td><strong><?php echo $sched['day']; ?></strong></td>
                                        <td><?php echo $sched['shift']; ?></td>
                                        <td><?php echo date('h:i A', strtotime($sched['start_time'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($sched['end_time'])); ?></td>
                                        <td>
                                            <?php 
                                            $start = strtotime($sched['start_time']);
                                            $end = strtotime($sched['end_time']);
                                            $hours = ($end - $start) / 3600;
                                            echo $hours . ' hours';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($sched['status'] == 'Active'): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No schedule found. Please contact administration to set your schedule.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Weekly Schedule View -->
                <div class="data-table mt-4">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-week mr-2"></i>Weekly Schedule Overview</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Morning (8 AM - 12 PM)</th>
                                    <th>Afternoon (12 PM - 4 PM)</th>
                                    <th>Evening (4 PM - 8 PM)</th>
                                    <th>Night (8 PM - 12 AM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                foreach($days as $day):
                                    $day_schedules = array_filter($my_schedules, function($s) use ($day) {
                                        return $s['day'] == $day;
                                    });
                                ?>
                                <tr>
                                    <td><strong><?php echo $day; ?></strong></td>
                                    <td class="<?php echo $thisDay = array_filter($day_schedules, function($s) { return $s['shift'] == 'Morning'; }) ? 'table-success' : 'table-light'; ?>">
                                        <?php echo $thisDay ? 'Available' : 'Not Available'; ?>
                                    </td>
                                    <td class="<?php echo $thisDay = array_filter($day_schedules, function($s) { return $s['shift'] == 'Afternoon'; }) ? 'table-success' : 'table-light'; ?>">
                                        <?php echo $thisDay ? 'Available' : 'Not Available'; ?>
                                    </td>
                                    <td class="<?php echo $thisDay = array_filter($day_schedules, function($s) { return $s['shift'] == 'Evening'; }) ? 'table-success' : 'table-light'; ?>">
                                        <?php echo $thisDay ? 'Available' : 'Not Available'; ?>
                                    </td>
                                    <td class="<?php echo $thisDay = array_filter($day_schedules, function($s) { return $s['shift'] == 'Night'; }) ? 'table-success' : 'table-light'; ?>">
                                        <?php echo $thisDay ? 'Available' : 'Not Available'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Earnings Tab -->
            <div class="tab-pane fade" id="pay-tab">
                <h3 class="mb-4"><i class="fas fa-credit-card mr-2"></i>Earnings & Payments</h3>
                
                <!-- Earnings Summary -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="earnings-card">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><i class="fas fa-rupee-sign mr-2"></i>Total Earnings</h5>
                                    <h2 class="mt-3">Rs. <?php echo number_format($total_earnings, 2); ?></h2>
                                    <p class="mb-0">Total consultation fees collected</p>
                                </div>
                                <div class="col-md-6 text-right">
                                    <h5><i class="fas fa-calendar-alt mr-2"></i>This Month</h5>
                                    <?php 
                                    $month_earnings = mysqli_fetch_array(mysqli_query($con, "SELECT SUM(fees) FROM paymenttb WHERE doctor='$doctor_name' AND pay_status='Paid' AND MONTH(pay_date) = MONTH(CURDATE()) AND YEAR(pay_date) = YEAR(CURDATE())"))[0];
                                    ?>
                                    <h2 class="mt-3">Rs. <?php echo number_format($month_earnings, 2); ?></h2>
                                    <p class="mb-0">Earnings for <?php echo date('F Y'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Paid
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM paymenttb WHERE doctor='$doctor_name' AND pay_status='Paid'"))[0]; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle stats-icon text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM paymenttb WHERE doctor='$doctor_name' AND pay_status='Pending'"))[0]; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock stats-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            This Month
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM paymenttb WHERE doctor='$doctor_name' AND MONTH(pay_date) = MONTH(CURDATE())"))[0]; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar stats-icon text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Today
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM paymenttb WHERE doctor='$doctor_name' AND pay_date = CURDATE()"))[0]; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-rupee-sign stats-icon text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="search-container">
                    <div class="search-bar">
                        <input type="text" class="form-control" id="payment-search" placeholder="Search payments by patient name, date, or status..." onkeyup="filterTable('payment-search', 'payments-table-body')">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Patient</th>
                                    <th>Appointment ID</th>
                                    <th>Amount (Rs.)</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Method</th>
                                    <th>Receipt No</th>
                                </tr>
                            </thead>
                            <tbody id="payments-table-body">
                                <?php if(count($my_payments) > 0): ?>
                                    <?php foreach($my_payments as $pay): ?>
                                    <tr>
                                        <td><?php echo $pay['id']; ?></td>
                                        <td><?php echo $pay['patient_name']; ?></td>
                                        <td><?php echo $pay['appointment_id']; ?></td>
                                        <td>Rs. <?php echo number_format($pay['fees'], 2); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($pay['pay_date'])); ?></td>
                                        <td>
                                            <?php if($pay['pay_status'] == 'Paid'): ?>
                                                <span class="status-badge status-paid">Paid</span>
                                            <?php else: ?>
                                                <span class="status-badge status-unpaid">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $pay['payment_method'] ?: 'Cash'; ?></td>
                                        <td><?php echo $pay['receipt_no'] ?: 'N/A'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No payment records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Profile Tab -->
            <div class="tab-pane fade" id="profile-tab">
                <h3 class="mb-4"><i class="fas fa-user mr-2"></i>My Profile</h3>
                
                <?php if($profile_msg): echo $profile_msg; endif; ?>
                
                <!-- Doctor Profile -->
                <div class="settings-card">
                    <h4><i class="fas fa-user-md mr-2"></i>Doctor Profile</h4>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Doctor ID</label>
                                    <input type="text" class="form-control" value="<?php echo $doctor_id; ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" class="form-control" value="<?php echo $doctor_name; ?>" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Specialization</label>
                                    <input type="text" class="form-control" value="<?php echo $doctor_spec; ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Consultation Fee (Rs.)</label>
                                    <input type="number" class="form-control" name="fees" value="<?php echo $doctor_fees; ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $doctor_data['email']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Contact *</label>
                                    <input type="text" class="form-control" name="contact" value="<?php echo $doctor_contact; ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Qualification</label>
                                    <input type="text" class="form-control" name="qualification" value="<?php echo $doctor_data['qualification'] ?? ''; ?>" placeholder="e.g., MBBS, MD">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Experience (Years)</label>
                                    <input type="number" class="form-control" name="experience" value="<?php echo $doctor_data['experience'] ?? ''; ?>" placeholder="e.g., 10">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Bio/Description</label>
                            <textarea class="form-control" name="bio" rows="3"><?php echo $doctor_data['bio'] ?? ''; ?></textarea>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Update Profile
                        </button>
                    </form>
                </div>
                
                <!-- Profile Statistics -->
                <div class="settings-card">
                    <h4><i class="fas fa-chart-bar mr-2"></i>Profile Statistics</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Total Appointments</label>
                                <input type="text" class="form-control" value="<?php echo $total_appointments; ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Total Patients</label>
                                <input type="text" class="form-control" value="<?php echo $total_patients; ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Prescriptions Issued</label>
                                <input type="text" class="form-control" value="<?php echo $total_prescriptions; ?>" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Appointment Completion Rate</label>
                                <?php 
                                $completion_rate = $total_appointments > 0 ? ($completed_appointments / $total_appointments * 100) : 0;
                                ?>
                                <input type="text" class="form-control" value="<?php echo number_format($completion_rate, 1); ?>%" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Average Patients Per Day</label>
                                <?php 
                                $avg_patients = $total_appointments > 0 ? ($total_appointments / 30) : 0; // Assuming 30 days
                                ?>
                                <input type="text" class="form-control" value="<?php echo number_format($avg_patients, 1); ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div class="tab-pane fade" id="settings-tab">
                <h3 class="mb-4"><i class="fas fa-cog mr-2"></i>Settings</h3>
                
                <?php if($settings_msg): echo $settings_msg; endif; ?>
                
                <!-- Change Password -->
                <div class="settings-card">
                    <h4><i class="fas fa-key mr-2"></i>Change Password</h4>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Current Password *</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>New Password *</label>
                                    <input type="password" class="form-control" name="new_password" required>
                                    <small class="text-muted">Min. 6 characters</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Confirm New Password *</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key mr-1"></i> Change Password
                        </button>
                    </form>
                </div>
                
                <!-- Notification Settings -->
                <div class="settings-card">
                    <h4><i class="fas fa-bell mr-2"></i>Notification Settings</h4>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="notify-appointments" checked>
                        <label class="form-check-label" for="notify-appointments">
                            Notify me about new appointments
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="notify-prescriptions" checked>
                        <label class="form-check-label" for="notify-prescriptions">
                            Notify me about prescription requests
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="notify-payments">
                        <label class="form-check-label" for="notify-payments">
                            Notify me about payments
                        </label>
                    </div>
                    <button class="btn btn-secondary mt-2">
                        <i class="fas fa-save mr-1"></i> Save Notification Settings
                    </button>
                </div>
                
                <!-- Working Hours Preferences -->
                <div class="settings-card">
                    <h4><i class="fas fa-clock mr-2"></i>Working Hours Preferences</h4>
                    <div class="form-group">
                        <label>Preferred Appointment Duration (Minutes)</label>
                        <select class="form-control">
                            <option value="15">15 minutes</option>
                            <option value="30" selected>30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">60 minutes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Maximum Appointments Per Day</label>
                        <input type="number" class="form-control" value="20" min="1" max="50">
                    </div>
                    <button class="btn btn-secondary">
                        <i class="fas fa-save mr-1"></i> Save Preferences
                    </button>
                </div>
                
                <!-- System Actions -->
                <div class="settings-card">
                    <h4><i class="fas fa-sign-out-alt mr-2"></i>System Actions</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <a href="logout.php" class="btn btn-danger btn-block">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="index.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-home mr-2"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Update Appointment Status Modal -->
    <div class="modal fade" id="updateAppointmentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Appointment Status</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="update-appointment-form">
                        <div class="form-group">
                            <label>Appointment Status</label>
                            <select class="form-control" id="appointment-status" required>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Notes (Optional)</label>
                            <textarea class="form-control" id="appointment-notes" rows="3" placeholder="Add any notes about this appointment..."></textarea>
                        </div>
                        <input type="hidden" id="appointment-id">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateAppointmentStatus()">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Prescription Modal -->
    <div class="modal fade" id="addPrescriptionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Prescription</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="add-prescription-form" method="POST">
                        <input type="hidden" name="patient_id" id="prescription-patient-id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Patient Name</label>
                                    <input type="text" class="form-control" id="prescription-patient-name" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Disease/Condition *</label>
                                    <input type="text" class="form-control" name="disease" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Medicine *</label>
                                    <input type="text" class="form-control" name="medicine" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Dose *</label>
                                    <input type="text" class="form-control" name="dose" placeholder="e.g., 1 tablet" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Duration (Days) *</label>
                                    <input type="number" class="form-control" name="duration" min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Prescription Details *</label>
                            <textarea class="form-control" name="prescription" rows="4" required placeholder="Enter full prescription details..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Additional Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Any additional instructions or notes..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_prescription" form="add-prescription-form" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Save Prescription
                    </button>
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
                <div class="modal-body" id="prescription-details">
                    <!-- Filled by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printPrescription()">
                        <i class="fas fa-print mr-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient History Modal -->
    <div class="modal fade" id="patientHistoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Patient Medical History</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="patient-history-details">
                    <!-- Filled by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let currentAppointmentId = null;
        let currentPrescriptionData = null;
        
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
            
            // Set up modal functionality
            $('#updateAppointmentModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                currentAppointmentId = button.data('appointment-id');
                const currentStatus = button.data('current-status');
                $('#appointment-id').val(currentAppointmentId);
                $('#appointment-status').val(currentStatus);
                $('#appointment-notes').val('');
            });
            
            $('#addPrescriptionModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const patientId = button.data('patient-id');
                const patientName = button.data('patient-name');
                
                if(patientId && patientName) {
                    $('#prescription-patient-id').val(patientId);
                    $('#prescription-patient-name').val(patientName);
                }
            });
            
            $('#viewPrescriptionModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                currentPrescriptionData = JSON.parse(button.data('prescription'));
                
                if(currentPrescriptionData) {
                    displayPrescriptionDetails(currentPrescriptionData);
                }
            });
            
            $('#patientHistoryModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const patientId = button.data('patient-id');
                
                if(patientId) {
                    loadPatientHistory(patientId);
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
        
        // Function to show tab
        function showTab(tabId) {
            // Hide all tab panes
            $('.tab-pane').removeClass('show active');
            
            // Show selected tab
            $('#' + tabId).addClass('show active');
            
            // Scroll to top
            window.scrollTo(0, 0);
        }
        
        // Function to filter table rows
        function filterTable(searchInputId, tableBodyId) {
            const input = $('#' + searchInputId).val().toLowerCase();
            $('#' + tableBodyId + ' tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(input) > -1);
            });
        }
        
        // Function to filter doctors by specialization
        function filterDoctors(specialization) {
            // Update active filter button
            $('.spec-badge').removeClass('active');
            $(event.target).addClass('active');
            
            if(specialization === 'all') {
                $('.doctor-card-item').show();
            } else {
                $('.doctor-card-item').hide();
                $(`.doctor-card-item[data-spec="${specialization.toLowerCase()}"]`).show();
            }
        }
        
        // Function to filter schedule by shift
        function filterScheduleByShift(shift) {
            if(shift === 'all') {
                $('#schedule-table-body tr').show();
            } else {
                $('#schedule-table-body tr').hide();
                $(`#schedule-table-body tr:contains(${shift})`).show();
            }
        }
        
        // Function to show doctor schedule
        function showDoctorSchedule(doctorName) {
            // Implement doctor schedule viewing
            alert(`Schedule for Dr. ${doctorName} will be displayed here.`);
            // You can implement a modal or redirect to schedule view
        }
        
        // Function to update appointment status
        function updateAppointmentStatus() {
            if(!currentAppointmentId) return;
            
            const status = $('#appointment-status').val();
            const notes = $('#appointment-notes').val();
            
            // Submit form
            const form = $('<form>').attr({
                method: 'POST',
                style: 'display: none;'
            });
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'appointment_id',
                value: currentAppointmentId
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'status',
                value: status
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'notes',
                value: notes
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'update_appointment_status',
                value: '1'
            }));
            
            $('body').append(form);
            form.submit();
        }
        
        // Function to display prescription details
        function displayPrescriptionDetails(prescription) {
            const html = `
                <div class="prescription-details">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Patient Information</h6>
                            <p><strong>Name:</strong> ${prescription.fname} ${prescription.lname}</p>
                            <p><strong>Gender:</strong> ${prescription.gender}</p>
                            <p><strong>Contact:</strong> ${prescription.contact}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Prescription Information</h6>
                            <p><strong>Date:</strong> ${prescription.appdate}</p>
                            <p><strong>Doctor:</strong> ${prescription.doctor}</p>
                            <p><strong>Prescription ID:</strong> ${prescription.id}</p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6>Diagnosis</h6>
                            <div class="alert alert-info">
                                <strong>Disease/Condition:</strong> ${prescription.disease}
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6>Medication</h6>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Dose</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>${prescription.medicine}</td>
                                        <td>${prescription.dose}</td>
                                        <td>${prescription.duration} days</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6>Prescription Details</h6>
                            <div class="card">
                                <div class="card-body">
                                    ${prescription.prescription.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    ${prescription.notes ? `
                    <div class="row">
                        <div class="col-12">
                            <h6>Additional Notes</h6>
                            <div class="alert alert-warning">
                                ${prescription.notes.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
            
            $('#prescription-details').html(html);
        }
        
        // Function to load patient history
        function loadPatientHistory(patientId) {
            // You would typically make an AJAX call here
            // For now, we'll show a loading message
            $('#patient-history-details').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading patient history...</p>
                </div>
            `);
            
            // Simulate loading
            setTimeout(() => {
                const html = `
                    <div class="patient-history">
                        <h6>Loading patient history for ID: ${patientId}</h6>
                        <p>This would display:</p>
                        <ul>
                            <li>Previous appointments</li>
                            <li>Past prescriptions</li>
                            <li>Medical history</li>
                            <li>Allergies</li>
                            <li>Lab test results</li>
                        </ul>
                        <p class="text-muted">Note: This feature requires additional database tables and data.</p>
                    </div>
                `;
                $('#patient-history-details').html(html);
            }, 1000);
        }
        
        // Function to print prescription
        function printPrescription() {
            if(!currentPrescriptionData) return;
            
            const printWindow = window.open('', '_blank');
            const printContent = `
                <html>
                <head>
                    <title>Prescription - ${currentPrescriptionData.fname} ${currentPrescriptionData.lname}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .hospital-name { font-size: 24px; font-weight: bold; color: #007bff; }
                        .prescription-title { font-size: 20px; margin: 20px 0; text-align: center; }
                        .patient-info, .doctor-info { margin-bottom: 20px; }
                        .section-title { font-weight: bold; margin-top: 20px; }
                        .medicine-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        .medicine-table th, .medicine-table td { border: 1px solid #000; padding: 8px; text-align: left; }
                        .signature { margin-top: 50px; text-align: right; }
                        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
                        @media print {
                            body { margin: 0; padding: 20px; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="hospital-name">Healthcare Hospital</div>
                        <div>123 Medical Street, City, Country</div>
                        <div>Phone: +94 11 234 5678 | Email: info@hospital.com</div>
                    </div>
                    
                    <div class="prescription-title">MEDICAL PRESCRIPTION</div>
                    
                    <div class="row">
                        <div class="col-6 patient-info">
                            <strong>Patient Information:</strong><br>
                            Name: ${currentPrescriptionData.fname} ${currentPrescriptionData.lname}<br>
                            Gender: ${currentPrescriptionData.gender}<br>
                            Contact: ${currentPrescriptionData.contact}<br>
                            Date: ${currentPrescriptionData.appdate}
                        </div>
                        <div class="col-6 doctor-info">
                            <strong>Doctor Information:</strong><br>
                            Name: Dr. ${currentPrescriptionData.doctor}<br>
                            Prescription ID: ${currentPrescriptionData.id}<br>
                            Date Issued: ${new Date().toLocaleDateString()}
                        </div>
                    </div>
                    
                    <div class="section-title">Diagnosis:</div>
                    <div>${currentPrescriptionData.disease}</div>
                    
                    <div class="section-title">Prescribed Medication:</div>
                    <table class="medicine-table">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Dose</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>${currentPrescriptionData.medicine}</td>
                                <td>${currentPrescriptionData.dose}</td>
                                <td>${currentPrescriptionData.duration} days</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="section-title">Instructions:</div>
                    <div>${currentPrescriptionData.prescription.replace(/\n/g, '<br>')}</div>
                    
                    ${currentPrescriptionData.notes ? `
                    <div class="section-title">Additional Notes:</div>
                    <div>${currentPrescriptionData.notes.replace(/\n/g, '<br>')}</div>
                    ` : ''}
                    
                    <div class="signature">
                        <div>_________________________</div>
                        <div>Dr. ${currentPrescriptionData.doctor}</div>
                        <div>${currentPrescriptionData.spec || 'Medical Practitioner'}</div>
                        <div>License No: MED-${currentPrescriptionData.id.toString().padStart(5, '0')}</div>
                    </div>
                    
                    <div class="footer">
                        <p>This is a computer-generated prescription. No signature required.</p>
                        <p>For emergency, call: +94 11 999 9999</p>
                    </div>
                    
                    <div class="no-print" style="margin-top: 20px;">
                        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">
                            Print Prescription
                        </button>
                        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer; margin-left: 10px;">
                            Close Window
                        </button>
                    </div>
                    
                    <script>
                        // Auto-print
                        window.onload = function() {
                            window.print();
                        };
                    <\/script>
                </body>
                </html>
            `;
            
            printWindow.document.write(printContent);
            printWindow.document.close();
        }
        
        // Quick action buttons
        function showTab(tabId) {
            $('.tab-pane').removeClass('show active');
            $('#' + tabId).addClass('show active');
        }
    </script>
</body>
</html>