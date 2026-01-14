<?php
session_start();

// ===========================
// Database Connection
// ===========================
$con = mysqli_connect("localhost", "root", "", "myhmsdb");
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

// ===========================
// Session Check
// ===========================
if (!isset($_SESSION['reception'])) {
    header("Location: index.php");
    exit();
}

$page = $_GET['page'] ?? 'dashboard';

// ===========================
// Helper Function to Prevent XSS
// ===========================
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reception Dashboard</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
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
            --light-gray: #f5f5f5;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            margin: 0; 
            padding: 0; 
            min-height: 100vh;
            color: var(--text-dark);
        }
        .navbar { 
            background: var(--primary-blue); 
            padding: 0.8rem 1rem; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar .navbar-brand { 
            font-weight: bold; 
            color: var(--white); 
            font-size: 1.5rem;
        }
        .navbar .welcome { 
            margin-left: auto; 
            color: var(--white); 
            font-weight: 500;
        }
        .navbar .welcome a { 
            color: var(--light-blue); 
            text-decoration: none; 
            margin-left: 10px;
            transition: color 0.3s;
        }
        .navbar .welcome a:hover {
            color: var(--white);
        }

        .sidebar { 
            width: 250px; 
            background: var(--primary-blue); 
            height: 100vh; 
            position: fixed; 
            top: 0; 
            left: 0; 
            padding-top: 80px; 
            transition: all 0.3s;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar ul { 
            list-style: none; 
            padding: 0; 
            margin: 0;
        }
        .sidebar ul li { 
            padding: 15px 25px; 
            color: var(--white); 
            cursor: pointer; 
            transition: all 0.3s;
            border-left: 4px solid transparent;
            margin: 5px 10px;
            border-radius: 0 8px 8px 0;
        }
        .sidebar ul li i { 
            margin-right: 12px; 
            width: 20px;
            text-align: center;
        }
        .sidebar ul li:hover, .sidebar ul li.active { 
            background: var(--dark-blue);
            border-left: 4px solid var(--medium-blue);
            transform: translateX(5px);
        }
        .sidebar ul li a { 
            color: var(--white); 
            text-decoration: none; 
            display: block;
            font-weight: 500;
        }

        .main { 
            margin-left: 250px; 
            padding: 30px;
            min-height: calc(100vh - 80px);
        }

        .card { 
            background: var(--white);
            color: var(--text-dark); 
            border-radius: 15px; 
            padding: 25px; 
            text-align: center; 
            box-shadow: 0 8px 25px rgba(30, 136, 229, 0.15);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            border-left: 5px solid var(--accent-blue);
        }
        .card i { 
            margin-bottom: 15px;
            font-size: 2.5rem;
            color: var(--primary-blue);
        }
        .card:hover { 
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(30, 136, 229, 0.2);
        }
        .card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
            color: var(--primary-blue);
        }
        .card p {
            font-size: 1rem;
            color: var(--text-light);
            margin: 0;
        }

        .table-container { 
            background: var(--white); 
            padding: 25px; 
            border-radius: 15px; 
            box-shadow: 0 8px 25px rgba(30, 136, 229, 0.1);
            margin-top: 20px; 
            overflow-x: auto;
        }
        h2 { 
            margin-bottom: 25px; 
            color: var(--primary-blue); 
            font-weight: bold;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 30px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .dashboard-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .dashboard-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(30, 136, 229, 0.1);
        }
        .table thead th {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        .table tbody td {
            padding: 12px 15px;
            border-color: #e3f2fd;
        }
        .table tbody tr:hover {
            background-color: var(--light-blue);
            transition: all 0.2s ease;
        }

        .badge-primary { background-color: var(--primary-blue); }
        .badge-success { background-color: #4caf50; }
        .badge-warning { background-color: #ff9800; }
        .badge-info { background-color: var(--accent-blue); }
        .badge-secondary { background-color: var(--text-light); }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding-top: 70px;
            }
            .sidebar ul li span {
                display: none;
            }
            .sidebar ul li i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            .main {
                margin-left: 70px;
                padding: 20px 15px;
            }
            .dashboard-header h1 {
                font-size: 2rem;
            }
            .stats-row .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
    <a class="navbar-brand" href="#">
        <i class="fas fa-hospital-alt mr-2"></i>HEALTHCARE 
    </a>
    <div class="welcome">
        <i class="fas fa-user-circle mr-2"></i>Welcome, <?php echo e($_SESSION['reception']); ?> 
        | <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
    </div>
</nav>

<!-- Sidebar -->
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
                <i class="fas fa-dollar-sign"></i><span> Payment</span>
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

<!-- Main Content -->
<div class="main">
<?php
switch($page){
    // ================== PATIENTS ==================
    case 'patients':
        $result = $con->query("SELECT * FROM patreg ORDER BY pid DESC");
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-user-injured mr-3'></i>Patient Management</h1>
                <p>Manage all patient records and information</p>
              </div>";
        echo "<div class='table-container'>
                <table class='table table-striped table-hover'>
                <thead><tr>
                <th>ID</th><th>First Name</th><th>Last Name</th><th>Gender</th><th>Email</th><th>Contact</th><th>National ID</th>
                </tr></thead><tbody>";
        while($row = $result->fetch_assoc()){
            echo "<tr>
            <td><strong>" . e($row['pid']) . "</strong></td>
            <td>" . e($row['fname']) . "</td>
            <td>" . e($row['lname']) . "</td>
            <td><span class='badge badge-primary'>" . e($row['gender']) . "</span></td>
            <td>" . e($row['email']) . "</td>
            <td>" . e($row['contact']) . "</td>
            <td><code>" . e($row['national_id']) . "</code></td>
            </tr>";
        }
        echo "</tbody></table></div>";
        break;

    // ================== APPOINTMENTS ==================
    case 'appointments':
        $result = $con->query("SELECT a.ID, p.fname, p.lname, p.national_id, a.doctor, a.appdate, a.apptime, a.userStatus, a.doctorStatus 
                               FROM appointmenttb a 
                               JOIN patreg p ON a.pid = p.pid 
                               ORDER BY a.appdate DESC, a.apptime DESC");
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-calendar-check mr-3'></i>Appointment Management</h1>
                <p>View and manage all patient appointments</p>
              </div>";
        echo "<div class='table-container'>
                <table class='table table-striped table-hover'>
                <thead><tr>
                <th>ID</th><th>Patient Name</th><th>National ID</th><th>Doctor</th><th>Date</th><th>Time</th><th>User Status</th><th>Doctor Status</th>
                </tr></thead><tbody>";
        while($row = $result->fetch_assoc()){
            $userStatus = $row['userStatus'] == 1 ? '<span class="badge badge-success">Confirmed</span>' : '<span class="badge badge-warning">Pending</span>';
            $doctorStatus = $row['doctorStatus'] == 1 ? '<span class="badge badge-success">Approved</span>' : '<span class="badge badge-warning">Pending</span>';
            
            echo "<tr>
            <td><strong>" . e($row['ID']) . "</strong></td>
            <td>" . e($row['fname']) . " " . e($row['lname']) . "</td>
            <td><code>" . e($row['national_id']) . "</code></td>
            <td><span class='badge badge-info'>" . e($row['doctor']) . "</span></td>
            <td><strong>" . e($row['appdate']) . "</strong></td>
            <td>" . e($row['apptime']) . "</td>
            <td>{$userStatus}</td>
            <td>{$doctorStatus}</td>
            </tr>";
        }
        echo "</tbody></table></div>";
        break;

    // ================== PAYMENT ==================
    case 'payment':
        $checkTable = $con->query("SHOW TABLES LIKE 'paymenttb'");
        if ($checkTable->num_rows === 0) {
            echo "<div class='alert alert-danger m-4'>Error: Payment table 'paymenttb' not found in database.</div>";
            break;
        }
        $result = $con->query("SELECT * FROM paymenttb ORDER BY pay_date DESC");
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-dollar-sign mr-3'></i>Payment Management</h1>
                <p>Track and manage all payment records</p>
              </div>";
        echo "<div class='table-container'>
                <table class='table table-striped table-hover'>
                <thead><tr>
                <th>ID</th><th>Patient Name</th><th>National ID</th><th>Doctor</th><th>Fees</th><th>Payment Date</th><th>Status</th>
                </tr></thead><tbody>";
        while($row = $result->fetch_assoc()){
            $statusBadge = $row['pay_status'] === 'Paid' ? 'badge-success' : 'badge-warning';
            echo "<tr>
            <td><strong>" . e($row['id']) . "</strong></td>
            <td>" . e($row['patient_name']) . "</td>
            <td><code>" . e($row['national_id']) . "</code></td>
            <td><span class='badge badge-info'>" . e($row['doctor']) . "</span></td>
            <td><strong>LKR " . number_format($row['fees'], 2) . "</strong></td>
            <td>" . e($row['pay_date']) . "</td>
            <td><span class='badge {$statusBadge}'>" . e($row['pay_status']) . "</span></td>
            </tr>";
        }
        echo "</tbody></table></div>";
        break;

    // ================== SCHEDULE ==================
    case 'schedule':
        $result = $con->query("SELECT * FROM scheduletb ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), shift");
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-calendar-alt mr-3'></i>Staff Schedule</h1>
                <p>View staff schedules and shifts</p>
              </div>";
        echo "<div class='table-container'>
                <table class='table table-striped table-hover'>
                <thead><tr>
                <th>ID</th><th>Staff Name</th><th>Role</th><th>Day</th><th>Shift</th>
                </tr></thead><tbody>";
        while($row = $result->fetch_assoc()){
            $shift = strtolower($row['shift']);
            $shiftBadge = match($shift) {
                'morning' => 'badge-primary',
                'evening' => 'badge-warning',
                default => 'badge-secondary'
            };
            echo "<tr>
            <td><strong>" . e($row['id']) . "</strong></td>
            <td>" . e($row['staff_name']) . "</td>
            <td><span class='badge badge-info'>" . e($row['role']) . "</span></td>
            <td><strong>" . e($row['day']) . "</strong></td>
            <td><span class='badge {$shiftBadge}'>" . e($row['shift']) . "</span></td>
            </tr>";
        }
        echo "</tbody></table></div>";
        break;

    // ================== STAFF ==================
    case 'staff':
        $result = $con->query("SELECT * FROM stafftb");
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-users mr-3'></i>Staff Management</h1>
                <p>View hospital staff information</p>
              </div>";
        echo "<div class='table-container'>
                <table class='table table-striped table-hover'>
                <thead><tr>
                <th>Name</th><th>Role</th><th>Email</th><th>Contact</th>
                </tr></thead><tbody>";
        while($row = $result->fetch_assoc()){
            echo "<tr>
            <td><strong>" . e($row['name']) . "</strong></td>
            <td><span class='badge badge-primary'>" . e($row['role']) . "</span></td>
            <td>" . e($row['email']) . "</td>
            <td>" . e($row['contact']) . "</td>
            </tr>";
        }
        echo "</tbody></table></div>";
        break;

    // ================== SETTINGS ==================
    case 'settings':
        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-cog mr-3'></i>Settings</h1>
                <p>System configuration and preferences</p>
              </div>
              <div class='table-container text-center py-5'>
                <i class='fas fa-tools fa-4x text-muted mb-3'></i>
                <h3 class='text-muted'>Settings Panel Coming Soon</h3>
                <p class='text-muted'>This feature is currently under development</p>
              </div>";
        break;

    // ================== DASHBOARD (DEFAULT) ==================
    default:
        $appointments_count = $con->query("SELECT COUNT(*) as total FROM appointmenttb")->fetch_assoc()['total'];
        $patients_count = $con->query("SELECT COUNT(*) as total FROM patreg")->fetch_assoc()['total'];
        $staff_count = $con->query("SELECT COUNT(*) as total FROM stafftb")->fetch_assoc()['total'];
        $sessions_count = $con->query("SELECT COUNT(*) as total FROM appointmenttb WHERE appdate = CURDATE()")->fetch_assoc()['total'];
        $doctors_count = $con->query("SELECT COUNT(*) as total FROM doctb")->fetch_assoc()['total'];
        
        $payments_today = 0;
        if ($con->query("SHOW TABLES LIKE 'paymenttb'")->num_rows > 0) {
            $payments_today = $con->query("SELECT COUNT(*) as total FROM paymenttb WHERE pay_date = CURDATE()")->fetch_assoc()['total'];
        }

        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-tachometer-alt mr-3'></i>Reception Dashboard</h1>
                <p>Welcome back! Here's your overview of hospital activities</p>
              </div>";

        echo "<div class='stats-row'>
                <div class='row'>
                    <div class='col-md-4 mb-4'>
                        <div class='card'>
                            <i class='fas fa-user-injured'></i>
                            <h3>{$patients_count}</h3>
                            <p>Total Patients</p>
                        </div>
                    </div>
                    <div class='col-md-4 mb-4'>
                        <div class='card'>
                            <i class='fas fa-calendar-check'></i>
                            <h3>{$appointments_count}</h3>
                            <p>Total Appointments</p>
                        </div>
                    </div>
                    <div class='col-md-4 mb-4'>
                        <div class='card'>
                            <i class='fas fa-users'></i>
                            <h3>{$staff_count}</h3>
                            <p>Staff Members</p>
                        </div>
                    </div>
                </div>
                <div class='row'>
                    <div class='col-md-4 mb-4'>
                        <div class='card'>
                            <i class='fas fa-user-md'></i>
                            <h3>{$doctors_count}</h3>
                            <p>Doctors</p>
                        </div>
                    </div>
                    <div class='col-md-4 mb-4'>
                        <div class='card'>
                            <i class='fas fa-calendar-day'></i>
                            <h3>{$sessions_count}</h3>
                            <p>Today's Sessions</p>
                        </div>
                    </div>
                    <div class='col-md-4 mb-4'>
                        <div class='card'>
                            <i class='fas fa-dollar-sign'></i>
                            <h3>{$payments_today}</h3>
                            <p>Today's Payments</p>
                        </div>
                    </div>
                </div>
              </div>";
        break;
}
?>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>