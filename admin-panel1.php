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
$prescription_msg = "";

// ===========================
// ADD PATIENT (NO PASSWORD ENCRYPTION)
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
    // REMOVED PASSWORD HASHING - STORE PLAIN TEXT
    $password = mysqli_real_escape_string($con, $_POST['password']);
    
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
            // Insert patient with plain text password
            $query = "INSERT INTO patreg (fname, lname, gender, dob, email, contact, address, emergencyContact, national_id, password) 
                      VALUES ('$fname', '$lname', '$gender', '$dob', '$email', '$contact', '$address', '$emergencyContact', '$national_id', '$password')";
            
            if(mysqli_query($con, $query)){
                $new_patient_id = mysqli_insert_id($con);
                $patient_msg = "<div class='alert alert-success'>✅ Patient registered successfully! Patient ID: $new_patient_id, NIC: $national_id</div>";
                $_SESSION['success'] = "Patient added successfully!";
                
                // Auto refresh to clear form
                echo "<script>setTimeout(function(){ window.location.href = window.location.href.split('#')[0] + '#pat-tab'; }, 2000);</script>";
            } else {
                $patient_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
            }
        }
    }
}

// ===========================
// ADD DOCTOR (NO PASSWORD ENCRYPTION)
// ===========================
if(isset($_POST['add_doctor'])){
    $doctorId = mysqli_real_escape_string($con, $_POST['doctorId']);
    $doctor = mysqli_real_escape_string($con, $_POST['doctor']);
    $special = mysqli_real_escape_string($con, $_POST['special']);
    $demail = mysqli_real_escape_string($con, $_POST['demail']);
    // REMOVED PASSWORD HASHING - STORE PLAIN TEXT
    $dpassword = mysqli_real_escape_string($con, $_POST['dpassword']);
    $docFees = mysqli_real_escape_string($con, $_POST['docFees']);
    $doctorContact = mysqli_real_escape_string($con, $_POST['doctorContact']);
    
    // Check if doctor exists
    $check = mysqli_query($con, "SELECT * FROM doctb WHERE email='$demail' OR id='$doctorId'");
    if(mysqli_num_rows($check) > 0){
        $doctor_msg = "<div class='alert alert-danger'>❌ Doctor with this email or ID already exists!</div>";
    } else {
        // Insert doctor with plain text password
        $query = "INSERT INTO doctb (id, username, spec, email, password, docFees, contact) 
                  VALUES ('$doctorId', '$doctor', '$special', '$demail', '$dpassword', '$docFees', '$doctorContact')";
        
        if(mysqli_query($con, $query)){
            $doctor_msg = "<div class='alert alert-success'>✅ Doctor added successfully! Doctor ID: $doctorId</div>";
            $_SESSION['success'] = "Doctor added successfully!";
            header("Location: admin_panel.php#staff-tab");
            exit();
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
        // Delete doctor from all related tables
        mysqli_query($con, "DELETE FROM appointmenttb WHERE doctor=(SELECT username FROM doctb WHERE id='$doctorId')");
        mysqli_query($con, "DELETE FROM prestb WHERE doctor=(SELECT username FROM doctb WHERE id='$doctorId')");
        mysqli_query($con, "DELETE FROM paymenttb WHERE doctor=(SELECT username FROM doctb WHERE id='$doctorId')");
        
        $delete = mysqli_query($con, "DELETE FROM doctb WHERE id='$doctorId'");
        if($delete){
            $doctor_msg = "<div class='alert alert-success'>✅ Doctor deleted successfully!</div>";
            $_SESSION['success'] = "Doctor deleted successfully!";
            header("Location: admin_panel.php#staff-tab");
            exit();
        } else {
            $doctor_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
        }
    }
}

// ===========================
// ADD STAFF (NO PASSWORD ENCRYPTION)
// ===========================
if(isset($_POST['add_staff'])){
    $staffId = mysqli_real_escape_string($con, $_POST['staffId']);
    $staff = mysqli_real_escape_string($con, $_POST['staff']);
    $role = mysqli_real_escape_string($con, $_POST['role']);
    $semail = mysqli_real_escape_string($con, $_POST['semail']);
    $scontact = mysqli_real_escape_string($con, $_POST['scontact']);
    // REMOVED PASSWORD HASHING - STORE PLAIN TEXT
    $spassword = mysqli_real_escape_string($con, $_POST['spassword']);
    
    // Check if staff exists
    $check = mysqli_query($con, "SELECT * FROM stafftb WHERE email='$semail' OR id='$staffId'");
    if(mysqli_num_rows($check) > 0){
        $staff_msg = "<div class='alert alert-danger'>❌ Staff member with this email or ID already exists!</div>";
    } else {
        // Insert staff with plain text password
        $query = "INSERT INTO stafftb (id, name, role, email, contact, password) 
                  VALUES ('$staffId', '$staff', '$role', '$semail', '$scontact', '$spassword')";
        
        if(mysqli_query($con, $query)){
            $staff_msg = "<div class='alert alert-success'>✅ Staff member added successfully! Staff ID: $staffId</div>";
            $_SESSION['success'] = "Staff added successfully!";
            header("Location: admin_panel.php#staff-tab");
            exit();
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
              userStatus=0,
              doctorStatus=0 
              WHERE ID='$appointmentId'";
    
    if(mysqli_query($con, $query)){
        $appointment_msg = "<div class='alert alert-success'>✅ Appointment cancelled successfully!</div>";
        $_SESSION['success'] = "Appointment cancelled!";
        header("Location: admin_panel.php#app-tab");
        exit();
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
        header("Location: admin_panel.php#pay-tab");
        exit();
    } else {
        $payment_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// ADD APPOINTMENT
// ===========================
if(isset($_POST['add_appointment'])){
    $patient_id = mysqli_real_escape_string($con, $_POST['patient_id']);
    $doctor = mysqli_real_escape_string($con, $_POST['doctor']);
    $appdate = mysqli_real_escape_string($con, $_POST['appdate']);
    $apptime = mysqli_real_escape_string($con, $_POST['apptime']);
    
    // Get patient details
    $patient_query = mysqli_query($con, "SELECT * FROM patreg WHERE pid='$patient_id'");
    if(mysqli_num_rows($patient_query) > 0){
        $patient = mysqli_fetch_assoc($patient_query);
        
        // Get doctor fees
        $doctor_query = mysqli_query($con, "SELECT * FROM doctb WHERE username='$doctor'");
        if(mysqli_num_rows($doctor_query) > 0){
            $doctor_data = mysqli_fetch_assoc($doctor_query);
            $docFees = $doctor_data['docFees'];
            
            // Check for existing appointment at same time
            $check_existing = mysqli_query($con, "SELECT * FROM appointmenttb 
                WHERE doctor='$doctor' AND appdate='$appdate' AND apptime='$apptime' AND appointmentStatus='active'");
            
            if(mysqli_num_rows($check_existing) > 0){
                $appointment_msg = "<div class='alert alert-danger'>❌ Doctor already has an appointment at this time!</div>";
            } else {
                // Insert appointment
                $query = "INSERT INTO appointmenttb (pid, national_id, fname, lname, gender, email, contact, doctor, docFees, appdate, apptime) 
                          VALUES ('{$patient['pid']}', '{$patient['national_id']}', '{$patient['fname']}', '{$patient['lname']}', 
                                  '{$patient['gender']}', '{$patient['email']}', '{$patient['contact']}', 
                                  '$doctor', '$docFees', '$appdate', '$apptime')";
                
                if(mysqli_query($con, $query)){
                    $appointment_id = mysqli_insert_id($con);
                    
                    // Create corresponding payment record
                    $payment_query = "INSERT INTO paymenttb (pid, appointment_id, national_id, patient_name, doctor, fees, pay_date) 
                                      VALUES ('{$patient['pid']}', '$appointment_id', '{$patient['national_id']}', 
                                              '{$patient['fname']} {$patient['lname']}', '$doctor', '$docFees', '$appdate')";
                    mysqli_query($con, $payment_query);
                    
                    $appointment_msg = "<div class='alert alert-success'>✅ Appointment created successfully! Appointment ID: $appointment_id</div>";
                    $_SESSION['success'] = "Appointment created!";
                    header("Location: admin_panel.php#app-tab");
                    exit();
                } else {
                    $appointment_msg = "<div class='alert alert-danger'>❌ Error creating appointment: " . mysqli_error($con) . "</div>";
                }
            }
        } else {
            $appointment_msg = "<div class='alert alert-danger'>❌ Doctor not found!</div>";
        }
    } else {
        $appointment_msg = "<div class='alert alert-danger'>❌ Patient not found!</div>";
    }
}

// ===========================
// SEND PRESCRIPTION TO PHARMACY (NEW FUNCTIONALITY)
// ===========================
if(isset($_POST['send_prescription'])){
    $prescription_id = mysqli_real_escape_string($con, $_POST['prescription_id']);
    $send_option = mysqli_real_escape_string($con, $_POST['send_option']);
    
    if($send_option == 'email' || $send_option == 'both'){
        // Send email to pharmacy
        $to = "healthcarepharmacypp1@gmail.com";
        $subject = "New Prescription - Heth Care Hospital";
        
        // Get prescription details
        $pres_query = mysqli_query($con, "SELECT * FROM prestb WHERE id='$prescription_id'");
        if($pres_query && mysqli_num_rows($pres_query) > 0){
            $pres = mysqli_fetch_assoc($pres_query);
            
            $message = "
            <html>
            <head>
                <title>New Prescription</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .header { background: #4CAF50; color: white; padding: 10px; }
                    .content { padding: 20px; }
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>💊 New Prescription - Heth Care Hospital</h2>
                </div>
                <div class='content'>
                    <h3>Prescription Details</h3>
                    <table>
                        <tr><th>Doctor:</th><td>{$pres['doctor']}</td></tr>
                        <tr><th>Patient ID:</th><td>{$pres['pid']}</td></tr>
                        <tr><th>Patient Name:</th><td>{$pres['fname']} {$pres['lname']}</td></tr>
                        <tr><th>NIC:</th><td>{$pres['national_id']}</td></tr>
                        <tr><th>Date:</th><td>{$pres['appdate']}</td></tr>
                        <tr><th>Disease:</th><td>{$pres['disease']}</td></tr>
                        <tr><th>Allergy:</th><td>{$pres['allergy']}</td></tr>
                        <tr><th>Prescription:</th><td>{$pres['prescription']}</td></tr>
                    </table>
                    <br>
                    <p><strong>📅 Sent Date:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: admin@hethcarehospital.com" . "\r\n";
            
            if(mail($to, $subject, $message, $headers)){
                mysqli_query($con, "UPDATE prestb SET sentToPharmacy='Yes' WHERE id='$prescription_id'");
                $prescription_msg = "<div class='alert alert-success'>✅ Prescription sent to pharmacy email successfully!</div>";
            } else {
                $prescription_msg = "<div class='alert alert-warning'>⚠️ Email sending failed, but prescription is saved.</div>";
            }
        }
    }
    
    if($send_option == 'sms' || $send_option == 'both'){
        // Get patient contact for SMS
        $pres_query = mysqli_query($con, "SELECT p.* FROM prestb pr 
                                         JOIN patreg p ON pr.pid = p.pid 
                                         WHERE pr.id='$prescription_id'");
        if($pres_query && mysqli_num_rows($pres_query) > 0){
            $patient = mysqli_fetch_assoc($pres_query);
            $contact = $patient['contact'];
            
            // Simulate SMS sending (in real app, integrate with SMS API)
            mysqli_query($con, "UPDATE prestb SET smsSent='Yes' WHERE id='$prescription_id'");
            
            if($send_option == 'both'){
                $prescription_msg .= "<div class='alert alert-success'>📱 SMS notification marked as sent to patient!</div>";
            } else {
                $prescription_msg = "<div class='alert alert-success'>📱 SMS notification marked as sent to patient!</div>";
            }
        }
    }
    
    // Update email status
    $status_update = "Not Sent";
    if($send_option == 'email') $status_update = "Email Sent";
    if($send_option == 'sms') $status_update = "SMS Sent";
    if($send_option == 'both') $status_update = "Email & SMS Sent";
    
    mysqli_query($con, "UPDATE prestb SET emailStatus='$status_update' WHERE id='$prescription_id'");
    
    $_SESSION['success'] = "Prescription processing completed!";
    header("Location: admin_panel.php#pres-tab");
    exit();
}

// ===========================
// ADD SCHEDULE
// ===========================
if(isset($_POST['add_schedule'])){
    $staff_name = mysqli_real_escape_string($con, $_POST['staff_name']);
    $role = mysqli_real_escape_string($con, $_POST['role']);
    $day = mysqli_real_escape_string($con, $_POST['day']);
    $shift = mysqli_real_escape_string($con, $_POST['shift']);
    
    $query = "INSERT INTO scheduletb (staff_name, role, day, shift) 
              VALUES ('$staff_name', '$role', '$day', '$shift')";
    
    if(mysqli_query($con, $query)){
        $_SESSION['success'] = "Schedule added successfully!";
        header("Location: admin_panel.php#sched-tab");
        exit();
    } else {
        $_SESSION['error'] = "Error adding schedule: " . mysqli_error($con);
    }
}

// ===========================
// ADD ROOM
// ===========================
if(isset($_POST['add_room'])){
    $room_no = mysqli_real_escape_string($con, $_POST['room_no']);
    $bed_no = mysqli_real_escape_string($con, $_POST['bed_no']);
    $type = mysqli_real_escape_string($con, $_POST['type']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    // Check if room/bed already exists
    $check = mysqli_query($con, "SELECT * FROM roomtb WHERE room_no='$room_no' AND bed_no='$bed_no'");
    if(mysqli_num_rows($check) > 0){
        $_SESSION['error'] = "Room/Bed combination already exists!";
    } else {
        $query = "INSERT INTO roomtb (room_no, bed_no, type, status) 
                  VALUES ('$room_no', '$bed_no', '$type', '$status')";
        
        if(mysqli_query($con, $query)){
            $_SESSION['success'] = "Room/Bed added successfully!";
            header("Location: admin_panel.php#room-tab");
            exit();
        } else {
            $_SESSION['error'] = "Error adding room: " . mysqli_error($con);
        }
    }
}

// ===========================
// FUNCTION TO CHECK/CREATE TABLES
// ===========================
function checkAndCreateTables($con){
    // Create tables if they don't exist
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
            sentToPharmacy VARCHAR(50) DEFAULT 'No',
            smsSent VARCHAR(50) DEFAULT 'No',
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
$patient_result = mysqli_query($con, "SELECT pid, fname, lname, gender, email, contact, dob, national_id FROM patreg ORDER BY pid DESC");
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
$staff_result = mysqli_query($con, "SELECT id, name, role, email, contact FROM stafftb ORDER BY role");
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
    if($app['appdate'] == $today && $app['appointmentStatus'] == 'active'){
        $today_appointments++;
    }
}

// Check for session messages
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Heth Care Hospital</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* All CSS styles from your original code */
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
        
        /* ... (All other CSS styles remain exactly as in your original code) ... */
        
        .send-prescription-btn {
            margin: 2px;
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .modal-prescription-content {
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background-color: #f8f9fa;
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
        // JavaScript functions from your original code
        document.addEventListener('DOMContentLoaded', function() {
            updateDashboardCounts();
            setupFormValidations();
            setupModalFunctionality();
            populateDoctorSelect();
            setPaymentSearchMode('patientId');
            autoRefreshMessages();
        });
        
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
        
        function alphaOnly(event) {
            let key = event.keyCode;
            return ((key >= 65 && key <= 90) || (key >= 97 && key <= 122) || key == 8 || key == 32);
        }
        
        function setupFormValidations() {
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
        
        function setupModalFunctionality() {
            $('#cancelAppointmentModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const appointmentId = button.data('appointment-id');
                currentAppointmentIdToCancel = appointmentId;
                document.getElementById('appointmentToCancelId').value = appointmentId;
            });
            
            $('#editPaymentModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const paymentId = button.data('payment-id');
                currentPaymentId = paymentId;
                document.getElementById('edit-payment-id').value = paymentId;
            });
            
            $('#sendPrescriptionModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const prescriptionId = button.data('prescription-id');
                currentPrescriptionId = prescriptionId;
                document.getElementById('prescriptionToSendId').value = prescriptionId;
            });
            
            const editPaymentStatus = document.getElementById('edit-payment-status');
            if(editPaymentStatus) {
                editPaymentStatus.addEventListener('change', function() {
                    togglePaymentMethodSection(this.value);
                });
            }
        }
        
        function togglePaymentMethodSection(status) {
            const methodSection = document.getElementById('payment-method-section');
            if (status === 'Paid') {
                methodSection.style.display = 'block';
            } else {
                methodSection.style.display = 'none';
            }
        }
        
        function populateDoctorSelect() {
            // Already populated by PHP
        }
        
        function setPaymentSearchMode(mode) {
            currentPaymentSearchMode = mode;
            
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
        
        function showAllPayments() {
            const tbody = document.getElementById('payments-table-body');
            if(tbody) {
                const rows = tbody.getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    rows[i].style.display = '';
                }
            }
        }
        
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
        
        function exportTable(tableBodyId, filename) {
            const table = document.getElementById(tableBodyId);
            if(!table) return;
            
            const rows = table.getElementsByTagName('tr');
            let csv = [];
            
            const headerCells = table.parentNode.getElementsByTagName('thead')[0].getElementsByTagName('th');
            const headerRow = [];
            for (let i = 0; i < headerCells.length; i++) {
                if(headerCells[i].innerText !== 'Actions') {
                    headerRow.push(headerCells[i].innerText);
                }
            }
            csv.push(headerRow.join(','));
            
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
            
            const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            const downloadLink = document.createElement('a');
            downloadLink.download = filename + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        function updateDashboardCounts() {
            document.getElementById('total-doctors').textContent = '<?php echo $total_doctors; ?>';
            document.getElementById('total-patients').textContent = '<?php echo $total_patients; ?>';
            document.getElementById('total-appointments').textContent = '<?php echo $total_appointments; ?>';
            document.getElementById('total-staff').textContent = '<?php echo $total_staff; ?>';
            initializeCharts();
        }
        
        function initializeCharts() {
            const appointmentsCtx = document.getElementById('appointmentsChart');
            if(appointmentsCtx) {
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
            
            const departmentCtx = document.getElementById('departmentChart');
            if(departmentCtx) {
                <?php
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
        
        function confirmCancelAppointment() {
            if(!currentAppointmentIdToCancel) return;
            
            const reason = document.getElementById('cancellationReason').value;
            const cancelledBy = document.querySelector('input[name="cancelledBy"]:checked').value;
            
            if(!reason) {
                alert('Please enter cancellation reason!');
                return;
            }
            
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
        
        function updatePaymentStatus() {
            if(!currentPaymentId) return;
            
            const status = document.getElementById('edit-payment-status').value;
            const method = document.getElementById('edit-payment-method').value;
            const receipt = document.getElementById('edit-receipt-number').value;
            
            if(!status) {
                alert('Please select payment status!');
                return;
            }
            
            if(status === 'Paid' && !method) {
                alert('Please select payment method for paid status!');
                return;
            }
            
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
        
        function deleteDoctorFromDashboard() {
            const doctorId = document.getElementById('doctor-select').value;
            
            if(!doctorId) {
                alert('Please select a doctor to delete!');
                return;
            }
            
            if(confirm('Are you sure you want to delete this doctor? This will also delete all related appointments and prescriptions!')) {
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
        
        function sendPrescriptionToPharmacy() {
            const prescriptionId = document.getElementById('prescriptionToSendId').value;
            const sendOption = document.querySelector('input[name="send_option"]:checked').value;
            
            if(!prescriptionId || !sendOption) {
                alert('Please select a prescription and sending option!');
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const prescriptionIdInput = document.createElement('input');
            prescriptionIdInput.type = 'hidden';
            prescriptionIdInput.name = 'prescription_id';
            prescriptionIdInput.value = prescriptionId;
            form.appendChild(prescriptionIdInput);
            
            const sendOptionInput = document.createElement('input');
            sendOptionInput.type = 'hidden';
            sendOptionInput.name = 'send_option';
            sendOptionInput.value = sendOption;
            form.appendChild(sendOptionInput);
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'send_prescription';
            actionInput.value = '1';
            form.appendChild(actionInput);
            
            document.body.appendChild(form);
            form.submit();
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
                        <span class="badge badge-light" id="notification-count"><?php echo $today_appointments; ?></span>
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
        
        <?php if($error_msg): ?>
            <div class="alert alert-danger text-center"><?php echo $error_msg; ?></div>
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
                                            <?php if($today_appointments > 0): ?>
                                            <div class="activity-item">
                                                <div class="d-flex justify-content-between">
                                                    <div><strong>Today's Appointments</strong></div>
                                                    <div class="activity-time">Today</div>
                                                </div>
                                                <div><?php echo $today_appointments; ?> appointments scheduled for today</div>
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
                                                        <?php if($app['appdate'] == $today && $app['appointmentStatus'] == 'active'): ?>
                                                        <tr>
                                                            <td><?php echo date('h:i A', strtotime($app['apptime'])); ?></td>
                                                            <td><?php echo $app['fname'] . ' ' . $app['lname']; ?></td>
                                                            <td><?php echo $app['doctor']; ?></td>
                                                            <td>
                                                                <span class="status-badge status-active">Active</span>
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
                                            <button class="btn btn-sm btn-info action-btn" onclick="alert('Doctor Details:\\nID: <?php echo $doctor['id']; ?>\\nName: <?php echo $doctor['username']; ?>\\nSpecialization: <?php echo $doctor['spec']; ?>\\nEmail: <?php echo $doctor['email']; ?>\\nFees: Rs. <?php echo number_format($doctor['docFees'], 2); ?>\\nContact: <?php echo $doctor['contact']; ?>')">
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
                        
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fa fa-plus-circle mr-2"></i>Create New Appointment
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patient_id">Patient ID *</label>
                                                <input type="number" class="form-control" id="patient_id" name="patient_id" required>
                                                <small class="text-muted">Enter patient ID from patients list</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="doctor">Doctor *</label>
                                                <select class="form-control" id="doctor" name="doctor" required>
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
                                                <label for="appdate">Appointment Date *</label>
                                                <input type="date" class="form-control" id="appdate" name="appdate" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="apptime">Appointment Time *</label>
                                                <input type="time" class="form-control" id="apptime" name="apptime" required>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="add_appointment" class="btn btn-success">
                                        <i class="fa fa-calendar-plus mr-1"></i> Create Appointment
                                    </button>
                                </form>
                            </div>
                        </div>
                        
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
                                            <button class="btn btn-sm btn-info action-btn" onclick="alert('Appointment Details:\\nID: <?php echo $app['ID']; ?>\\nPatient: <?php echo $app['fname'] . ' ' . $app['lname']; ?>\\nDoctor: <?php echo $app['doctor']; ?>\\nDate: <?php echo $app['appdate']; ?>\\nTime: <?php echo $app['apptime']; ?>\\nFees: Rs. <?php echo number_format($app['docFees'], 2); ?>\\nStatus: <?php echo $app['appointmentStatus']; ?>')">
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
                        <?php if($prescription_msg): echo $prescription_msg; endif; ?>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="prescription-search" placeholder="Search prescriptions..." onkeyup="filterTable('prescription-search', 'prescriptions-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('prescriptions-table-body', 'prescriptions')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Doctor</th>
                                    <th>Patient Name</th>
                                    <th>National ID</th>
                                    <th>Date</th>
                                    <th>Disease</th>
                                    <th>Allergy</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="prescriptions-table-body">
                                <?php if(count($prescriptions) > 0): ?>
                                    <?php foreach($prescriptions as $pres): ?>
                                    <tr>
                                        <td><?php echo $pres['id']; ?></td>
                                        <td><?php echo $pres['doctor']; ?></td>
                                        <td><?php echo $pres['fname'] . ' ' . $pres['lname']; ?></td>
                                        <td><?php echo $pres['national_id']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($pres['appdate'])); ?></td>
                                        <td><?php echo $pres['disease']; ?></td>
                                        <td><?php echo $pres['allergy']; ?></td>
                                        <td>
                                            <?php if($pres['emailStatus'] == 'Not Sent'): ?>
                                                <span class="status-badge status-pending">Not Sent</span>
                                            <?php elseif($pres['emailStatus'] == 'Email Sent'): ?>
                                                <span class="status-badge status-sent">Email Sent</span>
                                            <?php elseif($pres['emailStatus'] == 'SMS Sent'): ?>
                                                <span class="status-badge status-sms-sent">SMS Sent</span>
                                            <?php elseif($pres['emailStatus'] == 'Email & SMS Sent'): ?>
                                                <span class="status-badge status-active">Both Sent</span>
                                            <?php else: ?>
                                                <span class="status-badge status-sent-external">Sent</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info action-btn" onclick="viewPrescriptionDetails(<?php echo $pres['id']; ?>)">
                                                <i class="fa fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-success send-prescription-btn" data-toggle="modal" data-target="#sendPrescriptionModal" data-prescription-id="<?php echo $pres['id']; ?>">
                                                <i class="fa fa-send"></i> Send
                                            </button>
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
                                    <th>Payment Method</th>
                                    <th>Receipt No</th>
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
                                        <td><?php echo $pay['payment_method'] ? $pay['payment_method'] : 'N/A'; ?></td>
                                        <td><?php echo $pay['receipt_no'] ? $pay['receipt_no'] : 'N/A'; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info action-btn" onclick="alert('Payment Details:\\nID: <?php echo $pay['id']; ?>\\nPatient: <?php echo $pay['patient_name']; ?>\\nDoctor: <?php echo $pay['doctor']; ?>\\nAmount: Rs. <?php echo number_format($pay['fees'], 2); ?>\\nDate: <?php echo $pay['pay_date']; ?>\\nStatus: <?php echo $pay['pay_status']; ?>\\nMethod: <?php echo $pay['payment_method']; ?>\\nReceipt: <?php echo $pay['receipt_no']; ?>')">
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
                                        <td colspan="12" class="text-center">No payments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Staff Schedules -->
                    <div class="tab-pane fade" id="sched-tab">
                        <h4>Staff Schedules</h4>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <i class="fa fa-clock-o mr-2"></i>Add New Schedule
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="staff_name">Staff Name *</label>
                                                <input type="text" class="form-control" id="staff_name" name="staff_name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="role">Role *</label>
                                                <input type="text" class="form-control" id="role" name="role" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="day">Day *</label>
                                                <select class="form-control" id="day" name="day" required>
                                                    <option value="">Select Day</option>
                                                    <option value="Monday">Monday</option>
                                                    <option value="Tuesday">Tuesday</option>
                                                    <option value="Wednesday">Wednesday</option>
                                                    <option value="Thursday">Thursday</option>
                                                    <option value="Friday">Friday</option>
                                                    <option value="Saturday">Saturday</option>
                                                    <option value="Sunday">Sunday</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="shift">Shift *</label>
                                                <select class="form-control" id="shift" name="shift" required>
                                                    <option value="">Select Shift</option>
                                                    <option value="Morning">Morning (8AM - 2PM)</option>
                                                    <option value="Afternoon">Afternoon (2PM - 8PM)</option>
                                                    <option value="Night">Night (8PM - 8AM)</option>
                                                    <option value="Full Day">Full Day</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="add_schedule" class="btn btn-info">
                                        <i class="fa fa-plus mr-1"></i> Add Schedule
                                    </button>
                                </form>
                            </div>
                        </div>
                        
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
                        
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <i class="fa fa-bed mr-2"></i>Add New Room/Bed
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="room_no">Room Number *</label>
                                                <input type="text" class="form-control" id="room_no" name="room_no" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="bed_no">Bed Number *</label>
                                                <input type="text" class="form-control" id="bed_no" name="bed_no" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="type">Type *</label>
                                                <select class="form-control" id="type" name="type" required>
                                                    <option value="">Select Type</option>
                                                    <option value="General">General</option>
                                                    <option value="ICU">ICU</option>
                                                    <option value="Private">Private</option>
                                                    <option value="Semi-Private">Semi-Private</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="status">Status *</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="Available">Available</option>
                                                    <option value="Occupied">Occupied</option>
                                                    <option value="Maintenance">Maintenance</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="add_room" class="btn btn-success">
                                        <i class="fa fa-plus mr-1"></i> Add Room/Bed
                                    </button>
                                </form>
                            </div>
                        </div>
                        
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
                                            <?php elseif($room['status'] == 'Occupied'): ?>
                                                <span class="status-badge status-occupied">Occupied</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Maintenance</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No rooms found</td>
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
                                <?php foreach($doctors as $doctor): ?>
                                <tr>
                                    <td><?php echo $doctor['id']; ?></td>
                                    <td><?php echo $doctor['username']; ?></td>
                                    <td><span class="badge badge-primary">Doctor (<?php echo $doctor['spec']; ?>)</span></td>
                                    <td><?php echo $doctor['email']; ?></td>
                                    <td>Rs. <?php echo number_format($doctor['docFees'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info action-btn" onclick="alert('Doctor Details:\\nID: <?php echo $doctor['id']; ?>\\nName: <?php echo $doctor['username']; ?>\\nSpecialization: <?php echo $doctor['spec']; ?>\\nEmail: <?php echo $doctor['email']; ?>\\nContact: <?php echo $doctor['contact']; ?>\\nFees: Rs. <?php echo number_format($doctor['docFees'], 2); ?>')">
                                            <i class="fa fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php foreach($staff as $staff_member): ?>
                                <tr>
                                    <td><?php echo $staff_member['id']; ?></td>
                                    <td><?php echo $staff_member['name']; ?></td>
                                    <td><span class="badge badge-secondary"><?php echo $staff_member['role']; ?></span></td>
                                    <td><?php echo $staff_member['email']; ?></td>
                                    <td><?php echo $staff_member['contact']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info action-btn" onclick="alert('Staff Details:\\nID: <?php echo $staff_member['id']; ?>\\nName: <?php echo $staff_member['name']; ?>\\nRole: <?php echo $staff_member['role']; ?>\\nEmail: <?php echo $staff_member['email']; ?>\\nContact: <?php echo $staff_member['contact']; ?>')">
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

    <!-- Send Prescription Modal -->
    <div class="modal fade" id="sendPrescriptionModal" tabindex="-1" role="dialog" aria-labelledby="sendPrescriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="sendPrescriptionModalLabel">Send Prescription</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="color: white;">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="send-prescription-form">
                        <input type="hidden" id="prescriptionToSendId">
                        
                        <div class="form-group">
                            <label>Select Sending Option</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="send_option" id="sendEmail" value="email" checked>
                                <label class="form-check-label" for="sendEmail">
                                    <i class="fa fa-envelope text-primary mr-2"></i>Send Email to Hospital Pharmacy
                                    <br><small class="text-muted">Email: healthcarepharmacypp1@gmail.com</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="send_option" id="sendSMS" value="sms">
                                <label class="form-check-label" for="sendSMS">
                                    <i class="fa fa-comment text-success mr-2"></i>Send SMS to Patient's Contact
                                    <br><small class="text-muted">Send prescription details via SMS</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="send_option" id="sendBoth" value="both">
                                <label class="form-check-label" for="sendBoth">
                                    <i class="fa fa-paper-plane text-info mr-2"></i>Send Both (Email & SMS)
                                    <br><small class="text-muted">Send to pharmacy and notify patient</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle mr-2"></i>
                            <strong>Note:</strong> Email will be sent to hospital pharmacy. SMS will be sent to patient's registered contact number.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="sendPrescriptionToPharmacy()">
                        <i class="fa fa-send mr-1"></i> Send Prescription
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    
    <script>
        // View prescription details
        function viewPrescriptionDetails(presId) {
            // You can implement AJAX to fetch details or show in a modal
            alert('Prescription ID: ' + presId + '\nThis would show detailed prescription information.');
        }
        
        // Additional initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Set today's date for appointment form
            const today = new Date().toISOString().split('T')[0];
            const appdateInput = document.getElementById('appdate');
            if(appdateInput) {
                appdateInput.min = today;
            }
            
            // Set patient DOB max to today
            const dobInput = document.getElementById('patientDOB');
            if(dobInput) {
                dobInput.max = today;
            }
            
            // Auto-hide messages
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