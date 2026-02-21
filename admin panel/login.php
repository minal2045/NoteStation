<?php
session_start();
include("../config.php");

$error = "";

if(isset($_POST['login'])){

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query($conn,
        "SELECT * FROM registration 
         WHERE email='$email' 
         AND role='admin'");

    if(mysqli_num_rows($query) > 0){

        $admin = mysqli_fetch_assoc($query);

        // If using plain password (basic project)
        if($admin['password'] == $password){

            $_SESSION['admin_id'] = $admin['user_id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['name'];

            header("Location: dashboard.php");
            exit();

        } else {
            $error = "Invalid Password";
        }

    } else {
        $error = "Admin account not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NoteStation Admin Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    height:100vh;
    background: linear-gradient(135deg,#4361ee,#3a0ca3);
    display:flex;
    justify-content:center;
    align-items:center;
    font-family: 'Segoe UI', sans-serif;
}

.login-card{
    background:white;
    padding:40px;
    border-radius:20px;
    width:100%;
    max-width:400px;
    box-shadow:0 20px 50px rgba(0,0,0,0.2);
}

.logo-icon{
    font-size:40px;
    color:#4361ee;
}

.form-control{
    border-radius:10px;
    padding:12px;
}

.btn-login{
    border-radius:10px;
    padding:12px;
    font-weight:600;
    background:#4361ee;
    border:none;
}

.btn-login:hover{
    background:#3a0ca3;
}
</style>

</head>
<body>

<div class="login-card text-center">

    <div class="mb-4">
        <i class="bi bi-shield-lock-fill logo-icon"></i>
        <h3 class="mt-3">Admin Login</h3>
        <p class="text-muted">Login to manage NoteStation</p>
    </div>

    <?php if(!empty($error)) { ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <form method="POST">

        <div class="mb-3 text-start">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="Enter admin email" required>
        </div>

        <div class="mb-3 text-start">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
        </div>

        <button type="submit" name="login" class="btn btn-login w-100 text-white">
            <i class="bi bi-box-arrow-in-right"></i> Login
        </button>

    </form>

    <div class="mt-4 text-muted" style="font-size:14px;">
        © 2026 NoteStation Admin Panel
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>