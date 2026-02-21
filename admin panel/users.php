<?php
session_start();
include("../config.php");

// Protect page
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit();
}

// DELETE USER
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($conn,"DELETE FROM registration WHERE user_id='$id'");
    header("Location: users.php");
    exit();
}

// FETCH USERS
$users = mysqli_query($conn,"SELECT * FROM registration ORDER BY user_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body{
        background: linear-gradient(135deg,#eef2ff,#f8fbff);
        ont-family: 'Segoe UI', sans-serif;
    }
    .sidebar{
        height:100vh;
        background:white;
        padding:25px;
        box-shadow:5px 0 20px rgba(0,0,0,0.05);
    }
    .sidebar a{
        display:block;
        padding:12px;
        border-radius:12px;
        margin-bottom:10px;
        text-decoration:none;
        color:#444;
    }
    .sidebar a:hover,
    .sidebar a.active{
        background:#eef2ff;
        color:#4361ee;
    }
    .table-container{
        background:white;
        padding:25px;
        border-radius:20px;
        box-shadow:0 10px 25px rgba(0,0,0,0.05);
    }
</style>
</head>
<body>

<div class="container-fluid">
    <div class="row">

    <!-- SIDEBAR -->
    <div class="col-md-2 sidebar">
        <h4 class="mb-4">NoteStation</h4>
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php" class="active">Users</a>
            <a href="resources.php">Resources</a>
            <a href="ratings.php">Ratings</a>
            <a href="logout.php">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="col-md-10 p-4">

    <h4 class="mb-4 fw-bold">Manage Users</h4>

    <div class="table-container">
        <table class="table">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>

                <?php while($row = mysqli_fetch_assoc($users)) { ?>
                <tr>
                    <td><?php echo $row['user_id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td>
                        <a href="users.php?delete=<?php echo $row['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                    </td>
                </tr>
                <?php } ?>

            </tbody>
        </table>
    </div>

    </div>
    </div>
</div>

</body>
</html>