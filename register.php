<?php
require_once 'backend.php';?>

<!DOCTYPE html>
<html>
<head>
    <title>Registration Form</title>
    <style>
        body { font-family: Arial; margin: 50px; }
        .container { max-width: 500px; margin: auto; border: 1px solid #ccc; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 8px; box-sizing: border-box;
        }
        input[type="submit"] { 
            background: #4CAF50; color: white; padding: 10px 20px; 
            border: none; cursor: pointer; width: 100%;
        }
        input[type="submit"]:hover { background: #45a049; }
        .message { 
            padding: 10px; margin-bottom: 20px; border-radius: 4px;
        }
        .success { background: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .error { background: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registration Form</h2>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" 
                       value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <input type="submit" value="Register">
            </div>
            
            <p>Fields marked with * are required</p>
        </form>
        <p><a href="view_users.php">View All Registered Users</a></p>
    </div>
</body>
</html>