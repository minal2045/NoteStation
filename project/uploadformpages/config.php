<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "college project";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user ID (in a real application, this would come from session)
// For demo purposes, using a fixed user ID or you can modify based on your authentication system
session_start();
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to user 1 if not logged in
?>