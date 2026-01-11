<?php
include('func1.php');

// Fetch counts
$count_doctors = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM doctb"))[0];
$count_patients = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM patreg"))[0];
$count_appointments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM appointmenttb"))[0];
$count_payments = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) FROM paymenttb"))[0];

// Fetch data
$doctors = mysqli_query($con, "SELECT username, email, spec FROM doctb");
$patients = mysqli_query($con, "SELECT pid, fname, lname, email, contact FROM patreg");
$appointments = mysqli_query($con, "SELECT * FROM appointmenttb");
$payments = mysqli_query($con, "SELECT * FROM paymenttb");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Panel Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* === GENERAL PAGE === */
    body {
      display: flex;
      background-color: #f0f4f7;
      font-family: 'Poppins', sans-serif;
    }
    .sidebar {
      width: 230px;
      height: 100vh;
      background: linear-gradient(180deg, #0d47a1, #1565c0);
      color: #fff;
      position: fixed;
      padding-top: 25px;
      box-shadow: 3px 0 10px rgba(0,0,0,0.2);
    }
    .sidebar h3 {
      text-align: center;
      font-weight: 600;
      margin-bottom: 40px;
    }
    .sidebar a {
      color: #dfe6e9;
      text-decoration: none;
      display: block;
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
    .main {
      margin-left: 240px;
      padding: 25px;
      width: 100%;
    }
    .hidden {
      display: none;
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
      height: 500px;
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
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h3>Doctor Panel</h3>
    <a href="#" class="nav-link active" onclick="showSection('dashboard', event)">Dashboard</a>
    <a href="#" class="nav-link" onclick="showSection('doctors', event)">Doctors</a>
    <a href="#" class="nav-link" onclick="showSection('patients', event)">Patients</a>
    <a href="#" class="nav-link" onclick="showSection('appointments', event)">Appointments</a>
    <a href="#" class="nav-link" onclick="showSection('payments', event)">Payments</a>
  </div>

  <!-- Main -->
  <div class="main">

    <!-- DASHBOARD -->
    <div id="dashboard">
      <h2 class="fw-bold text-primary mb-3">Dashboard Overview</h2>

      <!-- Hospital Theme Poster Between Title & Cards -->
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
              <p>Doctors</p>
            </div>
          </div>
        </div>

        <!-- Patients -->
        <div class="col-md-3">
          <div class="card dashboard-card">
            <div class="card-body">
              <img src="https://img.freepik.com/free-photo/patient-smiling-hospital-bed_53876-14916.jpg" alt="Patients">
              <h3><?php echo $count_patients; ?></h3>
              <p>Patients</p>
            </div>
          </div>
        </div>

        <!-- Appointments -->
        <div class="col-md-3">
          <div class="card dashboard-card">
            <div class="card-body">
              <img src="https://img.freepik.com/free-photo/doctor-patient-consultation-office_23-2148077484.jpg" alt="Appointments">
              <h3><?php echo $count_appointments; ?></h3>
              <p>Appointments</p>
            </div>
          </div>
        </div>

        <!-- Payments -->
        <div class="col-md-3">
          <div class="card dashboard-card">
            <div class="card-body">
              <img src="https://img.freepik.com/free-photo/stethoscope-money-bills-economy-concept_53876-127116.jpg" alt="Payments">
              <h3><?php echo $count_payments; ?></h3>
              <p>Payments</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- DOCTORS -->
    <div id="doctors" class="hidden">
      <h2>Doctors List</h2>
      <table class="table table-striped table-bordered mt-3">
        <thead>
          <tr><th>Name</th><th>Email</th><th>Specialization</th></tr>
        </thead>
        <tbody>
          <?php while($row = mysqli_fetch_assoc($doctors)) { ?>
          <tr>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['spec']); ?></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

    <!-- PATIENTS -->
    <div id="patients" class="hidden">
      <h2>Patients List</h2>
      <table class="table table-striped table-bordered mt-3">
        <thead>
          <tr><th>ID</th><th>Name</th><th>Email</th><th>Contact</th></tr>
        </thead>
        <tbody>
          <?php while($row = mysqli_fetch_assoc($patients)) { ?>
          <tr>
            <td><?php echo $row['pid']; ?></td>
            <td><?php echo htmlspecialchars($row['fname'].' '.$row['lname']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['contact']); ?></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

    <!-- APPOINTMENTS -->
    <div id="appointments" class="hidden">
      <h2>Appointments</h2>
      <table class="table table-striped table-bordered mt-3">
        <thead>
          <tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php while($row = mysqli_fetch_assoc($appointments)) { 
            $status = ($row['userStatus']==1 && $row['doctorStatus']==1) ? 'Active' : (($row['userStatus']==0)?'Cancelled by Patient':'Cancelled by Doctor');
          ?>
          <tr>
            <td><?php echo $row['ID']; ?></td>
            <td><?php echo htmlspecialchars($row['fname'].' '.$row['lname']); ?></td>
            <td><?php echo htmlspecialchars($row['doctor']); ?></td>
            <td><?php echo htmlspecialchars($row['appdate']); ?></td>
            <td><?php echo htmlspecialchars($row['apptime']); ?></td>
            <td><?php echo $status; ?></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

    <!-- PAYMENTS -->
    <div id="payments" class="hidden">
      <h2>Payments</h2>
      <table class="table table-striped table-bordered mt-3">
        <thead>
          <tr><th>ID</th><th>Appointment ID</th><th>Amount</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php while($row = mysqli_fetch_assoc($payments)) { ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['app_id']); ?></td>
            <td><?php echo htmlspecialchars($row['fees']); ?></td>
            <td><?php echo htmlspecialchars($row['pay_date']); ?></td>
          </tr>
          <?php } ?>
        </tbody>
    </div>

  </div>

  <script>
    function showSection(id, event) {
      document.querySelectorAll('.main > div').forEach(div => div.classList.add('hidden'));
      document.getElementById(id).classList.remove('hidden');
      document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
      event.target.classList.add('active');
    }
  </script>

</body>
</html>
