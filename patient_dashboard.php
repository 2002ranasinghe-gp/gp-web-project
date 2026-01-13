<?php
session_start();
if(!isset($_SESSION['patient'])){
    header("Location: index.php");
    exit();
}

include('dbconnection.php');

$email = mysqli_real_escape_string($con, $_SESSION['patient']);
$query = "SELECT * FROM patreg WHERE email='$email' LIMIT 1";
$result = mysqli_query($con, $query);
$patient = mysqli_fetch_assoc($result);

$patient_id = $patient['pid'];
$national_id = $patient['national_id'];

// Get patient statistics
$appointments_count = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email'"));
$confirmed_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email' AND doctorStatus=1"));
$pending_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email' AND doctorStatus=0"));
$prescriptions_count = mysqli_num_rows(mysqli_query($con, "SELECT * FROM prestb WHERE national_id='$national_id'"));

// Get upcoming appointments
$upcoming_query = "SELECT * FROM appointmenttb WHERE email='$email' AND appdate >= CURDATE() ORDER BY appdate ASC LIMIT 5";
$upcoming_result = mysqli_query($con, $upcoming_query);

// Get recent prescriptions
$recent_prescriptions_query = "SELECT * FROM prestb WHERE national_id='$national_id' ORDER BY appdate DESC LIMIT 5";
$recent_prescriptions_result = mysqli_query($con, $recent_prescriptions_query);

// Handle password change
if(isset($_POST['change_password'])){
    $current_password = mysqli_real_escape_string($con, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($con, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);
    
    if(password_verify($current_password, $patient['password']) || $current_password == $patient['password']){
        if($new_password === $confirm_password){
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE patreg SET password='$hashed_password' WHERE pid='$patient_id'";
            if(mysqli_query($con, $update_query)){
                $password_success = "Password updated successfully!";
            }else{
                $password_error = "Error updating password!";
            }
        }else{
            $password_error = "New passwords do not match!";
        }
    }else{
        $password_error = "Current password is incorrect!";
    }
}

// Handle emergency call
if(isset($_POST['emergency_call'])){
    $emergency_contact = mysqli_real_escape_string($con, $_POST['emergency_contact']);
    $emergency_message = mysqli_real_escape_string($con, $_POST['emergency_message']);
    
    $insert_query = "INSERT INTO contact (name, email, contact, message) 
                    VALUES ('{$patient['fname']} {$patient['lname']}', 
                            '{$patient['email']}', 
                            '$emergency_contact', 
                            'EMERGENCY: $emergency_message')";
    if(mysqli_query($con, $insert_query)){
        $emergency_success = "Emergency alert sent! We'll contact you shortly.";
    }else{
        $emergency_error = "Error sending emergency alert!";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Healthcare Hospital - Patient Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <style>
        body { 
            background: #f8f9fa; 
            font-family: 'Poppins', sans-serif; 
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

        /* Interfaces */
        .interface {
            display: none;
            padding: 30px;
            animation: fadeIn 0.5s;
        }
        .interface.active { 
            display: block; 
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Emergency Call */
        .emergency-box {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 65, 108, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(255, 65, 108, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 65, 108, 0); }
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
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img class="logo" src="images/medical-logo.jpg" alt="Hospital Logo">
        <h4>Patient Portal</h4>
        <ul>
            <li data-target="dashboard" class="active">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </li>
            <li data-target="appointments">
                <i class="fas fa-calendar-check"></i> <span>Appointments</span>
            </li>
            <li data-target="prescriptions">
                <i class="fas fa-prescription"></i> <span>Prescriptions</span>
            </li>
            <li data-target="payments">
                <i class="fas fa-credit-card"></i> <span>Payments</span>
            </li>
            <li data-target="doctors">
                <i class="fas fa-user-md"></i> <span>Doctors</span>
            </li>
            <li data-target="emergency">
                <i class="fas fa-ambulance"></i> <span>Emergency</span>
            </li>
            <li data-target="ward">
                <i class="fas fa-bed"></i> <span>Ward Services</span>
            </li>
            <li data-target="profile">
                <i class="fas fa-user-cog"></i> <span>Profile & Settings</span>
            </li>
            <li>
                <a href="logout.php" style="color: white; text-decoration: none; display: block;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="brand">🏥 Healthcare Hospital</div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($patient['fname'], 0, 1)); ?>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname']); ?></strong><br>
                    <small>Patient ID: <?php echo htmlspecialchars($patient['pid']); ?></small>
                </div>
            </div>
        </div>

        <!-- Dashboard Interface -->
        <div id="dashboard" class="interface active">
            <div class="container-fluid">
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-primary" role="alert">
                            <h4 class="alert-heading">
                                Welcome back, <?php echo htmlspecialchars($patient['fname']); ?>!
                            </h4>
                            <p class="mb-0">Here's your healthcare dashboard. You can manage all your medical activities from here.</p>
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
                                            Total Appointments
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $appointments_count; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-alt stats-icon text-primary"></i>
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
                                            Confirmed Appointments
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $confirmed_appointments; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle stats-icon text-success"></i>
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
                                            Pending Appointments
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $pending_appointments; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock stats-icon text-warning"></i>
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
                                            Prescriptions
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $prescriptions_count; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-prescription-bottle-alt stats-icon text-info"></i>
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
                        <div class="quick-action-card" onclick="showInterface('appointments')">
                            <i class="fas fa-calendar-plus"></i>
                            <h5>Book Appointment</h5>
                            <p>Schedule a new appointment with our doctors</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="location.href='book_appointment.php'">
                            <i class="fas fa-file-medical"></i>
                            <h5>View Prescriptions</h5>
                            <p>Check your medical prescriptions</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showInterface('payments')">
                            <i class="fas fa-money-check-alt"></i>
                            <h5>Make Payment</h5>
                            <p>Pay your medical bills online</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showInterface('emergency')">
                            <i class="fas fa-ambulance"></i>
                            <h5>Emergency</h5>
                            <p>Request emergency assistance</p>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="table-header">
                                <h5 class="mb-0"><i class="fas fa-calendar-day mr-2"></i>Upcoming Appointments</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Doctor</th>
                                            <th>Specialization</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($upcoming_result) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($upcoming_result)): ?>
                                                <tr>
                                                    <td><?php echo date('d M Y', strtotime($row['appdate'])); ?></td>
                                                    <td><?php echo date('h:i A', strtotime($row['apptime'])); ?></td>
                                                    <td>Dr. <?php echo htmlspecialchars($row['doctor']); ?></td>
                                                    <td>
                                                        <?php 
                                                            $doc_query = "SELECT spec FROM doctb WHERE username='{$row['doctor']}'";
                                                            $doc_result = mysqli_query($con, $doc_query);
                                                            $doc = mysqli_fetch_assoc($doc_result);
                                                            echo htmlspecialchars($doc['spec'] ?? 'N/A');
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if($row['doctorStatus'] == 1): ?>
                                                            <span class="badge badge-success">Confirmed</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No upcoming appointments</td>
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

        <!-- Appointments Interface -->
        <div id="appointments" class="interface">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-calendar-check mr-2"></i>My Appointments</h3>
                    <button class="btn btn-primary" onclick="location.href='book_appointment.php'">
                        <i class="fas fa-plus mr-2"></i>Book New Appointment
                    </button>
                </div>
                
                <?php 
                $all_appointments_query = "SELECT a.*, d.spec FROM appointmenttb a 
                                          LEFT JOIN doctb d ON a.doctor = d.username 
                                          WHERE a.email='$email' 
                                          ORDER BY a.appdate DESC";
                $all_appointments_result = mysqli_query($con, $all_appointments_query);
                ?>
                
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Fees (LKR)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($all_appointments_result) > 0): ?>
                                    <?php while($appointment = mysqli_fetch_assoc($all_appointments_result)): ?>
                                        <tr>
                                            <td>#APP<?php echo str_pad($appointment['ID'], 5, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo date('d M Y', strtotime($appointment['appdate'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($appointment['apptime'])); ?></td>
                                            <td>Dr. <?php echo htmlspecialchars($appointment['doctor']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['spec'] ?? 'N/A'); ?></td>
                                            <td><?php echo number_format($appointment['docFees'], 2); ?></td>
                                            <td>
                                                <?php if($appointment['doctorStatus'] == 1): ?>
                                                    <span class="badge badge-success">Confirmed</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No appointments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prescriptions Interface -->
        <div id="prescriptions" class="interface">
            <div class="container-fluid">
                <h3 class="mb-4"><i class="fas fa-prescription mr-2"></i>My Prescriptions</h3>
                
                <div class="row">
                    <?php if(mysqli_num_rows($recent_prescriptions_result) > 0): ?>
                        <?php while($prescription = mysqli_fetch_assoc($recent_prescriptions_result)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">Prescription #PR<?php echo str_pad($prescription['id'], 5, '0', STR_PAD_LEFT); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($prescription['doctor']); ?></p>
                                        <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($prescription['appdate'])); ?></p>
                                        <p><strong>Disease:</strong> <?php echo htmlspecialchars($prescription['disease']); ?></p>
                                        <p><strong>Allergy:</strong> <?php echo htmlspecialchars($prescription['allergy']); ?></p>
                                        <p><strong>Prescription:</strong><br><?php echo nl2br(htmlspecialchars($prescription['prescription'])); ?></p>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">
                                            <i class="fas fa-envelope mr-1"></i> 
                                            Status: <?php echo htmlspecialchars($prescription['emailStatus']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                No prescriptions found in your records.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payments Interface -->
        <div id="payments" class="interface">
            <div class="container-fluid">
                <h3 class="mb-4"><i class="fas fa-credit-card mr-2"></i>Payment History</h3>
                
                <?php 
                $payments_query = "SELECT * FROM paymenttb WHERE national_id='$national_id' ORDER BY pay_date DESC";
                $payments_result = mysqli_query($con, $payments_query);
                ?>
                
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Receipt No</th>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Amount (LKR)</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($payments_result) > 0): ?>
                                    <?php while($payment = mysqli_fetch_assoc($payments_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['receipt_no'] ?? 'PENDING'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($payment['pay_date'])); ?></td>
                                            <td>Dr. <?php echo htmlspecialchars($payment['doctor']); ?></td>
                                            <td><?php echo number_format($payment['fees'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'Not Specified'); ?></td>
                                            <td>
                                                <?php if($payment['pay_status'] == 'Paid'): ?>
                                                    <span class="badge badge-success">Paid</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No payment records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Make Payment</h5>
                            </div>
                            <div class="card-body">
                                <p>Select an appointment to make payment:</p>
                                <?php 
                                $pending_appointments_query = "SELECT * FROM appointmenttb WHERE email='$email' AND doctorStatus=1";
                                $pending_apps_result = mysqli_query($con, $pending_appointments_query);
                                ?>
                                <form action="make_payment.php" method="POST">
                                    <div class="form-group">
                                        <label>Select Appointment:</label>
                                        <select class="form-control" name="appointment_id" required>
                                            <option value="">-- Select Appointment --</option>
                                            <?php while($app = mysqli_fetch_assoc($pending_apps_result)): ?>
                                                <option value="<?php echo $app['ID']; ?>">
                                                    Appointment #<?php echo $app['ID']; ?> - 
                                                    Dr. <?php echo $app['doctor']; ?> - 
                                                    LKR <?php echo $app['docFees']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Payment Method:</label>
                                        <select class="form-control" name="payment_method" required>
                                            <option value="Cash">Cash</option>
                                            <option value="Credit Card">Credit Card</option>
                                            <option value="Debit Card">Debit Card</option>
                                            <option value="Online Banking">Online Banking</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block">
                                        <i class="fas fa-money-check-alt mr-2"></i>Proceed to Payment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctors Interface -->
        <div id="doctors" class="interface">
            <div class="container-fluid">
                <h3 class="mb-4"><i class="fas fa-user-md mr-2"></i>Our Doctors</h3>
                
                <div class="row">
                    <?php 
                    $doctors_query = "SELECT * FROM doctb ORDER BY username";
                    $doctors_result = mysqli_query($con, $doctors_query);
                    ?>
                    
                    <?php while($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-user-md fa-4x text-primary"></i>
                                    </div>
                                    <h5 class="card-title">Dr. <?php echo htmlspecialchars($doctor['username']); ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <?php echo htmlspecialchars($doctor['spec']); ?>
                                    </h6>
                                    <p class="card-text">
                                        <i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($doctor['email']); ?><br>
                                        <i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($doctor['contact']); ?>
                                    </p>
                                    <div class="mt-3">
                                        <span class="badge badge-primary p-2">Fee: LKR <?php echo number_format($doctor['docFees'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <button class="btn btn-outline-primary btn-block" 
                                            onclick="location.href='book_appointment.php?doctor=<?php echo $doctor['username']; ?>'">
                                        <i class="fas fa-calendar-plus mr-2"></i>Book Appointment
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Emergency Interface -->
        <div id="emergency" class="interface">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        <div class="emergency-box mb-4">
                            <h2><i class="fas fa-exclamation-triangle mr-3"></i>EMERGENCY</h2>
                            <h1 class="display-4 font-weight-bold">1990</h1>
                            <p class="lead">Call this number immediately for ambulance or emergency assistance</p>
                            <button class="btn btn-light btn-lg mt-3" onclick="window.location.href='tel:1990'">
                                <i class="fas fa-phone mr-2"></i>Call Now
                            </button>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Send Emergency Alert</h5>
                            </div>
                            <div class="card-body">
                                <?php if(isset($emergency_success)): ?>
                                    <div class="alert alert-success"><?php echo $emergency_success; ?></div>
                                <?php elseif(isset($emergency_error)): ?>
                                    <div class="alert alert-danger"><?php echo $emergency_error; ?></div>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <div class="form-group">
                                        <label>Emergency Contact Number</label>
                                        <input type="tel" class="form-control" name="emergency_contact" 
                                               value="<?php echo htmlspecialchars($patient['contact']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Emergency Message</label>
                                        <textarea class="form-control" name="emergency_message" rows="4" required 
                                                  placeholder="Describe your emergency..."></textarea>
                                    </div>
                                    <button type="submit" name="emergency_call" class="btn btn-danger btn-block">
                                        <i class="fas fa-bell mr-2"></i>Send Emergency Alert
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">Emergency Contacts</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <strong>Ambulance:</strong> 1990 / 011-2345678
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Fire & Rescue:</strong> 101
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Police:</strong> 119 / 011-2444444
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Poison Control:</strong> 011-2691111
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Your Emergency Contact</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname']); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($patient['contact']); ?></p>
                                <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($patient['emergencyContact']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($patient['address']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ward Services Interface -->
        <div id="ward" class="interface">
            <div class="container-fluid">
                <h3 class="mb-4"><i class="fas fa-bed mr-2"></i>Ward Services</h3>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-door-closed mr-2"></i>Room Availability</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $rooms_query = "SELECT * FROM roomtb WHERE status='Available'";
                                $rooms_result = mysqli_query($con, $rooms_query);
                                $available_rooms = mysqli_num_rows($rooms_result);
                                ?>
                                <h2 class="text-center text-success"><?php echo $available_rooms; ?> Rooms Available</h2>
                                <p class="text-center">Check room availability and book your stay</p>
                                <div class="text-center mt-4">
                                    <a href="ward.php" class="btn btn-outline-success">
                                        <i class="fas fa-search mr-2"></i>View Available Rooms
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-utensils mr-2"></i>Food Services</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $food_query = "SELECT * FROM foodtb";
                                $food_result = mysqli_query($con, $food_query);
                                $food_items = mysqli_num_rows($food_result);
                                ?>
                                <h2 class="text-center text-warning"><?php echo $food_items; ?> Food Items</h2>
                                <p class="text-center">View and order hospital food services</p>
                                <div class="text-center mt-4">
                                    <a href="food.php" class="btn btn-outline-warning">
                                        <i class="fas fa-concierge-bell mr-2"></i>View Food Menu
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile & Settings Interface -->
        <div id="profile" class="interface">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-user-circle mr-2"></i>Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="user-avatar mx-auto" style="width: 80px; height: 80px; font-size: 36px;">
                                        <?php echo strtoupper(substr($patient['fname'], 0, 1)); ?>
                                    </div>
                                    <h4 class="mt-3"><?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname']); ?></h4>
                                    <p class="text-muted">Patient ID: <?php echo htmlspecialchars($patient['pid']); ?></p>
                                </div>
                                
                                <div class="patient-info">
                                    <p><strong><i class="fas fa-envelope mr-2"></i>Email:</strong><br>
                                       <?php echo htmlspecialchars($patient['email']); ?></p>
                                    <p><strong><i class="fas fa-phone mr-2"></i>Contact:</strong><br>
                                       <?php echo htmlspecialchars($patient['contact']); ?></p>
                                    <p><strong><i class="fas fa-id-card mr-2"></i>National ID:</strong><br>
                                       <?php echo htmlspecialchars($patient['national_id']); ?></p>
                                    <p><strong><i class="fas fa-birthday-cake mr-2"></i>Date of Birth:</strong><br>
                                       <?php echo date('d M Y', strtotime($patient['dob'])); ?></p>
                                    <p><strong><i class="fas fa-venus-mars mr-2"></i>Gender:</strong><br>
                                       <?php echo htmlspecialchars($patient['gender']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-key mr-2"></i>Change Password</h5>
                            </div>
                            <div class="card-body">
                                <?php if(isset($password_success)): ?>
                                    <div class="alert alert-success"><?php echo $password_success; ?></div>
                                <?php elseif(isset($password_error)): ?>
                                    <div class="alert alert-danger"><?php echo $password_error; ?></div>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <div class="form-group">
                                        <label>Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" class="form-control" name="new_password" required 
                                               minlength="6">
                                        <small class="form-text text-muted">Password must be at least 6 characters long</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-primary btn-block">
                                        <i class="fas fa-save mr-2"></i>Update Password
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Account Activity</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <i class="fas fa-calendar-plus text-primary mr-2"></i>
                                        <strong>Registration Date:</strong> 
                                        <?php echo date('d M Y h:i A', strtotime($patient['reg_date'])); ?>
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-calendar-check text-success mr-2"></i>
                                        <strong>Last Appointment:</strong> 
                                        <?php 
                                            $last_app_query = "SELECT MAX(appdate) as last_date FROM appointmenttb WHERE email='$email'";
                                            $last_app_result = mysqli_query($con, $last_app_query);
                                            $last_app = mysqli_fetch_assoc($last_app_result);
                                            echo $last_app['last_date'] ? date('d M Y', strtotime($last_app['last_date'])) : 'No appointments yet';
                                        ?>
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-file-invoice-dollar text-info mr-2"></i>
                                        <strong>Total Payments Made:</strong> 
                                        <?php 
                                            $total_payments_query = "SELECT SUM(fees) as total FROM paymenttb WHERE national_id='$national_id' AND pay_status='Paid'";
                                            $total_payments_result = mysqli_query($con, $total_payments_query);
                                            $total_payments = mysqli_fetch_assoc($total_payments_result);
                                            echo 'LKR ' . number_format($total_payments['total'] ?? 0, 2);
                                        ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle interfaces
        function showInterface(id){
            $('.interface').removeClass('active');
            $('#' + id).addClass('active');
            
            // Update active menu item
            $('.sidebar ul li').removeClass('active');
            $('.sidebar ul li[data-target="' + id + '"]').addClass('active');
        }

        // Sidebar navigation
        $(document).ready(function(){
            $('.sidebar ul li[data-target]').click(function(e){
                if(!$(e.target).is('a')) {
                    const target = $(this).data('target');
                    showInterface(target);
                }
            });

            // Handle form submissions
            $('#passwordForm').submit(function(e){
                e.preventDefault();
                alert("Password updated successfully!");
                this.reset();
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function(){
                $('.alert').fadeOut('slow');
            }, 5000);
        });

        // Logout confirmation
        function confirmLogout(){
            if(confirm("Are you sure you want to logout?")){
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>
<?php mysqli_close($con); ?>