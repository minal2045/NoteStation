<?php
require_once 'config.php'; 

$error = '';
$success = '';
// Check which form to show by default
$active_form = 'login'; // Default to login form

if (isset($_GET['form'])) {
    if ($_GET['form'] == 'register') {
        $active_form = 'register';
    } elseif ($_GET['form'] == 'login') {
        $active_form = 'login';
    }
}

// Also check if there was a registration error (from previous POST)
if (isset($error) && !empty($error) && isset($_POST['register'])) {
    $active_form = 'register';
}

// Also check if there was a login error (from previous POST)
if (isset($error) && !empty($error) && isset($_POST['login'])) {
    $active_form = 'login';
} 

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $full_name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $conpassword = $_POST['conpassword'];
    
    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required";
        $active_form = 'register';
    } elseif ($password !== $conpassword) {
        $error = "Passwords do not match";
        $active_form = 'register';
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
        $active_form = 'register';
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $full_name)) {
        $error = "Full name can only contain letters and spaces. No numbers or symbols allowed";
        $active_form = 'register';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
        $active_form = 'register';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Username or email already exists";
            $active_form = 'register';
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $full_name, $username, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $success = "Registration successful! You can now login.";
                $active_form = 'login'; // Switch to login form on success
            } else {
                $error = "Registration failed. Please try again.";
                $active_form = 'register';
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username_email = trim($_POST['username_email']);
    $password = $_POST['password'];
    
    if (empty($username_email) || empty($password)) {
        $error = "Please enter username/email or password";
        $active_form = 'login';
    } else {
        // Check user in database
        $stmt = $conn->prepare("SELECT id, full_name, username, email, password FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username_email, $username_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['is_admin'] = false;
                
                // Redirect to homepage with success message
                header("Location: homepage.php?login=success");
                exit();
            } else {
                $error = "Invalid username/email or password";
                $active_form = 'login';
            }
        } else {
            $error = "Invalid username/email or password";
            $active_form = 'login';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Login & Sign Up System</title>
    <style>
        /* Your existing CSS remains exactly the same */
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: #c9d6ff;
            background: linear-gradient(to right, #e2e2e2, #c9d6ff);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            border-radius: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
            position: relative;
            overflow: hidden;
            width: 768px;
            max-width: 100%;
            min-height: 480px;
        }

        .container h1 {
            margin-bottom: 10px;
        }

        .container p {
            font-size: 14px;
            line-height: 20px;
            letter-spacing: 0.3px;
            margin: 20px 0;
        }

        .container a {
            color: #333;
            font-size: 13px;
            text-decoration: none;
            margin: 15px 0 10px;
        }

        .container form {
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            height: 100%;
        }

        .container input {
            background-color: #eee;
            border: none;
            margin: 8px 0;
            padding: 10px 15px;
            font-size: 13px;
            border-radius: 8px;
            width: 100%;
            outline: none;
        }

        button {
            background-color: #654D87;
            color: #fff;
            font-size: 12px;
            padding: 10px 45px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 10px;
            cursor: pointer;
            transition: transform 80ms ease-in;
        }

        button:active {
            transform: scale(0.95);
        }

        button.hidden {
            background-color: transparent;
            border-color: #fff;
        }

        a.btn {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 45px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            background-color: #512da8;
            text-transform: uppercase;
            transition: opacity 0.3s;
        }

        a.btn:hover {
            opacity: 0.9;
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }

        .sign-in {
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .sign-up {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }

        .container.active .sign-in {
            transform: translateX(100%);
            opacity: 0;
        }

        .container.active .sign-up {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: move 0.6s;
        }

        @keyframes move {
            0%, 49.99% {
                opacity: 0;
                z-index: 1;
            }
            50%, 100% {
                opacity: 1;
                z-index: 5;
            }
        }

        .toggle-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: all 0.6s ease-in-out;
            border-radius: 150px 0 0 100px;
            z-index: 1000;
        }

        .container.active .toggle-container {
            transform: translateX(-100%);
            border-radius: 0 150px 100px 0;
        }

        .toggle {
            background-color: #512da8;
            height: 100%;
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: #fff;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        .container.active .toggle {
            transform: translateX(50%);
        }

        .toggle-panel {
            position: absolute;
            width: 50%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 30px;
            text-align: center;
            top: 0;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        .toggle-left {
            transform: translateX(-200%);
        }

        .container.active .toggle-left {
            transform: translateX(0);
        }

        .toggle-right {
            right: 0;
            transform: translateX(0);
        }

        .container.active .toggle-right {
            transform: translateX(200%);
        }

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

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        @keyframes slideDown {
            from {
                top: -50px;
                opacity: 0;
            }
            to {
                top: 20px;
                opacity: 1;
            }
        }
        
        .admin-hint {
            margin-top: 15px;
            font-size: 12px;
            color: #666;
            border-top: 1px dashed #ddd;
            padding-top: 10px;
        }
        
        .admin-hint i {
            color: #512da8;
        }
    </style>
</head>
<body>
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="container" id="container">
        <div class="form-container sign-up">
            <form method="post" action="">
                <h1>Create Account</h1>
                <input type="text" name="name" placeholder="Full Name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" >
                <input type="text" name="username" placeholder="User Name" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" >
                <input type="text" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" >
                <input type="password" name="password" placeholder="Password" >
                <input type="password" name="conpassword" placeholder="Confirm Password" >
                <button type="submit" name="register">Sign Up</button>
            </form>
        </div>

        <div class="form-container sign-in">
            <form method="post" action="">
                <h1>Sign In</h1>
                <input type="text" name="username_email" placeholder="Username or Email" value="<?php echo isset($_POST['username_email']) ? htmlspecialchars($_POST['username_email']) : ''; ?>" >
                <input type="password" name="password" placeholder="Password" >
                <button type="submit" name="login">Sign In</button>
            </form>
        </div>

        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to use all site features</p>
                    <button class="hidden" id="login">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello, Friend!</h1>
                    <p>Sign-up with your personal details to use all site features</p>
                    <button class="hidden" id="register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');

        // Set active form based on PHP variable
        <?php if ($active_form == 'register'): ?>
        container.classList.add("active");
        <?php else: ?>
        container.classList.remove("active");
        <?php endif; ?>

        if (registerBtn) {
            registerBtn.addEventListener('click', () => {
                container.classList.add("active");
            });
        }

        if (loginBtn) {
            loginBtn.addEventListener('click', () => {
                container.classList.remove("active");
            });
        }

        // Auto-hide messages after 3 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            });
        }, 3000);
    </script>
</body>
</html>