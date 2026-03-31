<?php
require_once 'config.php';

// If already logged in as admin, redirect to dashboard
// if (isAdmin()) {
//     header("Location: admin_dashboard.php");
//     exit();
// }

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === 'Admin' && $password === 'Admin@123') {
            // Admin login successful
            $_SESSION['user_id'] = 0; // Special ID for admin
            $_SESSION['username'] = 'admin';
            $_SESSION['full_name'] = 'Administrator';
            $_SESSION['is_admin'] = true;
            
            header("Location: admin_dashboard.php");
            exit();
        }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - NoteStation</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 90%;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header i {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .login-header h2 {
            color: #333;
            font-weight: 700;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-control {
            height: 50px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding-left: 45px;
        }
        
        .form-control:focus {
            border-color: #654D87;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #654D87;
            z-index: 10;
            font-size: 18px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            border: none;
            height: 50px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: transform 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #654D87;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Admin Login</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" class="form-control" name="username" placeholder="Username" required>
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
            </button>
        </form>
        
        <div class="back-link">
            <a href="homepage.php"><i class="fas fa-arrow-left me-2"></i>Back to Homepage</a>
        </div>
    </div>
</body>
</html>