<?php
ob_start(); // Add this at the VERY TOP

require_once 'config.php';

session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$resource_id = isset($_POST['resource_id']) ? (int)$_POST['resource_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$review = isset($_POST['review']) ? trim($_POST['review']) : '';

function sendResponse($success, $message) {
    ob_clean(); // Clear any accidental output BEFORE sending JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

if (!$user_id) {
    sendResponse(false, 'Please login to rate');
}

if (!$resource_id) {
    sendResponse(false, 'Invalid resource');
}

if ($rating < 1 || $rating > 5) {
    sendResponse(false, 'Invalid rating value');
}

// Check if user is the owner of the resource
$owner_sql = "SELECT user_id FROM Resources WHERE Resource_id = ?";
$owner_stmt = $conn->prepare($owner_sql);
$owner_stmt->bind_param("i", $resource_id);
$owner_stmt->execute();
$owner_result = $owner_stmt->get_result();

if ($owner_result->num_rows > 0) {
    $owner_data = $owner_result->fetch_assoc();
    if ($owner_data['user_id'] == $user_id) {
        sendResponse(false, 'You cannot rate your own resource');
    }
} else {
    sendResponse(false, 'Resource not found');
}

// Check if rating already exists
$check_sql = "SELECT rating_id FROM Ratings WHERE user_id = ? AND resource_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $user_id, $resource_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Update existing rating
    $sql = "UPDATE Ratings SET rating = ?, review = ? WHERE user_id = ? AND resource_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $rating, $review, $user_id, $resource_id);
} else {
    // Insert new rating
    $sql = "INSERT INTO Ratings (user_id, resource_id, rating, review) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $user_id, $resource_id, $rating, $review);
}

if ($stmt->execute()) {
    sendResponse(true, 'Rating submitted successfully');
} else {
    sendResponse(false, 'Failed to submit rating');
}
?>