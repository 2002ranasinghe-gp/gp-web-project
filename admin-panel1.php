<?php
// ===========================
// DATABASE CONNECTION & SESSION START
// ===========================
session_start();

// Security headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Check if admin is logged in
if(!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_name'])){
    $_SESSION['error'] = "Please login to access admin panel";
    header("Location: ../index.php");
    exit();
}

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
$settings_msg = "";
$room_msg = "";
$feedback_msg = "";

// ===========================
// GET ADMIN INFO
// ===========================
$admin_id = $_SESSION['admin_id'] ?? 'ADM001';
$admin_name = $_SESSION['admin_name'] ?? 'Admin User';
$admin_email = $_SESSION['admin_email'] ?? 'admin@hospital.com';

// Get admin profile picture from database
$admin_query = mysqli_query($con, "SELECT profile_pic FROM admintb WHERE username='$admin_name'");
if($admin_query && mysqli_num_rows($admin_query) > 0){
    $admin_data = mysqli_fetch_assoc($admin_query);
    $admin_profile_pic = $admin_data['profile_pic'] ?? 'default-avatar.jpg';
} else {
    $admin_profile_pic = 'default-avatar.jpg';
}

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
$total_feedback = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patient_feedback"));
$today_feedback = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patient_feedback WHERE DATE(feedback_date) = CURDATE()"));
$positive_feedback = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patient_feedback WHERE rating >= 4"));
$average_rating = mysqli_fetch_array(mysqli_query($con, "SELECT AVG(rating) as avg_rating FROM patient_feedback"))['avg_rating'] ?? 0;

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
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $cpassword = isset($_POST['cpassword']) ? mysqli_real_escape_string($con, $_POST['cpassword']) : '';
    
    if($password !== $cpassword) {
        $patient_msg = "<div class='alert alert-danger'>❌ Passwords do not match!</div>";
    } else {
        $nicNumbers = preg_replace('/[^0-9]/', '', $nic_input);
        $national_id = 'NIC' . $nicNumbers;
        
        $check_email = mysqli_query($con, "SELECT * FROM patreg WHERE email='$email'");
        if(mysqli_num_rows($check_email) > 0){
            $patient_msg = "<div class='alert alert-danger'>❌ Patient with this email already exists!</div>";
        } else {
            $check_nic = mysqli_query($con, "SELECT * FROM patreg WHERE national_id='$national_id'");
            if(mysqli_num_rows($check_nic) > 0){
                $patient_msg = "<div class='alert alert-danger'>❌ Patient with this NIC already exists!</div>";
            } else {
                $query = "INSERT INTO patreg (fname, lname, gender, dob, email, contact, address, emergencyContact, national_id, password) 
                          VALUES ('$fname', '$lname', '$gender', '$dob', '$email', '$contact', '$address', '$emergencyContact', '$national_id', '$password')";
                
                if(mysqli_query($con, $query)){
                    $new_patient_id = mysqli_insert_id($con);
                    $patient_msg = "<div class='alert alert-success'>✅ Patient registered successfully! Patient ID: $new_patient_id, NIC: $national_id</div>";
                    $_SESSION['success'] = "Patient added successfully!";
                    echo "<script>document.getElementById('add-patient-form').reset();</script>";
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
    
    $patient_query = mysqli_query($con, "SELECT * FROM patreg WHERE national_id='$patient_nic'");
    if(mysqli_num_rows($patient_query) > 0){
        $patient = mysqli_fetch_assoc($patient_query);
        
        $doctor_query = mysqli_query($con, "SELECT * FROM doctb WHERE username='$doctor'");
        if(mysqli_num_rows($doctor_query) > 0){
            $doctor_data = mysqli_fetch_assoc($doctor_query);
            $docFees = $doctor_data['docFees'];
            
            $query = "INSERT INTO appointmenttb (pid, national_id, fname, lname, gender, email, contact, doctor, docFees, appdate, apptime) 
                      VALUES ('{$patient['pid']}', '{$patient['national_id']}', '{$patient['fname']}', '{$patient['lname']}', 
                              '{$patient['gender']}', '{$patient['email']}', '{$patient['contact']}', 
                              '$doctor', '$docFees', '$appdate', '$apptime')";
            
            if(mysqli_query($con, $query)){
                $appointment_id = mysqli_insert_id($con);
                
                $payment_query = "INSERT INTO paymenttb (pid, appointment_id, national_id, patient_name, doctor, fees, pay_date) 
                                  VALUES ('{$patient['pid']}', '$appointment_id', '{$patient['national_id']}', 
                                          '{$patient['fname']} {$patient['lname']}', '$doctor', '$docFees', '$appdate')";
                mysqli_query($con, $payment_query);
                
                $appointment_msg = "<div class='alert alert-success'>✅ Appointment created successfully using NIC!<br>
                                   Appointment ID: $appointment_id<br>
                                   Patient: {$patient['fname']} {$patient['lname']}<br>
                                   NIC: {$patient['national_id']}</div>";
                $_SESSION['success'] = "Appointment created using NIC!";
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
    
    $check = mysqli_query($con, "SELECT * FROM doctb WHERE email='$demail' OR id='$doctorId'");
    if(mysqli_num_rows($check) > 0){
        $doctor_msg = "<div class='alert alert-danger'>❌ Doctor with this email or ID already exists!</div>";
    } else {
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
    
    $check = mysqli_query($con, "SELECT * FROM doctb WHERE id='$doctorId'");
    if(mysqli_num_rows($check) == 0){
        $edit_doctor_msg = "<div class='alert alert-danger'>❌ Doctor not found!</div>";
    } else {
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
    
    $check = mysqli_query($con, "SELECT * FROM doctb WHERE id='$doctorId'");
    if(mysqli_num_rows($check) == 0){
        $doctor_msg = "<div class='alert alert-danger'>❌ No doctor found with this ID!</div>";
    } else {
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
    
    $check = mysqli_query($con, "SELECT * FROM stafftb WHERE email='$semail' OR id='$staffId'");
    if(mysqli_num_rows($check) > 0){
        $staff_msg = "<div class='alert alert-danger'>❌ Staff member with this email or ID already exists!</div>";
    } else {
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
    
    $check = mysqli_query($con, "SELECT * FROM stafftb WHERE id='$staffId'");
    if(mysqli_num_rows($check) == 0){
        $edit_staff_msg = "<div class='alert alert-danger'>❌ Staff not found!</div>";
    } else {
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
    
    $patient_query = mysqli_query($con, "SELECT * FROM patreg WHERE pid='$patient_id'");
    if(mysqli_num_rows($patient_query) > 0){
        $patient = mysqli_fetch_assoc($patient_query);
        
        $doctor_query = mysqli_query($con, "SELECT * FROM doctb WHERE username='$doctor'");
        if(mysqli_num_rows($doctor_query) > 0){
            $doctor_data = mysqli_fetch_assoc($doctor_query);
            $docFees = $doctor_data['docFees'];
            
            $query = "INSERT INTO appointmenttb (pid, national_id, fname, lname, gender, email, contact, doctor, docFees, appdate, apptime) 
                      VALUES ('{$patient['pid']}', '{$patient['national_id']}', '{$patient['fname']}', '{$patient['lname']}', 
                              '{$patient['gender']}', '{$patient['email']}', '{$patient['contact']}', 
                              '$doctor', '$docFees', '$appdate', '$apptime')";
            
            if(mysqli_query($con, $query)){
                $appointment_id = mysqli_insert_id($con);
                
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
// ADD SCHEDULE
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
// ADD ROOM
// ===========================
if(isset($_POST['add_room'])){
    $room_no = mysqli_real_escape_string($con, $_POST['room_no']);
    $bed_no = mysqli_real_escape_string($con, $_POST['bed_no']);
    $type = mysqli_real_escape_string($con, $_POST['type']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    $check_query = "SELECT * FROM roomtb WHERE room_no='$room_no' AND bed_no='$bed_no'";
    $check_result = mysqli_query($con, $check_query);
    
    if(mysqli_num_rows($check_result) > 0){
        $room_msg = "<div class='alert alert-danger'>❌ Room/Bed combination already exists!</div>";
    } else {
        $query = "INSERT INTO roomtb (room_no, bed_no, type, status) 
                  VALUES ('$room_no', '$bed_no', '$type', '$status')";
        
        if(mysqli_query($con, $query)){
            $room_msg = "<div class='alert alert-success'>✅ Room/Bed added successfully!</div>";
            $_SESSION['success'] = "Room/Bed added successfully!";
        } else {
            $room_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
        }
    }
}

// ===========================
// EDIT ROOM/BED STATUS
// ===========================
if(isset($_POST['edit_room'])){
    $room_id = mysqli_real_escape_string($con, $_POST['room_id']);
    $room_no = mysqli_real_escape_string($con, $_POST['room_no']);
    $bed_no = mysqli_real_escape_string($con, $_POST['bed_no']);
    $type = mysqli_real_escape_string($con, $_POST['type']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    $query = "UPDATE roomtb SET 
              room_no='$room_no',
              bed_no='$bed_no',
              type='$type',
              status='$status'
              WHERE id='$room_id'";
    
    if(mysqli_query($con, $query)){
        $room_msg = "<div class='alert alert-success'>✅ Room/Bed updated successfully!</div>";
        $_SESSION['success'] = "Room/Bed updated successfully!";
    } else {
        $room_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// DELETE ROOM/BED
// ===========================
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
// SEND PRESCRIPTION TO PATIENT CONTACT (SMS)
// ===========================
if(isset($_POST['send_to_patient'])){
    $prescription_id = mysqli_real_escape_string($con, $_POST['prescription_id']);
    
    $get_contact_query = mysqli_query($con, "SELECT p.contact, ps.fname, ps.lname, ps.prescription 
                                            FROM prestb ps 
                                            JOIN patreg p ON ps.pid = p.pid 
                                            WHERE ps.id='$prescription_id'");
    
    if(mysqli_num_rows($get_contact_query) > 0){
        $patient_data = mysqli_fetch_assoc($get_contact_query);
        $contact = $patient_data['contact'];
        $patient_name = $patient_data['fname'] . ' ' . $patient_data['lname'];
        $prescription_text = $patient_data['prescription'];
        
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
// UPDATE ADMIN SETTINGS
// ===========================
if(isset($_POST['update_settings'])){
    $hospital_name = mysqli_real_escape_string($con, $_POST['hospital_name']);
    $hospital_address = mysqli_real_escape_string($con, $_POST['hospital_address']);
    $hospital_phone = mysqli_real_escape_string($con, $_POST['hospital_phone']);
    $hospital_email = mysqli_real_escape_string($con, $_POST['hospital_email']);
    $appointment_duration = mysqli_real_escape_string($con, $_POST['appointment_duration']);
    $working_hours_start = mysqli_real_escape_string($con, $_POST['working_hours_start']);
    $working_hours_end = mysqli_real_escape_string($con, $_POST['working_hours_end']);
    $enable_online_payment = isset($_POST['enable_online_payment']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    
    $check_table = mysqli_query($con, "SHOW TABLES LIKE 'hospital_settings'");
    if(mysqli_num_rows($check_table) == 0){
        $create_table = "CREATE TABLE IF NOT EXISTS hospital_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        mysqli_query($con, $create_table);
    }
    
    $settings = [
        'hospital_name' => $hospital_name,
        'hospital_address' => $hospital_address,
        'hospital_phone' => $hospital_phone,
        'hospital_email' => $hospital_email,
        'appointment_duration' => $appointment_duration,
        'working_hours_start' => $working_hours_start,
        'working_hours_end' => $working_hours_end,
        'enable_online_payment' => $enable_online_payment,
        'sms_notifications' => $sms_notifications,
        'email_notifications' => $email_notifications
    ];
    
    $success = true;
    foreach($settings as $key => $value){
        $check = mysqli_query($con, "SELECT * FROM hospital_settings WHERE setting_key='$key'");
        if(mysqli_num_rows($check) > 0){
            $query = "UPDATE hospital_settings SET setting_value='$value', updated_date=NOW() WHERE setting_key='$key'";
        } else {
            $query = "INSERT INTO hospital_settings (setting_key, setting_value) VALUES ('$key', '$value')";
        }
        if(!mysqli_query($con, $query)){
            $success = false;
            break;
        }
    }
    
    if($success){
        $settings_msg = "<div class='alert alert-success'>✅ Hospital settings updated successfully!</div>";
        $_SESSION['success'] = "Settings updated!";
    } else {
        $settings_msg = "<div class='alert alert-danger'>❌ Error updating settings: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// CHANGE ADMIN PASSWORD
// ===========================
if(isset($_POST['change_admin_password'])){
    $current_password = mysqli_real_escape_string($con, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($con, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);
    
    $check_table = mysqli_query($con, "SHOW TABLES LIKE 'admintb'");
    if(mysqli_num_rows($check_table) == 0){
        $create_table = "CREATE TABLE IF NOT EXISTS admintb (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            full_name VARCHAR(100),
            profile_pic VARCHAR(255) DEFAULT 'default-avatar.jpg',
            role VARCHAR(20) DEFAULT 'admin',
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        mysqli_query($con, $create_table);
        
        $default_password = 'admin123';
        $check_admin = mysqli_query($con, "SELECT * FROM admintb WHERE username='admin'");
        if(mysqli_num_rows($check_admin) == 0){
            $insert_admin = "INSERT INTO admintb (username, password, email, full_name) 
                            VALUES ('admin', '$default_password', 'admin@hospital.com', 'Administrator')";
            mysqli_query($con, $insert_admin);
        }
    }
    
    $check_password = mysqli_query($con, "SELECT * FROM admintb WHERE username='$admin_name' AND password='$current_password'");
    if(mysqli_num_rows($check_password) == 0){
        $generated_password = generateRandomPassword(8);
        $query = "UPDATE admintb SET password='$generated_password', updated_date=NOW() WHERE username='$admin_name'";
        if(mysqli_query($con, $query)){
            $settings_msg = "<div class='alert alert-warning'>⚠️ Current password was incorrect! A new password has been generated.<br>
                            <strong>New Password: $generated_password</strong><br>
                            Please use this new password to login and change it immediately.</div>";
        } else {
            $settings_msg = "<div class='alert alert-danger'>❌ Error generating new password: " . mysqli_error($con) . "</div>";
        }
    } elseif($new_password !== $confirm_password){
        $settings_msg = "<div class='alert alert-danger'>❌ New passwords do not match!</div>";
    } elseif(strlen($new_password) < 6){
        $settings_msg = "<div class='alert alert-danger'>❌ New password must be at least 6 characters!</div>";
    } else {
        $query = "UPDATE admintb SET password='$new_password', updated_date=NOW() WHERE username='$admin_name'";
        if(mysqli_query($con, $query)){
            $settings_msg = "<div class='alert alert-success'>✅ Admin password changed successfully!</div>";
            $_SESSION['success'] = "Password changed!";
        } else {
            $settings_msg = "<div class='alert alert-danger'>❌ Error changing password: " . mysqli_error($con) . "</div>";
        }
    }
}

// ===========================
// UPDATE ADMIN PROFILE PICTURE
// ===========================
if(isset($_POST['update_profile_pic'])){
    if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0){
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['profile_pic']['type'];
        
        if(in_array($file_type, $allowed_types)){
            $upload_dir = 'uploads/profile_pictures/';
            if(!file_exists($upload_dir)){
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $new_filename = 'admin_' . $admin_name . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)){
                $query = "UPDATE admintb SET profile_pic='$new_filename', updated_date=NOW() WHERE username='$admin_name'";
                if(mysqli_query($con, $query)){
                    $admin_profile_pic = $new_filename;
                    $settings_msg = "<div class='alert alert-success'>✅ Profile picture updated successfully!</div>";
                    $_SESSION['success'] = "Profile picture updated!";
                } else {
                    $settings_msg = "<div class='alert alert-danger'>❌ Error updating profile picture: " . mysqli_error($con) . "</div>";
                }
            } else {
                $settings_msg = "<div class='alert alert-danger'>❌ Error uploading file!</div>";
            }
        } else {
            $settings_msg = "<div class='alert alert-danger'>❌ Only JPG, PNG, and GIF files are allowed!</div>";
        }
    }
}

// ===========================
// BACKUP DATABASE
// ===========================
if(isset($_POST['backup_database'])){
    $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_file = 'backups/' . $backup_name;
    
    if(!file_exists('backups')){
        mkdir('backups', 0777, true);
    }
    
    $tables = [];
    $result = mysqli_query($con, 'SHOW TABLES');
    while($row = mysqli_fetch_row($result)){
        $tables[] = $row[0];
    }
    
    $return = '';
    foreach($tables as $table){
        $result = mysqli_query($con, 'SELECT * FROM ' . $table);
        $num_fields = mysqli_num_fields($result);
        
        $return .= 'DROP TABLE IF EXISTS ' . $table . ';';
        $row2 = mysqli_fetch_row(mysqli_query($con, 'SHOW CREATE TABLE ' . $table));
        $return .= "\n\n" . $row2[1] . ";\n\n";
        
        for($i = 0; $i < $num_fields; $i++){
            while($row = mysqli_fetch_row($result)){
                $return .= 'INSERT INTO ' . $table . ' VALUES(';
                for($j = 0; $j < $num_fields; $j++){
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if(isset($row[$j])){
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if($j < ($num_fields - 1)){
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }
    
    if(file_put_contents($backup_file, $return)){
        $settings_msg = "<div class='alert alert-success'>✅ Database backup created successfully!<br>
                        File: $backup_name<br>
                        Size: " . filesize($backup_file) . " bytes</div>";
        $_SESSION['success'] = "Backup created!";
    } else {
        $settings_msg = "<div class='alert alert-danger'>❌ Error creating backup!</div>";
    }
}

// ===========================
// PATIENT FEEDBACK MANAGEMENT
// ===========================
if(isset($_POST['delete_feedback'])){
    $feedback_id = mysqli_real_escape_string($con, $_POST['feedback_id']);
    
    $query = "DELETE FROM patient_feedback WHERE id='$feedback_id'";
    
    if(mysqli_query($con, $query)){
        $feedback_msg = "<div class='alert alert-success'>✅ Feedback deleted successfully!</div>";
        $_SESSION['success'] = "Feedback deleted!";
    } else {
        $feedback_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

if(isset($_POST['reply_to_feedback'])){
    $feedback_id = mysqli_real_escape_string($con, $_POST['feedback_id']);
    $reply_message = mysqli_real_escape_string($con, $_POST['reply_message']);
    
    $query = "UPDATE patient_feedback SET 
              admin_reply='$reply_message',
              reply_date=NOW(),
              status='Replied'
              WHERE id='$feedback_id'";
    
    if(mysqli_query($con, $query)){
        $feedback_msg = "<div class='alert alert-success'>✅ Reply sent successfully!</div>";
        $_SESSION['success'] = "Reply sent!";
    } else {
        $feedback_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

if(isset($_POST['mark_feedback_read'])){
    $feedback_id = mysqli_real_escape_string($con, $_POST['feedback_id']);
    
    $query = "UPDATE patient_feedback SET status='Read' WHERE id='$feedback_id'";
    
    if(mysqli_query($con, $query)){
        $feedback_msg = "<div class='alert alert-success'>✅ Feedback marked as read!</div>";
        $_SESSION['success'] = "Feedback marked as read!";
    } else {
        $feedback_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
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
$hospital_settings = [];
$feedback = [];

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

// Get hospital settings
$settings_result = mysqli_query($con, "SELECT * FROM hospital_settings");
if($settings_result){
    while($row = mysqli_fetch_assoc($settings_result)){
        $hospital_settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Get patient feedback
$feedback_result = mysqli_query($con, "SELECT pf.*, p.fname, p.lname, p.email as patient_email, p.contact as patient_contact 
                                      FROM patient_feedback pf 
                                      LEFT JOIN patreg p ON pf.patient_id = p.pid 
                                      ORDER BY pf.feedback_date DESC");
if($feedback_result){
    while($row = mysqli_fetch_assoc($feedback_result)){
        $feedback[] = $row;
    }
}

// Get all staff names for schedule datalist with ID
$all_staff_with_id = [];
foreach($doctors as $doctor){
    $all_staff_with_id[] = [
        'id' => $doctor['id'],
        'name' => $doctor['username'],
        'type' => 'Doctor'
    ];
}
foreach($staff as $staff_member){
    $all_staff_with_id[] = [
        'id' => $staff_member['id'],
        'name' => $staff_member['name'],
        'type' => $staff_member['role']
    ];
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
        )",
        
        'hospital_settings' => "CREATE TABLE IF NOT EXISTS hospital_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'admintb' => "CREATE TABLE IF NOT EXISTS admintb (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            full_name VARCHAR(100),
            profile_pic VARCHAR(255) DEFAULT 'default-avatar.jpg',
            role VARCHAR(20) DEFAULT 'admin',
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'patient_feedback' => "CREATE TABLE IF NOT EXISTS patient_feedback (
            id INT PRIMARY KEY AUTO_INCREMENT,
            patient_id INT NOT NULL,
            patient_name VARCHAR(100) NOT NULL,
            patient_email VARCHAR(100) NOT NULL,
            department VARCHAR(50),
            doctor_name VARCHAR(100),
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comments TEXT,
            admin_reply TEXT,
            status VARCHAR(20) DEFAULT 'Unread',
            feedback_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reply_date TIMESTAMP NULL,
            FOREIGN KEY (patient_id) REFERENCES patreg(pid) ON DELETE CASCADE
        )"
    ];
    
    foreach($tables as $table_name => $create_sql){
        $check = mysqli_query($con, "SHOW TABLES LIKE '$table_name'");
        if(mysqli_num_rows($check) == 0){
            if(!mysqli_query($con, $create_sql)){
                echo "<div class='alert alert-danger'>❌ Error creating table $table_name: " . mysqli_error($con) . "</div>";
            }
        }
    }
    
    // Insert default admin if not exists
    $check_admin = mysqli_query($con, "SELECT * FROM admintb WHERE username='admin'");
    if(mysqli_num_rows($check_admin) == 0){
        $default_password = 'admin123';
        $insert_admin = "INSERT INTO admintb (username, password, email, full_name) 
                        VALUES ('admin', '$default_password', 'admin@hospital.com', 'Administrator')";
        mysqli_query($con, $insert_admin);
    }
    
    // Insert default settings if not exists
    $default_settings = [
        ['hospital_name', 'Healthcare Hospital'],
        ['hospital_address', '123 Medical Street, City, Country'],
        ['hospital_phone', '+94 11 234 5678'],
        ['hospital_email', 'info@healthcarehospital.com'],
        ['appointment_duration', '30'],
        ['working_hours_start', '08:00'],
        ['working_hours_end', '18:00'],
        ['enable_online_payment', '1'],
        ['sms_notifications', '1'],
        ['email_notifications', '1']
    ];
    
    foreach($default_settings as $setting){
        $check = mysqli_query($con, "SELECT * FROM hospital_settings WHERE setting_key='{$setting[0]}'");
        if(mysqli_num_rows($check) == 0){
            $insert = "INSERT INTO hospital_settings (setting_key, setting_value) VALUES ('{$setting[0]}', '{$setting[1]}')";
            mysqli_query($con, $insert);
        }
    }
    
    if(!file_exists('uploads')){
        mkdir('uploads', 0777, true);
    }
    if(!file_exists('uploads/profile_pictures')){
        mkdir('uploads/profile_pictures', 0777, true);
    }
    if(!file_exists('backups')){
        mkdir('backups', 0777, true);
    }
}

// ===========================
// GENERATE RANDOM PASSWORD FUNCTION
// ===========================
function generateRandomPassword($length = 8){
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
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
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .stats-icon {
            font-size: 40px;
            opacity: 0.8;
        }
        
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
        .status-unread {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-read {
            background: #d4edda;
            color: #155724;
        }
        .status-replied {
            background: #cce5ff;
            color: #004085;
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
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

        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border-left: 5px solid #0077b6;
        }
        .settings-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0077b6, #0096c7);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: white;
            font-size: 24px;
        }
        .backup-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .backup-item {
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        .backup-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .feedback-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-left: 4px solid #0077b6;
            transition: all 0.3s;
        }
        .feedback-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .feedback-rating {
            font-size: 18px;
            color: #ffc107;
        }
        .feedback-content {
            margin-bottom: 15px;
        }
        .feedback-reply {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 3px solid #28a745;
        }
        .star-rating {
            color: #ffc107;
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
        
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }
        
        input:checked + .slider {
            background-color: #0077b6;
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px #0077b6;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .slider.round {
            border-radius: 34px;
        }
        
        .slider.round:before {
            border-radius: 50%;
        }
        
        .profile-pic-container {
            position: relative;
            display: inline-block;
        }
        
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #0077b6;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .profile-pic-upload {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #0077b6;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .profile-pic-upload input {
            display: none;
        }
        
        .user-avatar-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        .room-action-btn {
            margin: 2px;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 5px;
        }
        
        .password-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .custom-file-label::after {
            content: "Browse";
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .filter-btn {
            padding: 8px 15px;
            border-radius: 20px;
            border: 2px solid #0077b6;
            background: white;
            color: #0077b6;
            transition: all 0.3s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #0077b6;
            color: white;
        }
        
        .rating-stars {
            font-size: 18px;
            color: #ffc107;
        }
        
        .empty-star {
            color: #dee2e6;
        }
        
        /* Logout Button Style */
        .logout-btn-item {
            position: absolute;
            bottom: 20px;
            width: 100%;
        }
        
        .logout-btn-item:hover {
            background: rgba(255, 0, 0, 0.2);
            border-left: 4px solid #ff4444 !important;
        }
        
        /* Confirm Logout Modal */
        .logout-modal .modal-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .logout-confirm-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
        }
        
        .logout-confirm-btn:hover {
            background: linear-gradient(135deg, #c82333, #dc3545);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img class="logo" src="images/medical-logo.jpg" alt="Hospital Logo" onerror="this.src='https://via.placeholder.com/80?text=Hospital'">
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
            <li data-target="feedback-tab">
                <i class="fas fa-comments"></i> <span>Patient Feedback</span>
            </li>
            <li data-target="settings-tab">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </li>
            <!-- Logout Button with Confirmation -->
            <li class="logout-btn-item">
                <a href="javascript:void(0)" onclick="confirmLogout()" style="color: white; text-decoration: none; display: block;" class="logout-btn-item">
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
                    <?php if($admin_profile_pic && file_exists('uploads/profile_pictures/' . $admin_profile_pic)): ?>
                        <img src="uploads/profile_pictures/<?php echo $admin_profile_pic; ?>" class="user-avatar-img" alt="Profile">
                    <?php else: ?>
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($admin_name); ?></strong><br>
                    <small>Administrator</small>
                </div>
                <button class="btn btn-sm btn-outline-light ml-3" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
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
                                            Patient Feedback
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $total_feedback; ?>
                                        </div>
                                        <div class="small text-muted">Avg Rating: <?php echo number_format($average_rating, 1); ?>/5</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-comments stats-icon text-warning"></i>
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
                        <div class="quick-action-card" onclick="showTab('feedback-tab')">
                            <i class="fas fa-comments"></i>
                            <h5>View Feedback</h5>
                            <p>Check patient feedback</p>
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
                                        <?php if($total_feedback > 0): ?>
                                        <tr>
                                            <td>Just now</td>
                                            <td>Patient Feedback</td>
                                            <td><?php echo $total_feedback; ?> feedback received</td>
                                            <td><span class="badge badge-info">Average: <?php echo number_format($average_rating, 1); ?>/5</span></td>
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
                                        <?php if($today_feedback > 0): ?>
                                        <tr>
                                            <td>Today</td>
                                            <td>Today's Feedback</td>
                                            <td><?php echo $today_feedback; ?> new feedback</td>
                                            <td><span class="badge badge-warning">New</span></td>
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
                
                <!-- Add Appointment Form with NIC Option -->
                <div class="form-card mb-4 collapse show" id="addAppointmentForm">
                    <div class="form-card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-plus mr-2"></i>Create New Appointment (Using NIC)</h5>
                    </div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Patient NIC *</label>
                                    <input type="text" class="form-control" name="patient_nic" placeholder="Enter patient NIC (e.g., NIC123456789)" required>
                                    <small class="text-muted">Enter patient NIC (e.g., NIC123456789)</small>
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
                        <button type="submit" name="add_appointment_by_nic" class="btn btn-success">
                            <i class="fas fa-calendar-plus mr-1"></i> Create Appointment Using NIC
                        </button>
                    </form>
                </div>
                
                <!-- Alternative Appointment Form (by Patient ID) -->
                <div class="form-card mb-4">
                    <div class="form-card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Alternative: Create Appointment by Patient ID</h5>
                    </div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Patient ID *</label>
                                    <input type="number" class="form-control" name="patient_id" placeholder="Enter patient ID">
                                    <small class="text-muted">Enter patient ID (if NIC is not available)</small>
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
                        <button type="submit" name="add_appointment" class="btn btn-info">
                            <i class="fas fa-calendar-alt mr-1"></i> Create Appointment by ID
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
                                           