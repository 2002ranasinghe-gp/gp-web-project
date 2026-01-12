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
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $cpassword = isset($_POST['cpassword']) ? mysqli_real_escape_string($con, $_POST['cpassword']) : '';
    
    // Check if passwords match
    if($password !== $cpassword){
        $patient_msg = "<div class='alert alert-danger'>❌ Passwords do not match!</div>";
    } else {
        // Format NIC
        $nicNumbers = preg_replace('/[^0-9]/', '', $nic_input);
        
        // Check if NIC has at least 1 digit
        if(empty($nicNumbers)){
            $patient_msg = "<div class='alert alert-danger'>❌ Please enter a valid NIC number!</div>";
        } else {
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
                        // Refresh the page to show new patient
                        echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 2000);</script>";
                    } else {
                        $patient_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
                    }
                }
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
    $dpassword = mysqli_real_escape_string($con, $_POST['dpassword']);
    $cdpassword = isset($_POST['cdpassword']) ? mysqli_real_escape_string($con, $_POST['cdpassword']) : '';
    $docFees = mysqli_real_escape_string($con, $_POST['docFees']);
    $doctorContact = mysqli_real_escape_string($con, $_POST['doctorContact']);
    
    // Check if passwords match
    if($dpassword !== $cdpassword){
        $doctor_msg = "<div class='alert alert-danger'>❌ Passwords do not match!</div>";
    } else {
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
                echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 2000);</script>";
            } else {
                $doctor_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
            }
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
            echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 2000);</script>";
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
            echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 2000);</script>";
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
        echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 2000);</script>";
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
        echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 2000);</script>";
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
                echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 2000);</script>";
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
        echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 2000);</script>";
    } else {
        $staff_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
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
    
    $query = "INSERT INTO roomtb (room_no, bed_no, type, status) 
              VALUES ('$room_no', '$bed_no', '$type', '$status')";
    
    if(mysqli_query($con, $query)){
        $_SESSION['success'] = "Room/Bed added successfully!";
        echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 2000);</script>";
    } else {
        $staff_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// SEND PRESCRIPTION TO HOSPITAL PHARMACY
// ===========================
if(isset($_POST['send_to_hospital'])){
    $prescription_id = mysqli_real_escape_string($con, $_POST['prescription_id']);
    
    $query = "UPDATE prestb SET emailStatus='Sent to Hospital Pharmacy' WHERE id='$prescription_id'";
    
    if(mysqli_query($con, $query)){
        $prescription_msg = "<div class='alert alert-success'>✅ Prescription sent to Hospital Pharmacy successfully!</div>";
        $_SESSION['success'] = "Prescription sent to Hospital Pharmacy!";
        echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 2000);</script>";
    } else {
        $prescription_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// SEND PRESCRIPTION TO PATIENT CONTACT
// ===========================
if(isset($_POST['send_to_patient'])){
    $prescription_id = mysqli_real_escape_string($con, $_POST['prescription_id']);
    
    // Get patient contact from prescription
    $get_contact_query = mysqli_query($con, "SELECT p.contact, ps.fname, ps.lname, ps.prescription 
                                            FROM prestb ps 
                                            JOIN patreg p ON ps.pid = p.pid 
                                            WHERE ps.id='$prescription_id'");
    
    if(mysqli_num_rows($get_contact_query) > 0){
        $patient_data = mysqli_fetch_assoc($get_contact_query);
        $contact = $patient_data['contact'];
        $patient_name = $patient_data['fname'] . ' ' . $patient_data['lname'];
        $prescription_text = $patient_data['prescription'];
        
        // In a real system, you would integrate with SMS API here
        // For demo, we'll just update the status and log it
        $query = "UPDATE prestb SET emailStatus='Sent to Patient Contact (SMS)' WHERE id='$prescription_id'";
        
        if(mysqli_query($con, $query)){
            // Log the SMS sending (in real system, you'd call SMS API)
            $sms_log = "SMS sent to $patient_name at $contact with prescription details.";
            
            $prescription_msg = "<div class='alert alert-success'>✅ Prescription sent to patient's contact number via SMS!<br>
                                <small>Patient: $patient_name<br>
                                Contact: $contact<br>
                                Message: Prescription details have been sent to your mobile number.</small></div>";
            $_SESSION['success'] = "Prescription sent to patient via SMS!";
            echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 2000);</script>";
        } else {
            $prescription_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
        }
    } else {
        $prescription_msg = "<div class='alert alert-danger'>❌ Patient contact not found!</div>";
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
        }
        
        .bg-primary { 
            background: var(--primary-gradient);
        }
        
        .navbar-brand { 
            font-weight: bold;
            font-size: 1.8rem;
        }
        
        .tab-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
            min-height: 600px;
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
            color: white;
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
        
        .card-value {
            font-size: 3.5rem;
            font-weight: bold;
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
        
        .status-not-sent {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .status-hospital-pharmacy {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .status-patient-sms {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .generated-nic {
            background-color: #f0f8ff;
            border: 1px solid #b3e0ff;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            color: #0066cc;
            margin-top: 10px;
        }
        
        .search-bar {
            position: relative;
        }
        
        .search-bar .search-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
    </style>
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
                    <a class="nav-link" href="#">
                        <i class="fa fa-bell"></i> Notifications 
                        <span class="badge badge-light"><?php echo $today_appointments; ?></span>
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
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#pat-tab">
                        <i class="fa fa-users mr-2"></i>Patients
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#doc-tab">
                        <i class="fa fa-user-md mr-2"></i>Doctors
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
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#staff-tab">
                        <i class="fa fa-id-badge mr-2"></i>Staff Management
                    </a>
                </div>
            </div>

            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Dashboard -->
                    <div class="tab-pane fade show active" id="dash-tab">
                        <h4 class="mb-4">Dashboard Overview</h4>
                        
                        <div class="quick-actions">
                            <a class="quick-action-btn" data-toggle="list" href="#pat-tab">
                                <i class="fa fa-user-plus fa-2x mb-2"></i>
                                <div>Register Patient</div>
                            </a>
                            <a class="quick-action-btn" data-toggle="list" href="#staff-tab">
                                <i class="fa fa-user-md fa-2x mb-2"></i>
                                <div>Add Doctor</div>
                            </a>
                            <a class="quick-action-btn" data-toggle="list" href="#app-tab">
                                <i class="fa fa-calendar-plus fa-2x mb-2"></i>
                                <div>New Appointment</div>
                            </a>
                            <a class="quick-action-btn" data-toggle="modal" data-target="#deleteDoctorModal">
                                <i class="fa fa-trash fa-2x mb-2"></i>
                                <div>Delete Doctor</div>
                            </a>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="dash-card" style="background: linear-gradient(135deg, #0d47a1, #1976d2);">
                                    <i class="fa fa-user-md dash-icon"></i>
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Doctors</h5>
                                        <h3 class="card-value"><?php echo $total_doctors; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="dash-card" style="background: linear-gradient(135deg, #0d47a1, #2196f3);">
                                    <i class="fa fa-users dash-icon"></i>
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Patients</h5>
                                        <h3 class="card-value"><?php echo $total_patients; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="dash-card" style="background: linear-gradient(135deg, #0d47a1, #42a5f5);">
                                    <i class="fa fa-calendar dash-icon"></i>
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Appointments</h5>
                                        <h3 class="card-value"><?php echo $total_appointments; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="dash-card" style="background: linear-gradient(135deg, #0d47a1, #64b5f6);">
                                    <i class="fa fa-id-badge dash-icon"></i>
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Staff Members</h5>
                                        <h3 class="card-value"><?php echo $total_staff; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Recent Patients</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>NIC</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $recent_patients = array_slice($patients, 0, 5);
                                                foreach($recent_patients as $patient): 
                                                ?>
                                                <tr>
                                                    <td><?php echo $patient['pid']; ?></td>
                                                    <td><?php echo $patient['fname'] . ' ' . $patient['lname']; ?></td>
                                                    <td><?php echo $patient['email']; ?></td>
                                                    <td><span class="badge badge-info"><?php echo $patient['national_id']; ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Today's Appointments</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Patient</th>
                                                    <th>Doctor</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
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

                    <!-- Patients -->
                    <div class="tab-pane fade" id="pat-tab">
                        <!-- Patient Registration Form -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
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
                                                <label for="patientNIC">National ID (NIC) *</label>
                                                <input type="text" class="form-control" id="patientNIC" name="nic" 
                                                       placeholder="Enter NIC numbers only (e.g., 199012345678)" required>
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
                                                <input type="password" class="form-control" id="patientConfirmPassword" name="cpassword" required>
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
                        <div class="search-container mb-3">
                            <div class="search-bar">
                                <input type="text" class="form-control" id="patient-search" placeholder="Search patients...">
                                <i class="fa fa-search search-icon"></i>
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
                                        <td><?php echo $patient['dob']; ?></td>
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

                    <!-- Doctors -->
                    <div class="tab-pane fade" id="doc-tab">
                        <h4>Doctors List</h4>
                        <?php if($doctor_msg): echo $doctor_msg; endif; ?>
                        <div class="search-container mb-3">
                            <div class="search-bar">
                                <input type="text" class="form-control" id="doctor-search" placeholder="Search doctors...">
                                <i class="fa fa-search search-icon"></i>
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
                                            <button class="btn btn-sm btn-info" onclick="alert('Doctor Details:\\nID: <?php echo $doctor['id']; ?>\\nName: <?php echo $doctor['username']; ?>\\nSpecialization: <?php echo $doctor['spec']; ?>\\nEmail: <?php echo $doctor['email']; ?>\\nFees: Rs. <?php echo number_format($doctor['docFees'], 2); ?>')">
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

                    <!-- Appointments -->
                    <div class="tab-pane fade" id="app-tab">
                        <h4>Appointments</h4>
                        <?php if($appointment_msg): echo $appointment_msg; endif; ?>
                        
                        <!-- Add Appointment Form -->
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
                        
                        <div class="search-container mb-3">
                            <div class="search-bar">
                                <input type="text" class="form-control" id="appointment-search" placeholder="Search appointments...">
                                <i class="fa fa-search search-icon"></i>
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
                                        <td><?php echo $app['appdate']; ?></td>
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
                                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#cancelAppointmentModal" data-appointment-id="<?php echo $app['ID']; ?>">
                                                    <i class="fa fa-times"></i> Cancel
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    <i class="fa fa-times"></i> Cancelled
                                                </button>
                                            <?php endif; ?>
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
                        
                        <div class="search-container mb-3">
                            <div class="search-bar">
                                <input type="text" class="form-control" id="prescription-search" placeholder="Search prescriptions...">
                                <i class="fa fa-search search-icon"></i>
                            </div>
                        </div>
                        
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Doctor</th>
                                    <th>Patient ID</th>
                                    <th>Patient Name</th>
                                    <th>National ID</th>
                                    <th>Date</th>
                                    <th>Disease</th>
                                    <th>Prescription</th>
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
                                        <td><?php echo $pres['pid']; ?></td>
                                        <td><?php echo $pres['fname'] . ' ' . $pres['lname']; ?></td>
                                        <td><?php echo $pres['national_id']; ?></td>
                                        <td><?php echo $pres['appdate']; ?></td>
                                        <td><?php echo $pres['disease']; ?></td>
                                        <td style="max-width: 200px; word-wrap: break-word;"><?php echo $pres['prescription']; ?></td>
                                        <td>
                                            <?php 
                                            $status = $pres['emailStatus'];
                                            if($status == 'Not Sent'): ?>
                                                <span class="status-badge status-not-sent">Not Sent</span>
                                            <?php elseif($status == 'Sent to Hospital Pharmacy'): ?>
                                                <span class="status-badge status-hospital-pharmacy">Sent to Hospital</span>
                                            <?php elseif($status == 'Sent to Patient Contact (SMS)'): ?>
                                                <span class="status-badge status-patient-sms">Sent via SMS</span>
                                            <?php else: ?>
                                                <span class="status-badge"><?php echo $status; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($pres['emailStatus'] == 'Not Sent'): ?>
                                                <button class="btn btn-sm btn-primary" onclick="sendToHospitalPharmacy(<?php echo $pres['id']; ?>)">
                                                    <i class="fa fa-hospital-o"></i> Send to Hospital
                                                </button>
                                                <button class="btn btn-sm btn-success" onclick="sendToPatientContact(<?php echo $pres['id']; ?>)">
                                                    <i class="fa fa-mobile"></i> Send to Patient
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    Sent
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No prescriptions found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Payments -->
                    <div class="tab-pane fade" id="pay-tab">
                        <h4>Payments</h4>
                        <?php if($payment_msg): echo $payment_msg; endif; ?>
                        
                        <div class="search-container mb-3">
                            <div class="search-bar">
                                <input type="text" class="form-control" id="payment-search" placeholder="Search payments...">
                                <i class="fa fa-search search-icon"></i>
                            </div>
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
                                        <td><?php echo $pay['pay_date']; ?></td>
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
                                            <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editPaymentModal" data-payment-id="<?php echo $pay['id']; ?>">
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
                                        <form method="POST">
                                            <div class="form-group">
                                                <label>Doctor ID *</label>
                                                <input type="text" name="doctorId" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Name *</label>
                                                <input type="text" name="doctor" class="form-control" required>
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
                                                <input type="password" name="dpassword" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Confirm Password *</label>
                                                <input type="password" name="cdpassword" class="form-control" required>
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
                                        <form method="POST">
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
                                        <button class="btn btn-sm btn-info" onclick="alert('Doctor Details:\\nID: <?php echo $doctor['id']; ?>\\nName: <?php echo $doctor['username']; ?>\\nSpecialization: <?php echo $doctor['spec']; ?>\\nEmail: <?php echo $doctor['email']; ?>')">
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
                                        <button class="btn btn-sm btn-info" onclick="alert('Staff Details:\\nID: <?php echo $staff_member['id']; ?>\\nName: <?php echo $staff_member['name']; ?>\\nRole: <?php echo $staff_member['role']; ?>\\nEmail: <?php echo $staff_member['email']; ?>')">
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
    <div class="modal fade" id="deleteDoctorModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Doctor</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
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
    <div class="modal fade" id="cancelAppointmentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Appointment</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="cancel-appointment-form">
                        <div class="form-group">
                            <label for="cancellationReason">Cancellation Reason</label>
                            <textarea class="form-control" id="cancellationReason" rows="3"></textarea>
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
    <div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Edit Payment Status</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span style="color: white;">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="edit-payment-form">
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
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit-receipt-number">Receipt Number</label>
                                <input type="text" class="form-control" id="edit-receipt-number">
                            </div>
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
        let currentPaymentId = null;
        let currentAppointmentIdToCancel = null;
        
        // Search functionality
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
        
        // Setup search functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Patient search
            const patientSearch = document.getElementById('patient-search');
            if(patientSearch) {
                patientSearch.addEventListener('keyup', function() {
                    filterTable('patient-search', 'patients-table-body');
                });
            }
            
            // Doctor search
            const doctorSearch = document.getElementById('doctor-search');
            if(doctorSearch) {
                doctorSearch.addEventListener('keyup', function() {
                    filterTable('doctor-search', 'doctors-table-body');
                });
            }
            
            // Appointment search
            const appointmentSearch = document.getElementById('appointment-search');
            if(appointmentSearch) {
                appointmentSearch.addEventListener('keyup', function() {
                    filterTable('appointment-search', 'appointments-table-body');
                });
            }
            
            // Prescription search
            const prescriptionSearch = document.getElementById('prescription-search');
            if(prescriptionSearch) {
                prescriptionSearch.addEventListener('keyup', function() {
                    filterTable('prescription-search', 'prescriptions-table-body');
                });
            }
            
            // Payment search
            const paymentSearch = document.getElementById('payment-search');
            if(paymentSearch) {
                paymentSearch.addEventListener('keyup', function() {
                    filterTable('payment-search', 'payments-table-body');
                });
            }
            
            // Setup modal functionality
            $('#cancelAppointmentModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const appointmentId = button.data('appointment-id');
                currentAppointmentIdToCancel = appointmentId;
            });
            
            $('#editPaymentModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const paymentId = button.data('payment-id');
                currentPaymentId = paymentId;
            });
            
            // Payment status change listener
            const editPaymentStatus = document.getElementById('edit-payment-status');
            if(editPaymentStatus) {
                editPaymentStatus.addEventListener('change', function() {
                    const methodSection = document.getElementById('payment-method-section');
                    if (this.value === 'Paid') {
                        methodSection.style.display = 'block';
                    } else {
                        methodSection.style.display = 'none';
                    }
                });
            }
        });
        
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
        
        function sendToHospitalPharmacy(prescriptionId) {
            if(confirm('Send this prescription to Hospital Pharmacy?')) {
                // Submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const prescriptionIdInput = document.createElement('input');
                prescriptionIdInput.type = 'hidden';
                prescriptionIdInput.name = 'prescription_id';
                prescriptionIdInput.value = prescriptionId;
                form.appendChild(prescriptionIdInput);
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'send_to_hospital';
                actionInput.value = '1';
                form.appendChild(actionInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function sendToPatientContact(prescriptionId) {
            if(confirm('Send this prescription to Patient\'s Contact Number via SMS?')) {
                // Submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const prescriptionIdInput = document.createElement('input');
                prescriptionIdInput.type = 'hidden';
                prescriptionIdInput.name = 'prescription_id';
                prescriptionIdInput.value = prescriptionId;
                form.appendChild(prescriptionIdInput);
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'send_to_patient';
                actionInput.value = '1';
                form.appendChild(actionInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0.7';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 3000);
            });
        }, 5000);
    </script>
</body>
</html>