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
// Get current user ID (in a real application, this would come from session)
// For demo purposes, using a fixed user ID or you can modify based on your authentication system
session_start();