<?php
require_once 'config.php';

// Check if this is a metadata request
$format = isset($_GET['format']) ? $_GET['format'] : 'file';

// Get resource ID
$resource_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$resource_id) {
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid resource ID']);
    } else {
        die('Invalid resource ID');
    }
    exit();
}

// Get resource details
$sql = "SELECT * FROM Resources WHERE Resource_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Resource not found']);
    } else {
        die('Resource not found');
    }
    exit();
}

$row = $result->fetch_assoc();

// If format is JSON, return metadata
if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'Resource_id' => $row['Resource_id'],
        'Title' => $row['Title'],
        'Subject_name' => $row['Subject_name'],
        'Course_name' => $row['Course_name'],
        'University_name' => $row['University_name'],
        'Description' => $row['Description'],
        'File_type' => $row['File_type'],
        'Upload_date' => $row['Upload_date'],
        'Resource_type' => $row['Resource_type']
    ]);
    exit();
}

// Otherwise, serve the file for viewing
$file_path = $row['file_path'];
$file_type = $row['File_type'];
$title = $row['Title'];

// Check if file exists
if (!file_exists($file_path)) {
    die('File not found at path: ' . $file_path);
}

// Get file extension
$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Set appropriate headers based on file type
switch($file_ext) {
    case 'pdf':
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $title . '.pdf"');
        break;
        
    case 'docx':
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $title . '.docx"');
        break;
        
    case 'ppt':
    case 'pptx':
        header('Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
        header('Content-Disposition: attachment; filename="' . $title . '.' . $file_ext . '"');
        break;
        
    default:
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $title . '.' . $file_ext . '"');
}

// Cache headers
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Output file
readfile($file_path);
exit();
?>