<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "notestation");

$user_id = $_SESSION['user_id'];

$name = $_POST['name'];
$email = $_POST['email'];

if ($_FILES['profile_image']['name'] != "") {
    $image = "uploads/" . time() . $_FILES['profile_image']['name'];

    move_uploaded_file($_FILES['profile_image']['tmp_name'], $image);

    $sql = "UPDATE users SET fullname='$name',email='$email',profile_image='$image' WHERE id='$user_id'";
} else {
    $sql = "UPDATE users SET fullname='$name',email='$email' WHERE id='$user_id'";
}

mysqli_query($conn, $sql);

header("Location:profile.php");
?>