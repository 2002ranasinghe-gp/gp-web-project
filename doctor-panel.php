?php
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
<html>
<head>
  <title>Doctor Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      display: flex;
      background-color: #f5f7fa;
    }
    .sidebar {
      width: 220px;
      height: 100vh;
      background-color: #2d3436;
      color: #fff;
      position: fixed;
      padding-top: 20px;
    }
    .sidebar h3 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 22px;
    }
    .sidebar a {
      color: #fff;
      text-decoration: none;
      display: block;
      padding: 12px 20px;
      font-size: 16px;
    }
    .sidebar a:hover, .sidebar a.active {
      background-color: #0984e3;
    }
    .main {
      margin-left: 230px;
      padding: 25px;
      width: 100%;
    }
    .hidden {
      display: none;
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <h3>Doctor Panel</h3>
    <a href="#" class="nav-link active" onclick="showSection(event, 'dashboard')">Dashboard</a>
    <a href="#" class="nav-link" onclick="showSection(event, 'doctors')">Doctors</a>
    <a href="#" class="nav-link" onclick="showSection(event, 'patients')">Patients</a>
    <a href="#" class="nav-link" onclick="showSection(event, 'appointments')">Appointments</a>
    <a href="#" class="nav-link" onclick="showSection(event, 'payments')">Payments</a>
  </div>

  <div class="main">
    <!-- DASHBOARD -->
    <div id="dashboard">
      <h2 class="mb-4">Dashboard Overview</h2>
      <div class="row g-3">
        <div class="col-md-3"><div class="card text-center"><div class="card-body"><h4><?php echo $count_doctors; ?></h4><p>Doctors</p></div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body"><h4><?php echo $count_patients; ?></h4><p>Patients</p></div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body"><h4><?php echo $count_appointments; ?></h4><p>Appointments</p></div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body"><h4><?php echo $count_payments; ?></h4><p>Payments</p></div></div></div>
      </div>
    </div>

    <!-- DOCTORS -->
    <div id="doctors" class="hidden">
      <h2>Doctors List</h2>
      <table class="table table-striped table-bordered mt-3">
        <thead class="table-dark">
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
        <thead class="table-dark">
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
        <thead class="table-dark">
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
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Patient ID</th>
            <th>Appointment ID</th>
            <th>Patient Name</th>
            <th>Doctor</th>
            <th>Fees (LKR)</th>
            <th>Payment Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = mysqli_fetch_assoc($payments)) { ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['pid']; ?></td>
            <td><?php echo $row['app_id']; ?></td>
            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
            <td><?php echo htmlspecialchars($row['doctor']); ?></td>
            <td><?php echo htmlspecialchars($row['fees']); ?></td>
            <td><?php echo htmlspecialchars($row['pay_date']); ?></td>
            <td><?php echo htmlspecialchars($row['pay_status']); ?></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    function showSection(event, id) {
      document.querySelectorAll('.main > div').forEach(div => div.classList.add('hidden'));
      document.getElementById(id).classList.remove('hidden');
      document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
      event.target.classList.add('active');
    }
  </script>
</body>
</html>