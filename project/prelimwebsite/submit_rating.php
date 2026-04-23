<?php
session_start();
require_once 'config.php';

// Clear any output
ob_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$resource_id = isset($_POST['resource_id']) ? (int)$_POST['resource_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$review = isset($_POST['review']) ? $_POST['review'] : '';

if ($resource_id == 0 || $rating == 0) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

// Check if user owns this resource
$check = $conn->prepare("SELECT User_id FROM Resources WHERE Resource_id = ?");
$check->bind_param("i", $resource_id);
$check->execute();
$result = $check->get_result();
$resource = $result->fetch_assoc();

if ($resource && $resource['User_id'] == $user_id) {
    echo json_encode(['success' => false, 'message' => 'Cannot rate your own resource']);
    exit;
}

// Check if already rated
$check_rating = $conn->prepare("SELECT rating_id FROM ratings WHERE user_id = ? AND resource_id = ?");
$check_rating->bind_param("ii", $user_id, $resource_id);
$check_rating->execute();
$existing = $check_rating->get_result();

if ($existing->num_rows > 0) {
    // Update
    $update = $conn->prepare("UPDATE ratings SET rating = ?, review = ? WHERE user_id = ? AND resource_id = ?");
    $update->bind_param("isii", $rating, $review, $user_id, $resource_id);
    $update->execute();
    echo json_encode(['success' => true, 'message' => 'Rating updated!']);
    $update->close();
} else {
    // Insert
    $insert = $conn->prepare("INSERT INTO ratings (user_id, resource_id, rating, review) VALUES (?, ?, ?, ?)");
    $insert->bind_param("iiis", $user_id, $resource_id, $rating, $review);
    $insert->execute();
    echo json_encode(['success' => true, 'message' => 'Rating submitted successfully!']);
    $insert->close();
}

$check->close();
$check_rating->close();
$conn->close();
?>