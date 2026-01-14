<?php
// ===========================
// DATABASE CONNECTION
// ===========================
session_start();
$con = mysqli_connect("localhost", "root", "", "myhmsdb");

if(!$con){
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if required tables exist, create if not
checkAndCreateTables($con);

// ===========================
// MESSAGES VARIABLES
// ===========================
$patient_msg = "";
$doctor_msg = "";
$staff_msg = "";
$payment_msg = "";
$appointment_msg = "";
$prescription_msg = "";
$schedule_msg = "";
$edit_doctor_msg = "";
$edit_staff_msg = "";
$settings_msg = "";
$room_msg = "";
$feedback_msg = "";
$food_msg = "";
$room_booking_msg = "";
$food_order_msg = "";

// ===========================
// GET ADMIN INFO
// ===========================
$admin_id = $_SESSION['admin_id'] ?? 'ADM001';
$admin_name = $_SESSION['admin_name'] ?? 'Admin User';
$admin_email = $_SESSION['admin_email'] ?? 'admin@hospital.com';

// Get admin profile picture from database
$admin_query = mysqli_query($con, "SELECT profile_pic FROM admintb WHERE username='$admin_name'");
if($admin_query && mysqli_num_rows($admin_query) > 0){
    $admin_data = mysqli_fetch_assoc($admin_query);
    $admin_profile_pic = $admin_data['profile_pic'] ?? 'default-avatar.jpg';
} else {
    $admin_profile_pic = 'default-avatar.jpg';
}

// ===========================
// GET STATISTICS
// ===========================
$total_doctors = mysqli_num_rows(mysqli_query($con, "SELECT * FROM doctb"));
$total_patients = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patreg"));
$total_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb"));
$total_staff = mysqli_num_rows(mysqli_query($con, "SELECT * FROM stafftb"));
$today_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE appdate = CURDATE()"));
$pending_payments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM paymenttb WHERE pay_status = 'Pending'"));
$available_rooms = mysqli_num_rows(mysqli_query($con, "SELECT * FROM roomtb WHERE status = 'Available'"));
$active_prescriptions = mysqli_num_rows(mysqli_query($con, "SELECT * FROM prestb WHERE emailStatus = 'Not Sent'"));
$total_feedback = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patient_feedback"));
$today_feedback = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patient_feedback WHERE DATE(feedback_date) = CURDATE()"));
$positive_feedback = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patient_feedback WHERE rating >= 4"));
$average_rating = mysqli_fetch_array(mysqli_query($con, "SELECT AVG(rating) as avg_rating FROM patient_feedback"))['avg_rating'] ?? 0;
$total_food_items = mysqli_num_rows(mysqli_query($con, "SELECT * FROM food_menu"));
$total_food_orders = mysqli_num_rows(mysqli_query($con, "SELECT * FROM food_orders"));
$active_room_bookings = mysqli_num_rows(mysqli_query($con, "SELECT * FROM room_booking WHERE status = 'Active'"));
$pending_food_orders = mysqli_num_rows(mysqli_query($con, "SELECT * FROM food_orders WHERE status = 'Pending'"));

// ===========================
// FOOD MENU MANAGEMENT
// ===========================
if(isset($_POST['add_food'])){
    $food_name = mysqli_real_escape_string($con, $_POST['food_name']);
    $food_type = mysqli_real_escape_string($con, $_POST['food_type']);
    $meal_type = mysqli_real_escape_string($con, $_POST['meal_type']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $price = mysqli_real_escape_string($con, $_POST['price']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    $query = "INSERT INTO food_menu (food_name, food_type, meal_type, description, price, status) 
              VALUES ('$food_name', '$food_type', '$meal_type', '$description', '$price', '$status')";
    
    if(mysqli_query($con, $query)){
        $food_msg = "<div class='alert alert-success'>✅ Food item added successfully!</div>";
        $_SESSION['success'] = "Food item added!";
    } else {
        $food_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

if(isset($_POST['edit_food'])){
    $food_id = mysqli_real_escape_string($con, $_POST['food_id']);
    $food_name = mysqli_real_escape_string($con, $_POST['food_name']);
    $food_type = mysqli_real_escape_string($con, $_POST['food_type']);
    $meal_type = mysqli_real_escape_string($con, $_POST['meal_type']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $price = mysqli_real_escape_string($con, $_POST['price']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    $query = "UPDATE food_menu SET 
              food_name='$food_name',
              food_type='$food_type',
              meal_type='$meal_type',
              description='$description',
              price='$price',
              status='$status'
              WHERE id='$food_id'";
    
    if(mysqli_query($con, $query)){
        $food_msg = "<div class='alert alert-success'>✅ Food item updated successfully!</div>";
        $_SESSION['success'] = "Food item updated!";
    } else {
        $food_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

if(isset($_POST['delete_food'])){
    $food_id = mysqli_real_escape_string($con, $_POST['food_id']);
    
    $query = "DELETE FROM food_menu WHERE id='$food_id'";
    
    if(mysqli_query($con, $query)){
        $food_msg = "<div class='alert alert-success'>✅ Food item deleted successfully!</div>";
        $_SESSION['success'] = "Food item deleted!";
    } else {
        $food_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// ROOM BOOKING MANAGEMENT
// ===========================
if(isset($_POST['add_room_booking'])){
    $patient_id = mysqli_real_escape_string($con, $_POST['patient_id']);
    $patient_name = mysqli_real_escape_string($con, $_POST['patient_name']);
    $room_id = mysqli_real_escape_string($con, $_POST['room_id']);
    $booking_ref = 'BOOK' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    $check_in_date = mysqli_real_escape_string($con, $_POST['check_in_date']);
    $check_out_date = mysqli_real_escape_string($con, $_POST['check_out_date']);
    $daily_rate = mysqli_real_escape_string($con, $_POST['daily_rate']);
    $special_requests = mysqli_real_escape_string($con, $_POST['special_requests']);
    
    // Calculate total days and amount
    $check_in = new DateTime($check_in_date);
    $check_out = new DateTime($check_out_date);
    $total_days = $check_in->diff($check_out)->days;
    $total_amount = $total_days * $daily_rate;
    
    // Update room status
    mysqli_query($con, "UPDATE roomtb SET status='Occupied' WHERE id='$room_id'");
    
    $query = "INSERT INTO room_booking (patient_id, patient_name, room_id, booking_ref, check_in_date, check_out_date, total_days, daily_rate, total_amount, special_requests) 
              VALUES ('$patient_id', '$patient_name', '$room_id', '$booking_ref', '$check_in_date', '$check_out_date', '$total_days', '$daily_rate', '$total_amount', '$special_requests')";
    
    if(mysqli_query($con, $query)){
        $room_booking_msg = "<div class='alert alert-success'>✅ Room booking created successfully!<br>
                           Booking Reference: $booking_ref<br>
                           Total Amount: Rs. " . number_format($total_amount, 2) . "</div>";
        $_SESSION['success'] = "Room booking created!";
    } else {
        $room_booking_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

if(isset($_POST['edit_room_booking'])){
    $booking_id = mysqli_real_escape_string($con, $_POST['booking_id']);
    $patient_id = mysqli_real_escape_string($con, $_POST['patient_id']);
    $patient_name = mysqli_real_escape_string($con, $_POST['patient_name']);
    $room_id = mysqli_real_escape_string($con, $_POST['room_id']);
    $check_in_date = mysqli_real_escape_string($con, $_POST['check_in_date']);
    $check_out_date = mysqli_real_escape_string($con, $_POST['check_out_date']);
    $daily_rate = mysqli_real_escape_string($con, $_POST['daily_rate']);
    $special_requests = mysqli_real_escape_string($con, $_POST['special_requests']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    // Calculate total days and amount
    $check_in = new DateTime($check_in_date);
    $check_out = new DateTime($check_out_date);
    $total_days = $check_in->diff($check_out)->days;
    $total_amount = $total_days * $daily_rate;
    
    $query = "UPDATE room_booking SET 
              patient_id='$patient_id',
              patient_name='$patient_name',
              room_id='$room_id',
              check_in_date='$check_in_date',
              check_out_date='$check_out_date',
              total_days='$total_days',
              daily_rate='$daily_rate',
              total_amount='$total_amount',
              special_requests='$special_requests',
              status='$status'
              WHERE id='$booking_id'";
    
    if(mysqli_query($con, $query)){
        $room_booking_msg = "<div class='alert alert-success'>✅ Room booking updated successfully!</div>";
        $_SESSION['success'] = "Room booking updated!";
    } else {
        $room_booking_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

if(isset($_POST['cancel_room_booking'])){
    $booking_id = mysqli_real_escape_string($con, $_POST['booking_id']);
    $room_id = mysqli_real_escape_string($con, $_POST['room_id']);
    
    // Update room status back to available
    mysqli_query($con, "UPDATE roomtb SET status='Available' WHERE id='$room_id'");
    
    $query = "UPDATE room_booking SET status='Cancelled', cancelled_at=NOW() WHERE id='$booking_id'";
    
    if(mysqli_query($con, $query)){
        $room_booking_msg = "<div class='alert alert-success'>✅ Room booking cancelled successfully!</div>";
        $_SESSION['success'] = "Room booking cancelled!";
    } else {
        $room_booking_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// FOOD ORDER MANAGEMENT
// ===========================
if(isset($_POST['add_food_order'])){
    $patient_id = mysqli_real_escape_string($con, $_POST['patient_id']);
    $patient_name = mysqli_real_escape_string($con, $_POST['patient_name']);
    $booking_id = mysqli_real_escape_string($con, $_POST['booking_id']);
    $room_id = mysqli_real_escape_string($con, $_POST['room_id']);
    $food_items = mysqli_real_escape_string($con, $_POST['food_items']);
    $total_price = mysqli_real_escape_string($con, $_POST['total_price']);
    $special_instructions = mysqli_real_escape_string($con, $_POST['special_instructions']);
    $order_date = date('Y-m-d');
    $delivery_time = mysqli_real_escape_string($con, $_POST['delivery_time']);
    $status = 'Pending';
    $payment_status = 'Pending';
    
    $query = "INSERT INTO food_orders (patient_id, patient_name, booking_id, room_id, food_items, total_price, special_instructions, order_date, delivery_time, status, payment_status) 
              VALUES ('$patient_id', '$patient_name', '$booking_id', '$room_id', '$food_items', '$total_price', '$special_instructions', '$order_date', '$delivery_time', '$status', '$payment_status')";
    
    if(mysqli_query($con, $query)){
        $food_order_msg = "<div class='alert alert-success'>✅ Food order created successfully!</div>";
        $_SESSION['success'] = "Food order created!";
    } else {
        $food_order_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

if(isset($_POST['update_food_order'])){
    $order_id = mysqli_real_escape_string($con, $_POST['order_id']);
    $food_items = mysqli_real_escape_string($con, $_POST['food_items']);
    $total_price = mysqli_real_escape_string($con, $_POST['total_price']);
    $special_instructions = mysqli_real_escape_string($con, $_POST['special_instructions']);
    $delivery_time = mysqli_real_escape_string($con, $_POST['delivery_time']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $payment_status = mysqli_real_escape_string($con, $_POST['payment_status']);
    
    $query = "UPDATE food_orders SET 
              food_items='$food_items',
              total_price='$total_price',
              special_instructions='$special_instructions',
              delivery_time='$delivery_time',
              status='$status',
              payment_status='$payment_status'
              WHERE id='$order_id'";
    
    if(mysqli_query($con, $query)){
        $food_order_msg = "<div class='alert alert-success'>✅ Food order updated successfully!</div>";
        $_SESSION['success'] = "Food order updated!";
    } else {
        $food_order_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

if(isset($_POST['delete_food_order'])){
    $order_id = mysqli_real_escape_string($con, $_POST['order_id']);
    
    $query = "DELETE FROM food_orders WHERE id='$order_id'";
    
    if(mysqli_query($con, $query)){
        $food_order_msg = "<div class='alert alert-success'>✅ Food order deleted successfully!</div>";
        $_SESSION['success'] = "Food order deleted!";
    } else {
        $food_order_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// [REST OF YOUR EXISTING CODE FOR OTHER FUNCTIONALITIES]
// ===========================
// ... (Your existing code for patient, doctor, appointment, prescription, payment, schedule, room, feedback, settings management)

// ===========================
// GET DATA FROM DATABASE
// ===========================
// ... (Your existing data fetching code)

// Get food menu
$food_menu = [];
$food_result = mysqli_query($con, "SELECT * FROM food_menu ORDER BY meal_type, food_name");
if($food_result){
    while($row = mysqli_fetch_assoc($food_result)){
        $food_menu[] = $row;
    }
}

// Get room bookings
$room_bookings = [];
$room_booking_result = mysqli_query($con, "SELECT rb.*, r.room_no, r.bed_no 
                                          FROM room_booking rb 
                                          LEFT JOIN roomtb r ON rb.room_id = r.id 
                                          ORDER BY rb.check_in_date DESC");
if($room_booking_result){
    while($row = mysqli_fetch_assoc($room_booking_result)){
        $room_bookings[] = $row;
    }
}

// Get food orders
$food_orders = [];
$food_order_result = mysqli_query($con, "SELECT fo.*, r.room_no, r.bed_no 
                                        FROM food_orders fo 
                                        LEFT JOIN roomtb r ON fo.room_id = r.id 
                                        ORDER BY fo.order_date DESC, fo.delivery_time DESC");
if($food_order_result){
    while($row = mysqli_fetch_assoc($food_order_result)){
        $food_orders[] = $row;
    }
}

// Get active patients for booking/orders
$active_patients = [];
$active_patients_result = mysqli_query($con, "SELECT pid, CONCAT(fname, ' ', lname) as patient_name FROM patreg ORDER BY fname");
if($active_patients_result){
    while($row = mysqli_fetch_assoc($active_patients_result)){
        $active_patients[] = $row;
    }
}

// Get available rooms for booking
$available_rooms_list = [];
$available_rooms_result = mysqli_query($con, "SELECT id, room_no, bed_no, type FROM roomtb WHERE status = 'Available' ORDER BY room_no, bed_no");
if($available_rooms_result){
    while($row = mysqli_fetch_assoc($available_rooms_result)){
        $available_rooms_list[] = $row;
    }
}

// ===========================
// FUNCTION TO CHECK/CREATE TABLES (UPDATED WITH WARD SERVICE TABLES)
// ===========================
function checkAndCreateTables($con){
    $tables = [
        // ... (Your existing table creation queries)
        
        'food_menu' => "CREATE TABLE IF NOT EXISTS food_menu (
            id INT PRIMARY KEY AUTO_INCREMENT,
            food_name VARCHAR(100) NOT NULL,
            food_type VARCHAR(50) NOT NULL,
            meal_type VARCHAR(50) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) DEFAULT 'Available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'food_orders' => "CREATE TABLE IF NOT EXISTS food_orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            patient_id INT NOT NULL,
            patient_name VARCHAR(100) NOT NULL,
            booking_id INT,
            room_id INT NOT NULL,
            food_items TEXT NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            special_instructions TEXT,
            order_date DATE NOT NULL,
            delivery_time TIME NOT NULL,
            status VARCHAR(20) DEFAULT 'Pending',
            payment_status VARCHAR(20) DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES patreg(pid) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES roomtb(id) ON DELETE CASCADE
        )",
        
        'room_booking' => "CREATE TABLE IF NOT EXISTS room_booking (
            id INT PRIMARY KEY AUTO_INCREMENT,
            patient_id INT NOT NULL,
            patient_name VARCHAR(100) NOT NULL,
            room_id INT NOT NULL,
            booking_ref VARCHAR(50) UNIQUE,
            check_in_date DATE NOT NULL,
            check_out_date DATE,
            total_days INT,
            daily_rate DECIMAL(10,2) NOT NULL,
            total_amount DECIMAL(10,2),
            special_requests TEXT,
            status VARCHAR(20) DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            cancelled_at TIMESTAMP NULL,
            FOREIGN KEY (patient_id) REFERENCES patreg(pid) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES roomtb(id) ON DELETE CASCADE
        )"
    ];
    
    foreach($tables as $table_name => $create_sql){
        $check = mysqli_query($con, "SHOW TABLES LIKE '$table_name'");
        if(mysqli_num_rows($check) == 0){
            if(!mysqli_query($con, $create_sql)){
                echo "<div class='alert alert-danger'>❌ Error creating table $table_name: " . mysqli_error($con) . "</div>";
            }
        }
    }
    
    // Insert sample food items if table is empty
    $check_food = mysqli_query($con, "SELECT COUNT(*) as count FROM food_menu");
    $food_count = mysqli_fetch_assoc($check_food)['count'];
    
    if($food_count == 0){
        $sample_foods = [
            "('Rice and Curry', 'Regular', 'Lunch', 'White rice with chicken curry and vegetables', 8.00, 'Available')",
            "('Vegetable Rice', 'Vegetarian', 'Lunch', 'Vegetable fried rice with tofu', 9.00, 'Available')",
            "('Chicken Soup', 'Regular', 'Dinner', 'Hot chicken soup with vegetables', 6.00, 'Available')",
            "('Fruit Salad', 'Diabetic', 'Breakfast', 'Fresh fruit salad with yogurt', 5.00, 'Available')",
            "('Oatmeal', 'Low Sodium', 'Breakfast', 'Low sodium oatmeal with fruits', 4.50, 'Available')",
            "('Mashed Potatoes', 'Soft Diet', 'Dinner', 'Soft mashed potatoes with gravy', 5.50, 'Available')",
            "('Grilled Fish', 'Regular', 'Lunch', 'Grilled fish with steamed vegetables', 12.00, 'Available')",
            "('Vegetable Soup', 'Vegetarian', 'Dinner', 'Mixed vegetable soup', 5.50, 'Available')",
            "('Boiled Eggs', 'Regular', 'Breakfast', '2 boiled eggs with toast', 4.00, 'Available')",
            "('Pudding', 'Soft Diet', 'Snack', 'Vanilla pudding', 3.50, 'Available')"
        ];
        
        foreach($sample_foods as $food){
            mysqli_query($con, "INSERT INTO food_menu (food_name, food_type, meal_type, description, price, status) VALUES $food");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Healthcare Hospital</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Add to your existing styles */
        .ward-stats-card {
            border-left: 4px solid #28a745 !important;
        }
        
        .food-order-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-preparing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .booking-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .food-item-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .food-item-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .food-item-type {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 12px;
            background: #e9ecef;
            color: #495057;
        }
        
        .meal-type-badge {
            background: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        /* Modal styles for food selection */
        .food-selection-modal {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .selected-food-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 5px;
            border-left: 3px solid #28a745;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img class="logo" src="images/medical-logo.jpg" alt="Hospital Logo">
        <h4>Admin Portal</h4>
        <ul>
            <li data-target="dash-tab" class="active">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </li>
            <!-- ... (Your existing menu items) ... -->
            <li data-target="ward-tab">
                <i class="fas fa-procedures"></i> <span>Ward Services</span>
            </li>
            <li>
                <a href="logout1.php" style="color: white; text-decoration: none; display: block;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="brand">🏥 <?php echo isset($hospital_settings['hospital_name']) ? $hospital_settings['hospital_name'] : 'Healthcare Hospital'; ?></div>
            <div class="user-info">
                <!-- ... (Your existing user info) ... -->
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dash-tab">
                <!-- ... (Your existing dashboard content) ... -->
                
                <!-- Add Ward Service Stats to Dashboard -->
                <div class="row">
                    <!-- ... (Your existing stats cards) ... -->
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-success shadow h-100 py-2 ward-stats-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Active Room Bookings
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $active_room_bookings; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bed stats-icon text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-warning shadow h-100 py-2 ward-stats-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Food Orders
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $pending_food_orders; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-utensils stats-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-info shadow h-100 py-2 ward-stats-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Food Menu Items
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $total_food_items; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-hamburger stats-icon text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-primary shadow h-100 py-2 ward-stats-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Food Orders
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $total_food_orders; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-receipt stats-icon text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Add Ward Service Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12 mb-3">
                        <h4>Ward Service Actions</h4>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showTab('ward-tab')">
                            <i class="fas fa-utensils"></i>
                            <h5>Manage Food Menu</h5>
                            <p>Add/edit food items</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showTab('ward-tab'); setTimeout(() => $('#room-booking-tab').tab('show'), 100);">
                            <i class="fas fa-bed"></i>
                            <h5>Room Booking</h5>
                            <p>Manage room bookings</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="quick-action-card" onclick="showTab('ward-tab'); setTimeout(() => $('#food-orders-tab').tab('show'), 100);">
                            <i class="fas fa-concierge-bell"></i>
                            <h5>Food Orders</h5>
                            <p>Manage patient food orders</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ward Services Tab -->
            <div class="tab-pane fade" id="ward-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-procedures mr-2"></i>Ward Services Management</h3>
                </div>
                
                <?php if($food_msg): echo $food_msg; endif; ?>
                <?php if($room_booking_msg): echo $room_booking_msg; endif; ?>
                <?php if($food_order_msg): echo $food_order_msg; endif; ?>
                
                <!-- Tabs for Ward Services -->
                <ul class="nav nav-tabs" id="wardServiceTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="food-menu-tab" data-toggle="tab" href="#food-menu-content" role="tab">
                            <i class="fas fa-utensils mr-2"></i>Food Menu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="room-booking-tab" data-toggle="tab" href="#room-booking-content" role="tab">
                            <i class="fas fa-bed mr-2"></i>Room Booking
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="food-orders-tab" data-toggle="tab" href="#food-orders-content" role="tab">
                            <i class="fas fa-concierge-bell mr-2"></i>Food Orders
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content mt-4">
                    <!-- Food Menu Content -->
                    <div class="tab-pane fade show active" id="food-menu-content" role="tabpanel">
                        <!-- Add Food Form -->
                        <div class="form-card mb-4">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Add New Food Item</h5>
                            </div>
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Food Name *</label>
                                            <input type="text" class="form-control" name="food_name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Food Type *</label>
                                            <select class="form-control" name="food_type" required>
                                                <option value="Regular">Regular</option>
                                                <option value="Vegetarian">Vegetarian</option>
                                                <option value="Diabetic">Diabetic</option>
                                                <option value="Low Sodium">Low Sodium</option>
                                                <option value="Soft Diet">Soft Diet</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Meal Type *</label>
                                            <select class="form-control" name="meal_type" required>
                                                <option value="Breakfast">Breakfast</option>
                                                <option value="Lunch">Lunch</option>
                                                <option value="Dinner">Dinner</option>
                                                <option value="Snack">Snack</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Price (Rs.) *</label>
                                            <input type="number" step="0.01" class="form-control" name="price" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Status *</label>
                                            <select class="form-control" name="status" required>
                                                <option value="Available">Available</option>
                                                <option value="Unavailable">Unavailable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea class="form-control" name="description" rows="1"></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="add_food" class="btn btn-success btn-block">
                                    <i class="fas fa-plus mr-1"></i> Add Food Item
                                </button>
                            </form>
                        </div>
                        
                        <!-- Food Menu List -->
                        <div class="search-container mb-3">
                            <div class="search-bar">
                                <input type="text" class="form-control" id="food-search" placeholder="Search food items..." onkeyup="filterTable('food-search', 'food-table-body')">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>
                        
                        <div class="data-table">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Food Name</th>
                                            <th>Type</th>
                                            <th>Meal</th>
                                            <th>Description</th>
                                            <th>Price (Rs.)</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="food-table-body">
                                        <?php if(count($food_menu) > 0): ?>
                                            <?php foreach($food_menu as $food): ?>
                                            <tr>
                                                <td><?php echo $food['id']; ?></td>
                                                <td><strong><?php echo $food['food_name']; ?></strong></td>
                                                <td><span class="food-item-type"><?php echo $food['food_type']; ?></span></td>
                                                <td><span class="meal-type-badge"><?php echo $food['meal_type']; ?></span></td>
                                                <td><?php echo $food['description']; ?></td>
                                                <td>Rs. <?php echo number_format($food['price'], 2); ?></td>
                                                <td>
                                                    <?php if($food['status'] == 'Available'): ?>
                                                        <span class="status-badge status-available">Available</span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-occupied">Unavailable</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info action-btn" data-toggle="modal" data-target="#editFoodModal"
                                                            data-food-id="<?php echo $food['id']; ?>"
                                                            data-food-name="<?php echo $food['food_name']; ?>"
                                                            data-food-type="<?php echo $food['food_type']; ?>"
                                                            data-meal-type="<?php echo $food['meal_type']; ?>"
                                                            data-description="<?php echo $food['description']; ?>"
                                                            data-price="<?php echo $food['price']; ?>"
                                                            data-status="<?php echo $food['status']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger action-btn" onclick="deleteFoodItem(<?php echo $food['id']; ?>, '<?php echo $food['food_name']; ?>')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No food items found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Room Booking Content -->
                    <div class="tab-pane fade" id="room-booking-content" role="tabpanel">
                        <!-- Add Room Booking Form -->
                        <div class="form-card mb-4">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-bed mr-2"></i>Add New Room Booking</h5>
                            </div>
                            <form method="POST" id="add-room-booking-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Patient *</label>
                                            <select class="form-control" name="patient_id" id="patient_select" required onchange="updatePatientName()">
                                                <option value="">Select Patient</option>
                                                <?php foreach($active_patients as $patient): ?>
                                                <option value="<?php echo $patient['pid']; ?>" data-name="<?php echo $patient['patient_name']; ?>">
                                                    <?php echo $patient['patient_name']; ?> (ID: <?php echo $patient['pid']; ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Patient Name *</label>
                                            <input type="text" class="form-control" name="patient_name" id="patient_name" readonly required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Room *</label>
                                            <select class="form-control" name="room_id" required>
                                                <option value="">Select Room</option>
                                                <?php foreach($available_rooms_list as $room): ?>
                                                <option value="<?php echo $room['id']; ?>" data-rate="<?php echo ($room['type'] == 'Private' ? 5000 : ($room['type'] == 'ICU' ? 10000 : 3000)); ?>">
                                                    Room <?php echo $room['room_no']; ?> - Bed <?php echo $room['bed_no']; ?> (<?php echo $room['type']; ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Daily Rate (Rs.) *</label>
                                            <input type="number" step="0.01" class="form-control" name="daily_rate" id="daily_rate" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Check-in Date *</label>
                                            <input type="date" class="form-control" name="check_in_date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Check-out Date *</label>
                                            <input type="date" class="form-control" name="check_out_date" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Special Requests</label>
                                    <textarea class="form-control" name="special_requests" rows="2"></textarea>
                                </div>
                                
                                <button type="submit" name="add_room_booking" class="btn btn-success btn-block">
                                    <i class="fas fa-plus mr-1"></i> Create Room Booking
                                </button>
                            </form>
                        </div>
                        
                        <!-- Room Booking List -->
                        <div class="search-container mb-3">
                            <div class="search-bar">
                                <input type="text" class="form-control" id="booking-search" placeholder="Search bookings..." onkeyup="filterTable('booking-search', 'bookings-table-body')">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>
                        
                        <div class="data-table">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Booking Ref</th>
                                            <th>Patient</th>
                                            <th>Room</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Days</th>
                                            <th>Total (Rs.)</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bookings-table-body">
                                        <?php if(count($room_bookings) > 0): ?>
                                            <?php foreach($room_bookings as $booking): ?>
                                            <tr>
                                                <td><?php echo $booking['id']; ?></td>
                                                <td><strong><?php echo $booking['booking_ref']; ?></strong></td>
                                                <td><?php echo $booking['patient_name']; ?></td>
                                                <td>Room <?php echo $booking['room_no']; ?> - Bed <?php echo $booking['bed_no']; ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($booking['check_in_date'])); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($booking['check_out_date'])); ?></td>
                                                <td><?php echo $booking['total_days']; ?></td>
                                                <td>Rs. <?php echo number_format($booking['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php if($booking['status'] == 'Active'): ?>
                                                        <span class="booking-status status-active">Active</span>
                                                    <?php elseif($booking['status'] == 'Completed'): ?>
                                                        <span class="booking-status status-completed">Completed</span>
                                                    <?php else: ?>
                                                        <span class="booking-status status-cancelled">Cancelled</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info action-btn" data-toggle="modal" data-target="#editRoomBookingModal"
                                                            data-booking-id="<?php echo $booking['id']; ?>"
                                                            data-patient-id="<?php echo $booking['patient_id']; ?>"
                                                            data-patient-name="<?php echo $booking['patient_name']; ?>"
                                                            data-room-id="<?php echo $booking['room_id']; ?>"
                                                            data-check-in="<?php echo $booking['check_in_date']; ?>"
                                                            data-check-out="<?php echo $booking['check_out_date']; ?>"
                                                            data-daily-rate="<?php echo $booking['daily_rate']; ?>"
                                                            data-special-requests="<?php echo $booking['special_requests']; ?>"
                                                            data-status="<?php echo $booking['status']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <?php if($booking['status'] == 'Active'): ?>
                                                    <button class="btn btn-sm btn-danger action-btn" onclick="cancelRoomBooking(<?php echo $booking['id']; ?>, <?php echo $booking['room_id']; ?>)">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="10" class="text-center">No room bookings found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Food Orders Content -->
                    <div class="tab-pane fade" id="food-orders-content" role="tabpanel">
                        <!-- Add Food Order Form -->
                        <div class="form-card mb-4">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-concierge-bell mr-2"></i>Add New Food Order</h5>
                            </div>
                            <form method="POST" id="add-food-order-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Patient *</label>
                                            <select class="form-control" name="patient_id" id="order_patient_select" required onchange="updateOrderPatientName()">
                                                <option value="">Select Patient</option>
                                                <?php foreach($active_patients as $patient): ?>
                                                <option value="<?php echo $patient['pid']; ?>" data-name="<?php echo $patient['patient_name']; ?>">
                                                    <?php echo $patient['patient_name']; ?> (ID: <?php echo $patient['pid']; ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Patient Name *</label>
                                            <input type="text" class="form-control" name="patient_name" id="order_patient_name" readonly required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Booking Reference</label>
                                            <select class="form-control" name="booking_id" id="booking_select">
                                                <option value="">Select Booking (Optional)</option>
                                                <?php foreach($room_bookings as $booking): ?>
                                                    <?php if($booking['status'] == 'Active'): ?>
                                                    <option value="<?php echo $booking['id']; ?>" data-room-id="<?php echo $booking['room_id']; ?>">
                                                        <?php echo $booking['booking_ref']; ?> - <?php echo $booking['patient_name']; ?>
                                                    </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Room *</label>
                                            <select class="form-control" name="room_id" id="order_room_select" required>
                                                <option value="">Select Room</option>
                                                <?php foreach($available_rooms_list as $room): ?>
                                                <option value="<?php echo $room['id']; ?>">
                                                    Room <?php echo $room['room_no']; ?> - Bed <?php echo $room['bed_no']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Delivery Time *</label>
                                            <input type="time" class="form-control" name="delivery_time" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Total Price (Rs.) *</label>
                                            <input type="number" step="0.01" class="form-control" name="total_price" id="total_price" required readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Food Items *</label>
                                    <button type="button" class="btn btn-sm btn-primary mb-2" data-toggle="modal" data-target="#selectFoodModal">
                                        <i class="fas fa-plus mr-1"></i> Select Food Items
                                    </button>
                                    <div id="selected-food-items" class="mt-2">
                                        <!-- Selected food items will appear here -->
                                    </div>
                                    <input type="hidden" name="food_items" id="food_items_input" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Special Instructions</label>
                                    <textarea class="form-control" name="special_instructions" rows="2"></textarea>
                                </div>
                                
                                <button type="submit" name="add_food_order" class="btn btn-success btn-block">
                                    <i class="fas fa-plus mr-1"></i> Create Food Order
                                </button>
                            </form>
                        </div>
                        
                        <!-- Food Orders List -->
                        <div class="search-container mb-3">
                            <div class="search-bar">
                                <input type="text" class="form-control" id="order-search" placeholder="Search orders..." onkeyup="filterTable('order-search', 'orders-table-body')">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>
                        
                        <div class="data-table">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Patient</th>
                                            <th>Room</th>
                                            <th>Food Items</th>
                                            <th>Total (Rs.)</th>
                                            <th>Order Date</th>
                                            <th>Delivery Time</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="orders-table-body">
                                        <?php if(count($food_orders) > 0): ?>
                                            <?php foreach($food_orders as $order): ?>
                                            <tr>
                                                <td><?php echo $order['id']; ?></td>
                                                <td><?php echo $order['patient_name']; ?></td>
                                                <td>Room <?php echo $order['room_no']; ?> - Bed <?php echo $order['bed_no']; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info action-btn" onclick="viewOrderItems('<?php echo $order['food_items']; ?>')">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </td>
                                                <td>Rs. <?php echo number_format($order['total_price'], 2); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($order['delivery_time'])); ?></td>
                                                <td>
                                                    <?php if($order['status'] == 'Pending'): ?>
                                                        <span class="food-order-status status-pending">Pending</span>
                                                    <?php elseif($order['status'] == 'Preparing'): ?>
                                                        <span class="food-order-status status-preparing">Preparing</span>
                                                    <?php elseif($order['status'] == 'Delivered'): ?>
                                                        <span class="food-order-status status-delivered">Delivered</span>
                                                    <?php else: ?>
                                                        <span class="food-order-status status-cancelled">Cancelled</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($order['payment_status'] == 'Paid'): ?>
                                                        <span class="status-badge status-active">Paid</span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-pending">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info action-btn" data-toggle="modal" data-target="#editFoodOrderModal"
                                                            data-order-id="<?php echo $order['id']; ?>"
                                                            data-food-items='<?php echo $order['food_items']; ?>'
                                                            data-total-price="<?php echo $order['total_price']; ?>"
                                                            data-special-instructions="<?php echo $order['special_instructions']; ?>"
                                                            data-delivery-time="<?php echo $order['delivery_time']; ?>"
                                                            data-status="<?php echo $order['status']; ?>"
                                                            data-payment-status="<?php echo $order['payment_status']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger action-btn" onclick="deleteFoodOrder(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="10" class="text-center">No food orders found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ... (Your existing tabs for other sections) ... -->
        </div>
    </div>

    <!-- Modals for Ward Services -->
    
    <!-- Edit Food Modal -->
    <div class="modal fade" id="editFoodModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Food Item</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="edit-food-form">
                        <input type="hidden" name="food_id" id="edit_food_id">
                        <div class="form-group">
                            <label>Food Name</label>
                            <input type="text" class="form-control" name="food_name" id="edit_food_name" required>
                        </div>
                        <div class="form-group">
                            <label>Food Type</label>
                            <select class="form-control" name="food_type" id="edit_food_type" required>
                                <option value="Regular">Regular</option>
                                <option value="Vegetarian">Vegetarian</option>
                                <option value="Diabetic">Diabetic</option>
                                <option value="Low Sodium">Low Sodium</option>
                                <option value="Soft Diet">Soft Diet</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Meal Type</label>
                            <select class="form-control" name="meal_type" id="edit_meal_type" required>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Snack">Snack</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Price (Rs.)</label>
                            <input type="number" step="0.01" class="form-control" name="price" id="edit_price" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="status" id="edit_food_status" required>
                                <option value="Available">Available</option>
                                <option value="Unavailable">Unavailable</option>
                            </select>
                        </div>
                        <button type="submit" name="edit_food" class="btn btn-warning btn-block">
                            <i class="fas fa-save mr-1"></i> Update Food Item
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Room Booking Modal -->
    <div class="modal fade" id="editRoomBookingModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Room Booking</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="edit-room-booking-form">
                        <input type="hidden" name="booking_id" id="edit_booking_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Patient ID</label>
                                    <input type="text" class="form-control" name="patient_id" id="edit_patient_id" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Patient Name</label>
                                    <input type="text" class="form-control" name="patient_name" id="edit_patient_name" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Room ID</label>
                                    <input type="text" class="form-control" name="room_id" id="edit_room_id" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Daily Rate (Rs.)</label>
                                    <input type="number" step="0.01" class="form-control" name="daily_rate" id="edit_daily_rate" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Check-in Date</label>
                                    <input type="date" class="form-control" name="check_in_date" id="edit_check_in_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Check-out Date</label>
                                    <input type="date" class="form-control" name="check_out_date" id="edit_check_out_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Special Requests</label>
                            <textarea class="form-control" name="special_requests" id="edit_special_requests" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="status" id="edit_booking_status" required>
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" name="edit_room_booking" class="btn btn-warning btn-block">
                            <i class="fas fa-save mr-1"></i> Update Room Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Food Order Modal -->
    <div class="modal fade" id="editFoodOrderModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Food Order</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="edit-food-order-form">
                        <input type="hidden" name="order_id" id="edit_order_id">
                        <div class="form-group">
                            <label>Food Items (JSON)</label>
                            <textarea class="form-control" name="food_items" id="edit_food_items" rows="3" required></textarea>
                            <small class="text-muted">Format: [{"id":1,"name":"Food","price":100,"qty":2}]</small>
                        </div>
                        <div class="form-group">
                            <label>Total Price (Rs.)</label>
                            <input type="number" step="0.01" class="form-control" name="total_price" id="edit_total_price" required>
                        </div>
                        <div class="form-group">
                            <label>Special Instructions</label>
                            <textarea class="form-control" name="special_instructions" id="edit_special_instructions" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Delivery Time</label>
                                    <input type="time" class="form-control" name="delivery_time" id="edit_delivery_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control" name="status" id="edit_order_status" required>
                                        <option value="Pending">Pending</option>
                                        <option value="Preparing">Preparing</option>
                                        <option value="Delivered">Delivered</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Payment Status</label>
                            <select class="form-control" name="payment_status" id="edit_payment_status" required>
                                <option value="Pending">Pending</option>
                                <option value="Paid">Paid</option>
                            </select>
                        </div>
                        <button type="submit" name="update_food_order" class="btn btn-warning btn-block">
                            <i class="fas fa-save mr-1"></i> Update Food Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Select Food Items Modal -->
    <div class="modal fade" id="selectFoodModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Food Items</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body food-selection-modal">
                    <div class="row">
                        <?php foreach($food_menu as $food): ?>
                            <?php if($food['status'] == 'Available'): ?>
                            <div class="col-md-6 mb-3">
                                <div class="food-item-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo $food['food_name']; ?></h6>
                                            <span class="food-item-type"><?php echo $food['food_type']; ?></span>
                                            <span class="meal-type-badge ml-2"><?php echo $food['meal_type']; ?></span>
                                        </div>
                                        <div class="text-right">
                                            <strong>Rs. <?php echo number_format($food['price'], 2); ?></strong>
                                        </div>
                                    </div>
                                    <p class="mb-2 text-muted small"><?php echo $food['description']; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="input-group input-group-sm" style="width: 120px;">
                                            <div class="input-group-prepend">
                                                <button class="btn btn-outline-secondary" type="button" onclick="decrementQuantity(<?php echo $food['id']; ?>)">-</button>
                                            </div>
                                            <input type="number" class="form-control text-center" id="qty_<?php echo $food['id']; ?>" value="0" min="0" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" onclick="incrementQuantity(<?php echo $food['id']; ?>)">+</button>
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-success" onclick="addToOrder(<?php echo $food['id']; ?>, '<?php echo $food['food_name']; ?>', <?php echo $food['price']; ?>)">
                                            <i class="fas fa-plus mr-1"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateSelectedItems()">Done</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Order Items Modal -->
    <div class="modal fade" id="viewOrderItemsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Items</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="order-items-list">
                        <!-- Items will be displayed here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ... (Your existing modals) ... -->

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ward Service JavaScript Functions
        
        let selectedFoodItems = [];
        let orderItems = {};
        
        // Update patient name when patient is selected
        function updatePatientName() {
            const select = document.getElementById('patient_select');
            const selectedOption = select.options[select.selectedIndex];
            const patientName = selectedOption.getAttribute('data-name');
            document.getElementById('patient_name').value = patientName;
        }
        
        // Update order patient name
        function updateOrderPatientName() {
            const select = document.getElementById('order_patient_select');
            const selectedOption = select.options[select.selectedIndex];
            const patientName = selectedOption.getAttribute('data-name');
            document.getElementById('order_patient_name').value = patientName;
        }
        
        // Update room when booking is selected
        $(document).ready(function() {
            $('#booking_select').change(function() {
                const selectedOption = $(this).find('option:selected');
                const roomId = selectedOption.data('room-id');
                if(roomId) {
                    $('#order_room_select').val(roomId);
                }
            });
            
            // Update daily rate based on room type
            $('select[name="room_id"]').change(function() {
                const selectedOption = $(this).find('option:selected');
                const dailyRate = selectedOption.data('rate');
                if(dailyRate) {
                    $('#daily_rate').val(dailyRate);
                }
            });
            
            // Set today's date for check-in
            $('input[name="check_in_date"]').val(new Date().toISOString().split('T')[0]);
            
            // Set tomorrow's date for check-out
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            $('input[name="check_out_date"]').val(tomorrow.toISOString().split('T')[0]);
            
            // Set delivery time to next hour
            const nextHour = new Date();
            nextHour.setHours(nextHour.getHours() + 1);
            nextHour.setMinutes(0);
            $('input[name="delivery_time"]').val(nextHour.toTimeString().slice(0,5));
            
            // Initialize modals
            $('#editFoodModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                $('#edit_food_id').val(button.data('food-id'));
                $('#edit_food_name').val(button.data('food-name'));
                $('#edit_food_type').val(button.data('food-type'));
                $('#edit_meal_type').val(button.data('meal-type'));
                $('#edit_description').val(button.data('description'));
                $('#edit_price').val(button.data('price'));
                $('#edit_food_status').val(button.data('status'));
            });
            
            $('#editRoomBookingModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                $('#edit_booking_id').val(button.data('booking-id'));
                $('#edit_patient_id').val(button.data('patient-id'));
                $('#edit_patient_name').val(button.data('patient-name'));
                $('#edit_room_id').val(button.data('room-id'));
                $('#edit_check_in_date').val(button.data('check-in'));
                $('#edit_check_out_date').val(button.data('check-out'));
                $('#edit_daily_rate').val(button.data('daily-rate'));
                $('#edit_special_requests').val(button.data('special-requests'));
                $('#edit_booking_status').val(button.data('status'));
            });
            
            $('#editFoodOrderModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                $('#edit_order_id').val(button.data('order-id'));
                $('#edit_food_items').val(button.data('food-items'));
                $('#edit_total_price').val(button.data('total-price'));
                $('#edit_special_instructions').val(button.data('special-instructions'));
                $('#edit_delivery_time').val(button.data('delivery-time'));
                $('#edit_order_status').val(button.data('status'));
                $('#edit_payment_status').val(button.data('payment-status'));
            });
        });
        
        // Food item quantity functions
        function incrementQuantity(foodId) {
            const input = $(`#qty_${foodId}`);
            input.val(parseInt(input.val()) + 1);
        }
        
        function decrementQuantity(foodId) {
            const input = $(`#qty_${foodId}`);
            const currentVal = parseInt(input.val());
            if(currentVal > 0) {
                input.val(currentVal - 1);
            }
        }
        
        // Add food item to order
        function addToOrder(foodId, foodName, price) {
            const quantity = parseInt($(`#qty_${foodId}`).val());
            if(quantity > 0) {
                if(!orderItems[foodId]) {
                    orderItems[foodId] = {
                        id: foodId,
                        name: foodName,
                        price: price,
                        quantity: 0
                    };
                }
                orderItems[foodId].quantity = quantity;
                
                // Reset quantity input
                $(`#qty_${foodId}`).val(0);
                
                // Show success message
                showToast(`${foodName} added to order (x${quantity})`);
            }
        }
        
        // Update selected items display
        function updateSelectedItems() {
            const selectedItemsDiv = $('#selected-food-items');
            const foodItemsInput = $('#food_items_input');
            let totalPrice = 0;
            let itemsArray = [];
            
            selectedItemsDiv.empty();
            
            for(const foodId in orderItems) {
                const item = orderItems[foodId];
                if(item.quantity > 0) {
                    const itemTotal = item.price * item.quantity;
                    totalPrice += itemTotal;
                    
                    itemsArray.push({
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        quantity: item.quantity
                    });
                    
                    selectedItemsDiv.append(`
                        <div class="selected-food-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>${item.name}</strong><br>
                                    <small>Rs. ${item.price.toFixed(2)} × ${item.quantity}</small>
                                </div>
                                <div>
                                    <strong>Rs. ${itemTotal.toFixed(2)}</strong>
                                    <button type="button" class="btn btn-sm btn-danger ml-2" onclick="removeItem(${item.id})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `);
                }
            }
            
            if(itemsArray.length === 0) {
                selectedItemsDiv.html('<p class="text-muted">No items selected</p>');
            }
            
            // Update hidden input with JSON
            foodItemsInput.val(JSON.stringify(itemsArray));
            
            // Update total price
            $('#total_price').val(totalPrice.toFixed(2));
            
            // Close modal
            $('#selectFoodModal').modal('hide');
        }
        
        // Remove item from order
        function removeItem(foodId) {
            delete orderItems[foodId];
            updateSelectedItems();
        }
        
        // View order items
        function viewOrderItems(itemsJson) {
            try {
                const items = JSON.parse(itemsJson);
                const itemsList = $('#order-items-list');
                itemsList.empty();
                
                let total = 0;
                items.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    
                    itemsList.append(`
                        <div class="selected-food-item mb-2">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>${item.name}</strong><br>
                                    <small>Rs. ${item.price.toFixed(2)} × ${item.quantity}</small>
                                </div>
                                <div>
                                    <strong>Rs. ${itemTotal.toFixed(2)}</strong>
                                </div>
                            </div>
                        </div>
                    `);
                });
                
                itemsList.append(`
                    <div class="mt-3 pt-2 border-top">
                        <div class="d-flex justify-content-between">
                            <h5>Total:</h5>
                            <h5>Rs. ${total.toFixed(2)}</h5>
                        </div>
                    </div>
                `);
                
                $('#viewOrderItemsModal').modal('show');
            } catch(e) {
                alert('Error displaying order items: ' + e.message);
            }
        }
        
        // Delete food item
        function deleteFoodItem(foodId, foodName) {
            if(confirm(`Are you sure you want to delete "${foodName}"? This action cannot be undone.`)) {
                const form = $('<form>').attr({
                    method: 'POST',
                    style: 'display: none;'
                });
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'food_id',
                    value: foodId
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'delete_food',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Cancel room booking
        function cancelRoomBooking(bookingId, roomId) {
            if(confirm('Are you sure you want to cancel this room booking?')) {
                const form = $('<form>').attr({
                    method: 'POST',
                    style: 'display: none;'
                });
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'booking_id',
                    value: bookingId
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'room_id',
                    value: roomId
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'cancel_room_booking',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Delete food order
        function deleteFoodOrder(orderId) {
            if(confirm('Are you sure you want to delete this food order?')) {
                const form = $('<form>').attr({
                    method: 'POST',
                    style: 'display: none;'
                });
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'order_id',
                    value: orderId
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'delete_food_order',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Show toast notification
        function showToast(message) {
            const toast = $(`
                <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="3000" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                    <div class="toast-header">
                        <strong class="mr-auto">Notification</strong>
                        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `);
            
            $('body').append(toast);
            toast.toast('show');
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        // Form validation for room booking
        $(document).ready(function() {
            $('#add-room-booking-form').submit(function(e) {
                const checkIn = new Date($('input[name="check_in_date"]').val());
                const checkOut = new Date($('input[name="check_out_date"]').val());
                
                if(checkOut <= checkIn) {
                    e.preventDefault();
                    alert('Check-out date must be after check-in date!');
                    return false;
                }
                
                return true;
            });
            
            $('#add-food-order-form').submit(function(e) {
                const foodItems = $('#food_items_input').val();
                if(!foodItems || foodItems === '[]') {
                    e.preventDefault();
                    alert('Please select at least one food item!');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>