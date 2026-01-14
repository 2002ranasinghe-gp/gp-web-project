<?php
session_start();

$con = mysqli_connect("localhost", "root", "", "myhmsdb");
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['reception'])) {
    header("Location: index.php");
    exit();
}

$page = $_GET['page'] ?? 'dashboard';
$current_user = $_SESSION['reception'];
$patient_msg = "";
$appointment_msg = "";
$settings_msg = "";
$payment_msg = "";

// ===========================
// ADD PATIENT (Reception)
// ===========================
if (isset($_POST['add_patient'])) {
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

    if ($password !== $cpassword) {
        $patient_msg = "<div class='alert alert-danger'>❌ Passwords do not match!</div>";
    } else {
        $nicNumbers = preg_replace('/[^0-9]/', '', $nic_input);
        $national_id = 'NIC' . $nicNumbers;

        $check_email = mysqli_query($con, "SELECT * FROM patreg WHERE email='$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $patient_msg = "<div class='alert alert-danger'>❌ Email already exists!</div>";
        } else {
            $check_nic = mysqli_query($con, "SELECT * FROM patreg WHERE national_id='$national_id'");
            if (mysqli_num_rows($check_nic) > 0) {
                $patient_msg = "<div class='alert alert-danger'>❌ NIC already exists!</div>";
            } else {
                $query = "INSERT INTO patreg (fname, lname, gender, dob, email, contact, address, emergencyContact, national_id, password)
                          VALUES ('$fname', '$lname', '$gender', '$dob', '$email', '$contact', '$address', '$emergencyContact', '$national_id', '$password')";
                if (mysqli_query($con, $query)) {
                    $new_id = mysqli_insert_id($con);
                    $patient_msg = "<div class='alert alert-success'>✅ Patient registered! ID: $new_id | NIC: $national_id</div>";
                    echo "<script>document.getElementById('add-patient-form').reset();</script>";
                } else {
                    $patient_msg = "<div class='alert alert-danger'>❌ DB Error: " . mysqli_error($con) . "</div>";
                }
            }
        }
    }
}

// ===========================
// CREATE APPOINTMENT
// ===========================
if (isset($_POST['create_appointment'])) {
    $patient_id = mysqli_real_escape_string($con, $_POST['patient_id']);
    $doctor_id = mysqli_real_escape_string($con, $_POST['doctor_id']);
    $appointment_date = mysqli_real_escape_string($con, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($con, $_POST['appointment_time']);
    $reason = mysqli_real_escape_string($con, $_POST['reason']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    // Check if patient exists
    $check_patient = mysqli_query($con, "SELECT * FROM patreg WHERE pid='$patient_id'");
    if (mysqli_num_rows($check_patient) == 0) {
        $appointment_msg = "<div class='alert alert-danger'>❌ Patient ID not found!</div>";
    } else {
        // Check if doctor exists
        $check_doctor = mysqli_query($con, "SELECT * FROM doctb WHERE id='$doctor_id'");
        if (mysqli_num_rows($check_doctor) == 0) {
            $appointment_msg = "<div class='alert alert-danger'>❌ Doctor ID not found!</div>";
        } else {
            // Check for existing appointment at same time
            $check_slot = mysqli_query($con, "SELECT * FROM appointment 
                                               WHERE doctor_id='$doctor_id' 
                                               AND appointment_date='$appointment_date' 
                                               AND appointment_time='$appointment_time' 
                                               AND status != 'Cancelled'");
            
            if (mysqli_num_rows($check_slot) > 0) {
                $appointment_msg = "<div class='alert alert-warning'>⚠️ Time slot already booked! Choose another time.</div>";
            } else {
                $query = "INSERT INTO appointment (patient_id, doctor_id, appointment_date, appointment_time, reason, status, created_by)
                          VALUES ('$patient_id', '$doctor_id', '$appointment_date', '$appointment_time', '$reason', '$status', '$current_user')";
                
                if (mysqli_query($con, $query)) {
                    $appointment_id = mysqli_insert_id($con);
                    $appointment_msg = "<div class='alert alert-success'>✅ Appointment created! Appointment ID: $appointment_id</div>";
                    echo "<script>document.getElementById('appointment-form').reset();</script>";
                } else {
                    $appointment_msg = "<div class='alert alert-danger'>❌ DB Error: " . mysqli_error($con) . "</div>";
                }
            }
        }
    }
}

// ===========================
// UPDATE APPOINTMENT STATUS
// ===========================
if (isset($_POST['update_appointment_status'])) {
    $appointment_id = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $new_status = mysqli_real_escape_string($con, $_POST['new_status']);
    
    $query = "UPDATE appointment SET status='$new_status', updated_at=NOW() WHERE id='$appointment_id'";
    if (mysqli_query($con, $query)) {
        $appointment_msg = "<div class='alert alert-success'>✅ Appointment status updated to: $new_status</div>";
    } else {
        $appointment_msg = "<div class='alert alert-danger'>❌ Error updating status!</div>";
    }
}

// ===========================
// PROCESS PAYMENT
// ===========================
if (isset($_POST['process_payment'])) {
    $appointment_id = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $patient_id = mysqli_real_escape_string($con, $_POST['patient_id']);
    $amount = mysqli_real_escape_string($con, $_POST['amount']);
    $payment_method = mysqli_real_escape_string($con, $_POST['payment_method']);
    $payment_type = mysqli_real_escape_string($con, $_POST['payment_type']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    
    // Generate invoice number
    $invoice_number = 'INV' . date('Ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    $query = "INSERT INTO payments (invoice_number, appointment_id, patient_id, amount, payment_method, payment_type, description, processed_by)
              VALUES ('$invoice_number', '$appointment_id', '$patient_id', '$amount', '$payment_method', '$payment_type', '$description', '$current_user')";
    
    if (mysqli_query($con, $query)) {
        // Update appointment payment status
        mysqli_query($con, "UPDATE appointment SET payment_status='Paid' WHERE id='$appointment_id'");
        $payment_id = mysqli_insert_id($con);
        $payment_msg = "<div class='alert alert-success'>✅ Payment processed! Invoice: $invoice_number | Payment ID: $payment_id</div>";
        echo "<script>document.getElementById('payment-form').reset();</script>";
    } else {
        $payment_msg = "<div class='alert alert-danger'>❌ Error processing payment!</div>";
    }
}

// ===========================
// RECEPTION SETTINGS
// ===========================
if (isset($_POST['update_reception_settings'])) {
    $new_email = mysqli_real_escape_string($con, $_POST['email']);
    $new_contact = mysqli_real_escape_string($con, $_POST['contact']);
    $current_pass = mysqli_real_escape_string($con, $_POST['current_password']);
    $new_pass = mysqli_real_escape_string($con, $_POST['new_password']);
    $confirm_pass = mysqli_real_escape_string($con, $_POST['confirm_password']);

    $user_res = $con->query("SELECT * FROM reception WHERE username = '$current_user'");
    if ($user_res->num_rows == 0) {
        $settings_msg = "<div class='alert alert-danger'>User not found.</div>";
    } else {
        $user = $user_res->fetch_assoc();
        
        if ($current_pass !== $user['password']) {
            $settings_msg = "<div class='alert alert-danger'>❌ Current password is incorrect!</div>";
        } else {
            $con->query("UPDATE reception SET email='$new_email', contact='$new_contact' WHERE username='$current_user'");
            
            if (!empty($new_pass)) {
                if ($new_pass !== $confirm_pass) {
                    $settings_msg = "<div class='alert alert-danger'>❌ New passwords don't match!</div>";
                } else {
                    $con->query("UPDATE reception SET password='$new_pass' WHERE username='$current_user'");
                }
            }
            
            if (!isset($error)) {
                $settings_msg = "<div class='alert alert-success'>✅ Settings updated successfully!</div>";
            }
        }
    }
}

// Fetch data for various pages
$profile = $con->query("SELECT * FROM reception WHERE username = '$current_user'")->fetch_assoc();
$patients_count = $con->query("SELECT COUNT(*) as total FROM patreg")->fetch_assoc()['total'];
$appointments_today = $con->query("SELECT COUNT(*) as total FROM appointment WHERE appointment_date = CURDATE()")->fetch_assoc()['total'];
$pending_payments = $con->query("SELECT COUNT(*) as total FROM appointment WHERE payment_status = 'Pending' AND status = 'Completed'")->fetch_assoc()['total'];

// Helper to prevent XSS
function e($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reception Dashboard</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
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
    --success: #4caf50;
    --warning: #ff9800;
    --danger: #f44336;
    --info: #00bcd4;
}
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    margin: 0; padding: 0; min-height: 100vh;
    color: var(--text-dark);
}
.navbar { 
    background: var(--primary-blue); 
    padding: 0.8rem 1rem; 
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.navbar .navbar-brand { 
    font-weight: bold; color: var(--white); font-size: 1.5rem;
}
.navbar .welcome { 
    margin-left: auto; color: var(--white); font-weight: 500;
}
.navbar .welcome a { color: var(--white); text-decoration: none; }
.navbar .welcome a:hover { text-decoration: underline; }
.sidebar { 
    width: 250px; background: var(--primary-blue); height: 100vh; 
    position: fixed; top: 0; left: 0; padding-top: 80px; 
    transition: all 0.3s; box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}
.sidebar ul { list-style: none; padding: 0; margin: 0; }
.sidebar ul li { 
    padding: 15px 25px; color: var(--white); cursor: pointer; 
    transition: all 0.3s; border-left: 4px solid transparent;
    margin: 5px 10px; border-radius: 0 8px 8px 0;
}
.sidebar ul li:hover, .sidebar ul li.active { 
    background: var(--dark-blue); border-left: 4px solid var(--medium-blue);
    transform: translateX(5px);
}
.sidebar ul li a { color: var(--white); text-decoration: none; display: block; font-weight: 500; }
.main { margin-left: 250px; padding: 30px; min-height: calc(100vh - 80px); }
.form-card, .table-container { 
    background: var(--white); padding: 25px; border-radius: 15px; 
    box-shadow: 0 8px 25px rgba(30, 136, 229, 0.1); margin-top: 20px;
}
.form-card-header {
    background: var(--primary-blue); color: white;
    padding: 15px 20px; border-radius: 10px 10px 0 0;
    margin: -25px -25px 20px -25px;
}
.dashboard-header {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
    border-radius: 15px; padding: 40px; margin-bottom: 30px; color: white;
    text-align: center;
}
.stat-card {
    background: white; border-radius: 15px; padding: 25px; 
    box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 20px;
    transition: transform 0.3s;
}
.stat-card:hover { transform: translateY(-5px); }
.stat-icon { font-size: 2.5rem; margin-bottom: 15px; }
.stat-number { font-size: 2rem; font-weight: bold; }
.stat-title { color: var(--text-light); font-size: 0.9rem; text-transform: uppercase; }
.badge-status { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; }
.badge-pending { background: #fff3cd; color: #856404; }
.badge-confirmed { background: #d4edda; color: #155724; }
.badge-completed { background: #d1ecf1; color: #0c5460; }
.badge-cancelled { background: #f8d7da; color: #721c24; }
@media (max-width: 768px) {
    .sidebar { width: 70px; padding-top: 70px; }
    .sidebar ul li span { display: none; }
    .main { margin-left: 70px; padding: 15px; }
}
.table th { border-top: none; background: var(--light-blue); }
.dataTables_wrapper .dataTables_paginate .paginate_button { padding: 3px 10px; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <a class="navbar-brand" href="#"><i class="fas fa-hospital-alt mr-2"></i>HEALTHCARE HMS</a>
    <div class="welcome">
        <i class="fas fa-user-circle mr-2"></i>Welcome, <?php echo e($current_user); ?> 
        | <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
    </div>
</nav>

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

<div class="main">
<?php
switch($page){
    case 'dashboard':
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-tachometer-alt mr-3'></i>Reception Dashboard</h1>
                <p>Welcome back! Manage your daily operations efficiently</p>
              </div>";

        echo "<div class='row'>
                <div class='col-md-3'>
                    <div class='stat-card text-center'>
                        <div class='stat-icon text-primary'><i class='fas fa-user-injured'></i></div>
                        <div class='stat-number'>$patients_count</div>
                        <div class='stat-title'>Total Patients</div>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='stat-card text-center'>
                        <div class='stat-icon text-success'><i class='fas fa-calendar-check'></i></div>
                        <div class='stat-number'>$appointments_today</div>
                        <div class='stat-title'>Today's Appointments</div>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='stat-card text-center'>
                        <div class='stat-icon text-warning'><i class='fas fa-clock'></i></div>
                        <div class='stat-number'>" . $con->query("SELECT COUNT(*) as total FROM appointment WHERE status = 'Pending'")->fetch_assoc()['total'] . "</div>
                        <div class='stat-title'>Pending Appointments</div>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='stat-card text-center'>
                        <div class='stat-icon text-danger'><i class='fas fa-dollar-sign'></i></div>
                        <div class='stat-number'>$pending_payments</div>
                        <div class='stat-title'>Pending Payments</div>
                    </div>
                </div>
              </div>";

        // Today's Appointments
        $today = date('Y-m-d');
        $result = $con->query("SELECT a.*, p.fname, p.lname, d.username as doctor_name 
                               FROM appointment a 
                               LEFT JOIN patreg p ON a.patient_id = p.pid 
                               LEFT JOIN doctb d ON a.doctor_id = d.id 
                               WHERE a.appointment_date = '$today' 
                               ORDER BY a.appointment_time");
        
        if($result->num_rows > 0){
            echo "<div class='table-container'>
                    <h4><i class='fas fa-calendar-day mr-2'></i>Today's Appointments</h4>
                    <table class='table table-hover'>
                    <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Reason</th><th>Status</th></tr></thead><tbody>";
            while($row = $result->fetch_assoc()){
                $status_class = strtolower($row['status']);
                echo "<tr>
                <td>" . date('h:i A', strtotime($row['appointment_time'])) . "</td>
                <td>" . e($row['fname']) . " " . e($row['lname']) . "</td>
                <td>Dr. " . e($row['doctor_name']) . "</td>
                <td>" . e(substr($row['reason'], 0, 30)) . "...</td>
                <td><span class='badge-status badge-$status_class'>" . e($row['status']) . "</span></td>
                </tr>";
            }
            echo "</tbody></table></div>";
        }
        break;

    case 'patients':
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-user-injured mr-3'></i>Patient Management</h1>
                <p>Register new patients or view existing records</p>
              </div>";

        echo $patient_msg;
        echo "<div class='form-card'>
                <div class='form-card-header'>
                    <h5><i class='fas fa-user-plus mr-2'></i>Register New Patient</h5>
                </div>
                <form method='POST' id='add-patient-form'>
                    <div class='row'>
                        <div class='col-md-6'><input type='text' class='form-control mb-3' name='fname' placeholder='First Name *' required></div>
                        <div class='col-md-6'><input type='text' class='form-control mb-3' name='lname' placeholder='Last Name *' required></div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'>
                            <select class='form-control mb-3' name='gender' required>
                                <option value=''>Gender *</option>
                                <option value='Male'>Male</option>
                                <option value='Female'>Female</option>
                                <option value='Other'>Other</option>
                            </select>
                        </div>
                        <div class='col-md-6'><input type='date' class='form-control mb-3' name='dob' placeholder='DOB *' required></div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'><input type='email' class='form-control mb-3' name='email' placeholder='Email *' required></div>
                        <div class='col-md-6'><input type='tel' class='form-control mb-3' name='contact' placeholder='Contact *' required></div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'><input type='text' class='form-control mb-3' name='nic' placeholder='NIC (e.g., 123456789V) *' required></div>
                        <div class='col-md-6'><input type='password' class='form-control mb-3' name='password' placeholder='Password *' minlength='6' required></div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'><input type='password' class='form-control mb-3' name='cpassword' placeholder='Confirm Password *' required></div>
                        <div class='col-md-6'><textarea class='form-control mb-3' name='address' placeholder='Address'></textarea></div>
                    </div>
                    <input type='text' class='form-control mb-3' name='emergencyContact' placeholder='Emergency Contact'>
                    <button type='submit' name='add_patient' class='btn btn-success btn-block'>
                        <i class='fas fa-user-plus mr-1'></i> Register Patient
                    </button>
                </form>
              </div>";

        $result = $con->query("SELECT * FROM patreg ORDER BY pid DESC");
        echo "<div class='table-container'>
                <h4><i class='fas fa-list mr-2'></i>All Patients</h4>
                <table class='table table-striped data-table'>
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Contact</th><th>DOB</th><th>NIC</th><th>Action</th></tr></thead><tbody>";
        while($row = $result->fetch_assoc()){
            echo "<tr>
            <td>" . e($row['pid']) . "</td>
            <td>" . e($row['fname']) . " " . e($row['lname']) . "</td>
            <td>" . e($row['email']) . "</td>
            <td>" . e($row['contact']) . "</td>
            <td>" . e($row['dob']) . "</td>
            <td><code>" . e($row['national_id']) . "</code></td>
            <td>
                <button class='btn btn-sm btn-info' onclick='viewPatient(" . $row['pid'] . ")'><i class='fas fa-eye'></i></button>
                <button class='btn btn-sm btn-warning' onclick='editPatient(" . $row['pid'] . ")'><i class='fas fa-edit'></i></button>
            </td>
            </tr>";
        }
        echo "</tbody></table></div>";
        break;

    case 'appointments':
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-calendar-check mr-3'></i>Appointment Management</h1>
                <p>Create and manage patient appointments</p>
              </div>";

        echo $appointment_msg;
        
        // Create Appointment Form
        echo "<div class='form-card'>
                <div class='form-card-header'>
                    <h5><i class='fas fa-calendar-plus mr-2'></i>Create New Appointment</h5>
                </div>
                <form method='POST' id='appointment-form'>
                    <div class='row'>
                        <div class='col-md-6'>
                            <input type='number' class='form-control mb-3' name='patient_id' placeholder='Patient ID *' required>
                            <small class='text-muted'>Enter patient's ID number</small>
                        </div>
                        <div class='col-md-6'>
                            <input type='number' class='form-control mb-3' name='doctor_id' placeholder='Doctor ID *' required>
                            <small class='text-muted'>Enter doctor's ID number</small>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'><input type='date' class='form-control mb-3' name='appointment_date' min='" . date('Y-m-d') . "' required></div>
                        <div class='col-md-6'>
                            <select class='form-control mb-3' name='appointment_time' required>
                                <option value=''>Select Time *</option>
                                <option value='09:00:00'>09:00 AM</option>
                                <option value='10:00:00'>10:00 AM</option>
                                <option value='11:00:00'>11:00 AM</option>
                                <option value='12:00:00'>12:00 PM</option>
                                <option value='14:00:00'>02:00 PM</option>
                                <option value='15:00:00'>03:00 PM</option>
                                <option value='16:00:00'>04:00 PM</option>
                                <option value='17:00:00'>05:00 PM</option>
                            </select>
                        </div>
                    </div>
                    <div class='form-group'>
                        <textarea class='form-control mb-3' name='reason' placeholder='Reason for appointment *' rows='3' required></textarea>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'>
                            <select class='form-control mb-3' name='status' required>
                                <option value='Pending'>Pending</option>
                                <option value='Confirmed'>Confirmed</option>
                                <option value='Completed'>Completed</option>
                                <option value='Cancelled'>Cancelled</option>
                            </select>
                        </div>
                        <div class='col-md-6'>
                            <button type='submit' name='create_appointment' class='btn btn-primary btn-block'>
                                <i class='fas fa-calendar-check mr-1'></i> Create Appointment
                            </button>
                        </div>
                    </div>
                </form>
              </div>";

        // Update Status Form
        echo "<div class='form-card'>
                <div class='form-card-header'>
                    <h5><i class='fas fa-sync-alt mr-2'></i>Update Appointment Status</h5>
                </div>
                <form method='POST'>
                    <div class='row'>
                        <div class='col-md-6'>
                            <input type='number' class='form-control mb-3' name='appointment_id' placeholder='Appointment ID *' required>
                        </div>
                        <div class='col-md-6'>
                            <select class='form-control mb-3' name='new_status' required>
                                <option value=''>Select New Status *</option>
                                <option value='Pending'>Pending</option>
                                <option value='Confirmed'>Confirmed</option>
                                <option value='Completed'>Completed</option>
                                <option value='Cancelled'>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <button type='submit' name='update_appointment_status' class='btn btn-warning btn-block'>
                        <i class='fas fa-sync mr-1'></i> Update Status
                    </button>
                </form>
              </div>";

        // Appointments List
        $result = $con->query("SELECT a.*, p.fname, p.lname, d.username as doctor_name 
                               FROM appointment a 
                               LEFT JOIN patreg p ON a.patient_id = p.pid 
                               LEFT JOIN doctb d ON a.doctor_id = d.id 
                               ORDER BY a.appointment_date DESC, a.appointment_time DESC");
        
        echo "<div class='table-container'>
                <h4><i class='fas fa-list mr-2'></i>All Appointments</h4>
                <table class='table table-striped data-table'>
                <thead><tr><th>ID</th><th>Date</th><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th><th>Payment</th></tr></thead><tbody>";
        while($row = $result->fetch_assoc()){
            $status_class = strtolower($row['status']);
            $payment_status = $row['payment_status'] ?? 'Pending';
            $payment_class = strtolower($payment_status);
            
            echo "<tr>
            <td>" . e($row['id']) . "</td>
            <td>" . e($row['appointment_date']) . "</td>
            <td>" . date('h:i A', strtotime($row['appointment_time'])) . "</td>
            <td>" . e($row['fname']) . " " . e($row['lname']) . "</td>
            <td>Dr. " . e($row['doctor_name']) . "</td>
            <td><span class='badge-status badge-$status_class'>" . e($row['status']) . "</span></td>
            <td><span class='badge-status badge-$payment_class'>" . e($payment_status) . "</span></td>
            </tr>";
        }
        echo "</tbody></table></div>";
        break;

    case 'schedule':
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-calendar-alt mr-3'></i>Doctor Schedule</h1>
                <p>View and manage doctor schedules</p>
              </div>";

        // Get doctors
        $doctors = $con->query("SELECT * FROM doctb ORDER BY username");
        
        echo "<div class='row'>";
        while($doctor = $doctors->fetch_assoc()){
            $doctor_id = $doctor['id'];
            $doctor_name = $doctor['username'];
            $specialization = $doctor['spec'] ?? 'General';
            
            // Get today's appointments for this doctor
            $today = date('Y-m-d');
            $appointments = $con->query("SELECT a.*, p.fname, p.lname 
                                         FROM appointment a 
                                         LEFT JOIN patreg p ON a.patient_id = p.pid 
                                         WHERE a.doctor_id = '$doctor_id' 
                                         AND a.appointment_date = '$today' 
                                         AND a.status != 'Cancelled'
                                         ORDER BY a.appointment_time");
            
            echo "<div class='col-md-6'>
                    <div class='form-card'>
                        <div class='form-card-header'>
                            <h5><i class='fas fa-user-md mr-2'></i>Dr. " . e($doctor_name) . "</h5>
                            <small class='text-light'>" . e($specialization) . "</small>
                        </div>
                        <h6 class='mt-3'><i class='fas fa-calendar-day mr-2'></i>Today's Schedule (" . date('F j, Y') . ")</h6>";
            
            if($appointments->num_rows > 0){
                echo "<table class='table table-sm'>
                        <thead><tr><th>Time</th><th>Patient</th><th>Status</th></tr></thead><tbody>";
                while($apt = $appointments->fetch_assoc()){
                    $status_class = strtolower($apt['status']);
                    echo "<tr>
                    <td>" . date('h:i A', strtotime($apt['appointment_time'])) . "</td>
                    <td>" . e($apt['fname']) . " " . e($apt['lname']) . "</td>
                    <td><span class='badge-status badge-$status_class'>" . e($apt['status']) . "</span></td>
                    </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<div class='alert alert-info'>No appointments scheduled for today.</div>";
            }
            
            echo "</div></div>";
        }
        echo "</div>";
        break;

    case 'payment':
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-credit-card mr-3'></i>Payment Processing</h1>
                <p>Process payments and manage invoices</p>
              </div>";

        echo $payment_msg;
        
        // Payment Form
        echo "<div class='form-card'>
                <div class='form-card-header'>
                    <h5><i class='fas fa-cash-register mr-2'></i>Process Payment</h5>
                </div>
                <form method='POST' id='payment-form'>
                    <div class='row'>
                        <div class='col-md-6'>
                            <input type='number' class='form-control mb-3' name='appointment_id' placeholder='Appointment ID *' required>
                        </div>
                        <div class='col-md-6'>
                            <input type='number' class='form-control mb-3' name='patient_id' placeholder='Patient ID *' required>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'>
                            <input type='number' class='form-control mb-3' name='amount' placeholder='Amount (LKR) *' step='0.01' required>
                        </div>
                        <div class='col-md-6'>
                            <select class='form-control mb-3' name='payment_method' required>
                                <option value=''>Payment Method *</option>
                                <option value='Cash'>Cash</option>
                                <option value='Credit Card'>Credit Card</option>
                                <option value='Debit Card'>Debit Card</option>
                                <option value='Online Transfer'>Online Transfer</option>
                                <option value='Insurance'>Insurance</option>
                            </select>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'>
                            <select class='form-control mb-3' name='payment_type' required>
                                <option value=''>Payment Type *</option>
                                <option value='Consultation'>Consultation Fee</option>
                                <option value='Procedure'>Medical Procedure</option>
                                <option value='Medication'>Medication</option>
                                <option value='Lab Test'>Lab Test</option>
                                <option value='Other'>Other</option>
                            </select>
                        </div>
                        <div class='col-md-6'>
                            <textarea class='form-control mb-3' name='description' placeholder='Description' rows='2'></textarea>
                        </div>
                    </div>
                    <button type='submit' name='process_payment' class='btn btn-success btn-block'>
                        <i class='fas fa-check-circle mr-1'></i> Process Payment
                    </button>
                </form>
              </div>";

        // Recent Payments
        $result = $con->query("SELECT p.*, a.patient_id, pat.fname, pat.lname 
                               FROM payments p 
                               LEFT JOIN appointment a ON p.appointment_id = a.id 
                               LEFT JOIN patreg pat ON p.patient_id = pat.pid 
                               ORDER BY p.created_at DESC LIMIT 10");
        
        echo "<div class='table-container'>
                <h4><i class='fas fa-history mr-2'></i>Recent Payments</h4>
                <table class='table table-striped'>
                <thead><tr><th>Invoice</th><th>Date</th><th>Patient</th><th>Amount</th><th>Method</th><th>Type</th></tr></thead><tbody>";
        while($row = $result->fetch_assoc()){
            echo "<tr>
            <td><code>" . e($row['invoice_number']) . "</code></td>
            <td>" . date('Y-m-d', strtotime($row['created_at'])) . "</td>
            <td>" . e($row['fname']) . " " . e($row['lname']) . "</td>
            <td>LKR " . number_format($row['amount'], 2) . "</td>
            <td>" . e($row['payment_method']) . "</td>
            <td>" . e($row['payment_type']) . "</td>
            </tr>";
        }
        echo "</tbody></table></div>";
        break;

    case 'staff':
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-users mr-3'></i>Staff Directory</h1>
                <p>View hospital staff information</p>
              </div>";

        // Doctors List
        echo "<div class='table-container'>
                <h4><i class='fas fa-user-md mr-2'></i>Doctors</h4>
                <table class='table table-striped data-table'>
                <thead><tr><th>ID</th><th>Name</th><th>Specialization</th><th>Email</th><th>Contact</th><th>Schedule</th></tr></thead><tbody>";
        
        $result = $con->query("SELECT * FROM doctb ORDER BY username");
        while($row = $result->fetch_assoc()){
            echo "<tr>
            <td>" . e($row['id']) . "</td>
            <td>Dr. " . e($row['username']) . "</td>
            <td>" . e($row['spec'] ?? 'General') . "</td>
            <td>" . e($row['email'] ?? 'N/A') . "</td>
            <td>" . e($row['docFees'] ?? 'N/A') . "</td>
            <td><button class='btn btn-sm btn-info' onclick='viewDoctorSchedule(" . $row['id'] . ")'><i class='fas fa-calendar'></i> View</button></td>
            </tr>";
        }
        echo "</tbody></table></div>";

        // Reception Staff
        echo "<div class='table-container mt-4'>
                <h4><i class='fas fa-user-tie mr-2'></i>Reception Staff</h4>
                <table class='table table-striped'>
                <thead><tr><th>Username</th><th>Email</th><th>Contact</th><th>Status</th></tr></thead><tbody>";
        
        $result = $con->query("SELECT * FROM reception");
        while($row = $result->fetch_assoc()){
            echo "<tr>
            <td>" . e($row['username']) . "</td>
            <td>" . e($row['email']) . "</td>
            <td>" . e($row['contact'] ?? 'N/A') . "</td>
            <td><span class='badge badge-success'>Active</span></td>
            </tr>";
        }
        echo "</tbody></table></div>";
        break;

    case 'settings':
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-cog mr-3'></i>Receptionist Settings</h1>
                <p>Manage your account and preferences</p>
              </div>";
        echo $settings_msg;
        echo "<div class='form-card'>
                <div class='form-card-header'>
                    <h5><i class='fas fa-user-edit mr-2'></i>Your Profile</h5>
                </div>
                <form method='POST'>
                    <input type='hidden' name='update_reception_settings' value='1'>
                    <div class='form-group'>
                        <label>Username</label>
                        <input type='text' class='form-control' value='" . e($profile['username']) . "' disabled>
                    </div>
                    <div class='form-group'>
                        <label>Email</label>
                        <input type='email' name='email' class='form-control' value='" . e($profile['email'] ?? '') . "' required>
                    </div>
                    <div class='form-group'>
                        <label>Contact</label>
                        <input type='text' name='contact' class='form-control' value='" . e($profile['contact'] ?? '') . "' required>
                    </div>
                    <hr>
                    <h5><i class='fas fa-lock mr-2'></i>Change Password</h5>
                    <div class='form-group'>
                        <label>Current Password *</label>
                        <input type='password' name='current_password' class='form-control' required>
                    </div>
                    <div class='form-group'>
                        <label>New Password</label>
                        <input type='password' name='new_password' class='form-control' placeholder='Leave blank to keep current'>
                    </div>
                    <div class='form-group'>
                        <label>Confirm New Password</label>
                        <input type='password' name='confirm_password' class='form-control'>
                    </div>
                    <button type='submit' class='btn btn-primary'>
                        <i class='fas fa-save mr-1'></i> Save Changes
                    </button>
                </form>
              </div>";
        break;

    default:
        header("Location: reception_dashboard.php?page=dashboard");
        exit();
}
?>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $('.data-table').DataTable({
        "pageLength": 10,
        "ordering": true,
        "searching": true,
        "responsive": true
    });
});

// Auto-format NIC
document.querySelector('input[name="nic"]')?.addEventListener('input', function(e) {
    let v = e.target.value.replace(/[^0-9Vv]/g, '');
    e.target.value = v.toUpperCase();
});

// Helper functions
function viewPatient(pid) {
    alert('View patient details for ID: ' + pid);
    // Implement modal or redirect to patient details page
}

function editPatient(pid) {
    alert('Edit patient with ID: ' + pid);
    // Implement edit functionality
}

function viewDoctorSchedule(doctorId) {
    alert('View schedule for doctor ID: ' + doctorId);
    // Implement schedule view
}

// Auto-set minimum date for appointment date
document.addEventListener('DOMContentLoaded', function() {
    var dateInput = document.querySelector('input[name="appointment_date"]');
    if(dateInput) {
        dateInput.min = new Date().toISOString().split('T')[0];
    }
});
</script>
</body>
</html>