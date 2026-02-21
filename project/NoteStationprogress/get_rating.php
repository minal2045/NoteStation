<?php
require_once 'config.php';

session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$resource_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

header('Content-Type: application/json');

if (!$user_id || !$resource_id) {
    echo json_encode(['rating' => null]);
    exit();
}

$sql = "SELECT rating, review FROM Ratings WHERE user_id = ? AND resource_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['rating' => $row['rating'], 'review' => $row['review']]);
} else {
    echo json_encode(['rating' => null]);
}
?>