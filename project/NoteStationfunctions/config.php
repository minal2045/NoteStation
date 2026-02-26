<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "college_project";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Admin credentials (hardcoded, not in database)
// define('ADMIN_USERNAME', 'admin');
// define('ADMIN_PASSWORD', 'Admin@123'); // Change this to a secure password

session_start();