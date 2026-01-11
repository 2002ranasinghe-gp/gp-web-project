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
        
        /* NEW CREATIVE DASHBOARD STYLES */
        .dashboard-container {
            padding: 20px;
            position: relative;
        }
        
        .dashboard-welcome {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-welcome::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }
        
        .dashboard-welcome h3 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .dashboard-welcome p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .dashboard-time {
            position: absolute;
            right: 25px;
            top: 25px;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #eef2f7;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1.2;
            color: #2c3e50;
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .stat-change {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
        }
        
        .stat-change.positive {
            color: #27ae60;
        }
        
        .stat-change.negative {
            color: #e74c3c;
        }
        
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #eef2f7;
        }
        
        .chart-container h5 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .activity-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .activity-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #eef2f7;
        }
        
        .activity-card h5 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            align-items: flex-start;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: var(--primary);
            font-size: 0.9rem;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            font-size: 0.9rem;
            margin-bottom: 3px;
            color: #2c3e50;
        }
        
        .activity-time {
            font-size: 0.75rem;
            color: #95a5a6;
        }
        
        .appointment-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .appointment-table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-size: 0.85rem;
            color: #7f8c8d;
            font-weight: 600;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .appointment-table td {
            padding: 10px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 0.85rem;
        }
        
        .appointment-table tr:hover {
            background: #f8f9fa;
        }
        
        .doctor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .doctor-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .doctor-card:hover {
            transform: translateY(-3px);
        }
        
        .doctor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .doctor-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .doctor-specialty {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-bottom: 8px;
        }
        
        .doctor-status {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 10px;
            display: inline-block;
        }
        
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        
        .status-busy {
            background: #f8d7da;
            color: #721c24;
        }
        
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .quick-action-btn {
            background: white;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #2c3e50;
        }
        
        .quick-action-btn:hover {
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-color: var(--primary);
        }
        
        .quick-action-btn i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }
        
        .quick-action-text {
            font-size: 0.85rem;
        }
        
        @media (max-width: 992px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
            
            .activity-section {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-welcome {
                padding: 20px;
            }
            
            .dashboard-time {
                position: relative;
                right: auto;
                top: auto;
                margin-top: 10px;
                display: inline-block;
            }
        }
        
        /* Other existing styles remain the same */
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
        
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .action-btn {
            margin: 2px;
        }
        
        /* Fixed Pharmacy Email Display */
        .fixed-pharmacy-email {
            background-color: #fff3e0;
            border: 1px solid #ffcc80;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
        
        .fixed-pharmacy-email i {
            color: #ff9800;
            margin-right: 8px;
        }
        
        /* Patient Registration Styles */
        .patient-registration-card {
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #2196f3, #21cbf3);
            color: white;
            padding: 12px 15px;
            font-weight: bold;
            font-size: 1rem;
        }
        
        .generated-nic {
            background-color: #f0f8ff;
            border: 1px solid #b3e0ff;
            padding: 8px;
            border-radius: 5px;
            font-weight: bold;
            color: #0066cc;
            margin-top: 8px;
            font-size: 0.9rem;
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
        <h3 class="text-center mb-4" style="font-size: 1.5rem;">ADMIN PANEL</h3>
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
                    <!-- CREATIVE DASHBOARD -->
                    <div class="tab-pane fade show active" id="dash-tab">
                        <div class="dashboard-container">
                            <!-- Welcome Section -->
                            <div class="dashboard-welcome">
                                <h3>Welcome back, Admin!</h3>
                                <p>Here's what's happening with your hospital today.</p>
                                <div class="dashboard-time" id="current-time"></div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="chart-container">
                                <h5>Quick Actions</h5>
                                <div class="quick-actions-grid">
                                    <a class="quick-action-btn" data-toggle="list" href="#staff-tab">
                                        <i class="fa fa-user-md"></i>
                                        <div class="quick-action-text">Add Doctor</div>
                                    </a>
                                    <a class="quick-action-btn" data-toggle="list" href="#staff-tab">
                                        <i class="fa fa-id-badge"></i>
                                        <div class="quick-action-text">Manage Staff</div>
                                    </a>
                                    <a class="quick-action-btn" data-toggle="list" href="#app-tab">
                                        <i class="fa fa-calendar"></i>
                                        <div class="quick-action-text">View Appointments</div>
                                    </a>
                                    <a class="quick-action-btn" data-toggle="list" href="#pay-tab">
                                        <i class="fa fa-credit-card"></i>
                                        <div class="quick-action-text">Payments</div>
                                    </a>
                                    <a class="quick-action-btn" data-toggle="list" href="#room-tab">
                                        <i class="fa fa-bed"></i>
                                        <div class="quick-action-text">Rooms/Beds</div>
                                    </a>
                                    <a class="quick-action-btn" data-toggle="modal" data-target="#deleteDoctorModal">
                                        <i class="fa fa-trash"></i>
                                        <div class="quick-action-text">Delete Doctor</div>
                                    </a>
                                    <a class="quick-action-btn" data-toggle="list" href="#pres-tab">
                                        <i class="fa fa-prescription"></i>
                                        <div class="quick-action-text">Prescriptions</div>
                                    </a>
                                    <a class="quick-action-btn" data-toggle="list" href="#sched-tab">
                                        <i class="fa fa-clock"></i>
                                        <div class="quick-action-text">Schedules</div>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Stats Cards -->
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        <i class="fa fa-user-md"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-title">TOTAL DOCTORS</div>
                                        <div class="stat-value" id="total-doctors">8</div>
                                        <div class="stat-change positive">
                                            <i class="fa fa-arrow-up mr-1"></i> 2 new this month
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                        <i class="fa fa-users"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-title">TOTAL PATIENTS</div>
                                        <div class="stat-value" id="total-patients">10</div>
                                        <div class="stat-change positive">
                                            <i class="fa fa-arrow-up mr-1"></i> 3 new today
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-title">TODAY'S APPOINTMENTS</div>
                                        <div class="stat-value" id="today-appointments-count">3</div>
                                        <div class="stat-change positive">
                                            <i class="fa fa-check mr-1"></i> 2 completed
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                        <i class="fa fa-credit-card"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-title">TODAY'S REVENUE</div>
                                        <div class="stat-value">Rs. 1,850</div>
                                        <div class="stat-change positive">
                                            <i class="fa fa-arrow-up mr-1"></i> 15% increase
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Charts Section -->
                            <div class="charts-row">
                                <div class="chart-container">
                                    <h5>Appointments Overview</h5>
                                    <canvas id="appointmentsChart" height="250"></canvas>
                                </div>
                                <div class="chart-container">
                                    <h5>Department Distribution</h5>
                                    <canvas id="departmentChart" height="250"></canvas>
                                </div>
                            </div>
                            
                            <!-- Activity Section -->
                            <div class="activity-section">
                                <div class="activity-card">
                                    <h5>Recent Activity <span class="badge badge-primary">New</span></h5>
                                    <div class="activity-list" id="recent-activity">
                                        <!-- Activity items will be populated by JavaScript -->
                                    </div>
                                </div>
                                
                                <div class="activity-card">
                                    <h5>Today's Appointments</h5>
                                    <table class="appointment-table">
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
                            
                            <!-- Doctors Online -->
                            <div class="chart-container">
                                <h5>Available Doctors Today</h5>
                                <div class="doctor-grid" id="available-doctors">
                                    <!-- Available doctors will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Doctors Tab (Same as before) -->
                    <div class="tab-pane fade" id="doc-tab">
                        <h4>Doctors List</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="doctor-search" placeholder="Search doctors..." onkeyup="filterTable('doctor-search', 'doctors-table-body')">
                            <button class="btn btn-primary btn-sm" onclick="exportTable('doctors-table-body', 'doctors')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered table-sm">
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
                            <ul class="pagination justify-content-center pagination-sm">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Patients Tab (Same as before) -->
                    <div class="tab-pane fade" id="pat-tab">
                        <!-- Patient Registration Form -->
                        <div class="patient-registration-card">
                            <div class="card-header-custom">
                                <i class="fa fa-user-plus mr-2"></i>Register New Patient
                            </div>
                            <div class="card-body">
                                <form id="add-patient-form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientFirstName">First Name *</label>
                                                <input type="text" class="form-control form-control-sm" id="patientFirstName" name="fname" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientLastName">Last Name *</label>
                                                <input type="text" class="form-control form-control-sm" id="patientLastName" name="lname" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientGender">Gender *</label>
                                                <select class="form-control form-control-sm" id="patientGender" name="gender" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientDOB">Date of Birth *</label>
                                                <input type="date" class="form-control form-control-sm" id="patientDOB" name="dob" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientEmail">Email Address *</label>
                                                <input type="email" class="form-control form-control-sm" id="patientEmail" name="email" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientContact">Contact Number *</label>
                                                <input type="tel" class="form-control form-control-sm" id="patientContact" name="contact" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientAddress">Address</label>
                                                <textarea class="form-control form-control-sm" id="patientAddress" name="address" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientEmergencyContact">Emergency Contact</label>
                                                <input type="tel" class="form-control form-control-sm" id="patientEmergencyContact" name="emergencyContact">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientPassword">Password *</label>
                                                <input type="password" class="form-control form-control-sm" id="patientPassword" name="password" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="patientConfirmPassword">Confirm Password *</label>
                                                <input type="password" class="form-control form-control-sm" id="patientConfirmPassword" name="cpassword" onkeyup="checkPatientPassword()" required>
                                                <small id="patientPasswordMessage" class="form-text"></small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Generated NIC Display -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="generated-nic">
                                                <i class="fa fa-id-card mr-2"></i>
                                                <span id="generatedNICDisplay">NIC will be generated after registration</span>
                                            </div>
                                            <small class="text-muted">Note: NIC (National ID) is automatically generated by the system</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-success btn-sm" onclick="addPatient()">
                                            <i class="fa fa-user-plus mr-1"></i> Register Patient
                                        </button>
                                        <button type="reset" class="btn btn-secondary btn-sm ml-2">
                                            <i class="fa fa-refresh mr-1"></i> Reset Form
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <h4>Patients List</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="patient-search" placeholder="Search patients..." onkeyup="filterTable('patient-search', 'patients-table-body')">
                            <button class="btn btn-primary btn-sm" onclick="exportTable('patients-table-body', 'patients')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Gender</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Date of Birth</th>
                                    <th>NIC (Auto-generated)</th>
                                </tr>
                            </thead>
                            <tbody id="patients-table-body">
                                <!-- Patients data will be populated by JavaScript -->
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination justify-content-center pagination-sm">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Appointments Tab (Same as before) -->
                    <div class="tab-pane fade" id="app-tab">
                        <h4>Appointments</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="appointment-search" placeholder="Search appointments..." onkeyup="filterTable('appointment-search', 'appointments-table-body')">
                            <button class="btn btn-primary btn-sm" onclick="exportTable('appointments-table-body', 'appointments')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered table-sm">
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
                            <ul class="pagination justify-content-center pagination-sm">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Prescriptions Tab (Same as before) -->
                    <div class="tab-pane fade" id="pres-tab">
                        <h4>Prescriptions</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="prescription-search" placeholder="Search prescriptions..." onkeyup="filterTable('prescription-search', 'prescriptions-table-body')">
                            <button class="btn btn-primary btn-sm" onclick="exportTable('prescriptions-table-body', 'prescriptions')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered table-sm">
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
                            <ul class="pagination justify-content-center pagination-sm">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Payments Tab (Same as before) -->
                    <div class="tab-pane fade" id="pay-tab">
                        <h4>Payments</h4>
                        <form method="post" class="form-inline mb-2" id="payment-search-form">
                            <input type="number" name="pid" class="form-control form-control-sm mr-2" placeholder="Enter Patient ID" id="payment-patient-id">
                            <button type="button" class="btn btn-success btn-sm" onclick="searchPaymentsByPatient()">View Payments</button>
                        </form>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="payment-search" placeholder="Search payments..." onkeyup="filterTable('payment-search', 'payments-table-body')">
                            <button class="btn btn-primary btn-sm" onclick="exportTable('payments-table-body', 'payments')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered table-sm">
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
                            <ul class="pagination justify-content-center pagination-sm">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Staff Schedules Tab (Same as before) -->
                    <div class="tab-pane fade" id="sched-tab">
                        <h4>Staff Schedules</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="schedule-search" placeholder="Search schedules..." onkeyup="filterTable('schedule-search', 'schedules-table-body')">
                            <button class="btn btn-primary btn-sm" onclick="exportTable('schedules-table-body', 'schedules')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered table-sm">
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
                            <ul class="pagination justify-content-center pagination-sm">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Rooms / Beds Tab (Same as before) -->
                    <div class="tab-pane fade" id="room-tab">
                        <h4>Rooms / Beds</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="room-search" placeholder="Search rooms..." onkeyup="filterTable('room-search', 'rooms-table-body')">
                            <button class="btn btn-primary btn-sm" onclick="exportTable('rooms-table-body', 'rooms')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered table-sm">
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
                            <ul class="pagination justify-content-center pagination-sm">
                                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Staff Management Tab (Same as before) -->
                    <div class="tab-pane fade" id="staff-tab">
                        <h4>Staff & Doctor Management</h4>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white py-2">
                                        <i class="fa fa-user-md mr-2"></i>Add New Doctor
                                    </div>
                                    <div class="card-body">
                                        <form id="add-doctor-form">
                                            <div class="form-group">
                                                <label>Doctor ID</label>
                                                <input type="text" name="doctorId" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" name="doctor" class="form-control form-control-sm" onkeydown="return alphaOnly(event)" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Specialization</label>
                                                <select name="special" class="form-control form-control-sm" required>
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
                                                <input type="email" name="demail" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input type="password" id="dpassword" name="dpassword" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Confirm Password</label>
                                                <input type="password" id="cdpassword" class="form-control form-control-sm" onkeyup="checkPassword()" required>
                                                <small id="message" class="form-text"></small>
                                            </div>
                                            <div class="form-group">
                                                <label>Fees (Rs.)</label>
                                                <input type="number" name="docFees" class="form-control form-control-sm" required>
                                            </div>
                                            <button type="button" class="btn btn-success btn-sm btn-block" onclick="addDoctor()">Add Doctor</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white py-2">
                                        <i class="fa fa-plus-circle mr-2"></i>Add New Staff Member
                                    </div>
                                    <div class="card-body">
                                        <form id="add-staff-form">
                                            <div class="form-group">
                                                <label>Staff ID</label>
                                                <input type="text" name="staffId" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" name="staff" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Role</label>
                                                <select name="role" class="form-control form-control-sm" required>
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
                                                <input type="email" name="semail" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Contact</label>
                                                <input type="text" name="scontact" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input type="password" name="spassword" class="form-control form-control-sm" required>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm btn-block" onclick="addStaff()">Add Staff Member</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h5>Doctors & Staff List</h5>
                        <div class="d-flex justify-content-between mb-3">
                            <input type="text" class="form-control w-25" id="staff-search" placeholder="Search by ID or Name..." onkeyup="filterStaffTable()">
                            <button class="btn btn-primary btn-sm" onclick="exportTable('staff-table-body', 'staff')">Export</button>
                        </div>
                        <table class="table table-hover table-bordered table-sm">
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
                            <ul class="pagination justify-content-center pagination-sm">
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

    <!-- Delete Doctor Modal (Same as before) -->
    <div class="modal fade" id="deleteDoctorModal" tabindex="-1" role="dialog" aria-labelledby="deleteDoctorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="deleteDoctorModalLabel">Delete Doctor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-2">
                    <form id="delete-doctor-form">
                        <div class="form-group">
                            <label>Select Doctor</label>
                            <select name="doctorId" class="form-control form-control-sm" id="doctor-select" required>
                                <option value="">Select doctor to delete</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteDoctorFromDashboard()">Delete Doctor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Prescription Delivery Selection Modal (Same as before) -->
    <div class="modal fade" id="prescriptionDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="prescriptionDeliveryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="prescriptionDeliveryModalLabel">Send Prescription</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-2">
                    <p class="mb-2">Please select how to send this prescription:</p>
                    <button type="button" class="btn btn-success pharmacy-type-btn" onclick="selectDeliveryMethod('sms')">
                        <i class="fa fa-mobile"></i> Send SMS to Patient
                    </button>
                    <button type="button" class="btn btn-warning pharmacy-type-btn" onclick="selectDeliveryMethod('external')">
                        <i class="fa fa-medkit"></i> Send to Hospital Pharmacy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SMS to Patient Modal (Same as before) -->
    <div class="modal fade" id="smsToPatientModal" tabindex="-1" role="dialog" aria-labelledby="smsToPatientModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="smsToPatientModalLabel">Send SMS to Patient</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-2">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> SMS will be sent to the patient's contact number.
                    </div>
                    <form id="smsToPatientForm">
                        <div class="form-group">
                            <label for="patientContactNumber">Patient Contact Number</label>
                            <input type="text" class="form-control form-control-sm" id="patientContactNumber" readonly>
                        </div>
                        <div class="form-group">
                            <label for="smsMessage">SMS Message</label>
                            <textarea class="form-control form-control-sm" id="smsMessage" rows="6" readonly></textarea>
                        </div>
                        <div class="sms-preview" id="smsPreview">
                            <!-- SMS preview will be shown here -->
                        </div>
                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success btn-sm" id="sendSmsBtn">Send SMS to Patient</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hospital Pharmacy Email Modal (Same as before) -->
    <div class="modal fade" id="hospitalPharmacyModal" tabindex="-1" role="dialog" aria-labelledby="hospitalPharmacyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="hospitalPharmacyModalLabel">Send to Hospital Pharmacy</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-2">
                    <div class="fixed-pharmacy-email">
                        <i class="fa fa-envelope"></i>
                        Prescription will be sent to: <strong>healthcarepharmacypp1@gmail.com</strong>
                    </div>
                    <form id="hospitalPharmacyForm">
                        <div class="form-group">
                            <label for="hospitalEmailSubject">Subject</label>
                            <input type="text" class="form-control form-control-sm" id="hospitalEmailSubject" value="Prescription from Heth Care Hospital">
                        </div>
                        <div class="form-group">
                            <label for="hospitalEmailMessage">Prescription Details</label>
                            <textarea class="form-control form-control-sm" id="hospitalEmailMessage" rows="8" readonly></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning btn-sm" id="sendHospitalEmailBtn">Send to Hospital Pharmacy</button>
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
            patients: [
                {pid: 1, fname: 'Ram', lname: 'Kumar', gender: 'Male', dob: '1990-05-15', email: 'ram@gmail.com', contact: '0771234567', address: '123 Main St, Colombo', emergencyContact: '0779876543', national_id: 'NIC123456789', password: 'ram123', cpassword: 'ram123'},
                {pid: 2, fname: 'Alia', lname: 'Bhatt', gender: 'Female', dob: '1995-08-22', email: 'alia@gmail.com', contact: '0779876543', address: '456 Park Ave, Kandy', emergencyContact: '0771234567', national_id: 'NIC987654321', password: 'alia123', cpassword: 'alia123'},
                {pid: 3, fname: 'Shahrukh', lname: 'Khan', gender: 'Male', dob: '1985-11-02', email: 'shahrukh@gmail.com', contact: '0712345678', address: '789 Beach Rd, Galle', emergencyContact: '0718765432', national_id: 'NIC111222333', password: 'shahrukh123', cpassword: 'shahrukh123'},
                {pid: 4, fname: 'Kishan', lname: 'Lal', gender: 'Male', dob: '1978-03-30', email: 'kishan@gmail.com', contact: '0765432198', address: '321 Hill St, Negombo', emergencyContact: '0761234987', national_id: 'NIC444555666', password: 'kishan123', cpassword: 'kishan123'},
                {pid: 5, fname: 'Gautam', lname: 'Shankararam', gender: 'Male', dob: '1992-07-18', email: 'gautam@gmail.com', contact: '0754321876', address: '654 River Rd, Jaffna', emergencyContact: '0756789123', national_id: 'NIC777888999', password: 'gautam123', cpassword: 'gautam123'},
                {pid: 6, fname: 'Sushant', lname: 'Singh', gender: 'Male', dob: '1988-01-25', email: 'sushant@gmail.com', contact: '0787654321', address: '987 Lake View, Anuradhapura', emergencyContact: '0781234567', national_id: 'NIC123123123', password: 'sushant123', cpassword: 'sushant123'},
                {pid: 7, fname: 'Nancy', lname: 'Deborah', gender: 'Female', dob: '1993-09-14', email: 'nancy@gmail.com', contact: '0723456789', address: '147 Temple Rd, Polonnaruwa', emergencyContact: '0729876543', national_id: 'NIC321321321', password: 'nancy123', cpassword: 'nancy123'},
                {pid: 8, fname: 'Kenny', lname: 'Sebastian', gender: 'Male', dob: '1980-12-05', email: 'kenny@gmail.com', contact: '0745678901', address: '258 Market St, Trincomalee', emergencyContact: '0741098765', national_id: 'NIC456456456', password: 'kenny123', cpassword: 'kenny123'},
                {pid: 9, fname: 'William', lname: 'Blake', gender: 'Male', dob: '1975-06-28', email: 'william@gmail.com', contact: '0798765432', address: '369 Garden Rd, Ratnapura', emergencyContact: '0792345678', national_id: 'NIC654654654', password: 'william123', cpassword: 'william123'},
                {pid: 10, fname: 'Peter', lname: 'Norvig', gender: 'Male', dob: '1965-04-12', email: 'peter@gmail.com', contact: '0734567890', address: '741 Ocean Dr, Matara', emergencyContact: '0738901234', national_id: 'NIC789789789', password: 'peter123', cpassword: 'peter123'}
            ],
            doctors: [
                {id: 'DOC001', username: 'Dr. Ashok', password: 'ashok123', email: 'ashok@gmail.com', spec: 'General', docFees: 500, available: true},
                {id: 'DOC002', username: 'Dr. Arun', password: 'arun123', email: 'arun@gmail.com', spec: 'Cardiologist', docFees: 600, available: true},
                {id: 'DOC003', username: 'Dr. Dinesh', password: 'dinesh123', email: 'dinesh@gmail.com', spec: 'General', docFees: 700, available: false},
                {id: 'DOC004', username: 'Dr. Ganesh', password: 'ganesh123', email: 'ganesh@gmail.com', spec: 'Pediatrician', docFees: 550, available: true},
                {id: 'DOC005', username: 'Dr. Kumar', password: 'kumar123', email: 'kumar@gmail.com', spec: 'Pediatrician', docFees: 800, available: true},
                {id: 'DOC006', username: 'Dr. Amit', password: 'amit123', email: 'amit@gmail.com', spec: 'Cardiologist', docFees: 1000, available: false},
                {id: 'DOC007', username: 'Dr. Abbis', password: 'abbis123', email: 'abbis@gmail.com', spec: 'Neurologist', docFees: 1500, available: true},
                {id: 'DOC008', username: 'Dr. Tiwary', password: 'tiwary123', email: 'tiwary@gmail.com', spec: 'Pediatrician', docFees: 450, available: true}
            ],
            appointments: [
                {ID: 1, pid: 1, national_id: 'NIC123456789', fname: 'Ram', lname: 'Kumar', gender: 'Male', email: 'ram@gmail.com', contact: '0771234567', doctor: 'Dr. Ganesh', docFees: 500, appdate: '2025-10-29', apptime: '10:00:00', userStatus: 1, doctorStatus: 0},
                {ID: 2, pid: 2, national_id: 'NIC987654321', fname: 'Alia', lname: 'Bhatt', gender: 'Female', email: 'alia@gmail.com', contact: '0779876543', doctor: 'Dr. Ganesh', docFees: 550, appdate: '2025-10-30', apptime: '11:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 3, pid: 3, national_id: 'NIC111222333', fname: 'Shahrukh', lname: 'Khan', gender: 'Male', email: 'shahrukh@gmail.com', contact: '0712345678', doctor: 'Dr. Dinesh', docFees: 700, appdate: '2025-11-01', apptime: '09:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 4, pid: 4, national_id: 'NIC444555666', fname: 'Kishan', lname: 'Lal', gender: 'Male', email: 'kishan@gmail.com', contact: '0765432198', doctor: 'Dr. Amit', docFees: 1000, appdate: '2025-11-02', apptime: '14:00:00', userStatus: 1, doctorStatus: 0},
                {ID: 5, pid: 5, national_id: 'NIC777888999', fname: 'Gautam', lname: 'Shankararam', gender: 'Male', email: 'gautam@gmail.com', contact: '0754321876', doctor: 'Dr. Kumar', docFees: 800, appdate: '2025-11-03', apptime: '16:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 6, pid: 6, national_id: 'NIC123123123', fname: 'Sushant', lname: 'Singh', gender: 'Male', email: 'sushant@gmail.com', contact: '0787654321', doctor: 'Dr. Abbis', docFees: 1500, appdate: '2025-11-04', apptime: '12:00:00', userStatus: 1, doctorStatus: 0},
                {ID: 7, pid: 7, national_id: 'NIC321321321', fname: 'Nancy', lname: 'Deborah', gender: 'Female', email: 'nancy@gmail.com', contact: '0723456789', doctor: 'Dr. Tiwary', docFees: 450, appdate: '2025-11-05', apptime: '10:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 8, pid: 8, national_id: 'NIC456456456', fname: 'Kenny', lname: 'Sebastian', gender: 'Male', email: 'kenny@gmail.com', contact: '0745678901', doctor: 'Dr. Ganesh', docFees: 550, appdate: '2025-11-06', apptime: '11:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 9, pid: 9, national_id: 'NIC654654654', fname: 'William', lname: 'Blake', gender: 'Male', email: 'william@gmail.com', contact: '0798765432', doctor: 'Dr. Kumar', docFees: 800, appdate: '2025-11-07', apptime: '15:00:00', userStatus: 1, doctorStatus: 1},
                {ID: 10, pid: 10, national_id: 'NIC789789789', fname: 'Peter', lname: 'Norvig', gender: 'Male', email: 'peter@gmail.com', contact: '0734567890', doctor: 'Dr. Ganesh', docFees: 500, appdate: '2025-11-08', apptime: '09:00:00', userStatus: 1, doctorStatus: 1}
            ],
            prescriptions: [
                {id: 1, doctor: 'Dr. Ganesh', pid: 1, ID: 1, fname: 'Ram', lname: 'Kumar', national_id: 'NIC123456789', appdate: '2025-10-29', apptime: '10:00:00', disease: 'Fever', allergy: 'None', prescription: 'Take paracetamol 500mg twice daily', emailStatus: 'Not Sent'},
                {id: 2, doctor: 'Dr. Ganesh', pid: 2, ID: 2, fname: 'Alia', lname: 'Bhatt', national_id: 'NIC987654321', appdate: '2025-10-30', apptime: '11:00:00', disease: 'Cold', allergy: 'None', prescription: 'Take vitamin C and rest', emailStatus: 'SMS Sent'}
            ],
            payments: [
                {id: 1, pid: 1, app_id: 1, national_id: 'NIC123456789', patient_name: 'Ram Kumar', doctor: 'Dr. Ganesh', fees: 500.00, pay_date: '2025-10-29', pay_status: 'Paid'},
                {id: 2, pid: 2, app_id: 2, national_id: 'NIC987654321', patient_name: 'Alia Bhatt', doctor: 'Dr. Ganesh', fees: 550.00, pay_date: '2025-10-30', pay_status: 'Paid'},
                {id: 3, pid: 3, app_id: 3, national_id: 'NIC111222333', patient_name: 'Shahrukh Khan', doctor: 'Dr. Dinesh', fees: 700.00, pay_date: '2025-11-01', pay_status: 'Pending'}
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
            ],
            nicCounter: 100000000 // Starting counter for NIC generation
        };

        // Current prescription being processed
        let currentPrescriptionId = null;
        let currentPatientContact = '';
        
        // Fixed Hospital Pharmacy Email
        const HOSPITAL_PHARMACY_EMAIL = 'healthcarepharmacypp1@gmail.com';

        // Initialize dashboard with data
        document.addEventListener('DOMContentLoaded', function() {
            // Set current time
            updateCurrentTime();
            setInterval(updateCurrentTime, 60000); // Update every minute
            
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
            
            // Populate available doctors
            populateAvailableDoctors();
            
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

        // Update current time
        function updateCurrentTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.getElementById('current-time').textContent = 
                now.toLocaleDateString('en-US', options);
        }

        // Update dashboard counts
        function updateDashboardCounts() {
            document.getElementById('total-doctors').textContent = database.doctors.length;
            document.getElementById('total-patients').textContent = database.patients.length;
            
            // Calculate today's appointments
            const today = new Date().toISOString().split('T')[0];
            const todayAppointments = database.appointments.filter(app => app.appdate === today);
            document.getElementById('today-appointments-count').textContent = todayAppointments.length;
            
            // Calculate completed appointments
            const completedAppointments = todayAppointments.filter(app => app.doctorStatus === 1);
            
            // Calculate revenue for today
            let todayRevenue = 0;
            completedAppointments.forEach(app => {
                todayRevenue += app.docFees;
            });
            
            // Update revenue if needed
            const revenueElement = document.querySelector('.stat-card:nth-child(4) .stat-value');
            if (revenueElement) {
                revenueElement.textContent = `Rs. ${todayRevenue.toLocaleString()}`;
            }
        }

        // Populate available doctors
        function populateAvailableDoctors() {
            const container = document.getElementById('available-doctors');
            container.innerHTML = '';
            
            database.doctors.forEach(doctor => {
                const doctorCard = document.createElement('div');
                doctorCard.className = 'doctor-card';
                
                const initials = doctor.username.split(' ').map(n => n[0]).join('');
                
                doctorCard.innerHTML = `
                    <div class="doctor-avatar">${initials}</div>
                    <div class="doctor-name">${doctor.username}</div>
                    <div class="doctor-specialty">${doctor.spec}</div>
                    <div class="doctor-status ${doctor.available ? 'status-available' : 'status-busy'}">
                        ${doctor.available ? 'Available' : 'Busy'}
                    </div>
                `;
                
                container.appendChild(doctorCard);
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
                    <td>${patient.dob || 'N/A'}</td>
                    <td><span class="badge badge-info">${patient.national_id}</span></td>
                `;
                tbody.appendChild(row);
            });
        }

        // Function to generate unique NIC
        function generateNIC() {
            // Increment the NIC counter
            database.nicCounter++;
            
            // Format the NIC with leading zeros to make it 12 digits total
            const nicNumber = database.nicCounter.toString().padStart(9, '0');
            
            // Return NIC in format: NIC + 9 digits
            return `NIC${nicNumber}`;
        }

        // Function to add a new patient
        function addPatient() {
            const form = document.getElementById('add-patient-form');
            const formData = new FormData(form);
            
            // Get form values
            const fname = formData.get('fname');
            const lname = formData.get('lname');
            const gender = formData.get('gender');
            const dob = formData.get('dob');
            const email = formData.get('email');
            const contact = formData.get('contact');
            const address = formData.get('address');
            const emergencyContact = formData.get('emergencyContact');
            const password = formData.get('password');
            const cpassword = formData.get('cpassword');
            
            // Validate required fields
            if (!fname || !lname || !gender || !dob || !email || !contact || !password || !cpassword) {
                alert('Please fill in all required fields!');
                return;
            }
            
            // Validate password match
            if (password !== cpassword) {
                alert('Passwords do not match!');
                return;
            }
            
            // Check if email already exists
            const existingPatient = database.patients.find(p => p.email === email);
            if (existingPatient) {
                alert('A patient with this email already exists!');
                return;
            }
            
            // Generate new patient ID
            const newPid = database.patients.length > 0 ? 
                Math.max(...database.patients.map(p => p.pid)) + 1 : 1;
            
            // Generate NIC automatically
            const generatedNIC = generateNIC();
            
            // Create new patient object
            const newPatient = {
                pid: newPid,
                fname: fname,
                lname: lname,
                gender: gender,
                dob: dob,
                email: email,
                contact: contact,
                address: address || '',
                emergencyContact: emergencyContact || '',
                national_id: generatedNIC, // Auto-generated NIC
                password: password,
                cpassword: cpassword
            };
            
            // Add to database
            database.patients.push(newPatient);
            
            // Update UI
            populatePatientsTable();
            updateDashboardCounts();
            
            // Show generated NIC
            document.getElementById('generatedNICDisplay').innerHTML = 
                `<strong>Generated NIC:</strong> ${generatedNIC}`;
            
            // Reset form (but keep the generated NIC displayed)
            form.reset();
            document.getElementById('patientPasswordMessage').innerText = '';
            
            // Add to recent activity
            addRecentActivity(`New patient registered: ${fname} ${lname} (NIC: ${generatedNIC})`);
            
            // Show success message
            alert(`Patient registered successfully!\n\nPatient ID: ${newPid}\nGenerated NIC: ${generatedNIC}\n\nPlease note the NIC for future reference.`);
            
            // Scroll to patient list
            document.getElementById('patients-table-body').scrollIntoView({ behavior: 'smooth' });
        }

        // Function to check patient password match
        function checkPatientPassword() {
            let pass = document.getElementById('patientPassword').value;
            let cpass = document.getElementById('patientConfirmPassword').value;
            const message = document.getElementById('patientPasswordMessage');
            
            if (pass === cpass) {
                message.style.color = '#28a745';
                message.innerText = 'Passwords match';
            } else {
                message.style.color = '#dc3545';
                message.innerText = 'Passwords do not match';
            }
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

        // Function to populate recent activity
        function populateRecentActivity() {
            const container = document.getElementById('recent-activity');
            container.innerHTML = '';
            
            // Create activity items
            const activities = [
                {icon: 'fa-user-plus', text: 'New patient registered', time: 'Just now', color: '#27ae60'},
                {icon: 'fa-calendar-check', text: 'Appointment scheduled with Dr. Ganesh', time: '2 hours ago', color: '#3498db'},
                {icon: 'fa-credit-card', text: 'Payment received from Patient ID: 4', time: '5 hours ago', color: '#9b59b6'},
                {icon: 'fa-prescription', text: 'Prescription added by Dr. Dinesh', time: '1 day ago', color: '#e74c3c'},
                {icon: 'fa-user-md', text: 'New doctor added: Dr. Smith', time: '2 days ago', color: '#f39c12'},
                {icon: 'fa-calendar-times', text: 'Appointment cancelled by Patient ID: 4', time: '3 days ago', color: '#95a5a6'}
            ];
            
            activities.forEach(activity => {
                const item = document.createElement('div');
                item.className = 'activity-item';
                item.innerHTML = `
                    <div class="activity-icon" style="background: ${activity.color}20; color: ${activity.color};">
                        <i class="fa ${activity.icon}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">${activity.text}</div>
                        <div class="activity-time">${activity.time}</div>
                    </div>
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
            
            // Sort by time
            todayAppointments.sort((a, b) => a.apptime.localeCompare(b.apptime));
            
            // Take only 5 appointments for display
            const displayAppointments = todayAppointments.slice(0, 5);
            
            displayAppointments.forEach(appointment => {
                const status = appointment.doctorStatus === 1 ? 
                    (appointment.userStatus === 1 ? 'Active' : 'Cancelled by Patient') : 
                    'Cancelled by Doctor';
                
                const statusClass = status === 'Active' ? 'status-active' : 'status-cancelled';
                const time = appointment.apptime.substring(0, 5); // Get HH:MM format
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${time}</td>
                    <td>${appointment.fname} ${appointment.lname}</td>
                    <td>${appointment.doctor}</td>
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
                    statusText = 'Sent to Pharmacy';
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
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Appointments',
                        data: [12, 19, 15, 22, 18, 10, 7],
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
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
            
            const colors = [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ];
            
            const departmentChart = new Chart(departmentCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(specCount),
                    datasets: [{
                        data: Object.values(specCount),
                        backgroundColor: colors.slice(0, Object.keys(specCount).length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
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
                username: 'Dr. ' + formData.get('doctor'),
                password: password,
                email: formData.get('demail'),
                spec: formData.get('special'),
                docFees: parseInt(formData.get('docFees')),
                available: true
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
            populateAvailableDoctors();
            
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
            populateAvailableDoctors();
            
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
                    populateAvailableDoctors();
                    
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
                    populateAvailableDoctors();
                    addRecentActivity(`Doctor edited: ${oldName} name changed to ${newName} (ID: ${doctorId})`);
                }
                
                const newSpec = prompt('Edit specialization:', doctor.spec);
                if (newSpec) {
                    doctor.spec = newSpec;
                    populateDoctorsTable();
                    populateStaffTable();
                    populateDoctorSelect();
                    populateAvailableDoctors();
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
            document.getElementById('add-patient-form').addEventListener('submit', function(e) {
                e.preventDefault();
                addPatient();
            });
            
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
            document.getElementById('sendHospitalEmailBtn').addEventListener('click', function() {
                sendEmailToHospitalPharmacy();
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
                
                const smsMessage = formatSMSMessage(prescription);
                const emailMessage = formatEmailMessage(prescription);
                
                // Store the message for use in both modals
                document.getElementById('smsMessage').value = smsMessage;
                document.getElementById('hospitalEmailMessage').value = emailMessage;
                
                // Update SMS preview
                document.getElementById('smsPreview').innerHTML = `
                    <strong>SMS Preview:</strong><br>
                    ${smsMessage.replace(/\n/g, '<br>')}
                `;
                
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
                // Changed to hospital pharmacy modal
                $('#hospitalPharmacyModal').modal('show');
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
                alert(`SMS sent to patient's contact number (${currentPatientContact}) successfully!\n\nMessage sent:\n${document.getElementById('smsMessage').value}`);
                
                // Close the modal
                $('#smsToPatientModal').modal('hide');
                
                // Add to recent activity
                addRecentActivity(`Prescription sent via SMS - Patient: ${prescription.fname} ${prescription.lname}, Contact: ${currentPatientContact}`);
            }
        }

        // Function to send email to hospital pharmacy
        function sendEmailToHospitalPharmacy() {
            if (!currentPrescriptionId) return;
            
            const prescription = database.prescriptions.find(p => p.id === currentPrescriptionId);
            
            if (prescription) {
                // Update prescription status
                prescription.emailStatus = 'External';
                
                // Update the UI
                populatePrescriptionsTable();
                
                // Show success message
                alert(`Prescription sent to Hospital Pharmacy (${HOSPITAL_PHARMACY_EMAIL}) successfully!\n\nSubject: ${document.getElementById('hospitalEmailSubject').value}`);
                
                // Close the modal
                $('#hospitalPharmacyModal').modal('hide');
                
                // Add to recent activity
                addRecentActivity(`Prescription sent to Hospital Pharmacy (${HOSPITAL_PHARMACY_EMAIL}) - Patient: ${prescription.fname} ${prescription.lname}`);
            }
        }

        // Function to add recent activity
        function addRecentActivity(activityText) {
            const container = document.getElementById('recent-activity');
            const activityItem = document.createElement('div');
            activityItem.className = 'activity-item';
            
            activityItem.innerHTML = `
                <div class="activity-icon" style="background: #3498db20; color: #3498db;">
                    <i class="fa fa-bell"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">${activityText}</div>
                    <div class="activity-time">Just now</div>
                </div>
            `;
            
            // Add to the top of the activity list
            container.insertBefore(activityItem, container.firstChild);
            
            // If there are more than 6 items, remove the last one
            if (container.children.length > 6) {
                container.removeChild(container.lastChild);
            }
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