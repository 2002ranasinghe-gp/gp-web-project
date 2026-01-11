<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB Connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "myhmsdb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Reception login
if (isset($_POST['receptsub'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // SQL injection prevent
    $stmt = $conn->prepare("SELECT * FROM reception WHERE username=? AND password=?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $_SESSION['reception'] = $username; // Session set
        header("Location: reception_dashboard.php");
        exit();
    } else {
        echo "<script>alert('Invalid Username or Password'); window.location='index.php';</script>";
    }
}
?>
