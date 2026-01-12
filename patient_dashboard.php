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

// Get patient 
$appointments_query = "SELECT * FROM appointmenttb WHERE email='$email' ORDER BY appdate DESC";
$appointments_result = mysqli_query($con, $appointments_query);

// Get payments
$payments_query = "SELECT * FROM paymenttb WHERE national_id='" . ($patient['national_id'] ?? '') . "' ORDER BY pay_date DESC";
$payments_result = mysqli_query($con, $payments_query);

// Get prescriptions
$prescriptions_query = "SELECT * FROM prestb WHERE national_id='" . ($patient['national_id'] ?? '') . "' ORDER BY appdate DESC";
$prescriptions_result = mysqli_query($con, $prescriptions_query);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Healthcare Hospital - Patient Dashboard</title>
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    body { background:#eef2f5; font-family:'Poppins',sans-serif; display:flex; }

    /* Sidebar */
    .sidebar {
      width:250px; background:#0077b6; color:white; min-height:100vh;
      position:fixed; left:0; top:0; padding-top:20px; box-shadow:2px 0 10px rgba(0,0,0,.1);
    }
    .sidebar .logo {
      width:60px; height:60px; border-radius:10px; background:white; object-fit:cover;
      margin:0 auto 10px; display:block; padding:5px;
    }
    .sidebar h4 { text-align:center; font-weight:700; font-size:20px; margin-bottom:30px; }
    .sidebar ul { list-style:none; padding-left:0; }
    .sidebar ul li {
      padding:12px 20px; cursor:pointer; transition:all .3s; border-left:4px solid transparent;
    }
    .sidebar ul li:hover, .sidebar ul li.active {
      background:#0096c7; border-left:4px solid #fff;
    }

    .submenu { 
        list-style: none; 
        padding-left: 25px; 
        background: #0086b8;
        display: none; 
    }
    .submenu li { 
        padding: 8px 10px; 
        font-size: 14px; 
    }
    .submenu li:hover { 
        background: #00a3da; 
    }

    .ward-menu-item.active > .submenu {
        display: block;
    }

    .ward-menu-item {
        cursor: pointer;
    }

    /* Main content */
    .main-content { margin-left:250px; width:calc(100% - 250px); }
    .topbar {
      background:#0077b6; color:white; padding:14px 24px;
      display:flex; align-items:center; gap:14px; box-shadow:0 2px 10px rgba(0,0,0,.1);
    }
    .brand { font-weight:700; font-size:22px; }

    /* Interfaces */
    .interface {
      display:none; padding:30px; margin:20px auto; background:white; border-radius:10px;
      box-shadow:0 6px 20px rgba(0,0,0,.05);
    }
    .interface.active { display:block; }
    .header-title { text-align:center; font-weight:700; font-size:24px; margin-bottom:30px; }
    .back { color:#0077b6; font-weight:600; cursor:pointer; text-decoration:underline; }

    /* Cards */
    .service-grid {
      display:grid;
      grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
      gap:20px;
    }
    .service-card {
      background:#f0f8ff;
      border:none;
      border-radius:12px;
      padding:25px 15px;
      text-align:center;
      box-shadow:0 4px 12px rgba(0,0,0,0.08);
      transition:all .3s ease;
      cursor:pointer;
    }
    .service-card:hover {
      background:#0096c7;
      color:white;
      transform:translateY(-5px);
      box-shadow:0 8px 20px rgba(0,0,0,0.12);
    }
    .service-card img.service-icon {
      width:60px;
      height:60px;
      margin-bottom:15px;
      transition: transform .3s;
    }
    .service-card:hover img.service-icon {
      transform: scale(1.2);
    }
    .service-card h6 { font-weight:600; margin-top:5px; }
    
    /* Patient info */
    .patient-info {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <img class="logo" src="images/medical-logo - Copy.jpg" alt="Hospital Logo">
    <ul>
      <li data-target="dashboard" class="active">🏠 Dashboard</li>
      <li data-target="services">🩺 Services</li>
      <li data-target="schedule">📅 Schedule</li>
      <li data-target="appointment">📋 Appointments</li>
      <li data-target="payment">💳 Payments</li>

      <li id="wardMenu" class="ward-menu-item">🏨 Ward ▾
        <ul class="submenu">
          <li class="submenu-item">
            <a href="ward.php" style="color: white; text-decoration: none; display: block; padding: 8px 10px;">Room</a>
          </li> 
          <li class="submenu-item">
            <a href="food.php" style="color: white; text-decoration: none; display: block; padding: 8px 10px;">Food</a>
          </li>
        </ul>
      </li>
      
      <li data-target="settings">⚙️ Settings</li>
      <li><a href="logout.php" style="color: white; text-decoration: none;">🚪 Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="topbar">
      <div class="brand">Healthcare Hospital</div>
      <div style="margin-left:auto;">
        👤 Welcome, <?php echo htmlspecialchars($patient['fname'] ?? 'Patient'); ?>
      </div>
    </div>

    <!-- Patient Info -->
    <div class="patient-info">
      <div class="row">
        <div class="col-md-6">
          <h5><?php echo htmlspecialchars(($patient['fname'] ?? '').' '.($patient['lname'] ?? '')); ?></h5>
          <p>Email: <?php echo htmlspecialchars($patient['email'] ?? ''); ?></p>
          <p>Contact: <?php echo htmlspecialchars($patient['contact'] ?? ''); ?></p>
        </div>
        <div class="col-md-6">
          <p>Patient ID: <?php echo htmlspecialchars($patient['pid'] ?? ''); ?></p>
          <p>National ID: <?php echo htmlspecialchars($patient['national_id'] ?? ''); ?></p>
          <p>Gender: <?php echo htmlspecialchars($patient['gender'] ?? ''); ?></p>
        </div>
      </div>
    </div>

    <!-- Dashboard -->
    <div id="dashboard" class="interface active">
      <div class="header-title">Welcome to Your Dashboard</div>
      <p style="text-align:center;">Select a quick option below:</p>

      <div class="service-grid mt-4">
        <div class="service-card" data-target="allDoctors">
          <img src="images/doctor.png" alt="Doctor" class="service-icon">
          <h6> Doctors</h6>
        </div>
        <div class="service-card" data-target="emergencyCall">
          <img src="images/Emergency call.jpeg" alt="Emergency" class="service-icon">
          <h6>Emergency Call</h6>
        </div>
        <div class="service-card" data-target="todaysSessions">
          <img src="images/Session.png" alt="Sessions" class="service-icon">
          <h6>Today's Sessions</h6>
        </div>
        <div class="service-card" onclick="location.href='book_appointment.php'">
          <img src="images/appointment.png" alt="Book Appointment" class="service-icon">
          <h6>Book Appointment</h6>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="row mt-5">
        <div class="col-md-3">
          <div class="card text-white bg-info">
            <div class="card-body text-center">
              <h4><?php echo mysqli_num_rows(mysqli_query($con, $appointments_query)); ?></h4>
              <p>Total Appointments</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-success">
            <div class="card-body text-center">
              <h4><?php echo mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email' AND doctorStatus=1")); ?></h4>
              <p>Confirmed</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-warning">
            <div class="card-body text-center">
              <h4><?php echo mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email' AND doctorStatus=0")); ?></h4>
              <p>Pending</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-secondary">
            <div class="card-body text-center">
              <h4><?php echo mysqli_num_rows(mysqli_query($con, $prescriptions_query)); ?></h4>
              <p>Prescriptions</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- All Doctors -->
    <div id="allDoctors" class="interface">
      <span class="back" onclick="showInterface('dashboard')">← Back</span>
      <div class="header-title">All Doctors</div>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead class="thead-light">
            <tr><th>Doctor Name</th><th>Specialization</th><th>Available Time</th><th>Fees (LKR)</th></tr>
          </thead>
          <tbody>
            <?php
            $doctors_query = "SELECT * FROM doctb";
            $doctors_result = mysqli_query($con, $doctors_query);
            while($doctor = mysqli_fetch_assoc($doctors_result)): 
            ?>
            <tr>
              <td>Dr. <?php echo htmlspecialchars($doctor['username']); ?></td>
              <td><?php echo htmlspecialchars($doctor['spec']); ?></td>
              <td>9 AM - 5 PM</td>
              <td><?php echo htmlspecialchars($doctor['docFees']); ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Emergency Call -->
    <div id="emergencyCall" class="interface">
      <span class="back" onclick="showInterface('dashboard')">← Back</span>
      <div class="header-title">Emergency Contact</div>
      <div class="text-center">
        <h4 class="text-danger">Call Now: <strong>1990</strong></h4>
        <p class="mt-3">For ambulance or emergency assistance, call the hotline above immediately.</p>
        <button class="btn btn-danger btn-lg mt-3">🚑 Call Ambulance</button>
      </div>
    </div>

    <!-- Today's Sessions -->
    <div id="todaysSessions" class="interface">
      <span class="back" onclick="showInterface('dashboard')">← Back</span>
      <div class="header-title">Today's Sessions</div>
      <ul class="list-group" style="max-width:600px; margin:auto;">
        <li class="list-group-item"><strong>Dr. Nimal Perera</strong> – Cardiology Clinic – 9:00 AM</li>
        <li class="list-group-item"><strong>Dr. Samanthi Silva</strong> – Neurology Follow-up – 10:30 AM</li>
        <li class="list-group-item"><strong>Dr. Ruwan Fernando</strong> – Dental Cleaning – 1:00 PM</li>
        <li class="list-group-item"><strong>Dr. Malika Wijesinghe</strong> – Eye Checkup – 3:00 PM</li>
      </ul>
    </div>

    <!-- Services -->
    <div id="services" class="interface">
      <div class="header-title">Hospital Services</div>
      <div class="service-grid">
        <div class="service-card" data-target="opd">
          <img src="images/OPD.jpeg" alt="OPD" class="service-icon"><h6>OPD</h6>
        </div>
        <div class="service-card" data-target="dental">
          <img src="images/dental.png" alt="Dental" class="service-icon"><h6>Dental Care</h6>
        </div>
        <div class="service-card" data-target="eye">
          <img src="images/eye.jpeg" alt="Eye" class="service-icon"><h6>Eye Care</h6>
        </div>
        <div class="service-card" data-target="cardiology">
          <img src="images/cardiology.png" alt="Cardiology" class="service-icon"><h6>Cardiology</h6>
        </div>
      </div>
    </div>

    <!-- Appointments -->
    <div id="appointment" class="interface">
      <div class="header-title">My Appointments</div>
      <?php if(mysqli_num_rows($appointments_result) > 0): ?>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Doctor</th>
                <th>Fees</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while($appointment = mysqli_fetch_assoc($appointments_result)): ?>
              <tr>
                <td><?php echo htmlspecialchars($appointment['appdate']); ?></td>
                <td><?php echo htmlspecialchars($appointment['apptime']); ?></td>
                <td>Dr. <?php echo htmlspecialchars($appointment['doctor']); ?></td>
                <td>Rs. <?php echo htmlspecialchars($appointment['docFees']); ?></td>
                <td>
                  <?php if($appointment['doctorStatus'] == 1): ?>
                    <span class="badge badge-success">Confirmed</span>
                  <?php else: ?>
                    <span class="badge badge-warning">Pending</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-4">
          <p>No appointments found.</p>
          <button class="btn btn-primary" onclick="location.href='book_appointment.php'">
            Book Your First Appointment
          </button>
        </div>
      <?php endif; ?>
    </div>

    <!-- Payments -->
    <div id="payment" class="interface">
      <div class="header-title">Payment Portal</div>
      <?php if(mysqli_num_rows($payments_result) > 0): ?>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Date</th>
                <th>Doctor</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while($payment = mysqli_fetch_assoc($payments_result)): ?>
              <tr>
                <td><?php echo htmlspecialchars($payment['pay_date']); ?></td>
                <td>Dr. <?php echo htmlspecialchars($payment['doctor']); ?></td>
                <td>Rs. <?php echo htmlspecialchars($payment['fees']); ?></td>
                <td>
                  <?php if($payment['pay_status'] == 'Paid'): ?>
                    <span class="badge badge-success">Paid</span>
                  <?php else: ?>
                    <span class="badge badge-danger">Pending</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-4">
          <p>No payment records found.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Settings -->
    <div id="settings" class="interface">
      <div class="header-title">Settings</div>
      <div class="p-4" style="max-width:500px; margin:auto;">
        <h5>Change Password</h5>
        <form id="passwordForm">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" class="form-control" required>
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Update Password</button>
        </form>
      </div>
    </div>

  </div>

  <script>
    // Toggle interfaces
    function showInterface(id){
      document.querySelectorAll('.interface').forEach(el=>el.classList.remove('active'));
      document.getElementById(id).classList.add('active');
    }

    // Sidebar navigation
    document.querySelectorAll('.sidebar ul li[data-target]').forEach(li=>{
      li.addEventListener('click',()=>{
        document.querySelectorAll('.sidebar ul li').forEach(i=>i.classList.remove('active'));
        li.classList.add('active');
        showInterface(li.getAttribute('data-target'));
      });
    });

    // Ward submenu toggle
    const wardMenu = document.getElementById('wardMenu');
    wardMenu.addEventListener('click', function() {
        this.classList.toggle('active');
    });

    // Service cards click
    document.querySelectorAll('.service-card').forEach(card=>{
      card.addEventListener('click',()=>{
        let target = card.getAttribute('data-target');
        if(target) {
          showInterface(target);
        }
      });
    });

    // Password form submit
    document.getElementById('passwordForm').addEventListener('submit', function(e){
      e.preventDefault();
      alert("Password updated successfully!");
      this.reset();
    });
  </script>

</body>
</html>
<?php mysqli_close($con); ?>