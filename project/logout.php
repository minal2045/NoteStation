<?php
require_once 'config.php';

// Check if user was admin before clearing session
$was_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect based on user type
if ($was_admin) {
    // Admin was logged in - redirect to admin login with success message
    header("Location: admin_login.php?logout=success");
} else {
    // Regular user was logged in - redirect to homepage with success message
    header("Location: homepage.php?logout=success");
}
exit();
?>