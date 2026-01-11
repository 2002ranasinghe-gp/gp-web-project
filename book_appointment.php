
<?php
session_start();
if(!isset($_SESSION['patient'])){
    header("Location: index.php");
    exit();
}

include('dbconnection.php');

if(isset($_POST['book_appointment'])) {
    $pid = $_SESSION['patient_id'];
    $email = $_SESSION['email'];
    $fname = $_SESSION['fname'];
    $lname = $_SESSION['lname'];
    $national_id = $_SESSION['national_id'];
    $contact = $_SESSION['contact'];
    $gender = $_SESSION['gender'];
    $doctor = mysqli_real_escape_string($con, $_POST['doctor']);
    $appdate = mysqli_real_escape_string($con, $_POST['appdate']);
    $apptime = mysqli_real_escape_string($con, $_POST['apptime']);
    
    // Get doctor fees
    $fee_query = "SELECT docFees FROM doctb WHERE username='$doctor'";
    $fee_result = mysqli_query($con, $fee_query);
    $doctor_data = mysqli_fetch_assoc($fee_result);
    $docFees = $doctor_data['docFees'];
    
    $query = "INSERT INTO appointmenttb (pid, national_id, fname, lname, gender, email, contact, doctor, docFees, appdate, apptime, userStatus, doctorStatus) 
              VALUES ('$pid', '$national_id', '$fname', '$lname', '$gender', '$email', '$contact', '$doctor', '$docFees', '$appdate', '$apptime', 1, 0)";
    
    if(mysqli_query($con, $query)) {
        echo "<script>alert('Appointment booked successfully!'); window.location.href='patient_dashboard.php';</script>";
    } else {
        echo "<script>alert('Error booking appointment!');</script>";
    }
}

// Get doctors list
$doctors_query = "SELECT * FROM doctb";
$doctors_result = mysqli_query($con, $doctors_query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book Appointment</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Book Appointment</h2>
        <form method="post">
            <div class="form-group">
                <label>Doctor:</label>
                <select name="doctor" class="form-control" required>
                    <option value="">Select Doctor</option>
                    <?php while($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                        <option value="<?php echo $doctor['username']; ?>">
                            Dr. <?php echo $doctor['username']; ?> - <?php echo $doctor['spec']; ?> (Rs. <?php echo $doctor['docFees']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="appdate" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>Time:</label>
                <input type="time" name="apptime" class="form-control" required>
            </div>
            <button type="submit" name="book_appointment" class="btn btn-primary">Book Appointment</button>
            <a href="patient_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
<?php mysqli_close($con); ?>