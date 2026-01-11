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
    padding: 10px; /* ⬅️ Before 20px — now smaller */
    text-align: center;
    color: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
    .logo {
    width: 55px; /* ⬅️ slightly smaller */
    height: 55px;
    border-radius: 10px;
    margin-bottom: 3px;
}

    
    .section { display: none; }
    .card-box {
        cursor: pointer;
        transition: .3s;
        border-radius: 20px;
        background: white;
        box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    }
    .card-box:hover {
        background-color: #cfe4ff;
        transform: scale(1.05);
    }
    .back-btn { cursor: pointer; color: #0058a3; font-weight: bold; }
    .form-control { border-radius: 10px; }
    button { border-radius: 10px; }
    
</style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <img class="logo" src="https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcSQVGNYNJcHeoSmY8lq2WRbK_cBCBwnqtqvRWUzY67Tmx1GsmJ2">
    <h2 class="fw-bold">Healthcare Hospital</h2>
</div>

<div class="container mt-5">

    <!-- HOME -->
    <div id="homeSection">
        <h3 class="text-center mb-4 fw-bold">Choose Your Room Type</h3>
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
        <h3 class="text-center mb-3">Normal Room Facilities</h3>
        <ul class="list-group mb-4">
            <li class="list-group-item"><i class="fa-solid fa-bed"></i> 1 Bed</li>
            <li class="list-group-item"><i class="fa-solid fa-fan"></i> Fan</li>
            <li class="list-group-item"><i class="fa-solid fa-bath"></i> Shared Bathroom</li>
        </ul>
        <h5>Book Normal Room</h5>
        <input class="form-control mb-2" placeholder="Patient Name">
        <input class="form-control mb-2" placeholder="Patient ID">
        <textarea class="form-control mb-2" placeholder="Address"></textarea>
        <select class="form-select mb-3">
            <option selected disabled>Select Room Number</option>
            <option>N101</option>
            <option>N102</option>
            <option>N103</option>
        </select>
        <button class="btn btn-primary w-100">Confirm Booking</button>
    </div>

    <!-- VIP ROOM -->
    <div id="vipRoom" class="section mt-5">
        <p class="back-btn" onclick="goHome()"><i class="fa-solid fa-arrow-left"></i> Back</p>
        <h3 class="text-center mb-3">VIP Room Facilities</h3>
        <ul class="list-group mb-4">
            <li class="list-group-item"><i class="fa-solid fa-bed"></i> King Size Bed</li>
            <li class="list-group-item"><i class="fa-solid fa-tv"></i> Smart TV</li>
            <li class="list-group-item"><i class="fa-solid fa-wifi"></i> Free WiFi</li>
            <li class="list-group-item"><i class="fa-solid fa-bath"></i> Private Bathroom</li>
            <li class="list-group-item"><i class="fa-solid fa-bell-concierge"></i> 24/7 Service</li>
        </ul>
        <h5>Book VIP Room</h5>
        <input class="form-control mb-2" placeholder="Patient Name">
        <input class="form-control mb-2" placeholder="Patient ID">
        <textarea class="form-control mb-2" placeholder="Address"></textarea>
        <select class="form-select mb-3">
            <option selected disabled>Select VIP Room Number</option>
            <option>V201</option>
            <option>V202</option>
            <option>V203</option>
        </select>
        <button class="btn btn-warning w-100">Confirm VIP Booking</button>
    </div>

</div>

<script>
function showSection(type) {
    document.getElementById("homeSection").style.display = "none";
    document.getElementById("normalRoom").style.display = "none";
    document.getElementById("vipRoom").style.display = "none";
    
    if (type === "normal") {
        document.getElementById("normalRoom").style.display = "block";
    } else {
        document.getElementById("vipRoom").style.display = "block";
    }
}
function goHome() {
    document.getElementById("homeSection").style.display = "block";
    document.getElementById("normalRoom").style.display = "none";
    document.getElementById("vipRoom").style.display = "none";
}
</script>

</body>
</html>
