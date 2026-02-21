<?php
session_start();
include("config.php");

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit();
}

// COUNTS
$totalUsers = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM registration"));
$totalResources = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM resource"));
$totalNotes = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM resource WHERE resource_type='notes'"));
$totalPapers = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM resource WHERE resource_type='question_paper'"));
$pendingResources = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM resource WHERE status='pending'"));

// Recent uploads
$recent = mysqli_query($conn,"SELECT * FROM resource ORDER BY resource_id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    background: linear-gradient(135deg,#eef2ff,#f8fbff);
    font-family: 'Segoe UI', sans-serif;
}
.card{
    border:none;
    border-radius:20px;
    box-shadow:0 10px 25px rgba(0,0,0,0.05);
    transition:0.3s;
}
.card:hover{
    transform:translateY(-5px);
}
.stat-icon{
    font-size:30px;
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
<div class="container mt-4">

<h3 class="mb-4 fw-bold">
Welcome, <?= $_SESSION['admin_name']; ?>
</h3>

<div class="row g-4">

<div class="col-md-3">
<div class="card p-4 text-center text-primary">
<i class="bi bi-people-fill stat-icon"></i>
<h5 class="mt-3">Users</h5>
<h3><?= $totalUsers ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card p-4 text-center text-success">
<i class="bi bi-journal-text stat-icon"></i>
<h5 class="mt-3">Total Resources</h5>
<h3><?= $totalResources ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card p-4 text-center text-info">
<i class="bi bi-file-earmark-text stat-icon"></i>
<h5 class="mt-3">Notes</h5>
<h3><?= $totalNotes ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card p-4 text-center text-warning">
<i class="bi bi-file-text-fill stat-icon"></i>
<h5 class="mt-3">Question Papers</h5>
<h3><?= $totalPapers ?></h3>
</div>
</div>

</div>

<!-- Pending Section -->
<div class="row mt-4">
<div class="col-md-4">
<div class="card p-4 text-center text-danger">
<i class="bi bi-hourglass-split stat-icon"></i>
<h5 class="mt-3">Pending Resources</h5>
<h3><?= $pendingResources ?></h3>
</div>
</div>
</div>

<!-- Recent Uploads -->
<div class="mt-5">
<h4 class="mb-3">Recent Uploads</h4>

<div class="table-container">
<table class="table align-middle">
<thead class="table-light">
<tr>
<th>ID</th>
<th>Title</th>
<th>Type</th>
<th>Subject</th>
<th>Date</th>
</tr>
</thead>
<tbody>

<?php while($row = mysqli_fetch_assoc($recent)) { ?>
<tr>
<td><?= $row['resource_id'] ?></td>
<td><?= $row['title'] ?></td>
<td>
<?php if($row['resource_type']=='notes'){ ?>
<span class="badge bg-primary">Notes</span>
<?php } else { ?>
<span class="badge bg-success">Question Paper</span>
<?php } ?>
</td>
<td><?= $row['subject_name'] ?></td>
<td><?= $row['upload_date'] ?></td>
</tr>
<?php } ?>

</tbody>
</table>
</div>
</div>

</div>
</body>
</html>