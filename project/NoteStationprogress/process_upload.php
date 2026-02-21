<?php
require_once 'config.php';

// Set maximum file size (50MB)
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate response
function sendResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method.');
}

// Get form data
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$resource_type = isset($_POST['resource_type']) ? sanitize_input($_POST['resource_type']) : '';
$course_name = isset($_POST['course_name']) ? sanitize_input($_POST['course_name']) : '';
$subject_name = isset($_POST['subject_name']) ? sanitize_input($_POST['subject_name']) : '';
$title = isset($_POST['title']) ? sanitize_input($_POST['title']) : '';
$description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
$file_type = isset($_POST['file_type']) ? sanitize_input($_POST['file_type']) : '';
$upload_date = isset($_POST['upload_date']) ? sanitize_input($_POST['upload_date']) : date('Y-m-d');

// Handle university_name based on resource type
$university_name = null;
if ($resource_type === 'question_paper' && isset($_POST['university_name'])) {
    $university_name = sanitize_input($_POST['university_name']);
}

// Validate required fields
if (!$user_id || !$resource_type || !$course_name || !$subject_name || !$title || !$file_type) {
    sendResponse(false, 'Please fill in all required fields.');
}

// Validate resource type
if (!in_array($resource_type, ['notes', 'question_paper'])) {
    sendResponse(false, 'Invalid resource type.');
}

// Validate file type
if (!in_array($file_type, ['ppt', 'pdf', 'docx'])) {
    sendResponse(false, 'Invalid file type. Only PPT, PDF, and DOCX are allowed.');
}

// Handle file upload
if (!isset($_FILES['file_upload']) || $_FILES['file_upload']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
    ];
    
    $error_code = $_FILES['file_upload']['error'];
    $error_message = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : 'Unknown upload error.';
    sendResponse(false, 'File upload failed: ' . $error_message);
}

$file = $_FILES['file_upload'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_size = $file['size'];

// Get file extension
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Validate file extension based on selected file type
$allowed_extensions = [
    'ppt' => ['ppt', 'pptx'],
    'pdf' => ['pdf'],
    'docx' => ['docx']
];

if (!in_array($file_ext, $allowed_extensions[$file_type])) {
    sendResponse(false, 'File extension does not match selected file type.');
}

// Validate file size (50MB max)
$max_file_size = 50 * 1024 * 1024; // 50MB in bytes
if ($file_size > $max_file_size) {
    sendResponse(false, 'File size exceeds 50MB limit.');
}

// Create upload directory if it doesn't exist
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$safe_title = preg_replace('/[^a-zA-Z0-9]/', '_', $title);
$new_file_name = time() . '_' . $safe_title . '.' . $file_ext;
$upload_path = $upload_dir . $new_file_name;

// Move uploaded file
if (!move_uploaded_file($file_tmp, $upload_path)) {
    sendResponse(false, 'Failed to save uploaded file.');
}

// Insert into database
$sql = "INSERT INTO Resources (User_id, Course_name, Resource_type, University_name, Subject_name, Title, Description, File_type, Upload_date, file_path) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    // Delete uploaded file if database insert fails
    unlink($upload_path);
    sendResponse(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("isssssssss", 
    $user_id, 
    $course_name, 
    $resource_type, 
    $university_name, 
    $subject_name, 
    $title, 
    $description, 
    $file_type, 
    $upload_date,
    $upload_path
);

if ($stmt->execute()) {
    $resource_id = $stmt->insert_id;
    sendResponse(true, 'Resource uploaded successfully! Resource ID: ' . $resource_id);
} else {
    // Delete uploaded file if database insert fails
    unlink($upload_path);
    sendResponse(false, 'Failed to save resource information: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>