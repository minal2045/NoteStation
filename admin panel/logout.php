<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy session
session_destroy();

// Prevent caching after logout
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

// Redirect to login page
header("Location: login.php");
exit();
?>