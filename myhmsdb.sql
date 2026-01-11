<!DOCTYPE html>
<?php
//===========================
// Database Connection
//===========================
$con = mysqli_connect("localhost","root","","myhmsdb");
if(!$con){
    die("Database connection failed: ".mysqli_connect_error());
}

//===========================
// Messages
//===========================
$msg = "";

//===========================
// Add Doctor
//===========================
if(isset($_POST['docsub'])){
    $doctor = mysqli_real_escape_string($con, $_POST['doctor']);
    $special = mysqli_real_escape_string($con, $_POST['special']);
    $demail = mysqli_real_escape_string($con, $_POST['demail']);
    $dpassword = password_hash($_POST['dpassword'], PASSWORD_DEFAULT);
    $docFees = mysqli_real_escape_string($con, $_POST['docFees']);

    $check = mysqli_query($con, "SELECT * FROM doctb WHERE email='$demail'");
    if(mysqli_num_rows($check) > 0){
        $msg = "Doctor with this email already exists!";
    } else {
        $insert = mysqli_query($con, "INSERT INTO doctb(username,spec,email,password,docFees) VALUES('$doctor','$special','$demail','$dpassword','$docFees')");
        $msg = $insert ? "Doctor added successfully!" : "Error adding doctor: ".mysqli_error($con);
    }
}

//===========================
// Delete Doctor
//===========================
if(isset($_POST['docsub1'])){
    $demail = mysqli_real_escape_string($con, $_POST['demail']);
    $check = mysqli_query($con, "SELECT * FROM doctb WHERE email='$demail'");
    if(mysqli_num_rows($check) == 0){
        $msg = "No doctor found with this email!";
    } else {
        $delete = mysqli_query($con, "DELETE FROM doctb WHERE email='$demail'");
        $msg = $delete ? "Doctor deleted successfully!" : "Error deleting doctor: ".mysqli_error($con);
    }
}

//===========================
// View Payments
//===========================
$payments_result = null;
if(isset($_POST['viewpaysub'])){
    $pid = mysqli_real_escape_string($con, $_POST['pid']);
    $payments_result = mysqli_query($con, "SELECT * FROM paymenttb WHERE pid='$pid'");
}
?>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel - Heth Care Hospital</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
.bg-primary { background: linear-gradient(to right, #3931af, #00c6ff); }
.navbar-brand { font-weight: bold; }
.list-group-item.active { background-color: #342ac1; border-color: #007bff; color: #fff; }
.tab-content { overflow-x:auto; }
.table { width:100%; }
</style>
<script>
function checkPassword() {
    let pass = document.getElementById('dpassword').value;
    let cpass = document.getElementById('cdpassword').value;
    if(pass === cpass){
        document.getElementById('message').style.color = '#5dd05d';
        document.getElementById('message').innerText = 'Matched';
    } else {
        document.getElementById('message').style.color = '#f55252';
        document.getElementById('message').innerText = 'Not Matching';
    }
}
function alphaOnly(event){
    let key = event.keyCode;
    return ((key >= 65 && key <= 90) || (key >= 97 && key <= 122) || key == 8 || key == 32);
}
function clickDiv(id){ document.querySelector(id).click(); }
</script>
</head>
<body style="padding-top:70px;">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
  <a class="navbar-brand" href="#"><i class="fa fa-user-plus"></i> Heth Care Hospital</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ml-auto">
          <li class="nav-item"><a class="nav-link" href="logout1.php"><i class="fa fa-sign-out"></i> Logout</a></li>
      </ul>
  </div>
</nav>

<div class="container-fluid">
<h3 class="text-center mb-4">WELCOME ADMIN</h3>
<div class="row">
    <div class="col-md-3">
        <div class="list-group" id="list-tab" role="tablist">
            <a class="list-group-item list-group-item-action active" data-toggle="list" href="#dash-tab">Dashboard</a>
            <a class="list-group-item list-group-item-action" data-toggle="list" href="#doc-tab">Doctor Details</a>
            <a class="list-group-item list-group-item-action" data-toggle="list" href="#pat-tab">Patient Details</a>
            <a class="list-group-item list-group-item-action" data-toggle="list" href="#app-tab">Appointments</a>
            <a class="list-group-item list-group-item-action" data-toggle="list" href="#pres-tab">Prescriptions</a>
            <a class="list-group-item list-group-item-action" data-toggle="list" href="#add-doc-tab">Add Doctor</a>
            <a class="list-group-item list-group-item-action" data-toggle="list" href="#del-doc-tab">Delete Doctor</a>
            <a class="list-group-item list-group-item-action" data-toggle="list" href="#pay-tab">View Payments</a>
        </div>
    </div>

    <div class="col-md-9">
        <div class="tab-content">

            <!-- Dashboard -->
            <div class="tab-pane fade show active" id="dash-tab">
                <h4>Admin Dashboard</h4>
                <?php if($msg!="") echo "<div class='alert alert-info'>$msg</div>"; ?>
            </div>

            <!-- Doctor Details -->
            <div class="tab-pane fade" id="doc-tab">
                <h4>Doctors List</h4>
                <table class="table table-hover table-bordered">
                    <thead><tr><th>Name</th><th>Specialization</th><th>Email</th><th>Fees</th></tr></thead>
                    <tbody>
                    <?php
                    $res = mysqli_query($con, "SELECT * FROM doctb");
                    if(mysqli_num_rows($res) > 0){
                        while($r=mysqli_fetch_assoc($res)){
                            echo "<tr><td>{$r['username']}</td><td>{$r['spec']}</td><td>{$r['email']}</td><td>{$r['docFees']}</td></tr>";
                        }
                    } else { echo "<tr><td colspan='4' class='text-center'>No doctors found</td></tr>"; }
                    ?>
                    </tbody>
                </table>
            </div>

            <!-- Patient Details -->
            <div class="tab-pane fade" id="pat-tab">
                <h4>Patients List</h4>
                <table class="table table-hover table-bordered">
                    <thead><tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Gender</th><th>Email</th><th>Contact</th></tr></thead>
                    <tbody>
                    <?php
                    $res = mysqli_query($con, "SELECT * FROM patreg");
                    while($r=mysqli_fetch_assoc($res)){
                        echo "<tr><td>{$r['pid']}</td><td>{$r['fname']}</td><td>{$r['lname']}</td><td>{$r['gender']}</td><td>{$r['email']}</td><td>{$r['contact']}</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <!-- Appointments -->
            <div class="tab-pane fade" id="app-tab">
                <h4>Appointments</h4>
                <table class="table table-hover table-bordered">
                    <thead><tr><th>ID</th><th>Patient ID</th><th>Doctor</th><th>Fees</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php
                    $res = mysqli_query($con, "SELECT * FROM appointmenttb");
                    while($r=mysqli_fetch_assoc($res)){
                        $status = ($r['userStatus']==1 && $r['doctorStatus']==1) ? "Active" : (($r['userStatus']==0)?"Cancelled by Patient":"Cancelled by Doctor");
                        echo "<tr><td>{$r['ID']}</td><td>{$r['pid']}</td><td>{$r['doctor']}</td><td>{$r['docFees']}</td><td>{$r['appdate']}</td><td>{$r['apptime']}</td><td>$status</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <!-- Prescriptions -->
            <div class="tab-pane fade" id="pres-tab">
                <h4>Prescriptions</h4>
                <table class="table table-hover table-bordered">
                    <thead><tr><th>Doctor</th><th>Patient ID</th><th>Date</th><th>Prescription</th></tr></thead>
                    <tbody>
                    <?php
                    $res = mysqli_query($con, "SELECT * FROM prestb");
                    while($r=mysqli_fetch_assoc($res)){
                        echo "<tr><td>{$r['doctor']}</td><td>{$r['pid']}</td><td>{$r['appdate']}</td><td>{$r['prescription']}</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Doctor -->
            <div class="tab-pane fade" id="add-doc-tab">
                <h4>Add Doctor</h4>
                <form method="post">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="doctor" class="form-control" onkeydown="return alphaOnly(event)" required>
                    </div>
                    <div class="form-group">
                        <label>Specialization</label>
                        <input type="text" name="special" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="demail" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="dpassword" name="dpassword" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" id="cdpassword" class="form-control" onkeyup="checkPassword()" required>
                        <small id="message"></small>
                    </div>
                    <div class="form-group">
                        <label>Fees (Rs.)</label>
                        <input type="number" name="docFees" class="form-control" required>
                    </div>
                    <input type="submit" name="docsub" class="btn btn-success" value="Add Doctor">
                </form>
            </div>

            <!-- Delete Doctor -->
            <div class="tab-pane fade" id="del-doc-tab">
                <h4>Delete Doctor</h4>
                <form method="post">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="demail" class="form-control" required>
                    </div>
                    <input type="submit" name="docsub1" class="btn btn-danger" value="Delete Doctor">
                </form>
            </div>

            <!-- View Payments -->
            <div class="tab-pane fade" id="pay-tab">
                <h4>Payments</h4>
                <form method="post" class="form-inline mb-2">
                    <input type="number" name="pid" class="form-control mr-2" placeholder="Enter Patient ID" required>
                    <input type="submit" name="viewpaysub" class="btn btn-success" value="View Payments">
                </form>
                <?php
                if($payments_result){
                    if(mysqli_num_rows($payments_result) > 0){
                        echo "<table class='table table-bordered table-hover'><thead><tr>
                            <th>Payment ID</th><th>Appointment ID</th><th>Patient Name</th>
                            <th>Doctor</th><th>Fees (Rs.)</th><th>Date</th><th>Status</th>
                        </tr></thead><tbody>";
                        while($r = mysqli_fetch_assoc($payments_result)){
                            echo "<tr><td>{$r['id']}</td><td>{$r['app_id']}</td><td>{$r['patient_name']}</td>
                            <td>{$r['doctor']}</td><td>{$r['fees']}</td><td>{$r['pay_date']}</td><td>{$r['pay_status']}</td></tr>";
                        }
                        echo "</tbody></table>";
                    } else {
                        echo "<div class='alert alert-warning'>No payment records found for this Patient ID</div>";
                    }
                }
                ?>
            </div>

        </div>
    </div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>
