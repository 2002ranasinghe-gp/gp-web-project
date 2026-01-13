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
$schedule_msg = "";
$room_msg = "";

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
                // Insert patient with plain text password
                $query = "INSERT INTO patreg (fname, lname, gender, dob, email, contact, address, emergencyContact, national_id, password) 
                          VALUES ('$fname', '$lname', '$gender', '$dob', '$email', '$contact', '$address', '$emergencyContact', '$national_id', '$password')";
                
                if(mysqli_query($con, $query)){
                    $new_patient_id = mysqli_insert_id($con);
                    $patient_msg = "<div class='alert alert-success'>✅ Patient registered successfully! Patient ID: $new_patient_id, NIC: $national_id</div>";
                    $_SESSION['success'] = "Patient added successfully!";
                } else {
                    $patient_msg = "<div class='alert alert-danger'>❌ Database Error: " . mysqli_error($con) . "</div>";
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
// ADD APPOINTMENT (NEW FUNCTIONALITY)
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
// SCHEDULE MANAGEMENT
// ===========================
// ADD SCHEDULE
if(isset($_POST['add_schedule'])){
    $staff_type = mysqli_real_escape_string($con, $_POST['staff_type']);
    $staff_id = mysqli_real_escape_string($con, $_POST['staff_id']);
    $day = mysqli_real_escape_string($con, $_POST['day']);
    $shift = mysqli_real_escape_string($con, $_POST['shift']);
    $start_time = mysqli_real_escape_string($con, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($con, $_POST['end_time']);
    
    // Get staff/doctor name based on type
    if($staff_type == 'doctor') {
        $staff_query = mysqli_query($con, "SELECT username FROM doctb WHERE id='$staff_id'");
        $staff_data = mysqli_fetch_assoc($staff_query);
        $staff_name = $staff_data['username'];
        $role = 'Doctor';
    } else {
        $staff_query = mysqli_query($con, "SELECT name, role FROM stafftb WHERE id='$staff_id'");
        $staff_data = mysqli_fetch_assoc($staff_query);
        $staff_name = $staff_data['name'];
        $role = $staff_data['role'];
    }
    
    $query = "INSERT INTO scheduletb (staff_type, staff_id, staff_name, role, day, shift, start_time, end_time) 
              VALUES ('$staff_type', '$staff_id', '$staff_name', '$role', '$day', '$shift', '$start_time', '$end_time')";
    
    if(mysqli_query($con, $query)){
        $schedule_msg = "<div class='alert alert-success'>✅ Schedule added successfully!</div>";
        $_SESSION['success'] = "Schedule added successfully!";
    } else {
        $schedule_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// DELETE SCHEDULE
if(isset($_POST['delete_schedule'])){
    $schedule_id = mysqli_real_escape_string($con, $_POST['schedule_id']);
    
    $query = "DELETE FROM scheduletb WHERE id='$schedule_id'";
    
    if(mysqli_query($con, $query)){
        $schedule_msg = "<div class='alert alert-success'>✅ Schedule deleted successfully!</div>";
        $_SESSION['success'] = "Schedule deleted successfully!";
    } else {
        $schedule_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// UPDATE SCHEDULE
if(isset($_POST['update_schedule'])){
    $schedule_id = mysqli_real_escape_string($con, $_POST['schedule_id']);
    $day = mysqli_real_escape_string($con, $_POST['day']);
    $shift = mysqli_real_escape_string($con, $_POST['shift']);
    $start_time = mysqli_real_escape_string($con, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($con, $_POST['end_time']);
    
    $query = "UPDATE scheduletb SET 
              day='$day',
              shift='$shift',
              start_time='$start_time',
              end_time='$end_time'
              WHERE id='$schedule_id'";
    
    if(mysqli_query($con, $query)){
        $schedule_msg = "<div class='alert alert-success'>✅ Schedule updated successfully!</div>";
        $_SESSION['success'] = "Schedule updated successfully!";
    } else {
        $schedule_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// ADD ROOM (NEW FUNCTIONALITY)
// ===========================
if(isset($_POST['add_room'])){
    $room_no = mysqli_real_escape_string($con, $_POST['room_no']);
    $bed_no = mysqli_real_escape_string($con, $_POST['bed_no']);
    $type = mysqli_real_escape_string($con, $_POST['type']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    $query = "INSERT INTO roomtb (room_no, bed_no, type, status) 
              VALUES ('$room_no', '$bed_no', '$type', '$status')";
    
    if(mysqli_query($con, $query)){
        $room_msg = "<div class='alert alert-success'>✅ Room/Bed added successfully!</div>";
        $_SESSION['success'] = "Room/Bed added successfully!";
    } else {
        $room_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// UPDATE ROOM STATUS
if(isset($_POST['update_room_status'])){
    $room_id = mysqli_real_escape_string($con, $_POST['room_id']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    $query = "UPDATE roomtb SET status='$status' WHERE id='$room_id'";
    
    if(mysqli_query($con, $query)){
        $room_msg = "<div class='alert alert-success'>✅ Room status updated successfully!</div>";
        $_SESSION['success'] = "Room status updated!";
    } else {
        $room_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// DELETE ROOM
if(isset($_POST['delete_room'])){
    $room_id = mysqli_real_escape_string($con, $_POST['room_id']);
    
    $query = "DELETE FROM roomtb WHERE id='$room_id'";
    
    if(mysqli_query($con, $query)){
        $room_msg = "<div class='alert alert-success'>✅ Room/Bed deleted successfully!</div>";
        $_SESSION['success'] = "Room/Bed deleted successfully!";
    } else {
        $room_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
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
        
        // Update the status
        $query = "UPDATE prestb SET emailStatus='Sent to Patient Contact (SMS)' WHERE id='$prescription_id'";
        
        if(mysqli_query($con, $query)){
            $prescription_msg = "<div class='alert alert-success'>✅ Prescription sent to patient's contact number via SMS!<br>
                                <small>Patient: $patient_name<br>
                                Contact: $contact<br>
                                Message: Prescription details have been sent to your mobile number.</small></div>";
            $_SESSION['success'] = "Prescription sent to patient via SMS!";
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

// Get schedules - FIXED: Check if table exists and has columns
$schedule_result = mysqli_query($con, "SHOW TABLES LIKE 'scheduletb'");
if(mysqli_num_rows($schedule_result) == 1) {
    // Check if start_time column exists
    $check_column = mysqli_query($con, "SHOW COLUMNS FROM scheduletb LIKE 'start_time'");
    if(mysqli_num_rows($check_column) > 0) {
        // Column exists, use it in ORDER BY
        $schedule_result = mysqli_query($con, "SELECT * FROM scheduletb ORDER BY day, start_time");
    } else {
        // Column doesn't exist, order by day only
        $schedule_result = mysqli_query($con, "SELECT * FROM scheduletb ORDER BY day");
    }
    
    if($schedule_result){
        while($row = mysqli_fetch_assoc($schedule_result)){
            $schedules[] = $row;
        }
    }
} else {
    // Table doesn't exist yet, schedules will be empty
    $schedules = [];
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
$total_rooms = count($rooms);
$today_appointments = 0;
$today = date('Y-m-d');

foreach($appointments as $app){
    if($app['appdate'] == $today){
        $today_appointments++;
    }
}

// Get recent appointments (last 5)
$recent_appointments = array_slice($appointments, 0, 5);

// Get pending payments
$pending_payments = 0;
foreach($payments as $payment){
    if($payment['pay_status'] == 'Pending'){
        $pending_payments++;
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
            staff_type VARCHAR(10) NOT NULL DEFAULT 'staff',
            staff_id VARCHAR(20) NOT NULL,
            staff_name VARCHAR(50) NOT NULL,
            role VARCHAR(50) NOT NULL,
            day VARCHAR(20) NOT NULL,
            shift VARCHAR(20) NOT NULL,
            start_time TIME,
            end_time TIME,
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
            if(!mysqli_query($con, $create_sql)){
                echo "<div class='alert alert-danger'>❌ Error creating table $table_name: " . mysqli_error($con) . "</div>";
            }
        } else {
            // For existing scheduletb table, check if columns exist and alter if needed
            if($table_name == 'scheduletb') {
                // Check for staff_type column
                $check_column = mysqli_query($con, "SHOW COLUMNS FROM scheduletb LIKE 'staff_type'");
                if(mysqli_num_rows($check_column) == 0) {
                    // Add missing columns
                    $alter_queries = [
                        "ALTER TABLE scheduletb ADD COLUMN staff_type VARCHAR(10) NOT NULL DEFAULT 'staff'",
                        "ALTER TABLE scheduletb ADD COLUMN staff_id VARCHAR(20) NOT NULL DEFAULT ''",
                        "ALTER TABLE scheduletb ADD COLUMN start_time TIME",
                        "ALTER TABLE scheduletb ADD COLUMN end_time TIME"
                    ];
                    
                    foreach($alter_queries as $alter_query) {
                        if(!mysqli_query($con, $alter_query)) {
                            echo "<div class='alert alert-danger'>❌ Error altering table $table_name: " . mysqli_error($con) . "</div>";
                        }
                    }
                }
            }
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
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --blue: #007bff;
            --indigo: #6610f2;
            --purple: #6f42c1;
            --pink: #e83e8c;
            --orange: #fd7e14;
            --teal: #20c997;
            --cyan: #17a2b8;
        }
        
        body {
            padding-top: 70px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 16px;
            min-height: 100vh;
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.4rem;
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            border-radius: 5px;
            transform: translateY(-2px);
        }
        
        .container-fluid {
            padding: 20px;
        }
        
        /* Dashboard Cards */
        .dashboard-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .dashboard-card .card-body {
            padding: 25px;
        }
        
        .dashboard-card .card-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 10px;
            color: rgba(255,255,255,0.9);
        }
        
        .dashboard-card .card-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0;
            color: white;
        }
        
        .dashboard-card .card-icon {
            font-size: 2.5rem;
            opacity: 0.8;
            color: white;
        }
        
        /* Stats Colors */
        .card-doctor {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .card-patient {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .card-appointment {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .card-staff {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .card-room {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .card-payment {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        }
        
        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .chart-container h5 {
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        /* Recent Activity */
        .recent-activity {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        
        .activity-item:hover {
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        /* Tab Content */
        .tab-content {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            min-height: 500px;
        }
        
        .tab-content h4 {
            color: #333;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary);
        }
        
        /* Search Bar */
        .search-container {
            margin-bottom: 25px;
        }
        
        .search-bar {
            position: relative;
        }
        
        .search-bar input {
            padding-left: 45px;
            border-radius: 25px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .search-bar input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .search-bar .search-icon {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #6c757d;
        }
        
        /* Tables */
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .table th {
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table td {
            vertical-align: middle;
            border-color: #f0f0f0;
        }
        
        .table tbody tr {
            transition: all 0.3s;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.002);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-available {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-occupied {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Action Buttons */
        .action-btn {
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        
        /* Forms */
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        
        /* Sidebar */
        .list-group-item {
            border: none;
            padding: 15px 20px;
            margin-bottom: 5px;
            border-radius: 10px !important;
            font-weight: 500;
            color: #555;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .list-group-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: var(--primary);
            color: white;
            transform: translateX(5px);
        }
        
        .list-group-item:not(.active):hover {
            background-color: #f8f9fa;
            color: var(--primary);
            transform: translateX(5px);
            border-left-color: var(--primary);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .quick-action-btn {
            background: white;
            border-radius: 15px;
            padding: 20px 15px;
            text-align: center;
            color: #555;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 2px solid transparent;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            color: var(--primary);
            border-color: var(--primary);
            text-decoration: none;
        }
        
        .quick-action-btn i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        /* Modal Styling */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }
        
        /* Alert Styling */
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        /* Custom Card */
        .custom-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .custom-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 25px;
            font-weight: 600;
        }
        
        .custom-card .card-body {
            padding: 25px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .dashboard-card .card-value {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            body {
                padding-top: 60px;
            }
            
            .container-fluid {
                padding: 10px;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tab-pane {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
    </style>
    <script>
        // Global variables
        let currentScheduleId = null;
        let currentScheduleData = {};
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dashboard
            updateDashboardCounts();
            initializeCharts();
            
            // Setup form validations
            setupFormValidations();
            
            // Setup modal functionality
            setupModalFunctionality();
            
            // Setup schedule functionality
            setupScheduleFunctionality();
            
            // Auto-refresh messages
            autoRefreshMessages();
            
            <?php if(!empty($patient_msg)): ?>
                setTimeout(function() {
                    document.querySelector('a[href="#pat-tab"]').click();
                }, 500);
            <?php endif; ?>
            
            <?php if(!empty($schedule_msg)): ?>
                setTimeout(function() {
                    document.querySelector('a[href="#sched-tab"]').click();
                }, 500);
            <?php endif; ?>
        });
        
        function updateDashboardCounts() {
            // These values are set by PHP
            document.getElementById('total-doctors').textContent = '<?php echo $total_doctors; ?>';
            document.getElementById('total-patients').textContent = '<?php echo $total_patients; ?>';
            document.getElementById('total-appointments').textContent = '<?php echo $total_appointments; ?>';
            document.getElementById('total-staff').textContent = '<?php echo $total_staff; ?>';
            document.getElementById('total-rooms').textContent = '<?php echo $total_rooms; ?>';
            document.getElementById('pending-payments').textContent = '<?php echo $pending_payments; ?>';
        }
        
        function initializeCharts() {
            // Appointments Chart
            const appointmentsCtx = document.getElementById('appointmentsChart');
            if(appointmentsCtx) {
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
                
                new Chart(appointmentsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Cancelled'],
                        datasets: [{
                            data: [<?php echo $active_apps; ?>, <?php echo $cancelled_apps; ?>],
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 99, 132, 0.8)'
                            ],
                            borderColor: [
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 99, 132, 1)'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Department Chart
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
                
                new Chart(departmentCtx, {
                    type: 'pie',
                    data: {
                        labels: specLabels,
                        datasets: [{
                            data: specCount,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 206, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(153, 102, 255, 0.8)',
                                'rgba(255, 159, 64, 0.8)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }
        
        function setupFormValidations() {
            // Password matching for patient registration
            const patientConfirmPassword = document.getElementById('patientConfirmPassword');
            if(patientConfirmPassword) {
                patientConfirmPassword.addEventListener('input', function() {
                    checkPasswordMatch('patientPassword', 'patientConfirmPassword', 'patientPasswordMessage');
                });
            }
            
            // Password matching for doctor registration
            const cdpassword = document.getElementById('cdpassword');
            if(cdpassword) {
                cdpassword.addEventListener('input', function() {
                    checkPasswordMatch('dpassword', 'cdpassword', 'doctorPasswordMessage');
                });
            }
            
            // NIC formatting
            const patientNIC = document.getElementById('patientNIC');
            if(patientNIC) {
                patientNIC.addEventListener('input', formatNIC);
            }
        }
        
        function checkPasswordMatch(passwordId, confirmId, messageId) {
            const password = document.getElementById(passwordId).value;
            const confirmPassword = document.getElementById(confirmId).value;
            const messageElement = document.getElementById(messageId);
            
            if(!messageElement) return;
            
            if(password === confirmPassword) {
                messageElement.innerHTML = '<i class="fa fa-check-circle mr-1"></i> Passwords match';
                messageElement.className = 'text-success small mt-1';
            } else {
                messageElement.innerHTML = '<i class="fa fa-times-circle mr-1"></i> Passwords do not match';
                messageElement.className = 'text-danger small mt-1';
            }
        }
        
        function formatNIC() {
            const nicInput = this;
            const nicDisplay = document.getElementById('generatedNICDisplay');
            
            let nicValue = nicInput.value.replace(/[^0-9]/g, '');
            nicInput.value = nicValue;
            
            if (nicValue) {
                nicDisplay.innerHTML = `<strong>Formatted NIC:</strong> NIC${nicValue}`;
                nicDisplay.className = 'alert alert-info p-2 mt-2';
            } else {
                nicDisplay.innerHTML = 'Enter NIC number above (numbers only)';
                nicDisplay.className = 'alert alert-light p-2 mt-2';
            }
        }
        
        function setupModalFunctionality() {
            // Edit schedule modal
            $('#editScheduleModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                currentScheduleId = button.data('schedule-id');
                
                // Find the schedule data from the table row
                const row = button.closest('tr');
                currentScheduleData = {
                    day: row.find('td:eq(3)').text(),
                    shift: row.find('td:eq(4)').text(),
                    start_time: row.find('td:eq(5)').text(),
                    end_time: row.find('td:eq(6)').text()
                };
                
                // Populate the form
                document.getElementById('edit-day').value = currentScheduleData.day;
                document.getElementById('edit-shift').value = currentScheduleData.shift;
                document.getElementById('edit-start-time').value = currentScheduleData.start_time;
                document.getElementById('edit-end-time').value = currentScheduleData.end_time;
            });
        }
        
        function setupScheduleFunctionality() {
            // Staff type change handler
            const staffTypeSelect = document.getElementById('staff_type');
            const staffIdSelect = document.getElementById('staff_id');
            
            if(staffTypeSelect && staffIdSelect) {
                staffTypeSelect.addEventListener('change', function() {
                    updateStaffOptions();
                });
                
                // Initial load
                updateStaffOptions();
            }
        }
        
        function updateStaffOptions() {
            const staffType = document.getElementById('staff_type').value;
            const staffIdSelect = document.getElementById('staff_id');
            
            if(!staffType) {
                staffIdSelect.innerHTML = '<option value="">Select staff/doctor first</option>';
                return;
            }
            
            // Clear existing options
            staffIdSelect.innerHTML = '<option value="">Select...</option>';
            
            // Add options based on type
            if(staffType === 'doctor') {
                <?php foreach($doctors as $doctor): ?>
                staffIdSelect.innerHTML += `<option value="<?php echo $doctor['id']; ?>">
                    <?php echo $doctor['username']; ?> (<?php echo $doctor['spec']; ?>)
                </option>`;
                <?php endforeach; ?>
            } else {
                <?php foreach($staff as $staff_member): ?>
                staffIdSelect.innerHTML += `<option value="<?php echo $staff_member['id']; ?>">
                    <?php echo $staff_member['name']; ?> (<?php echo $staff_member['role']; ?>)
                </option>`;
                <?php endforeach; ?>
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
        
        function confirmDeleteSchedule(scheduleId) {
            if(confirm('Are you sure you want to delete this schedule?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const scheduleIdInput = document.createElement('input');
                scheduleIdInput.type = 'hidden';
                scheduleIdInput.name = 'schedule_id';
                scheduleIdInput.value = scheduleId;
                form.appendChild(scheduleIdInput);
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'delete_schedule';
                actionInput.value = '1';
                form.appendChild(actionInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function updateSchedule() {
            if(!currentScheduleId) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const scheduleIdInput = document.createElement('input');
            scheduleIdInput.type = 'hidden';
            scheduleIdInput.name = 'schedule_id';
            scheduleIdInput.value = currentScheduleId;
            form.appendChild(scheduleIdInput);
            
            const dayInput = document.createElement('input');
            dayInput.type = 'hidden';
            dayInput.name = 'day';
            dayInput.value = document.getElementById('edit-day').value;
            form.appendChild(dayInput);
            
            const shiftInput = document.createElement('input');
            shiftInput.type = 'hidden';
            shiftInput.name = 'shift';
            shiftInput.value = document.getElementById('edit-shift').value;
            form.appendChild(shiftInput);
            
            const startTimeInput = document.createElement('input');
            startTimeInput.type = 'hidden';
            startTimeInput.name = 'start_time';
            startTimeInput.value = document.getElementById('edit-start-time').value;
            form.appendChild(startTimeInput);
            
            const endTimeInput = document.createElement('input');
            endTimeInput.type = 'hidden';
            endTimeInput.name = 'end_time';
            endTimeInput.value = document.getElementById('edit-end-time').value;
            form.appendChild(endTimeInput);
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'update_schedule';
            actionInput.value = '1';
            form.appendChild(actionInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function confirmDeleteRoom(roomId) {
            if(confirm('Are you sure you want to delete this room/bed?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const roomIdInput = document.createElement('input');
                roomIdInput.type = 'hidden';
                roomIdInput.name = 'room_id';
                roomIdInput.value = roomId;
                form.appendChild(roomIdInput);
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'delete_room';
                actionInput.value = '1';
                form.appendChild(actionInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function updateRoomStatus(roomId) {
            const status = prompt('Enter new status (Available/Occupied/Maintenance):');
            if(status && ['Available', 'Occupied', 'Maintenance'].includes(status)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const roomIdInput = document.createElement('input');
                roomIdInput.type = 'hidden';
                roomIdInput.name = 'room_id';
                roomIdInput.value = roomId;
                form.appendChild(roomIdInput);
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = status;
                form.appendChild(statusInput);
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'update_room_status';
                actionInput.value = '1';
                form.appendChild(actionInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function exportTable(tableBodyId, filename) {
            const table = document.getElementById(tableBodyId);
            if(!table) return;
            
            const rows = table.getElementsByTagName('tr');
            let csv = [];
            
            // Get headers from thead
            const thead = table.closest('table').getElementsByTagName('thead')[0];
            const headerCells = thead.getElementsByTagName('th');
            const headerRow = [];
            for (let i = 0; i < headerCells.length; i++) {
                if(!headerCells[i].innerText.includes('Actions')) {
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
                    // Skip action columns
                    if(!colText.includes('View') && !colText.includes('Edit') && !colText.includes('Delete')) {
                        row.push('"' + colText.replace(/"/g, '""') + '"');
                    }
                }
                
                if(row.length > 0) {
                    csv.push(row.join(','));
                }
            }
            
            // Download CSV
            const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            const downloadLink = document.createElement('a');
            downloadLink.download = filename + '_' + new Date().toISOString().split('T')[0] + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <a class="navbar-brand" href="#"><i class="fa fa-hospital-o mr-2"></i> Heth Care Hospital - Admin Panel</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fa fa-bell"></i> 
                        <span class="badge badge-light" id="notification-count"><?php echo $today_appointments; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="fa fa-user-circle mr-1"></i> Admin</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout1.php"><i class="fa fa-sign-out mr-1"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid">
        <?php if($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-3">
                <div class="list-group" id="list-tab" role="tablist">
                    <a class="list-group-item list-group-item-action active" data-toggle="list" href="#dash-tab">
                        <i class="fa fa-tachometer mr-2"></i> Dashboard Overview
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#doc-tab">
                        <i class="fa fa-user-md mr-2"></i> Doctors Management
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#pat-tab">
                        <i class="fa fa-users mr-2"></i> Patients Management
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#app-tab">
                        <i class="fa fa-calendar mr-2"></i> Appointments
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#pres-tab">
                        <i class="fa fa-file-text mr-2"></i> Prescriptions
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#pay-tab">
                        <i class="fa fa-credit-card mr-2"></i> Payments
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#sched-tab">
                        <i class="fa fa-clock-o mr-2"></i> Staff Schedules
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#room-tab">
                        <i class="fa fa-bed mr-2"></i> Rooms & Beds
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#staff-tab">
                        <i class="fa fa-id-badge mr-2"></i> Staff Management
                    </a>
                </div>
            </div>

            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane fade show active" id="dash-tab">
                        <h4><i class="fa fa-tachometer mr-2"></i> Dashboard Overview</h4>
                        
                        <!-- Quick Actions -->
                        <div class="quick-actions mb-4">
                            <a class="quick-action-btn" data-toggle="list" href="#doc-tab">
                                <i class="fa fa-user-md"></i>
                                <div>Manage Doctors</div>
                            </a>
                            <a class="quick-action-btn" data-toggle="list" href="#pat-tab">
                                <i class="fa fa-user-plus"></i>
                                <div>Add Patient</div>
                            </a>
                            <a class="quick-action-btn" data-toggle="list" href="#app-tab">
                                <i class="fa fa-calendar-plus-o"></i>
                                <div>New Appointment</div>
                            </a>
                            <a class="quick-action-btn" data-toggle="list" href="#sched-tab">
                                <i class="fa fa-calendar-check-o"></i>
                                <div>Add Schedule</div>
                            </a>
                            <a class="quick-action-btn" data-toggle="list" href="#room-tab">
                                <i class="fa fa-bed"></i>
                                <div>Manage Rooms</div>
                            </a>
                            <a class="quick-action-btn" data-toggle="modal" data-target="#deleteDoctorModal">
                                <i class="fa fa-trash"></i>
                                <div>Delete Doctor</div>
                            </a>
                        </div>
                        
                        <!-- Stats Cards -->
                        <div class="row">
                            <div class="col-md-4 col-lg-2">
                                <div class="dashboard-card card-doctor">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="card-title">Doctors</div>
                                                <div class="card-value" id="total-doctors">0</div>
                                            </div>
                                            <div class="card-icon">
                                                <i class="fa fa-user-md"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <div class="dashboard-card card-patient">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="card-title">Patients</div>
                                                <div class="card-value" id="total-patients">0</div>
                                            </div>
                                            <div class="card-icon">
                                                <i class="fa fa-users"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <div class="dashboard-card card-appointment">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="card-title">Appointments</div>
                                                <div class="card-value" id="total-appointments">0</div>
                                            </div>
                                            <div class="card-icon">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <div class="dashboard-card card-staff">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="card-title">Staff</div>
                                                <div class="card-value" id="total-staff">0</div>
                                            </div>
                                            <div class="card-icon">
                                                <i class="fa fa-id-badge"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <div class="dashboard-card card-room">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="card-title">Rooms</div>
                                                <div class="card-value" id="total-rooms">0</div>
                                            </div>
                                            <div class="card-icon">
                                                <i class="fa fa-bed"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <div class="dashboard-card card-payment">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="card-title">Pending Payments</div>
                                                <div class="card-value" id="pending-payments">0</div>
                                            </div>
                                            <div class="card-icon">
                                                <i class="fa fa-credit-card"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Charts and Additional Stats -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <h5><i class="fa fa-pie-chart mr-2"></i> Appointments Distribution</h5>
                                    <canvas id="appointmentsChart" height="250"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <h5><i class="fa fa-bar-chart mr-2"></i> Department Distribution</h5>
                                    <canvas id="departmentChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <h5><i class="fa fa-history mr-2"></i> Recent Activity</h5>
                                    <div class="recent-activity">
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
                                            <div><?php echo $today_appointments; ?> appointments scheduled</div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <h5><i class="fa fa-calendar-check-o mr-2"></i> Recent Appointments</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Patient</th>
                                                    <th>Doctor</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="today-appointments">
                                                <?php if(count($recent_appointments) > 0): ?>
                                                    <?php foreach($recent_appointments as $app): ?>
                                                    <tr>
                                                        <td>
                                                            <?php echo date('M d', strtotime($app['appdate'])); ?><br>
                                                            <small><?php echo date('h:i A', strtotime($app['apptime'])); ?></small>
                                                        </td>
                                                        <td><?php echo substr($app['fname'] . ' ' . $app['lname'], 0, 15); ?>...</td>
                                                        <td><?php echo substr($app['doctor'], 0, 15); ?>...</td>
                                                        <td>
                                                            <?php if($app['appointmentStatus'] == 'active'): ?>
                                                                <span class="status-badge status-active">Active</span>
                                                            <?php else: ?>
                                                                <span class="status-badge status-cancelled">Cancelled</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No recent appointments</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Doctors Tab -->
                    <div class="tab-pane fade" id="doc-tab">
                        <h4><i class="fa fa-user-md mr-2"></i> Doctors Management</h4>
                        <?php if($doctor_msg): echo $doctor_msg; endif; ?>
                        
                        <div class="search-container">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="search-bar">
                                        <input type="text" class="form-control" id="doctor-search" 
                                               placeholder="Search doctors by name, ID, or specialization..." 
                                               onkeyup="filterTable('doctor-search', 'doctors-table-body')">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" onclick="exportTable('doctors-table-body', 'doctors')">
                                        <i class="fa fa-download mr-2"></i> Export Doctors
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
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
                                            <td><span class="badge bg-primary"><?php echo $doctor['id']; ?></span></td>
                                            <td><?php echo $doctor['username']; ?></td>
                                            <td><span class="badge bg-info"><?php echo $doctor['spec']; ?></span></td>
                                            <td><?php echo $doctor['email']; ?></td>
                                            <td class="font-weight-bold text-success">Rs. <?php echo number_format($doctor['docFees'], 2); ?></td>
                                            <td><?php echo $doctor['contact'] ? $doctor['contact'] : 'N/A'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info action-btn" 
                                                        onclick="alert('Doctor Details:\\nID: <?php echo $doctor['id']; ?>\\nName: <?php echo $doctor['username']; ?>\\nSpecialization: <?php echo $doctor['spec']; ?>\\nEmail: <?php echo $doctor['email']; ?>\\nFees: Rs. <?php echo number_format($doctor['docFees'], 2); ?>\\nContact: <?php echo $doctor['contact']; ?>')">
                                                    <i class="fa fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fa fa-user-md fa-3x mb-3"></i><br>
                                                No doctors found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Patients Tab -->
                    <div class="tab-pane fade" id="pat-tab">
                        <!-- Patient Registration Form -->
                        <div class="custom-card mb-4">
                            <div class="card-header">
                                <i class="fa fa-user-plus mr-2"></i>Register New Patient
                            </div>
                            <div class="card-body">
                                <?php if($patient_msg): echo $patient_msg; endif; ?>
                                <form method="POST" id="add-patient-form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientFirstName" class="form-label">First Name *</label>
                                                <input type="text" class="form-control" id="patientFirstName" name="fname" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientLastName" class="form-label">Last Name *</label>
                                                <input type="text" class="form-control" id="patientLastName" name="lname" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientGender" class="form-label">Gender *</label>
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
                                                <label for="patientDOB" class="form-label">Date of Birth *</label>
                                                <input type="date" class="form-control" id="patientDOB" name="dob" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientEmail" class="form-label">Email Address *</label>
                                                <input type="email" class="form-control" id="patientEmail" name="email" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientContact" class="form-label">Contact Number *</label>
                                                <input type="tel" class="form-control" id="patientContact" name="contact" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientNIC" class="form-label">National ID (NIC) *</label>
                                                <input type="text" class="form-control" id="patientNIC" name="nic" 
                                                       placeholder="Enter NIC numbers only (e.g., 199012345678)" required>
                                                <small class="text-muted">Enter numbers only, "NIC" will be added automatically</small>
                                                <div id="generatedNICDisplay" class="alert alert-light p-2 mt-2">
                                                    Enter NIC number above (numbers only)
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientEmergencyContact" class="form-label">Emergency Contact</label>
                                                <input type="tel" class="form-control" id="patientEmergencyContact" name="emergencyContact">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientPassword" class="form-label">Password *</label>
                                                <input type="password" class="form-control" id="patientPassword" name="password" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientConfirmPassword" class="form-label">Confirm Password *</label>
                                                <input type="password" class="form-control" id="patientConfirmPassword" name="cpassword" required>
                                                <small id="patientPasswordMessage" class="form-text"></small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="patientAddress" class="form-label">Address</label>
                                                <textarea class="form-control" id="patientAddress" name="address" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
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
                        
                        <h4><i class="fa fa-users mr-2"></i> Patients List</h4>
                        <div class="search-container">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="search-bar">
                                        <input type="text" class="form-control" id="patient-search" 
                                               placeholder="Search patients by NIC, name, or contact..." 
                                               onkeyup="filterTable('patient-search', 'patients-table-body')">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" onclick="exportTable('patients-table-body', 'patients')">
                                        <i class="fa fa-download mr-2"></i> Export Patients
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
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
                                            <td><span class="badge bg-primary"><?php echo $patient['pid']; ?></span></td>
                                            <td><?php echo $patient['fname']; ?></td>
                                            <td><?php echo $patient['lname']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $patient['gender'] == 'Male' ? 'info' : ($patient['gender'] == 'Female' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo $patient['gender']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $patient['email']; ?></td>
                                            <td><?php echo $patient['contact']; ?></td>
                                            <td><?php echo $patient['dob'] ? date('Y-m-d', strtotime($patient['dob'])) : 'N/A'; ?></td>
                                            <td><span class="badge bg-dark"><?php echo $patient['national_id']; ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fa fa-users fa-3x mb-3"></i><br>
                                                No patients found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Appointments Tab -->
                    <div class="tab-pane fade" id="app-tab">
                        <h4><i class="fa fa-calendar mr-2"></i> Appointments Management</h4>
                        <?php if($appointment_msg): echo $appointment_msg; endif; ?>
                        
                        <!-- Add Appointment Form -->
                        <div class="custom-card mb-4">
                            <div class="card-header">
                                <i class="fa fa-plus-circle mr-2"></i>Create New Appointment
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patient_id" class="form-label">Patient ID *</label>
                                                <input type="number" class="form-control" id="patient_id" name="patient_id" required>
                                                <small class="text-muted">Enter patient ID from patients list</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="doctor" class="form-label">Doctor *</label>
                                                <select class="form-control" id="doctor" name="doctor" required>
                                                    <option value="">Select Doctor</option>
                                                    <?php foreach($doctors as $doctor): ?>
                                                    <option value="<?php echo $doctor['username']; ?>">
                                                        Dr. <?php echo $doctor['username']; ?> (<?php echo $doctor['spec']; ?>)
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="appdate" class="form-label">Appointment Date *</label>
                                                <input type="date" class="form-control" id="appdate" name="appdate" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="apptime" class="form-label">Appointment Time *</label>
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
                                        <input type="text" class="form-control" id="appointment-search" 
                                               placeholder="Search appointments..." 
                                               onkeyup="filterTable('appointment-search', 'appointments-table-body')">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" onclick="exportTable('appointments-table-body', 'appointments')">
                                        <i class="fa fa-download mr-2"></i> Export Appointments
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Patient ID</th>
                                        <th>Patient Name</th>
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
                                            <td><span class="badge bg-primary"><?php echo $app['ID']; ?></span></td>
                                            <td><span class="badge bg-info"><?php echo $app['pid']; ?></span></td>
                                            <td><?php echo $app['fname'] . ' ' . $app['lname']; ?></td>
                                            <td><?php echo $app['doctor']; ?></td>
                                            <td class="font-weight-bold text-success">Rs. <?php echo number_format($app['docFees'], 2); ?></td>
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
                                                    <button class="btn btn-sm btn-danger action-btn" 
                                                            data-toggle="modal" 
                                                            data-target="#cancelAppointmentModal" 
                                                            data-appointment-id="<?php echo $app['ID']; ?>">
                                                        <i class="fa fa-times"></i> Cancel
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary action-btn" disabled>
                                                        <i class="fa fa-times"></i> Cancelled
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-info action-btn" 
                                                        onclick="alert('Appointment Details:\\nID: <?php echo $app['ID']; ?>\\nPatient: <?php echo $app['fname'] . ' ' . $app['lname']; ?>\\nContact: <?php echo $app['contact']; ?>\\nDoctor: <?php echo $app['doctor']; ?>\\nDate: <?php echo $app['appdate']; ?>\\nTime: <?php echo $app['apptime']; ?>\\nFees: Rs. <?php echo number_format($app['docFees'], 2); ?>\\nStatus: <?php echo $app['appointmentStatus']; ?>')">
                                                    <i class="fa fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="fa fa-calendar fa-3x mb-3"></i><br>
                                                No appointments found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Prescriptions Tab -->
                    <div class="tab-pane fade" id="pres-tab">
                        <h4><i class="fa fa-file-text mr-2"></i> Prescriptions Management</h4>
                        <?php if($prescription_msg): echo $prescription_msg; endif; ?>
                        
                        <div class="search-container">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="search-bar">
                                        <input type="text" class="form-control" id="prescription-search" 
                                               placeholder="Search prescriptions..." 
                                               onkeyup="filterTable('prescription-search', 'prescriptions-table-body')">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" onclick="exportTable('prescriptions-table-body', 'prescriptions')">
                                        <i class="fa fa-download mr-2"></i> Export Prescriptions
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Doctor</th>
                                        <th>Patient Name</th>
                                        <th>Date</th>
                                        <th>Disease</th>
                                        <th>Prescription</th>
                                        <th>Status</th>
                                        <th>Send Options</th>
                                    </tr>
                                </thead>
                                <tbody id="prescriptions-table-body">
                                    <?php if(count($prescriptions) > 0): ?>
                                        <?php foreach($prescriptions as $pres): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo $pres['id']; ?></span></td>
                                            <td><?php echo $pres['doctor']; ?></td>
                                            <td><?php echo $pres['fname'] . ' ' . $pres['lname']; ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($pres['appdate'])); ?></td>
                                            <td><?php echo $pres['disease']; ?></td>
                                            <td style="max-width: 200px; word-wrap: break-word;"><?php echo $pres['prescription']; ?></td>
                                            <td>
                                                <?php 
                                                $status = $pres['emailStatus'];
                                                if($status == 'Not Sent'): ?>
                                                    <span class="status-badge status-pending">Not Sent</span>
                                                <?php elseif($status == 'Sent to Hospital Pharmacy'): ?>
                                                    <span class="status-badge status-active">Sent to Hospital</span>
                                                <?php elseif($status == 'Sent to Patient Contact (SMS)'): ?>
                                                    <span class="status-badge status-active">Sent via SMS</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-pending"><?php echo $status; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button class="btn btn-sm btn-primary action-btn" 
                                                            onclick="if(confirm('Send this prescription to Hospital Pharmacy?')){ 
                                                                const form = document.createElement('form'); 
                                                                form.method='POST'; 
                                                                form.style.display='none'; 
                                                                const input = document.createElement('input'); 
                                                                input.type='hidden'; input.name='prescription_id'; input.value='<?php echo $pres['id']; ?>'; 
                                                                form.appendChild(input); 
                                                                const action = document.createElement('input'); 
                                                                action.type='hidden'; action.name='send_to_hospital'; action.value='1'; 
                                                                form.appendChild(action); 
                                                                document.body.appendChild(form); form.submit(); 
                                                            }">
                                                        <i class="fa fa-hospital-o mr-1"></i> To Hospital
                                                    </button>
                                                    <button class="btn btn-sm btn-success action-btn" 
                                                            onclick="if(confirm('Send this prescription to Patient\\'s Contact via SMS?')){ 
                                                                const form = document.createElement('form'); 
                                                                form.method='POST'; 
                                                                form.style.display='none'; 
                                                                const input = document.createElement('input'); 
                                                                input.type='hidden'; input.name='prescription_id'; input.value='<?php echo $pres['id']; ?>'; 
                                                                form.appendChild(input); 
                                                                const action = document.createElement('input'); 
                                                                action.type='hidden'; action.name='send_to_patient'; action.value='1'; 
                                                                form.appendChild(action); 
                                                                document.body.appendChild(form); form.submit(); 
                                                            }">
                                                        <i class="fa fa-mobile mr-1"></i> To Patient SMS
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fa fa-file-text fa-3x mb-3"></i><br>
                                                No prescriptions found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payments Tab -->
                    <div class="tab-pane fade" id="pay-tab">
                        <h4><i class="fa fa-credit-card mr-2"></i> Payments Management</h4>
                        <?php if($payment_msg): echo $payment_msg; endif; ?>
                        
                        <div class="search-container">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="search-bar">
                                        <input type="text" class="form-control" id="payment-search" 
                                               placeholder="Search payments..." 
                                               onkeyup="filterTable('payment-search', 'payments-table-body')">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" onclick="exportTable('payments-table-body', 'payments')">
                                        <i class="fa fa-download mr-2"></i> Export Payments
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Patient Name</th>
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
                                            <td><span class="badge bg-primary"><?php echo $pay['id']; ?></span></td>
                                            <td><?php echo $pay['patient_name']; ?></td>
                                            <td><?php echo $pay['doctor']; ?></td>
                                            <td class="font-weight-bold text-success">Rs. <?php echo number_format($pay['fees'], 2); ?></td>
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
                                                <button class="btn btn-sm btn-info action-btn" 
                                                        onclick="alert('Payment Details:\\nID: <?php echo $pay['id']; ?>\\nPatient: <?php echo $pay['patient_name']; ?>\\nDoctor: <?php echo $pay['doctor']; ?>\\nAmount: Rs. <?php echo number_format($pay['fees'], 2); ?>\\nDate: <?php echo $pay['pay_date']; ?>\\nStatus: <?php echo $pay['pay_status']; ?>\\nMethod: <?php echo $pay['payment_method']; ?>\\nReceipt: <?php echo $pay['receipt_no']; ?>')">
                                                    <i class="fa fa-eye"></i> View
                                                </button>
                                                <button class="btn btn-sm btn-warning action-btn" 
                                                        data-toggle="modal" 
                                                        data-target="#editPaymentModal" 
                                                        data-payment-id="<?php echo $pay['id']; ?>">
                                                    <i class="fa fa-edit"></i> Edit Status
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="fa fa-credit-card fa-3x mb-3"></i><br>
                                                No payments found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Staff Schedules Tab -->
                    <div class="tab-pane fade" id="sched-tab">
                        <h4><i class="fa fa-clock-o mr-2"></i> Staff & Doctor Schedules</h4>
                        <?php if($schedule_msg): echo $schedule_msg; endif; ?>
                        
                        <!-- Add Schedule Form -->
                        <div class="custom-card mb-4">
                            <div class="card-header">
                                <i class="fa fa-plus-circle mr-2"></i>Add New Schedule
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="staff_type" class="form-label">Staff Type *</label>
                                                <select class="form-control" id="staff_type" name="staff_type" required onchange="updateStaffOptions()">
                                                    <option value="">Select Type</option>
                                                    <option value="doctor">Doctor</option>
                                                    <option value="staff">Staff Member</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="staff_id" class="form-label">Staff/Doctor *</label>
                                                <select class="form-control" id="staff_id" name="staff_id" required>
                                                    <option value="">Select staff/doctor first</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="day" class="form-label">Day *</label>
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
                                                <label for="shift" class="form-label">Shift *</label>
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
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="start_time" class="form-label">Start Time</label>
                                                <input type="time" class="form-control" id="start_time" name="start_time">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="end_time" class="form-label">End Time</label>
                                                <input type="time" class="form-control" id="end_time" name="end_time">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="add_schedule" class="btn btn-success">
                                        <i class="fa fa-plus mr-1"></i> Add Schedule
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="search-container">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="search-bar">
                                        <input type="text" class="form-control" id="schedule-search" 
                                               placeholder="Search schedules..." 
                                               onkeyup="filterTable('schedule-search', 'schedules-table-body')">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" onclick="exportTable('schedules-table-body', 'schedules')">
                                        <i class="fa fa-download mr-2"></i> Export Schedules
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Staff/Doctor Name</th>
                                        <th>Role</th>
                                        <th>Day</th>
                                        <th>Shift</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="schedules-table-body">
                                    <?php if(count($schedules) > 0): ?>
                                        <?php foreach($schedules as $schedule): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo $schedule['id']; ?></span></td>
                                            <td>
                                                <span class="badge bg-<?php echo $schedule['staff_type'] == 'doctor' ? 'info' : 'warning'; ?>">
                                                    <?php echo $schedule['staff_type']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $schedule['staff_name']; ?></td>
                                            <td><?php echo $schedule['role']; ?></td>
                                            <td><?php echo $schedule['day']; ?></td>
                                            <td><span class="badge bg-dark"><?php echo $schedule['shift']; ?></span></td>
                                            <td><?php echo isset($schedule['start_time']) && $schedule['start_time'] != '00:00:00' ? date('h:i A', strtotime($schedule['start_time'])) : 'N/A'; ?></td>
                                            <td><?php echo isset($schedule['end_time']) && $schedule['end_time'] != '00:00:00' ? date('h:i A', strtotime($schedule['end_time'])) : 'N/A'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning action-btn" 
                                                        data-toggle="modal" 
                                                        data-target="#editScheduleModal" 
                                                        data-schedule-id="<?php echo $schedule['id']; ?>">
                                                    <i class="fa fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger action-btn" 
                                                        onclick="confirmDeleteSchedule(<?php echo $schedule['id']; ?>)">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="fa fa-calendar fa-3x mb-3"></i><br>
                                                No schedules found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Rooms & Beds Tab -->
                    <div class="tab-pane fade" id="room-tab">
                        <h4><i class="fa fa-bed mr-2"></i> Rooms & Beds Management</h4>
                        <?php if($room_msg): echo $room_msg; endif; ?>
                        
                        <!-- Add Room Form -->
                        <div class="custom-card mb-4">
                            <div class="card-header">
                                <i class="fa fa-plus-circle mr-2"></i>Add New Room/Bed
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="room_no" class="form-label">Room Number *</label>
                                                <input type="text" class="form-control" id="room_no" name="room_no" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="bed_no" class="form-label">Bed Number *</label>
                                                <input type="text" class="form-control" id="bed_no" name="bed_no" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="type" class="form-label">Type *</label>
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
                                                <label for="status" class="form-label">Status *</label>
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
                        
                        <div class="search-container">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="search-bar">
                                        <input type="text" class="form-control" id="room-search" 
                                               placeholder="Search rooms..." 
                                               onkeyup="filterTable('room-search', 'rooms-table-body')">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" onclick="exportTable('rooms-table-body', 'rooms')">
                                        <i class="fa fa-download mr-2"></i> Export Rooms
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Room No</th>
                                        <th>Bed No</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="rooms-table-body">
                                    <?php if(count($rooms) > 0): ?>
                                        <?php foreach($rooms as $room): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo $room['id']; ?></span></td>
                                            <td><span class="badge bg-info"><?php echo $room['room_no']; ?></span></td>
                                            <td><span class="badge bg-dark"><?php echo $room['bed_no']; ?></span></td>
                                            <td>
                                                <span class="badge bg-<?php echo $room['type'] == 'ICU' ? 'danger' : ($room['type'] == 'Private' ? 'success' : ($room['type'] == 'Semi-Private' ? 'warning' : 'secondary')); ?>">
                                                    <?php echo $room['type']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if($room['status'] == 'Available'): ?>
                                                    <span class="status-badge status-active">Available</span>
                                                <?php elseif($room['status'] == 'Occupied'): ?>
                                                    <span class="status-badge status-cancelled">Occupied</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-pending">Maintenance</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning action-btn" 
                                                        onclick="updateRoomStatus(<?php echo $room['id']; ?>)">
                                                    <i class="fa fa-edit"></i> Update Status
                                                </button>
                                                <button class="btn btn-sm btn-danger action-btn" 
                                                        onclick="confirmDeleteRoom(<?php echo $room['id']; ?>)">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fa fa-bed fa-3x mb-3"></i><br>
                                                No rooms found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Staff Management Tab -->
                    <div class="tab-pane fade" id="staff-tab">
                        <h4><i class="fa fa-id-badge mr-2"></i> Staff & Doctor Management</h4>
                        
                        <?php if($staff_msg): echo $staff_msg; endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="custom-card">
                                    <div class="card-header">
                                        <i class="fa fa-user-md mr-2"></i>Add New Doctor
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" id="add-doctor-form">
                                            <div class="form-group">
                                                <label class="form-label">Doctor ID *</label>
                                                <input type="text" name="doctorId" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Name *</label>
                                                <input type="text" name="doctor" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Contact Number *</label>
                                                <input type="tel" name="doctorContact" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Specialization *</label>
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
                                                <label class="form-label">Email *</label>
                                                <input type="email" name="demail" class="form-control" required>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Password *</label>
                                                        <input type="password" id="dpassword" name="dpassword" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Confirm Password *</label>
                                                        <input type="password" id="cdpassword" class="form-control" required>
                                                        <small id="doctorPasswordMessage" class="form-text"></small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Fees (Rs.) *</label>
                                                <input type="number" name="docFees" class="form-control" required>
                                            </div>
                                            <button type="submit" name="add_doctor" class="btn btn-success btn-block">
                                                <i class="fa fa-plus mr-1"></i> Add Doctor
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-card">
                                    <div class="card-header">
                                        <i class="fa fa-user-plus mr-2"></i>Add New Staff Member
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" id="add-staff-form">
                                            <div class="form-group">
                                                <label class="form-label">Staff ID *</label>
                                                <input type="text" name="staffId" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Name *</label>
                                                <input type="text" name="staff" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Role *</label>
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
                                                <label class="form-label">Email *</label>
                                                <input type="email" name="semail" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Contact *</label>
                                                <input type="text" name="scontact" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Password *</label>
                                                <input type="password" name="spassword" class="form-control" required>
                                            </div>
                                            <button type="submit" name="add_staff" class="btn btn-primary btn-block">
                                                <i class="fa fa-plus mr-1"></i> Add Staff Member
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h5><i class="fa fa-list mr-2"></i> Doctors & Staff List</h5>
                        <div class="search-container">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="search-bar">
                                        <input type="text" class="form-control" id="staff-search" 
                                               placeholder="Search by ID or Name..." 
                                               onkeyup="filterTable('staff-search', 'staff-table-body')">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" onclick="exportTable('staff-table-body', 'staff')">
                                        <i class="fa fa-download mr-2"></i> Export Staff
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
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
                                        <td><span class="badge bg-primary"><?php echo $doctor['id']; ?></span></td>
                                        <td>Dr. <?php echo $doctor['username']; ?></td>
                                        <td><span class="badge bg-info">Doctor (<?php echo $doctor['spec']; ?>)</span></td>
                                        <td><?php echo $doctor['email']; ?></td>
                                        <td class="font-weight-bold text-success">Rs. <?php echo number_format($doctor['docFees'], 2); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info action-btn" 
                                                    onclick="alert('Doctor Details:\\nID: <?php echo $doctor['id']; ?>\\nName: Dr. <?php echo $doctor['username']; ?>\\nSpecialization: <?php echo $doctor['spec']; ?>\\nEmail: <?php echo $doctor['email']; ?>\\nFees: Rs. <?php echo number_format($doctor['docFees'], 2); ?>\\nContact: <?php echo $doctor['contact']; ?>')">
                                                <i class="fa fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Staff -->
                                    <?php foreach($staff as $staff_member): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo $staff_member['id']; ?></span></td>
                                        <td><?php echo $staff_member['name']; ?></td>
                                        <td><span class="badge bg-warning"><?php echo $staff_member['role']; ?></span></td>
                                        <td><?php echo $staff_member['email']; ?></td>
                                        <td><?php echo $staff_member['contact']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info action-btn" 
                                                    onclick="alert('Staff Details:\\nID: <?php echo $staff_member['id']; ?>\\nName: <?php echo $staff_member['name']; ?>\\nRole: <?php echo $staff_member['role']; ?>\\nEmail: <?php echo $staff_member['email']; ?>\\nContact: <?php echo $staff_member['contact']; ?>')">
                                                <i class="fa fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if(count($doctors) == 0 && count($staff) == 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fa fa-users fa-3x mb-3"></i><br>
                                            No doctors or staff members found
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
                            <label class="form-label">Select Doctor</label>
                            <select name="doctorId" class="form-control" id="doctor-select" required>
                                <option value="">Select doctor to delete</option>
                                <?php foreach($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['id']; ?>">
                                    <?php echo $doctor['id']; ?> - Dr. <?php echo $doctor['username']; ?> (<?php echo $doctor['spec']; ?>)
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
                            <label for="cancellationReason" class="form-label">Cancellation Reason</label>
                            <textarea class="form-control" id="cancellationReason" rows="3" placeholder="Enter reason for cancellation"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cancelled By</label>
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
    <div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPaymentModalLabel">Edit Payment Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="edit-payment-form">
                        <div class="form-group">
                            <label for="edit-payment-status" class="form-label">Payment Status</label>
                            <select class="form-control" id="edit-payment-status" required>
                                <option value="">Select Status</option>
                                <option value="Paid">Paid</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                        
                        <div id="payment-method-section" style="display: none;">
                            <div class="form-group">
                                <label for="edit-payment-method" class="form-label">Payment Method</label>
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
                                <label for="edit-receipt-number" class="form-label">Receipt Number (Optional)</label>
                                <input type="text" class="form-control" id="edit-receipt-number" placeholder="Enter receipt number">
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

    <!-- Edit Schedule Modal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">Edit Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="edit-schedule-form">
                        <div class="form-group">
                            <label for="edit-day" class="form-label">Day *</label>
                            <select class="form-control" id="edit-day" required>
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
                        <div class="form-group">
                            <label for="edit-shift" class="form-label">Shift *</label>
                            <select class="form-control" id="edit-shift" required>
                                <option value="">Select Shift</option>
                                <option value="Morning">Morning (8AM - 2PM)</option>
                                <option value="Afternoon">Afternoon (2PM - 8PM)</option>
                                <option value="Night">Night (8PM - 8AM)</option>
                                <option value="Full Day">Full Day</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit-start-time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="edit-start-time">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit-end-time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="edit-end-time">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="updateSchedule()">Update Schedule</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    
    <script>
        // Additional functions for modals
        function deleteDoctorFromDashboard() {
            const doctorId = document.getElementById('doctor-select').value;
            
            if(!doctorId) {
                alert('Please select a doctor to delete!');
                return;
            }
            
            if(confirm('Are you sure you want to delete this doctor?')) {
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
        
        let currentAppointmentIdToCancel = null;
        let currentPaymentId = null;
        
        $('#cancelAppointmentModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            currentAppointmentIdToCancel = button.data('appointment-id');
        });
        
        $('#editPaymentModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            currentPaymentId = button.data('payment-id');
        });
        
        function confirmCancelAppointment() {
            if(!currentAppointmentIdToCancel) return;
            
            const reason = document.getElementById('cancellationReason').value;
            const cancelledBy = document.querySelector('input[name="cancelledBy"]:checked').value;
            
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
        
        // Toggle payment method section based on status
        document.addEventListener('DOMContentLoaded', function() {
            const paymentStatusSelect = document.getElementById('edit-payment-status');
            if(paymentStatusSelect) {
                paymentStatusSelect.addEventListener('change', function() {
                    const methodSection = document.getElementById('payment-method-section');
                    if(this.value === 'Paid') {
                        methodSection.style.display = 'block';
                    } else {
                        methodSection.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>