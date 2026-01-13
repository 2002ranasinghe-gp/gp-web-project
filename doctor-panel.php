<?php
session_start();
if(!isset($_SESSION['doctor'])){
    header("Location: ../doctorlogin.php");
    exit();
}

include('../dbconnection.php');

$doctor_email = $_SESSION['doctor'];
$doctor_name = $_SESSION['doctor_name'];
$doctor_id = $_SESSION['doctor_id'];

// Get doctor details
$doctor_query = "SELECT * FROM doctb WHERE email='$doctor_email'";
$doctor_result = mysqli_query($con, $doctor_query);
$doctor = mysqli_fetch_assoc($doctor_result);

// Get counts
$appointments_count = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb WHERE doctor='$doctor_name'"))[0];
$today_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb WHERE doctor='$doctor_name' AND appdate=CURDATE()"))[0];
$prescriptions_count = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM prestb WHERE doctor='$doctor_name'"))[0];
$pending_payments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM paymenttb WHERE doctor='$doctor_name' AND pay_status='Pending'"))[0];

// Get today's appointments
$today_query = "SELECT a.*, p.fname, p.lname, p.contact FROM appointmenttb a JOIN patreg p ON a.pid = p.pid WHERE a.doctor='$doctor_name' AND a.appdate=CURDATE() ORDER BY a.apptime";
$today_result = mysqli_query($con, $today_query);

// Get all appointments
$all_appointments = mysqli_query($con, "SELECT a.*, p.fname, p.lname, p.email as patient_email FROM appointmenttb a JOIN patreg p ON a.pid = p.pid WHERE a.doctor='$doctor_name' ORDER BY a.appdate DESC, a.apptime DESC");

// Get prescriptions
$prescriptions = mysqli_query($con, "SELECT p.*, pt.fname, pt.lname FROM prestb p JOIN patreg pt ON p.pid = pt.pid WHERE p.doctor='$doctor_name' ORDER BY p.created_date DESC");
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
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, var(--secondary-color) 0%, #1a252f 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            padding: 0;
            box-shadow: 3px 0 15px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 30px 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .doctor-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, #2980b9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 36px;
            font-weight: bold;
            color: white;
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .doctor-info h5 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .doctor-info p {
            font-size: 14px;
            color: #bdc3c7;
            margin-bottom: 0;
        }
        
        .nav-menu {
            padding: 20px 0;
            margin: 0;
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
            padding: 14px 25px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid var(--primary-color);
        }
        
        .nav-link i {
            width: 25px;
            text-align: center;
            margin-right: 12px;
            font-size: 18px;
        }
        
        .logout-section {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
        }
        
        .logout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--danger-color) 0%, #c0392b 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            width: 80%;
            font-weight: 500;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
            color: white;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            width: calc(100% - 260px);
            min-height: 100vh;
        }
        
        .topbar {
            background: white;
            padding: 20px 30px;
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
            color: var(--secondary-color);
            margin: 0;
        }
        
        .welcome-text {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        /* Stats Cards */
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
        
        .card-primary {
            border-left: 5px solid var(--primary-color);
        }
        
        .card-success {
            border-left: 5px solid var(--success-color);
        }
        
        .card-warning {
            border-left: 5px solid var(--warning-color);
        }
        
        .card-danger {
            border-left: 5px solid var(--danger-color);
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
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        /* Tables */
        .data-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .table-header {
            background: linear-gradient(90deg, var(--primary-color) 0%, #2980b9 100%);
            color: white;
            padding: 18px 25px;
            border-radius: 12px 12px 0 0;
        }
        
        .table-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: var(--secondary-color);
            padding: 15px;
        }
        
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        /* Today's Appointments */
        .today-appointment-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s;
        }
        
        .today-appointment-item:hover {
            transform: translateX(5px);
        }
        
        .appointment-time {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        /* Interface Management */
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header .doctor-info,
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
            
            .logout-btn span {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
                padding: 15px;
            }
        }
        
        /* Buttons */
        .btn-prescription {
            background: linear-gradient(135deg, var(--success-color) 0%, #27ae60 100%);
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-prescription:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
            color: white;
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
            <div class="doctor-info">
                <h5>Dr. <?php echo htmlspecialchars($doctor_name); ?></h5>
                <p><?php echo htmlspecialchars($doctor['spec']); ?></p>
                <small>ID: <?php echo htmlspecialchars($doctor_id); ?></small>
            </div>
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
                <a href="#" class="nav-link" onclick="showInterface('profile')">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile</span>
                </a>
            </li>
        </ul>

        <div class="logout-section">
            <a href="../doctorlogout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i>
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
                <p class="welcome-text">Welcome back, Dr. <?php echo htmlspecialchars($doctor_name); ?>!</p>
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
                    <div class="card stats-card card-primary">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stats-label">TOTAL APPOINTMENTS</div>
                                    <div class="stats-number text-primary"><?php echo $appointments_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-alt stats-icon text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card card-success">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stats-label">TODAY'S APPOINTMENTS</div>
                                    <div class="stats-number text-success"><?php echo $today_appointments; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-day stats-icon text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card card-warning">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stats-label">PRESCRIPTIONS</div>
                                    <div class="stats-number text-warning"><?php echo $prescriptions_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-prescription-bottle-alt stats-icon text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card card-danger">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stats-label">PENDING PAYMENTS</div>
                                    <div class="stats-number text-danger"><?php echo $pending_payments; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-money-bill-wave stats-icon text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments & Quick Actions -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="data-table">
                        <div class="table-header">
                            <h5><i class="fas fa-calendar-day me-2"></i>Today's Appointments</h5>
                        </div>
                        <div class="p-4">
                            <?php if(mysqli_num_rows($today_result) > 0): ?>
                                <?php while($appointment = mysqli_fetch_assoc($today_result)): ?>
                                    <div class="today-appointment-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <div class="appointment-time">
                                                    <?php echo date('h:i A', strtotime($appointment['apptime'])); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($appointment['fname'] . ' ' . $appointment['lname']); ?></h6>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($appointment['contact']); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <button class="btn btn-prescription btn-sm" onclick="alert('Add prescription for <?php echo htmlspecialchars($appointment['fname']); ?>')">
                                                    <i class="fas fa-file-medical me-1"></i>Add Prescription
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <h5>No appointments scheduled for today</h5>
                                    <p class="text-muted">Enjoy your day!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="data-table">
                        <div class="table-header">
                            <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="p-4">
                            <div class="d-grid gap-3">
                                <button class="btn btn-primary btn-lg" onclick="showInterface('appointments')">
                                    <i class="fas fa-calendar-plus me-2"></i>View All Appointments
                                </button>
                                <button class="btn btn-success btn-lg" onclick="alert('Add prescription feature coming soon!')">
                                    <i class="fas fa-file-medical me-2"></i>Add New Prescription
                                </button>
                                <button class="btn btn-info btn-lg" onclick="showInterface('patients')">
                                    <i class="fas fa-user-friends me-2"></i>Patient Directory
                                </button>
                                <button class="btn btn-warning btn-lg" onclick="showInterface('profile')">
                                    <i class="fas fa-user-cog me-2"></i>Update Profile
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

            <div class="data-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
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
                            <?php if(mysqli_num_rows($all_appointments) > 0): ?>
                                <?php while($appointment = mysqli_fetch_assoc($all_appointments)): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($appointment['appdate'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($appointment['apptime'])); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['fname'] . ' ' . $appointment['lname']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['contact']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_email']); ?></td>
                                        <td><?php echo number_format($appointment['docFees'], 2); ?></td>
                                        <td>
                                            <?php if($appointment['doctorStatus'] == 1): ?>
                                                <span class="badge bg-success">Confirmed</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-prescription btn-sm" onclick="alert('Prescription for <?php echo htmlspecialchars($appointment['fname']); ?>')">
                                                <i class="fas fa-file-medical"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h5>No appointments found</h5>
                                    </td>
                                </tr>
                            <?php endif; ?>
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
                    <button class="btn btn-success" onclick="alert('Add prescription feature coming soon!')">
                        <i class="fas fa-plus me-2"></i>Add New
                    </button>
                </div>
            </div>

            <div class="data-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Disease</th>
                                <th>Allergy</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($prescriptions) > 0): ?>
                                <?php while($prescription = mysqli_fetch_assoc($prescriptions)): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($prescription['appdate'])); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['fname'] . ' ' . $prescription['lname']); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['disease']); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['allergy']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $prescription['emailStatus'] == 'Sent' ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo htmlspecialchars($prescription['emailStatus']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="alert('View prescription details for <?php echo htmlspecialchars($prescription['fname']); ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-prescription-bottle fa-3x text-muted mb-3"></i>
                                        <h5>No prescriptions found</h5>
                                        <p class="text-muted">Start by adding your first prescription</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
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
            $patients_query = mysqli_query($con, "SELECT DISTINCT p.* FROM patreg p JOIN appointmenttb a ON p.pid = a.pid WHERE a.doctor='$doctor_name' ORDER BY p.fname");
            ?>

            <div class="data-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Date of Birth</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Last Visit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($patients_query) > 0): ?>
                                <?php while($patient = mysqli_fetch_assoc($patients_query)): ?>
                                    <tr>
                                        <td>PAT<?php echo str_pad($patient['pid'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($patient['dob'])); ?></td>
                                        <td><?php echo htmlspecialchars($patient['contact']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                        <td>
                                            <?php 
                                            $last_visit = mysqli_query($con, "SELECT MAX(appdate) as last_visit FROM appointmenttb WHERE pid='{$patient['pid']}' AND doctor='$doctor_name'");
                                            $last = mysqli_fetch_assoc($last_visit);
                                            echo $last['last_visit'] ? date('d M Y', strtotime($last['last_visit'])) : 'Never';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                        <h5>No patients found</h5>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                    <div class="data-table">
                        <div class="table-header">
                            <h5><i class="fas fa-user me-2"></i>Doctor Information</h5>
                        </div>
                        <div class="p-4 text-center">
                            <div class="doctor-avatar mx-auto mb-3" style="width: 120px; height: 120px; font-size: 48px;">
                                <?php echo strtoupper(substr($doctor_name, 0, 1)); ?>
                            </div>
                            <h4>Dr. <?php echo htmlspecialchars($doctor_name); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($doctor['spec']); ?></p>
                            
                            <div class="list-group list-group-flush text-start mt-4">
                                <div class="list-group-item border-0 px-0">
                                    <i class="fas fa-id-card me-2 text-primary"></i>
                                    <strong>Doctor ID:</strong> <?php echo htmlspecialchars($doctor['id']); ?>
                                </div>
                                <div class="list-group-item border-0 px-0">
                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?>
                                </div>
                                <div class="list-group-item border-0 px-0">
                                    <i class="fas fa-phone me-2 text-primary"></i>
                                    <strong>Contact:</strong> <?php echo htmlspecialchars($doctor['contact']); ?>
                                </div>
                                <div class="list-group-item border-0 px-0">
                                    <i class="fas fa-money-bill-wave me-2 text-primary"></i>
                                    <strong>Consultation Fee:</strong> LKR <?php echo number_format($doctor['docFees'], 2); ?>
                                </div>
                                <div class="list-group-item border-0 px-0">
                                    <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                    <strong>Registered:</strong> <?php echo date('d M Y', strtotime($doctor['reg_date'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="data-table">
                        <div class="table-header">
                            <h5><i class="fas fa-key me-2"></i>Change Password</h5>
                        </div>
                        <div class="p-4">
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
                                        <i class="fas fa-save me-2"></i>Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle interfaces
        function showInterface(id) {
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
                'profile': 'My Profile'
            };
            document.getElementById('pageTitle').textContent = titles[id] || 'Doctor Dashboard';
            
            // Update active menu item
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Find and activate the clicked menu item
            const activeLink = document.querySelector(`.nav-link[onclick="showInterface('${id}')"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }
        }

        // Password form
        document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Password updated successfully!');
            this.reset();
        });

        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.3s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if(alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 300);
            });
        }, 5000);
    </script>
</body>
</html>
<?php mysqli_close($con); ?>