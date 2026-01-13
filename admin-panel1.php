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
$edit_doctor_msg = "";
$edit_staff_msg = "";

// ===========================
// GET ADMIN INFO
// ===========================
$admin_id = $_SESSION['admin_id'] ?? 'ADM001';
$admin_name = $_SESSION['admin_name'] ?? 'Admin User';
$admin_email = $_SESSION['admin_email'] ?? 'admin@hospital.com';

// ===========================
// GET STATISTICS
// ===========================
$total_doctors = mysqli_num_rows(mysqli_query($con, "SELECT * FROM doctb"));
$total_patients = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patreg"));
$total_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb"));
$total_staff = mysqli_num_rows(mysqli_query($con, "SELECT * FROM stafftb"));
$today_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE appdate = CURDATE()"));
$pending_payments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM paymenttb WHERE pay_status = 'Pending'"));
$available_rooms = mysqli_num_rows(mysqli_query($con, "SELECT * FROM roomtb WHERE status = 'Available'"));
$active_prescriptions = mysqli_num_rows(mysqli_query($con, "SELECT * FROM prestb WHERE emailStatus = 'Not Sent'"));

// ===========================
// ADD PATIENT (WITH PASSWORD VALIDATION FIXED)
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
                    // Clear form fields
                    echo "<script>document.getElementById('add-patient-form').reset();</script>";
                } else {
                    $patient_msg = "<div class='alert alert-danger'>❌ Database Error: " . mysqli_error($con) . "</div>";
                }
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
            echo "<script>document.getElementById('add-doctor-form').reset();</script>";
        } else {
            $doctor_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
        }
    }
}

// ===========================
// EDIT DOCTOR
// ===========================
if(isset($_POST['edit_doctor'])){
    $doctorId = mysqli_real_escape_string($con, $_POST['edit_doctorId']);
    $doctor = mysqli_real_escape_string($con, $_POST['edit_doctor']);
    $special = mysqli_real_escape_string($con, $_POST['edit_special']);
    $demail = mysqli_real_escape_string($con, $_POST['edit_demail']);
    $docFees = mysqli_real_escape_string($con, $_POST['edit_docFees']);
    $doctorContact = mysqli_real_escape_string($con, $_POST['edit_doctorContact']);
    $update_password = isset($_POST['update_password']) && $_POST['update_password'] == '1';
    $dpassword = mysqli_real_escape_string($con, $_POST['edit_dpassword']);
    
    // Check if doctor exists
    $check = mysqli_query($con, "SELECT * FROM doctb WHERE id='$doctorId'");
    if(mysqli_num_rows($check) == 0){
        $edit_doctor_msg = "<div class='alert alert-danger'>❌ Doctor not found!</div>";
    } else {
        // Build update query
        if($update_password && !empty($dpassword)){
            $query = "UPDATE doctb SET 
                      username='$doctor', 
                      spec='$special', 
                      email='$demail', 
                      password='$dpassword', 
                      docFees='$docFees', 
                      contact='$doctorContact' 
                      WHERE id='$doctorId'";
        } else {
            $query = "UPDATE doctb SET 
                      username='$doctor', 
                      spec='$special', 
                      email='$demail', 
                      docFees='$docFees', 
                      contact='$doctorContact' 
                      WHERE id='$doctorId'";
        }
        
        if(mysqli_query($con, $query)){
            $edit_doctor_msg = "<div class='alert alert-success'>✅ Doctor updated successfully!</div>";
            $_SESSION['success'] = "Doctor updated successfully!";
        } else {
            $edit_doctor_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
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
        // Check if doctor has appointments
        $check_appointments = mysqli_query($con, "SELECT * FROM appointmenttb WHERE doctor=(SELECT username FROM doctb WHERE id='$doctorId')");
        if(mysqli_num_rows($check_appointments) > 0){
            $doctor_msg = "<div class='alert alert-warning'>⚠️ Cannot delete doctor. There are appointments associated with this doctor.</div>";
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
            echo "<script>document.getElementById('add-staff-form').reset();</script>";
        } else {
            $staff_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
        }
    }
}

// ===========================
// EDIT STAFF
// ===========================
if(isset($_POST['edit_staff'])){
    $staffId = mysqli_real_escape_string($con, $_POST['edit_staffId']);
    $staff = mysqli_real_escape_string($con, $_POST['edit_staff']);
    $role = mysqli_real_escape_string($con, $_POST['edit_role']);
    $semail = mysqli_real_escape_string($con, $_POST['edit_semail']);
    $scontact = mysqli_real_escape_string($con, $_POST['edit_scontact']);
    $update_password = isset($_POST['update_staff_password']) && $_POST['update_staff_password'] == '1';
    $spassword = mysqli_real_escape_string($con, $_POST['edit_spassword']);
    
    // Check if staff exists
    $check = mysqli_query($con, "SELECT * FROM stafftb WHERE id='$staffId'");
    if(mysqli_num_rows($check) == 0){
        $edit_staff_msg = "<div class='alert alert-danger'>❌ Staff not found!</div>";
    } else {
        // Build update query
        if($update_password && !empty($spassword)){
            $query = "UPDATE stafftb SET 
                      name='$staff', 
                      role='$role', 
                      email='$semail', 
                      contact='$scontact', 
                      password='$spassword' 
                      WHERE id='$staffId'";
        } else {
            $query = "UPDATE stafftb SET 
                      name='$staff', 
                      role='$role', 
                      email='$semail', 
                      contact='$scontact' 
                      WHERE id='$staffId'";
        }
        
        if(mysqli_query($con, $query)){
            $edit_staff_msg = "<div class='alert alert-success'>✅ Staff member updated successfully!</div>";
            $_SESSION['success'] = "Staff updated successfully!";
        } else {
            $edit_staff_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
        }
    }
}

// ===========================
// DELETE STAFF
// ===========================
if(isset($_POST['delete_staff'])){
    $staffId = mysqli_real_escape_string($con, $_POST['staffId']);
    
    // Check if staff exists
    $check = mysqli_query($con, "SELECT * FROM stafftb WHERE id='$staffId'");
    if(mysqli_num_rows($check) == 0){
        $staff_msg = "<div class='alert alert-danger'>❌ No staff found with this ID!</div>";
    } else {
        $delete = mysqli_query($con, "DELETE FROM stafftb WHERE id='$staffId'");
        if($delete){
            $staff_msg = "<div class='alert alert-success'>✅ Staff member deleted successfully!</div>";
            $_SESSION['success'] = "Staff deleted successfully!";
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
// ADD SCHEDULE (WITH STAFF/DOCTOR ID FIXED)
// ===========================
if(isset($_POST['add_schedule'])){
    $staff_id = mysqli_real_escape_string($con, $_POST['staff_id']);
    $staff_name = mysqli_real_escape_string($con, $_POST['staff_name']);
    $role = mysqli_real_escape_string($con, $_POST['role']);
    $day = mysqli_real_escape_string($con, $_POST['day']);
    $shift = mysqli_real_escape_string($con, $_POST['shift']);
    
    $query = "INSERT INTO scheduletb (staff_id, staff_name, role, day, shift) 
              VALUES ('$staff_id', '$staff_name', '$role', '$day', '$shift')";
    
    if(mysqli_query($con, $query)){
        $schedule_msg = "<div class='alert alert-success'>✅ Schedule added successfully!</div>";
        $_SESSION['success'] = "Schedule added successfully!";
    } else {
        $schedule_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// DELETE SCHEDULE
// ===========================
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

// ===========================
// ADD ROOM (FIXED TO SAVE TO DATABASE)
// ===========================
if(isset($_POST['add_room'])){
    $room_no = mysqli_real_escape_string($con, $_POST['room_no']);
    $bed_no = mysqli_real_escape_string($con, $_POST['bed_no']);
    $type = mysqli_real_escape_string($con, $_POST['type']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    // Check if room/bed already exists
    $check_query = "SELECT * FROM roomtb WHERE room_no='$room_no' AND bed_no='$bed_no'";
    $check_result = mysqli_query($con, $check_query);
    
    if(mysqli_num_rows($check_result) > 0){
        $_SESSION['error'] = "❌ Room/Bed combination already exists!";
    } else {
        $query = "INSERT INTO roomtb (room_no, bed_no, type, status) 
                  VALUES ('$room_no', '$bed_no', '$type', '$status')";
        
        if(mysqli_query($con, $query)){
            $_SESSION['success'] = "✅ Room/Bed added successfully!";
        } else {
            $_SESSION['error'] = "❌ Error: " . mysqli_error($con);
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "#room-tab");
    exit();
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
// SEND PRESCRIPTION TO PATIENT CONTACT (SMS)
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
                                Message: Your prescription has been sent to your mobile number.</small></div>";
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

// Get appointments WITH NIC
$appointment_result = mysqli_query($con, "SELECT *, national_id as patient_nic FROM appointmenttb ORDER BY appdate DESC, apptime DESC");
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

// Get payments WITH NIC
$payment_result = mysqli_query($con, "SELECT *, national_id as patient_nic FROM paymenttb ORDER BY pay_date DESC");
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

// Get schedules WITH STAFF ID
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

// Get all staff names for schedule datalist with ID
$all_staff_with_id = [];
// Get doctor names with ID
foreach($doctors as $doctor){
    $all_staff_with_id[] = [
        'id' => $doctor['id'],
        'name' => $doctor['username'],
        'type' => 'Doctor'
    ];
}
// Get staff names with ID
foreach($staff as $staff_member){
    $all_staff_with_id[] = [
        'id' => $staff_member['id'],
        'name' => $staff_member['name'],
        'type' => $staff_member['role']
    ];
}

// ===========================
// FUNCTION TO CHECK/CREATE TABLES (UPDATED FOR SCHEDULE ID)
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
            staff_id VARCHAR(20) NOT NULL,
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
            if(!mysqli_query($con, $create_sql)){
                echo "<div class='alert alert-danger'>❌ Error creating table $table_name: " . mysqli_error($con) . "</div>";
            }
        } else {
            // Check if scheduletb has staff_id column, add if not
            if($table_name == 'scheduletb'){
                $check_column = mysqli_query($con, "SHOW COLUMNS FROM scheduletb LIKE 'staff_id'");
                if(mysqli_num_rows($check_column) == 0){
                    $alter_query = "ALTER TABLE scheduletb ADD COLUMN staff_id VARCHAR(20) AFTER id";
                    mysqli_query($con, $alter_query);
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
    <title>Admin Panel - Healthcare Hospital</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            background: #f8f9fa; 
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Sidebar */
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

        /* Main content */
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
            border-color: #0077b6;
        }
        .quick-action-card i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #0077b6;
        }

        /* Tables */
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
        .status-available {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-occupied {
            background: #f8d7da;
            color: #721c24;
        }

        /* Charts Container */
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
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
            background: #0077b6;
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -25px -25px 20px -25px;
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

        /* Additional Admin Styles */
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
        
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            border: none;
            font-weight: bold;
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
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
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
            background-color: rgba(0,119,182,0.05);
        }
        
        .action-btn {
            margin: 2px;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img class="logo" src="images/medical-logo.jpg" alt="Hospital Logo">
        <h4>Admin Portal</h4>
        <ul>
            <li data-target="dash-tab" class="active">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </li>
            <li data-target="staff-management-tab">
                <i class="fas fa-users-cog"></i> <span>Staff Management</span>
            </li>
            <li data-target="pat-tab">
                <i class="fas fa-user-injured"></i> <span>Patients</span>
            </li>
            <li data-target="app-tab">
                <i class="fas fa-calendar-check"></i> <span>Appointments</span>
            </li>
            <li data-target="pres-tab">
                <i class="fas fa-prescription"></i> <span>Prescriptions</span>
            </li>
            <li data-target="pay-tab">
                <i class="fas fa-credit-card"></i> <span>Payments</span>
            </li>
            <li data-target="sched-tab">
                <i class="fas fa-clock"></i> <span>Schedules</span>
            </li>
            <li data-target="room-tab">
                <i class="fas fa-bed"></i> <span>Rooms/Beds</span>
            </li>
            <li>
                <a href="logout1.php" style="color: white; text-decoration: none; display: block;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="brand">🏥 Healthcare Hospital Admin</div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($admin_name); ?></strong><br>
                    <small>Administrator</small>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dash-tab">
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
                
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-primary" role="alert">
                            <h4 class="alert-heading">Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</h4>
                            <p class="mb-0">Here's your admin dashboard. You can manage all hospital activities from here.</p>
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
                                            Total Doctors
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $total_doctors; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-md stats-icon text-primary"></i>
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
                                            Today's Appointments
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $today_appointments; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day stats-icon text-info"></i>
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
                                            Pending Payments
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $pending_payments; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-money-bill-wave stats-icon text-warning"></i>
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
                        <div class="quick-action-card" onclick="showTab('staff-management-tab')">
                            <i class="fas fa-user-plus"></i>
                            <h5>Add Staff</h5>
                            <p>Add new doctors or staff members</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showTab('pat-tab')">
                            <i class="fas fa-user-plus"></i>
                            <h5>Register Patient</h5>
                            <p>Register a new patient</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showTab('app-tab')">
                            <i class="fas fa-calendar-plus"></i>
                            <h5>Create Appointment</h5>
                            <p>Schedule a new appointment</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showTab('room-tab')">
                            <i class="fas fa-bed"></i>
                            <h5>Manage Rooms</h5>
                            <p>Add or manage rooms/beds</p>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Appointments Overview</h5>
                            <canvas id="appointmentsChart" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Department Distribution</h5>
                            <canvas id="departmentChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="data-table">
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
                                        <?php if($total_patients > 0): ?>
                                        <tr>
                                            <td>Just now</td>
                                            <td>Patients Registered</td>
                                            <td>Total <?php echo $total_patients; ?> patients</td>
                                            <td><span class="badge badge-success">Active</span></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if($total_doctors > 0): ?>
                                        <tr>
                                            <td>Just now</td>
                                            <td>Doctors Available</td>
                                            <td>Total <?php echo $total_doctors; ?> doctors</td>
                                            <td><span class="badge badge-success">Active</span></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if($today_appointments > 0): ?>
                                        <tr>
                                            <td>Today</td>
                                            <td>Today's Appointments</td>
                                            <td><?php echo $today_appointments; ?> appointments</td>
                                            <td><span class="badge badge-info">Scheduled</span></td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Management Tab -->
            <div class="tab-pane fade" id="staff-management-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-users-cog mr-2"></i>Staff Management</h3>
                </div>
                
                <?php if($doctor_msg): echo $doctor_msg; endif; ?>
                <?php if($staff_msg): echo $staff_msg; endif; ?>
                <?php if($edit_doctor_msg): echo $edit_doctor_msg; endif; ?>
                <?php if($edit_staff_msg): echo $edit_staff_msg; endif; ?>
                
                <!-- Tabs for Staff Management -->
                <ul class="nav nav-tabs" id="staffManagementTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="doctors-tab" data-toggle="tab" href="#doctors-content" role="tab">
                            <i class="fas fa-user-md mr-2"></i>Doctors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="staff-tab" data-toggle="tab" href="#staff-content" role="tab">
                            <i class="fas fa-id-badge mr-2"></i>Staff Members
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="add-doctor-tab" data-toggle="tab" href="#add-doctor-content" role="tab">
                            <i class="fas fa-plus-circle mr-2"></i>Add Doctor
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="add-staff-tab" data-toggle="tab" href="#add-staff-content" role="tab">
                            <i class="fas fa-plus mr-2"></i>Add Staff
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content mt-4">
                    <!-- Doctors Content -->
                    <div class="tab-pane fade show active" id="doctors-content" role="tabpanel">
                        <div class="search-container">
                            <div class="search-bar">
                                <input type="text" class="form-control" id="doctor-search" placeholder="Search doctors by name, ID, or specialization..." onkeyup="filterTable('doctor-search', 'doctors-table-body')">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>
                        
                        <div class="data-table">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Doctor ID</th>
                                            <th>Name</th>
                                            <th>Specialization</th>
                                            <th>Email</th>
                                            <th>Contact</th>
                                            <th>Fees (Rs.)</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="doctors-table-body">
                                        <?php if(count($doctors) > 0): ?>
                                            <?php foreach($doctors as $doctor): ?>
                                            <tr>
                                                <td><strong><?php echo $doctor['id']; ?></strong></td>
                                                <td><?php echo $doctor['username']; ?></td>
                                                <td><?php echo $doctor['spec']; ?></td>
                                                <td><?php echo $doctor['email']; ?></td>
                                                <td><?php echo $doctor['contact'] ?: 'N/A'; ?></td>
                                                <td>Rs. <?php echo number_format($doctor['docFees'], 2); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info action-btn" data-toggle="modal" data-target="#editDoctorModal"
                                                            data-doctor-id="<?php echo $doctor['id']; ?>"
                                                            data-doctor-name="<?php echo $doctor['username']; ?>"
                                                            data-doctor-spec="<?php echo $doctor['spec']; ?>"
                                                            data-doctor-email="<?php echo $doctor['email']; ?>"
                                                            data-doctor-fees="<?php echo $doctor['docFees']; ?>"
                                                            data-doctor-contact="<?php echo $doctor['contact']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger action-btn" onclick="deleteDoctor('<?php echo $doctor['id']; ?>', '<?php echo $doctor['username']; ?>')">
                                                        <i class="fas fa-trash"></i> Delete
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
                        </div>
                    </div>
                    
                    <!-- Staff Content -->
                    <div class="tab-pane fade" id="staff-content" role="tabpanel">
                        <div class="search-container">
                            <div class="search-bar">
                                <input type="text" class="form-control" id="staff-search" placeholder="Search staff by name, ID, or role..." onkeyup="filterTable('staff-search', 'staff-table-body')">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>
                        
                        <div class="data-table">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Staff ID</th>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th>Email</th>
                                            <th>Contact</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="staff-table-body">
                                        <?php if(count($staff) > 0): ?>
                                            <?php foreach($staff as $staff_member): ?>
                                            <tr>
                                                <td><strong><?php echo $staff_member['id']; ?></strong></td>
                                                <td><?php echo $staff_member['name']; ?></td>
                                                <td><?php echo $staff_member['role']; ?></td>
                                                <td><?php echo $staff_member['email']; ?></td>
                                                <td><?php echo $staff_member['contact']; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info action-btn" data-toggle="modal" data-target="#editStaffModal"
                                                            data-staff-id="<?php echo $staff_member['id']; ?>"
                                                            data-staff-name="<?php echo $staff_member['name']; ?>"
                                                            data-staff-role="<?php echo $staff_member['role']; ?>"
                                                            data-staff-email="<?php echo $staff_member['email']; ?>"
                                                            data-staff-contact="<?php echo $staff_member['contact']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger action-btn" onclick="deleteStaff('<?php echo $staff_member['id']; ?>', '<?php echo $staff_member['name']; ?>')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No staff members found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Doctor Content -->
                    <div class="tab-pane fade" id="add-doctor-content" role="tabpanel">
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-user-md mr-2"></i>Add New Doctor</h5>
                            </div>
                            <form method="POST" id="add-doctor-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Doctor ID *</label>
                                            <input type="text" name="doctorId" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Name *</label>
                                            <input type="text" name="doctor" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Contact Number *</label>
                                            <input type="tel" name="doctorContact" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
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
                                                <option value="ENT">ENT Specialist</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email *</label>
                                            <input type="email" name="demail" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Fees (Rs.) *</label>
                                            <input type="number" name="docFees" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Password *</label>
                                            <input type="password" name="dpassword" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Confirm Password *</label>
                                            <input type="password" name="confirm_dpassword" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="add_doctor" class="btn btn-success btn-block">Add Doctor</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Add Staff Content -->
                    <div class="tab-pane fade" id="add-staff-content" role="tabpanel">
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-id-badge mr-2"></i>Add New Staff Member</h5>
                            </div>
                            <form method="POST" id="add-staff-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Staff ID *</label>
                                            <input type="text" name="staffId" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Name *</label>
                                            <input type="text" name="staff" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Role *</label>
                                            <select name="role" class="form-control" required>
                                                <option value="">Select Role</option>
                                                <option value="Nurse">Nurse</option>
                                                <option value="Receptionist">Receptionist</option>
                                                <option value="Admin">Admin</option>
                                                <option value="Lab Technician">Lab Technician</option>
                                                <option value="Pharmacist">Pharmacist</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Contact *</label>
                                            <input type="text" name="scontact" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email *</label>
                                            <input type="email" name="semail" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Password *</label>
                                            <input type="password" name="spassword" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="add_staff" class="btn btn-primary btn-block">Add Staff Member</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Patients Tab -->
            <div class="tab-pane fade" id="pat-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-user-injured mr-2"></i>Patient Management</h3>
                    <button class="btn btn-primary" onclick="showTab('pat-tab')">
                        <i class="fas fa-user-plus mr-2"></i>Register New Patient
                    </button>
                </div>
                
                <?php if($patient_msg): echo $patient_msg; endif; ?>
                
                <!-- Patient Registration Form -->
                <div class="form-card mb-4">
                    <div class="form-card-header">
                        <h5 class="mb-0"><i class="fas fa-user-plus mr-2"></i>Register New Patient</h5>
                    </div>
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
                                    <small class="text-muted">Enter NIC numbers only</small>
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
                        
                        <button type="submit" name="add_patient" class="btn btn-success">
                            <i class="fas fa-user-plus mr-1"></i> Register Patient
                        </button>
                    </form>
                </div>
                
                <!-- Patients List -->
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
                </div>
            </div>

            <!-- Appointments Tab -->
            <div class="tab-pane fade" id="app-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-calendar-check mr-2"></i>Appointments</h3>
                    <button class="btn btn-primary" data-toggle="collapse" data-target="#addAppointmentForm">
                        <i class="fas fa-calendar-plus mr-2"></i>Create New Appointment
                    </button>
                </div>
                
                <?php if($appointment_msg): echo $appointment_msg; endif; ?>
                
                <!-- Add Appointment Form -->
                <div class="form-card mb-4 collapse" id="addAppointmentForm">
                    <div class="form-card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-plus mr-2"></i>Create New Appointment</h5>
                    </div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Patient ID *</label>
                                    <input type="number" class="form-control" name="patient_id" required>
                                    <small class="text-muted">Enter patient ID</small>
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
                        <button type="submit" name="add_appointment" class="btn btn-success">
                            <i class="fas fa-calendar-plus mr-1"></i> Create Appointment
                        </button>
                    </form>
                </div>
                
                <div class="search-container">
                    <div class="search-bar">
                        <input type="text" class="form-control" id="appointment-search" placeholder="Search appointments by patient name, doctor, date, or NIC..." onkeyup="filterTable('appointment-search', 'appointments-table-body')">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Patient</th>
                                    <th>NIC</th>
                                    <th>Doctor</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Fees (Rs.)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="appointments-table-body">
                                <?php if(count($appointments) > 0): ?>
                                    <?php foreach($appointments as $app): ?>
                                    <tr>
                                        <td><?php echo $app['ID']; ?></td>
                                        <td><?php echo $app['fname'] . ' ' . $app['lname']; ?></td>
                                        <td><?php echo $app['patient_nic'] ?: $app['national_id']; ?></td>
                                        <td><?php echo $app['doctor']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($app['appdate'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($app['apptime'])); ?></td>
                                        <td>Rs. <?php echo number_format($app['docFees'], 2); ?></td>
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
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No appointments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Prescriptions Tab -->
            <div class="tab-pane fade" id="pres-tab">
                <h3 class="mb-4"><i class="fas fa-prescription mr-2"></i>Prescriptions</h3>
                
                <?php if($prescription_msg): echo $prescription_msg; endif; ?>
                
                <div class="search-container">
                    <div class="search-bar">
                        <input type="text" class="form-control" id="prescription-search" placeholder="Search prescriptions..." onkeyup="filterTable('prescription-search', 'prescriptions-table-body')">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Doctor</th>
                                    <th>Patient</th>
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
                                        <td><?php echo $pres['fname'] . ' ' . $pres['lname']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($pres['appdate'])); ?></td>
                                        <td><?php echo $pres['disease']; ?></td>
                                        <td><?php echo substr($pres['prescription'], 0, 50) . '...'; ?></td>
                                        <td><?php echo $pres['emailStatus']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary action-btn" onclick="sendToHospitalPharmacy(<?php echo $pres['id']; ?>)">
                                                <i class="fas fa-hospital"></i> Hospital
                                            </button>
                                            <button class="btn btn-sm btn-info action-btn" onclick="sendToPatientContact(<?php echo $pres['id']; ?>)">
                                                <i class="fas fa-mobile-alt"></i> Patient SMS
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

            <!-- Payments Tab -->
            <div class="tab-pane fade" id="pay-tab">
                <h3 class="mb-4"><i class="fas fa-credit-card mr-2"></i>Payments</h3>
                
                <?php if($payment_msg): echo $payment_msg; endif; ?>
                
                <div class="search-container">
                    <div class="search-bar">
                        <input type="text" class="form-control" id="payment-search" placeholder="Search payments by patient name, NIC, or doctor..." onkeyup="filterTable('payment-search', 'payments-table-body')">
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
                            <tbody id="payments-table-body">
                                <?php if(count($payments) > 0): ?>
                                    <?php foreach($payments as $pay): ?>
                                    <tr>
                                        <td><?php echo $pay['id']; ?></td>
                                        <td><?php echo $pay['patient_name']; ?></td>
                                        <td><?php echo $pay['patient_nic'] ?: $pay['national_id']; ?></td>
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
                                        <td><?php echo $pay['payment_method'] ?: 'N/A'; ?></td>
                                        <td><?php echo $pay['receipt_no'] ?: 'N/A'; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning action-btn" data-toggle="modal" data-target="#editPaymentModal" data-payment-id="<?php echo $pay['id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
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
            </div>

            <!-- Schedules Tab -->
            <div class="tab-pane fade" id="sched-tab">
                <h3 class="mb-4"><i class="fas fa-clock mr-2"></i>Staff Schedules</h3>
                
                <?php if($schedule_msg): echo $schedule_msg; endif; ?>
                
                <!-- Add Schedule Form -->
                <div class="form-card mb-4">
                    <div class="form-card-header">
                        <h5 class="mb-0"><i class="fas fa-clock mr-2"></i>Add New Schedule</h5>
                    </div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Select Staff/Doctor *</label>
                                    <select class="form-control" name="staff_id" id="staff_id" required onchange="updateStaffName()">
                                        <option value="">Select Staff/Doctor</option>
                                        <?php foreach($all_staff_with_id as $person): ?>
                                        <option value="<?php echo $person['id']; ?>" data-name="<?php echo $person['name']; ?>" data-type="<?php echo $person['type']; ?>">
                                            <?php echo $person['id'] . ' - ' . $person['name'] . ' (' . $person['type'] . ')'; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Staff Name *</label>
                                    <input type="text" class="form-control" name="staff_name" id="staff_name" readonly required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Role *</label>
                                    <input type="text" class="form-control" name="role" id="staff_role" readonly required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Day *</label>
                                    <select class="form-control" name="day" required>
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
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Shift *</label>
                                    <select class="form-control" name="shift" required>
                                        <option value="">Select Shift</option>
                                        <option value="Morning">Morning (8AM - 2PM)</option>
                                        <option value="Afternoon">Afternoon (2PM - 8PM)</option>
                                        <option value="Night">Night (8PM - 8AM)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_schedule" class="btn btn-info">
                            <i class="fas fa-plus mr-1"></i> Add Schedule
                        </button>
                    </form>
                </div>
                
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Schedule ID</th>
                                    <th>Staff/Doctor ID</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Day</th>
                                    <th>Shift</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="schedules-table-body">
                                <?php if(count($schedules) > 0): ?>
                                    <?php foreach($schedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo $schedule['id']; ?></td>
                                        <td><strong><?php echo $schedule['staff_id']; ?></strong></td>
                                        <td><?php echo $schedule['staff_name']; ?></td>
                                        <td><?php echo $schedule['role']; ?></td>
                                        <td><?php echo $schedule['day']; ?></td>
                                        <td><?php echo $schedule['shift']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-danger action-btn" onclick="deleteSchedule(<?php echo $schedule['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
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
            </div>

            <!-- Rooms/Beds Tab -->
            <div class="tab-pane fade" id="room-tab">
                <h3 class="mb-4"><i class="fas fa-bed mr-2"></i>Rooms & Beds</h3>
                
                <!-- Success/Error Messages -->
                <?php if(isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Add Room Form -->
                <div class="form-card mb-4">
                    <div class="form-card-header">
                        <h5 class="mb-0"><i class="fas fa-bed mr-2"></i>Add New Room/Bed</h5>
                    </div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Room Number *</label>
                                    <input type="text" class="form-control" name="room_no" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Bed Number *</label>
                                    <input type="text" class="form-control" name="bed_no" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Type *</label>
                                    <select class="form-control" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="General">General</option>
                                        <option value="ICU">ICU</option>
                                        <option value="Private">Private</option>
                                        <option value="Emergency">Emergency</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Status *</label>
                                    <select class="form-control" name="status" required>
                                        <option value="Available">Available</option>
                                        <option value="Occupied">Occupied</option>
                                        <option value="Maintenance">Under Maintenance</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_room" class="btn btn-success">
                            <i class="fas fa-plus mr-1"></i> Add Room/Bed
                        </button>
                    </form>
                </div>
                
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Room No</th>
                                    <th>Bed No</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Added Date</th>
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
                                                <span class="status-badge status-cancelled"><?php echo $room['status']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($room['created_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No rooms found. Add your first room/bed above.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
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
                            <label>Reason for Cancellation</label>
                            <textarea class="form-control" id="cancellationReason" rows="3" required></textarea>
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

    <!-- Edit Payment Modal -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Payment Status</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="edit-payment-form">
                        <input type="hidden" id="edit-payment-id">
                        <div class="form-group">
                            <label>Payment Status</label>
                            <select class="form-control" id="edit-payment-status">
                                <option value="Pending">Pending</option>
                                <option value="Paid">Paid</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select class="form-control" id="edit-payment-method">
                                <option value="">Select Method</option>
                                <option value="Cash">Cash</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Debit Card">Debit Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Online">Online Payment</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Receipt Number</label>
                            <input type="text" class="form-control" id="edit-receipt-number" placeholder="Auto-generated if empty">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" onclick="updatePaymentStatus()">Update Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Doctor Modal -->
    <div class="modal fade" id="editDoctorModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Doctor Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="edit-doctor-form">
                        <input type="hidden" name="edit_doctorId" id="edit_doctorId">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" class="form-control" name="edit_doctor" id="edit_doctor" required>
                        </div>
                        <div class="form-group">
                            <label>Specialization</label>
                            <select class="form-control" name="edit_special" id="edit_special" required>
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
                            <label>Email</label>
                            <input type="email" class="form-control" name="edit_demail" id="edit_demail" required>
                        </div>
                        <div class="form-group">
                            <label>Fees (Rs.)</label>
                            <input type="number" class="form-control" name="edit_docFees" id="edit_docFees" required>
                        </div>
                        <div class="form-group">
                            <label>Contact</label>
                            <input type="text" class="form-control" name="edit_doctorContact" id="edit_doctorContact" required>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="update_password_check" name="update_password" value="1">
                            <label class="form-check-label" for="update_password_check">Update Password</label>
                        </div>
                        <div class="form-group" id="password_field" style="display: none;">
                            <label>New Password</label>
                            <input type="password" class="form-control" name="edit_dpassword" id="edit_dpassword">
                        </div>
                        <button type="submit" name="edit_doctor" class="btn btn-warning btn-block">Update Doctor</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Staff Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="edit-staff-form">
                        <input type="hidden" name="edit_staffId" id="edit_staffId">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" class="form-control" name="edit_staff" id="edit_staff" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select class="form-control" name="edit_role" id="edit_role" required>
                                <option value="Nurse">Nurse</option>
                                <option value="Receptionist">Receptionist</option>
                                <option value="Admin">Admin</option>
                                <option value="Lab Technician">Lab Technician</option>
                                <option value="Pharmacist">Pharmacist</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="edit_semail" id="edit_semail" required>
                        </div>
                        <div class="form-group">
                            <label>Contact</label>
                            <input type="text" class="form-control" name="edit_scontact" id="edit_scontact" required>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="update_staff_password_check" name="update_staff_password" value="1">
                            <label class="form-check-label" for="update_staff_password_check">Update Password</label>
                        </div>
                        <div class="form-group" id="staff_password_field" style="display: none;">
                            <label>New Password</label>
                            <input type="password" class="form-control" name="edit_spassword" id="edit_spassword">
                        </div>
                        <button type="submit" name="edit_staff" class="btn btn-warning btn-block">Update Staff</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let currentPaymentId = null;
        let currentAppointmentIdToCancel = null;
        
        // Initialize on page load
        $(document).ready(function() {
            // Initialize charts
            initializeCharts();
            
            // Set up sidebar navigation
            $('.sidebar ul li[data-target]').click(function() {
                const target = $(this).data('target');
                showTab(target);
                
                // Update active state
                $('.sidebar ul li').removeClass('active');
                $(this).addClass('active');
            });
            
            // Set up modal functionality
            $('#cancelAppointmentModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                currentAppointmentIdToCancel = button.data('appointment-id');
                $('#cancellationReason').val('');
            });
            
            $('#editPaymentModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                currentPaymentId = button.data('payment-id');
            });
            
            $('#editDoctorModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                $('#edit_doctorId').val(button.data('doctor-id'));
                $('#edit_doctor').val(button.data('doctor-name'));
                $('#edit_special').val(button.data('doctor-spec'));
                $('#edit_demail').val(button.data('doctor-email'));
                $('#edit_docFees').val(button.data('doctor-fees'));
                $('#edit_doctorContact').val(button.data('doctor-contact'));
                $('#password_field').hide();
                $('#update_password_check').prop('checked', false);
            });
            
            $('#editStaffModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                $('#edit_staffId').val(button.data('staff-id'));
                $('#edit_staff').val(button.data('staff-name'));
                $('#edit_role').val(button.data('staff-role'));
                $('#edit_semail').val(button.data('staff-email'));
                $('#edit_scontact').val(button.data('staff-contact'));
                $('#staff_password_field').hide();
                $('#update_staff_password_check').prop('checked', false);
            });
            
            // Toggle password field in edit doctor modal
            $('#update_password_check').change(function() {
                if(this.checked) {
                    $('#password_field').show();
                    $('#edit_dpassword').prop('required', true);
                } else {
                    $('#password_field').hide();
                    $('#edit_dpassword').prop('required', false);
                    $('#edit_dpassword').val('');
                }
            });
            
            // Toggle password field in edit staff modal
            $('#update_staff_password_check').change(function() {
                if(this.checked) {
                    $('#staff_password_field').show();
                    $('#edit_spassword').prop('required', true);
                } else {
                    $('#staff_password_field').hide();
                    $('#edit_spassword').prop('required', false);
                    $('#edit_spassword').val('');
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Check URL hash on load
            if(window.location.hash) {
                const tabId = window.location.hash.substring(1);
                showTab(tabId);
                
                // Update sidebar active state
                $('.sidebar ul li').removeClass('active');
                $(`.sidebar ul li[data-target="${tabId}"]`).addClass('active');
            }
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
        
        // Function to filter table rows
        function filterTable(searchInputId, tableBodyId) {
            const input = $('#' + searchInputId).val().toLowerCase();
            $('#' + tableBodyId + ' tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(input) > -1);
            });
        }
        
        // Function to initialize charts
        function initializeCharts() {
            // Appointments Chart
            const appointmentsCtx = document.getElementById('appointmentsChart');
            if(appointmentsCtx) {
                const appointmentsChart = new Chart(appointmentsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Cancelled'],
                        datasets: [{
                            data: [<?php echo $total_appointments - count(array_filter($appointments, function($app) { return $app['appointmentStatus'] == 'cancelled'; })); ?>, 
                                   <?php echo count(array_filter($appointments, function($app) { return $app['appointmentStatus'] == 'cancelled'; })); ?>],
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 99, 132, 0.8)'
                            ]
                        }]
                    }
                });
            }
            
            // Department Chart
            const departmentCtx = document.getElementById('departmentChart');
            if(departmentCtx) {
                const departmentChart = new Chart(departmentCtx, {
                    type: 'pie',
                    data: {
                        labels: ['General', 'Cardiology', 'Pediatrics', 'Neurology', 'Dermatology', 'Orthopedics'],
                        datasets: [{
                            data: [5, 3, 4, 2, 3, 2],
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 206, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(153, 102, 255, 0.8)',
                                'rgba(255, 159, 64, 0.8)'
                            ]
                        }]
                    }
                });
            }
        }
        
        // Function to update staff name and role based on selected ID
        function updateStaffName() {
            const select = document.getElementById('staff_id');
            const selectedOption = select.options[select.selectedIndex];
            const staffName = selectedOption.getAttribute('data-name');
            const staffRole = selectedOption.getAttribute('data-type');
            
            document.getElementById('staff_name').value = staffName;
            document.getElementById('staff_role').value = staffRole;
        }
        
        // Function to confirm appointment cancellation
        function confirmCancelAppointment() {
            if(!currentAppointmentIdToCancel) return;
            
            const reason = $('#cancellationReason').val();
            
            if(!reason.trim()) {
                alert('Please provide a reason for cancellation.');
                return;
            }
            
            // Submit form
            const form = $('<form>').attr({
                method: 'POST',
                style: 'display: none;'
            });
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'appointmentId',
                value: currentAppointmentIdToCancel
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'reason',
                value: reason
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'cancelledBy',
                value: 'admin'
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'cancel_appointment',
                value: '1'
            }));
            
            $('body').append(form);
            form.submit();
        }
        
        // Function to update payment status
        function updatePaymentStatus() {
            if(!currentPaymentId) return;
            
            const status = $('#edit-payment-status').val();
            const method = $('#edit-payment-method').val();
            const receipt = $('#edit-receipt-number').val();
            
            // Submit form
            const form = $('<form>').attr({
                method: 'POST',
                style: 'display: none;'
            });
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'paymentId',
                value: currentPaymentId
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'status',
                value: status
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'method',
                value: method
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'receipt',
                value: receipt
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'update_payment',
                value: '1'
            }));
            
            $('body').append(form);
            form.submit();
        }
        
        // Function to delete doctor
        function deleteDoctor(doctorId, doctorName) {
            if(confirm(`Are you sure you want to delete ${doctorName}? This action cannot be undone.`)) {
                const form = $('<form>').attr({
                    method: 'POST',
                    style: 'display: none;'
                });
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'doctorId',
                    value: doctorId
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'delete_doctor',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Function to delete staff
        function deleteStaff(staffId, staffName) {
            if(confirm(`Are you sure you want to delete ${staffName}? This action cannot be undone.`)) {
                const form = $('<form>').attr({
                    method: 'POST',
                    style: 'display: none;'
                });
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'staffId',
                    value: staffId
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'delete_staff',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Function to send prescription to hospital pharmacy
        function sendToHospitalPharmacy(prescriptionId) {
            if(confirm('Send this prescription to Hospital Pharmacy?')) {
                const form = $('<form>').attr({
                    method: 'POST',
                    style: 'display: none;'
                });
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'prescription_id',
                    value: prescriptionId
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'send_to_hospital',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Function to send prescription to patient contact
        function sendToPatientContact(prescriptionId) {
            if(confirm('Send this prescription to patient via SMS?')) {
                const form = $('<form>').attr({
                    method: 'POST',
                    style: 'display: none;'
                });
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'prescription_id',
                    value: prescriptionId
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'send_to_patient',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Function to delete schedule
        function deleteSchedule(scheduleId) {
            if(confirm('Are you sure you want to delete this schedule?')) {
                const form = $('<form>').attr({
                    method: 'POST',
                    style: 'display: none;'
                });
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'schedule_id',
                    value: scheduleId
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'delete_schedule',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Form validation for add patient
        $(document).ready(function() {
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
            
            // Validate doctor form
            $('#add-doctor-form').submit(function(e) {
                const password = $('input[name="dpassword"]').val();
                const cpassword = $('input[name="confirm_dpassword"]').val();
                
                if(password !== cpassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>