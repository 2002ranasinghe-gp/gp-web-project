<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "myhmsdb";

$con = mysqli_connect($host, $user, $pass, $db);
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
