<?php
// patient-dashboard.php
// ===========================
// SECURITY HEADERS & CACHE CONTROL
// ===========================
ob_start(); // Start output buffering
session_start();

// Prevent caching of secure pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// ===========================
// DATABASE CONNECTION
// ===========================
$con = mysqli_connect("localhost", "root", "", "myhmsdb");

if(!$con){
    die("Database connection failed: " . mysqli_connect_error());
}

// ===========================
// SESSION VALIDATION
// ===========================
if(!isset($_SESSION['patient'])){
    $_SESSION['error'] = "Session expired. Please login again.";
    header("Location: ../index.php");
    exit();
}

// Validate session timeout (30 minutes)
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    $_SESSION['error'] = "Session expired. Please login again.";
    header("Location: ../index.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// ===========================
// PATIENT DATA FETCH
// ===========================
$email = mysqli_real_escape_string($con, $_SESSION['patient']);
$query = "SELECT * FROM patreg WHERE email='$email' LIMIT 1";
$result = mysqli_query($con, $query);

if(!$result || mysqli_num_rows($result) == 0){
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$patient = mysqli_fetch_assoc($result);
$patient_id = $patient['pid'];
$patient_name = $patient['fname'] . ' ' . $patient['lname'];
$national_id = $patient['national_id'];

// ===========================
// GET STATISTICS FOR DASHBOARD
// ===========================
$total_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email'"));
$confirmed_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email' AND doctorStatus=1"));
$pending_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email' AND doctorStatus=0"));
$total_prescriptions = mysqli_num_rows(mysqli_query($con, "SELECT * FROM prestb WHERE national_id='$national_id'"));
$pending_payments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM paymenttb WHERE national_id='$national_id' AND pay_status='Pending'"));
$completed_payments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM paymenttb WHERE national_id='$national_id' AND pay_status='Paid'"));
$today_appointments = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointmenttb WHERE email='$email' AND appdate = CURDATE()"));

// ===========================
// WARD SERVICE STATISTICS
// ===========================
$total_room_bookings = mysqli_num_rows(mysqli_query($con, "SELECT * FROM ward_bookings WHERE patient_id='$patient_id'"));
$active_room_bookings = mysqli_num_rows(mysqli_query($con, "SELECT * FROM ward_bookings WHERE patient_id='$patient_id' AND status='Active'"));
$completed_room_bookings = mysqli_num_rows(mysqli_query($con, "SELECT * FROM ward_bookings WHERE patient_id='$patient_id' AND status='Completed'"));
$total_food_orders = mysqli_num_rows(mysqli_query($con, "SELECT * FROM food_orders WHERE patient_id='$patient_id'"));

// ===========================
// GET DATA FOR TABLES
// ===========================
$appointments = [];
$prescriptions = [];
$payments = [];
$doctors = [];
$rooms = [];
$room_bookings = [];
$food_orders = [];
$food_menu = [];

// Get appointments
$appointment_query = mysqli_query($con, "SELECT a.*, d.spec FROM appointmenttb a LEFT JOIN doctb d ON a.doctor = d.username WHERE a.email='$email' ORDER BY a.appdate DESC");
while($row = mysqli_fetch_assoc($appointment_query)){
    $appointments[] = $row;
}

// Get prescriptions
$prescription_query = mysqli_query($con, "SELECT * FROM prestb WHERE national_id='$national_id' ORDER BY appdate DESC");
while($row = mysqli_fetch_assoc($prescription_query)){
    $prescriptions[] = $row;
}

// Get payments
$payment_query = mysqli_query($con, "SELECT * FROM paymenttb WHERE national_id='$national_id' ORDER BY pay_date DESC");
while($row = mysqli_fetch_assoc($payment_query)){
    $payments[] = $row;
}

// Get doctors
$doctor_query = mysqli_query($con, "SELECT * FROM doctb ORDER BY username");
while($row = mysqli_fetch_assoc($doctor_query)){
    $doctors[] = $row;
}

// Get available rooms - CHANGE THIS QUERY BASED ON YOUR ROOM TABLE STRUCTURE
// Assuming your room table has columns: room_id, room_no, room_type, status, rate, description
$room_query = mysqli_query($con, "SELECT * FROM room WHERE status='Available' ORDER BY room_type, room_no");
while($row = mysqli_fetch_assoc($room_query)){
    $rooms[] = $row;
}

// Get patient's room bookings
$booking_query = mysqli_query($con, "SELECT wb.*, r.room_no, r.room_type, r.rate 
                                    FROM ward_bookings wb 
                                    JOIN room r ON wb.room_id = r.room_id 
                                    WHERE wb.patient_id='$patient_id' 
                                    ORDER BY wb.check_in_date DESC");
while($row = mysqli_fetch_assoc($booking_query)){
    $room_bookings[] = $row;
}

// Get patient's food orders
$food_query = mysqli_query($con, "SELECT * FROM food_orders WHERE patient_id='$patient_id' ORDER BY order_date DESC");
while($row = mysqli_fetch_assoc($food_query)){
    $food_orders[] = $row;
}

// Get food menu
$menu_query = mysqli_query($con, "SELECT * FROM food_menu WHERE status='Available' ORDER BY meal_type, food_type");
while($row = mysqli_fetch_assoc($menu_query)){
    $food_menu[] = $row;
}

// ===========================
// UPDATE PROFILE
// ===========================
$profile_msg = "";
if(isset($_POST['update_profile'])){
    $contact = mysqli_real_escape_string($con, $_POST['contact']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $emergencyContact = mysqli_real_escape_string($con, $_POST['emergencyContact']);
    
    $update_query = "UPDATE patreg SET contact='$contact', address='$address', emergencyContact='$emergencyContact' WHERE pid='$patient_id'";
    
    if(mysqli_query($con, $update_query)){
        $profile_msg = "<div class='alert alert-success'>✅ Profile updated successfully!</div>";
        $query = "SELECT * FROM patreg WHERE email='$email' LIMIT 1";
        $result = mysqli_query($con, $query);
        $patient = mysqli_fetch_assoc($result);
        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $profile_msg = "<div class='alert alert-danger'>❌ Error updating profile: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// CHANGE PASSWORD
// ===========================
$password_msg = "";
if(isset($_POST['change_password'])){
    $current_password = mysqli_real_escape_string($con, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($con, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);
    
    if($current_password == $patient['password']){
        if($new_password === $confirm_password){
            if(strlen($new_password) >= 6){
                $update_query = "UPDATE patreg SET password='$new_password' WHERE pid='$patient_id'";
                if(mysqli_query($con, $update_query)){
                    $password_msg = "<div class='alert alert-success'>✅ Password changed successfully!</div>";
                    $_SESSION['success'] = "Password changed successfully!";
                } else {
                    $password_msg = "<div class='alert alert-danger'>❌ Error updating password: " . mysqli_error($con) . "</div>";
                }
            } else {
                $password_msg = "<div class='alert alert-danger'>❌ Password must be at least 6 characters!</div>";
            }
        } else {
            $password_msg = "<div class='alert alert-danger'>❌ New passwords do not match!</div>";
        }
    } else {
        $password_msg = "<div class='alert alert-danger'>❌ Current password is incorrect!</div>";
    }
}

// ===========================
// CANCEL APPOINTMENT
// ===========================
$appointment_msg = "";
if(isset($_POST['cancel_appointment'])){
    $appointment_id = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $reason = "Cancelled by patient";
    
    $query = "UPDATE appointmenttb SET 
              appointmentStatus='cancelled',
              cancelledBy='Patient',
              cancellationReason='$reason',
              userStatus=0 
              WHERE ID='$appointment_id' AND email='$email'";
    
    if(mysqli_query($con, $query)){
        $appointment_msg = "<div class='alert alert-success'>✅ Appointment cancelled successfully!</div>";
        $_SESSION['success'] = "Appointment cancelled!";
    } else {
        $appointment_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// BOOK APPOINTMENT
// ===========================
$book_msg = "";
if(isset($_POST['book_appointment'])){
    $doctor = mysqli_real_escape_string($con, $_POST['doctor']);
    $appdate = mysqli_real_escape_string($con, $_POST['appdate']);
    $apptime = mysqli_real_escape_string($con, $_POST['apptime']);
    $reason = mysqli_real_escape_string($con, $_POST['reason'] ?? '');
    
    $check_appointment = mysqli_query($con, "SELECT * FROM appointmenttb WHERE doctor='$doctor' AND appdate='$appdate' AND apptime='$apptime'");
    
    if(mysqli_num_rows($check_appointment) > 0){
        $book_msg = "<div class='alert alert-warning'>⚠️ This time slot is already booked. Please choose another time.</div>";
    } else {
        $doctor_query = mysqli_query($con, "SELECT * FROM doctb WHERE username='$doctor'");
        if(mysqli_num_rows($doctor_query) > 0){
            $doctor_data = mysqli_fetch_assoc($doctor_query);
            $docFees = $doctor_data['docFees'];
            
            $query = "INSERT INTO appointmenttb (pid, national_id, fname, lname, gender, email, contact, doctor, docFees, appdate, apptime, appointment_reason) 
                      VALUES ('{$patient['pid']}', '{$patient['national_id']}', '{$patient['fname']}', '{$patient['lname']}', 
                              '{$patient['gender']}', '{$patient['email']}', '{$patient['contact']}', 
                              '$doctor', '$docFees', '$appdate', '$apptime', '$reason')";
            
            if(mysqli_query($con, $query)){
                $appointment_id = mysqli_insert_id($con);
                
                $payment_query = "INSERT INTO paymenttb (pid, appointment_id, national_id, patient_name, doctor, fees, pay_date) 
                                  VALUES ('{$patient['pid']}', '$appointment_id', '{$patient['national_id']}', 
                                          '{$patient['fname']} {$patient['lname']}', '$doctor', '$docFees', '$appdate')";
                mysqli_query($con, $payment_query);
                
                $book_msg = "<div class='alert alert-success'>✅ Appointment booked successfully!<br>
                            Appointment ID: APT$appointment_id<br>
                            Date: $appdate at $apptime<br>
                            Doctor: Dr. $doctor<br>
                            Please arrive 15 minutes before your appointment time.</div>";
                $_SESSION['success'] = "Appointment booked successfully!";
            } else {
                $book_msg = "<div class='alert alert-danger'>❌ Error booking appointment: " . mysqli_error($con) . "</div>";
            }
        } else {
            $book_msg = "<div class='alert alert-danger'>❌ Doctor not found!</div>";
        }
    }
}

// ===========================
// MAKE PAYMENT
// ===========================
$payment_msg = "";
if(isset($_POST['make_payment'])){
    $payment_id = mysqli_real_escape_string($con, $_POST['payment_id']);
    $method = mysqli_real_escape_string($con, $_POST['method']);
    
    $receipt_no = 'REC' . date('Ymd') . str_pad($payment_id, 3, '0', STR_PAD_LEFT);
    
    $query = "UPDATE paymenttb SET 
              pay_status='Paid',
              payment_method='$method',
              receipt_no='$receipt_no'
              WHERE id='$payment_id' AND national_id='$national_id'";
    
    if(mysqli_query($con, $query)){
        $payment_msg = "<div class='alert alert-success'>✅ Payment completed successfully! Receipt No: $receipt_no</div>";
        $_SESSION['success'] = "Payment completed!";
    } else {
        $payment_msg = "<div class='alert alert-danger'>❌ Error: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// BOOK ROOM
// ===========================
$ward_msg = "";
if(isset($_POST['book_room'])){
    $room_id = mysqli_real_escape_string($con, $_POST['room_id']);
    $check_in = mysqli_real_escape_string($con, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($con, $_POST['check_out']);
    $special_requests = mysqli_real_escape_string($con, $_POST['special_requests'] ?? '');
    
    // Check if room is available
    $check_room = mysqli_query($con, "SELECT * FROM room WHERE room_id='$room_id' AND status='Available'");
    
    if(mysqli_num_rows($check_room) > 0){
        $room_data = mysqli_fetch_assoc($check_room);
        $daily_rate = $room_data['rate']; // Assuming 'rate' column exists
        
        // Calculate number of days
        $datetime1 = new DateTime($check_in);
        $datetime2 = new DateTime($check_out);
        $interval = $datetime1->diff($datetime2);
        $days = $interval->days;
        
        if($days <= 0){
            $ward_msg = "<div class='alert alert-danger'>❌ Check-out date must be after check-in date!</div>";
        } else {
            $total_amount = $days * $daily_rate;
            
            // Generate booking reference
            $booking_ref = 'WRB' . date('Ymd') . str_pad($patient_id, 3, '0', STR_PAD_LEFT);
            
            $query = "INSERT INTO ward_bookings (patient_id, patient_name, room_id, booking_ref, check_in_date, check_out_date, 
                      total_days, daily_rate, total_amount, special_requests, status) 
                      VALUES ('$patient_id', '$patient_name', '$room_id', '$booking_ref', '$check_in', '$check_out', 
                              '$days', '$daily_rate', '$total_amount', '$special_requests', 'Active')";
            
            if(mysqli_query($con, $query)){
                $booking_id = mysqli_insert_id($con);
                
                // Update room status - change to your room table column name
                mysqli_query($con, "UPDATE room SET status='Occupied' WHERE room_id='$room_id'");
                
                // Create payment record
                $payment_query = "INSERT INTO paymenttb (pid, patient_name, national_id, appointment_id, 
                                  fees, pay_date, pay_status, payment_type) 
                                  VALUES ('$patient_id', '$patient_name', '$national_id', 
                                          '$booking_id', '$total_amount', '$check_in', 
                                          'Pending', 'Ward Service')";
                mysqli_query($con, $payment_query);
                
                $ward_msg = "<div class='alert alert-success'>✅ Room booked successfully!<br>
                            Booking Reference: $booking_ref<br>
                            Room: {$room_data['room_type']} - Room {$room_data['room_no']}<br>
                            Duration: $days days (Rs. $daily_rate/day)<br>
                            Total Amount: Rs. $total_amount<br>
                            Check-in: $check_in | Check-out: $check_out</div>";
                $_SESSION['success'] = "Room booked successfully!";
            } else {
                $ward_msg = "<div class='alert alert-danger'>❌ Error booking room: " . mysqli_error($con) . "</div>";
            }
        }
    } else {
        $ward_msg = "<div class='alert alert-danger'>❌ Room not available!</div>";
    }
}

// ===========================
// ORDER FOOD
// ===========================
$food_msg = "";
if(isset($_POST['order_food'])){
    $booking_id = isset($_POST['booking_id']) ? mysqli_real_escape_string($con, $_POST['booking_id']) : NULL;
    $room_id = isset($_POST['room_id']) ? mysqli_real_escape_string($con, $_POST['room_id']) : NULL;
    $food_items = $_POST['food_items'] ?? [];
    $special_instructions = mysqli_real_escape_string($con, $_POST['special_instructions'] ?? '');
    
    if(empty($food_items)){
        $food_msg = "<div class='alert alert-danger'>❌ Please select at least one food item!</div>";
    } else {
        $total_price = 0;
        $selected_items = [];
        
        foreach($food_items as $food_id => $quantity){
            if($quantity > 0){
                $food_query = mysqli_query($con, "SELECT * FROM food_menu WHERE id='$food_id'");
                if($food_data = mysqli_fetch_assoc($food_query)){
                    $item_total = $food_data['price'] * $quantity;
                    $total_price += $item_total;
                    
                    $selected_items[] = [
                        'id' => $food_id,
                        'name' => $food_data['food_name'],
                        'qty' => $quantity,
                        'price' => $food_data['price'],
                        'total' => $item_total
                    ];
                }
            }
        }
        
        if($total_price > 0){
            $food_items_json = json_encode($selected_items);
            $order_ref = 'FOD' . date('YmdHi') . str_pad($patient_id, 3, '0', STR_PAD_LEFT);
            
            $query = "INSERT INTO food_orders (patient_id, patient_name, booking_id, room_id, food_items, 
                      total_price, special_instructions, status) 
                      VALUES ('$patient_id', '$patient_name', " . 
                      ($booking_id ? "'$booking_id'" : "NULL") . ", " .
                      ($room_id ? "'$room_id'" : "NULL") . ", 
                      '$food_items_json', '$total_price', '$special_instructions', 'Pending')";
            
            if(mysqli_query($con, $query)){
                $order_id = mysqli_insert_id($con);
                
                // Create payment record
                $payment_query = "INSERT INTO paymenttb (pid, patient_name, national_id, appointment_id, 
                                  fees, pay_date, pay_status, payment_type) 
                                  VALUES ('$patient_id', '$patient_name', '$national_id', 
                                          '$order_id', '$total_price', NOW(), 
                                          'Pending', 'Food Order')";
                mysqli_query($con, $payment_query);
                
                $item_list = "";
                foreach($selected_items as $item){
                    $item_list .= "{$item['name']} x{$item['qty']} - Rs. {$item['total']}<br>";
                }
                
                $food_msg = "<div class='alert alert-success'>✅ Food ordered successfully!<br>
                            Order Reference: $order_ref<br>
                            Items:<br>$item_list
                            Total: Rs. $total_price<br>
                            Status: Pending (Will be delivered within 30 minutes)</div>";
                $_SESSION['success'] = "Food ordered successfully!";
            } else {
                $food_msg = "<div class='alert alert-danger'>❌ Error ordering food: " . mysqli_error($con) . "</div>";
            }
        } else {
            $food_msg = "<div class='alert alert-danger'>❌ Please select valid food items!</div>";
        }
    }
}

// ===========================
// CANCEL ROOM BOOKING
// ===========================
if(isset($_POST['cancel_room_booking'])){
    $booking_id = mysqli_real_escape_string($con, $_POST['booking_id']);
    
    $query = "UPDATE ward_bookings SET status='Cancelled', cancelled_at=NOW() 
              WHERE id='$booking_id' AND patient_id='$patient_id'";
    
    if(mysqli_query($con, $query)){
        // Get room ID to update status
        $booking_query = mysqli_query($con, "SELECT room_id FROM ward_bookings WHERE id='$booking_id'");
        if($booking_data = mysqli_fetch_assoc($booking_query)){
            mysqli_query($con, "UPDATE room SET status='Available' WHERE room_id='{$booking_data['room_id']}'");
        }
        
        $ward_msg = "<div class='alert alert-success'>✅ Room booking cancelled successfully!</div>";
        $_SESSION['success'] = "Room booking cancelled!";
    } else {
        $ward_msg = "<div class='alert alert-danger'>❌ Error cancelling booking: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// SEND FEEDBACK
// ===========================
$feedback_msg = "";
if(isset($_POST['send_feedback'])){
    $feedback = mysqli_real_escape_string($con, $_POST['feedback']);
    $rating = mysqli_real_escape_string($con, $_POST['rating']);
    
    $query = "INSERT INTO feedbacktb (patient_id, patient_name, email, feedback, rating, created_date) 
              VALUES ('$patient_id', '$patient_name', '$email', '$feedback', '$rating', NOW())";
    
    if(mysqli_query($con, $query)){
        $feedback_msg = "<div class='alert alert-success'>✅ Thank you for your feedback!</div>";
        $_SESSION['success'] = "Feedback submitted!";
    } else {
        $feedback_msg = "<div class='alert alert-danger'>❌ Error submitting feedback: " . mysqli_error($con) . "</div>";
    }
}

// ===========================
// CHECK SESSION MESSAGES
// ===========================
if(isset($_SESSION['success'])){
    $success_msg = $_SESSION['success'];
    unset($_SESSION['success']);
} else {
    $success_msg = "";
}

if(isset($_SESSION['error'])){
    $error_msg = $_SESSION['error'];
    unset($_SESSION['error']);
} else {
    $error_msg = "";
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Healthcare Hospital</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <style>
        /* Keep all your existing CSS styles */
        /* ... (all the CSS styles from your original code remain the same) ... */
        
        /* Additional styles for food ordering */
        .food-item-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .food-item-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .food-item-img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .food-quantity-input {
            width: 60px;
            text-align: center;
        }
        .food-category {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        /* Room status colors */
        .room-available { color: #28a745; }
        .room-occupied { color: #dc3545; }
        .room-maintenance { color: #ffc107; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img class="logo" src="images/medical-logo.jpg" alt="Hospital Logo">
        <h4>Patient Portal</h4>
        <ul>
            <li data-target="dashboard-tab" class="active">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </li>
            <li data-target="appointments-tab">
                <i class="fas fa-calendar-check"></i> <span>Appointments</span>
            </li>
            <li data-target="ward-services-tab">
                <i class="fas fa-bed"></i> <span>Ward Services</span>
            </li>
            <li data-target="prescriptions-tab">
                <i class="fas fa-prescription"></i> <span>Prescriptions</span>
            </li>
            <li data-target="payments-tab">
                <i class="fas fa-credit-card"></i> <span>Payments</span>
            </li>
            <li data-target="doctors-tab">
                <i class="fas fa-user-md"></i> <span>Our Doctors</span>
            </li>
            <li data-target="profile-tab">
                <i class="fas fa-user-cog"></i> <span>Profile</span>
            </li>
            <li data-target="feedback-tab">
                <i class="fas fa-comment-dots"></i> <span>Feedback</span>
            </li>
            <!-- Logout Link with confirmation -->
            <li class="logout-item">
                <a href="logout.php" onclick="return confirmLogout()" style="color: white; text-decoration: none; display: block;" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="brand">🏥 Healthcare Hospital - Patient Portal</div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($patient['fname'], 0, 1)); ?>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($patient_name); ?></strong><br>
                    <small>Patient ID: <?php echo htmlspecialchars($patient['pid']); ?></small>
                </div>
                <button class="btn btn-sm btn-outline-light ml-3" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard-tab">
                <?php if($success_msg): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_msg; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if($error_msg): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_msg; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2>Welcome back, <?php echo htmlspecialchars($patient['fname']); ?>!</h2>
                            <p class="mb-0">Your health is our priority. Access all your medical services in one place.</p>
                            <small><i class="fas fa-clock mr-1"></i> Session active since: <?php echo date('H:i:s', $_SESSION['last_activity'] ?? time()); ?></small>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="user-avatar" style="width: 80px; height: 80px; font-size: 36px; margin-left: auto;">
                                <?php echo strtoupper(substr($patient['fname'], 0, 1)); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-label">TOTAL APPOINTMENTS</div>
                                        <div class="stats-number text-primary">
                                            <?php echo $total_appointments; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-alt stats-icon text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-label">CONFIRMED APPOINTMENTS</div>
                                        <div class="stats-number text-success">
                                            <?php echo $confirmed_appointments; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle stats-icon text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-label">ROOM BOOKINGS</div>
                                        <div class="stats-number text-info">
                                            <?php echo $total_room_bookings; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bed stats-icon text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-label">FOOD ORDERS</div>
                                        <div class="stats-number text-warning">
                                            <?php echo $total_food_orders; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-utensils stats-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Services -->
                <div class="row mt-4">
                    <div class="col-12 mb-3">
                        <h4>Quick Services</h4>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="image-card" onclick="showTab('appointments-tab')">
                            <div class="card-image" style="background: linear-gradient(135deg, #0077b6, #0096c7);">
                                <div class="card-overlay-text">
                                    <h5 class="mb-0"><i class="fas fa-calendar-plus mr-2"></i>Book Appointment</h5>
                                </div>
                            </div>
                            <div class="card-content">
                                <p>Schedule a new appointment with our specialists</p>
                                <button class="btn btn-primary btn-sm">Book Now</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="image-card" onclick="showTab('ward-services-tab')">
                            <div class="card-image" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                <div class="card-overlay-text">
                                    <h5 class="mb-0"><i class="fas fa-bed mr-2"></i>Ward Services</h5>
                                </div>
                            </div>
                            <div class="card-content">
                                <p>Book VIP/Normal rooms & order hospital food</p>
                                <button class="btn btn-success btn-sm">View Services</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="image-card" onclick="showTab('prescriptions-tab')">
                            <div class="card-image" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                                <div class="card-overlay-text">
                                    <h5 class="mb-0"><i class="fas fa-prescription mr-2"></i>View Prescriptions</h5>
                                </div>
                            </div>
                            <div class="card-content">
                                <p>Check your medical prescriptions online</p>
                                <button class="btn btn-info btn-sm">View All</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="image-card" onclick="showTab('payments-tab')">
                            <div class="card-image" style="background: linear-gradient(135deg, #6f42c1, #6610f2);">
                                <div class="card-overlay-text">
                                    <h5 class="mb-0"><i class="fas fa-credit-card mr-2"></i>Make Payment</h5>
                                </div>
                            </div>
                            <div class="card-content">
                                <p>Pay your medical bills securely online</p>
                                <button class="btn btn-primary btn-sm">Pay Now</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="table-header">
                                <h5 class="mb-0"><i class="fas fa-calendar-day mr-2"></i>Today's Appointments</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Time</th>
                                            <th>Doctor</th>
                                            <th>Specialization</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $today_query = mysqli_query($con, "SELECT a.*, d.spec FROM appointmenttb a LEFT JOIN doctb d ON a.doctor = d.username WHERE a.email='$email' AND a.appdate = CURDATE() ORDER BY a.apptime");
                                        if(mysqli_num_rows($today_query) > 0):
                                            while($row = mysqli_fetch_assoc($today_query)):
                                        ?>
                                        <tr>
                                            <td><?php echo date('h:i A', strtotime($row['apptime'])); ?></td>
                                            <td>Dr. <?php echo htmlspecialchars($row['doctor']); ?></td>
                                            <td><?php echo htmlspecialchars($row['spec']); ?></td>
                                            <td>
                                                <?php if($row['doctorStatus'] == 1): ?>
                                                    <span class="status-badge status-active">Confirmed</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-pending">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($row['appointmentStatus'] == 'active' && $row['doctorStatus'] == 1): ?>
                                                    <button class="btn btn-sm btn-danger action-btn" onclick="cancelAppointment(<?php echo $row['ID']; ?>)">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No appointments for today</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Room Bookings -->
                <?php if($active_room_bookings > 0): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="table-header">
                                <h5 class="mb-0"><i class="fas fa-bed mr-2"></i>Active Room Bookings</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Room</th>
                                            <th>Type</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $active_bookings_query = mysqli_query($con, "SELECT wb.*, r.room_no, r.room_type FROM ward_bookings wb JOIN room r ON wb.room_id = r.room_id WHERE wb.patient_id='$patient_id' AND wb.status='Active' ORDER BY wb.check_in_date");
                                        while($row = mysqli_fetch_assoc($active_bookings_query)):
                                        ?>
                                        <tr>
                                            <td>Room <?php echo htmlspecialchars($row['room_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['room_type']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($row['check_in_date'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($row['check_out_date'])); ?></td>
                                            <td>Rs. <?php echo number_format($row['total_amount'], 2); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="orderFood(<?php echo $row['id']; ?>, <?php echo $row['room_id']; ?>)">
                                                    <i class="fas fa-utensils"></i> Order Food
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="cancelRoomBooking(<?php echo $row['id']; ?>)">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Emergency Contact -->
                <div class="emergency-contact">
                    <h4><i class="fas fa-phone-alt mr-2"></i>Emergency Contact</h4>
                    <h2>011-234-5678</h2>
                    <p>Available 24/7 for emergencies</p>
                </div>
            </div>

            <!-- Appointments Tab -->
            <div class="tab-pane fade" id="appointments-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-calendar-check mr-2"></i>My Appointments</h3>
                    <button class="btn btn-primary" data-toggle="collapse" data-target="#bookAppointmentForm">
                        <i class="fas fa-plus mr-2"></i>Book New Appointment
                    </button>
                </div>
                
                <?php if($appointment_msg): echo $appointment_msg; endif; ?>
                <?php if($book_msg): echo $book_msg; endif; ?>
                
                <!-- Book Appointment Form -->
                <div class="form-card mb-4 collapse show" id="bookAppointmentForm">
                    <div class="form-card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-plus mr-2"></i>Book New Appointment</h5>
                    </div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Select Doctor *</label>
                                    <select class="form-control" name="doctor" required>
                                        <option value="">Select Doctor</option>
                                        <?php foreach($doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['username']; ?>">
                                            Dr. <?php echo $doctor['username']; ?> (<?php echo $doctor['spec']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Reason for Visit</label>
                                    <input type="text" class="form-control" name="reason" placeholder="Optional - e.g., Routine Checkup">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Appointment Date *</label>
                                    <input type="date" class="form-control" name="appdate" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Appointment Time *</label>
                                    <input type="time" class="form-control" name="apptime" required>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            Please arrive 15 minutes before your appointment time. Bring your medical records if any.
                        </div>
                        <button type="submit" name="book_appointment" class="btn btn-success btn-block">
                            <i class="fas fa-calendar-plus mr-2"></i>Book Appointment
                        </button>
                    </form>
                </div>
                
                <!-- Appointments Table -->
                <div class="data-table">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-list mr-2"></i>All Appointments</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Fees (Rs.)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($appointments) > 0): ?>
                                    <?php foreach($appointments as $app): ?>
                                    <tr>
                                        <td>APT<?php echo str_pad($app['ID'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($app['appdate'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($app['apptime'])); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($app['doctor']); ?></td>
                                        <td><?php echo htmlspecialchars($app['spec']); ?></td>
                                        <td>Rs. <?php echo number_format($app['docFees'], 2); ?></td>
                                        <td>
                                            <?php if($app['appointmentStatus'] == 'cancelled'): ?>
                                                <span class="status-badge status-cancelled">Cancelled</span>
                                            <?php elseif($app['doctorStatus'] == 1): ?>
                                                <span class="status-badge status-active">Confirmed</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($app['appointmentStatus'] == 'active' && $app['doctorStatus'] == 1): ?>
                                                <button class="btn btn-sm btn-danger action-btn" onclick="cancelAppointment(<?php echo $app['ID']; ?>)">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No appointments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Ward Services Tab -->
            <div class="tab-pane fade" id="ward-services-tab">
                <h3 class="mb-4"><i class="fas fa-bed mr-2"></i>Ward Services</h3>
                
                <?php if($ward_msg): echo $ward_msg; endif; ?>
                <?php if($food_msg): echo $food_msg; endif; ?>
                
                <!-- Available Rooms -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-door-open mr-2"></i>Available Rooms</h5>
                            </div>
                            <div class="row">
                                <?php if(count($rooms) > 0): ?>
                                    <?php foreach($rooms as $room): ?>
                                    <div class="col-lg-4 col-md-6 mb-4">
                                        <div class="room-card">
                                            <div class="room-header <?php echo $room['room_type'] == 'VIP' ? 'room-vip' : 'room-normal'; ?>">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-bed mr-2"></i>
                                                    <?php echo htmlspecialchars($room['room_type']); ?> Room
                                                </h5>
                                            </div>
                                            <div class="room-body">
                                                <h4>Room <?php echo htmlspecialchars($room['room_no']); ?></h4>
                                                <p class="text-muted"><?php echo htmlspecialchars($room['description'] ?? 'Comfortable hospital room'); ?></p>
                                                
                                                <ul class="room-features">
                                                    <li><i class="fas fa-check"></i> Air Conditioning</li>
                                                    <li><i class="fas fa-check"></i> Private Bathroom</li>
                                                    <li><i class="fas fa-check"></i> TV & WiFi</li>
                                                    <li><i class="fas fa-check"></i> Nurse Call Button</li>
                                                    <?php if($room['room_type'] == 'VIP'): ?>
                                                    <li><i class="fas fa-check"></i> Mini Fridge</li>
                                                    <li><i class="fas fa-check"></i> Sofa Bed</li>
                                                    <li><i class="fas fa-check"></i> Room Service</li>
                                                    <?php endif; ?>
                                                </ul>
                                                
                                                <div class="mt-3">
                                                    <h3 class="text-primary">Rs. <?php echo number_format($room['rate'], 2); ?>/day</h3>
                                                    <span class="status-badge status-available">Available</span>
                                                </div>
                                                
                                                <button class="btn btn-primary btn-block mt-3" onclick="bookRoomModal(<?php echo $room['room_id']; ?>, '<?php echo $room['room_type']; ?>', <?php echo $room['rate']; ?>)">
                                                    <i class="fas fa-calendar-plus mr-2"></i>Book This Room
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            No rooms available at the moment. Please check back later.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Room Booking Form (Modal) -->
                <div class="form-card mb-4" id="bookRoomForm" style="display: none;">
                    <div class="form-card-header">
                        <h5 class="mb-0"><i class="fas fa-bed mr-2"></i>Book Room: <span id="roomTypeDisplay"></span></h5>
                    </div>
                    <form method="POST" id="roomBookingForm">
                        <input type="hidden" name="room_id" id="room_id">
                        <input type="hidden" id="daily_rate">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Room Type</label>
                                    <input type="text" class="form-control" id="room_type_display" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Daily Rate</label>
                                    <input type="text" class="form-control" id="daily_rate_display" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Check-in Date *</label>
                                    <input type="date" class="form-control" name="check_in" id="check_in" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Check-out Date *</label>
                                    <input type="date" class="form-control" name="check_out" id="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Special Requests</label>
                            <textarea class="form-control" name="special_requests" rows="3" placeholder="Any special requirements or preferences..."></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span id="calculationInfo">Please select dates to see total amount.</span>
                        </div>
                        <div class="text-center mb-3">
                            <h3 id="totalAmountDisplay" class="text-success">Total: Rs. 0.00</h3>
                        </div>
                        <button type="submit" name="book_room" class="btn btn-success btn-block">
                            <i class="fas fa-calendar-check mr-2"></i>Confirm Booking
                        </button>
                        <button type="button" class="btn btn-secondary btn-block mt-2" onclick="hideBookRoomForm()">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                    </form>
                </div>
                
                <!-- Food Ordering Section -->
                <div class="form-card mb-4" id="orderFoodSection">
                    <div class="form-card-header">
                        <h5 class="mb-0"><i class="fas fa-utensils mr-2"></i>Order Food</h5>
                    </div>
                    
                    <!-- Food Categories -->
                    <div class="row">
                        <?php
                        $meal_types = ['Breakfast', 'Lunch', 'Dinner', 'Snack'];
                        foreach($meal_types as $meal_type):
                            $meal_foods = array_filter($food_menu, function($food) use ($meal_type) {
                                return $food['meal_type'] == $meal_type;
                            });
                            
                            if(!empty($meal_foods)):
                        ?>
                        <div class="col-12 mb-4">
                            <div class="food-category">
                                <h5 class="mb-0"><i class="fas fa-<?php echo $meal_type == 'Breakfast' ? 'coffee' : ($meal_type == 'Lunch' ? 'utensils' : ($meal_type == 'Dinner' ? 'moon' : 'cookie')); ?> mr-2"></i><?php echo $meal_type; ?></h5>
                            </div>
                            <div class="row">
                                <?php foreach($meal_foods as $food): ?>
                                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                    <div class="food-item-card" id="food-item-<?php echo $food['id']; ?>">
                                        <h6><?php echo htmlspecialchars($food['food_name']); ?></h6>
                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($food['description']); ?></p>
                                        <p class="text-success font-weight-bold">Rs. <?php echo number_format($food['price'], 2); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge badge-info"><?php echo $food['food_type']; ?></span>
                                            <div class="quantity-control">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="decreaseQuantity(<?php echo $food['id']; ?>)">-</button>
                                                <input type="number" class="food-quantity-input" id="quantity-<?php echo $food['id']; ?>" value="0" min="0" max="10" data-price="<?php echo $food['price']; ?>">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="increaseQuantity(<?php echo $food['id']; ?>)">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                    
                    <!-- Order Form -->
                    <form method="POST" id="foodOrderForm" class="mt-4">
                        <input type="hidden" name="booking_id" id="form_booking_id" value="">
                        <input type="hidden" name="room_id" id="form_room_id" value="">
                        <div class="form-group">
                            <label>Special Instructions</label>
                            <textarea class="form-control" name="special_instructions" rows="2" placeholder="Any dietary restrictions or preferences..."></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            Food will be delivered within 30 minutes of ordering. Minimum order: Rs. 200.00
                        </div>
                        <div class="text-center mb-3">
                            <h4 id="foodTotalDisplay" class="text-success">Total: Rs. 0.00</h4>
                        </div>
                        <button type="submit" name="order_food" class="btn btn-success btn-block" id="orderFoodBtn" disabled>
                            <i class="fas fa-shopping-cart mr-2"></i>Place Order
                        </button>
                    </form>
                </div>
                
                <!-- My Room Bookings -->
                <div class="data-table mb-4">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-list mr-2"></i>My Room Bookings</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Room</th>
                                    <th>Type</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Days</th>
                                    <th>Amount (Rs.)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($room_bookings) > 0): ?>
                                    <?php foreach($room_bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['booking_ref']); ?></td>
                                        <td>Room <?php echo htmlspecialchars($booking['room_no']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['room_type']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($booking['check_in_date'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($booking['check_out_date'])); ?></td>
                                        <td><?php echo $booking['total_days']; ?></td>
                                        <td>Rs. <?php echo number_format($booking['total_amount'], 2); ?></td>
                                        <td>
                                            <?php if($booking['status'] == 'Active'): ?>
                                                <span class="status-badge status-active">Active</span>
                                            <?php elseif($booking['status'] == 'Completed'): ?>
                                                <span class="status-badge status-paid">Completed</span>
                                            <?php elseif($booking['status'] == 'Cancelled'): ?>
                                                <span class="status-badge status-cancelled">Cancelled</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending"><?php echo $booking['status']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($booking['status'] == 'Active'): ?>
                                                <button class="btn btn-sm btn-info" onclick="quickOrderFood(<?php echo $booking['id']; ?>, <?php echo $booking['room_id']; ?>)">
                                                    <i class="fas fa-utensils"></i> Food
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="cancelRoomBooking(<?php echo $booking['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No room bookings found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- My Food Orders -->
                <div class="data-table">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-utensils mr-2"></i>My Food Orders</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order Date</th>
                                    <th>Items</th>
                                    <th>Total (Rs.)</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($food_orders) > 0): ?>
                                    <?php foreach($food_orders as $order): 
                                        $items = json_decode($order['food_items'], true);
                                        $item_count = is_array($items) ? count($items) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <small>
                                            <?php 
                                            if(is_array($items)) {
                                                $item_names = array_slice(array_column($items, 'name'), 0, 2);
                                                echo implode(', ', $item_names);
                                                if($item_count > 2) echo ' +' . ($item_count - 2) . ' more';
                                            } else {
                                                echo 'No items';
                                            }
                                            ?>
                                            </small>
                                        </td>
                                        <td>Rs. <?php echo number_format($order['total_price'], 2); ?></td>
                                        <td>
                                            <?php if($order['status'] == 'Delivered'): ?>
                                                <span class="status-badge status-active">Delivered</span>
                                            <?php elseif($order['status'] == 'Preparing'): ?>
                                                <span class="status-badge status-pending">Preparing</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending"><?php echo $order['status']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($order['payment_status'] == 'Paid'): ?>
                                                <span class="status-badge status-paid">Paid</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No food orders found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Prescriptions Tab -->
            <div class="tab-pane fade" id="prescriptions-tab">
                <h3 class="mb-4"><i class="fas fa-prescription mr-2"></i>My Prescriptions</h3>
                
                <div class="data-table">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-list mr-2"></i>All Prescriptions</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Disease</th>
                                    <th>Allergy</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($prescriptions) > 0): ?>
                                    <?php foreach($prescriptions as $pres): ?>
                                    <tr>
                                        <td>PRE<?php echo str_pad($pres['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($pres['appdate'])); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($pres['doctor']); ?></td>
                                        <td><?php echo htmlspecialchars($pres['disease']); ?></td>
                                        <td><?php echo htmlspecialchars($pres['allergy']); ?></td>
                                        <td>
                                            <?php if($pres['emailStatus'] == 'Sent'): ?>
                                                <span class="status-badge status-active">Sent</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Not Sent</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No prescriptions found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payments Tab -->
            <div class="tab-pane fade" id="payments-tab">
                <h3 class="mb-4"><i class="fas fa-credit-card mr-2"></i>Payment History</h3>
                
                <?php if($payment_msg): echo $payment_msg; endif; ?>
                
                <div class="data-table">
                    <div class="table-header">
                        <h5 class="mb-0"><i class="fas fa-list mr-2"></i>All Payments</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Date</th>
                                    <th>Service Type</th>
                                    <th>Amount (Rs.)</th>
                                    <th>Method</th>
                                    <th>Receipt No</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($payments) > 0): ?>
                                    <?php foreach($payments as $pay): ?>
                                    <tr>
                                        <td>PAY<?php echo str_pad($pay['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($pay['pay_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($pay['payment_type'] ?? 'Appointment'); ?></td>
                                        <td>Rs. <?php echo number_format($pay['fees'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($pay['payment_method'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($pay['receipt_no'] ?: 'N/A'); ?></td>
                                        <td>
                                            <?php if($pay['pay_status'] == 'Paid'): ?>
                                                <span class="status-badge status-paid">Paid</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($pay['pay_status'] == 'Pending'): ?>
                                                <button class="btn btn-sm btn-success action-btn" onclick="makePayment(<?php echo $pay['id']; ?>)">
                                                    <i class="fas fa-money-bill-wave"></i> Pay Now
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No payments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Doctors Tab -->
            <div class="tab-pane fade" id="doctors-tab">
                <h3 class="mb-4"><i class="fas fa-user-md mr-2"></i>Our Doctors</h3>
                
                <div class="row">
                    <?php if(count($doctors) > 0): ?>
                        <?php foreach($doctors as $doc): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card doctor-card">
                                <div class="card-body text-center">
                                    <div class="doctor-avatar mb-3" style="width: 80px; height: 80px; background: #0077b6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 32px; font-weight: bold;">
                                        <?php echo strtoupper(substr($doc['username'], 0, 1)); ?>
                                    </div>
                                    <h5>Dr. <?php echo htmlspecialchars($doc['username']); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($doc['spec']); ?></p>
                                    <div class="doctor-info">
                                        <p><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($doc['email']); ?></p>
                                        <p><i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($doc['contact']); ?></p>
                                        <p class="text-primary font-weight-bold">
                                            <i class="fas fa-money-bill-wave mr-2"></i>
                                            Fee: Rs. <?php echo number_format($doc['docFees'], 2); ?>
                                        </p>
                                    </div>
                                    <button class="btn btn-primary btn-block mt-3" onclick="bookDoctor('<?php echo $doc['username']; ?>')">
                                        <i class="fas fa-calendar-plus mr-2"></i>Book Appointment
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                No doctors available at the moment.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Tab -->
            <div class="tab-pane fade" id="profile-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-user-cog mr-2"></i>My Profile</h3>
                </div>
                
                <?php if($profile_msg): echo $profile_msg; endif; ?>
                <?php if($password_msg): echo $password_msg; endif; ?>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-user-circle mr-2"></i>Profile Information</h5>
                            </div>
                            <div class="text-center mb-4">
                                <div class="user-avatar mx-auto" style="width: 100px; height: 100px; font-size: 48px;">
                                    <?php echo strtoupper(substr($patient['fname'], 0, 1)); ?>
                                </div>
                                <h4 class="mt-3"><?php echo htmlspecialchars($patient_name); ?></h4>
                                <p class="text-muted">Patient ID: <?php echo htmlspecialchars($patient['pid']); ?></p>
                            </div>
                            
                            <div class="patient-info">
                                <div class="mb-3">
                                    <label class="font-weight-bold">Email Address</label>
                                    <p><?php echo htmlspecialchars($patient['email']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold">National ID</label>
                                    <p><?php echo htmlspecialchars($patient['national_id']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold">Date of Birth</label>
                                    <p><?php echo $patient['dob'] ? date('d M Y', strtotime($patient['dob'])) : 'Not specified'; ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold">Gender</label>
                                    <p><?php echo htmlspecialchars($patient['gender']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <!-- Update Profile Form -->
                        <div class="form-card mb-4">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-user-edit mr-2"></i>Update Contact Information</h5>
                            </div>
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Contact Number *</label>
                                            <input type="tel" class="form-control" name="contact" value="<?php echo htmlspecialchars($patient['contact']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Emergency Contact</label>
                                            <input type="tel" class="form-control" name="emergencyContact" value="<?php echo htmlspecialchars($patient['emergencyContact'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                        
                        <!-- Change Password Form -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-key mr-2"></i>Change Password</h5>
                            </div>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Current Password *</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>New Password *</label>
                                            <input type="password" class="form-control" name="new_password" required minlength="6">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Confirm New Password *</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary btn-block">
                                    <i class="fas fa-save mr-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Tab -->
            <div class="tab-pane fade" id="feedback-tab">
                <h3 class="mb-4"><i class="fas fa-comment-dots mr-2"></i>Send Feedback</h3>
                
                <?php if($feedback_msg): echo $feedback_msg; endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-star mr-2"></i>Share Your Experience</h5>
                            </div>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Your Feedback *</label>
                                    <textarea class="form-control" name="feedback" rows="4" required placeholder="Share your experience with us..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Rate Your Experience *</label>
                                    <div class="rating-stars" id="rating-stars">
                                        <span class="star" data-value="1"><i class="far fa-star"></i></span>
                                        <span class="star" data-value="2"><i class="far fa-star"></i></span>
                                        <span class="star" data-value="3"><i class="far fa-star"></i></span>
                                        <span class="star" data-value="4"><i class="far fa-star"></i></span>
                                        <span class="star" data-value="5"><i class="far fa-star"></i></span>
                                    </div>
                                    <input type="hidden" name="rating" id="rating-value" value="0" required>
                                </div>
                                <button type="submit" name="send_feedback" class="btn btn-primary">
                                    <i class="fas fa-paper-plane mr-2"></i>Submit Feedback
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Why Feedback Matters</h5>
                            </div>
                            <div class="mt-3">
                                <p><i class="fas fa-check text-success mr-2"></i> Helps us improve services</p>
                                <p><i class="fas fa-check text-success mr-2"></i> Better patient experience</p>
                                <p><i class="fas fa-check text-success mr-2"></i> Quality healthcare</p>
                                <p><i class="fas fa-check text-success mr-2"></i> Personalized service</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Make Payment Modal -->
    <div class="modal fade" id="makePaymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Make Payment</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="payment-form">
                        <input type="hidden" name="payment_id" id="payment_id">
                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select class="form-control" name="method" required>
                                <option value="Cash">Cash</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Debit Card">Debit Card</option>
                                <option value="Online Banking">Online Banking</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            You will receive a receipt number after payment confirmation.
                        </div>
                        <button type="submit" name="make_payment" class="btn btn-success btn-block">
                            <i class="fas fa-money-check-alt mr-2"></i>Confirm Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-sign-out-alt mr-2"></i>Confirm Logout</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to logout?</p>
                    <p class="text-muted"><small>You will need to login again to access your dashboard.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-danger">Yes, Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Food items array to track selected items
        let selectedFoodItems = {};
        
        // Initialize on page load
        $(document).ready(function() {
            // Set up sidebar navigation
            $('.sidebar ul li[data-target]').click(function() {
                const target = $(this).data('target');
                showTab(target);
                
                // Update active state
                $('.sidebar ul li').removeClass('active');
                $(this).addClass('active');
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Set default date for appointment booking
            $('input[name="appdate"]').val(new Date().toISOString().split('T')[0]);
            
            // Set default time (next hour)
            const nextHour = new Date();
            nextHour.setHours(nextHour.getHours() + 1);
            nextHour.setMinutes(0);
            $('input[name="apptime"]').val(nextHour.toTimeString().slice(0,5));
            
            // Set default dates for room booking
            $('input[name="check_in"]').val(new Date().toISOString().split('T')[0]);
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            $('input[name="check_out"]').val(tomorrow.toISOString().split('T')[0]);
            
            // Room booking date calculation
            $('#check_in, #check_out').change(calculateRoomCost);
            
            // Food quantity change events
            $('.food-quantity-input').change(function() {
                updateFoodTotal();
            });
            
            // Food order form submission
            $('#foodOrderForm').submit(function(e) {
                // Prepare food items data
                let foodItems = {};
                $('.food-quantity-input').each(function() {
                    const foodId = $(this).attr('id').replace('quantity-', '');
                    const quantity = parseInt($(this).val());
                    if(quantity > 0) {
                        foodItems[foodId] = quantity;
                    }
                });
                
                // Add hidden inputs for food items
                for(const foodId in foodItems) {
                    $(this).append(`<input type="hidden" name="food_items[${foodId}]" value="${foodItems[foodId]}">`);
                }
                
                return true;
            });
            
            // Rating stars functionality
            $('.rating-stars .star').click(function() {
                const value = $(this).data('value');
                $('#rating-value').val(value);
                
                // Update stars
                $('.rating-stars .star').each(function() {
                    const starValue = $(this).data('value');
                    if (starValue <= value) {
                        $(this).html('<i class="fas fa-star"></i>');
                        $(this).css('color', '#ffc107');
                    } else {
                        $(this).html('<i class="far fa-star"></i>');
                        $(this).css('color', '#ffc107');
                    }
                });
            });
            
            // Session timeout warning
            let warningTimeout;
            function startSessionTimer() {
                // Show warning 2 minutes before timeout (28 minutes)
                warningTimeout = setTimeout(function() {
                    if(confirm('Your session will expire in 2 minutes. Do you want to extend your session?')) {
                        // AJAX call to extend session
                        $.ajax({
                            url: 'extend_session.php',
                            method: 'POST',
                            success: function() {
                                alert('Session extended!');
                                startSessionTimer();
                            }
                        });
                    }
                }, 1680000); // 28 minutes
            }
            
            // Start session timer
            startSessionTimer();
            
            // Clear timer on page unload
            $(window).on('beforeunload', function() {
                clearTimeout(warningTimeout);
            });
            
            // Initialize food total
            updateFoodTotal();
        });
        
        // Function to show tab
        function showTab(tabId) {
            // Hide all tab panes
            $('.tab-pane').removeClass('show active');
            
            // Show selected tab
            $('#' + tabId).addClass('show active');
            
            // Update URL hash
            window.location.hash = tabId;
            
            // Hide room booking form when switching tabs
            hideBookRoomForm();
        }
        
        // Function to calculate room cost
        function calculateRoomCost() {
            const checkIn = $('#check_in').val();
            const checkOut = $('#check_out').val();
            const dailyRate = parseFloat($('#daily_rate').val());
            
            if (checkIn && checkOut && dailyRate) {
                const start = new Date(checkIn);
                const end = new Date(checkOut);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays > 0) {
                    const totalAmount = diffDays * dailyRate;
                    $('#totalAmountDisplay').text('Total: Rs. ' + totalAmount.toFixed(2));
                    $('#calculationInfo').text(`Duration: ${diffDays} days × Rs. ${dailyRate.toFixed(2)}/day = Rs. ${totalAmount.toFixed(2)}`);
                } else {
                    $('#totalAmountDisplay').text('Total: Rs. 0.00');
                    $('#calculationInfo').text('Check-out date must be after check-in date.');
                }
            }
        }
        
        // Function to show room booking form
        function bookRoomModal(roomId, roomType, dailyRate) {
            // Scroll to top
            $('html, body').animate({
                scrollTop: $('#ward-services-tab').offset().top - 100
            }, 500);
            
            // Set form values
            $('#room_id').val(roomId);
            $('#room_type_display').val(roomType + ' Room');
            $('#daily_rate_display').val('Rs. ' + dailyRate.toFixed(2));
            $('#daily_rate').val(dailyRate);
            $('#roomTypeDisplay').text(roomType + ' Room');
            
            // Reset dates
            const today = new Date().toISOString().split('T')[0];
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().split('T')[0];
            
            $('#check_in').val(today).attr('min', today);
            $('#check_out').val(tomorrowStr).attr('min', tomorrowStr);
            
            // Show form and hide others
            $('#bookRoomForm').show();
            $('#orderFoodSection').hide();
            calculateRoomCost();
        }
        
        // Function to hide room booking form
        function hideBookRoomForm() {
            $('#bookRoomForm').hide();
            $('#orderFoodSection').show();
        }
        
        // Function for quick food order from booking
        function quickOrderFood(bookingId, roomId) {
            showTab('ward-services-tab');
            $('#form_booking_id').val(bookingId);
            $('#form_room_id').val(roomId);
            $('html, body').animate({
                scrollTop: $('#orderFoodSection').offset().top - 100
            }, 500);
        }
        
        // Function to increase food quantity
        function increaseQuantity(foodId) {
            const input = $('#quantity-' + foodId);
            const currentVal = parseInt(input.val());
            if(currentVal < 10) {
                input.val(currentVal + 1).trigger('change');
            }
        }
        
        // Function to decrease food quantity
        function decreaseQuantity(foodId) {
            const input = $('#quantity-' + foodId);
            const currentVal = parseInt(input.val());
            if(currentVal > 0) {
                input.val(currentVal - 1).trigger('change');
            }
        }
        
        // Function to update food total
        function updateFoodTotal() {
            let total = 0;
            let itemCount = 0;
            
            $('.food-quantity-input').each(function() {
                const quantity = parseInt($(this).val());
                const price = parseFloat($(this).data('price'));
                if(quantity > 0) {
                    total += quantity * price;
                    itemCount += quantity;
                }
            });
            
            $('#foodTotalDisplay').text('Total: Rs. ' + total.toFixed(2));
            
            // Enable/disable order button based on minimum order
            if(total >= 200 && itemCount > 0) {
                $('#orderFoodBtn').prop('disabled', false);
            } else {
                $('#orderFoodBtn').prop('disabled', true);
            }
        }
        
        // Function to cancel appointment
        function cancelAppointment(appointmentId) {
            if(confirm('Are you sure you want to cancel this appointment?')) {
                const form = $('<form>').attr({
                    method: 'POST',
                    style: 'display: none;'
                });
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'appointment_id',
                    value: appointmentId
                }));
                
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'cancel_appointment',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Function to cancel room booking
        function cancelRoomBooking(bookingId) {
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
                    name: 'cancel_room_booking',
                    value: '1'
                }));
                
                $('body').append(form);
                form.submit();
            }
        }
        
        // Function to make payment
        function makePayment(paymentId) {
            $('#payment_id').val(paymentId);
            $('#makePaymentModal').modal('show');
        }
        
        // Function to book appointment with specific doctor
        function bookDoctor(doctorName) {
            showTab('appointments-tab');
            $('#bookAppointmentForm').collapse('show');
            $('select[name="doctor"]').val(doctorName);
            $('html, body').animate({
                scrollTop: $('#bookAppointmentForm').offset().top - 100
            }, 500);
        }
        
        // Function to confirm logout
        function confirmLogout() {
            $('#logoutModal').modal('show');
            return false;
        }
        
        // Prevent back button after logout
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function() {
            history.pushState(null, null, document.URL);
        });
        
        // Form validation
        $(document).ready(function() {
            $('form[name="change_password"]').submit(function(e) {
                const newPassword = $('input[name="new_password"]').val();
                const confirmPassword = $('input[name="confirm_password"]'.val());
                
                if(newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match!');
                    return false;
                }
                
                if(newPassword.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long!');
                    return false;
                }
                
                return true;
            });
            
            // Room booking form validation
            $('#roomBookingForm').submit(function(e) {
                const checkIn = $('#check_in').val();
                const checkOut = $('#check_out').val();
                
                if(new Date(checkOut) <= new Date(checkIn)) {
                    e.preventDefault();
                    alert('Check-out date must be after check-in date!');
                    return false;
                }
                
                return true;
            });
            
            // Feedback form validation
            $('form[name="send_feedback"]').submit(function(e) {
                const rating = $('#rating-value').val();
                const feedback = $('textarea[name="feedback"]').val();
                
                if(rating == 0) {
                    e.preventDefault();
                    alert('Please select a rating!');
                    return false;
                }
                
                if(feedback.trim().length < 10) {
                    e.preventDefault();
                    alert('Please provide meaningful feedback (at least 10 characters)!');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($con); ?>