<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['reception'])) { header("Location: reception_dashboard.php"); exit(); }
if (isset($_SESSION['doctor'])) { header("Location: doctor_dashboard.php"); exit(); }
if (isset($_SESSION['admin'])) { header("Location: admin_dashboard.php"); exit(); }
if (isset($_SESSION['patient'])) { header("Location: patient_dashboard.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HMS - Registration/Login</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css?family=IBM+Plex+Sans&display=swap" rel="stylesheet">

<!-- Bootstrap & FontAwesome -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
<link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">

<style>
body { 
    font-family: 'IBM Plex Sans', sans-serif; 
    margin: 0; 
    min-height: 100vh; 
    background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('images/10.jpg') no-repeat center center fixed; 
    background-size: cover; 
    display: flex; 
    justify-content: center; 
    align-items: center; 
    padding: 30px 0; 
}
.navbar { 
    background: #1976d2; 
    padding: 0.8rem 1rem; 
}
.navbar-brand { 
    color: #fff; 
    font-weight: 700; 
    display: flex; 
    align-items: center; 
}
.navbar-brand img { 
    height: 45px; 
    width: 45px; 
    margin-right: 10px; 
    border-radius: 50%; 
    background: #fff; 
    padding: 3px; 
}
.container.register { 
    background: rgba(255,255,255,0.96); 
    border-top-left-radius: 12rem; 
    border-bottom-right-radius: 12rem; 
    box-shadow: 0 8px 20px rgba(0,0,0,0.18); 
    padding: 40px; 
    max-width: 1000px; 
    width: 95%; 
    display: flex; 
    z-index: 1; 
}
.register-left { 
    flex: 1; 
    text-align: center; 
    padding: 20px; 
}
.register-left img { 
    max-width: 220px; 
    border-radius: .8rem; 
    box-shadow: 0 6px 18px rgba(0,0,0,0.12); 
}
.register-left h2 { 
    margin-top: 20px; 
    font-weight: 700; 
    color: #1976d2; 
}
.register-left .small-note { 
    font-size: 0.9rem; 
    color: #666; 
    margin-top: 8px; 
    text-align: center; 
}
.register-right { 
    flex: 1.2; 
    padding: 10px 25px; 
}
.register-heading { 
    color: #0d47a1; 
    font-weight: 700; 
    text-align: center; 
    margin-bottom: 20px; 
}
.nav-tabs .nav-link { 
    color: #1976d2; 
    font-weight: 600; 
    border-radius: 1rem; 
    margin: 0 5px; 
}
.nav-tabs .nav-link.active { 
    background: #1976d2; 
    color: #fff !important; 
}
.register-form .btnRegister { 
    background: linear-gradient(90deg, #1976d2, #0d47a1); 
    color: #fff; 
    border-radius: 50px; 
    padding: 10px 28px; 
}
#message {
    font-size: 0.85rem;
    margin-top: 5px;
    display: block;
}
@media(max-width:767px){ 
    .container.register { 
        flex-direction: column; 
        border-radius: 1rem; 
    } 
    .register-left { 
        display: none; 
    } 
    .register-right { 
        padding: 15px; 
    } 
}
</style>

<script>
function check() { 
    var p = document.getElementById('password'); 
    var cp = document.getElementById('cpassword'); 
    if(p && cp) { 
        if(p.value === cp.value) { 
            document.getElementById('message').style.color = '#5dd05d'; 
            document.getElementById('message').innerHTML = 'Matched'; 
        } else { 
            document.getElementById('message').style.color = '#f55252'; 
            document.getElementById('message').innerHTML = 'Not Matching'; 
        } 
    } 
}

function alphaOnly(event) { 
    var key = event.keyCode; 
    return ((key >= 65 && key <= 90) || (key >= 97 && key <= 122) || key == 8 || key == 32); 
}

function checklen() { 
    var pass = document.getElementById("password"); 
    if(pass && pass.value.length < 6) { 
        alert("Password must be at least 6 characters long."); 
        return false; 
    } 
    
    // Check if National ID is provided and has proper format
    var nationalId = document.getElementById("national_id");
    if(nationalId && nationalId.value.trim() === "") {
        alert("National ID is required.");
        return false;
    }
    
    return true; 
}

function validateNationalId(event) {
    var key = event.keyCode;
    // Allow numbers, backspace, and delete
    return ((key >= 48 && key <= 57) || (key >= 96 && key <= 105) || key == 8 || key == 46);
}
</script>
</head>

<body>
<!-- ===== Navbar ===== -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container">
    <a class="navbar-brand" href="#">
      <img src="images/medical-logo - Copy.jpg" alt="Hospital Logo">
      <span><i class="fa fa-user-plus"></i> HEALTHCARE HOSPITAL</span>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarResponsive">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
        <li class="nav-item"><a class="nav-link" href="services.html">ABOUT US</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.html">CONTACT</a></li>
      </ul>
    </div>
  </div>
</nav>

<div style="height:70px"></div> <!-- spacer for fixed navbar -->

<div class="container register">
  <div class="row w-100 no-gutters">
    <!-- Left image -->
    <div class="col-md-5 register-left d-none d-md-block">
      <img src="images/14 - Copy.png" alt="Welcome Image">
      <h2>Welcome</h2>
      <p class="small-note">At HealthcareHospital, we are constantly committed to your health.</p>
    </div>

    <div class="col-md-7 register-right">
      <ul class="nav nav-tabs nav-justified mb-3" id="myTab" role="tablist" style="width:100%;">
        <li class="nav-item"><a class="nav-link active" id="patient-register-tab" data-toggle="tab" href="#patient-register" role="tab">Patient Register</a></li>
        <li class="nav-item"><a class="nav-link" id="patient-login-tab" data-toggle="tab" href="#patient-login" role="tab">Patient Login</a></li>
        <li class="nav-item"><a class="nav-link" id="doctor-tab" data-toggle="tab" href="#doctor" role="tab">Doctor</a></li>
        <li class="nav-item"><a class="nav-link" id="admin-tab" data-toggle="tab" href="#admin" role="tab">Admin</a></li>
        <li class="nav-item"><a class="nav-link" id="reception-tab" data-toggle="tab" href="#reception" role="tab">Reception</a></li>
      </ul>

      <div class="tab-content" id="myTabContent">
        <!-- Patient Register -->
        <div class="tab-pane fade show active" id="patient-register" role="tabpanel">
          <h3 class="register-heading">Register as Patient</h3>
          <form method="post" action="func2.php" onsubmit="return checklen();">
            <div class="form-row">
              <div class="form-group col-md-6">
                <input type="text" class="form-control" name="fname" placeholder="First Name *" onkeydown="return alphaOnly(event);" required>
              </div>
              <div class="form-group col-md-6">
                <input type="text" class="form-control" name="lname" placeholder="Last Name *" onkeydown="return alphaOnly(event);" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <input type="email" class="form-control" name="email" placeholder="Your Email *" required>
              </div>
              <div class="form-group col-md-6">
                <input type="tel" class="form-control" name="contact" placeholder="Your Phone *" minlength="10" maxlength="10" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <input type="text" class="form-control" id="national_id" name="national_id" placeholder="National ID *" onkeydown="return validateNationalId(event);" required>
              </div>
              <div class="form-group col-md-6">
                <div class="form-group">
                  <label class="radio-inline"><input type="radio" name="gender" value="Male" checked> Male</label> 
                  <label class="radio-inline"><input type="radio" name="gender" value="Female"> Female</label>
                </div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password *" onkeyup="check();" required>
              </div>
              <div class="form-group col-md-6">
                <input type="password" class="form-control" id="cpassword" name="cpassword" placeholder="Confirm Password *" onkeyup="check();" required>
                <span id="message"></span>
              </div>
            </div>
            <div class="text-center">
              <input type="submit" class="btn btn-primary btnRegister" name="patsub1" value="Register">
            </div>
          </form>
        </div>

        <!-- Patient Login -->
        <div class="tab-pane fade" id="patient-login" role="tabpanel">
          <h3 class="register-heading">Login as Patient</h3>
          <form method="post" action="patient_login.php">
            <div class="form-row">
              <div class="form-group col-md-6">
                <input type="email" name="email" class="form-control" placeholder="Your Email *" required>
              </div>
              <div class="form-group col-md-6">
                <input type="password" name="password" class="form-control" placeholder="Password *" required>
              </div>
            </div>
            <div class="text-center">
              <input type="submit" class="btn btn-primary btnRegister" name="patientlogin" value="Login">
            </div>
          </form>
        </div>

        <!-- Doctor Login -->
        <div class="tab-pane fade" id="doctor" role="tabpanel">
          <h3 class="register-heading">Login as Doctor</h3>
          <form method="post" action="func1.php">
            <div class="form-row">
              <div class="form-group col-md-6">
                <input type="text" name="username3" class="form-control" placeholder="User Name *" required>
              </div>
              <div class="form-group col-md-6">
                <input type="password" name="password3" class="form-control" placeholder="Password *" required>
              </div>
            </div>
            <div class="text-center">
              <input type="submit" class="btn btn-primary btnRegister" name="docsub1" value="Login">
            </div>
          </form>
        </div>

        <!-- Admin Login -->
        <div class="tab-pane fade" id="admin" role="tabpanel">
          <h3 class="register-heading">Login as Admin</h3>
          <form method="post" action="func3.php">
            <div class="form-row">
              <div class="form-group col-md-6">
                <input type="text" name="username1" class="form-control" placeholder="User Name *" required>
              </div>
              <div class="form-group col-md-6">
                <input type="password" name="password2" class="form-control" placeholder="Password *" required>
              </div>
            </div>
            <div class="text-center">
              <input type="submit" class="btn btn-primary btnRegister" name="adsub" value="Login">
            </div>
          </form>
        </div>

        <!-- Reception Login -->
        <div class="tab-pane fade" id="reception" role="tabpanel">
          <h3 class="register-heading">Login as Reception</h3>
          <form method="post" action="func4.php">
            <div class="form-row">
              <div class="form-group col-md-6">
                <input type="text" name="username" class="form-control" placeholder="User Name *" required>
              </div>
              <div class="form-group col-md-6">
                <input type="password" name="password" class="form-control" placeholder="Password *" required>
              </div>
            </div>
            <div class="text-center">
              <input type="submit" class="btn btn-primary btnRegister" name="receptsub" value="Login">
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>