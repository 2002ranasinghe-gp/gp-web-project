<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Healthcare Hospital Food Service</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #e9f4ff;
    font-family: Arial, sans-serif;
}

/* HEADER */
.header-bar {
    background: #0d6efd;
    color: white;
    padding: 22px;
    font-size: 32px;
    text-align: center;
    font-weight: bold;
    border-radius: 0 0 0px 0px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    margin-right: 12px;
}

/* Stylish Cards */
.card-option {
    border-radius: 20px;
    transition: .3s;
    cursor: pointer;
}
.card-option:hover {
    transform: scale(1.07);
    box-shadow: 0px 5px 22px #0004;
}

/* Hide all sections except home */
.section { display: none; }
#homeSection { display: block; }

.btn-primary {
    background: #0d6efd;
    border-radius: 12px;
    border: none;
    font-weight: bold;
}

.summary-box {
    background: #ffffff;
    padding: 20px;
    border-radius: 20px;
    box-shadow: 0px 0px 15px #0002;
}

.total-icon {
    font-size: 35px;
    margin-right: 10px;
    color: #0d6efd;
}

/* NO Message Animation */
.thank-box {
    animation: fadeInUp 1s;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="header-bar">
    <img src="https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcSQVGNYNJcHeoSmY8lq2WRbK_cBCBwnqtqvRWUzY67Tmx1GsmJ2" class="logo">
    Healthcare Hospital
</div>

<!-- 🟦 SECTION 1 — YES/NO -->
<section id="homeSection" class="text-center mt-5">
    <h2>Do you want Food Service?</h2><br>
    <button class="btn btn-primary px-5 py-3 me-3" onclick="showMeal()">YES</button>
    <button class="btn btn-danger px-5 py-3" onclick="showThanks()">NO</button>
</section>

<!-- 🟦 SECTION 2 — Meal Select -->
<section id="mealSection" class="section text-center mt-5">
    <h3>Select Meal Type</h3><br>

    <div class="d-flex justify-content-center gap-4 flex-wrap">
        <div class="card card-option p-4" onclick="selectMeal('Breakfast', 350)">
            <h4>🍳 Breakfast</h4><p>Rs. 350.00</p>
        </div>
        <div class="card card-option p-4" onclick="selectMeal('Lunch', 550)">
            <h4>🍛 Lunch</h4><p>Rs. 550.00</p>
        </div>
        <div class="card card-option p-4" onclick="selectMeal('Dinner', 500)">
            <h4>🍽️ Dinner</h4><p>Rs. 500.00</p>
        </div>
    </div>
</section>

<!-- 🟦 SECTION 3 — Patient Details -->
<section id="patientSection" class="section container mt-5">
    <h3 class="text-center">Enter Patient Details</h3>
    <div class="mt-4">
        <label class="form-label">Patient Name :</label>
        <input id="pName" class="form-control">

        <label class="form-label mt-3">Phone Number :</label>
        <input id="pPhone" class="form-control">

        <label class="form-label mt-3">Patient Address :</label>
        <input id="pAdd" class="form-control">

        <label class="form-label mt-3">Patient ID :</label>
        <input id="pID" class="form-control">
    </div>
    <button class="btn btn-primary w-100 mt-4" onclick="showSummary()">Submit</button>
</section>

<!-- 🟦 SECTION 4 — Summary -->
<section id="summarySection" class="section container mt-5">
    <div class="summary-box">
        <h3>Order Summary</h3>
        <p><strong>Meal Type :</strong> <span id="outMeal"></span></p>
        <p><strong>Meal Price :</strong> Rs. <span id="outPrice"></span></p>
        <p><strong>Name :</strong> <span id="outName"></span></p>
        <p><strong>Phone :</strong> <span id="outPhone"></span></p>
        <p><strong>Address :</strong> <span id="outAdd"></span></p>
        <p><strong>Patient ID :</strong> <span id="outID"></span></p>

        <h4 class="mt-3">
            <span class="total-icon">🧾</span>
            Total Bill : Rs. <span id="totalBill"></span>
        </h4>
    </div>

    <button class="btn btn-primary w-100 mt-4" onclick="goHome()">Done</button>
</section>

<!-- 🟦 SECTION 5 — NO Selection -->
<section id="thankSection" class="section text-center mt-5">
    <div class="thank-box">
        <h1>🙏 Thank You!</h1>
        <p>Your preference has been saved.</p>
        <h3>Have a Healthy Day ❤️</h3>
        <button class="btn btn-primary mt-4 px-4" onclick="goHome()">Back Home</button>
    </div>
</section>

<script>
let meal = "";
let price = 0;

function showSection(id) {
    document.querySelectorAll(".section").forEach(s => s.style.display = "none");
    document.getElementById(id).style.display = "block";
}

function showMeal() { showSection("mealSection"); }
function showThanks() { showSection("thankSection"); }
function goHome() { showSection("homeSection"); }

function selectMeal(m, p) {
    meal = m;
    price = p;
    showSection("patientSection");
}

function showSummary() {
    let nameVal = document.getElementById("pName").value;
    let phoneVal = document.getElementById("pPhone").value;
    let addVal = document.getElementById("pAdd").value;
    let idVal = document.getElementById("pID").value;

    document.getElementById("outMeal").innerText = meal;
    document.getElementById("outPrice").innerText = price;
    document.getElementById("outName").innerText = nameVal;
    document.getElementById("outPhone").innerText = phoneVal;
    document.getElementById("outAdd").innerText = addVal;
    document.getElementById("outID").innerText = idVal;
    document.getElementById("totalBill").innerText = price;

    showSection("summarySection");
}
</script>

</body>
</html>
