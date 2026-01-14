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

// Fetch counts for current doctor
$count_doctors = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM doctb"))[0];
$count_patients = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(DISTINCT pid) FROM appointmenttb WHERE doctor = '$current_doctor'"))[0];
$count_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb WHERE doctor = '$current_doctor'"))[0];
$count_payments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM paymenttb WHERE doctor = '$current_doctor'"))[0];

// Fetch data with proper column names
$doctors = mysqli_query($con, "SELECT username, email, spec FROM doctb");

// Current doctor's patients (patients who have appointments with this doctor)
$patients = mysqli_query($con, "SELECT DISTINCT p.pid, p.fname, p.lname, p.email, p.contact 
                               FROM patreg p 
                               INNER JOIN appointmenttb a ON p.pid = a.pid 
                               WHERE a.doctor = '$current_doctor' 
                               ORDER BY p.fname");

// Current doctor's appointments
$appointments = mysqli_query($con, "SELECT * FROM appointmenttb WHERE doctor = '$current_doctor' ORDER BY appdate DESC, apptime DESC");

// Current doctor's payments
$payments = mysqli_query($con, "SELECT * FROM paymenttb WHERE doctor = '$current_doctor' ORDER BY pay_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Panel Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* === GENERAL PAGE === */
    body {
      display: flex;
      background-color: #f0f4f7;
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
    }
    .sidebar {
      width: 230px;
      height: 100vh;
      background: linear-gradient(180deg, #0d47a1, #1565c0);
      color: #fff;
      position: fixed;
      padding-top: 25px;
      box-shadow: 3px 0 10px rgba(0,0,0,0.2);
      display: flex;
      flex-direction: column;
    }
    .sidebar h3 {
      text-align: center;
      font-weight: 600;
      margin-bottom: 30px;
      padding: 0 15px;
    }
    .sidebar a {
      color: #dfe6e9;
      text-decoration: none;
      display: flex;
      align-items: center;
      padding: 12px 20px;
      font-size: 16px;
      border-radius: 6px;
      margin: 5px 10px;
      transition: background 0.3s;
    }
    .sidebar a:hover, .sidebar a.active {
      background-color: #42a5f5;
      color: #fff;
    }
    .sidebar i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }
    .main {
      margin-left: 240px;
      padding: 25px;
      width: calc(100% - 240px);
    }
    .hidden {
      display: none;
    }

    /* === HEADER === */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid #e0e0e0;
    }
    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .logout-btn {
      background-color: #d32f2f;
      color: white;
      border: none;
      padding: 8px 20px;
      border-radius: 5px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: background-color 0.3s;
    }
    .logout-btn:hover {
      background-color: #b71c1c;
    }

    /* === HOSPITAL POSTER === */
    .dashboard-banner {
      margin: 20px 0 40px 0;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    .dashboard-banner img {
      width: 100%;
      height: 250px;
      object-fit: cover;
    }

    /* === DASHBOARD CARDS === */
    .dashboard-card {
      border-radius: 18px;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      overflow: hidden;
      color: white;
      text-align: center;
      background: linear-gradient(135deg, #1976d2, #42a5f5);
      box-shadow: 0 5px 20px rgba(0,0,0,0.15);
      height: 100%;
    }
    .dashboard-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    }
    .dashboard-card .card-body {
      padding: 30px 10px;
      position: relative;
    }
    .dashboard-card img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 15px;
      border: 3px solid #bbdefb;
      background: #fff;
      padding: 5px;
    }
    .dashboard-card h3 {
      font-size: 2.4rem;
      font-weight: bold;
      margin: 5px 0;
    }
    .dashboard-card p {
      margin: 0;
      font-size: 1.1rem;
      letter-spacing: 0.5px;
    }

    /* === TABLES === */
    table th {
      background-color: #1565c0 !important;
      color: white !important;
      text-align: center;
    }
    table td {
      text-align: center;
      vertical-align: middle;
    }
    .status-active {
      background-color: #4caf50;
      color: white;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.85rem;
    }
    .status-pending {
      background-color: #ff9800;
      color: white;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.85rem;
    }
    .status-cancelled {
      background-color: #f44336;
      color: white;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.85rem;
    }
    
    /* Doctor specific badge */
    .doctor-badge {
      background-color: #6a1b9a;
      color: white;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: bold;
    }

    /* === LOGOUT BUTTON IN SIDEBAR === */
    .sidebar-footer {
      margin-top: auto;
      padding: 20px;
    }
    .sidebar-logout {
      background-color: #d32f2f;
      color: white;
      border: none;
      width: 100%;
      padding: 12px;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: background-color 0.3s;
    }
    .sidebar-logout:hover {
      background-color: #b71c1c;
    }
    
    /* Doctor info box */
    .doctor-info-box {
      background: linear-gradient(135deg, #2196f3, #0d47a1);
      color: white;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h3>Doctor Panel</h3>
    <a href="#" class="nav-link active" onclick="showSection('dashboard', event)">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="#" class="nav-link" onclick="showSection('doctors', event)">
      <i class="fas fa-user-md"></i> Doctors
    </a>
    <a href="#" class="nav-link" onclick="showSection('patients', event)">
      <i class="fas fa-users"></i> My Patients
    </a>
    <a href="#" class="nav-link" onclick="showSection('appointments', event)">
      <i class="fas fa-calendar-check"></i> My Appointments
    </a>
    <a href="#" class="nav-link" onclick="showSection('payments', event)">
      <i class="fas fa-credit-card"></i> My Payments
    </a>
    
    <!-- Logout Button in Sidebar Footer -->
    <div class="sidebar-footer">
      <button class="sidebar-logout" onclick="logout()">
        <i class="fas fa-sign-out-alt"></i> Logout
      </button>
    </div>
  </div>

  <!-- Main -->
  <div class="main">

    <!-- Header -->
    <div class="header">
      <h2 class="fw-bold text-primary" id="section-title">Dashboard Overview</h2>
      <div class="user-info">
        <span class="doctor-badge">Dr. <?php echo htmlspecialchars($current_doctor); ?></span>
        <button class="logout-btn" onclick="logout()">
          <i class="fas fa-sign-out-alt"></i> Logout
        </button>
      </div>
    </div>

    <!-- Doctor Information Box -->
    <div class="doctor-info-box">
      <h4><i class="fas fa-stethoscope"></i> Welcome, Dr. <?php echo htmlspecialchars($current_doctor); ?></h4>
      <p>You are logged in as a doctor. Here you can view your appointments, patients, and payments.</p>
    </div>

    <!-- DASHBOARD -->
    <div id="dashboard">
      <!-- Hospital Theme Poster -->
      <div class="dashboard-banner">
        <img src="img/photo/6.jpg" alt="Hospital Poster" />
      </div>

      <div class="row g-4">
        <!-- Doctors -->
        <div class="col-md-3">
          <div class="card dashboard-card">
            <div class="card-body">
              <img src="https://img.freepik.com/free-photo/portrait-smiling-doctor-with-stethoscope_23-2147896147.jpg" alt="Doctors">
              <h3><?php echo $count_doctors; ?></h3>
              <p>Total Doctors</p>
            </div>
          </div>
        </div>

        <!-- My Patients -->
        <div class="col-md-3">
          <div class="card dashboard-card">
            <div class="card-body">
              <img src="https://img.freepik.com/free-photo/patient-smiling-hospital-bed_53876-14916.jpg" alt="Patients">
              <h3><?php echo $count_patients; ?></h3>
              <p>My Patients</p>
            </div>
          </div>
        </div>

        <!-- My Appointments -->
        <div class="col-md-3">
          <div class="card dashboard-card">
            <div class="card-body">
              <img src="https://img.freepik.com/free-photo/doctor-patient-consultation-office_23-2148077484.jpg" alt="Appointments">
              <h3><?php echo $count_appointments; ?></h3>
              <p>My Appointments</p>
            </div>
          </div>
        </div>

        <!-- My Payments -->
        <div class="col-md-3">
          <div class="card dashboard-card">
            <div class="card-body">
              <img src="https://img.freepik.com/free-photo/stethoscope-money-bills-economy-concept_53876-127116.jpg" alt="Payments">
              <h3><?php echo $count_payments; ?></h3>
              <p>My Payments</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- DOCTORS -->
    <div id="doctors" class="hidden">
      <h2>Doctors List</h2>
      <p class="text-muted">Showing all doctors in the system</p>
      <div class="table-responsive">
        <table class="table table-striped table-bordered mt-3">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Specialization</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            while($row = mysqli_fetch_assoc($doctors)) { 
              $isCurrent = ($row['username'] == $current_doctor);
            ?>
            <tr>
              <td>
                <?php echo htmlspecialchars($row['username']); ?>
                <?php if($isCurrent): ?>
                  <span class="badge bg-success">You</span>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($row['email']); ?></td>
              <td><?php echo htmlspecialchars($row['spec']); ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MY PATIENTS -->
    <div id="patients" class="hidden">
      <h2>My Patients</h2>
      <p class="text-muted">Showing patients who have appointments with you</p>
      <div class="table-responsive">
        <table class="table table-striped table-bordered mt-3">
          <thead>
            <tr>
              <th>Patient ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Contact</th>
              <th>National ID</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            // Reset pointer for patients query
            mysqli_data_seek($patients, 0);
            if(mysqli_num_rows($patients) > 0) {
              while($row = mysqli_fetch_assoc($patients)) { 
                // Get national ID for this patient
                $pid = $row['pid'];
                $national_id_query = mysqli_query($con, "SELECT national_id FROM patreg WHERE pid = '$pid'");
                $national_id_row = mysqli_fetch_assoc($national_id_query);
                $national_id = $national_id_row['national_id'] ?? 'N/A';
            ?>
            <tr>
              <td><?php echo $row['pid']; ?></td>
              <td><?php echo htmlspecialchars($row['fname'].' '.$row['lname']); ?></td>
              <td><?php echo htmlspecialchars($row['email']); ?></td>
              <td><?php echo htmlspecialchars($row['contact']); ?></td>
              <td><?php echo htmlspecialchars($national_id); ?></td>
            </tr>
            <?php 
              }
            } else {
            ?>
            <tr>
              <td colspan="5" class="text-center">No patients found for Dr. <?php echo htmlspecialchars($current_doctor); ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MY APPOINTMENTS -->
    <div id="appointments" class="hidden">
      <h2>My Appointments</h2>
      <p class="text-muted">Showing appointments scheduled with you</p>
      <div class="table-responsive">
        <table class="table table-striped table-bordered mt-3">
          <thead>
            <tr>
              <th>Appointment ID</th>
              <th>Patient</th>
              <th>Date</th>
              <th>Time</th>
              <th>Fee (LKR)</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            // Reset pointer for appointments query
            mysqli_data_seek($appointments, 0);
            if(mysqli_num_rows($appointments) > 0) {
              while($row = mysqli_fetch_assoc($appointments)) { 
                $status = '';
                $statusClass = '';
                
                if ($row['appointmentStatus'] == 'active') {
                  $status = 'Active';
                  $statusClass = 'status-active';
                } elseif ($row['appointmentStatus'] == 'cancelled') {
                  $status = 'Cancelled';
                  $statusClass = 'status-cancelled';
                } else {
                  $status = 'Pending';
                  $statusClass = 'status-pending';
                }
            ?>
            <tr>
              <td><?php echo $row['ID']; ?></td>
              <td><?php echo htmlspecialchars($row['fname'].' '.$row['lname']); ?></td>
              <td><?php echo htmlspecialchars($row['appdate']); ?></td>
              <td><?php echo htmlspecialchars($row['apptime']); ?></td>
              <td>Rs. <?php echo number_format($row['docFees'], 2); ?></td>
              <td><span class="<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
            </tr>
            <?php 
              }
            } else {
            ?>
            <tr>
              <td colspan="6" class="text-center">No appointments found for Dr. <?php echo htmlspecialchars($current_doctor); ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MY PAYMENTS -->
    <div id="payments" class="hidden">
      <h2>My Payments</h2>
      <p class="text-muted">Showing payments for your appointments</p>
      <div class="table-responsive">
        <table class="table table-striped table-bordered mt-3">
          <thead>
            <tr>
              <th>Payment ID</th>
              <th>Appointment ID</th>
              <th>Patient Name</th>
              <th>Amount (LKR)</th>
              <th>Payment Date</th>
              <th>Status</th>
              <th>Method</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            // Reset pointer for payments query
            mysqli_data_seek($payments, 0);
            if(mysqli_num_rows($payments) > 0) {
              while($row = mysqli_fetch_assoc($payments)) { 
                $statusClass = $row['pay_status'] == 'Paid' ? 'status-active' : 'status-pending';
            ?>
            <tr>
              <td><?php echo $row['id']; ?></td>
              <td><?php echo htmlspecialchars($row['appointment_id']); ?></td>
              <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
              <td>Rs. <?php echo number_format($row['fees'], 2); ?></td>
              <td><?php echo htmlspecialchars($row['pay_date']); ?></td>
              <td><span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['pay_status']); ?></span></td>
              <td><?php echo htmlspecialchars($row['payment_method'] ?? 'N/A'); ?></td>
            </tr>
            <?php 
              }
            } else {
            ?>
            <tr>
              <td colspan="7" class="text-center">No payments found for Dr. <?php echo htmlspecialchars($current_doctor); ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <script>
    function showSection(id, event) {
      // Hide all sections
      document.querySelectorAll('.main > div').forEach(div => {
        if(div.id !== 'dashboard' && div.id !== 'doctors' && div.id !== 'patients' && 
           div.id !== 'appointments' && div.id !== 'payments') return;
        div.classList.add('hidden');
      });
      
      // Show selected section
      document.getElementById(id).classList.remove('hidden');
      
      // Update active nav link
      document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
      event.currentTarget.classList.add('active');
      
      // Update section title
      const titles = {
        'dashboard': 'Dashboard Overview',
        'doctors': 'Doctors List',
        'patients': 'My Patients',
        'appointments': 'My Appointments',
        'payments': 'My Payments'
      };
      document.getElementById('section-title').textContent = titles[id];
    }

    function logout() {
      if(confirm("Are you sure you want to logout?")) {
        window.location.href = "logout.php";
      }
    }

    // Handle logout with Enter key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Enter' && event.target.classList.contains('logout-btn')) {
        logout();
      }
    });
  </script>

</body>
</html>