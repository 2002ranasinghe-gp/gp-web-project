<?php
// ===========================
// SESSION AND DATABASE
// ===========================
session_start();

// Redirect if not logged in as reception
if(!isset($_SESSION['reception'])){
    header("Location: index.php");
    exit();
}

// Database connection
$con = mysqli_connect("localhost", "root", "", "myhmsdb");
if(!$con){
    die("Database connection failed: " . mysqli_connect_error());
}

// ===========================
// RECEPTION INFO
// ===========================
$reception_name = $_SESSION['reception'];
$reception_email = $_SESSION['reception_email'] ?? 'reception@hospital.com';

// Get reception details from database
$reception_query = mysqli_query($con, "SELECT * FROM stafftb WHERE name='$reception_name' AND role='Receptionist'");
if($reception_query && mysqli_num_rows($reception_query) > 0){
    $reception_data = mysqli_fetch_assoc($reception_query);
    $reception_id = $reception_data['id'] ?? 'REC001';
    $reception_contact = $reception_data['contact'] ?? '';
    $reception_profile_pic = $reception_data['profile_pic'] ?? 'default-avatar.jpg';
} else {
    $reception_id = 'REC001';
    $reception_contact = '';
    $reception_profile_pic = 'default-avatar.jpg';
}

// ===========================
// MESSAGES VARIABLES
// ===========================
$patient_msg = "";
$appointment_msg = "";
$payment_msg = "";
$settings_msg = "";

// ===========================
// STATISTICS
// ===========================
$total_patients = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patreg"));
$total_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb"));
$today_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE appdate = CURDATE()"));
$pending_payments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM paymenttb WHERE pay_status = 'Pending'"));
$total_doctors = mysqli_num_rows(mysqli_query($con, "SELECT * FROM doctb"));
$total_staff = mysqli_num_rows(mysqli_query($con, "SELECT * FROM stafftb"));
$today_payments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM paymenttb WHERE pay_date = CURDATE()"));
$available_rooms = mysqli_num_rows(mysqli_query($con, "SELECT * FROM roomtb WHERE status = 'Available'"));
$active_prescriptions = mysqli_num_rows(mysqli_query($con, "SELECT * FROM prestb WHERE emailStatus = 'Not Sent'"));
$total_feedback = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patient_feedback"));
$today_feedback = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patient_feedback WHERE DATE(feedback_date) = CURDATE()"));

// ===========================
// ADD PATIENT (RECEPTION)
// ===========================
if(isset($_POST['add_patient'])){
    $fname = mysqli_real_escape_string($con, $_POST['fname']);
    $lname = mysqli_real_escape_string($con, $_POST['lname']);
    $gender = mysqli_real_escape_string($con, $_POST['gender']);
    $dob = mysqli_real_escape_string($con, $_POST['dob']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $contact = mysqli_real_escape_string($con, $_POST['contact']);
    $address = isset($_POST['address']) ? mysqli_real_escape_string($con, $_POST['address']) : '';
    $emergencyContact = isset($_POST['emergencyContact']) ? mysqli_real_escape_string($con, $_POST['emergencyContact']) : '';
    $nic_input = mysqli_real_escape_string($con, $_POST['nic']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $cpassword = isset($_POST['cpassword']) ? mysqli_real_escape_string($con, $_POST['cpassword']) : '';
    
    // Check if passwords match
    if($password !== $cpassword) {
        $patient_msg = "<div class='alert alert-danger'>❌ Passwords do not match!</div>";
    } else {
        // Format NIC
        $nicNumbers = preg_replace('/[^0-9]/', '', $nic_input);
        $national_id = 'NIC' . $nicNumbers;
        
        // Check if email exists
        $check_email = mysqli_query($con, "SELECT * FROM patreg WHERE email='$email'");
        if(mysqli_num_rows($check_email) > 0){
            $patient_msg = "<div class='alert alert-danger'>❌ Patient with this email already exists!</div>";
        } else {
            // Check if NIC exists
            $check_nic = mysqli_query($con, "SELECT * FROM patreg WHERE national_id='$national_id'");
            if(mysqli_num_rows($check_nic) > 0){
                $patient_msg = "<div class='alert alert-danger'>❌ Patient with this NIC already exists!</div>";
            } else {
                // Insert patient
                $query = "INSERT INTO patreg (fname, lname, gender, dob, email, contact, address, emergencyContact, national_id, password) 
                          VALUES ('$fname', '$lname', '$gender', '$dob', '$email', '$contact', '$address', '$emergencyContact', '$national_id', '$password')";
                
                if(mysqli_query($con, $query)){
                    $new_patient_id = mysqli_insert_id($con);
                    $patient_msg = "<div class='alert alert-success'>✅ Patient registered successfully! Patient ID: $new_patient_id, NIC: $national_id</div>";
                } else {
                    $patient_msg = "<div class='alert alert-danger'>❌ Database Error: " . mysqli_error($con) . "</div>";
                }
            }
        }
    }
}

// ===========================
// ADD APPOINTMENT BY NIC
// ===========================
if(isset($_POST['add_appointment_by_nic'])){
    $patient_nic = mysqli_real_escape_string($con, $_POST['patient_nic']);
    $doctor = mysqli_real_escape_string($con, $_POST['doctor']);
    $appdate = mysqli_real_escape_string($con, $_POST['appdate']);
    $apptime = mysqli_real_escape_string($con, $_POST['apptime']);
    $appointment_reason = isset($_POST['appointment_reason']) ? mysqli_real_escape_string($con, $_POST['appointment_reason']) : '';
    
    // Get patient details by NIC
    $patient_query = mysqli_query($con, "SELECT * FROM patreg WHERE national_id='$patient_nic'");
    if(mysqli_num_rows($patient_query) > 0){
        $patient = mysqli_fetch_assoc($patient_query);
        
        // Get doctor fees
        $doctor_query = mysqli_query($con, "SELECT * FROM doctb WHERE username='$doctor'");
        if(mysqli_num_rows($doctor_query) > 0){
            $doctor_data = mysqli_fetch_assoc($doctor_query);
            $docFees = $doctor_data['docFees'];
            
            // Insert appointment
            $query = "INSERT INTO appointmenttb (pid, national_id, fname, lname, gender, email, contact, doctor, docFees, appdate, apptime, appointment_reason) 
                      VALUES ('{$patient['pid']}', '{$patient['national_id']}', '{$patient['fname']}', '{$patient['lname']}', 
                              '{$patient['gender']}', '{$patient['email']}', '{$patient['contact']}', 
                              '$doctor', '$docFees', '$appdate', '$apptime', '$appointment_reason')";
            
            if(mysqli_query($con, $query)){
                $appointment_id = mysqli_insert_id($con);
                
                // Create corresponding payment record
                $payment_query = "INSERT INTO paymenttb (pid, appointment_id, national_id, patient_name, doctor, fees, pay_date) 
                                  VALUES ('{$patient['pid']}', '$appointment_id', '{$patient['national_id']}', 
                                          '{$patient['fname']} {$patient['lname']}', '$doctor', '$docFees', '$appdate')";
                mysqli_query($con, $payment_query);
                
                $appointment_msg = "<div class='alert alert-success'>✅ Appointment created successfully using NIC!<br>
                                   Appointment ID: $appointment_id<br>
                                   Patient: {$patient['fname']} {$patient['lname']}<br>
                                   NIC: {$patient['national_id']}</div>";
            } else {
                $appointment_msg = "<div class='alert alert-danger'>❌ Error creating appointment: " . mysqli_error($con) . "</div>";
            }
        } else {
            $appointment_msg = "<div class='alert alert-danger'>❌ Doctor not found!</div>";
        }
    } else {
        $appointment_msg = "<div class='alert alert-danger'>❌ Patient not found with NIC: $patient_nic</div>";
    }
}

// ===========================
// ADD APPOINTMENT BY PATIENT ID
// ===========================
if(isset($_POST['add_appointment'])){
    $patient_id = mysqli_real_escape_string($con, $_POST['patient_id']);
    $doctor = mysqli_real_escape_string($con, $_POST['doctor']);
    $appdate = mysqli_real_escape_string($con, $_POST['appdate']);
    $apptime = mysqli_real_escape_string($con, $_POST['apptime']);
    $appointment_reason = isset($_POST['appointment_reason']) ? mysqli_real_escape_string($con, $_POST['appointment_reason']) : '';
    
    // Get patient details
    $patient_query = mysqli_query($con, "SELECT * FROM patreg WHERE pid='$patient_id'");
    if(mysqli_num_rows($patient_query) > 0){
        $patient = mysqli_fetch_assoc($patient_query);
        
        // Get doctor fees
        $doctor_query = mysqli_query($con, "SELECT * FROM doctb WHERE username='$doctor'");
        if(mysqli_num_rows($doctor_query) > 0){
            $doctor_data = mysqli_fetch_assoc($doctor_query);
            $docFees = $doctor_data['docFees'];
            
            // Insert appointment
            $query = "INSERT INTO appointmenttb (pid, national_id, fname, lname, gender, email, contact, doctor, docFees, appdate, apptime, appointment_reason) 
                      VALUES ('{$patient['pid']}', '{$patient['national_id']}', '{$patient['fname']}', '{$patient['lname']}', 
                              '{$patient['gender']}', '{$patient['email']}', '{$patient['contact']}', 
                              '$doctor', '$docFees', '$appdate', '$apptime', '$appointment_reason')";
            
            if(mysqli_query($con, $query)){
                $appointment_id = mysqli_insert_id($con);
                
                // Create corresponding payment record
                $payment_query = "INSERT INTO paymenttb (pid, appointment_id, national_id, patient_name, doctor, fees, pay_date) 
                                  VALUES ('{$patient['pid']}', '$appointment_id', '{$patient['national_id']}', 
                                          '{$patient['fname']} {$patient['lname']}', '$doctor', '$docFees', '$appdate')";
                mysqli_query($con, $payment_query);
                
                $appointment_msg = "<div class='alert alert-success'>✅ Appointment created successfully! Appointment ID: $appointment_id</div>";
            } else {
                $appointment_msg = "<div class='alert alert-danger'>❌ Error creating appointment: " . mysqli_error($con) . "</div>";
            }
        } else {
            $appointment_msg = "<div class='alert alert-danger'>❌ Doctor not found!</div>";
        }
    } else {
        $appointment_msg = "<div class='alert alert-danger'>❌ Patient not found!</div>";
    }
}

// ===========================
// CANCEL APPOINTMENT
// ===========================
if(isset($_POST['cancel_appointment'])){
    $appointmentId = mysqli_real_escape_string($con, $_POST['appointmentId']);
    $reason = mysqli_real_escape_string($con, $_POST['reason']);
    
    $query = "UPDATE appointmenttb SET 
              appointmentStatus='cancelled',
              cancelledBy='reception',
              cancellationReason='$reason',
              userStatus=0 
              WHERE ID='$appointmentId'";
    
    if(mysqli_query($con, $query)){
        $appointment_msg = "<div class='alert alert-success'>✅ Appointment cancelled successfully!</div>";
    } else {
        $appointment_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// UPDATE PAYMENT STATUS
// ===========================
if(isset($_POST['update_payment'])){
    $paymentId = mysqli_real_escape_string($con, $_POST['paymentId']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $method = mysqli_real_escape_string($con, $_POST['method']);
    $receipt = mysqli_real_escape_string($con, $_POST['receipt']);
    
    if($status == 'Paid' && empty($receipt)){
        $receipt = 'REC' . str_pad($paymentId, 3, '0', STR_PAD_LEFT);
    }
    
    $query = "UPDATE paymenttb SET 
              pay_status='$status',
              payment_method='$method',
              receipt_no='$receipt'
              WHERE id='$paymentId'";
    
    if(mysqli_query($con, $query)){
        $payment_msg = "<div class='alert alert-success'>✅ Payment status updated successfully!</div>";
    } else {
        $payment_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// CHANGE RECEPTION PASSWORD
// ===========================
if(isset($_POST['change_password'])){
    $current_password = mysqli_real_escape_string($con, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($con, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);
    
    // Verify current password
    $check_password = mysqli_query($con, "SELECT * FROM stafftb WHERE name='$reception_name' AND password='$current_password'");
    if(mysqli_num_rows($check_password) == 0){
        $settings_msg = "<div class='alert alert-danger'>❌ Current password is incorrect!</div>";
    } elseif($new_password !== $confirm_password){
        $settings_msg = "<div class='alert alert-danger'>❌ New passwords do not match!</div>";
    } elseif(strlen($new_password) < 6){
        $settings_msg = "<div class='alert alert-danger'>❌ New password must be at least 6 characters!</div>";
    } else {
        // Update password
        $query = "UPDATE stafftb SET password='$new_password' WHERE name='$reception_name'";
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
$patients = [];
$doctors = [];
$appointments = [];
$payments = [];
$staff = [];
$schedules = [];

// Get patients
$patient_result = mysqli_query($con, "SELECT pid, fname, lname, gender, email, contact, dob, national_id FROM patreg ORDER BY pid DESC LIMIT 50");
if($patient_result){
    while($row = mysqli_fetch_assoc($patient_result)){
        $patients[] = $row;
    }
}

// Get doctors
$doctor_result = mysqli_query($con, "SELECT id, username, spec, email, docFees, contact FROM doctb ORDER BY username");
if($doctor_result){
    while($row = mysqli_fetch_assoc($doctor_result)){
        $doctors[] = $row;
    }
}

// Get appointments
$appointment_result = mysqli_query($con, "SELECT *, national_id as patient_nic FROM appointmenttb ORDER BY appdate DESC, apptime DESC LIMIT 50");
if($appointment_result){
    while($row = mysqli_fetch_assoc($appointment_result)){
        $appointments[] = $row;
    }
}

// Get payments
$payment_result = mysqli_query($con, "SELECT *, national_id as patient_nic FROM paymenttb ORDER BY pay_date DESC LIMIT 50");
if($payment_result){
    while($row = mysqli_fetch_assoc($payment_result)){
        $payments[] = $row;
    }
}

// Get staff
$staff_result = mysqli_query($con, "SELECT id, name, role, email, contact FROM stafftb ORDER BY role");
if($staff_result){
    while($row = mysqli_fetch_assoc($staff_result)){
        $staff[] = $row;
    }
}

// Get schedules
$schedule_result = mysqli_query($con, "SELECT id, staff_name, staff_id, role, day, shift, staff_type FROM scheduletb ORDER BY day, shift");
if($schedule_result){
    while($row = mysqli_fetch_assoc($schedule_result)){
        $schedules[] = $row;
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

// Current page for navigation
$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reception Dashboard - Healthcare Hospital</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-blue: #0077b6;
            --light-blue: #e3f2fd;
            --medium-blue: #90caf9;
            --dark-blue: #005b91;
            --accent-blue: #42a5f5;
            --text-dark: #37474f;
            --text-light: #607d8b;
            --white: #ffffff;
            --light-gray: #f5f5f5;
            --success-green: #4caf50;
            --warning-orange: #ff9800;
            --danger-red: #f44336;
            --card-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        body { 
            background: #f8f9fa; 
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-dark);
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
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
            background: linear-gradient(90deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
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
            color: var(--primary-blue);
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
            box-shadow: var(--card-shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
            background: white;
            padding: 25px;
            text-align: center;
            border-left: 5px solid var(--accent-blue);
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .stats-icon {
            font-size: 40px;
            color: var(--primary-blue);
            margin-bottom: 15px;
        }
        .stats-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
            color: var(--primary-blue);
        }
        .stats-card p {
            font-size: 1rem;
            color: var(--text-light);
            margin: 0;
        }
        
        /* Quick Actions */
        .quick-action-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
            border: 2px solid transparent;
        }
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            border-color: var(--primary-blue);
        }
        .quick-action-card i {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--primary-blue);
        }

        /* Tables */
        .data-table {
            background: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-top: 20px;
        }
        .table-header {
            background: var(--primary-blue);
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

        /* Form Cards */
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }
        .form-card-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -25px -25px 20px -25px;
        }

        /* Buttons */
        .btn-primary {
            background: var(--primary-blue);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: var(--dark-blue);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,119,182,0.3);
        }

        /* Alerts */
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        /* Status Badges */
        .badge-primary { background-color: var(--primary-blue); }
        .badge-success { background-color: var(--success-green); }
        .badge-warning { background-color: var(--warning-orange); }
        .badge-danger { background-color: var(--danger-red); }
        .badge-info { background-color: var(--accent-blue); }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 30px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .dashboard-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .dashboard-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Modal */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .modal-header {
            background: var(--primary-blue);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }

        /* Settings Cards */
        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            border-left: 5px solid var(--primary-blue);
        }
        .settings-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: white;
            font-size: 24px;
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
            .dashboard-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img class="logo" src="images/medical-logo.jpg" alt="Hospital Logo">
        <h4>Reception Portal</h4>
        <ul>
            <li class="<?php echo $page == 'dashboard' ? 'active' : ''; ?>">
                <a href="reception_dashboard.php?page=dashboard" style="color: white; text-decoration: none;">
                    <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="<?php echo $page == 'patients' ? 'active' : ''; ?>">
                <a href="reception_dashboard.php?page=patients" style="color: white; text-decoration: none;">
                    <i class="fas fa-user-injured"></i> <span>Patients</span>
                </a>
            </li>
            <li class="<?php echo $page == 'appointments' ? 'active' : ''; ?>">
                <a href="reception_dashboard.php?page=appointments" style="color: white; text-decoration: none;">
                    <i class="fas fa-calendar-check"></i> <span>Appointments</span>
                </a>
            </li>
            <li class="<?php echo $page == 'schedule' ? 'active' : ''; ?>">
                <a href="reception_dashboard.php?page=schedule" style="color: white; text-decoration: none;">
                    <i class="fas fa-clock"></i> <span>Schedule</span>
                </a>
            </li>
            <li class="<?php echo $page == 'payment' ? 'active' : ''; ?>">
                <a href="reception_dashboard.php?page=payment" style="color: white; text-decoration: none;">
                    <i class="fas fa-credit-card"></i> <span>Payment</span>
                </a>
            </li>
            <li class="<?php echo $page == 'staff' ? 'active' : ''; ?>">
                <a href="reception_dashboard.php?page=staff" style="color: white; text-decoration: none;">
                    <i class="fas fa-users"></i> <span>Staff</span>
                </a>
            </li>
            <li class="<?php echo $page == 'settings' ? 'active' : ''; ?>">
                <a href="reception_dashboard.php?page=settings" style="color: white; text-decoration: none;">
                    <i class="fas fa-cog"></i> <span>Settings</span>
                </a>
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
            <div class="brand">🏥 <?php echo isset($hospital_settings['hospital_name']) ? $hospital_settings['hospital_name'] : 'Healthcare Hospital'; ?></div>
            <div class="user-info">
                <div class="profile-pic-container">
                    <?php if($reception_profile_pic && file_exists('uploads/profile_pictures/' . $reception_profile_pic)): ?>
                        <img src="uploads/profile_pictures/<?php echo $reception_profile_pic; ?>" class="user-avatar-img" alt="Profile" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid white;">
                    <?php else: ?>
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($reception_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($reception_name); ?></strong><br>
                    <small>Receptionist</small>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="tab-content">
            <?php
            switch($page){
                //===========================
                case 'dashboard':
                    ?>
                    <!-- Dashboard Page -->
                    <div class="dashboard-header">
                        <h1><i class="fas fa-tachometer-alt mr-3"></i>Reception Dashboard</h1>
                        <p>Welcome back, <?php echo htmlspecialchars($reception_name); ?>! Here's your overview of hospital activities.</p>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card">
                                <i class="fas fa-user-injured stats-icon"></i>
                                <h3><?php echo $total_patients; ?></h3>
                                <p>Total Patients</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card">
                                <i class="fas fa-calendar-check stats-icon"></i>
                                <h3><?php echo $total_appointments; ?></h3>
                                <p>Total Appointments</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card">
                                <i class="fas fa-users stats-icon"></i>
                                <h3><?php echo $total_staff; ?></h3>
                                <p>Staff Members</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card">
                                <i class="fas fa-user-md stats-icon"></i>
                                <h3><?php echo $total_doctors; ?></h3>
                                <p>Doctors</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card">
                                <i class="fas fa-calendar-day stats-icon"></i>
                                <h3><?php echo $today_appointments; ?></h3>
                                <p>Today's Appointments</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card">
                                <i class="fas fa-credit-card stats-icon"></i>
                                <h3><?php echo $today_payments; ?></h3>
                                <p>Today's Payments</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card">
                                <i class="fas fa-clock stats-icon"></i>
                                <h3><?php echo $pending_payments; ?></h3>
                                <p>Pending Payments</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card">
                                <i class="fas fa-bed stats-icon"></i>
                                <h3><?php echo $available_rooms; ?></h3>
                                <p>Available Rooms</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mt-5">
                        <div class="col-12 mb-3">
                            <h3><i class="fas fa-bolt mr-2"></i>Quick Actions</h3>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="quick-action-card" onclick="window.location.href='reception_dashboard.php?page=patients'">
                                <i class="fas fa-user-plus"></i>
                                <h5>Register Patient</h5>
                                <p>Add a new patient</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="quick-action-card" onclick="window.location.href='reception_dashboard.php?page=appointments'">
                                <i class="fas fa-calendar-plus"></i>
                                <h5>Create Appointment</h5>
                                <p>Schedule appointment</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="quick-action-card" onclick="window.location.href='reception_dashboard.php?page=payment'">
                                <i class="fas fa-credit-card"></i>
                                <h5>Process Payment</h5>
                                <p>Update payment status</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="quick-action-card" onclick="window.location.href='reception_dashboard.php?page=settings'">
                                <i class="fas fa-user-cog"></i>
                                <h5>Profile Settings</h5>
                                <p>Update your profile</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="data-table mt-5">
                        <div class="table-header">
                            <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Recent Activity</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Time</th>
                                        <th>Activity</th>
                                        <th>Details</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Today</td>
                                        <td>Patients Registered</td>
                                        <td>Total <?php echo $total_patients; ?> patients in system</td>
                                        <td><span class="badge badge-success">Active</span></td>
                                    </tr>
                                    <tr>
                                        <td>Today</td>
                                        <td>Appointments</td>
                                        <td><?php echo $today_appointments; ?> appointments scheduled for today</td>
                                        <td><span class="badge badge-info">Scheduled</span></td>
                                    </tr>
                                    <tr>
                                        <td>Today</td>
                                        <td>Payments</td>
                                        <td><?php echo $today_payments; ?> payments processed today</td>
                                        <td><span class="badge badge-warning">Processing</span></td>
                                    </tr>
                                    <tr>
                                        <td>Now</td>
                                        <td>Reception Staff</td>
                                        <td>Logged in as <?php echo $reception_name; ?> (ID: <?php echo $reception_id; ?>)</td>
                                        <td><span class="badge badge-primary">Active</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                    break;

                //===========================
                case 'patients':
                    ?>
                    <!-- Patients Page -->
                    <div class="dashboard-header">
                        <h1><i class="fas fa-user-injured mr-3"></i>Patient Management</h1>
                        <p>Register and manage patient records</p>
                    </div>
                    
                    <?php if($patient_msg): echo $patient_msg; endif; ?>
                    
                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="quick-action-card" data-toggle="modal" data-target="#addPatientModal">
                                <i class="fas fa-user-plus"></i>
                                <h5>Register New Patient</h5>
                                <p>Add a new patient to the system</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="quick-action-card" onclick="window.location.href='reception_dashboard.php?page=appointments'">
                                <i class="fas fa-calendar-plus"></i>
                                <h5>Create Appointment</h5>
                                <p>Schedule an appointment</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="quick-action-card" onclick="window.print()">
                                <i class="fas fa-print"></i>
                                <h5>Print Records</h5>
                                <p>Print patient information</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Patients List -->
                    <div class="data-table">
                        <div class="table-header">
                            <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Patients List</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Gender</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Date of Birth</th>
                                        <th>NIC</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($patients) > 0): ?>
                                        <?php foreach($patients as $patient): ?>
                                        <tr>
                                            <td><strong><?php echo $patient['pid']; ?></strong></td>
                                            <td><?php echo $patient['fname']; ?></td>
                                            <td><?php echo $patient['lname']; ?></td>
                                            <td><span class="badge badge-primary"><?php echo $patient['gender']; ?></span></td>
                                            <td><?php echo $patient['email']; ?></td>
                                            <td><?php echo $patient['contact']; ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($patient['dob'])); ?></td>
                                            <td><code><?php echo $patient['national_id']; ?></code></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewPatient(<?php echo $patient['pid']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No patients found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Add Patient Modal -->
                    <div class="modal fade" id="addPatientModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Register New Patient</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" id="add-patient-form">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>First Name *</label>
                                                    <input type="text" class="form-control" name="fname" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Last Name *</label>
                                                    <input type="text" class="form-control" name="lname" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Gender *</label>
                                                    <select class="form-control" name="gender" required>
                                                        <option value="">Select Gender</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                        <option value="Other">Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Date of Birth *</label>
                                                    <input type="date" class="form-control" name="dob" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Email Address *</label>
                                                    <input type="email" class="form-control" name="email" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Contact Number *</label>
                                                    <input type="tel" class="form-control" name="contact" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>National ID (NIC) *</label>
                                                    <input type="text" class="form-control" name="nic" required>
                                                    <small class="text-muted">Enter NIC numbers only (e.g., 123456789)</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Password *</label>
                                                    <input type="password" class="form-control" name="password" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Confirm Password *</label>
                                                    <input type="password" class="form-control" name="cpassword" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Address</label>
                                                    <textarea class="form-control" name="address" rows="1"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Emergency Contact</label>
                                                    <input type="text" class="form-control" name="emergencyContact">
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" name="add_patient" form="add-patient-form" class="btn btn-primary">
                                        <i class="fas fa-user-plus mr-1"></i> Register Patient
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;

                //===========================
                case 'appointments':
                    ?>
                    <!-- Appointments Page -->
                    <div class="dashboard-header">
                        <h1><i class="fas fa-calendar-check mr-3"></i>Appointment Management</h1>
                        <p>Schedule and manage patient appointments</p>
                    </div>
                    
                    <?php if($appointment_msg): echo $appointment_msg; endif; ?>
                    
                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="quick-action-card" data-toggle="modal" data-target="#addAppointmentModal">
                                <i class="fas fa-calendar-plus"></i>
                                <h5>New Appointment</h5>
                                <p>Schedule a new appointment</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="quick-action-card" data-toggle="modal" data-target="#addAppointmentByNICModal">
                                <i class="fas fa-id-card"></i>
                                <h5>Appointment by NIC</h5>
                                <p>Create appointment using NIC</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="quick-action-card" onclick="window.location.href='reception_dashboard.php?page=payment'">
                                <i class="fas fa-credit-card"></i>
                                <h5>Process Payment</h5>
                                <p>Update payment status</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appointments List -->
                    <div class="data-table">
                        <div class="table-header">
                            <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Appointments List</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Patient Name</th>
                                        <th>NIC</th>
                                        <th>Doctor</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Fees (Rs.)</th>
                                        <th>Status</th>
                                        <th>User Status</th>
                                        <th>Doctor Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($appointments) > 0): ?>
                                        <?php foreach($appointments as $app): ?>
                                        <tr>
                                            <td><?php echo $app['ID']; ?></td>
                                            <td><?php echo $app['fname'] . ' ' . $app['lname']; ?></td>
                                            <td><code><?php echo $app['patient_nic'] ?: $app['national_id']; ?></code></td>
                                            <td><span class="badge badge-info"><?php echo $app['doctor']; ?></span></td>
                                            <td><?php echo date('Y-m-d', strtotime($app['appdate'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($app['apptime'])); ?></td>
                                            <td>Rs. <?php echo number_format($app['docFees'], 2); ?></td>
                                            <td>
                                                <?php if($app['appointmentStatus'] == 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Cancelled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($app['userStatus'] == 1): ?>
                                                    <span class="badge badge-success">Confirmed</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($app['doctorStatus'] == 1): ?>
                                                    <span class="badge badge-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($app['appointmentStatus'] == 'active'): ?>
                                                    <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#cancelAppointmentModal" 
                                                            data-appointment-id="<?php echo $app['ID']; ?>">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="11" class="text-center">No appointments found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Add Appointment Modal -->
                    <div class="modal fade" id="addAppointmentModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-calendar-plus mr-2"></i>Create New Appointment</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" id="add-appointment-form">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Patient ID *</label>
                                                    <input type="number" class="form-control" name="patient_id" placeholder="Enter patient ID" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Doctor *</label>
                                                    <select class="form-control" name="doctor" required>
                                                        <option value="">Select Doctor</option>
                                                        <?php foreach($doctors as $doctor): ?>
                                                        <option value="<?php echo $doctor['username']; ?>">
                                                            <?php echo $doctor['username']; ?> (<?php echo $doctor['spec']; ?>)
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Appointment Date *</label>
                                                    <input type="date" class="form-control" name="appdate" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Appointment Time *</label>
                                                    <input type="time" class="form-control" name="apptime" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Appointment Reason (Optional)</label>
                                            <textarea class="form-control" name="appointment_reason" rows="2" placeholder="Reason for appointment..."></textarea>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" name="add_appointment" form="add-appointment-form" class="btn btn-primary">
                                        <i class="fas fa-calendar-plus mr-1"></i> Create Appointment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Appointment by NIC Modal -->
                    <div class="modal fade" id="addAppointmentByNICModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-id-card mr-2"></i>Create Appointment by NIC</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" id="add-appointment-by-nic-form">
                                        <div class="form-group">
                                            <label>Patient NIC *</label>
                                            <input type="text" class="form-control" name="patient_nic" placeholder="Enter patient NIC (e.g., NIC123456789)" required>
                                            <small class="text-muted">Enter patient NIC (e.g., NIC123456789)</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Doctor *</label>
                                            <select class="form-control" name="doctor" required>
                                                <option value="">Select Doctor</option>
                                                <?php foreach($doctors as $doctor): ?>
                                                <option value="<?php echo $doctor['username']; ?>">
                                                    <?php echo $doctor['username']; ?> (<?php echo $doctor['spec']; ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Appointment Date *</label>
                                                    <input type="date" class="form-control" name="appdate" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Appointment Time *</label>
                                                    <input type="time" class="form-control" name="apptime" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Appointment Reason (Optional)</label>
                                            <textarea class="form-control" name="appointment_reason" rows="2" placeholder="Reason for appointment..."></textarea>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" name="add_appointment_by_nic" form="add-appointment-by-nic-form" class="btn btn-primary">
                                        <i class="fas fa-calendar-plus mr-1"></i> Create Appointment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cancel Appointment Modal -->
                    <div class="modal fade" id="cancelAppointmentModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i>Cancel Appointment</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" id="cancel-appointment-form">
                                        <div class="form-group">
                                            <label>Reason for Cancellation *</label>
                                            <textarea class="form-control" name="reason" rows="3" required></textarea>
                                        </div>
                                        <input type="hidden" name="appointmentId" id="appointmentToCancelId">
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" form="cancel-appointment-form" name="cancel_appointment" class="btn btn-danger">
                                        <i class="fas fa-times mr-1"></i> Cancel Appointment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;

                //===========================
                case 'payment':
                    ?>
                    <!-- Payments Page -->
                    <div class="dashboard-header">
                        <h1><i class="fas fa-credit-card mr-3"></i>Payment Management</h1>
                        <p>Process and track patient payments</p>
                    </div>
                    
                    <?php if($payment_msg): echo $payment_msg; endif; ?>
                    
                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-money-bill-wave stats-icon"></i>
                                <h3><?php echo $today_payments; ?></h3>
                                <p>Today's Payments</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-clock stats-icon"></i>
                                <h3><?php echo $pending_payments; ?></h3>
                                <p>Pending Payments</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-check-circle stats-icon"></i>
                                <h3><?php echo mysqli_num_rows(mysqli_query($con, "SELECT * FROM paymenttb WHERE pay_status = 'Paid'")); ?></h3>
                                <p>Completed Payments</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-chart-line stats-icon"></i>
                                <h3>Rs. <?php 
                                    $total_revenue = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(fees) as total FROM paymenttb WHERE pay_status = 'Paid'"));
                                    echo number_format($total_revenue['total'] ?? 0, 2);
                                ?></h3>
                                <p>Total Revenue</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payments List -->
                    <div class="data-table">
                        <div class="table-header">
                            <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Payments List</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Patient Name</th>
                                        <th>NIC</th>
                                        <th>Doctor</th>
                                        <th>Amount (Rs.)</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Method</th>
                                        <th>Receipt No</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($payments) > 0): ?>
                                        <?php foreach($payments as $pay): ?>
                                        <tr>
                                            <td><?php echo $pay['id']; ?></td>
                                            <td><?php echo $pay['patient_name']; ?></td>
                                            <td><code><?php echo $pay['patient_nic'] ?: $pay['national_id']; ?></code></td>
                                            <td><span class="badge badge-info"><?php echo $pay['doctor']; ?></span></td>
                                            <td>Rs. <?php echo number_format($pay['fees'], 2); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($pay['pay_date'])); ?></td>
                                            <td>
                                                <?php if($pay['pay_status'] == 'Paid'): ?>
                                                    <span class="badge badge-success">Paid</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $pay['payment_method'] ?: 'N/A'; ?></td>
                                            <td><?php echo $pay['receipt_no'] ?: 'N/A'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editPaymentModal" 
                                                        data-payment-id="<?php echo $pay['id']; ?>">
                                                    <i class="fas fa-edit"></i> Update
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center">No payments found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Edit Payment Modal -->
                    <div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Update Payment Status</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" id="edit-payment-form">
                                        <input type="hidden" name="paymentId" id="edit-payment-id">
                                        <div class="form-group">
                                            <label>Payment Status *</label>
                                            <select class="form-control" name="status" required>
                                                <option value="Pending">Pending</option>
                                                <option value="Paid">Paid</option>
                                                <option value="Cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Payment Method</label>
                                            <select class="form-control" name="method">
                                                <option value="">Select Method</option>
                                                <option value="Cash">Cash</option>
                                                <option value="Credit Card">Credit Card</option>
                                                <option value="Debit Card">Debit Card</option>
                                                <option value="Bank Transfer">Bank Transfer</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Receipt Number</label>
                                            <input type="text" class="form-control" name="receipt" placeholder="Auto-generated if empty">
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" form="edit-payment-form" name="update_payment" class="btn btn-primary">
                                        <i class="fas fa-save mr-1"></i> Update Payment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;

                //===========================
                case 'schedule':
                    ?>
                    <!-- Schedule Page -->
                    <div class="dashboard-header">
                        <h1><i class="fas fa-calendar-alt mr-3"></i>Staff Schedule</h1>
                        <p>View staff schedules and shifts</p>
                    </div>
                    
                    <!-- Schedule List -->
                    <div class="data-table">
                        <div class="table-header">
                            <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Staff Schedules</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Schedule ID</th>
                                        <th>Staff ID</th>
                                        <th>Staff Name</th>
                                        <th>Role</th>
                                        <th>Day</th>
                                        <th>Shift</th>
                                        <th>Staff Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($schedules) > 0): ?>
                                        <?php foreach($schedules as $schedule): ?>
                                        <tr>
                                            <td><?php echo $schedule['id']; ?></td>
                                            <td><code><?php echo $schedule['staff_id']; ?></code></td>
                                            <td><?php echo $schedule['staff_name']; ?></td>
                                            <td><span class="badge badge-primary"><?php echo $schedule['role']; ?></span></td>
                                            <td><strong><?php echo $schedule['day']; ?></strong></td>
                                            <td>
                                                <?php if($schedule['shift'] == 'Morning'): ?>
                                                    <span class="badge badge-success">Morning</span>
                                                <?php elseif($schedule['shift'] == 'Evening'): ?>
                                                    <span class="badge badge-warning">Evening</span>
                                                <?php else: ?>
                                                    <span class="badge badge-info"><?php echo $schedule['shift']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($schedule['staff_type'] == 'doctor'): ?>
                                                    <span class="badge badge-primary">Doctor</span>
                                                <?php elseif($schedule['staff_type'] == 'nurse'): ?>
                                                    <span class="badge badge-success">Nurse</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary"><?php echo $schedule['staff_type']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No schedules found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                    break;

                //===========================
                case 'staff':
                    ?>
                    <!-- Staff Page -->
                    <div class="dashboard-header">
                        <h1><i class="fas fa-users mr-3"></i>Staff Management</h1>
                        <p>View hospital staff information</p>
                    </div>
                    
                    <!-- Staff List -->
                    <div class="data-table">
                        <div class="table-header">
                            <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Staff Directory</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Staff ID</th>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($staff) > 0): ?>
                                        <?php foreach($staff as $staff_member): ?>
                                        <tr>
                                            <td><strong><?php echo $staff_member['id']; ?></strong></td>
                                            <td><?php echo $staff_member['name']; ?></td>
                                            <td>
                                                <?php if($staff_member['role'] == 'Doctor'): ?>
                                                    <span class="badge badge-primary">Doctor</span>
                                                <?php elseif($staff_member['role'] == 'Nurse'): ?>
                                                    <span class="badge badge-success">Nurse</span>
                                                <?php elseif($staff_member['role'] == 'Receptionist'): ?>
                                                    <span class="badge badge-info">Receptionist</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary"><?php echo $staff_member['role']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $staff_member['email']; ?></td>
                                            <td><?php echo $staff_member['contact']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No staff members found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                    break;

                //===========================
                case 'settings':
                    ?>
                    <!-- Settings Page -->
                    <div class="dashboard-header">
                        <h1><i class="fas fa-cog mr-3"></i>Settings</h1>
                        <p>Manage your account and preferences</p>
                    </div>
                    
                    <?php if($settings_msg): echo $settings_msg; endif; ?>
                    
                    <div class="row">
                        <!-- Reception Profile -->
                        <div class="col-md-6">
                            <div class="settings-card">
                                <div class="settings-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h4>Reception Profile</h4>
                                <div class="form-group">
                                    <label>Reception ID</label>
                                    <input type="text" class="form-control" value="<?php echo $reception_id; ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($reception_name); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($reception_email); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Contact Number</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($reception_contact); ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Change Password -->
                        <div class="col-md-6">
                            <div class="settings-card">
                                <div class="settings-icon">
                                    <i class="fas fa-key"></i>
                                </div>
                                <h4>Change Password</h4>
                                <form method="POST">
                                    <div class="form-group">
                                        <label>Current Password *</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label>New Password *</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Confirm New Password *</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-primary btn-block">
                                        <i class="fas fa-key mr-1"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hospital Information -->
                    <div class="settings-card">
                        <div class="settings-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <h4>Hospital Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Hospital Name</label>
                                    <input type="text" class="form-control" value="<?php echo isset($hospital_settings['hospital_name']) ? $hospital_settings['hospital_name'] : 'Healthcare Hospital'; ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Hospital Phone</label>
                                    <input type="text" class="form-control" value="<?php echo isset($hospital_settings['hospital_phone']) ? $hospital_settings['hospital_phone'] : '+94 11 234 5678'; ?>" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Hospital Address</label>
                            <textarea class="form-control" rows="2" readonly><?php echo isset($hospital_settings['hospital_address']) ? $hospital_settings['hospital_address'] : '123 Medical Street, City, Country'; ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Working Hours</label>
                                    <input type="text" class="form-control" value="<?php echo isset($hospital_settings['working_hours_start']) ? $hospital_settings['working_hours_start'] : '08:00'; ?> - <?php echo isset($hospital_settings['working_hours_end']) ? $hospital_settings['working_hours_end'] : '18:00'; ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Appointment Duration</label>
                                    <input type="text" class="form-control" value="<?php echo isset($hospital_settings['appointment_duration']) ? $hospital_settings['appointment_duration'] : '30'; ?> minutes" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Actions -->
                    <div class="settings-card">
                        <div class="settings-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h4>System Actions</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <a href="logout.php" class="btn btn-danger btn-block">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="index.php" class="btn btn-secondary btn-block">
                                    <i class="fas fa-home mr-2"></i> Back to Home
                                </a>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-info btn-block" onclick="window.print()">
                                    <i class="fas fa-print mr-2"></i> Print Dashboard
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;
            }
            ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Modal functionality
            $('#cancelAppointmentModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const appointmentId = button.data('appointment-id');
                $('#appointmentToCancelId').val(appointmentId);
            });
            
            $('#editPaymentModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const paymentId = button.data('payment-id');
                $('#edit-payment-id').val(paymentId);
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            $('input[name="appdate"]').val(today);
            
            // Set default time to next hour
            const nextHour = new Date();
            nextHour.setHours(nextHour.getHours() + 1);
            nextHour.setMinutes(0);
            nextHour.setSeconds(0);
            $('input[name="apptime"]').val(nextHour.toTimeString().slice(0,5));
            
            // Format NIC input
            $('input[name="nic"]').on('input', function() {
                let value = $(this).val().replace(/[^0-9]/g, '');
                if(value) {
                    $(this).val('NIC' + value);
                }
            });
            
            // Form validation
            $('#add-patient-form').submit(function(e) {
                const password = $('input[name="password"]').val();
                const cpassword = $('input[name="cpassword"]').val();
                
                if(password !== cpassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if(password.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long!');
                    return false;
                }
                
                return true;
            });
        });
        
        // Functions for patient management
        function viewPatient(patientId) {
            window.location.href = 'patient_details.php?id=' + patientId;
        }
    </script>
</body>
</html>