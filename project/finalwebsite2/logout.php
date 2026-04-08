<?php
require_once 'config.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to homepage with logout success message
header("Location: homepage.php?logout=success");
exit();
?>