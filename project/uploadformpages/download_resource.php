<?php
require_once 'config.php';

// Get resource ID
$resource_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$resource_id) {
    die('Invalid resource ID');
}

// Get file information
$sql = "SELECT Title, file_path, File_type FROM Resources WHERE Resource_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Resource not found');
}

$row = $result->fetch_assoc();
$file_path = $row['file_path'];

if (!file_exists($file_path)) {
    die('File not found');
}

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($row['Title'] . '.' . $row['File_type']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Clear output buffer
ob_clean();
flush();

// Read file and output to browser
readfile($file_path);
exit();
?><?php
require_once 'config.php';

// Get resource ID
$resource_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$resource_id) {
    die('Invalid resource ID');
}

// Get file information
$sql = "SELECT Title, file_path, File_type FROM Resources WHERE Resource_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Resource not found');
}

$row = $result->fetch_assoc();
$file_path = $row['file_path'];
$title = $row['Title'];
$file_type = $row['File_type'];

if (!file_exists($file_path)) {
    die('File not found');
}

// Get file extension
$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $title . '.' . $file_ext . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Read file and output to browser
readfile($file_path);
exit();
?>