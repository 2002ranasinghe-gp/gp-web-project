<?php
session_start();

$con = mysqli_connect("localhost", "root", "", "myhmsdb");
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['reception'])) {
    header("Location: index.php");
    exit();
}

$page = $_GET['page'] ?? 'dashboard';
$patient_msg = "";

// ===========================
// ADD PATIENT FROM RECEPTION
// ===========================
if (isset($_POST['add_patient'])) {
    $fname = mysqli_real_escape_string($con, $_POST['fname']);
    $lname = mysqli_real_escape_string($con, $_POST['lname']);
    $gender = mysqli_real_escape_string($con, $_POST['gender']);
    $dob = mysqli_real_escape_string($con, $_POST['dob']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $contact = mysqli_real_escape_string($con, $_POST['contact']);
    $address = isset($_POST['address']) ? mysqli_real_escape_string($con, $_POST['address']) : '';
    $emergencyContact = isset($_POST['emergencyContact']) ? mysqli_real_escape_string($con, $_POST['emergencyContact']) : '';
    $nic_input = mysqli_real_escape_string($con, $_POST['nic']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $cpassword = isset($_POST['cpassword']) ? mysqli_real_escape_string($con, $_POST['cpassword']) : '';

    // Password match check
    if ($password !== $cpassword) {
        $patient_msg = "<div class='alert alert-danger'>❌ Passwords do not match!</div>";
    } else {
        // Format NIC: keep only numbers and prefix with NIC
        $nicNumbers = preg_replace('/[^0-9]/', '', $nic_input);
        $national_id = 'NIC' . $nicNumbers;

        // Check email exists
        $check_email = mysqli_query($con, "SELECT * FROM patreg WHERE email='$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $patient_msg = "<div class='alert alert-danger'>❌ Patient with this email already exists!</div>";
        } else {
            // Check NIC exists
            $check_nic = mysqli_query($con, "SELECT * FROM patreg WHERE national_id='$national_id'");
            if (mysqli_num_rows($check_nic) > 0) {
                $patient_msg = "<div class='alert alert-danger'>❌ Patient with this NIC already exists!</div>";
            } else {
                // Insert patient (plain text password for now)
                $query = "INSERT INTO patreg (fname, lname, gender, dob, email, contact, address, emergencyContact, national_id, password)
                          VALUES ('$fname', '$lname', '$gender', '$dob', '$email', '$contact', '$address', '$emergencyContact', '$national_id', '$password')";
                if (mysqli_query($con, $query)) {
                    $new_patient_id = mysqli_insert_id($con);
                    $patient_msg = "<div class='alert alert-success'>✅ Patient registered successfully!<br>Patient ID: $new_patient_id | NIC: $national_id</div>";
                    // Clear form via JS
                    echo "<script>document.getElementById('add-patient-form').reset();</script>";
                } else {
                    $patient_msg = "<div class='alert alert-danger'>❌ Database Error: " . mysqli_error($con) . "</div>";
                }
            }
        }
    }
}

// Helper to prevent XSS
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
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<style>
/* [Your existing CSS from previous reception_dashboard.php] */
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
    margin:0; padding:0; min-height: 100vh;
    color: var(--text-dark);
}
.navbar { 
    background: var(--primary-blue); 
    padding:0.8rem 1rem; 
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.navbar .navbar-brand { 
    font-weight:bold; color:var(--white); font-size: 1.5rem;
}
.navbar .welcome { 
    margin-left:auto; color:var(--white); font-weight: 500;
}
.navbar .welcome a { 
    color:var(--light-blue); text-decoration:none; margin-left:10px;
    transition: color 0.3s;
}
.navbar .welcome a:hover { color: var(--white); }

.sidebar { 
    width:250px; background: var(--primary-blue); height:100vh; 
    position:fixed; top:0; left:0; padding-top:80px; 
    transition:all 0.3s; box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}
.sidebar ul { list-style:none; padding:0; margin:0; }
.sidebar ul li { 
    padding:15px 25px; color:var(--white); cursor:pointer; 
    transition:all 0.3s; border-left: 4px solid transparent;
    margin: 5px 10px; border-radius: 0 8px 8px 0;
}
.sidebar ul li i { margin-right:12px; width:20px; text-align:center; }
.sidebar ul li:hover, .sidebar ul li.active { 
    background: var(--dark-blue); border-left: 4px solid var(--medium-blue);
    transform: translateX(5px);
}
.sidebar ul li a { color:var(--white); text-decoration:none; display:block; font-weight:500; }

.main { margin-left:250px; padding:30px; min-height: calc(100vh - 80px); }

.card { 
    background: var(--white); color: var(--text-dark); border-radius:15px; 
    padding:25px; text-align:center; box-shadow: 0 8px 25px rgba(30, 136, 229, 0.15);
    transition: all 0.3s ease; border: none; height: 100%;
    border-left: 5px solid var(--accent-blue);
}
.card i { margin-bottom:15px; font-size: 2.5rem; color: var(--primary-blue); }
.card:hover { transform: translateY(-8px); box-shadow: 0 12px 35px rgba(30, 136, 229, 0.2); }
.card h3 { font-size: 2rem; font-weight: bold; margin: 10px 0; color: var(--primary-blue); }
.card p { font-size: 1rem; color: var(--text-light); margin: 0; }

.table-container, .form-card { 
    background:var(--white); padding:25px; border-radius:15px; 
    box-shadow: 0 8px 25px rgba(30, 136, 229, 0.1); margin-top:20px;
}
.form-card-header {
    background: var(--primary-blue);
    color: white;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0;
    margin: -25px -25px 20px -25px;
}
.dashboard-header {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
    border-radius: 15px; padding: 40px; margin-bottom: 30px; color: white;
    text-align: center;
}
.dashboard-header h1 { font-size: 2.5rem; margin-bottom: 10px; }
.dashboard-header p { font-size: 1.2rem; opacity: 0.9; }

@media (max-width: 768px) {
    .sidebar { width: 70px; padding-top: 70px; }
    .sidebar ul li span { display: none; }
    .sidebar ul li i { margin-right: 0; font-size: 1.2rem; }
    .main { margin-left: 70px; padding: 15px; }
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <a class="navbar-brand" href="#">
        <i class="fas fa-hospital-alt mr-2"></i>HEALTHCARE 
    </a>
    <div class="welcome">
        <i class="fas fa-user-circle mr-2"></i>Welcome, <?php echo e($_SESSION['reception']); ?> 
        | <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
    </div>
</nav>

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

<div class="main">
<?php
switch($page){
    case 'patients':
        // Fetch patients
        $result = $con->query("SELECT * FROM patreg ORDER BY pid DESC");

        echo "<div class='dashboard-header'>
                <h1><i class='fas fa-user-injured mr-3'></i>Patient Management</h1>
                <p>Register new patients or view existing records</p>
              </div>";

        // === NEW: ADD PATIENT FORM ===
        echo "<div class='form-card'>
                <div class='form-card-header'>
                    <h5 class='mb-0'><i class='fas fa-user-plus mr-2'></i>Register New Patient</h5>
                </div>
                {$patient_msg}
                <form method='POST' id='add-patient-form'>
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label>First Name *</label>
                                <input type='text' class='form-control' name='fname' required>
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label>Last Name *</label>
                                <input type='text' class='form-control' name='lname' required>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label>Gender *</label>
                                <select class='form-control' name='gender' required>
                                    <option value=''>Select</option>
                                    <option value='Male'>Male</option>
                                    <option value='Female'>Female</option>
                                </select>
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label>Date of Birth *</label>
                                <input type='date' class='form-control' name='dob' required>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label>Email *</label>
                                <input type='email' class='form-control' name='email' required>
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label>Contact *</label>
                                <input type='tel' class='form-control' name='contact' required>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label>National ID (NIC) *</label>
                                <input type='text' class='form-control' name='nic' placeholder='e.g., 123456789V or 200012345678' required>
                                <small class='text-muted'>Enter NIC without spaces or dashes</small>
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label>Password *</label>
                                <input type='password' class='form-control' name='password' minlength='6' required>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label>Confirm Password *</label>
                                <input type='password' class='form-control' name='cpassword' required>
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label>Address</label>
                                <textarea class='form-control' name='address' rows='1'></textarea>
                            </div>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label>Emergency Contact</label>
                        <input type='text' class='form-control' name='emergencyContact'>
                    </div>
                    <button type='submit' name='add_patient' class='btn btn-success'>
                        <i class='fas fa-user-plus mr-1'></i> Register Patient
                    </button>
                </form>
              </div>";

        // === PATIENTS LIST ===
        echo "<div class='table-container'>
                <table class='table table-striped table-hover'>
                <thead><tr>
                <th>ID</th><th>First Name</th><th>Last Name</th><th>Gender</th><th>Email</th><th>Contact</th><th>NIC</th>
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

    // ... [Other cases: appointments, payment, schedule, staff, settings, dashboard remain unchanged] ...

    default: // Dashboard
        // [Keep your existing dashboard stats code here]
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

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script>
// Auto-format NIC input (optional enhancement)
document.querySelector('input[name="nic"]')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/[^0-9Vv]/g, '');
    e.target.value = value.toUpperCase();
});
</script>
</body>
</html>