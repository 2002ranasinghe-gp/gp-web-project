<?php
// func1.php

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "hospital_management";

// Create connection
$con = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8
mysqli_set_charset($con, "utf8");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<?php
session_start();
include('func1.php');

// Check if user is logged in
if(!isset($_SESSION['doctor'])) {
    header("location:login.php");
    exit();
}

// Get current doctor's name
$current_doctor = $_SESSION['doctor'];

// Get doctor's ID
$doctor_query = mysqli_query($con, "SELECT id, spec FROM doctb WHERE username='$current_doctor'");
$doctor_data = mysqli_fetch_assoc($doctor_query);
$doctor_id = $doctor_data['id'];
$doctor_spec = $doctor_data['spec'];

// Get current time and date
$current_time = date('H:i:s');
$current_date = date('Y-m-d');
$current_day = date('l');

// Get doctor's statistics
// Total patients for this doctor
$count_patients = mysqli_fetch_array(mysqli_query($con, 
    "SELECT COUNT(DISTINCT pid) FROM appointmenttb WHERE doctor='$current_doctor'"))[0];

// Total appointments for this doctor
$count_appointments = mysqli_fetch_array(mysqli_query($con, 
    "SELECT COUNT(*) FROM appointmenttb WHERE doctor='$current_doctor'"))[0];

// Today's appointments
$count_today_appointments = mysqli_fetch_array(mysqli_query($con, 
    "SELECT COUNT(*) FROM appointmenttb WHERE doctor='$current_doctor' AND appdate='$current_date'"))[0];

// Total payments for this doctor
$count_payments = mysqli_fetch_array(mysqli_query($con, 
    "SELECT COUNT(*) FROM paymenttb WHERE doctor='$current_doctor'"))[0];

// Get doctor's patients
$patients_query = mysqli_query($con, 
    "SELECT DISTINCT p.* 
     FROM patreg p
     INNER JOIN appointmenttb a ON p.pid = a.pid
     WHERE a.doctor='$current_doctor'
     ORDER BY p.reg_date DESC");

// Get doctor's appointments
$appointments_query = mysqli_query($con, 
    "SELECT a.*, p.fname, p.lname, p.contact, p.national_id
     FROM appointmenttb a
     LEFT JOIN patreg p ON a.pid = p.pid
     WHERE a.doctor='$current_doctor'
     ORDER BY a.appdate DESC, a.apptime DESC");

// Get today's appointments for this doctor
$today_appointments_query = mysqli_query($con,
    "SELECT a.*, p.fname, p.lname, p.contact
     FROM appointmenttb a
     LEFT JOIN patreg p ON a.pid = a.pid
     WHERE a.doctor='$current_doctor' AND a.appdate='$current_date'
     ORDER BY a.apptime");

// Get doctor's payments
$payments_query = mysqli_query($con,
    "SELECT p.*, pat.fname, pat.lname
     FROM paymenttb p
     LEFT JOIN patreg pat ON p.pid = pat.pid
     WHERE p.doctor='$current_doctor'
     ORDER BY p.pay_date DESC");

// Handle search
$search_results = [];
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($con, $_GET['search']);
    
    // Search in patients
    $search_patients = mysqli_query($con,
        "SELECT p.* FROM patreg p
         INNER JOIN appointmenttb a ON p.pid = a.pid
         WHERE a.doctor='$current_doctor' AND 
         (p.fname LIKE '%$search_term%' OR 
          p.lname LIKE '%$search_term%' OR 
          p.national_id LIKE '%$search_term%' OR
          p.contact LIKE '%$search_term%')");
    
    // Search in appointments
    $search_appointments = mysqli_query($con,
        "SELECT a.*, p.fname, p.lname FROM appointmenttb a
         LEFT JOIN patreg p ON a.pid = p.pid
         WHERE a.doctor='$current_doctor' AND 
         (p.fname LIKE '%$search_term%' OR 
          p.lname LIKE '%$search_term%' OR 
          a.appointmentStatus LIKE '%$search_term%')");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Panel Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* === VARIABLES === */
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #3498db;
      --accent-color: #e74c3c;
      --light-color: #ecf0f1;
      --dark-color: #2c3e50;
      --success-color: #27ae60;
      --warning-color: #f39c12;
      --info-color: #2980b9;
    }

    /* === GENERAL STYLES === */
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f7fa;
      color: #333;
      margin: 0;
      padding: 0;
    }

    /* === SIDEBAR === */
    .sidebar {
      width: 250px;
      height: 100vh;
      background: linear-gradient(180deg, var(--primary-color), #34495e);
      position: fixed;
      left: 0;
      top: 0;
      z-index: 1000;
      box-shadow: 3px 0 15px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }

    .sidebar.collapsed {
      width: 70px;
    }

    .sidebar-header {
      padding: 25px 20px;
      text-align: center;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar-header h3 {
      color: white;
      margin: 0;
      font-size: 1.5rem;
      font-weight: 600;
    }

    .sidebar-header p {
      color: #bdc3c7;
      margin: 5px 0 0 0;
      font-size: 0.9rem;
    }

    .nav-links {
      padding: 20px 0;
    }

    .nav-item {
      display: flex;
      align-items: center;
      padding: 15px 25px;
      color: #ecf0f1;
      text-decoration: none;
      transition: all 0.3s;
      border-left: 4px solid transparent;
    }

    .nav-item:hover, .nav-item.active {
      background: rgba(255,255,255,0.1);
      border-left-color: var(--secondary-color);
      color: white;
    }

    .nav-item i {
      width: 20px;
      margin-right: 15px;
      font-size: 1.2rem;
    }

    .nav-text {
      transition: opacity 0.3s;
    }

    .sidebar.collapsed .nav-text {
      display: none;
    }

    .logout-btn {
      position: absolute;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
      width: 80%;
      background: var(--accent-color);
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: all 0.3s;
    }

    .logout-btn:hover {
      background: #c0392b;
      transform: translateX(-50%) translateY(-2px);
    }

    /* === MAIN CONTENT === */
    .main-content {
      margin-left: 250px;
      padding: 20px;
      transition: all 0.3s ease;
    }

    .sidebar.collapsed + .main-content {
      margin-left: 70px;
    }

    /* === HEADER === */
    .top-header {
      background: white;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      margin-bottom: 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header-left h1 {
      color: var(--primary-color);
      margin: 0;
      font-size: 1.8rem;
      font-weight: 600;
    }

    .header-left p {
      color: #7f8c8d;
      margin: 5px 0 0 0;
      font-size: 0.9rem;
    }

    /* === WEATHER & TIME WIDGET === */
    .weather-widget {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 15px 25px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      gap: 15px;
      min-width: 250px;
    }

    .weather-icon {
      font-size: 2.5rem;
    }

    .weather-info h3 {
      margin: 0;
      font-size: 1.8rem;
      font-weight: 600;
    }

    .weather-info p {
      margin: 5px 0 0 0;
      font-size: 0.9rem;
      opacity: 0.9;
    }

    /* === SEARCH BAR === */
    .search-container {
      position: relative;
      width: 300px;
    }

    .search-input {
      width: 100%;
      padding: 12px 20px 12px 45px;
      border: 2px solid #e0e0e0;
      border-radius: 25px;
      font-size: 14px;
      transition: all 0.3s;
      background: #f8f9fa;
    }

    .search-input:focus {
      outline: none;
      border-color: var(--secondary-color);
      background: white;
      box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #7f8c8d;
      font-size: 16px;
    }

    /* === DASHBOARD CARDS === */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      display: flex;
      align-items: center;
      gap: 20px;
      transition: all 0.3s;
      border-left: 4px solid var(--secondary-color);
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: white;
    }

    .icon-patients { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .icon-appointments { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .icon-today { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .icon-payments { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

    .stat-info h3 {
      margin: 0;
      font-size: 28px;
      color: var(--primary-color);
      font-weight: 600;
    }

    .stat-info p {
      margin: 5px 0 0 0;
      color: #7f8c8d;
      font-size: 14px;
    }

    /* === HOSPITAL POSTER === */
    .dashboard-banner {
      background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(44, 62, 80, 0.9)),
                  url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
      background-size: cover;
      background-position: center;
      height: 200px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 30px 0;
      position: relative;
      overflow: hidden;
    }

    .banner-content {
      text-align: center;
      color: white;
      z-index: 2;
    }

    .banner-content h2 {
      font-size: 2.5rem;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .banner-content p {
      font-size: 1.1rem;
      opacity: 0.9;
    }

    /* === TABLES === */
    .section-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      margin-bottom: 30px;
      overflow: hidden;
    }

    .section-header {
      padding: 20px 25px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .section-header h3 {
      margin: 0;
      color: var(--primary-color);
      font-size: 1.3rem;
      font-weight: 600;
    }

    .section-body {
      padding: 25px;
    }

    .table-responsive {
      overflow-x: auto;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
    }

    .table th {
      background: #f8f9fa;
      padding: 15px;
      text-align: left;
      color: var(--primary-color);
      font-weight: 600;
      border-bottom: 2px solid #eee;
    }

    .table td {
      padding: 15px;
      border-bottom: 1px solid #eee;
      vertical-align: middle;
    }

    .table tr:hover {
      background: #f9f9f9;
    }

    /* === STATUS BADGES === */
    .badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .badge-success {
      background: #d4edda;
      color: #155724;
    }

    .badge-warning {
      background: #fff3cd;
      color: #856404;
    }

    .badge-danger {
      background: #f8d7da;
      color: #721c24;
    }

    .badge-info {
      background: #cce5ff;
      color: #004085;
    }

    /* === TODAY'S APPOINTMENTS === */
    .today-appointments {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 15px;
      margin-top: 20px;
    }

    .appointment-card {
      background: white;
      border-radius: 10px;
      padding: 15px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.08);
      border-left: 4px solid var(--secondary-color);
      transition: all 0.3s;
    }

    .appointment-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .patient-name {
      font-weight: 600;
      color: var(--primary-color);
      margin-bottom: 5px;
    }

    .appointment-time {
      color: #7f8c8d;
      font-size: 0.9rem;
    }

    /* === FILTER BUTTONS === */
    .filter-buttons {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .filter-btn {
      padding: 8px 16px;
      border: 1px solid #ddd;
      background: white;
      border-radius: 20px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .filter-btn.active {
      background: var(--secondary-color);
      color: white;
      border-color: var(--secondary-color);
    }

    .filter-btn:hover:not(.active) {
      background: #f8f9fa;
    }

    /* === ACTION BUTTONS === */
    .action-buttons {
      display: flex;
      gap: 8px;
    }

    .btn-sm {
      padding: 6px 12px;
      border: none;
      border-radius: 6px;
      font-size: 12px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: all 0.3s;
    }

    .btn-primary {
      background: var(--secondary-color);
      color: white;
    }

    .btn-primary:hover {
      background: #2980b9;
    }

    .btn-success {
      background: var(--success-color);
      color: white;
    }

    .btn-success:hover {
      background: #219653;
    }

    .btn-warning {
      background: var(--warning-color);
      color: white;
    }

    .btn-warning:hover {
      background: #e67e22;
    }

    .btn-danger {
      background: var(--accent-color);
      color: white;
    }

    .btn-danger:hover {
      background: #c0392b;
    }

    /* === RESPONSIVE DESIGN === */
    @media (max-width: 1200px) {
      .sidebar {
        width: 70px;
      }
      
      .sidebar-header h3, 
      .sidebar-header p,
      .nav-text {
        display: none;
      }
      
      .main-content {
        margin-left: 70px;
      }
      
      .logout-btn span {
        display: none;
      }
    }

    @media (max-width: 768px) {
      .top-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
      }
      
      .weather-widget {
        width: 100%;
        justify-content: center;
      }
      
      .search-container {
        width: 100%;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .action-buttons {
        flex-direction: column;
      }
    }

    /* === ANIMATIONS === */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .fade-in {
      animation: fadeIn 0.5s ease-out;
    }

    /* === COUNTDOWN TIMER === */
    .countdown-timer {
      background: linear-gradient(135deg, #2c3e50, #34495e);
      color: white;
      padding: 15px;
      border-radius: 10px;
      text-align: center;
      margin: 20px 0;
    }

    .timer-display {
      font-size: 2rem;
      font-weight: bold;
      font-family: 'Courier New', monospace;
      margin-bottom: 10px;
    }

    .timer-label {
      font-size: 0.9rem;
      opacity: 0.8;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <h3>Doctor Panel</h3>
      <p>Dr. <?php echo htmlspecialchars($current_doctor); ?></p>
      <p style="font-size: 0.8rem; color: #95a5a6;"><?php echo $doctor_spec; ?></p>
    </div>
    
    <div class="nav-links">
      <a href="#dashboard" class="nav-item active" onclick="showSection('dashboard')">
        <i class="fas fa-tachometer-alt"></i>
        <span class="nav-text">Dashboard</span>
      </a>
      
      <a href="#patients" class="nav-item" onclick="showSection('patients')">
        <i class="fas fa-users"></i>
        <span class="nav-text">My Patients</span>
        <span class="badge badge-info" style="margin-left: auto;"><?php echo $count_patients; ?></span>
      </a>
      
      <a href="#appointments" class="nav-item" onclick="showSection('appointments')">
        <i class="fas fa-calendar-check"></i>
        <span class="nav-text">Appointments</span>
        <span class="badge badge-warning" style="margin-left: auto;"><?php echo $count_appointments; ?></span>
      </a>
      
      <a href="#payments" class="nav-item" onclick="showSection('payments')">
        <i class="fas fa-credit-card"></i>
        <span class="nav-text">Payments</span>
        <span class="badge badge-success" style="margin-left: auto;"><?php echo $count_payments; ?></span>
      </a>
      
      <a href="#prescriptions" class="nav-item" onclick="showSection('prescriptions')">
        <i class="fas fa-prescription"></i>
        <span class="nav-text">Prescriptions</span>
      </a>
    </div>
    
    <button class="logout-btn" onclick="logout()">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </button>
  </div>

  <!-- Main Content -->
  <div class="main-content" id="mainContent">
    
    <!-- Top Header -->
    <div class="top-header">
      <div class="header-left">
        <h1 id="pageTitle">Dashboard Overview</h1>
        <p>Welcome back, Dr. <?php echo htmlspecialchars($current_doctor); ?> | <?php echo date('F j, Y'); ?></p>
      </div>
      
      <div style="display: flex; align-items: center; gap: 20px;">
        <!-- Weather Widget -->
        <div class="weather-widget">
          <div class="weather-icon">
            <i class="fas fa-sun"></i>
          </div>
          <div class="weather-info">
            <h3 id="temperature">31°C</h3>
            <p id="weatherCondition">Mostly sunny</p>
          </div>
        </div>
        
        <!-- Search Bar -->
        <div class="search-container">
          <i class="fas fa-search search-icon"></i>
          <form method="GET" action="" id="searchForm">
            <input type="text" 
                   name="search" 
                   class="search-input" 
                   placeholder="Search patients, appointments..." 
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
          </form>
        </div>
      </div>
    </div>

    <!-- Dashboard Section -->
    <section id="dashboard" class="fade-in">
      <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
        <!-- Search Results -->
        <div class="section-card">
          <div class="section-header">
            <h3>Search Results for "<?php echo htmlspecialchars($_GET['search']); ?>"</h3>
          </div>
          <div class="section-body">
            <!-- Search results will be shown here -->
            <p>Search functionality would display results here...</p>
          </div>
        </div>
      <?php endif; ?>

      <!-- Countdown Timer -->
      <div class="countdown-timer">
        <div class="timer-display" id="countdown">08:00:00</div>
        <div class="timer-label">Next Appointment Countdown</div>
      </div>

      <!-- Hospital Poster Banner -->
      <div class="dashboard-banner">
        <div class="banner-content">
          <h2>Excellence in Healthcare</h2>
          <p>Providing quality medical care with compassion and expertise</p>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon icon-patients">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-info">
            <h3><?php echo $count_patients; ?></h3>
            <p>Total Patients</p>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon icon-appointments">
            <i class="fas fa-calendar-check"></i>
          </div>
          <div class="stat-info">
            <h3><?php echo $count_appointments; ?></h3>
            <p>Total Appointments</p>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon icon-today">
            <i class="fas fa-calendar-day"></i>
          </div>
          <div class="stat-info">
            <h3><?php echo $count_today_appointments; ?></h3>
            <p>Today's Appointments</p>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon icon-payments">
            <i class="fas fa-money-bill-wave"></i>
          </div>
          <div class="stat-info">
            <h3><?php echo $count_payments; ?></h3>
            <p>Total Payments</p>
          </div>
        </div>
      </div>

      <!-- Today's Appointments -->
      <div class="section-card">
        <div class="section-header">
          <h3><i class="fas fa-calendar-day"></i> Today's Appointments</h3>
          <span class="badge badge-info"><?php echo $count_today_appointments; ?> Appointments</span>
        </div>
        <div class="section-body">
          <?php if(mysqli_num_rows($today_appointments_query) > 0): ?>
            <div class="today-appointments">
              <?php while($appointment = mysqli_fetch_assoc($today_appointments_query)): ?>
                <div class="appointment-card">
                  <div class="patient-name">
                    <?php echo htmlspecialchars($appointment['fname'] . ' ' . $appointment['lname']); ?>
                  </div>
                  <div class="appointment-time">
                    <i class="far fa-clock"></i> 
                    <?php echo date('h:i A', strtotime($appointment['apptime'])); ?>
                  </div>
                  <div style="margin-top: 10px; font-size: 0.85rem; color: #666;">
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($appointment['contact']); ?>
                  </div>
                  <?php if($appointment['appointmentStatus'] == 'active'): ?>
                    <span class="badge badge-success" style="margin-top: 10px; display: inline-block;">Confirmed</span>
                  <?php else: ?>
                    <span class="badge badge-warning" style="margin-top: 10px; display: inline-block;">Pending</span>
                  <?php endif; ?>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <p style="text-align: center; color: #7f8c8d; padding: 20px;">
              <i class="fas fa-calendar-times fa-2x" style="margin-bottom: 10px; display: block;"></i>
              No appointments scheduled for today
            </p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Patients Section (Hidden by default) -->
    <section id="patients" style="display: none;">
      <div class="section-card">
        <div class="section-header">
          <h3><i class="fas fa-users"></i> My Patients</h3>
          <div class="filter-buttons">
            <button class="filter-btn active" onclick="filterPatients('all')">All</button>
            <button class="filter-btn" onclick="filterPatients('recent')">Recent</button>
            <button class="filter-btn" onclick="filterPatients('active')">Active</button>
          </div>
        </div>
        <div class="section-body">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Patient ID</th>
                  <th>Name</th>
                  <th>National ID</th>
                  <th>Contact</th>
                  <th>Last Visit</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(mysqli_num_rows($patients_query) > 0): ?>
                  <?php mysqli_data_seek($patients_query, 0); ?>
                  <?php while($patient = mysqli_fetch_assoc($patients_query)): ?>
                  <tr>
                    <td>PAT<?php echo str_pad($patient['pid'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td>
                      <strong><?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname']); ?></strong><br>
                      <small style="color: #666;"><?php echo htmlspecialchars($patient['email']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($patient['national_id']); ?></td>
                    <td><?php echo htmlspecialchars($patient['contact']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($patient['reg_date'])); ?></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-sm btn-primary" onclick="viewPatient(<?php echo $patient['pid']; ?>)">
                          <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn-sm btn-success" onclick="addPrescription(<?php echo $patient['pid']; ?>)">
                          <i class="fas fa-prescription"></i> Rx
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #7f8c8d;">
                      <i class="fas fa-user-slash fa-2x" style="margin-bottom: 10px; display: block;"></i>
                      No patients found
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- Appointments Section (Hidden by default) -->
    <section id="appointments" style="display: none;">
      <div class="section-card">
        <div class="section-header">
          <h3><i class="fas fa-calendar-check"></i> Appointments</h3>
          <div class="filter-buttons">
            <button class="filter-btn active" onclick="filterAppointments('all')">All</button>
            <button class="filter-btn" onclick="filterAppointments('today')">Today</button>
            <button class="filter-btn" onclick="filterAppointments('upcoming')">Upcoming</button>
            <button class="filter-btn" onclick="filterAppointments('past')">Past</button>
          </div>
        </div>
        <div class="section-body">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Appointment ID</th>
                  <th>Patient</th>
                  <th>Date & Time</th>
                  <th>Status</th>
                  <th>Fee (LKR)</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="appointmentsTable">
                <?php if(mysqli_num_rows($appointments_query) > 0): ?>
                  <?php mysqli_data_seek($appointments_query, 0); ?>
                  <?php while($appointment = mysqli_fetch_assoc($appointments_query)): 
                    $appointment_date = new DateTime($appointment['appdate']);
                    $today = new DateTime();
                    $is_today = $appointment_date->format('Y-m-d') == $today->format('Y-m-d');
                    $is_past = $appointment_date < $today;
                    
                    $status_class = '';
                    $status_text = '';
                    
                    switch($appointment['appointmentStatus']) {
                      case 'active':
                        $status_class = 'badge-success';
                        $status_text = 'Active';
                        break;
                      case 'cancelled':
                        $status_class = 'badge-danger';
                        $status_text = 'Cancelled';
                        break;
                      default:
                        $status_class = 'badge-warning';
                        $status_text = 'Pending';
                    }
                  ?>
                  <tr data-date="<?php echo $appointment['appdate']; ?>" 
                      data-status="<?php echo $appointment['appointmentStatus']; ?>"
                      class="<?php echo $is_today ? 'today-row' : ''; ?> <?php echo $is_past ? 'past-row' : ''; ?>">
                    <td>APT<?php echo str_pad($appointment['ID'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td>
                      <strong><?php echo htmlspecialchars($appointment['fname'] . ' ' . $appointment['lname']); ?></strong><br>
                      <small style="color: #666;"><?php echo htmlspecialchars($appointment['contact']); ?></small>
                    </td>
                    <td>
                      <?php echo date('M d, Y', strtotime($appointment['appdate'])); ?><br>
                      <small style="color: #666;"><?php echo date('h:i A', strtotime($appointment['apptime'])); ?></small>
                      <?php if($is_today): ?>
                        <span class="badge badge-info">Today</span>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                    <td>Rs. <?php echo number_format($appointment['docFees'], 2); ?></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-sm btn-primary" onclick="viewAppointment(<?php echo $appointment['ID']; ?>)">
                          <i class="fas fa-eye"></i>
                        </button>
                        <?php if($appointment['appointmentStatus'] == 'active' && !$is_past): ?>
                          <button class="btn-sm btn-warning" onclick="cancelAppointment(<?php echo $appointment['ID']; ?>)">
                            <i class="fas fa-times"></i>
                          </button>
                        <?php endif; ?>
                        <button class="btn-sm btn-success" onclick="addPrescription(<?php echo $appointment['pid']; ?>, <?php echo $appointment['ID']; ?>)">
                          <i class="fas fa-prescription"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #7f8c8d;">
                      <i class="fas fa-calendar-times fa-2x" style="margin-bottom: 10px; display: block;"></i>
                      No appointments found
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- Payments Section (Hidden by default) -->
    <section id="payments" style="display: none;">
      <div class="section-card">
        <div class="section-header">
          <h3><i class="fas fa-credit-card"></i> Payments</h3>
          <div class="filter-buttons">
            <button class="filter-btn active" onclick="filterPayments('all')">All</button>
            <button class="filter-btn" onclick="filterPayments('paid')">Paid</button>
            <button class="filter-btn" onclick="filterPayments('pending')">Pending</button>
          </div>
        </div>
        <div class="section-body">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Payment ID</th>
                  <th>Patient</th>
                  <th>Appointment ID</th>
                  <th>Amount (LKR)</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Method</th>
                </tr>
              </thead>
              <tbody>
                <?php if(mysqli_num_rows($payments_query) > 0): ?>
                  <?php while($payment = mysqli_fetch_assoc($payments_query)): 
                    $status_class = $payment['pay_status'] == 'Paid' ? 'badge-success' : 'badge-warning';
                  ?>
                  <tr>
                    <td>PAY<?php echo str_pad($payment['id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($payment['fname'] . ' ' . $payment['lname']); ?></td>
                    <td>APT<?php echo str_pad($payment['appointment_id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td><strong>Rs. <?php echo number_format($payment['fees'], 2); ?></strong></td>
                    <td><?php echo date('M d, Y', strtotime($payment['pay_date'])); ?></td>
                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $payment['pay_status']; ?></span></td>
                    <td><?php echo htmlspecialchars($payment['payment_method'] ?: 'N/A'); ?></td>
                  </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: #7f8c8d;">
                      <i class="fas fa-money-bill-wave fa-2x" style="margin-bottom: 10px; display: block;"></i>
                      No payments found
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- Prescriptions Section (Hidden by default) -->
    <section id="prescriptions" style="display: none;">
      <div class="section-card">
        <div class="section-header">
          <h3><i class="fas fa-prescription"></i> Prescriptions</h3>
          <button class="btn-sm btn-success" onclick="addNewPrescription()">
            <i class="fas fa-plus"></i> New Prescription
          </button>
        </div>
        <div class="section-body">
          <p style="text-align: center; color: #7f8c8d; padding: 20px;">
            <i class="fas fa-file-medical fa-2x" style="margin-bottom: 10px; display: block;"></i>
            Prescriptions management will be added soon
          </p>
        </div>
      </div>
    </section>

  </div>

  <!-- JavaScript -->
  <script>
    // Navigation functionality
    function showSection(sectionId) {
      // Hide all sections
      document.querySelectorAll('section').forEach(section => {
        section.style.display = 'none';
      });
      
      // Remove active class from all nav items
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
      });
      
      // Show selected section
      document.getElementById(sectionId).style.display = 'block';
      
      // Add active class to clicked nav item
      event.target.closest('.nav-item').classList.add('active');
      
      // Update page title
      const titles = {
        'dashboard': 'Dashboard Overview',
        'patients': 'My Patients',
        'appointments': 'Appointments',
        'payments': 'Payments',
        'prescriptions': 'Prescriptions'
      };
      
      document.getElementById('pageTitle').textContent = titles[sectionId];
      
      // Scroll to top
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Filter functions
    function filterPatients(filter) {
      const buttons = document.querySelectorAll('#patients .filter-btn');
      buttons.forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
      
      // Implement filter logic here
      console.log('Filter patients by:', filter);
    }

    function filterAppointments(filter) {
      const buttons = document.querySelectorAll('#appointments .filter-btn');
      buttons.forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
      
      const rows = document.querySelectorAll('#appointmentsTable tr');
      const today = new Date().toISOString().split('T')[0];
      
      rows.forEach(row => {
        if(row.cells.length === 1) return; // Skip the "no data" row
        
        const date = row.getAttribute('data-date');
        const rowDate = new Date(date);
        const now = new Date();
        
        switch(filter) {
          case 'today':
            row.style.display = date === today ? '' : 'none';
            break;
          case 'upcoming':
            row.style.display = rowDate >= now ? '' : 'none';
            break;
          case 'past':
            row.style.display = rowDate < now ? '' : 'none';
            break;
          default:
            row.style.display = '';
        }
      });
    }

    function filterPayments(filter) {
      const buttons = document.querySelectorAll('#payments .filter-btn');
      buttons.forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
      
      // Implement payment filter logic here
      console.log('Filter payments by:', filter);
    }

    // Logout function
    function logout() {
      if(confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
      }
    }

    // View patient details
    function viewPatient(patientId) {
      window.open(`view_patient.php?pid=${patientId}`, '_blank');
    }

    // View appointment
    function viewAppointment(appointmentId) {
      alert(`View appointment ${appointmentId}\nFeature coming soon!`);
    }

    // Cancel appointment
    function cancelAppointment(appointmentId) {
      if(confirm('Cancel this appointment?')) {
        fetch('cancel_appointment.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `appointment_id=${appointmentId}&cancelledBy=doctor`
        })
        .then(response => response.text())
        .then(data => {
          alert('Appointment cancelled');
          location.reload();
        })
        .catch(error => {
          alert('Error cancelling appointment');
        });
      }
    }

    // Add prescription
    function addPrescription(patientId, appointmentId = null) {
      let url = `add_prescription.php?pid=${patientId}`;
      if(appointmentId) url += `&appointment_id=${appointmentId}`;
      window.open(url, '_blank', 'width=800,height=600');
    }

    // Add new prescription
    function addNewPrescription() {
      window.open('add_prescription.php', '_blank', 'width=800,height=600');
    }

    // Countdown timer
    function updateCountdown() {
      const now = new Date();
      const target = new Date();
      target.setHours(16, 0, 0, 0); // 4:00 PM
      
      if(now > target) {
        target.setDate(target.getDate() + 1);
      }
      
      const diff = target - now;
      
      const hours = Math.floor(diff / (1000 * 60 * 60));
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((diff % (1000 * 60)) / 1000);
      
      document.getElementById('countdown').textContent = 
        `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    // Weather simulation
    function updateWeather() {
      const temps = [28, 29, 30, 31, 32, 33];
      const conditions = ['Sunny', 'Mostly sunny', 'Partly cloudy', 'Clear'];
      
      const randomTemp = temps[Math.floor(Math.random() * temps.length)];
      const randomCondition = conditions[Math.floor(Math.random() * conditions.length)];
      
      document.getElementById('temperature').textContent = `${randomTemp}°C`;
      document.getElementById('weatherCondition').textContent = randomCondition;
    }

    // Search functionality
    document.getElementById('searchForm').addEventListener('submit', function(e) {
      const searchInput = this.querySelector('input[name="search"]');
      if(!searchInput.value.trim()) {
        e.preventDefault();
        alert('Please enter search terms');
      }
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
      // Start countdown timer
      updateCountdown();
      setInterval(updateCountdown, 1000);
      
      // Update weather every 30 minutes
      updateWeather();
      setInterval(updateWeather, 1800000);
      
      // Add keyboard shortcuts
      document.addEventListener('keydown', function(e) {
        // Ctrl + D for dashboard
        if(e.ctrlKey && e.key === 'd') {
          e.preventDefault();
          showSection('dashboard');
        }
        // Ctrl + P for patients
        if(e.ctrlKey && e.key === 'p') {
          e.preventDefault();
          showSection('patients');
        }
        // Ctrl + A for appointments
        if(e.ctrlKey && e.key === 'a') {
          e.preventDefault();
          showSection('appointments');
        }
        // Ctrl + M for payments
        if(e.ctrlKey && e.key === 'm') {
          e.preventDefault();
          showSection('payments');
        }
        // Ctrl + L for logout
        if(e.ctrlKey && e.key === 'l') {
          e.preventDefault();
          logout();
        }
        // Esc to clear search
        if(e.key === 'Escape') {
          const searchInput = document.querySelector('.search-input');
          if(searchInput.value) {
            searchInput.value = '';
            window.location.href = window.location.pathname;
          }
        }
      });
      
      // Auto-refresh appointments every 2 minutes
      setInterval(() => {
        if(document.getElementById('appointments').style.display !== 'none') {
          console.log('Auto-refreshing appointments...');
          // You can add AJAX refresh here
        }
      }, 120000);
    });

    // Toggle sidebar (optional feature)
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('collapsed');
      document.getElementById('mainContent').classList.toggle('sidebar-collapsed');
    }
  </script>

  <!-- Additional CSS for table rows -->
  <style>
    .today-row {
      background-color: rgba(52, 152, 219, 0.1);
    }
    
    .past-row {
      opacity: 0.7;
    }
    
    .past-row:hover {
      opacity: 1;
    }
    
    /* Print styles */
    @media print {
      .sidebar, .top-header, .action-buttons, .filter-buttons, .logout-btn {
        display: none !important;
      }
      
      .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 0 !important;
      }
      
      .section-card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
      }
    }
  </style>

</body>
</html>