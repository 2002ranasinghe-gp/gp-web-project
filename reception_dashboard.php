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
} else {
    $reception_id = 'REC001';
    $reception_contact = '';
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
// SEARCH FUNCTIONALITY
// ===========================
$search_results = [];
if(isset($_GET['search']) && !empty($_GET['search_term'])){
    $search_term = mysqli_real_escape_string($con, $_GET['search_term']);
    $search_type = $_GET['search_type'] ?? 'patients';
    
    switch($search_type){
        case 'patients':
            $search_query = "SELECT * FROM patreg WHERE 
                           fname LIKE '%$search_term%' OR 
                           lname LIKE '%$search_term%' OR 
                           email LIKE '%$search_term%' OR 
                           contact LIKE '%$search_term%' OR 
                           national_id LIKE '%$search_term%' OR 
                           pid LIKE '%$search_term%'";
            break;
        case 'appointments':
            $search_query = "SELECT a.*, p.fname, p.lname FROM appointmenttb a 
                           LEFT JOIN patreg p ON a.pid = p.pid 
                           WHERE p.fname LIKE '%$search_term%' OR 
                           p.lname LIKE '%$search_term%' OR 
                           a.doctor LIKE '%$search_term%' OR 
                           a.national_id LIKE '%$search_term%'";
            break;
        case 'payments':
            $search_query = "SELECT * FROM paymenttb WHERE 
                           patient_name LIKE '%$search_term%' OR 
                           doctor LIKE '%$search_term%' OR 
                           national_id LIKE '%$search_term%' OR 
                           pay_status LIKE '%$search_term%'";
            break;
        default:
            $search_query = "";
    }
    
    if(!empty($search_query)){
        $search_result = mysqli_query($con, $search_query);
        if($search_result){
            while($row = mysqli_fetch_assoc($search_result)){
                $search_results[] = $row;
            }
        }
    }
}

// ===========================
// GET DATA FROM DATABASE (WITH CORRECT TABLE STRUCTURES)
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

// Get schedules - CORRECTED to match your table structure
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
    <style>
        :root {
            --primary-blue: #1e88e5;
            --light-blue: #e3f2fd;
            --medium-blue: #90caf9;
            --dark-blue: #1565c0;
            --accent-blue: #42a5f5;
            --text-dark: #37474f;
            --text-light: #607d8b;
            --white: #ffffff;
            --light-gray: #f5f5f5;
            --success-green: #4caf50;
            --warning-orange: #ff9800;
            --danger-red: #f44336;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            margin:0; 
            padding:0; 
            min-height: 100vh;
            color: var(--text-dark);
        }
        
        /* Navbar */
        .navbar { 
            background: var(--primary-blue); 
            padding: 0.8rem 1rem; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        .navbar .navbar-brand { 
            font-weight:bold; 
            color: var(--white); 
            font-size: 1.5rem;
        }
        .navbar .welcome { 
            margin-left:auto; 
            color: var(--white); 
            font-weight: 500;
        }
        .navbar .welcome a { 
            color: var(--light-blue); 
            text-decoration:none; 
            margin-left:10px;
            transition: color 0.3s;
        }
        .navbar .welcome a:hover {
            color: var(--white);
        }
        
        /* Sidebar */
        .sidebar { 
            width: 250px; 
            background: var(--primary-blue); 
            height: 100vh; 
            position: fixed; 
            top: 56px; 
            left:0; 
            padding-top: 20px; 
            transition: all 0.3s;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 999;
        }
        .sidebar ul { 
            list-style:none; 
            padding:0; 
            margin: 0;
        }
        .sidebar ul li { 
            padding: 15px 25px; 
            color: var(--white); 
            cursor: pointer; 
            transition: all 0.3s;
            border-left: 4px solid transparent;
            margin: 5px 10px;
            border-radius: 0 8px 8px 0;
        }
        .sidebar ul li i { 
            margin-right: 12px; 
            width: 20px;
            text-align: center;
        }
        .sidebar ul li:hover, .sidebar ul li.active { 
            background: var(--dark-blue);
            border-left: 4px solid var(--medium-blue);
            transform: translateX(5px);
        }
        .sidebar ul li a { 
            color: var(--white); 
            text-decoration:none; 
            display:block;
            font-weight: 500;
        }
        
        /* Main Content */
        .main { 
            margin-left: 250px; 
            padding: 90px 30px 30px 30px;
            min-height: calc(100vh - 56px);
        }
        
        /* Dashboard Cards */
        .stats-card { 
            background: var(--white);
            color: var(--text-dark); 
            border-radius: 15px; 
            padding: 25px; 
            text-align: center; 
            box-shadow: 0 8px 25px rgba(30, 136, 229, 0.15);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            border-left: 5px solid var(--accent-blue);
            margin-bottom: 20px;
        }
        .stats-card i { 
            margin-bottom: 15px;
            font-size: 2.5rem;
            color: var(--primary-blue);
        }
        .stats-card:hover { 
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(30, 136, 229, 0.2);
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
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(30, 136, 229, 0.08);
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
            border: 2px solid transparent;
            margin-bottom: 20px;
        }
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(30, 136, 229, 0.12);
            border-color: var(--primary-blue);
        }
        .quick-action-card i {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--primary-blue);
        }
        
        /* Form Cards */
        .form-card {
            background: var(--white);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(30, 136, 229, 0.1);
            margin-bottom: 20px;
        }
        .form-card-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            margin: -25px -25px 25px -25px;
        }
        
        /* Tables */
        .table-container { 
            background: var(--white); 
            padding: 25px; 
            border-radius: 15px; 
            box-shadow: 0 8px 25px rgba(30, 136, 229, 0.1);
            margin-top: 20px; 
            overflow-x: auto;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(30, 136, 229, 0.1);
        }
        .table thead th {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        .table tbody td {
            padding: 12px 15px;
            border-color: #e3f2fd;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: var(--light-blue);
            transition: all 0.2s ease;
        }
        
        /* Status Badges */
        .badge-primary { background-color: var(--primary-blue); }
        .badge-success { background-color: var(--success-green); }
        .badge-warning { background-color: var(--warning-orange); }
        .badge-danger { background-color: var(--danger-red); }
        .badge-info { background-color: var(--accent-blue); }
        
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
            box-shadow: 0 5px 15px rgba(30, 136, 229, 0.3);
        }
        
        /* Alerts */
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        /* Search Bar */
        .search-bar {
            background: var(--white);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(30, 136, 229, 0.1);
            margin-bottom: 20px;
        }
        .search-bar input {
            border: 2px solid var(--light-blue);
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        .search-bar input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(30, 136, 229, 0.25);
        }
        
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
        
        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid var(--light-blue);
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link {
            color: var(--text-light);
            border: none;
            padding: 12px 25px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .nav-tabs .nav-link:hover {
            color: var(--primary-blue);
            border: none;
        }
        .nav-tabs .nav-link.active {
            color: var(--primary-blue);
            border: none;
            border-bottom: 3px solid var(--primary-blue);
            background: transparent;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding-top: 20px;
            }
            .sidebar ul li span {
                display: none;
            }
            .sidebar ul li i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            .main {
                margin-left: 70px;
                padding: 90px 15px 15px 15px;
            }
            .dashboard-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <a class="navbar-brand" href="reception_dashboard.php?page=dashboard">
        <i class="fas fa-hospital-alt mr-2"></i>Healthcare HMS
    </a>
    <div class="welcome">
        <i class="fas fa-user-circle mr-2"></i>Welcome, <?php echo htmlspecialchars($reception_name); ?> 
        | <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar">
    <ul>
        <li class="<?php if($page=='dashboard') echo 'active'; ?>">
            <a href="reception_dashboard.php?page=dashboard">
                <i class="fas fa-tachometer-alt"></i><span> Dashboard</span>
            </a>
        </li>
        <li class="<?php if($page=='patients') echo 'active'; ?>">
            <a href="reception_dashboard.php?page=patients">
                <i class="fas fa-user-injured"></i><span> Patients</span>
            </a>
        </li>
        <li class="<?php if($page=='appointments') echo 'active'; ?>">
            <a href="reception_dashboard.php?page=appointments">
                <i class="fas fa-calendar-check"></i><span> Appointments</span>
            </a>
        </li>
        <li class="<?php if($page=='schedule') echo 'active'; ?>">
            <a href="reception_dashboard.php?page=schedule">
                <i class="fas fa-calendar-alt"></i><span> Schedule</span>
            </a>
        </li>
        <li class="<?php if($page=='payment') echo 'active'; ?>">
            <a href="reception_dashboard.php?page=payment">
                <i class="fas fa-credit-card"></i><span> Payment</span>
            </a>
        </li>
        <li class="<?php if($page=='staff') echo 'active'; ?>">
            <a href="reception_dashboard.php?page=staff">
                <i class="fas fa-users"></i><span> Staff</span>
            </a>
        </li>
        <li class="<?php if($page=='settings') echo 'active'; ?>">
            <a href="reception_dashboard.php?page=settings">
                <i class="fas fa-cog"></i><span> Settings</span>
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main">
    <?php
    switch($page){
        //===========================
        case 'patients':
            ?>
            <!-- Patients Page -->
            <div class="dashboard-header">
                <h1><i class="fas fa-user-injured mr-3"></i>Patient Management</h1>
                <p>Register and manage patient records</p>
            </div>
            
            <?php if($patient_msg): echo $patient_msg; endif; ?>
            
            <!-- Search Bar -->
            <div class="search-bar">
                <form method="GET" class="form-inline">
                    <input type="hidden" name="page" value="patients">
                    <input type="text" name="search_term" class="form-control mr-2" style="flex: 1;" 
                           placeholder="Search patients by name, ID, NIC, or contact..."
                           value="<?php echo isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : ''; ?>">
                    <select name="search_type" class="form-control mr-2" style="width: auto;">
                        <option value="patients" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'patients') ? 'selected' : ''; ?>>Patients</option>
                    </select>
                    <button type="submit" name="search" class="btn btn-primary mr-2">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="reception_dashboard.php?page=patients" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
            </div>
            
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
            <div class="table-container">
                <h4><i class="fas fa-list mr-2"></i>Patients List</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
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
                            <?php if(!empty($search_results) && isset($_GET['search'])): ?>
                                <?php foreach($search_results as $patient): ?>
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
                                        <button class="btn btn-sm btn-warning" onclick="editPatient(<?php echo $patient['pid']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php elseif(count($patients) > 0): ?>
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
                                        <button class="btn btn-sm btn-warning" onclick="editPatient(<?php echo $patient['pid']; ?>)">
                                            <i class="fas fa-edit"></i>
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
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Medical History</label>
                                            <textarea class="form-control" name="medicalHistory" rows="1"></textarea>
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
            
            <!-- Search Bar -->
            <div class="search-bar">
                <form method="GET" class="form-inline">
                    <input type="hidden" name="page" value="appointments">
                    <input type="text" name="search_term" class="form-control mr-2" style="flex: 1;" 
                           placeholder="Search appointments by patient name, doctor, or NIC..."
                           value="<?php echo isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : ''; ?>">
                    <select name="search_type" class="form-control mr-2" style="width: auto;">
                        <option value="appointments" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'appointments') ? 'selected' : ''; ?>>Appointments</option>
                    </select>
                    <button type="submit" name="search" class="btn btn-primary mr-2">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="reception_dashboard.php?page=appointments" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
            </div>
            
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
            <div class="table-container">
                <h4><i class="fas fa-list mr-2"></i>Appointments List</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
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
                            <?php if(!empty($search_results) && isset($_GET['search'])): ?>
                                <?php foreach($search_results as $app): ?>
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
                            <?php elseif(count($appointments) > 0): ?>
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
            
            <!-- Search Bar -->
            <div class="search-bar">
                <form method="GET" class="form-inline">
                    <input type="hidden" name="page" value="payment">
                    <input type="text" name="search_term" class="form-control mr-2" style="flex: 1;" 
                           placeholder="Search payments by patient name, NIC, or doctor..."
                           value="<?php echo isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : ''; ?>">
                    <select name="search_type" class="form-control mr-2" style="width: auto;">
                        <option value="payments" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'payments') ? 'selected' : ''; ?>>Payments</option>
                    </select>
                    <button type="submit" name="search" class="btn btn-primary mr-2">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="reception_dashboard.php?page=payment" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
            </div>
            
            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-money-bill-wave"></i>
                        <h3><?php echo $today_payments; ?></h3>
                        <p>Today's Payments</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-clock"></i>
                        <h3><?php echo $pending_payments; ?></h3>
                        <p>Pending Payments</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-check-circle"></i>
                        <h3><?php echo mysqli_num_rows(mysqli_query($con, "SELECT * FROM paymenttb WHERE pay_status = 'Paid'")); ?></h3>
                        <p>Completed Payments</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <i class="fas fa-chart-line"></i>
                        <h3>Rs. <?php 
                            $total_revenue = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(fees) as total FROM paymenttb WHERE pay_status = 'Paid'"));
                            echo number_format($total_revenue['total'] ?? 0, 2);
                        ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
            
            <!-- Payments List -->
            <div class="table-container">
                <h4><i class="fas fa-list mr-2"></i>Payments List</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
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
                            <?php if(!empty($search_results) && isset($_GET['search'])): ?>
                                <?php foreach($search_results as $pay): ?>
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
                            <?php elseif(count($payments) > 0): ?>
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
            <div class="table-container">
                <h4><i class="fas fa-list mr-2"></i>Staff Schedules</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
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
            <div class="table-container">
                <h4><i class="fas fa-list mr-2"></i>Staff Directory</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
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
                    <div class="form-card">
                        <div class="form-card-header">
                            <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Reception Profile</h5>
                        </div>
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
                    <div class="form-card">
                        <div class="form-card-header">
                            <h5 class="mb-0"><i class="fas fa-key mr-2"></i>Change Password</h5>
                        </div>
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
            <div class="form-card">
                <div class="form-card-header">
                    <h5 class="mb-0"><i class="fas fa-hospital mr-2"></i>Hospital Information</h5>
                </div>
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
            <div class="form-card">
                <div class="form-card-header">
                    <h5 class="mb-0"><i class="fas fa-tools mr-2"></i>System Actions</h5>
                </div>
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

        //===========================
        default: // Dashboard
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
                        <i class="fas fa-user-injured"></i>
                        <h3><?php echo $total_patients; ?></h3>
                        <p>Total Patients</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <i class="fas fa-calendar-check"></i>
                        <h3><?php echo $total_appointments; ?></h3>
                        <p>Total Appointments</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <i class="fas fa-users"></i>
                        <h3><?php echo $total_staff; ?></h3>
                        <p>Staff Members</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <i class="fas fa-user-md"></i>
                        <h3><?php echo $total_doctors; ?></h3>
                        <p>Doctors</p>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <i class="fas fa-calendar-day"></i>
                        <h3><?php echo $today_appointments; ?></h3>
                        <p>Today's Appointments</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <i class="fas fa-credit-card"></i>
                        <h3><?php echo $today_payments; ?></h3>
                        <p>Today's Payments</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <i class="fas fa-clock"></i>
                        <h3><?php echo $pending_payments; ?></h3>
                        <p>Pending Payments</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <i class="fas fa-hospital"></i>
                        <h3><?php echo date('Y'); ?></h3>
                        <p>Current Year</p>
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
            <div class="table-container mt-5">
                <h4><i class="fas fa-history mr-2"></i>Recent Activity</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
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
    }
    ?>
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
    
    function editPatient(patientId) {
        window.location.href = 'edit_patient.php?id=' + patientId;
    }
</script>
</body>
</html>