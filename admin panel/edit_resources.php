<?php
session_start();
include("config.php");

// Protect page
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit();
}

// Get resource ID
if(!isset($_GET['id'])){
    header("Location: resources.php");
    exit();
}

$id = $_GET['id'];
$result = mysqli_query($conn,"SELECT * FROM resource WHERE resource_id='$id'");
$resource = mysqli_fetch_assoc($result);

if(!$resource){
    header("Location: resources.php");
    exit();
}

// UPDATE RESOURCE
if(isset($_POST['update'])){

    $course = $_POST['course_name'];
    $university = $_POST['university_name'];
    $subject = $_POST['subject_name'];
    $title = $_POST['title'];
    $fileType = $_POST['file_type'];
    $type = $_POST['resource_type'];

    mysqli_query($conn,"
        UPDATE resource SET
        course_name='$course',
        university_name='$university',
        subject_name='$subject',
        title='$title',
        file_type='$fileType',
        resource_type='$type'
        WHERE resource_id='$id'
    ");

    header("Location: resources.php");
    exit();
}

include("includes/header.php");
include("includes/sidebar.php");
?>

<h4 class="mb-4 fw-bold">Edit Resource</h4>

<div class="card p-4 shadow-sm">

<form method="POST">

<div class="row">

<div class="col-md-6 mb-3">
<label class="form-label">Course</label>
<input type="text" name="course_name" 
       value="<?= $resource['course_name'] ?>" 
       class="form-control" required>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">University</label>
<input type="text" name="university_name" 
       value="<?= $resource['university_name'] ?>" 
       class="form-control">
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Subject</label>
<input type="text" name="subject_name" 
       value="<?= $resource['subject_name'] ?>" 
       class="form-control" required>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Title</label>
<input type="text" name="title" 
       value="<?= $resource['title'] ?>" 
       class="form-control" required>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">File Type</label>
<select name="file_type" class="form-select">
<option value="PDF" <?= ($resource['file_type']=='PDF')?'selected':'' ?>>PDF</option>
<option value="DOCX" <?= ($resource['file_type']=='DOCX')?'selected':'' ?>>DOCX</option>
</select>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Resource Type</label>
<select name="resource_type" class="form-select">
<option value="notes" <?= ($resource['resource_type']=='notes')?'selected':'' ?>>Notes</option>
<option value="question_paper" <?= ($resource['resource_type']=='question_paper')?'selected':'' ?>>Question Paper</option>
</select>
</div>

</div>

<button type="submit" name="update" class="btn btn-success">
Update Resource
</button>

<a href="resources.php" class="btn btn-secondary">
Cancel
</a>

</form>
</div>

<?php include("includes/footer.php"); ?>