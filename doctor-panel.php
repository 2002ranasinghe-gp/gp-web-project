<?php
session_start();
if(!isset($_SESSION['doctor'])){
    header("Location: doctorlogin.php");
    exit();
}

include('dbconnection.php');

$doctor_email = mysqli_real_escape_string($con, $_SESSION['doctor']);
$doctor_query = "SELECT * FROM doctb WHERE email='$doctor_email' LIMIT 1";
$doctor_result = mysqli_query($con, $doctor_query);
$doctor = mysqli_fetch_assoc($doctor_result);

$doctor_name = $doctor['username'];
$doctor_id = $doctor['id'];

// Get counts for dashboard
$count_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb WHERE doctor='$doctor_name'"))[0];
$count_today_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb WHERE doctor='$doctor_name' AND appdate=CURDATE()"))[0];
$count_prescriptions = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM prestb WHERE doctor='$doctor_name'"))[0];
$count_pending_payments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM paymenttb WHERE doctor='$doctor_name' AND pay_status='Pending'"))[0];

// Get doctor's appointments
$appointments = mysqli_query($con, "SELECT a.*, p.fname, p.lname, p.contact, p.email as patient_email 
                                   FROM appointmenttb a 
                                   JOIN patreg p ON a.pid = p.pid 
                                   WHERE a.doctor='$doctor_name' 
                                   ORDER BY a.appdate DESC");

// Get doctor's prescriptions
$prescriptions = mysqli_query($con, "SELECT p.*, pt.fname, pt.lname, pt.national_id 
                                    FROM prestb p 
                                    JOIN patreg pt ON p.pid = pt.pid 
                                    WHERE p.doctor='$doctor_name' 
                                    ORDER BY p.appdate DESC");

// Get doctor's payments
$payments = mysqli_query($con, "SELECT * FROM paymenttb WHERE doctor='$doctor_name' ORDER BY pay_date DESC");

// Get today's appointments
$today_appointments = mysqli_query($con, "SELECT a.*, p.fname, p.lname, p.contact 
                                         FROM appointmenttb a 
                                         JOIN patreg p ON a.pid = p.pid 
                                         WHERE a.doctor='$doctor_name' AND a.appdate=CURDATE() 
                                         ORDER BY a.apptime");

// Add new prescription
if(isset($_POST['add_prescription'])){
    $patient_id = mysqli_real_escape_string($con, $_POST['patient_id']);
    $appointment_id = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $disease = mysqli_real_escape_string($con, $_POST['disease']);
    $allergy = mysqli_real_escape_string($con, $_POST['allergy']);
    $prescription_text = mysqli_real_escape_string($con, $_POST['prescription']);
    
    // Get patient details
    $patient_query = "SELECT * FROM patreg WHERE pid='$patient_id'";
    $patient_result = mysqli_query($con, $patient_query);
    $patient = mysqli_fetch_assoc($patient_result);
    
    // Get appointment details
    $appointment_query = "SELECT * FROM appointmenttb WHERE ID='$appointment_id'";
    $appointment_result = mysqli_query($con, $appointment_query);
    $appointment = mysqli_fetch_assoc($appointment_result);
    
    $insert_query = "INSERT INTO prestb (doctor, pid, appointment_id, fname, lname, national_id, 
                                        appdate, apptime, disease, allergy, prescription, emailStatus) 
                     VALUES ('$doctor_name', '$patient_id', '$appointment_id', 
                            '{$patient['fname']}', '{$patient['lname']}', '{$patient['national_id']}',
                            '{$appointment['appdate']}', '{$appointment['apptime']}',
                            '$disease', '$allergy', '$prescription_text', 'Not Sent')";
    
    if(mysqli_query($con, $insert_query)){
        $prescription_success = "Prescription added successfully!";
    }else{
        $prescription_error = "Error adding prescription!";
    }
}

// Update appointment status
if(isset($_POST['update_status'])){
    $appointment_id = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    $update_query = "UPDATE appointmenttb SET doctorStatus='$status' WHERE ID='$appointment_id'";
    if(mysqli_query($con, $update_query)){
        $status_success = "Appointment status updated!";
    }else{
        $status_error = "Error updating status!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Healthcare Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* === GENERAL STYLES === */
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }

        /* === SIDEBAR === */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50, #34495e);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            padding: 0;
            box-shadow: 3px 0 15px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .sidebar-header {
            padding: 25px 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .doctor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3498db, #2980b9);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 32px;
            font-weight: bold;
            color: white;
            border: 3px solid rgba(255,255,255,0.3);
        }
        .doctor-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .doctor-specialty {
            font-size: 14px;
            color: #bdc3c7;
            margin-bottom: 0;
        }

        .nav-menu {
            padding: 20px 0;
        }
        .nav-item {
            list-style: none;
            margin: 5px 0;
        }
        .nav-link {
            display: flex;
            align-items: center;
            color: #ecf0f1;
            text-decoration: none;
            padding: 12px 25px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid #3498db;
        }
        .nav-link i {
            width: 25px;
            text-align: center;
            margin-right: 12px;
            font-size: 18px;
        }
        .nav-link span {
            font-size: 15px;
        }

        .logout-btn {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
        }
        .logout-btn a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 25px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            width: 80%;
        }
        .logout-btn a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        /* === MAIN CONTENT === */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            width: calc(100% - 260px);
            min-height: 100vh;
        }

        .topbar {
            background: white;
            padding: 15px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }
        .welcome-text {
            color: #7f8c8d;
            font-size: 16px;
        }

        /* === DASHBOARD CARDS === */
        .stats-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 25px;
            overflow: hidden;
        }
        .stats-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
        }
        .stats-icon {
            font-size: 40px;
            opacity: 0.9;
        }
        .stats-number {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 10px 0;
        }
        .stats-label {
            font-size: 15px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* === TABLES === */
        .data-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .table-header {
            background: linear-gradient(90deg, #3498db, #2980b9);
            color: white;
            padding: 18px 25px;
            border-radius: 12px 12px 0 0;
        }
        .table-header h5 {
            margin: 0;
            font-weight: 600;
        }
        .table-responsive {
            padding: 0;
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            padding: 15px;
        }
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        /* === FORMS === */
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        .form-card h5 {
            color: #3498db;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }

        /* === BUTTONS === */
        .btn-action {
            padding: 6px 15px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-prescription {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            border: none;
        }
        .btn-prescription:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }
        .btn-update {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
        }
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        /* === ALERTS === */
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        /* === INTERFACES === */
        .interface {
            display: none;
            animation: fadeIn 0.5s;
        }
        .interface.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* === TODAY'S APPOINTMENTS === */
        .today-appointment-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #3498db;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .today-appointment-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
        }
        .appointment-time {
            font-size: 24px;
            font-weight: 700;
            color: #3498db;
        }
        .appointment-patient {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header .doctor-name,
            .sidebar-header .doctor-specialty,
            .nav-link span {
                display: none;
            }
            .nav-link {
                justify-content: center;
                padding: 15px 0;
            }
            .nav-link i {
                margin-right: 0;
                font-size: 22px;
            }
            .logout-btn a span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
                padding: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="doctor-avatar">
                <?php echo strtoupper(substr($doctor_name, 0, 1)); ?>
            </div>
            <h5 class="doctor-name">Dr. <?php echo htmlspecialchars($doctor_name); ?></h5>
            <p class="doctor-specialty"><?php echo htmlspecialchars($doctor['spec']); ?></p>
        </div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="#" class="nav-link active" onclick="showInterface('dashboard')">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="showInterface('appointments')">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointments</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="showInterface('prescriptions')">
                    <i class="fas fa-prescription"></i>
                    <span>Prescriptions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="showInterface('patients')">
                    <i class="fas fa-user-injured"></i>
                    <span>Patients</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="showInterface('payments')">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="showInterface('add-prescription')">
                    <i class="fas fa-file-medical"></i>
                    <span>Add Prescription</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="showInterface('profile')">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile</span>
                </a>
            </li>
        </ul>

        <div class="logout-btn">
            <a href="doctorlogout.php">
                <i class="fas fa-sign-out-alt mr-2"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h1 class="page-title" id="pageTitle">Doctor Dashboard</h1>
                <p class="welcome-text">Welcome back, Dr. <?php echo htmlspecialchars($doctor_name); ?>! Have a productive day.</p>
            </div>
            <div class="text-end">
                <span class="badge bg-primary p-2">
                    <i class="fas fa-clock me-2"></i>
                    <?php echo date('l, F j, Y'); ?>
                </span>
            </div>
        </div>

        <!-- Dashboard Interface -->
        <div id="dashboard" class="interface active">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card border-left-primary">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stats-label">TOTAL APPOINTMENTS</div>
                                    <div class="stats-number"><?php echo $count_appointments; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-alt stats-icon text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card border-left-success">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stats-label">TODAY'S APPOINTMENTS</div>
                                    <div class="stats-number"><?php echo $count_today_appointments; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-day stats-icon text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card border-left-info">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stats-label">PRESCRIPTIONS</div>
                                    <div class="stats-number"><?php echo $count_prescriptions; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-prescription-bottle-alt stats-icon text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card border-left-warning">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stats-label">PENDING PAYMENTS</div>
                                    <div class="stats-number"><?php echo $count_pending_payments; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-money-bill-wave stats-icon text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="data-table">
                        <div class="table-header">
                            <h5><i class="fas fa-calendar-day me-2"></i>Today's Appointments</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Contact</th>
                                        <th>Appointment ID</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($today_appointments) > 0): ?>
                                        <?php while($appointment = mysqli_fetch_assoc($today_appointments)): ?>
                                            <tr>
                                                <td class="appointment-time">
                                                    <?php echo date('h:i A', strtotime($appointment['apptime'])); ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($appointment['fname'] . ' ' . $appointment['lname']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($appointment['contact']); ?></td>
                                                <td>#APP<?php echo str_pad($appointment['ID'], 5, '0', STR_PAD_LEFT); ?></td>
                                                <td>
                                                    <?php if($appointment['doctorStatus'] == 1): ?>
                                                        <span class="badge-status bg-success">Confirmed</span>
                                                    <?php else: ?>
                                                        <span class="badge-status bg-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-action btn-prescription btn-sm" 
                                                            onclick="addPrescriptionForAppointment(<?php echo $appointment['ID']; ?>, '<?php echo $appointment['fname'] . ' ' . $appointment['lname']; ?>')">
                                                        <i class="fas fa-file-medical me-1"></i>Add Rx
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No appointments scheduled for today</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="data-table">
                        <div class="table-header">
                            <h5><i class="fas fa-chart-line me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="p-4">
                            <div class="d-grid gap-3">
                                <button class="btn btn-primary btn-lg" onclick="showInterface('add-prescription')">
                                    <i class="fas fa-file-medical me-2"></i>Add New Prescription
                                </button>
                                <button class="btn btn-success btn-lg" onclick="showInterface('appointments')">
                                    <i class="fas fa-calendar-plus me-2"></i>View All Appointments
                                </button>
                                <button class="btn btn-info btn-lg" onclick="showInterface('patients')">
                                    <i class="fas fa-user-friends me-2"></i>Patient Directory
                                </button>
                                <button class="btn btn-warning btn-lg" onclick="showInterface('payments')">
                                    <i class="fas fa-money-check-alt me-2"></i>Payment Records
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments Interface -->
        <div id="appointments" class="interface">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-calendar-check me-2"></i>My Appointments</h3>
                <button class="btn btn-primary" onclick="showInterface('dashboard')">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </button>
            </div>

            <?php if(isset($status_success)): ?>
                <div class="alert alert-success alert-custom"><?php echo $status_success; ?></div>
            <?php elseif(isset($status_error)): ?>
                <div class="alert alert-danger alert-custom"><?php echo $status_error; ?></div>
            <?php endif; ?>

            <div class="data-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Appointment ID</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Fees (LKR)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($appointments, 0);
                            while($appointment = mysqli_fetch_assoc($appointments)): 
                            ?>
                                <tr>
                                    <td>#APP<?php echo str_pad($appointment['ID'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo date('d M Y', strtotime($appointment['appdate'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($appointment['apptime'])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['fname'] . ' ' . $appointment['lname']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['contact']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['patient_email']); ?></td>
                                    <td><?php echo number_format($appointment['docFees'], 2); ?></td>
                                    <td>
                                        <?php if($appointment['doctorStatus'] == 1): ?>
                                            <span class="badge-status bg-success">Confirmed</span>
                                        <?php else: ?>
                                            <span class="badge-status bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-prescription me-1" 
                                                    onclick="addPrescriptionForAppointment(<?php echo $appointment['ID']; ?>, '<?php echo $appointment['fname'] . ' ' . $appointment['lname']; ?>')">
                                                <i class="fas fa-file-medical"></i>
                                            </button>
                                            <button class="btn btn-sm btn-update" 
                                                    onclick="updateAppointmentStatus(<?php echo $appointment['ID']; ?>, <?php echo $appointment['doctorStatus']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Prescriptions Interface -->
        <div id="prescriptions" class="interface">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-prescription me-2"></i>Prescriptions</h3>
                <div>
                    <button class="btn btn-primary me-2" onclick="showInterface('dashboard')">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </button>
                    <button class="btn btn-success" onclick="showInterface('add-prescription')">
                        <i class="fas fa-plus me-2"></i>Add New
                    </button>
                </div>
            </div>

            <div class="data-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Prescription ID</th>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>National ID</th>
                                <th>Disease</th>
                                <th>Allergy</th>
                                <th>Email Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($prescription = mysqli_fetch_assoc($prescriptions)): ?>
                                <tr>
                                    <td>#PR<?php echo str_pad($prescription['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo date('d M Y', strtotime($prescription['appdate'])); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['fname'] . ' ' . $prescription['lname']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['national_id']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['disease']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['allergy']); ?></td>
                                    <td>
                                        <span class="badge-status <?php echo $prescription['emailStatus'] == 'Sent' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo htmlspecialchars($prescription['emailStatus']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" 
                                                onclick="viewPrescription(<?php echo $prescription['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Patients Interface -->
        <div id="patients" class="interface">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-user-injured me-2"></i>Patient Directory</h3>
                <button class="btn btn-primary" onclick="showInterface('dashboard')">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </button>
            </div>

            <?php 
            $all_patients = mysqli_query($con, "SELECT p.*, 
                                               (SELECT COUNT(*) FROM appointmenttb a WHERE a.pid = p.pid AND a.doctor='$doctor_name') as appointment_count
                                               FROM patreg p 
                                               WHERE EXISTS (SELECT 1 FROM appointmenttb a WHERE a.pid = p.pid AND a.doctor='$doctor_name')
                                               ORDER BY p.fname");
            ?>

            <div class="data-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Date of Birth</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Appointments</th>
                                <th>Last Visit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($patient = mysqli_fetch_assoc($all_patients)): ?>
                                <tr>
                                    <td>#PAT<?php echo str_pad($patient['pid'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($patient['dob'])); ?></td>
                                    <td><?php echo htmlspecialchars($patient['contact']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                    <td>
                                        <span class="badge-status bg-primary"><?php echo $patient['appointment_count']; ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $last_visit_query = "SELECT MAX(appdate) as last_visit FROM appointmenttb WHERE pid='{$patient['pid']}' AND doctor='$doctor_name'";
                                        $last_visit_result = mysqli_query($con, $last_visit_query);
                                        $last_visit = mysqli_fetch_assoc($last_visit_result);
                                        echo $last_visit['last_visit'] ? date('d M Y', strtotime($last_visit['last_visit'])) : 'Never';
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payments Interface -->
        <div id="payments" class="interface">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-credit-card me-2"></i>Payment Records</h3>
                <button class="btn btn-primary" onclick="showInterface('dashboard')">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </button>
            </div>

            <div class="data-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Appointment ID</th>
                                <th>Amount (LKR)</th>
                                <th>Method</th>
                                <th>Receipt No</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($payment = mysqli_fetch_assoc($payments)): ?>
                                <tr>
                                    <td>#PAY<?php echo str_pad($payment['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo date('d M Y', strtotime($payment['pay_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['patient_name']); ?></td>
                                    <td>#APP<?php echo str_pad($payment['appointment_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo number_format($payment['fees'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'Not Specified'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['receipt_no'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if($payment['pay_status'] == 'Paid'): ?>
                                            <span class="badge-status bg-success">Paid</span>
                                        <?php else: ?>
                                            <span class="badge-status bg-danger">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Prescription Interface -->
        <div id="add-prescription" class="interface">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-file-medical me-2"></i>Add New Prescription</h3>
                <button class="btn btn-primary" onclick="showInterface('dashboard')">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </button>
            </div>

            <?php if(isset($prescription_success)): ?>
                <div class="alert alert-success alert-custom"><?php echo $prescription_success; ?></div>
            <?php elseif(isset($prescription_error)): ?>
                <div class="alert alert-danger alert-custom"><?php echo $prescription_error; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="form-card">
                        <h5>Prescription Details</h5>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Select Patient</label>
                                    <select class="form-control" name="patient_id" id="patientSelect" required>
                                        <option value="">-- Select Patient --</option>
                                        <?php 
                                        $doctor_patients = mysqli_query($con, "SELECT DISTINCT p.pid, p.fname, p.lname 
                                                                              FROM patreg p 
                                                                              JOIN appointmenttb a ON p.pid = a.pid 
                                                                              WHERE a.doctor='$doctor_name' 
                                                                              ORDER BY p.fname");
                                        while($patient = mysqli_fetch_assoc($doctor_patients)): 
                                        ?>
                                            <option value="<?php echo $patient['pid']; ?>">
                                                <?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Select Appointment</label>
                                    <select class="form-control" name="appointment_id" id="appointmentSelect" required>
                                        <option value="">-- Select Appointment --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Disease / Condition</label>
                                    <input type="text" class="form-control" name="disease" required 
                                           placeholder="Enter disease or condition">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Allergies</label>
                                    <input type="text" class="form-control" name="allergy" 
                                           placeholder="Enter any allergies (if none, type 'None')">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prescription</label>
                                <textarea class="form-control" name="prescription" rows="8" required 
                                          placeholder="Enter detailed prescription..."></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="add_prescription" class="btn btn-success btn-lg">
                                    <i class="fas fa-save me-2"></i>Save Prescription
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="form-card">
                        <h5>Recent Prescriptions</h5>
                        <?php 
                        $recent_prescriptions = mysqli_query($con, "SELECT p.*, pt.fname, pt.lname 
                                                                   FROM prestb p 
                                                                   JOIN patreg pt ON p.pid = pt.pid 
                                                                   WHERE p.doctor='$doctor_name' 
                                                                   ORDER BY p.created_date DESC LIMIT 5");
                        ?>
                        <div class="list-group">
                            <?php while($rx = mysqli_fetch_assoc($recent_prescriptions)): ?>
                                <div class="list-group-item">
                                    <small class="text-muted"><?php echo date('d M', strtotime($rx['appdate'])); ?></small>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($rx['fname'] . ' ' . $rx['lname']); ?></h6>
                                    <p class="mb-1 text-truncate"><?php echo htmlspecialchars($rx['disease']); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Interface -->
        <div id="profile" class="interface">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-user-cog me-2"></i>My Profile</h3>
                <button class="btn btn-primary" onclick="showInterface('dashboard')">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </button>
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <div class="form-card">
                        <div class="text-center mb-4">
                            <div class="doctor-avatar mx-auto" style="width: 100px; height: 100px; font-size: 40px;">
                                <?php echo strtoupper(substr($doctor_name, 0, 1)); ?>
                            </div>
                            <h4 class="mt-3">Dr. <?php echo htmlspecialchars($doctor_name); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($doctor['spec']); ?></p>
                        </div>

                        <div class="list-group">
                            <div class="list-group-item">
                                <i class="fas fa-id-card me-2 text-primary"></i>
                                <strong>Doctor ID:</strong> <?php echo htmlspecialchars($doctor['id']); ?>
                            </div>
                            <div class="list-group-item">
                                <i class="fas fa-envelope me-2 text-primary"></i>
                                <strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?>
                            </div>
                            <div class="list-group-item">
                                <i class="fas fa-phone me-2 text-primary"></i>
                                <strong>Contact:</strong> <?php echo htmlspecialchars($doctor['contact']); ?>
                            </div>
                            <div class="list-group-item">
                                <i class="fas fa-money-bill-wave me-2 text-primary"></i>
                                <strong>Consultation Fee:</strong> LKR <?php echo number_format($doctor['docFees'], 2); ?>
                            </div>
                            <div class="list-group-item">
                                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                <strong>Registered:</strong> <?php echo date('d M Y', strtotime($doctor['reg_date'])); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="form-card">
                        <h5>Change Password</h5>
                        <form id="changePasswordForm">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Toggle interfaces
        function showInterface(id){
            // Hide all interfaces
            document.querySelectorAll('.interface').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected interface
            document.getElementById(id).classList.add('active');
            
            // Update page title
            const titles = {
                'dashboard': 'Doctor Dashboard',
                'appointments': 'Appointments',
                'prescriptions': 'Prescriptions',
                'patients': 'Patient Directory',
                'payments': 'Payment Records',
                'add-prescription': 'Add Prescription',
                'profile': 'My Profile'
            };
            document.getElementById('pageTitle').textContent = titles[id] || 'Doctor Dashboard';
            
            // Update active menu item
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector(`.nav-link[onclick="showInterface('${id}')"]`).classList.add('active');
        }

        // Patient selection for appointments
        document.getElementById('patientSelect').addEventListener('change', function(){
            const patientId = this.value;
            const appointmentSelect = document.getElementById('appointmentSelect');
            
            if(patientId) {
                // Fetch appointments for selected patient
                fetch(`get_appointments.php?patient_id=${patientId}&doctor=<?php echo $doctor_name; ?>`)
                    .then(response => response.json())
                    .then(data => {
                        appointmentSelect.innerHTML = '<option value="">-- Select Appointment --</option>';
                        data.forEach(appointment => {
                            const option = document.createElement('option');
                            option.value = appointment.id;
                            option.textContent = `Appointment #${appointment.id} - ${appointment.date} ${appointment.time}`;
                            appointmentSelect.appendChild(option);
                        });
                    });
            } else {
                appointmentSelect.innerHTML = '<option value="">-- Select Appointment --</option>';
            }
        });

        // Add prescription for specific appointment
        function addPrescriptionForAppointment(appointmentId, patientName){
            showInterface('add-prescription');
            // Here you would need to implement logic to auto-fill the form
            alert(`Add prescription for ${patientName} (Appointment #${appointmentId})`);
        }

        // Update appointment status
        function updateAppointmentStatus(appointmentId, currentStatus){
            const newStatus = currentStatus == 1 ? 0 : 1;
            const statusText = newStatus == 1 ? 'Confirm' : 'Mark as Pending';
            
            if(confirm(`Are you sure you want to ${statusText} this appointment?`)){
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const appointmentIdInput = document.createElement('input');
                appointmentIdInput.type = 'hidden';
                appointmentIdInput.name = 'appointment_id';
                appointmentIdInput.value = appointmentId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = newStatus;
                
                const submitInput = document.createElement('input');
                submitInput.type = 'hidden';
                submitInput.name = 'update_status';
                submitInput.value = '1';
                
                form.appendChild(appointmentIdInput);
                form.appendChild(statusInput);
                form.appendChild(submitInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // View prescription details
        function viewPrescription(prescriptionId){
            // Implement view prescription modal or redirect
            alert(`View prescription #${prescriptionId}`);
        }

        // Change password form
        document.getElementById('changePasswordForm').addEventListener('submit', function(e){
            e.preventDefault();
            alert('Password updated successfully!');
            this.reset();
        });

        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
<?php mysqli_close($con); ?>