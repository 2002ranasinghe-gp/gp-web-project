<?php
include('func1.php');

// Fetch counts
$count_doctors = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM doctb"))[0];
$count_patients = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM patreg"))[0];
$count_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb"))[0];
$count_payments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM paymenttb"))[0];
$today_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb WHERE appdate = CURDATE()"))[0];
$pending_payments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM paymenttb WHERE pay_status = 'Pending'"))[0];
$total_prescriptions = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM prestb"))[0];
$available_rooms = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM roomtb WHERE status = 'Available'"))[0];

// Fetch data
$doctors = mysqli_query($con, "SELECT id, username, email, spec, contact, docFees FROM doctb ORDER BY username");
$patients = mysqli_query($con, "SELECT pid, fname, lname, email, contact, gender, dob, national_id FROM patreg ORDER BY pid DESC");
$appointments = mysqli_query($con, "SELECT * FROM appointmenttb ORDER BY appdate DESC, apptime DESC");
$payments = mysqli_query($con, "SELECT * FROM paymenttb ORDER BY pay_date DESC");
$prescriptions = mysqli_query($con, "SELECT * FROM prestb ORDER BY appdate DESC");
$schedules = mysqli_query($con, "SELECT * FROM scheduletb ORDER BY day, shift");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Doctor Panel - Healthcare Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
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
    .status-paid {
      background: #d4edda;
      color: #155724;
    }
    .status-unpaid {
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
      background-color: rgba(0, 119, 182, 0.05);
    }
    
    .action-btn {
      margin: 2px;
      padding: 5px 10px;
      font-size: 12px;
      border-radius: 5px;
    }
    
    .user-avatar-img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid white;
    }

    /* Hidden class for tab switching */
    .hidden {
      display: none !important;
    }

    /* Active link styling */
    .nav-link.active {
      background: rgba(255,255,255,0.1);
      border-left: 4px solid #fff;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <img class="logo" src="images/medical-logo.jpg" alt="Hospital Logo">
    <h4>Doctor Portal</h4>
    <ul>
      <li class="nav-link active" onclick="showSection(event, 'dash-tab')">
        <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
      </li>
      <li class="nav-link" onclick="showSection(event, 'doctors-tab')">
        <i class="fas fa-user-md"></i> <span>Doctors</span>
      </li>
      <li class="nav-link" onclick="showSection(event, 'patients-tab')">
        <i class="fas fa-user-injured"></i> <span>Patients</span>
      </li>
      <li class="nav-link" onclick="showSection(event, 'appointments-tab')">
        <i class="fas fa-calendar-check"></i> <span>Appointments</span>
      </li>
      <li class="nav-link" onclick="showSection(event, 'prescriptions-tab')">
        <i class="fas fa-prescription"></i> <span>Prescriptions</span>
      </li>
      <li class="nav-link" onclick="showSection(event, 'payments-tab')">
        <i class="fas fa-credit-card"></i> <span>Payments</span>
      </li>
      <li class="nav-link" onclick="showSection(event, 'schedules-tab')">
        <i class="fas fa-clock"></i> <span>Schedules</span>
      </li>
      <li>
        <a href="logout.php" style="color: white; text-decoration: none; display: block; padding: 12px 20px;">
          <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
      </li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
      <div class="brand">🏥 Healthcare Hospital - Doctor Panel</div>
      <div class="user-info">
        <div class="user-avatar">
          <i class="fas fa-user-md"></i>
        </div>
        <div>
          <strong>Doctor</strong><br>
          <small>Medical Practitioner</small>
        </div>
      </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
      <!-- Dashboard Tab -->
      <div id="dash-tab">
        <!-- Welcome Section -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="alert alert-primary" role="alert">
              <h4 class="alert-heading">Welcome, Doctor!</h4>
              <p class="mb-0">Manage your appointments, prescriptions, and patient records from this doctor dashboard.</p>
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
                      Total Patients
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                      <?php echo $count_patients; ?>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-user-injured stats-icon text-primary"></i>
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
                      Today's Appointments
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                      <?php echo $today_appointments; ?>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-calendar-day stats-icon text-success"></i>
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
                    <i class="fas fa-credit-card stats-icon text-warning"></i>
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
                      Total Prescriptions
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                      <?php echo $total_prescriptions; ?>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-prescription stats-icon text-info"></i>
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
            <div class="quick-action-card" onclick="showSection(event, 'patients-tab')">
              <i class="fas fa-user-injured"></i>
              <h5>View Patients</h5>
              <p>Check patient records</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="quick-action-card" onclick="showSection(event, 'appointments-tab')">
              <i class="fas fa-calendar-plus"></i>
              <h5>Appointments</h5>
              <p>Manage appointments</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="quick-action-card" onclick="showSection(event, 'prescriptions-tab')">
              <i class="fas fa-prescription"></i>
              <h5>Prescriptions</h5>
              <p>Write prescriptions</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="quick-action-card" onclick="showSection(event, 'schedules-tab')">
              <i class="fas fa-clock"></i>
              <h5>Schedule</h5>
              <p>View your schedule</p>
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
                    <tr>
                      <td>Today</td>
                      <td>Patients</td>
                      <td>Total <?php echo $count_patients; ?> patients</td>
                      <td><span class="badge badge-success">Active</span></td>
                    </tr>
                    <tr>
                      <td>Today</td>
                      <td>Appointments</td>
                      <td><?php echo $today_appointments; ?> appointments today</td>
                      <td><span class="badge badge-info">Scheduled</span></td>
                    </tr>
                    <tr>
                      <td>Today</td>
                      <td>Prescriptions</td>
                      <td><?php echo $total_prescriptions; ?> prescriptions issued</td>
                      <td><span class="badge badge-warning">Active</span></td>
                    </tr>
                    <tr>
                      <td>Now</td>
                      <td>Doctor Panel</td>
                      <td>Logged in as Doctor</td>
                      <td><span class="badge badge-primary">Active</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Doctors Tab -->
      <div id="doctors-tab" class="hidden">
        <h3 class="mb-4"><i class="fas fa-user-md mr-2"></i>Doctors</h3>
        
        <div class="search-container">
          <div class="search-bar">
            <input type="text" class="form-control" id="doctor-search" placeholder="Search doctors by name, specialization, or contact..." onkeyup="filterTable('doctor-search', 'doctors-table-body')">
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
                </tr>
              </thead>
              <tbody id="doctors-table-body">
                <?php while($row = mysqli_fetch_assoc($doctors)) { ?>
                <tr>
                  <td><strong><?php echo $row['id']; ?></strong></td>
                  <td><?php echo htmlspecialchars($row['username']); ?></td>
                  <td><?php echo htmlspecialchars($row['spec']); ?></td>
                  <td><?php echo htmlspecialchars($row['email']); ?></td>
                  <td><?php echo htmlspecialchars($row['contact']); ?></td>
                  <td>Rs. <?php echo number_format($row['docFees'], 2); ?></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Patients Tab -->
      <div id="patients-tab" class="hidden">
        <h3 class="mb-4"><i class="fas fa-user-injured mr-2"></i>Patients</h3>
        
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
                <?php while($row = mysqli_fetch_assoc($patients)) { ?>
                <tr>
                  <td><strong><?php echo $row['pid']; ?></strong></td>
                  <td><?php echo htmlspecialchars($row['fname']); ?></td>
                  <td><?php echo htmlspecialchars($row['lname']); ?></td>
                  <td><?php echo htmlspecialchars($row['gender']); ?></td>
                  <td><?php echo htmlspecialchars($row['email']); ?></td>
                  <td><?php echo htmlspecialchars($row['contact']); ?></td>
                  <td><?php echo $row['dob'] ? date('Y-m-d', strtotime($row['dob'])) : 'N/A'; ?></td>
                  <td><span class="badge badge-info"><?php echo $row['national_id']; ?></span></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Appointments Tab -->
      <div id="appointments-tab" class="hidden">
        <h3 class="mb-4"><i class="fas fa-calendar-check mr-2"></i>Appointments</h3>
        
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
                  <th>Doctor</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Fees (Rs.)</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="appointments-table-body">
                <?php while($row = mysqli_fetch_assoc($appointments)) { 
                  $status = ($row['userStatus']==1 && $row['doctorStatus']==1) ? 'Active' : (($row['userStatus']==0)?'Cancelled by Patient':'Cancelled by Doctor');
                ?>
                <tr>
                  <td><?php echo $row['ID']; ?></td>
                  <td><?php echo htmlspecialchars($row['fname'].' '.$row['lname']); ?></td>
                  <td><?php echo htmlspecialchars($row['doctor']); ?></td>
                  <td><?php echo htmlspecialchars($row['appdate']); ?></td>
                  <td><?php echo date('h:i A', strtotime($row['apptime'])); ?></td>
                  <td>Rs. <?php echo number_format($row['docFees'], 2); ?></td>
                  <td>
                    <?php if($status == 'Active'): ?>
                      <span class="status-badge status-active">Active</span>
                    <?php else: ?>
                      <span class="status-badge status-cancelled"><?php echo $status; ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Prescriptions Tab -->
      <div id="prescriptions-tab" class="hidden">
        <h3 class="mb-4"><i class="fas fa-prescription mr-2"></i>Prescriptions</h3>
        
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
                </tr>
              </thead>
              <tbody id="prescriptions-table-body">
                <?php while($row = mysqli_fetch_assoc($prescriptions)) { ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td><?php echo htmlspecialchars($row['doctor']); ?></td>
                  <td><?php echo htmlspecialchars($row['fname'].' '.$row['lname']); ?></td>
                  <td><?php echo htmlspecialchars($row['appdate']); ?></td>
                  <td><?php echo htmlspecialchars($row['disease']); ?></td>
                  <td><?php echo substr($row['prescription'], 0, 50) . '...'; ?></td>
                  <td><?php echo htmlspecialchars($row['emailStatus']); ?></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Payments Tab -->
      <div id="payments-tab" class="hidden">
        <h3 class="mb-4"><i class="fas fa-credit-card mr-2"></i>Payments</h3>
        
        <div class="search-container">
          <div class="search-bar">
            <input type="text" class="form-control" id="payment-search" placeholder="Search payments by patient name or doctor..." onkeyup="filterTable('payment-search', 'payments-table-body')">
            <i class="fas fa-search search-icon"></i>
          </div>
        </div>
        
        <div class="data-table">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Payment ID</th>
                  <th>Patient ID</th>
                  <th>Appointment ID</th>
                  <th>Patient Name</th>
                  <th>Doctor</th>
                  <th>Amount (Rs.)</th>
                  <th>Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="payments-table-body">
                <?php while($row = mysqli_fetch_assoc($payments)) { ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td><?php echo $row['pid']; ?></td>
                  <td><?php echo $row['app_id'] ?? 'N/A'; ?></td>
                  <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['doctor']); ?></td>
                  <td>Rs. <?php echo number_format($row['fees'], 2); ?></td>
                  <td><?php echo htmlspecialchars($row['pay_date']); ?></td>
                  <td>
                    <?php if($row['pay_status'] == 'Paid'): ?>
                      <span class="status-badge status-paid">Paid</span>
                    <?php else: ?>
                      <span class="status-badge status-unpaid">Pending</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Schedules Tab -->
      <div id="schedules-tab" class="hidden">
        <h3 class="mb-4"><i class="fas fa-clock mr-2"></i>Doctor Schedules</h3>
        
        <div class="search-container">
          <div class="search-bar">
            <input type="text" class="form-control" id="schedule-search" placeholder="Search schedules by staff name, role, or day..." onkeyup="filterTable('schedule-search', 'schedules-table-body')">
            <i class="fas fa-search search-icon"></i>
          </div>
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
                </tr>
              </thead>
              <tbody id="schedules-table-body">
                <?php while($row = mysqli_fetch_assoc($schedules)) { ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td><strong><?php echo $row['staff_id']; ?></strong></td>
                  <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['role']); ?></td>
                  <td><?php echo htmlspecialchars($row['day']); ?></td>
                  <td><?php echo htmlspecialchars($row['shift']); ?></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Function to show section
    function showSection(event, id) {
      // Hide all sections
      document.querySelectorAll('.tab-content > div').forEach(div => {
        div.classList.add('hidden');
      });
      
      // Show selected section
      document.getElementById(id).classList.remove('hidden');
      
      // Update active state in sidebar
      document.querySelectorAll('.sidebar ul li').forEach(li => {
        li.classList.remove('active');
      });
      
      // Add active class to clicked item
      event.currentTarget.classList.add('active');
    }

    // Function to filter table rows
    function filterTable(searchInputId, tableBodyId) {
      const input = document.getElementById(searchInputId).value.toLowerCase();
      const rows = document.querySelectorAll(`#${tableBodyId} tr`);
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.indexOf(input) > -1 ? '' : 'none';
      });
    }

    // Set up initial state
    document.addEventListener('DOMContentLoaded', function() {
      // Auto-hide alerts after 5 seconds
      setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
          alert.style.transition = 'opacity 0.5s';
          alert.style.opacity = '0';
          setTimeout(() => alert.remove(), 500);
        });
      }, 5000);
    });
  </script>
</body>
</html>