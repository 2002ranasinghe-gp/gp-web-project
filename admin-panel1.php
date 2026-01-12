<!DOCTYPE html>
<?php
// ===========================
// DATABASE CONNECTION
// ===========================
session_start();
$con = mysqli_connect("localhost", "root", "", "myhmsdb");

if(!$con){
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if required tables exist, create if not
checkAndCreateTables($con);

// ===========================
// MESSAGES VARIABLES
// ===========================
$patient_msg = "";
$doctor_msg = "";
$staff_msg = "";
$payment_msg = "";
$appointment_msg = "";

// ===========================
// ADD PATIENT
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
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
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
                $_SESSION['success'] = "Patient added successfully!";
            } else {
                $patient_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
            }
        }
    }
}

// ===========================
// ADD DOCTOR
// ===========================
if(isset($_POST['add_doctor'])){
    $doctorId = mysqli_real_escape_string($con, $_POST['doctorId']);
    $doctor = mysqli_real_escape_string($con, $_POST['doctor']);
    $special = mysqli_real_escape_string($con, $_POST['special']);
    $demail = mysqli_real_escape_string($con, $_POST['demail']);
    $dpassword = password_hash($_POST['dpassword'], PASSWORD_DEFAULT);
    $docFees = mysqli_real_escape_string($con, $_POST['docFees']);
    $doctorContact = mysqli_real_escape_string($con, $_POST['doctorContact']);
    
    // Check if doctor exists
    $check = mysqli_query($con, "SELECT * FROM doctb WHERE email='$demail' OR id='$doctorId'");
    if(mysqli_num_rows($check) > 0){
        $doctor_msg = "<div class='alert alert-danger'>❌ Doctor with this email or ID already exists!</div>";
    } else {
        $query = "INSERT INTO doctb (id, username, spec, email, password, docFees, contact) 
                  VALUES ('$doctorId', '$doctor', '$special', '$demail', '$dpassword', '$docFees', '$doctorContact')";
        
        if(mysqli_query($con, $query)){
            $doctor_msg = "<div class='alert alert-success'>✅ Doctor added successfully! Doctor ID: $doctorId</div>";
            $_SESSION['success'] = "Doctor added successfully!";
        } else {
            $doctor_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
        }
    }
}

// ===========================
// DELETE DOCTOR
// ===========================
if(isset($_POST['delete_doctor'])){
    $doctorId = mysqli_real_escape_string($con, $_POST['doctorId']);
    
    // Check if doctor exists
    $check = mysqli_query($con, "SELECT * FROM doctb WHERE id='$doctorId'");
    if(mysqli_num_rows($check) == 0){
        $doctor_msg = "<div class='alert alert-danger'>❌ No doctor found with this ID!</div>";
    } else {
        $delete = mysqli_query($con, "DELETE FROM doctb WHERE id='$doctorId'");
        if($delete){
            $doctor_msg = "<div class='alert alert-success'>✅ Doctor deleted successfully!</div>";
            $_SESSION['success'] = "Doctor deleted successfully!";
        } else {
            $doctor_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
        }
    }
}

// ===========================
// ADD STAFF
// ===========================
if(isset($_POST['add_staff'])){
    $staffId = mysqli_real_escape_string($con, $_POST['staffId']);
    $staff = mysqli_real_escape_string($con, $_POST['staff']);
    $role = mysqli_real_escape_string($con, $_POST['role']);
    $semail = mysqli_real_escape_string($con, $_POST['semail']);
    $scontact = mysqli_real_escape_string($con, $_POST['scontact']);
    $spassword = password_hash($_POST['spassword'], PASSWORD_DEFAULT);
    
    // Check if staff exists
    $check = mysqli_query($con, "SELECT * FROM stafftb WHERE email='$semail' OR id='$staffId'");
    if(mysqli_num_rows($check) > 0){
        $staff_msg = "<div class='alert alert-danger'>❌ Staff member with this email or ID already exists!</div>";
    } else {
        $query = "INSERT INTO stafftb (id, name, role, email, contact, password) 
                  VALUES ('$staffId', '$staff', '$role', '$semail', '$scontact', '$spassword')";
        
        if(mysqli_query($con, $query)){
            $staff_msg = "<div class='alert alert-success'>✅ Staff member added successfully! Staff ID: $staffId</div>";
            $_SESSION['success'] = "Staff added successfully!";
        } else {
            $staff_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
        }
    }
}

// ===========================
// CANCEL APPOINTMENT
// ===========================
if(isset($_POST['cancel_appointment'])){
    $appointmentId = mysqli_real_escape_string($con, $_POST['appointmentId']);
    $reason = mysqli_real_escape_string($con, $_POST['reason']);
    $cancelledBy = mysqli_real_escape_string($con, $_POST['cancelledBy']);
    
    $query = "UPDATE appointmenttb SET 
              appointmentStatus='cancelled',
              cancelledBy='$cancelledBy',
              cancellationReason='$reason',
              userStatus=0 
              WHERE ID='$appointmentId'";
    
    if(mysqli_query($con, $query)){
        $appointment_msg = "<div class='alert alert-success'>✅ Appointment cancelled successfully!</div>";
        $_SESSION['success'] = "Appointment cancelled!";
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
        $_SESSION['success'] = "Payment updated!";
    } else {
        $payment_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// GET DATA FROM DATABASE
// ===========================
$patients = [];
$doctors = [];
$appointments = [];
$prescriptions = [];
$payments = [];
$staff = [];
$schedules = [];
$rooms = [];

// Get patients
$patient_result = mysqli_query($con, "SELECT * FROM patreg ORDER BY pid DESC");
if($patient_result){
    while($row = mysqli_fetch_assoc($patient_result)){
        $patients[] = $row;
    }
}

// Get doctors
$doctor_result = mysqli_query($con, "SELECT * FROM doctb ORDER BY username");
if($doctor_result){
    while($row = mysqli_fetch_assoc($doctor_result)){
        $doctors[] = $row;
    }
}

// Get appointments
$appointment_result = mysqli_query($con, "SELECT * FROM appointmenttb ORDER BY appdate DESC, apptime DESC");
if($appointment_result){
    while($row = mysqli_fetch_assoc($appointment_result)){
        $appointments[] = $row;
    }
}

// Get prescriptions
$prescription_result = mysqli_query($con, "SELECT * FROM prestb ORDER BY appdate DESC");
if($prescription_result){
    while($row = mysqli_fetch_assoc($prescription_result)){
        $prescriptions[] = $row;
    }
}

// Get payments
$payment_result = mysqli_query($con, "SELECT * FROM paymenttb ORDER BY pay_date DESC");
if($payment_result){
    while($row = mysqli_fetch_assoc($payment_result)){
        $payments[] = $row;
    }
}

// Get staff
$staff_result = mysqli_query($con, "SELECT * FROM stafftb ORDER BY role");
if($staff_result){
    while($row = mysqli_fetch_assoc($staff_result)){
        $staff[] = $row;
    }
}

// Get schedules
$schedule_result = mysqli_query($con, "SELECT * FROM scheduletb ORDER BY day, shift");
if($schedule_result){
    while($row = mysqli_fetch_assoc($schedule_result)){
        $schedules[] = $row;
    }
}

// Get rooms
$room_result = mysqli_query($con, "SELECT * FROM roomtb ORDER BY room_no, bed_no");
if($room_result){
    while($row = mysqli_fetch_assoc($room_result)){
        $rooms[] = $row;
    }
}

// Get dashboard counts
$total_doctors = count($doctors);
$total_patients = count($patients);
$total_appointments = count($appointments);
$total_staff = count($staff);
$today_appointments = 0;
$today = date('Y-m-d');

foreach($appointments as $app){
    if($app['appdate'] == $today){
        $today_appointments++;
    }
}

// ===========================
// FUNCTION TO CHECK/CREATE TABLES
// ===========================
function checkAndCreateTables($con){
    $tables = [
        'patreg' => "CREATE TABLE IF NOT EXISTS patreg (
            pid INT PRIMARY KEY AUTO_INCREMENT,
            fname VARCHAR(50) NOT NULL,
            lname VARCHAR(50) NOT NULL,
            gender VARCHAR(10),
            dob DATE,
            email VARCHAR(100) UNIQUE NOT NULL,
            contact VARCHAR(15) NOT NULL,
            address TEXT,
            emergencyContact VARCHAR(15),
            national_id VARCHAR(20) UNIQUE,
            password VARCHAR(255) NOT NULL,
            reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'doctb' => "CREATE TABLE IF NOT EXISTS doctb (
            id VARCHAR(20) PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            spec VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            docFees DECIMAL(10,2) NOT NULL,
            contact VARCHAR(15),
            reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'appointmenttb' => "CREATE TABLE IF NOT EXISTS appointmenttb (
            ID INT PRIMARY KEY AUTO_INCREMENT,
            pid INT NOT NULL,
            national_id VARCHAR(20),
            fname VARCHAR(50),
            lname VARCHAR(50),
            gender VARCHAR(10),
            email VARCHAR(100),
            contact VARCHAR(15),
            doctor VARCHAR(50) NOT NULL,
            docFees DECIMAL(10,2) NOT NULL,
            appdate DATE NOT NULL,
            apptime TIME NOT NULL,
            userStatus INT DEFAULT 1,
            doctorStatus INT DEFAULT 1,
            appointmentStatus VARCHAR(20) DEFAULT 'active',
            cancelledBy VARCHAR(20),
            cancellationReason TEXT,
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pid) REFERENCES patreg(pid) ON DELETE CASCADE
        )",
        
        'prestb' => "CREATE TABLE IF NOT EXISTS prestb (
            id INT PRIMARY KEY AUTO_INCREMENT,
            doctor VARCHAR(50) NOT NULL,
            pid INT NOT NULL,
            appointment_id INT,
            fname VARCHAR(50),
            lname VARCHAR(50),
            national_id VARCHAR(20),
            appdate DATE,
            apptime TIME,
            disease VARCHAR(100),
            allergy VARCHAR(100),
            prescription TEXT,
            emailStatus VARCHAR(50) DEFAULT 'Not Sent',
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pid) REFERENCES patreg(pid) ON DELETE CASCADE
        )",
        
        'paymenttb' => "CREATE TABLE IF NOT EXISTS paymenttb (
            id INT PRIMARY KEY AUTO_INCREMENT,
            pid INT NOT NULL,
            appointment_id INT,
            national_id VARCHAR(20),
            patient_name VARCHAR(100),
            doctor VARCHAR(50) NOT NULL,
            fees DECIMAL(10,2) NOT NULL,
            pay_date DATE NOT NULL,
            pay_status VARCHAR(20) DEFAULT 'Pending',
            payment_method VARCHAR(50),
            receipt_no VARCHAR(50),
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pid) REFERENCES patreg(pid) ON DELETE CASCADE
        )",
        
        'stafftb' => "CREATE TABLE IF NOT EXISTS stafftb (
            id VARCHAR(20) PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            role VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            contact VARCHAR(15) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'scheduletb' => "CREATE TABLE IF NOT EXISTS scheduletb (
            id INT PRIMARY KEY AUTO_INCREMENT,
            staff_name VARCHAR(50) NOT NULL,
            role VARCHAR(50) NOT NULL,
            day VARCHAR(20) NOT NULL,
            shift VARCHAR(20) NOT NULL,
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'roomtb' => "CREATE TABLE IF NOT EXISTS roomtb (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_no VARCHAR(10) NOT NULL,
            bed_no VARCHAR(10) NOT NULL,
            type VARCHAR(20) NOT NULL,
            status VARCHAR(20) DEFAULT 'Available',
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach($tables as $table_name => $create_sql){
        $check = mysqli_query($con, "SHOW TABLES LIKE '$table_name'");
        if(mysqli_num_rows($check) == 0){
            mysqli_query($con, $create_sql);
        }
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
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Heth Care Hospital</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #342ac1;
            --primary-gradient: linear-gradient(to right, #3931af, #00c6ff);
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            padding-top: 70px;
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 16px;
        }
        
        .bg-primary { 
            background: var(--primary-gradient);
        }
        
        .navbar-brand { 
            font-weight: bold;
            font-size: 1.8rem;
        }
        
        .list-group-item {
            font-size: 1.1rem;
        }
        
        .list-group-item.active {
            background-color: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        
        .tab-content {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
            min-height: 600px;
        }
        
        .table {
            width: 100%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            font-size: 1.1rem;
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
            font-size: 1.2rem;
        }
        
        h3, h4, h5 {
            font-weight: 600;
        }
        
        h3 {
            font-size: 2.2rem;
        }
        
        h4 {
            font-size: 1.8rem;
        }
        
        h5 {
            font-size: 1.5rem;
        }
        
        .dashboard-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') no-repeat center center/cover;
            opacity: 0.05;
            z-index: 0;
            border-radius: 10px;
        }
        
        .dashboard-content {
            position: relative;
            z-index: 1;
            padding: 20px;
        }
        
        .dash-card {
            height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-radius: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .dash-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .dash-icon {
            width: 50px;
            margin-bottom: 10px;
            filter: brightness(0) invert(1);
        }
        
        .card-title {
            font-size: 1.3rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .card-value {
            font-size: 3.5rem;
            font-weight: bold;
        }
        
        .stats-card {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .quick-action-btn {
            flex: 1;
            min-width: 120px;
            padding: 10px;
            border-radius: 8px;
            background: white;
            border: 1px solid #e0e0e0;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: var(--dark);
            font-size: 1rem;
        }
        
        .quick-action-btn:hover {
            background: var(--primary);
            color: white;
            text-decoration: none;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-cancelled-patient {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-cancelled-doctor {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .status-cancelled-admin {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .status-available {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-occupied {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-sent {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .status-sms-sent {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-sent-external {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .recent-activity {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
            font-size: 1rem;
        }
        
        .activity-time {
            font-size: 0.9rem;
            color: var(--secondary);
        }
        
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .action-btn {
            margin: 2px;
            font-size: 1rem;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .pharmacy-type-btn {
            width: 100%;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .form-control {
            font-size: 1.1rem;
        }
        
        label {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .btn {
            font-size: 1.1rem;
        }
        
        .modal-title {
            font-size: 1.5rem;
        }
        
        .modal-body {
            font-size: 1.1rem;
        }
        
        .patient-registration-card {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #2196f3, #21cbf3);
            color: white;
            padding: 15px 20px;
            font-weight: bold;
            font-size: 1.3rem;
        }
        
        .generated-nic {
            background-color: #f0f8ff;
            border: 1px solid #b3e0ff;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            color: #0066cc;
            margin-top: 10px;
            font-size: 1.2rem;
        }
        
        .search-container {
            margin-bottom: 20px;
        }
        
        .search-bar {
            position: relative;
        }
        
        .search-bar .form-control {
            padding-right: 40px;
            font-size: 1.1rem;
        }
        
        .search-bar .search-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.2rem;
        }
        
        .search-options {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .search-option-btn {
            flex: 1;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .search-option-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .search-option-btn:hover {
            background: #e9ecef;
        }
        
        .search-option-btn.active:hover {
            background: var(--primary);
        }
        
        .payment-details-modal .detail-row {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .payment-details-modal .detail-label {
            font-weight: 600;
            color: #495057;
        }
        
        .payment-details-modal .detail-value {
            color: #212529;
        }
        
        @media (max-width: 992px) {
            .dash-card {
                width: 48% !important;
            }
            
            .card-value {
                font-size: 2.8rem;
            }
            
            body {
                font-size: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .dash-card {
                width: 100% !important;
            }
            
            .quick-action-btn {
                min-width: 100%;
            }
            
            .card-value {
                font-size: 2.5rem;
            }
            
            body {
                font-size: 14px;
            }
            
            h3 {
                font-size: 1.8rem;
            }
            
            h4 {
                font-size: 1.5rem;
            }
            
            .search-options {
                flex-direction: column;
            }
        }
    </style>
    <script>
        // Global variables
        let currentPaymentId = null;
        let currentPrescriptionId = null;
        let currentPatientContact = '';
        let currentAppointmentIdToCancel = null;
        let currentPaymentSearchMode = 'patientId';
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Update dashboard counts with real data from PHP
            updateDashboardCounts();
            
            // Set up form validations
            setupFormValidations();
            
            // Set up modal functionality
            setupModalFunctionality();
            
            // Populate doctor select for delete modal
            populateDoctorSelect();
            
            // Set initial payment search mode
            setPaymentSearchMode('patientId');
            
            // Auto-refresh success messages
            autoRefreshMessages();
        });
        
        // Update dashboard counts with real data
        function updateDashboardCounts() {
            // These values are set by PHP
            document.getElementById('total-doctors').textContent = '<?php echo $total_doctors; ?>';
            document.getElementById('total-patients').textContent = '<?php echo $total_patients; ?>';
            document.getElementById('total-appointments').textContent = '<?php echo $total_appointments; ?>';
            document.getElementById('total-staff').textContent = '<?php echo $total_staff; ?>';
            
            // Initialize charts with real data
            initializeCharts();
        }
        
        // Function to format NIC input
        function formatNIC() {
            const nicInput = document.getElementById('patientNIC');
            const nicDisplay = document.getElementById('generatedNICDisplay');
            
            let nicValue = nicInput.value.replace(/[^0-9]/g, '');
            nicInput.value = nicValue;
            
            if (nicValue) {
                nicDisplay.innerHTML = `<strong>NIC will be:</strong> NIC${nicValue}`;
            } else {
                nicDisplay.innerHTML = 'Enter NIC number above';
            }
        }
        
        // Function to check patient password match
        function checkPatientPassword() {
            let pass = document.getElementById('patientPassword').value;
            let cpass = document.getElementById('patientConfirmPassword').value;
            const message = document.getElementById('patientPasswordMessage');
            
            if (pass === cpass) {
                message.style.color = '#28a745';
                message.innerText = 'Passwords match';
            } else {
                message.style.color = '#dc3545';
                message.innerText = 'Passwords do not match';
            }
        }
        
        // Function to check doctor password match
        function checkDoctorPassword() {
            let pass = document.getElementById('dpassword').value;
            let cpass = document.getElementById('cdpassword').value;
            const message = document.getElementById('message');
            
            if (pass === cpass) {
                message.style.color = '#28a745';
                message.innerText = 'Passwords match';
            } else {
                message.style.color = '#dc3545';
                message.innerText = 'Passwords do not match';
            }
        }
        
        // Alpha only validation for names
        function alphaOnly(event) {
            let key = event.keyCode;
            return ((key >= 65 && key <= 90) || (key >= 97 && key <= 122) || key == 8 || key == 32);
        }
        
        // Function to setup form validations
        function setupFormValidations() {
            // Add Patient form validation
            const addPatientForm = document.getElementById('add-patient-form');
            if(addPatientForm) {
                addPatientForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('patientPassword').value;
                    const confirmPassword = document.getElementById('patientConfirmPassword').value;
                    
                    if(password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        return false;
                    }
                    
                    const nic = document.getElementById('patientNIC').value;
                    if(!nic || nic.trim() === '') {
                        e.preventDefault();
                        alert('Please enter NIC number!');
                        return false;
                    }
                    
                    return true;
                });
            }
            
            // Add Doctor form validation
            const addDoctorForm = document.getElementById('add-doctor-form');
            if(addDoctorForm) {
                addDoctorForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('dpassword').value;
                    const confirmPassword = document.getElementById('cdpassword').value;
                    
                    if(password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        return false;
                    }
                    
                    return true;
                });
            }
        }
        
        // Function to setup modal functionality
        function setupModalFunctionality() {
            // Cancel appointment modal
            $('#cancelAppointmentModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const appointmentId = button.data('appointment-id');
                currentAppointmentIdToCancel = appointmentId;
            });
            
            // Edit payment modal
            $('#editPaymentModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const paymentId = button.data('payment-id');
                currentPaymentId = paymentId;
            });
            
            // Payment status change listener
            const editPaymentStatus = document.getElementById('edit-payment-status');
            if(editPaymentStatus) {
                editPaymentStatus.addEventListener('change', function() {
                    togglePaymentMethodSection(this.value);
                });
            }
        }
        
        // Function to toggle payment method section
        function togglePaymentMethodSection(status) {
            const methodSection = document.getElementById('payment-method-section');
            if (status === 'Paid') {
                methodSection.style.display = 'block';
            } else {
                methodSection.style.display = 'none';
            }
        }
        
        // Function to populate doctor select dropdown
        function populateDoctorSelect() {
            const select = document.getElementById('doctor-select');
            if(select) {
                // This should be populated from database via AJAX or PHP
                // For now, we'll do it with PHP inline
            }
        }
        
        // Function to set payment search mode
        function setPaymentSearchMode(mode) {
            currentPaymentSearchMode = mode;
            
            // Update active button
            const buttons = document.querySelectorAll('.search-option-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            if (mode === 'patientId') {
                document.querySelector('.search-option-btn:nth-child(1)').classList.add('active');
                document.getElementById('patientId-search-form').style.display = 'block';
                document.getElementById('nic-search-form').style.display = 'none';
                showAllPayments();
            } else if (mode === 'nic') {
                document.querySelector('.search-option-btn:nth-child(2)').classList.add('active');
                document.getElementById('patientId-search-form').style.display = 'none';
                document.getElementById('nic-search-form').style.display = 'block';
                document.getElementById('payment-nic-search').value = '';
                showAllPayments();
            } else if (mode === 'all') {
                document.querySelector('.search-option-btn:nth-child(3)').classList.add('active');
                document.getElementById('patientId-search-form').style.display = 'none';
                document.getElementById('nic-search-form').style.display = 'none';
                showAllPayments();
            }
        }
        
        // Function to show all payments in table
        function showAllPayments() {
            const tbody = document.getElementById('payments-table-body');
            if(tbody) {
                const rows = tbody.getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    rows[i].style.display = '';
                }
            }
        }
        
        // Function to filter patients by NIC
        function filterPatientsByNIC() {
            const input = document.getElementById('patient-search');
            const filter = input.value.toUpperCase();
            const tbody = document.getElementById('patients-table-body');
            
            if(!tbody) return;
            
            const rows = tbody.getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                if (cells.length >= 8) {
                    const nicCell = cells[7];
                    if (nicCell) {
                        let nicText = (nicCell.textContent || nicCell.innerText).toUpperCase();
                        let nicWithoutPrefix = nicText.replace('NIC', '');
                        
                        if (nicText.indexOf(filter) > -1 || nicWithoutPrefix.indexOf(filter) > -1) {
                            found = true;
                        }
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }
        
        // Function to filter payments by NIC
        function filterPaymentsByNIC() {
            const input = document.getElementById('payment-nic-search');
            const filter = input.value.toUpperCase();
            const tbody = document.getElementById('payments-table-body');
            
            if(!tbody) return;
            
            const rows = tbody.getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                if (cells.length >= 5) {
                    const nicCell = cells[4];
                    if (nicCell) {
                        let nicText = (nicCell.textContent || nicCell.innerText).toUpperCase();
                        let nicWithoutPrefix = nicText.replace('NIC', '');
                        
                        if (nicText.indexOf(filter) > -1 || nicWithoutPrefix.indexOf(filter) > -1) {
                            found = true;
                        }
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }
        
        // Function to search payments by patient ID
        function searchPaymentsByPatient() {
            const patientId = document.getElementById('payment-patient-id').value;
            const tbody = document.getElementById('payments-table-body');
            
            if(!tbody || !patientId) {
                showAllPayments();
                return;
            }
            
            const rows = tbody.getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                if (cells.length > 1 && cells[1].textContent == patientId) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
        
        // Function to export table to CSV
        function exportTable(tableBodyId, filename) {
            const table = document.getElementById(tableBodyId);
            if(!table) return;
            
            const rows = table.getElementsByTagName('tr');
            let csv = [];
            
            // Get headers
            const headerCells = table.parentNode.getElementsByTagName('thead')[0].getElementsByTagName('th');
            const headerRow = [];
            for (let i = 0; i < headerCells.length; i++) {
                if(headerCells[i].innerText !== 'Actions') {
                    headerRow.push(headerCells[i].innerText);
                }
            }
            csv.push(headerRow.join(','));
            
            // Get data
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td');
                
                for (let j = 0; j < cols.length; j++) {
                    const colText = cols[j].innerText;
                    if(!colText.includes('View') && !colText.includes('Edit') && !colText.includes('Delete') && !colText.includes('Cancel') && !colText.includes('Send')) {
                        row.push(cols[j].innerText);
                    }
                }
                
                if(row.length > 0) {
                    csv.push(row.join(','));
                }
            }
            
            // Download CSV
            const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            const downloadLink = document.createElement('a');
            downloadLink.download = filename + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        // Function to initialize charts
        function initializeCharts() {
            // Appointments Chart
            const appointmentsCtx = document.getElementById('appointmentsChart');
            if(appointmentsCtx) {
                // Count appointments by status
                let active = 0;
                let cancelled = 0;
                
                <?php
                $active_apps = 0;
                $cancelled_apps = 0;
                foreach($appointments as $app) {
                    if($app['appointmentStatus'] == 'active') {
                        $active_apps++;
                    } else {
                        $cancelled_apps++;
                    }
                }
                ?>
                
                const appointmentsChart = new Chart(appointmentsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Cancelled'],
                        datasets: [{
                            data: [<?php echo $active_apps; ?>, <?php echo $cancelled_apps; ?>],
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.5)',
                                'rgba(255, 99, 132, 0.5)'
                            ],
                            borderColor: [
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 99, 132, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }
            
            // Department Chart
            const departmentCtx = document.getElementById('departmentChart');
            if(departmentCtx) {
                <?php
                // Count doctors by specialization
                $spec_count = [];
                foreach($doctors as $doctor) {
                    $spec = $doctor['spec'];
                    if(!isset($spec_count[$spec])) {
                        $spec_count[$spec] = 0;
                    }
                    $spec_count[$spec]++;
                }
                
                $spec_labels = json_encode(array_keys($spec_count));
                $spec_values = json_encode(array_values($spec_count));
                ?>
                
                const specCount = <?php echo $spec_values; ?>;
                const specLabels = <?php echo $spec_labels; ?>;
                
                const departmentChart = new Chart(departmentCtx, {
                    type: 'pie',
                    data: {
                        labels: specLabels,
                        datasets: [{
                            data: specCount,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.5)',
                                'rgba(54, 162, 235, 0.5)',
                                'rgba(255, 206, 86, 0.5)',
                                'rgba(75, 192, 192, 0.5)',
                                'rgba(153, 102, 255, 0.5)',
                                'rgba(255, 159, 64, 0.5)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }
        }
        
        // Function to auto-refresh success messages
        function autoRefreshMessages() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    if(alert.classList.contains('alert-success') || alert.classList.contains('alert-danger')) {
                        alert.style.opacity = '0.7';
                        setTimeout(function() {
                            alert.style.display = 'none';
                        }, 3000);
                    }
                });
            }, 5000);
        }
        
        // Function to confirm appointment cancellation
        function confirmCancelAppointment() {
            if(!currentAppointmentIdToCancel) return;
            
            const reason = document.getElementById('cancellationReason').value;
            const cancelledBy = document.querySelector('input[name="cancelledBy"]:checked').value;
            
            // Submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const appointmentIdInput = document.createElement('input');
            appointmentIdInput.type = 'hidden';
            appointmentIdInput.name = 'appointmentId';
            appointmentIdInput.value = currentAppointmentIdToCancel;
            form.appendChild(appointmentIdInput);
            
            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'reason';
            reasonInput.value = reason;
            form.appendChild(reasonInput);
            
            const cancelledByInput = document.createElement('input');
            cancelledByInput.type = 'hidden';
            cancelledByInput.name = 'cancelledBy';
            cancelledByInput.value = cancelledBy;
            form.appendChild(cancelledByInput);
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'cancel_appointment';
            actionInput.value = '1';
            form.appendChild(actionInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Function to update payment status
        function updatePaymentStatus() {
            if(!currentPaymentId) return;
            
            const status = document.getElementById('edit-payment-status').value;
            const method = document.getElementById('edit-payment-method').value;
            const receipt = document.getElementById('edit-receipt-number').value;
            
            // Submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const paymentIdInput = document.createElement('input');
            paymentIdInput.type = 'hidden';
            paymentIdInput.name = 'paymentId';
            paymentIdInput.value = currentPaymentId;
            form.appendChild(paymentIdInput);
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = status;
            form.appendChild(statusInput);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = 'method';
            methodInput.value = method;
            form.appendChild(methodInput);
            
            const receiptInput = document.createElement('input');
            receiptInput.type = 'hidden';
            receiptInput.name = 'receipt';
            receiptInput.value = receipt;
            form.appendChild(receiptInput);
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'update_payment';
            actionInput.value = '1';
            form.appendChild(actionInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Function to delete doctor from dashboard
        function deleteDoctorFromDashboard() {
            const doctorId = document.getElementById('doctor-select').value;
            
            if(!doctorId) {
                alert('Please select a doctor to delete!');
                return;
            }
            
            if(confirm('Are you sure you want to delete this doctor?')) {
                // Submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const doctorIdInput = document.createElement('input');
                doctorIdInput.type = 'hidden';
                doctorIdInput.name = 'doctorId';
                doctorIdInput.value = doctorId;
                form.appendChild(doctorIdInput);
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'delete_doctor';
                actionInput.value = '1';
                form.appendChild(actionInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <a class="navbar-brand" href="#"><i class="fa fa-user-plus"></i> Heth Care Hospital</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#" id="notification-badge">
                        <i class="fa fa-bell"></i> Notifications 
                        <span class="badge badge-light" id="notification-count">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="fa fa-user"></i> Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout1.php"><i class="fa fa-sign-out"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid">
        <h3 class="text-center mb-4">ADMIN PANEL</h3>
        
        <?php if($success_msg): ?>
            <div class="alert alert-success text-center"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-3">
                <div class="list-group" id="list-tab" role="tablist">
                    <a class="list-group-item list-group-item-action active" data-toggle="list" href="#dash-tab">
                        <i class="fa fa-tachometer mr-2"></i>Dashboard
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#doc-tab">
                        <i class="fa fa-user-md mr-2"></i>Doctors
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#pat-tab">
                        <i class="fa fa-users mr-2"></i>Patients
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#app-tab">
                        <i class="fa fa-calendar mr-2"></i>Appointments
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#pres-tab">
                        <i class="fa fa-file-text mr-2"></i>Prescriptions
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#pay-tab">
                        <i class="fa fa-credit-card mr-2"></i>Payments
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#sched-tab">
                        <i class="fa fa-clock-o mr-2"></i>Schedules
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#room-tab">
                        <i class="fa fa-bed mr-2"></i>Rooms/Beds
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#staff-tab">
                        <i class="fa fa-id-badge mr-2"></i>Staff Management
                    </a>
                </div>
            </div>

            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Dashboard -->
                    <div class="tab-pane fade show active" id="dash-tab">
                        <div class="dashboard-bg"></div>
                        <div class="dashboard-content">
                            <h4 class="mb-4 text-dark">Dashboard Overview</h4>
                            
                            <!-- Quick Actions -->
                            <div class="quick-actions">
                                <a class="quick-action-btn" data-toggle="list" href="#staff-tab">
                                    <i class="fa fa-user-md fa-2x mb-2"></i>
                                    <div>Add Doctor</div>
                                </a>
                                <a class="quick-action-btn" data-toggle="list" href="#staff-tab">
                                    <i class="fa fa-id-badge fa-2x mb-2"></i>
                                    <div>Manage Staff</div>
                                </a>
                                <a class="quick-action-btn" data-toggle="list" href="#app-tab">
                                    <i class="fa fa-calendar fa-2x mb-2"></i>
                                    <div>View Appointments</div>
                                </a>
                                <a class="quick-action-btn" data-toggle="list" href="#pay-tab">
                                    <i class="fa fa-credit-card fa-2x mb-2"></i>
                                    <div>Payments</div>
                                </a>
                                <a class="quick-action-btn" data-toggle="list" href="#room-tab">
                                    <i class="fa fa-bed fa-2x mb-2"></i>
                                    <div>Rooms/Beds</div>
                                </a>
                                <a class="quick-action-btn" data-toggle="modal" data-target="#deleteDoctorModal">
                                    <i class="fa fa-trash fa-2x mb-2"></i>
                                    <div>Delete Doctor</div>
                                </a>
                            </div>
                            
                            <!-- Stats Cards -->
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="dash-card text-white" style="background: linear-gradient(135deg, #0d47a1, #1976d2);">
                                        <i class="fa fa-user-md dash-icon"></i>
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Total Doctors</h5>
                                            <h3 class="card-value" id="total-doctors">0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="dash-card text-white" style="background: linear-gradient(135deg, #0d47a1, #2196f3);">
                                        <i class="fa fa-users dash-icon"></i>
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Total Patients</h5>
                                            <h3 class="card-value" id="total-patients">0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="dash-card text-white" style="background: linear-gradient(135deg, #0d47a1, #42a5f5);">
                                        <i class="fa fa-calendar dash-icon"></i>
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Appointments</h5>
                                            <h3 class="card-value" id="total-appointments">0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="dash-card text-white" style="background: linear-gradient(135deg, #0d47a1, #64b5f6);">
                                        <i class="fa fa-id-badge dash-icon"></i>
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Staff Members</h5>
                                            <h3 class="card-value" id="total-staff">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Charts and Additional Stats -->
                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="chart-container">
                                        <h5>Appointments Overview</h5>
                                        <canvas id="appointmentsChart" height="250"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="chart-container">
                                        <h5>Department Distribution</h5>
                                        <canvas id="departmentChart" height="250"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Recent Activity -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="chart-container">
                                        <h5>Recent Activity</h5>
                                        <div class="recent-activity" id="recent-activity">
                                            <div class="activity-item">
                                                <div class="d-flex justify-content-between">
                                                    <div><strong>System Started</strong></div>
                                                    <div class="activity-time">Just now</div>
                                                </div>
                                                <div>Admin Panel loaded successfully</div>
                                            </div>
                                            <?php if($total_patients > 0): ?>
                                            <div class="activity-item">
                                                <div class="d-flex justify-content-between">
                                                    <div><strong>Patients Registered</strong></div>
                                                    <div class="activity-time">Today</div>
                                                </div>
                                                <div>Total <?php echo $total_patients; ?> patients in system</div>
                                            </div>
                                            <?php endif; ?>
                                            <?php if($total_doctors > 0): ?>
                                            <div class="activity-item">
                                                <div class="d-flex justify-content-between">
                                                    <div><strong>Doctors Available</strong></div>
                                                    <div class="activity-time">Today</div>
                                                </div>
                                                <div>Total <?php echo $total_doctors; ?> doctors in system</div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="chart-container">
                                        <h5>Today's Appointments</h5>
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Patient</th>
                                                    <th>Doctor</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="today-appointments">
                                                <?php if($today_appointments > 0): ?>
                                                    <?php foreach($appointments as $app): ?>
                                                        <?php if($app['appdate'] == $today): ?>
                                                        <tr>
                                                            <td><?php echo date('h:i A', strtotime($app['apptime'])); ?></td>
                                                            <td><?php echo $app['fname'] . ' ' . $app['lname']; ?></td>
                                                            <td><?php echo $app['doctor']; ?></td>
                                                            <td>
                                                                <?php if($app['appointmentStatus'] == 'active'): ?>
                                                                    <span class="status-badge status-active">Active</span>
                                                                <?php else: ?>
                                                                    <span class="status-badge status-cancelled">Cancelled</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">No appointments for today</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Doctors -->
                    <div class="tab-pane fade" id="doc-tab">
                        <h4>Doctors List</h4>
                        <?php if($doctor_msg): echo $doctor_msg; endif; ?>
                        <div class="search-container">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="search-bar">
                                        <input type="text" class="form-control" id="doctor-search" placeholder="Search doctors by name or ID..." onkeyup="filterTable('doctor-search', 'doctors-table-body')">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" onclick="exportTable('doctors-table-body', 'doctors')">
                                        <i class="fa fa-download mr-2"></i>Export Doctors
                                    </button>
                                </div>
                            </div>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>Doctor ID</th>
                                    <th>Name</th>
                                    <th>Specialization</th>
                                    <th>Email</th>
                                    <th>Fees (Rs.)</th>
                                    <th>Contact Number</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="doctors-table-body">
                                <?php if(count($doctors) > 0): ?>
                                    <?php foreach($doctors as $doctor): ?>
                                    <tr>
                                        <td><?php echo $doctor['id']; ?></td>
                                        <td><?php echo $doctor['username']; ?></td>
                                        <td><?php echo $doctor['spec']; ?></td>
                                        <td><?php echo $doctor['email']; ?></td>
                                        <td>Rs. <?php echo number_format($doctor['docFees'], 2); ?></td>
                                        <td><?php echo $doctor['contact'] ? $doctor['contact'] : 'N/A'; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info action-btn" onclick="alert('Doctor Details:\\nID: <?php echo $doctor['id']; ?>\\nName: <?php echo $doctor['username']; ?>\\nSpecialization: <?php echo $doctor['spec']; ?>\\nEmail: <?php echo $doctor['email']; ?>\\nFees: Rs. <?php echo number_format($doctor['docFees'], 2); ?>')">
                                                <i class="fa fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No doctors found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Patients -->
                    <div class="tab-pane fade" id="pat-tab">
                        <!-- Patient Registration Form -->
                        <div class="patient-registration-card">
                            <div class="card-header-custom">
                                <i class="fa fa-user-plus mr-2"></i>Register New Patient
                            </div>
                            <div class="card-body">
                                <?php echo $patient_msg; ?>
                                <form method="POST" id="add-patient-form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientFirstName">First Name *</label>
                                                <input type="text" class="form-control" id="patientFirstName" name="fname" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientLastName">Last Name *</label>
                                                <input type="text" class="form-control" id="patientLastName" name="lname" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientGender">Gender *</label>
                                                <select class="form-control" id="patientGender" name="gender" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientDOB">Date of Birth *</label>
                                                <input type="date" class="form-control" id="patientDOB" name="dob" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientEmail">Email Address *</label>
                                                <input type="email" class="form-control" id="patientEmail" name="email" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientContact">Contact Number *</label>
                                                <input type="tel" class="form-control" id="patientContact" name="contact" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientAddress">Address</label>
                                                <textarea class="form-control" id="patientAddress" name="address" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientEmergencyContact">Emergency Contact</label>
                                                <input type="tel" class="form-control" id="patientEmergencyContact" name="emergencyContact">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="patientNIC">National ID (NIC) * <small class="text-muted">(Enter numbers only, "NIC" will be added automatically)</small></label>
                                                <input type="text" class="form-control" id="patientNIC" name="nic" 
                                                       placeholder="Enter NIC numbers only (e.g., 199012345678)" 
                                                       oninput="formatNIC()" required>
                                                <small class="text-muted">Example: 199012345678 → Will be stored as NIC199012345678</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientPassword">Password *</label>
                                                <input type="password" class="form-control" id="patientPassword" name="password" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientConfirmPassword">Confirm Password *</label>
                                                <input type="password" class="form-control" id="patientConfirmPassword" name="cpassword" onkeyup="checkPatientPassword()" required>
                                                <small id="patientPasswordMessage" class="form-text"></small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Generated NIC Display -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="generated-nic">
                                                <i class="fa fa-id-card mr-2"></i>
                                                <span id="generatedNICDisplay">Enter NIC number above</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="submit" name="add_patient" class="btn btn-success">
                                            <i class="fa fa-user-plus mr-1"></i> Register Patient
                                        </button>
                                        <button type="reset" class="btn btn-secondary ml-2">
                                            <i class="fa fa-refresh mr-1"></i> Reset Form
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <h4>Patients List</h4>
                        <div class="search-container">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="search-bar">
                                        <input type="text" class="form-control" id="patient-search" placeholder="Search patients by NIC only..." onkeyup="filterPatientsByNIC()">
                                        <i class="fa fa-search search-icon"></i>
                                        <small class="text-muted form-text">Search by National ID (NIC) only</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" onclick="exportTable('patients-table-body', 'patients')">
                                        <i class="fa fa-download mr-2"></i>Export Patients
                                    </button>
                                </div>
                            </div>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Gender</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Date of Birth</th>
                                    <th>NIC</th>
                                </tr>
                            </thead>
                            <tbody id="patients-table-body">
                                <?php if(count($patients) > 0): ?>
                                    <?php foreach($patients as $patient): ?>
                                    <tr>
                                        <td><?php echo $patient['pid']; ?></td>
                                        <td><?php echo $patient['fname']; ?></td>
                                        <td><?php echo $patient['lname']; ?></td>
                                        <td><?php echo $patient['gender']; ?></td>
                                        <td><?php echo $patient['email']; ?></td>
                                        <td><?php echo $patient['contact']; ?></td>
                                        <td><?php echo $patient['dob'] ? date('Y-m-d', strtotime($patient['dob'])) : 'N/A'; ?></td>
                                        <td><span class="badge badge-info"><?php echo $patient['national_id']; ?></span></td>
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

                    <!-- Appointments -->
                    <div class="tab-pane fade" id="app-tab">
                        <h4>Appointments</h4>
                        <?php if($appointment_msg): echo $appointment_msg; endif; ?>
                        <div class="search-container">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="search-bar">
                                        <input type="text" class="form-control" id="appointment-search" placeholder="Search appointments..." onkeyup="filterTable('appointment-search', 'appointments-table-body')">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" onclick="exportTable('appointments-table-body', 'appointments')">
                                        <i class="fa fa-download mr-2"></i>Export Appointments
                                    </button>
                                </div>
                            </div>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Patient ID</th>
                                    <th>Patient Name</th>
                                    <th>National ID</th>
                                    <th>Doctor</th>
                                    <th>Fees (Rs.)</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="appointments-table-body">
                                <?php if(count($appointments) > 0): ?>
                                    <?php foreach($appointments as $app): ?>
                                    <tr>
                                        <td><?php echo $app['ID']; ?></td>
                                        <td><?php echo $app['pid']; ?></td>
                                        <td><?php echo $app['fname'] . ' ' . $app['lname']; ?></td>
                                        <td><?php echo $app['national_id']; ?></td>
                                        <td><?php echo $app['doctor']; ?></td>
                                        <td>Rs. <?php echo number_format($app['docFees'], 2); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($app['appdate'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($app['apptime'])); ?></td>
                                        <td>
                                            <?php if($app['appointmentStatus'] == 'active'): ?>
                                                <span class="status-badge status-active">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-cancelled">Cancelled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($app['appointmentStatus'] == 'active'): ?>
                                                <button class="btn btn-sm btn-danger action-btn" data-toggle="modal" data-target="#cancelAppointmentModal" data-appointment-id="<?php echo $app['ID']; ?>">
                                                    <i class="fa fa-times"></i> Cancel
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary action-btn" disabled>
                                                    <i class="fa fa-times"></i> Cancelled
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-info action-btn" onclick="alert('Appointment Details:\\nID: <?php echo $app['ID']; ?>\\nPatient: <?php echo $app['fname'] . ' ' . $app['lname']; ?>\\nDoctor: <?php echo $app['doctor']; ?>\\nDate: <?php echo $app['appdate']; ?>\\nTime: <?php echo $app['apptime']; ?>\\nStatus: <?php echo $app['appointmentStatus']; ?>')">
                                                <i class="fa fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No appointments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Prescriptions -->
                    <div class="tab-pane fade" id="pres-tab">
                        <h4>Prescriptions</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="prescription-search" placeholder="Search prescriptions..." onkeyup="filterTable('prescription-search', 'prescriptions-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('prescriptions-table-body', 'prescriptions')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Patient ID</th>
                                    <th>Patient Name</th>
                                    <th>National ID</th>
                                    <th>Date</th>
                                    <th>Disease</th>
                                    <th>Allergy</th>
                                    <th>Prescription</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="prescriptions-table-body">
                                <?php if(count($prescriptions) > 0): ?>
                                    <?php foreach($prescriptions as $pres): ?>
                                    <tr>
                                        <td><?php echo $pres['doctor']; ?></td>
                                        <td><?php echo $pres['pid']; ?></td>
                                        <td><?php echo $pres['fname'] . ' ' . $pres['lname']; ?></td>
                                        <td><?php echo $pres['national_id']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($pres['appdate'])); ?></td>
                                        <td><?php echo $pres['disease']; ?></td>
                                        <td><?php echo $pres['allergy']; ?></td>
                                        <td><?php echo $pres['prescription']; ?></td>
                                        <td>
                                            <?php if($pres['emailStatus'] == 'Not Sent'): ?>
                                                <span class="status-badge status-pending">Not Sent</span>
                                            <?php elseif($pres['emailStatus'] == 'SMS Sent'): ?>
                                                <span class="status-badge status-sms-sent">SMS Sent</span>
                                            <?php else: ?>
                                                <span class="status-badge status-sent-external">Sent to Pharmacy</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No prescriptions found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Payments -->
                    <div class="tab-pane fade" id="pay-tab">
                        <h4>Payments</h4>
                        <?php if($payment_msg): echo $payment_msg; endif; ?>
                        
                        <!-- Search Options -->
                        <div class="search-options mb-3">
                            <div class="search-option-btn active" onclick="setPaymentSearchMode('patientId')">
                                <i class="fa fa-user mr-2"></i>Search by Patient ID
                            </div>
                            <div class="search-option-btn" onclick="setPaymentSearchMode('nic')">
                                <i class="fa fa-id-card mr-2"></i>Search by NIC
                            </div>
                            <div class="search-option-btn" onclick="setPaymentSearchMode('all')">
                                <i class="fa fa-list mr-2"></i>Show All Payments
                            </div>
                        </div>
                        
                        <!-- Search Forms -->
                        <div id="patientId-search-form" class="mb-3">
                            <div class="form-inline">
                                <input type="number" class="form-control mr-2" placeholder="Enter Patient ID" id="payment-patient-id">
                                <button type="button" class="btn btn-success" onclick="searchPaymentsByPatient()">
                                    <i class="fa fa-search mr-1"></i> Search Payments
                                </button>
                            </div>
                        </div>
                        
                        <div id="nic-search-form" class="mb-3" style="display: none;">
                            <div class="search-bar">
                                <input type="text" class="form-control" id="payment-nic-search" placeholder="Search payments by NIC only..." onkeyup="filterPaymentsByNIC()">
                                <i class="fa fa-search search-icon"></i>
                                <small class="text-muted form-text">Search by National ID (NIC) only</small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <div></div>
                            <button class="btn btn-primary" onclick="exportTable('payments-table-body', 'payments')">
                                <i class="fa fa-download mr-2"></i>Export Payments
                            </button>
                        </div>
                        
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Patient ID</th>
                                    <th>Appointment ID</th>
                                    <th>Patient Name</th>
                                    <th>National ID</th>
                                    <th>Doctor</th>
                                    <th>Fees (Rs.)</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="payments-table-body">
                                <?php if(count($payments) > 0): ?>
                                    <?php foreach($payments as $pay): ?>
                                    <tr>
                                        <td><?php echo $pay['id']; ?></td>
                                        <td><?php echo $pay['pid']; ?></td>
                                        <td><?php echo $pay['appointment_id']; ?></td>
                                        <td><?php echo $pay['patient_name']; ?></td>
                                        <td><span class="badge badge-info"><?php echo $pay['national_id']; ?></span></td>
                                        <td><?php echo $pay['doctor']; ?></td>
                                        <td>Rs. <?php echo number_format($pay['fees'], 2); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($pay['pay_date'])); ?></td>
                                        <td>
                                            <?php if($pay['pay_status'] == 'Paid'): ?>
                                                <span class="status-badge status-active">Paid</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info action-btn" onclick="alert('Payment Details:\\nID: <?php echo $pay['id']; ?>\\nPatient: <?php echo $pay['patient_name']; ?>\\nDoctor: <?php echo $pay['doctor']; ?>\\nAmount: Rs. <?php echo number_format($pay['fees'], 2); ?>\\nDate: <?php echo $pay['pay_date']; ?>\\nStatus: <?php echo $pay['pay_status']; ?>')">
                                                <i class="fa fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-warning action-btn" data-toggle="modal" data-target="#editPaymentModal" data-payment-id="<?php echo $pay['id']; ?>">
                                                <i class="fa fa-edit"></i> Edit Status
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

                    <!-- Staff Schedules -->
                    <div class="tab-pane fade" id="sched-tab">
                        <h4>Staff Schedules</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="schedule-search" placeholder="Search schedules..." onkeyup="filterTable('schedule-search', 'schedules-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('schedules-table-body', 'schedules')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Staff Name</th>
                                    <th>Role</th>
                                    <th>Day</th>
                                    <th>Shift</th>
                                </tr>
                            </thead>
                            <tbody id="schedules-table-body">
                                <?php if(count($schedules) > 0): ?>
                                    <?php foreach($schedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo $schedule['id']; ?></td>
                                        <td><?php echo $schedule['staff_name']; ?></td>
                                        <td><?php echo $schedule['role']; ?></td>
                                        <td><?php echo $schedule['day']; ?></td>
                                        <td><?php echo $schedule['shift']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No schedules found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Rooms / Beds -->
                    <div class="tab-pane fade" id="room-tab">
                        <h4>Rooms / Beds</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="room-search" placeholder="Search rooms..." onkeyup="filterTable('room-search', 'rooms-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('rooms-table-body', 'rooms')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Room No</th>
                                    <th>Bed No</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="rooms-table-body">
                                <?php if(count($rooms) > 0): ?>
                                    <?php foreach($rooms as $room): ?>
                                    <tr>
                                        <td><?php echo $room['id']; ?></td>
                                        <td><?php echo $room['room_no']; ?></td>
                                        <td><?php echo $room['bed_no']; ?></td>
                                        <td><?php echo $room['type']; ?></td>
                                        <td>
                                            <?php if($room['status'] == 'Available'): ?>
                                                <span class="status-badge status-available">Available</span>
                                            <?php else: ?>
                                                <span class="status-badge status-occupied">Occupied</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No rooms found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Staff Management -->
                    <div class="tab-pane fade" id="staff-tab">
                        <h4>Staff & Doctor Management</h4>
                        
                        <?php if($staff_msg): echo $staff_msg; endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <i class="fa fa-user-md mr-2"></i>Add New Doctor
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" id="add-doctor-form">
                                            <div class="form-group">
                                                <label>Doctor ID *</label>
                                                <input type="text" name="doctorId" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Name *</label>
                                                <input type="text" name="doctor" class="form-control" onkeydown="return alphaOnly(event)" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Contact Number *</label>
                                                <input type="tel" name="doctorContact" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Specialization *</label>
                                                <select name="special" class="form-control" required>
                                                    <option value="">Select Specialization</option>
                                                    <option value="General">General Physician</option>
                                                    <option value="Cardiologist">Cardiologist</option>
                                                    <option value="Pediatrician">Pediatrician</option>
                                                    <option value="Neurologist">Neurologist</option>
                                                    <option value="Dermatologist">Dermatologist</option>
                                                    <option value="Orthopedic">Orthopedic</option>
                                                    <option value="Gynecologist">Gynecologist</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Email *</label>
                                                <input type="email" name="demail" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Password *</label>
                                                <input type="password" id="dpassword" name="dpassword" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Confirm Password *</label>
                                                <input type="password" id="cdpassword" class="form-control" onkeyup="checkDoctorPassword()" required>
                                                <small id="message"></small>
                                            </div>
                                            <div class="form-group">
                                                <label>Fees (Rs.) *</label>
                                                <input type="number" name="docFees" class="form-control" required>
                                            </div>
                                            <button type="submit" name="add_doctor" class="btn btn-success btn-block">Add Doctor</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fa fa-plus-circle mr-2"></i>Add New Staff Member
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" id="add-staff-form">
                                            <div class="form-group">
                                                <label>Staff ID</label>
                                                <input type="text" name="staffId" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" name="staff" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Role</label>
                                                <select name="role" class="form-control" required>
                                                    <option value="">Select Role</option>
                                                    <option value="Nurse">Nurse</option>
                                                    <option value="Receptionist">Receptionist</option>
                                                    <option value="Admin">Admin</option>
                                                    <option value="Lab Technician">Lab Technician</option>
                                                    <option value="Pharmacist">Pharmacist</option>
                                                    <option value="Cleaner">Cleaner</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Email</label>
                                                <input type="email" name="semail" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Contact</label>
                                                <input type="text" name="scontact" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input type="password" name="spassword" class="form-control" required>
                                            </div>
                                            <button type="submit" name="add_staff" class="btn btn-primary btn-block">Add Staff Member</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h5>Doctors & Staff List</h5>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="staff-search" placeholder="Search by ID or Name..." onkeyup="filterTable('staff-search', 'staff-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('staff-table-body', 'staff')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Role/Type</th>
                                    <th>Email</th>
                                    <th>Contact/Fees</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="staff-table-body">
                                <!-- Doctors -->
                                <?php foreach($doctors as $doctor): ?>
                                <tr>
                                    <td><?php echo $doctor['id']; ?></td>
                                    <td><?php echo $doctor['username']; ?></td>
                                    <td><span class="badge badge-primary">Doctor (<?php echo $doctor['spec']; ?>)</span></td>
                                    <td><?php echo $doctor['email']; ?></td>
                                    <td>Rs. <?php echo number_format($doctor['docFees'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info action-btn" onclick="alert('Doctor Details:\\nID: <?php echo $doctor['id']; ?>\\nName: <?php echo $doctor['username']; ?>\\nSpecialization: <?php echo $doctor['spec']; ?>\\nEmail: <?php echo $doctor['email']; ?>')">
                                            <i class="fa fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <!-- Staff -->
                                <?php foreach($staff as $staff_member): ?>
                                <tr>
                                    <td><?php echo $staff_member['id']; ?></td>
                                    <td><?php echo $staff_member['name']; ?></td>
                                    <td><span class="badge badge-secondary"><?php echo $staff_member['role']; ?></span></td>
                                    <td><?php echo $staff_member['email']; ?></td>
                                    <td><?php echo $staff_member['contact']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info action-btn" onclick="alert('Staff Details:\\nID: <?php echo $staff_member['id']; ?>\\nName: <?php echo $staff_member['name']; ?>\\nRole: <?php echo $staff_member['role']; ?>\\nEmail: <?php echo $staff_member['email']; ?>')">
                                            <i class="fa fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if(count($doctors) == 0 && count($staff) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No doctors or staff members found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Doctor Modal -->
    <div class="modal fade" id="deleteDoctorModal" tabindex="-1" role="dialog" aria-labelledby="deleteDoctorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteDoctorModalLabel">Delete Doctor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="delete-doctor-form">
                        <div class="form-group">
                            <label>Select Doctor</label>
                            <select name="doctorId" class="form-control" id="doctor-select" required>
                                <option value="">Select doctor to delete</option>
                                <?php foreach($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['id']; ?>">
                                    <?php echo $doctor['id']; ?> - <?php echo $doctor['username']; ?> (<?php echo $doctor['spec']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="deleteDoctorFromDashboard()">Delete Doctor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Appointment Modal -->
    <div class="modal fade" id="cancelAppointmentModal" tabindex="-1" role="dialog" aria-labelledby="cancelAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelAppointmentModalLabel">Cancel Appointment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="cancel-appointment-form">
                        <div class="form-group">
                            <label for="cancellationReason">Cancellation Reason</label>
                            <textarea class="form-control" id="cancellationReason" rows="3" placeholder="Enter reason for cancellation"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Cancelled By</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="cancelledBy" id="cancelledByAdmin" value="admin" checked>
                                    <label class="form-check-label" for="cancelledByAdmin">Admin</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="cancelledBy" id="cancelledByPatient" value="patient">
                                    <label class="form-check-label" for="cancelledByPatient">Patient</label>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="appointmentToCancelId">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" onclick="confirmCancelAppointment()">Cancel Appointment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Payment Status Modal -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="editPaymentModalLabel">Edit Payment Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="color: white;">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="edit-payment-form">
                        <input type="hidden" id="edit-payment-id">
                        
                        <div class="form-group">
                            <label for="edit-payment-status">Payment Status</label>
                            <select class="form-control" id="edit-payment-status" required>
                                <option value="">Select Status</option>
                                <option value="Paid">Paid</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                        
                        <div id="payment-method-section" style="display: none;">
                            <div class="form-group">
                                <label for="edit-payment-method">Payment Method</label>
                                <select class="form-control" id="edit-payment-method">
                                    <option value="">Select Payment Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Online Payment">Online Payment</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit-receipt-number">Receipt Number (Optional)</label>
                                <input type="text" class="form-control" id="edit-receipt-number" placeholder="Enter receipt number">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-payment-notes">Notes (Optional)</label>
                            <textarea class="form-control" id="edit-payment-notes" rows="3" placeholder="Add any additional notes"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="updatePaymentStatus()">Update Payment</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    
    <script>
        // Function to filter table rows
        function filterTable(searchInputId, tableBodyId) {
            const input = document.getElementById(searchInputId);
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableBodyId);
            if(!table) return;
            
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell) {
                        const text = cell.textContent || cell.innerText;
                        if (text.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }
        
        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Update counts immediately
            updateDashboardCounts();
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    if(alert.classList.contains('alert-success') || alert.classList.contains('alert-danger')) {
                        alert.style.opacity = '0.7';
                        setTimeout(function() {
                            alert.style.display = 'none';
                        }, 3000);
                    }
                });
            }, 5000);
        });
    </script>
</body>
</html>