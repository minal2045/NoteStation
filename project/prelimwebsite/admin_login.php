<?php
require_once 'config.php';

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validation 1: Check if fields are empty
    if (empty($username)) {
        $error = "Username is required";
    } elseif (empty($password)) {
        $error = "Password is required";
    }
    // Validation 2: Check credentials
    elseif ($username === 'Admin' && $password === 'Admin@123') {
        // Admin login successful
        $_SESSION['user_id'] = 0; // Special ID for admin
        $_SESSION['usname'] = 'Admin';
        $_SESSION['full_name'] = 'Administrator';
        $_SESSION['is_admin'] = true;
        
        header("Location: admin_dashboard.php");
        exit();
    }
    // Validation 3: Invalid credentials
    else {
        $error = "Invalid username or password. Please try again.";
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
        .message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            z-index: 2000;
            animation: slideDown 0.5s ease;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        @keyframes slideDown {
            from { top: -50px; opacity: 0; }
            to   { top: 20px;  opacity: 1; }
        }

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
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="login-container">
        <div class="login-header">
            <h2>Admin Login</h2>
           
        </div>
        
        <form method="POST" action="" id="loginForm">
            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" class="form-control" name="username" id="username" placeholder="Username" required>
            </div>
            <div class="invalid-feedback d-none" id="usernameError">Username is required</div>
            
            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
            </div>
            <div class="invalid-feedback d-none" id="passwordError">Password is required</div>
            
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
            </button>
        </form>
        
        <div class="back-link">
            <a href="homepage.php"><i class="fas fa-arrow-left me-2"></i>Back to Homepage</a>
        </div>
    </div>
    
    <script>
        // Auto-hide messages after 3 seconds
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 3000);
        
        // Client-side validation before form submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            let isValid = true;
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const usernameError = document.getElementById('usernameError');
            const passwordError = document.getElementById('passwordError');
            
            // Reset error states
            usernameError.classList.add('d-none');
            passwordError.classList.add('d-none');
            document.getElementById('username').classList.remove('is-invalid');
            document.getElementById('password').classList.remove('is-invalid');
            
            // Validate username
            if (username === '') {
                usernameError.classList.remove('d-none');
                document.getElementById('username').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate password
            if (password === '') {
                passwordError.classList.remove('d-none');
                document.getElementById('password').classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Remove invalid styling on input
        document.getElementById('username').addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.classList.remove('is-invalid');
                document.getElementById('usernameError').classList.add('d-none');
            }
        });
        
        document.getElementById('password').addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.classList.remove('is-invalid');
                document.getElementById('passwordError').classList.add('d-none');
            }
        });
    </script>
</body>
</html>
