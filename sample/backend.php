<?php
require_once 'db_connection.php';

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $check_query = "SELECT id FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($check_query);
        
        // Check if prepare() was successful
        if ($stmt === false) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = "Username or email already exists.";
            }
            $stmt->close();
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        // Hash password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (full_name, email, phone, username, password) 
                         VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        // Check if prepare() was successful
        if ($stmt === false) {
            $message = "Database error: " . $conn->error;
            $message_type = "error";
        } else {
            $stmt->bind_param("sssss", $full_name, $email, $phone, $username, $hashed_password);
            
            if ($stmt->execute()) {
                $message = "Registration successful!";
                $message_type = "success";
                
                // Clear form fields
                $full_name = $email = $phone = $username = '';
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}
?>