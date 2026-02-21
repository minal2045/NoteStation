<?php
session_start();
include("config.php");

// Protect page
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit();
}

// DELETE RESOURCE
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($conn,"DELETE FROM resource WHERE resource_id='$id'");
    header("Location: resources.php");
    exit();
}

// FETCH RESOURCES WITH AVG RATING
$result = mysqli_query($conn,"
    SELECT r.*, 
    IFNULL(AVG(rt.rating_value),0) as avg_rating
    FROM resource r
    LEFT JOIN rating rt ON r.resource_id = rt.resource_id
    GROUP BY r.resource_id
    ORDER BY r.resource_id DESC
");

include("includes/header.php");
include("includes/sidebar.php");
?>

<h4 class="mb-4 fw-bold">Manage Resources</h4>

<div class="table-container">
<div class="table-responsive">
<table class="table align-middle">
<thead class="table-light">
<tr>
<th>ID</th>
<th>Type</th>
<th>Course</th>
<th>University</th>
<th>Subject</th>
<th>Title</th>
<th>File Type</th>
<th>Avg Rating</th>
<th>Date</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($result)) { ?>
<tr>

<td><?= $row['resource_id'] ?></td>

<td>
<?php if($row['resource_type'] == 'notes') { ?>
<span class="badge bg-primary">Notes</span>
<?php } else { ?>
<span class="badge bg-success">Question Paper</span>
<?php } ?>
</td>

<td><?= $row['course_name'] ?></td>

<td>
<?= ($row['university_name']) ? $row['university_name'] : "-" ?>
</td>

<td><?= $row['subject_name'] ?></td>

<td><?= $row['title'] ?></td>

<td>
<span class="badge bg-secondary">
<?= $row['file_type'] ?>
</span>
</td>

<td>
⭐ <?= round($row['avg_rating'],1) ?>
</td>

<td><?= $row['upload_date'] ?></td>

<td>
<a href="edit_resource.php?id=<?= $row['resource_id'] ?>" 
   class="btn btn-warning btn-sm">
Edit
</a>

<a href="resources.php?delete=<?= $row['resource_id'] ?>" 
   class="btn btn-danger btn-sm"
   onclick="return confirm('Are you sure you want to delete this resource?')">
Delete
</a>
</td>

</tr>
<?php } ?>

</tbody>
</table>
</div>
</div>

<?php include("includes/footer.php"); ?>