<?php
$conn = mysqli_connect("localhost", "root", "", "hospital_db");

if (isset($_POST['book_room'])) {
    $patient_name = $_POST['patient_name'];
    $patient_id   = $_POST['patient_id'];
    $address      = $_POST['address'];
    $room_type    = $_POST['room_type'];
    $room_no      = $_POST['room_no'];

    $sql = "INSERT INTO room_bookings 
            (patient_name, patient_id, address, room_type, room_no)
            VALUES 
            ('$patient_name', '$patient_id', '$address', '$room_type', '$room_no')";

    mysqli_query($conn, $sql);

    echo "<script>alert('Room Booked Successfully');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Healthcare Hospital</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
body { background: #e9f4ff; }
.header {
    background: linear-gradient(to right, #0058a3, #007bff);
    padding: 10px;
    text-align: center;
    color: white;
}
.logo {
    width: 55px;
    height: 55px;
    border-radius: 10px;
}
.section { display: none; }
.card-box {
    cursor: pointer;
    transition: .3s;
    border-radius: 20px;
    background: white;
}
.card-box:hover {
    background-color: #cfe4ff;
    transform: scale(1.05);
}
.back-btn { cursor: pointer; color: #0058a3; font-weight: bold; }
</style>
</head>

<body>

<div class="header">
    <img class="logo" src="https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcSQVGNYNJcHeoSmY8lq2WRbK_cBCBwnqtqvRWUzY67Tmx1GsmJ2">
    <h2 class="fw-bold">Healthcare Hospital</h2>
</div>

<div class="container mt-5">

<!-- HOME -->
<div id="homeSection">
    <h3 class="text-center mb-4 fw-bold">Choose Your Room</h3>
    <div class="row justify-content-center">
        <div class="col-md-4 mt-3">
            <div class="card card-box p-4 text-center" onclick="showSection('normal')">
                <i class="fa-solid fa-bed fa-3x mb-2 text-primary"></i>
                <h4>Normal Room</h4>
            </div>
        </div>
        <div class="col-md-4 mt-3">
            <div class="card card-box p-4 text-center" onclick="showSection('vip')">
                <i class="fa-solid fa-crown fa-3x mb-2 text-warning"></i>
                <h4>VIP Room</h4>
            </div>
        </div>
    </div>
</div>

<!-- NORMAL ROOM -->
<div id="normalRoom" class="section mt-5">
<p class="back-btn" onclick="goHome()"><i class="fa-solid fa-arrow-left"></i> Back</p>
<h3 class="text-center mb-3">Normal Room Booking</h3>

<form method="POST">
<input type="hidden" name="room_type" value="Normal">

<input class="form-control mb-2" name="patient_name" placeholder="Patient Name" required>
<input class="form-control mb-2" name="patient_id" placeholder="Patient ID" required>
<textarea class="form-control mb-2" name="address" placeholder="Address" required></textarea>

<select class="form-select mb-3" name="room_no" required>
<option disabled selected>Select Room Number</option>
<option>N101</option>
<option>N102</option>
<option>N103</option>
</select>

<button class="btn btn-primary w-100" name="book_room">Confirm Booking</button>
</form>
</div>

<!-- VIP ROOM -->
<div id="vipRoom" class="section mt-5">
<p class="back-btn" onclick="goHome()"><i class="fa-solid fa-arrow-left"></i> Back</p>
<h3 class="text-center mb-3">VIP Room Booking</h3>

<form method="POST">
<input type="hidden" name="room_type" value="VIP">

<input class="form-control mb-2" name="patient_name" placeholder="Patient Name" required>
<input class="form-control mb-2" name="patient_id" placeholder="Patient ID" required>
<textarea class="form-control mb-2" name="address" placeholder="Address" required></textarea>

<select class="form-select mb-3" name="room_no" required>
<option disabled selected>Select VIP Room Number</option>
<option>V201</option>
<option>V202</option>
<option>V203</option>
</select>

<button class="btn btn-warning w-100" name="book_room">Confirm VIP Booking</button>
</form>
</div>

</div>

<script>
function showSection(type) {
    homeSection.style.display = "none";
    normalRoom.style.display = "none";
    vipRoom.style.display = "none";
    type === "normal" ? normalRoom.style.display = "block" : vipRoom.style.display = "block";
}
function goHome() {
    homeSection.style.display = "block";
    normalRoom.style.display = "none";
    vipRoom.style.display = "none";
}
</script>

</body>
</html>
