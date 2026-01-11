<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Heth Care Hospital</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #342ac1;
            --primary-gradient: linear-gradient(to right, #3931af, #00c6ff);
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            padding-top: 70px;
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .bg-primary { 
            background: var(--primary-gradient);
        }
        
        .navbar-brand { 
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .list-group-item.active {
            background-color: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        
        .tab-content {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
            min-height: 600px;
        }
        
        .table {
            width: 100%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
        }
        
        .dashboard-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') no-repeat center center/cover;
            opacity: 0.05;
            z-index: 0;
            border-radius: 10px;
        }
        
        .dashboard-content {
            position: relative;
            z-index: 1;
            padding: 20px;
        }
        
        .dash-card {
            height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-radius: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .dash-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .dash-icon {
            width: 50px;
            margin-bottom: 10px;
            filter: brightness(0) invert(1);
        }
        
        .card-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .card-value {
            font-size: 3.5rem; /* Increased from 2rem to 3.5rem */
            font-weight: bold;
        }
        
        .stats-card {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .quick-action-btn {
            flex: 1;
            min-width: 120px;
            padding: 10px;
            border-radius: 8px;
            background: white;
            border: 1px solid #e0e0e0;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: var(--dark);
        }
        
        .quick-action-btn:hover {
            background: var(--primary);
            color: white;
            text-decoration: none;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-available {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-occupied {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-sent {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .status-sms-sent {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-sent-external {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .recent-activity {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: var(--secondary);
        }
        
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .action-btn {
            margin: 2px;
        }
        
        .pharmacy-type-btn {
            width: 100%;
            margin-bottom: 10px;
        }
        
        @media (max-width: 992px) {
            .dash-card {
                width: 48% !important;
            }
            
            .card-value {
                font-size: 2.8rem; /* Adjusted for medium screens */
            }
        }
        
        @media (max-width: 576px) {
            .dash-card {
                width: 100% !important;
            }
            
            .quick-action-btn {
                min-width: 100%;
            }
            
            .card-value {
                font-size: 2.5rem; /* Adjusted for small screens */
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <a class="navbar-brand" href="#"><i class="fa fa-user-plus"></i> Heth Care Hospital</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#" id="notification-badge"><i class="fa fa-bell"></i> Notifications <span class="badge badge-light" id="notification-count">3</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="fa fa-user"></i> Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout1.php"><i class="fa fa-sign-out"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid">
        <h3 class="text-center mb-4">ADMIN PANEL</h3>
        <div class="row">
            <div class="col-md-3">
                <div class="list-group" id="list-tab" role="tablist">
                    <a class="list-group-item list-group-item-action active" data-toggle="list" href="#dash-tab">
                        <i class="fa fa-tachometer mr-2"></i>Dashboard
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#doc-tab">
                        <i class="fa fa-user-md mr-2"></i>Doctors
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#pat-tab">
                        <i class="fa fa-users mr-2"></i>Patients
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#app-tab">
                        <i class="fa fa-calendar mr-2"></i>Appointments
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#pres-tab">
                        <i class="fa fa-file-text mr-2"></i>Prescriptions
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#pay-tab">
                        <i class="fa fa-credit-card mr-2"></i>Payments
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#sched-tab">
                        <i class="fa fa-clock-o mr-2"></i>Schedules
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#room-tab">
                        <i class="fa fa-bed mr-2"></i>Rooms/Beds
                    </a>
                    <a class="list-group-item list-group-item-action" data-toggle="list" href="#staff-tab">
                        <i class="fa fa-id-badge mr-2"></i>Staff Management
                    </a>
                </div>
            </div>

            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Dashboard -->
                    <div class="tab-pane fade show active" id="dash-tab">
                        <div class="dashboard-bg"></div>
                        <div class="dashboard-content">
                            <h4 class="mb-4 text-dark">Dashboard Overview</h4>
                            
                            <!-- Quick Actions -->
                            <div class="quick-actions">
                                <a class="quick-action-btn" data-toggle="list" href="#staff-tab">
                                    <i class="fa fa-user-md fa-2x mb-2"></i>
                                    <div>Add Doctor</div>
                                </a>
                                <a class="quick-action-btn" data-toggle="list" href="#staff-tab">
                                    <i class="fa fa-id-badge fa-2x mb-2"></i>
                                    <div>Manage Staff</div>
                                </a>
                                <a class="quick-action-btn" data-toggle="list" href="#app-tab">
                                    <i class="fa fa-calendar fa-2x mb-2"></i>
                                    <div>View Appointments</div>
                                </a>
                                <a class="quick-action-btn" data-toggle="list" href="#pay-tab">
                                    <i class="fa fa-credit-card fa-2x mb-2"></i>
                                    <div>Payments</div>
                                </a>
                                <a class="quick-action-btn" data-toggle="list" href="#room-tab">
                                    <i class="fa fa-bed fa-2x mb-2"></i>
                                    <div>Rooms/Beds</div>
                                </a>
                                <a class="quick-action-btn" data-toggle="modal" data-target="#deleteDoctorModal">
                                    <i class="fa fa-trash fa-2x mb-2"></i>
                                    <div>Delete Doctor</div>
                                </a>
                            </div>
                            
                            <!-- Stats Cards -->
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="dash-card text-white" style="background: linear-gradient(135deg, #0d47a1, #1976d2);">
                                        <i class="fa fa-user-md dash-icon"></i>
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Total Doctors</h5>
                                            <h3 class="card-value" id="total-doctors">8</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="dash-card text-white" style="background: linear-gradient(135deg, #0d47a1, #2196f3);">
                                        <i class="fa fa-users dash-icon"></i>
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Total Patients</h5>
                                            <h3 class="card-value" id="total-patients">10</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="dash-card text-white" style="background: linear-gradient(135deg, #0d47a1, #42a5f5);">
                                        <i class="fa fa-calendar dash-icon"></i>
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Appointments</h5>
                                            <h3 class="card-value" id="total-appointments">10</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="dash-card text-white" style="background: linear-gradient(135deg, #0d47a1, #64b5f6);">
                                        <i class="fa fa-id-badge dash-icon"></i>
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Staff Members</h5>
                                            <h3 class="card-value" id="total-staff">2</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Charts and Additional Stats -->
                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="chart-container">
                                        <h5>Appointments Overview</h5>
                                        <canvas id="appointmentsChart" height="250"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="chart-container">
                                        <h5>Department Distribution</h5>
                                        <canvas id="departmentChart" height="250"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Recent Activity -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="chart-container">
                                        <h5>Recent Activity</h5>
                                        <div class="recent-activity" id="recent-activity">
                                            <!-- Activity items will be populated by JavaScript -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="chart-container">
                                        <h5>Today's Appointments</h5>
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Patient</th>
                                                    <th>Doctor</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="today-appointments">
                                                <!-- Today's appointments will be populated by JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Doctors -->
                    <div class="tab-pane fade" id="doc-tab">
                        <h4>Doctors List</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="doctor-search" placeholder="Search doctors..." onkeyup="filterTable('doctor-search', 'doctors-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('doctors-table-body', 'doctors')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>Doctor ID</th>
                                    <th>Name</th>
                                    <th>Specialization</th>
                                    <th>Email</th>
                                    <th>Fees</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="doctors-table-body">
                                <!-- Doctors data will be populated by JavaScript -->
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Patients -->
                    <div class="tab-pane fade" id="pat-tab">
                        <h4>Patients List</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="patient-search" placeholder="Search patients..." onkeyup="filterTable('patient-search', 'patients-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('patients-table-body', 'patients')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Gender</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>National ID</th>
                                </tr>
                            </thead>
                            <tbody id="patients-table-body">
                                <!-- Patients data will be populated by JavaScript -->
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Appointments -->
                    <div class="tab-pane fade" id="app-tab">
                        <h4>Appointments</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="appointment-search" placeholder="Search appointments..." onkeyup="filterTable('appointment-search', 'appointments-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('appointments-table-body', 'appointments')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient ID</th>
                                    <th>Patient Name</th>
                                    <th>National ID</th>
                                    <th>Doctor</th>
                                    <th>Fees</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="appointments-table-body">
                                <!-- Appointments data will be populated by JavaScript -->
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Prescriptions -->
                    <div class="tab-pane fade" id="pres-tab">
                        <h4>Prescriptions</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="prescription-search" placeholder="Search prescriptions..." onkeyup="filterTable('prescription-search', 'prescriptions-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('prescriptions-table-body', 'prescriptions')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Patient ID</th>
                                    <th>Patient Name</th>
                                    <th>National ID</th>
                                    <th>Date</th>
                                    <th>Disease</th>
                                    <th>Allergy</th>
                                    <th>Prescription</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="prescriptions-table-body">
                                <!-- Prescriptions data will be populated by JavaScript -->
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Payments -->
                    <div class="tab-pane fade" id="pay-tab">
                        <h4>Payments</h4>
                        <form method="post" class="form-inline mb-2" id="payment-search-form">
                            <input type="number" name="pid" class="form-control mr-2" placeholder="Enter Patient ID" id="payment-patient-id">
                            <button type="button" class="btn btn-success" onclick="searchPaymentsByPatient()">View Payments</button>
                        </form>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="payment-search" placeholder="Search payments..." onkeyup="filterTable('payment-search', 'payments-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('payments-table-body', 'payments')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient ID</th>
                                    <th>Appointment ID</th>
                                    <th>Patient Name</th>
                                    <th>National ID</th>
                                    <th>Doctor</th>
                                    <th>Fees</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="payments-table-body">
                                <!-- Payments data will be populated by JavaScript -->
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Staff Schedules -->
                    <div class="tab-pane fade" id="sched-tab">
                        <h4>Staff Schedules</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="schedule-search" placeholder="Search schedules..." onkeyup="filterTable('schedule-search', 'schedules-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('schedules-table-body', 'schedules')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Staff Name</th>
                                    <th>Role</th>
                                    <th>Day</th>
                                    <th>Shift</th>
                                </tr>
                            </thead>
                            <tbody id="schedules-table-body">
                                <!-- Schedules data will be populated by JavaScript -->
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Rooms / Beds -->
                    <div class="tab-pane fade" id="room-tab">
                        <h4>Rooms / Beds</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="room-search" placeholder="Search rooms..." onkeyup="filterTable('room-search', 'rooms-table-body')">
                            <button class="btn btn-primary" onclick="exportTable('rooms-table-body', 'rooms')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Room No</th>
                                    <th>Bed No</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rooms-table-body">
                                <!-- Rooms data will be populated by JavaScript -->
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Staff Management (now includes Doctor Management) -->
                    <div class="tab-pane fade" id="staff-tab">
                        <h4>Staff & Doctor Management</h4>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <i class="fa fa-user-md mr-2"></i>Add New Doctor
                                    </div>
                                    <div class="card-body">
                                        <form id="add-doctor-form">
                                            <div class="form-group">
                                                <label>Doctor ID</label>
                                                <input type="text" name="doctorId" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" name="doctor" class="form-control" onkeydown="return alphaOnly(event)" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Specialization</label>
                                                <select name="special" class="form-control" required>
                                                    <option value="">Select Specialization</option>
                                                    <option value="General">General Physician</option>
                                                    <option value="Cardiologist">Cardiologist</option>
                                                    <option value="Pediatrician">Pediatrician</option>
                                                    <option value="Neurologist">Neurologist</option>
                                                    <option value="Dermatologist">Dermatologist</option>
                                                    <option value="Orthopedic">Orthopedic</option>
                                                    <option value="Gynecologist">Gynecologist</option>
                                                </select>
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
                                            <button type="button" class="btn btn-success btn-block" onclick="addDoctor()">Add Doctor</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fa fa-plus-circle mr-2"></i>Add New Staff Member
                                    </div>
                                    <div class="card-body">
                                        <form id="add-staff-form">
                                            <div class="form-group">
                                                <label>Staff ID</label>
                                                <input type="text" name="staffId" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" name="staff" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Role</label>
                                                <select name="role" class="form-control" required>
                                                    <option value="">Select Role</option>
                                                    <option value="Nurse">Nurse</option>
                                                    <option value="Receptionist">Receptionist</option>
                                                    <option value="Admin">Admin</option>
                                                    <option value="Lab Technician">Lab Technician</option>
                                                    <option value="Pharmacist">Pharmacist</option>
                                                    <option value="Cleaner">Cleaner</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Email</label>
                                                <input type="email" name="semail" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Contact</label>
                                                <input type="text" name="scontact" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input type="password" name="spassword" class="form-control" required>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-block" onclick="addStaff()">Add Staff Member</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h5>Doctors & Staff List</h5>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="staff-search" placeholder="Search by ID or Name..." onkeyup="filterStaffTable()">
                            <button class="btn btn-primary" onclick="exportTable('staff-table-body', 'staff')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Role/Type</th>
                                    <th>Email</th>
                                    <th>Contact/Fees</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="staff-table-body">
                                <!-- Staff and Doctors data will be populated by JavaScript -->
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Doctor Modal -->
    <div class="modal fade" id="deleteDoctorModal" tabindex="-1" role="dialog" aria-labelledby="deleteDoctorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteDoctorModalLabel">Delete Doctor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="delete-doctor-form">
                        <div class="form-group">
                            <label>Select Doctor</label>
                            <select name="doctorId" class="form-control" id="doctor-select" required>
                                <option value="">Select doctor to delete</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="deleteDoctorFromDashboard()">Delete Doctor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Prescription Delivery Selection Modal -->
    <div class="modal fade" id="prescriptionDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="prescriptionDeliveryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="prescriptionDeliveryModalLabel">Send Prescription</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Please select how to send this prescription:</p>
                    <button type="button" class="btn btn-success pharmacy-type-btn" onclick="selectDeliveryMethod('sms')">
                        <i class="fa fa-mobile"></i> Send SMS to Patient
                    </button>
                    <button type="button" class="btn btn-warning pharmacy-type-btn" onclick="selectDeliveryMethod('external')">
                        <i class="fa fa-medkit"></i> Send to External Pharmacy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SMS to Patient Modal -->
    <div class="modal fade" id="smsToPatientModal" tabindex="-1" role="dialog" aria-labelledby="smsToPatientModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="smsToPatientModalLabel">Send SMS to Patient</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="smsToPatientForm">
                        <div class="form-group">
                            <label for="patientContactNumber">Patient Contact Number</label>
                            <input type="text" class="form-control" id="patientContactNumber" readonly>
                        </div>
                        <div class="form-group">
                            <label for="smsMessage">SMS Message</label>
                            <textarea class="form-control" id="smsMessage" rows="10" readonly></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="sendSmsBtn">Send SMS to Patient</button>
                </div>
            </div>
        </div>
    </div>

    <!-- External Pharmacy Email Modal -->
    <div class="modal fade" id="externalPharmacyModal" tabindex="-1" role="dialog" aria-labelledby="externalPharmacyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="externalPharmacyModalLabel">Send to External Pharmacy</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="externalPharmacyForm">
                        <div class="form-group">
                            <label for="externalRecipientEmail">Pharmacy Email Address</label>
                            <input type="email" class="form-control" id="externalRecipientEmail" placeholder="Enter pharmacy email address">
                        </div>
                        <div class="form-group">
                            <label for="externalEmailSubject">Subject</label>
                            <input type="text" class="form-control" id="externalEmailSubject" value="Prescription from Heth Care Hospital">
                        </div>
                        <div class="form-group">
                            <label for="externalEmailMessage">Prescription Details</label>
                            <textarea class="form-control" id="externalEmailMessage" rows="10" readonly></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" id="sendExternalEmailBtn">Send to External Pharmacy</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    
    <script>
        // Database simulation
        let database = {
            doctors: [
                {id: 'DOC001', username: 'ashok', password: 'ashok123', email: 'ashok@gmail.com', spec: 'General', docFees: 500},
                {id: 'DOC002', username: 'arun', password: 'arun123', email: 'arun@gmail.com', spec: 'Cardiologist', docFees: 600},
                {id: 'DOC003', username: 'Dinesh', password: 'dinesh123', email: 'dinesh@gmail.com', spec: 'General', docFees: 700},
                {id: 'DOC004', username: 'Ganesh', password: 'ganesh123', email: 'ganesh@gmail.com', spec: 'Pediatrician', docFees: 550},
                {id: 'DOC005', username: 'Kumar', password: 'kumar123', email: 'kumar@gmail.com', spec: 'Pediatrician', docFees: 800},
                {id: 'DOC006', username: 'Amit', password: 'amit123', email: 'amit@gmail.com', spec: 'Cardiologist', docFees: 1000},
                {id: 'DOC007', username: 'Abbis', password: 'abbis123', email: 'abbis@gmail.com', spec: 'Neurologist', docFees: 1500},
                {id: 'DOC008', username: 'Tiwary', password: 'tiwary123', email: 'tiwary@gmail.com', spec: 'Pediatrician', docFees: 450}
            ],
            patients: [
                {pid: 1, fname: 'Ram', lname: 'Kumar', gender: 'Male', email: 'ram@gmail.com', contact: '0771234567', national_id: 'NIC123456789', password: 'ram123', cpassword: 'ram123'},
                {pid: 2, fname: 'Alia', lname: 'Bhatt', gender: 'Female', email: 'alia@gmail.com', contact: '0779876543', national_id: 'NIC987654321', password: 'alia123', cpassword: 'alia123'},
                {pid: 3, fname: 'Shahrukh', lname: 'Khan', gender: 'Male', email: 'shahrukh@gmail.com', contact: '0712345678', national_id: 'NIC111222333', password: 'shahrukh123', cpassword: 'shahrukh123'},
                {pid: 4, fname: 'Kishan', lname: 'Lal', gender: 'Male', email: 'kishan@gmail.com', contact: '0765432198', national_id: 'NIC444555666', password: 'kishan123', cpassword: 'kishan123'},
                {pid: 5, fname: 'Gautam', lname: 'Shankararam', gender: 'Male', email: 'gautam@gmail.com', contact: '0754321876', national_id: 'NIC777888999', password: 'gautam123', cpassword: 'gautam123'},
                {pid: 6, fname: 'Sushant', lname: 'Singh', gender: 'Male', email: 'sushant@gmail.com', contact: '0787654321', national_id: 'NIC123123123', password: 'sushant123', cpassword: 'sushant123'},
                {pid: 7, fname: 'Nancy', lname: 'Deborah', gender: 'Female', email: 'nancy@gmail.com', contact: '0723456789', national_id: 'NIC321321321', password: 'nancy123', cpassword: 'nancy123'},
                {pid: 8, fname: 'Kenny', lname: 'Sebastian', gender: 'Male', email: 'kenny@gmail.com', contact: '0745678901', national_id: 'NIC456456456', password: 'kenny123', cpassword: 'kenny123'},
                {pid: 9, fname: 'William', lname: 'Blake', gender: 'Male', email: 'william@gmail.com', contact: '0798765432', national_id: 'NIC654654654', password: 'william123', cpassword: 'william123'},
                {pid: 10, fname: 'Peter', lname: 'Norvig', gender: 'Male', email: 'peter@gmail.com', contact: '0734567890', national_id: 'NIC789789789', password: 'peter123', cpassword: 'peter123'}
            ],
            appointments: [
                {ID: 1, pid: 1, national_id: 'NIC123456789', fname: 'Ram', lname: 'Kumar', gender: 'Male', email: 'ram@gmail.com', contact: '0771234567', doctor: 'Ganesh', docFees: 500, appdate: '2025-10-29', apptime: '10:00:00', userStatus: 1, doctorStatus: 0},
                {ID: 2, pid: 2, national_id: 'NIC987654321', fname: 'Alia', lname: 'Bhatt', gender: 'Female', email: 'alia@gmail.com', contact: '0779876543', doctor: 'Ganesh', docFees: 550, appdate: '2025-10-30', apptime: '11:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 3, pid: 3, national_id: 'NIC111222333', fname: 'Shahrukh', lname: 'Khan', gender: 'Male', email: 'shahrukh@gmail.com', contact: '0712345678', doctor: 'Dinesh', docFees: 700, appdate: '2025-11-01', apptime: '09:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 4, pid: 4, national_id: 'NIC444555666', fname: 'Kishan', lname: 'Lal', gender: 'Male', email: 'kishan@gmail.com', contact: '0765432198', doctor: 'Amit', docFees: 1000, appdate: '2025-11-02', apptime: '14:00:00', userStatus: 1, doctorStatus: 0},
                {ID: 5, pid: 5, national_id: 'NIC777888999', fname: 'Gautam', lname: 'Shankararam', gender: 'Male', email: 'gautam@gmail.com', contact: '0754321876', doctor: 'Kumar', docFees: 800, appdate: '2025-11-03', apptime: '16:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 6, pid: 6, national_id: 'NIC123123123', fname: 'Sushant', lname: 'Singh', gender: 'Male', email: 'sushant@gmail.com', contact: '0787654321', doctor: 'Abbis', docFees: 1500, appdate: '2025-11-04', apptime: '12:00:00', userStatus: 1, doctorStatus: 0},
                {ID: 7, pid: 7, national_id: 'NIC321321321', fname: 'Nancy', lname: 'Deborah', gender: 'Female', email: 'nancy@gmail.com', contact: '0723456789', doctor: 'Tiwary', docFees: 450, appdate: '2025-11-05', apptime: '10:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 8, pid: 8, national_id: 'NIC456456456', fname: 'Kenny', lname: 'Sebastian', gender: 'Male', email: 'kenny@gmail.com', contact: '0745678901', doctor: 'Ganesh', docFees: 550, appdate: '2025-11-06', apptime: '11:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 9, pid: 9, national_id: 'NIC654654654', fname: 'William', lname: 'Blake', gender: 'Male', email: 'william@gmail.com', contact: '0798765432', doctor: 'Kumar', docFees: 800, appdate: '2025-11-07', apptime: '15:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 10, pid: 10, national_id: 'NIC789789789', fname: 'Peter', lname: 'Norvig', gender: 'Male', email: 'peter@gmail.com', contact: '0734567890', doctor: 'Ganesh', docFees: 500, appdate: '2025-11-08', apptime: '09:00:00', userStatus: 1, doctorStatus: 1}
            ],
            prescriptions: [
                {id: 1, doctor: 'Ganesh', pid: 1, ID: 1, fname: 'Ram', lname: 'Kumar', national_id: 'NIC123456789', appdate: '2025-10-29', apptime: '10:00:00', disease: 'Fever', allergy: 'None', prescription: 'Take paracetamol 500mg twice daily', emailStatus: 'Not Sent'},
                {id: 2, doctor: 'Ganesh', pid: 2, ID: 2, fname: 'Alia', lname: 'Bhatt', national_id: 'NIC987654321', appdate: '2025-10-30', apptime: '11:00:00', disease: 'Cold', allergy: 'None', prescription: 'Take vitamin C and rest', emailStatus: 'SMS Sent'}
            ],
            payments: [
                {id: 1, pid: 1, app_id: 1, national_id: 'NIC123456789', patient_name: 'Ram Kumar', doctor: 'Ganesh', fees: 500.00, pay_date: '2025-10-29', pay_status: 'Paid'},
                {id: 2, pid: 2, app_id: 2, national_id: 'NIC987654321', patient_name: 'Alia Bhatt', doctor: 'Ganesh', fees: 550.00, pay_date: '2025-10-30', pay_status: 'Paid'},
                {id: 3, pid: 3, app_id: 3, national_id: 'NIC111222333', patient_name: 'Shahrukh Khan', doctor: 'Dinesh', fees: 700.00, pay_date: '2025-11-01', pay_status: 'Pending'}
            ],
            staff: [
                {id: 'STF001', name: 'Ramesh', role: 'Nurse', email: 'ramesh@gmail.com', contact: '0771112222', password: 'ramesh123'},
                {id: 'STF002', name: 'Sita', role: 'Receptionist', email: 'sita@gmail.com', contact: '0773334444', password: 'sita123'}
            ],
            schedules: [
                {id: 1, staff_name: 'Ramesh', role: 'Nurse', day: 'Monday', shift: 'Morning'},
                {id: 2, staff_name: 'Sita', role: 'Receptionist', day: 'Monday', shift: 'Evening'}
            ],
            rooms: [
                {id: 1, room_no: '101', bed_no: '1', type: 'Normal', status: 'Available'},
                {id: 2, room_no: '101', bed_no: '2', type: 'Normal', status: 'Occupied'},
                {id: 3, room_no: '102', bed_no: '1', type: 'VIP', status: 'Available'},
                {id: 4, room_no: '102', bed_no: '2', type: 'VIP', status: 'Occupied'},
                {id: 5, room_no: '103', bed_no: '1', type: 'ICU', status: 'Available'}
            ]
        };

        // Current prescription being processed
        let currentPrescriptionId = null;
        let currentPatientContact = '';

        // Initialize dashboard with data
        document.addEventListener('DOMContentLoaded', function() {
            // Set dashboard counts
            updateDashboardCounts();
            
            // Populate tables
            populateDoctorsTable();
            populatePatientsTable();
            populateAppointmentsTable();
            populatePrescriptionsTable();
            populatePaymentsTable();
            populateStaffTable();
            populateSchedulesTable();
            populateRoomsTable();
            
            // Set up recent activity
            populateRecentActivity();
            
            // Set up today's appointments
            populateTodayAppointments();
            
            // Initialize charts
            initializeCharts();
            
            // Set up form submissions
            setupFormSubmissions();
            
            // Set up email functionality
            setupEmailFunctionality();
            
            // Set up SMS functionality
            setupSMSFunctionality();
            
            // Populate select dropdowns
            populateDoctorSelect();
        });

        // Update dashboard counts
        function updateDashboardCounts() {
            document.getElementById('total-doctors').textContent = database.doctors.length;
            document.getElementById('total-patients').textContent = database.patients.length;
            document.getElementById('total-appointments').textContent = database.appointments.length;
            document.getElementById('total-staff').textContent = database.staff.length;
        }

        // Function to populate doctors table
        function populateDoctorsTable() {
            const tbody = document.getElementById('doctors-table-body');
            tbody.innerHTML = '';
            
            database.doctors.forEach(doctor => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${doctor.id}</td>
                    <td>${doctor.username}</td>
                    <td>${doctor.spec}</td>
                    <td>${doctor.email}</td>
                    <td>Rs. ${doctor.docFees}</td>
                    <td>
                        <button class="btn btn-sm btn-warning action-btn" onclick="editDoctor('${doctor.id}')">Edit</button>
                        <button class="btn btn-sm btn-danger action-btn" onclick="deleteDoctorPrompt('${doctor.id}')">Delete</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Function to populate patients table
        function populatePatientsTable() {
            const tbody = document.getElementById('patients-table-body');
            tbody.innerHTML = '';
            
            database.patients.forEach(patient => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${patient.pid}</td>
                    <td>${patient.fname}</td>
                    <td>${patient.lname}</td>
                    <td>${patient.gender}</td>
                    <td>${patient.email}</td>
                    <td>${patient.contact}</td>
                    <td>${patient.national_id}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Function to populate appointments table
        function populateAppointmentsTable() {
            const tbody = document.getElementById('appointments-table-body');
            tbody.innerHTML = '';
            
            database.appointments.forEach(appointment => {
                const status = appointment.doctorStatus === 1 ? 
                    (appointment.userStatus === 1 ? 'Active' : 'Cancelled by Patient') : 
                    'Cancelled by Doctor';
                
                const statusClass = status === 'Active' ? 'status-active' : 'status-cancelled';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${appointment.ID}</td>
                    <td>${appointment.pid}</td>
                    <td>${appointment.fname} ${appointment.lname}</td>
                    <td>${appointment.national_id}</td>
                    <td>${appointment.doctor}</td>
                    <td>${appointment.docFees}</td>
                    <td>${appointment.appdate}</td>
                    <td>${appointment.apptime}</td>
                    <td><span class="status-badge ${statusClass}">${status}</span></td>
                `;
                tbody.appendChild(row);
            });
        }

        // Function to populate prescriptions table
        function populatePrescriptionsTable() {
            const tbody = document.getElementById('prescriptions-table-body');
            tbody.innerHTML = '';
            
            database.prescriptions.forEach(prescription => {
                let statusClass, statusText;
                
                if (prescription.emailStatus === 'Not Sent') {
                    statusClass = 'status-pending';
                    statusText = 'Not Sent';
                } else if (prescription.emailStatus === 'SMS Sent') {
                    statusClass = 'status-sms-sent';
                    statusText = 'SMS Sent';
                } else if (prescription.emailStatus === 'External') {
                    statusClass = 'status-sent-external';
                    statusText = 'Sent to External';
                }
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${prescription.doctor}</td>
                    <td>${prescription.pid}</td>
                    <td>${prescription.fname} ${prescription.lname}</td>
                    <td>${prescription.national_id}</td>
                    <td>${prescription.appdate}</td>
                    <td>${prescription.disease}</td>
                    <td>${prescription.allergy}</td>
                    <td>${prescription.prescription}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>
                        <button class="btn btn-sm btn-info action-btn" onclick="viewPrescription(${prescription.id})">View</button>
                        <button class="btn btn-sm btn-success action-btn" onclick="sendPrescription(${prescription.id})">Send Prescription</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Function to populate payments table
        function populatePaymentsTable() {
            const tbody = document.getElementById('payments-table-body');
            tbody.innerHTML = '';
            
            database.payments.forEach(payment => {
                const statusClass = payment.pay_status === 'Paid' ? 'status-active' : 'status-pending';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${payment.id}</td>
                    <td>${payment.pid}</td>
                    <td>${payment.app_id}</td>
                    <td>${payment.patient_name}</td>
                    <td>${payment.national_id}</td>
                    <td>${payment.doctor}</td>
                    <td>Rs. ${payment.fees}</td>
                    <td>${payment.pay_date}</td>
                    <td><span class="status-badge ${statusClass}">${payment.pay_status}</span></td>
                `;
                tbody.appendChild(row);
            });
        }

        // Function to populate staff table (including doctors)
        function populateStaffTable() {
            const tbody = document.getElementById('staff-table-body');
            tbody.innerHTML = '';
            
            // Add doctors to the table
            database.doctors.forEach(doctor => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${doctor.id}</td>
                    <td>${doctor.username}</td>
                    <td><span class="badge badge-primary">Doctor (${doctor.spec})</span></td>
                    <td>${doctor.email}</td>
                    <td>Rs. ${doctor.docFees}</td>
                    <td>
                        <button class="btn btn-sm btn-warning action-btn" onclick="editDoctor('${doctor.id}')">Edit</button>
                        <button class="btn btn-sm btn-danger action-btn" onclick="deleteDoctorPrompt('${doctor.id}')">Delete</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            // Add staff to the table
            database.staff.forEach(staff => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${staff.id}</td>
                    <td>${staff.name}</td>
                    <td><span class="badge badge-secondary">${staff.role}</span></td>
                    <td>${staff.email}</td>
                    <td>${staff.contact}</td>
                    <td>
                        <button class="btn btn-sm btn-warning action-btn" onclick="editStaff('${staff.id}')">Edit</button>
                        <button class="btn btn-sm btn-danger action-btn" onclick="deleteStaffPrompt('${staff.id}')">Delete</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Function to populate schedules table
        function populateSchedulesTable() {
            const tbody = document.getElementById('schedules-table-body');
            tbody.innerHTML = '';
            
            database.schedules.forEach(schedule => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${schedule.id}</td>
                    <td>${schedule.staff_name}</td>
                    <td>${schedule.role}</td>
                    <td>${schedule.day}</td>
                    <td>${schedule.shift}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Function to populate rooms table
        function populateRoomsTable() {
            const tbody = document.getElementById('rooms-table-body');
            tbody.innerHTML = '';
            
            database.rooms.forEach(room => {
                const statusClass = room.status === 'Available' ? 'status-available' : 'status-occupied';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${room.id}</td>
                    <td>${room.room_no}</td>
                    <td>${room.bed_no}</td>
                    <td>${room.type}</td>
                    <td><span class="status-badge ${statusClass}">${room.status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-info action-btn" onclick="viewRoomDetails(${room.id})">View</button>
                        <button class="btn btn-sm btn-warning action-btn" onclick="updateRoomStatus(${room.id})">Update</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Function to populate recent activity
        function populateRecentActivity() {
            const container = document.getElementById('recent-activity');
            container.innerHTML = '';
            
            // Create activity items
            const activities = [
                {text: 'New appointment scheduled', details: 'Patient: Ram Kumar with Dr. Ganesh', time: '2 hours ago'},
                {text: 'Payment received', details: 'Patient ID: 4, Amount: Rs. 700', time: '5 hours ago'},
                {text: 'Prescription added', details: 'Dr. Dinesh for Patient ID: 4', time: '1 day ago'},
                {text: 'New patient registered', details: 'dinuvi ranasinghe', time: '2 days ago'},
                {text: 'Appointment cancelled', details: 'Patient ID: 4 with Dr. Ganesh', time: '3 days ago'}
            ];
            
            activities.forEach(activity => {
                const item = document.createElement('div');
                item.className = 'activity-item';
                item.innerHTML = `
                    <div class="d-flex justify-content-between">
                        <div><strong>${activity.text}</strong></div>
                        <div class="activity-time">${activity.time}</div>
                    </div>
                    <div>${activity.details}</div>
                `;
                container.appendChild(item);
            });
        }

        // Function to populate today's appointments
        function populateTodayAppointments() {
            const tbody = document.getElementById('today-appointments');
            tbody.innerHTML = '';
            
            // Get today's date
            const today = new Date().toISOString().split('T')[0];
            
            // Filter today's appointments
            const todayAppointments = database.appointments.filter(app => app.appdate === today);
            
            if (todayAppointments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No appointments for today</td></tr>';
                return;
            }
            
            todayAppointments.forEach(appointment => {
                const status = appointment.doctorStatus === 1 ? 
                    (appointment.userStatus === 1 ? 'Active' : 'Cancelled by Patient') : 
                    'Cancelled by Doctor';
                
                const statusClass = status === 'Active' ? 'status-active' : 'status-cancelled';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${appointment.apptime}</td>
                    <td>${appointment.fname} ${appointment.lname}</td>
                    <td>${appointment.doctor}</td>
                    <td><span class="status-badge ${statusClass}">${status}</span></td>
                `;
                tbody.appendChild(row);
            });
        }

        // Function to populate doctor select dropdown
        function populateDoctorSelect() {
            const select = document.getElementById('doctor-select');
            select.innerHTML = '<option value="">Select doctor to delete</option>';
            
            database.doctors.forEach(doctor => {
                const option = document.createElement('option');
                option.value = doctor.id;
                option.textContent = `${doctor.id} - ${doctor.username} (${doctor.spec})`;
                select.appendChild(option);
            });
        }

        // Function to initialize charts
        function initializeCharts() {
            // Appointments Chart
            const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
            const appointmentsChart = new Chart(appointmentsCtx, {
                type: 'bar',
                data: {
                    labels: ['Oct 29', 'Oct 30', 'Nov 1', 'Nov 2', 'Nov 3', 'Nov 4'],
                    datasets: [{
                        label: 'Appointments',
                        data: [1, 1, 1, 1, 1, 1],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
            // Department Chart
            const departmentCtx = document.getElementById('departmentChart').getContext('2d');
            
            // Count doctors by specialization
            const specCount = {};
            database.doctors.forEach(doctor => {
                specCount[doctor.spec] = (specCount[doctor.spec] || 0) + 1;
            });
            
            const departmentChart = new Chart(departmentCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(specCount),
                    datasets: [{
                        data: Object.values(specCount),
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.5)',
                            'rgba(54, 162, 235, 0.5)',
                            'rgba(255, 206, 86, 0.5)',
                            'rgba(75, 192, 192, 0.5)',
                            'rgba(153, 102, 255, 0.5)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true
                }
            });
        }

        // Function to filter table
        function filterTable(searchInputId, tableBodyId) {
            const input = document.getElementById(searchInputId);
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableBodyId);
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell) {
                        const text = cell.textContent || cell.innerText;
                        if (text.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }

        // Function to filter staff table
        function filterStaffTable() {
            const input = document.getElementById('staff-search');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('staff-table-body');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                // Check ID (first cell) and Name (second cell)
                if (cells.length > 1) {
                    const staffId = cells[0].textContent || cells[0].innerText;
                    const staffName = cells[1].textContent || cells[1].innerText;
                    
                    if (staffId.toLowerCase().indexOf(filter) > -1 || 
                        staffName.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }

        // Function to export table
        function exportTable(tableBodyId, filename) {
            const table = document.getElementById(tableBodyId);
            const rows = table.getElementsByTagName('tr');
            let csv = [];
            
            // Add headers
            const headerRow = [];
            const headerCells = table.parentNode.getElementsByTagName('thead')[0].getElementsByTagName('th');
            for (let i = 0; i < headerCells.length; i++) {
                headerRow.push(headerCells[i].innerText);
            }
            csv.push(headerRow.join(','));
            
            // Add data
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td');
                
                for (let j = 0; j < cols.length; j++) {
                    row.push(cols[j].innerText);
                }
                
                csv.push(row.join(','));
            }
            
            // Download CSV file
            const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            const downloadLink = document.createElement('a');
            downloadLink.download = filename + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        // Function to search payments by patient ID
        function searchPaymentsByPatient() {
            const patientId = document.getElementById('payment-patient-id').value;
            const tbody = document.getElementById('payments-table-body');
            const rows = tbody.getElementsByTagName('tr');
            
            if (!patientId) {
                // Show all payments if no ID is entered
                for (let i = 0; i < rows.length; i++) {
                    rows[i].style.display = '';
                }
                return;
            }
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                if (cells.length > 1 && cells[1].textContent == patientId) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        // Function to add a new doctor
        function addDoctor() {
            const form = document.getElementById('add-doctor-form');
            const formData = new FormData(form);
            
            const password = formData.get('dpassword');
            const confirmPassword = document.getElementById('cdpassword').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            const newDoctor = {
                id: formData.get('doctorId'),
                username: formData.get('doctor'),
                password: password,
                email: formData.get('demail'),
                spec: formData.get('special'),
                docFees: parseInt(formData.get('docFees'))
            };
            
            // Check if doctor already exists
            const existingDoctor = database.doctors.find(d => d.id === newDoctor.id || d.email === newDoctor.email);
            if (existingDoctor) {
                alert('A doctor with this ID or email already exists!');
                return;
            }
            
            // Add to database
            database.doctors.push(newDoctor);
            
            // Update UI
            populateDoctorsTable();
            populateStaffTable();
            updateDashboardCounts();
            populateDoctorSelect();
            
            // Reset form
            form.reset();
            document.getElementById('message').innerText = '';
            
            // Add to recent activity
            addRecentActivity(`New doctor added: ${newDoctor.username} (${newDoctor.spec}) - ID: ${newDoctor.id}`);
            
            alert('Doctor added successfully!');
        }

        // Function to delete doctor from dashboard
        function deleteDoctorFromDashboard() {
            const form = document.getElementById('delete-doctor-form');
            const formData = new FormData(form);
            const doctorId = formData.get('doctorId');
            
            if (!doctorId) {
                alert('Please select a doctor to delete!');
                return;
            }
            
            // Find doctor index
            const doctorIndex = database.doctors.findIndex(d => d.id === doctorId);
            
            if (doctorIndex === -1) {
                alert('No doctor found with this ID!');
                return;
            }
            
            // Get doctor name for activity log
            const doctorName = database.doctors[doctorIndex].username;
            const doctorIdValue = database.doctors[doctorIndex].id;
            
            // Remove from database
            database.doctors.splice(doctorIndex, 1);
            
            // Update UI
            populateDoctorsTable();
            populateStaffTable();
            updateDashboardCounts();
            populateDoctorSelect();
            
            // Reset form
            form.reset();
            
            // Close modal
            $('#deleteDoctorModal').modal('hide');
            
            // Add to recent activity
            addRecentActivity(`Doctor deleted: ${doctorName} (ID: ${doctorIdValue})`);
            
            alert('Doctor deleted successfully!');
        }

        // Function to delete doctor prompt
        function deleteDoctorPrompt(doctorId) {
            const doctor = database.doctors.find(d => d.id === doctorId);
            if (!doctor) return;
            
            if (confirm(`Are you sure you want to delete doctor: ${doctor.username} (ID: ${doctor.id})?`)) {
                const doctorIndex = database.doctors.findIndex(d => d.id === doctorId);
                
                if (doctorIndex !== -1) {
                    const doctorName = database.doctors[doctorIndex].username;
                    const doctorIdValue = database.doctors[doctorIndex].id;
                    database.doctors.splice(doctorIndex, 1);
                    
                    // Update UI
                    populateDoctorsTable();
                    populateStaffTable();
                    updateDashboardCounts();
                    populateDoctorSelect();
                    
                    // Add to recent activity
                    addRecentActivity(`Doctor deleted: ${doctorName} (ID: ${doctorIdValue})`);
                    
                    alert('Doctor deleted successfully!');
                }
            }
        }

        // Function to edit doctor
        function editDoctor(doctorId) {
            const doctor = database.doctors.find(d => d.id === doctorId);
            if (doctor) {
                const newName = prompt('Edit doctor name:', doctor.username);
                if (newName) {
                    const oldName = doctor.username;
                    doctor.username = newName;
                    populateDoctorsTable();
                    populateStaffTable();
                    populateDoctorSelect();
                    addRecentActivity(`Doctor edited: ${oldName} name changed to ${newName} (ID: ${doctorId})`);
                }
                
                const newSpec = prompt('Edit specialization:', doctor.spec);
                if (newSpec) {
                    doctor.spec = newSpec;
                    populateDoctorsTable();
                    populateStaffTable();
                    populateDoctorSelect();
                }
                
                const newFees = prompt('Edit fees:', doctor.docFees);
                if (newFees) {
                    doctor.docFees = parseInt(newFees);
                    populateDoctorsTable();
                    populateStaffTable();
                }
                
                alert('Doctor updated successfully!');
            }
        }

        // Function to add a new staff member
        function addStaff() {
            const form = document.getElementById('add-staff-form');
            const formData = new FormData(form);
            
            const newStaff = {
                id: formData.get('staffId'),
                name: formData.get('staff'),
                role: formData.get('role'),
                email: formData.get('semail'),
                contact: formData.get('scontact'),
                password: formData.get('spassword')
            };
            
            // Check if staff already exists
            const existingStaff = database.staff.find(s => s.id === newStaff.id || s.email === newStaff.email);
            if (existingStaff) {
                alert('A staff member with this ID or email already exists!');
                return;
            }
            
            // Add to database
            database.staff.push(newStaff);
            
            // Update UI
            populateStaffTable();
            updateDashboardCounts();
            
            // Reset form
            form.reset();
            
            // Add to recent activity
            addRecentActivity(`New staff member added: ${newStaff.name} (${newStaff.role}) - ID: ${newStaff.id}`);
            
            alert('Staff member added successfully!');
        }

        // Function to delete staff prompt
        function deleteStaffPrompt(staffId) {
            const staff = database.staff.find(s => s.id === staffId);
            if (!staff) return;
            
            if (confirm(`Are you sure you want to delete staff member: ${staff.name} (ID: ${staff.id})?`)) {
                const staffIndex = database.staff.findIndex(s => s.id === staffId);
                
                if (staffIndex !== -1) {
                    const staffName = database.staff[staffIndex].name;
                    const staffIdValue = database.staff[staffIndex].id;
                    database.staff.splice(staffIndex, 1);
                    
                    // Update UI
                    populateStaffTable();
                    updateDashboardCounts();
                    
                    // Add to recent activity
                    addRecentActivity(`Staff member deleted: ${staffName} (ID: ${staffIdValue})`);
                    
                    alert('Staff member deleted successfully!');
                }
            }
        }

        // Function to edit staff
        function editStaff(staffId) {
            const staff = database.staff.find(s => s.id === staffId);
            if (staff) {
                const newName = prompt('Edit staff name:', staff.name);
                if (newName) {
                    const oldName = staff.name;
                    staff.name = newName;
                    populateStaffTable();
                    addRecentActivity(`Staff edited: ${oldName} name changed to ${newName} (ID: ${staffId})`);
                }
                
                const newRole = prompt('Edit role:', staff.role);
                if (newRole) {
                    staff.role = newRole;
                    populateStaffTable();
                }
                
                const newEmail = prompt('Edit email:', staff.email);
                if (newEmail) {
                    staff.email = newEmail;
                    populateStaffTable();
                }
                
                const newContact = prompt('Edit contact:', staff.contact);
                if (newContact) {
                    staff.contact = newContact;
                    populateStaffTable();
                }
                
                alert('Staff member updated successfully!');
            }
        }

        // Password validation function
        function checkPassword() {
            let pass = document.getElementById('dpassword').value;
            let cpass = document.getElementById('cdpassword').value;
            if (pass === cpass) {
                document.getElementById('message').style.color = '#5dd05d';
                document.getElementById('message').innerText = 'Matched';
            } else {
                document.getElementById('message').style.color = '#f55252';
                document.getElementById('message').innerText = 'Not Matching';
            }
        }

        // Alpha only validation function
        function alphaOnly(event) {
            let key = event.keyCode;
            return ((key >= 65 && key <= 90) || (key >= 97 && key <= 122) || key == 8 || key == 32);
        }

        // Setup form submissions
        function setupFormSubmissions() {
            // Prevent default form submission
            document.getElementById('add-doctor-form').addEventListener('submit', function(e) {
                e.preventDefault();
                addDoctor();
            });
            
            document.getElementById('delete-doctor-form').addEventListener('submit', function(e) {
                e.preventDefault();
                deleteDoctorFromDashboard();
            });
            
            document.getElementById('add-staff-form').addEventListener('submit', function(e) {
                e.preventDefault();
                addStaff();
            });
            
            document.getElementById('payment-search-form').addEventListener('submit', function(e) {
                e.preventDefault();
                searchPaymentsByPatient();
            });
        }

        // Setup email functionality
        function setupEmailFunctionality() {
            document.getElementById('sendExternalEmailBtn').addEventListener('click', function() {
                sendEmailToExternalPharmacy();
            });
        }

        // Setup SMS functionality
        function setupSMSFunctionality() {
            document.getElementById('sendSmsBtn').addEventListener('click', function() {
                sendSMSToPatient();
            });
        }

        // Function to view prescription details
        function viewPrescription(prescriptionId) {
            const prescription = database.prescriptions.find(p => p.id === prescriptionId);
            
            if (prescription) {
                const message = formatPrescriptionMessage(prescription);
                alert(message);
            }
        }

        // Function to send prescription
        function sendPrescription(prescriptionId) {
            currentPrescriptionId = prescriptionId;
            const prescription = database.prescriptions.find(p => p.id === prescriptionId);
            
            if (prescription) {
                // Find patient contact
                const patient = database.patients.find(p => p.pid === prescription.pid);
                if (patient) {
                    currentPatientContact = patient.contact;
                    document.getElementById('patientContactNumber').value = patient.contact;
                }
                
                const message = formatSMSMessage(prescription);
                
                // Store the message for use in both modals
                document.getElementById('smsMessage').value = message;
                document.getElementById('externalEmailMessage').value = formatEmailMessage(prescription);
                
                // Show delivery selection modal
                $('#prescriptionDeliveryModal').modal('show');
            }
        }

        // Function to select delivery method
        function selectDeliveryMethod(method) {
            $('#prescriptionDeliveryModal').modal('hide');
            
            if (method === 'sms') {
                $('#smsToPatientModal').modal('show');
            } else if (method === 'external') {
                $('#externalPharmacyModal').modal('show');
            }
        }

        // Function to format SMS message
        function formatSMSMessage(prescription) {
            return `HETH CARE HOSPITAL\nPrescription Details:\nPatient: ${prescription.fname} ${prescription.lname}\nDoctor: Dr. ${prescription.doctor}\nDisease: ${prescription.disease}\nPrescription: ${prescription.prescription}\nAllergy: ${prescription.allergy}\nDate: ${prescription.appdate}\nThank you!`;
        }

        // Function to format email message
        function formatEmailMessage(prescription) {
            return `
HETH CARE HOSPITAL - PRESCRIPTION DETAILS

Patient Information:
- Name: ${prescription.fname} ${prescription.lname}
- Patient ID: ${prescription.pid}
- National ID: ${prescription.national_id}
- Appointment Date: ${prescription.appdate}

Medical Information:
- Doctor: Dr. ${prescription.doctor}
- Disease/Diagnosis: ${prescription.disease}
- Known Allergies: ${prescription.allergy}

PRESCRIPTION:
${prescription.prescription}

Issued on: ${prescription.appdate}
Hospital Contact: +94 11 234 5678

This is an electronically generated prescription from Heth Care Hospital.
Please dispense the medication as prescribed.
            `;
        }

        // Function to format prescription message for alert
        function formatPrescriptionMessage(prescription) {
            return `
Prescription Details:
Patient: ${prescription.fname} ${prescription.lname}
Doctor: Dr. ${prescription.doctor}
Disease: ${prescription.disease}
Allergy: ${prescription.allergy}
Prescription: ${prescription.prescription}
Date: ${prescription.appdate}
Status: ${prescription.emailStatus}
            `;
        }

        // Function to send SMS to patient
        function sendSMSToPatient() {
            if (!currentPrescriptionId) return;
            
            const prescription = database.prescriptions.find(p => p.id === currentPrescriptionId);
            
            if (prescription) {
                // Update prescription status
                prescription.emailStatus = 'SMS Sent';
                
                // Update the UI
                populatePrescriptionsTable();
                
                // Show success message
                alert(`SMS sent to patient's contact number (${currentPatientContact}) successfully!`);
                
                // Close the modal
                $('#smsToPatientModal').modal('hide');
                
                // Add to recent activity
                addRecentActivity(`Prescription sent via SMS - Patient: ${prescription.fname} ${prescription.lname}, Contact: ${currentPatientContact}`);
            }
        }

        // Function to send email to external pharmacy
        function sendEmailToExternalPharmacy() {
            if (!currentPrescriptionId) return;
            
            const pharmacyEmail = document.getElementById('externalRecipientEmail').value;
            
            if (!pharmacyEmail) {
                alert('Please enter a valid pharmacy email address.');
                return;
            }
            
            const prescription = database.prescriptions.find(p => p.id === currentPrescriptionId);
            
            if (prescription) {
                // Update prescription status
                prescription.emailStatus = 'External';
                
                // Update the UI
                populatePrescriptionsTable();
                
                // Show success message
                alert(`Prescription sent to ${pharmacyEmail} successfully!`);
                
                // Close the modal
                $('#externalPharmacyModal').modal('hide');
                
                // Add to recent activity
                addRecentActivity(`Prescription sent to External Pharmacy (${pharmacyEmail}) - Patient: ${prescription.fname} ${prescription.lname}`);
                
                // Reset the email field
                document.getElementById('externalRecipientEmail').value = '';
            }
        }

        // Function to add recent activity
        function addRecentActivity(activityText) {
            const container = document.getElementById('recent-activity');
            const activityItem = document.createElement('div');
            activityItem.className = 'activity-item';
            
            const now = new Date();
            const timeString = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            activityItem.innerHTML = `
                <div class="d-flex justify-content-between">
                    <div><strong>${activityText}</strong></div>
                    <div class="activity-time">Just now</div>
                </div>
            `;
            
            // Add to the top of the activity list
            container.insertBefore(activityItem, container.firstChild);
        }

        // Function to view room details
        function viewRoomDetails(roomId) {
            const room = database.rooms.find(r => r.id === roomId);
            if (room) {
                alert(`Room Details:\nRoom No: ${room.room_no}\nBed No: ${room.bed_no}\nType: ${room.type}\nStatus: ${room.status}`);
            }
        }

        // Function to update room status
        function updateRoomStatus(roomId) {
            const room = database.rooms.find(r => r.id === roomId);
            if (room) {
                const newStatus = room.status === 'Available' ? 'Occupied' : 'Available';
                room.status = newStatus;
                populateRoomsTable();
                addRecentActivity(`Room ${room.room_no} Bed ${room.bed_no} status updated to ${newStatus}`);
                alert(`Room status updated to ${newStatus}`);
            }
        }
    </script>
</body>
</html>