<?php
require_once 'config.php';

// Function to send JSON response
function sendResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

// Get resource ID
$resource_id = isset($_POST['resource_id']) ? (int)$_POST['resource_id'] : 0;

if (!$resource_id) {
    sendResponse(false, 'Invalid resource ID');
}

// Get current user ID
session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// First get the file path to delete the actual file
$sql = "SELECT file_path FROM Resources WHERE Resource_id = ? AND User_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $resource_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendResponse(false, 'Resource not found or you do not have permission to delete it');
}

$row = $result->fetch_assoc();
$file_path = $row['file_path'];

// Delete from database
$delete_sql = "DELETE FROM Resources WHERE Resource_id = ? AND User_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $resource_id, $user_id);

if ($delete_stmt->execute()) {
    // Delete the actual file
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    sendResponse(true, 'Resource deleted successfully');
} else {
    sendResponse(false, 'Failed to delete resource');
}
?>