<?php
session_start();
include('dbconnection.php');

if(isset($_POST['patientlogin'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    
    $query = "SELECT * FROM patreg WHERE email='$email' AND password='$password'";
    $result = mysqli_query($con, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $patient = mysqli_fetch_assoc($result);
        $_SESSION['patient'] = $email;
        $_SESSION['patient_id'] = $patient['pid'];
        $_SESSION['patient_name'] = $patient['fname'] . ' ' . $patient['lname'];
        
        header("Location: patient_dashboard.php");
        exit();
    } else {
        echo "<script>
            alert('Invalid email or password! Please try again.');
            window.location.href = 'index.php#patient-login';
        </script>";
        exit();
    }
    
    mysqli_close($con);
}
?>