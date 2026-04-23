<?php
require_once 'config.php';

// Check if connection is successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Start session and check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : 0;
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Get user's full name if logged in
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $name_query = "SELECT full_name FROM Users WHERE id = ?";
    $name_stmt = $conn->prepare($name_query);
    if ($name_stmt) {
        $name_stmt->bind_param("i", $user_id);
        $name_stmt->execute();
        $name_result = $name_stmt->get_result();
        if ($name_row = $name_result->fetch_assoc()) {
            $user_full_name = $name_row['full_name'];
        }
        $name_stmt->close();
    }
}

// Get filter parameters
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$filter_course = isset($_GET['course']) ? $_GET['course'] : '';
$filter_subject = isset($_GET['subject']) ? $_GET['subject'] : '';
$filter_university = isset($_GET['university']) ? $_GET['university'] : '';
$filter_rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query based on filters - ONLY SHOW APPROVED RESOURCES
$sql = "SELECT r.*, r.User_id as uploader_id,
        COALESCE(AVG(rt.rating), 0) as avg_rating,
        COUNT(rt.rating_id) as rating_count
        FROM Resources r 
        LEFT JOIN ratings rt ON r.Resource_id = rt.resource_id";

$conditions = ["r.approval_status = 'approved'"];
$params = [];
$types = "";

if ($filter_type != 'all') {
    $conditions[] = "r.Resource_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if (!empty($filter_course)) {
    $conditions[] = "r.Course_name LIKE ?";
    $params[] = "%$filter_course%";
    $types .= "s";
}

if (!empty($filter_subject)) {
    $conditions[] = "r.Subject_name LIKE ?";
    $params[] = "%$filter_subject%";
    $types .= "s";
}

if (!empty($filter_university)) {
    $conditions[] = "r.University_name = ?";
    $params[] = $filter_university;
    $types .= "s";
}

if ($filter_rating > 0) {
    $conditions[] = "r.Resource_id IN (
        SELECT resource_id FROM ratings 
        GROUP BY resource_id 
        HAVING AVG(rating) >= ?
    )";
    $params[] = $filter_rating;
    $types .= "i";
}

if (!empty($search_query)) {
    $conditions[] = "(r.Title LIKE ? OR r.Description LIKE ? OR r.Subject_name LIKE ? OR r.Course_name LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY r.Resource_id ORDER BY r.Upload_date DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get all resources into an array for reuse
$all_resources = [];
while($row = $result->fetch_assoc()) {
    $all_resources[] = $row;
}

// Get unique courses for filter dropdown
$courses_sql = "SELECT DISTINCT Course_name FROM Resources WHERE approval_status = 'approved' ORDER BY Course_name";
$courses_result = $conn->query($courses_sql);

// Get subjects based on current filter type for dropdown
$subjects_sql = "SELECT DISTINCT Subject_name FROM Resources WHERE approval_status = 'approved'";
if ($filter_type != 'all') {
    $subjects_sql .= " AND Resource_type = '" . $conn->real_escape_string($filter_type) . "'";
}
$subjects_sql .= " ORDER BY Subject_name";
$subjects_result = $conn->query($subjects_sql);

// Get universities based on current filter type for dropdown
$universities_sql = "SELECT DISTINCT University_name FROM Resources WHERE approval_status = 'approved' AND University_name IS NOT NULL AND University_name != ''";
if ($filter_type == 'question_paper') {
    $universities_sql .= " AND Resource_type = 'question_paper'";
}
$universities_sql .= " ORDER BY University_name";
$universities_result = $conn->query($universities_sql);

// Determine if university column should be visible
$show_university_col = ($filter_type == 'question_paper' || !empty($filter_university));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NoteStation - Home</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            --primary-dark: #4a3f7a;
            --secondary-dark: #5a3d7c;
            --primary-color: #654D87;
            --secondary-color: #764ba2;
            --accent-color: #9459CF;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --hover-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
        }
        .message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            z-index: 9999;
            animation: slideDown 0.5s ease;
            white-space: nowrap;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        @keyframes slideDown {
            from { top: -50px; opacity: 0; }
            to   { top: 20px;  opacity: 1; }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            overflow-x: hidden;
            padding-top: 0;
        }

        /* Parallax Background */
        .parallax-container {
            position: relative;
            height: 100vh;
            overflow: hidden;
        }

        .parallax-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 120%;
            background-image: url('back.jpeg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transform: translateY(0);
            will-change: transform;
            z-index: -2;
        }

        .parallax-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            z-index: -1;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            padding: 12px 0;
            transition: all 0.3s ease;
            width: 100%;
        }

        .navbar.scrolled {
            padding: 8px 0;
            box-shadow: 0 4px 25px rgba(0,0,0,0.3);
        }

        .navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.5px;
            font-family: 'Playfair Display', serif;
            text-decoration: none;
        }

        .navbar-brand i {
            font-size: 28px;
            color: white;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 5px;
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 8px 18px !important;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 15px;
            letter-spacing: 0.3px;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            display: inline-block;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Dropdown Styles */
        .dropdown-menu {
            background: white;
            border: none;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 8px 0;
            margin-top: 10px;
        }

        .dropdown-item {
            color: #333;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .dropdown-item i {
            margin-right: 10px;
            color: #654D87;
            width: 20px;
        }

        .dropdown-item:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #654D87;
        }

        .navbar-toggler {
            display: none;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.5);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 18px;
            cursor: pointer;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 1100px;
            padding: 0 20px;
            animation: fadeInUp 1s ease;
        }

        .hero-small-title {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 25px;
            letter-spacing: 4px;
            opacity: 0.95;
            text-transform: uppercase;
            font-family: 'Inter', sans-serif;
            color: rgba(255, 255, 255, 0.9);
        }

        .hero-large-title {
            font-family: 'Playfair Display', serif;
            font-size: 96px;
            font-weight: 800;
            margin-bottom: 30px;
            letter-spacing: -3px;
            line-height: 1.1;
            color: #FFFFFF;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .hero-large-title .station-text {
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            color: #9459CF;
            display: inline-block;
        }

        .hero-description {
            font-size: 22px;
            max-width: 800px;
            margin: 0 auto 50px;
            line-height: 1.6;
            opacity: 0.95;
            font-weight: 300;
            letter-spacing: 0.5px;
            font-family: 'Inter', sans-serif;
            color: rgba(255, 255, 255, 0.9);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Main Content */
        .main-content {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 80px 0;
            position: relative;
            z-index: 3;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 50px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .filter-title {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            margin-bottom: 25px;
            font-family: 'Inter', sans-serif;
        }

        .filter-btns {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .filter-btn {
            padding: 12px 30px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            color: #666;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
        }

        .filter-btn:hover {
            border-color: #654D87;
            color: #654D87;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            border-color: transparent;
            color: white;
        }

        .filter-select {
            padding: 12px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            color: #666;
            width: 100%;
            transition: all 0.3s;
            background-color: white;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
        }

        .filter-select:focus {
            border-color: #654D87;
            outline: none;
        }

        /* Star Rating Filter Styles */
        .star-filter-row {
            display: flex;
            align-items: center;
            gap: 4px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 10px 14px;
            transition: border-color 0.3s;
            cursor: default;
            min-height: 50px;
        }
        
        .star-filter-row:hover {
            border-color: #654D87;
        }
        
        .star-filter-row.has-selection {
            border-color: #654D87;
        }
        
        .filter-star {
            font-size: 22px;
            color: #e0e0e0;
            cursor: pointer;
            transition: color 0.15s, transform 0.15s;
            line-height: 1;
            user-select: none;
        }
        
        .filter-star.active {
            color: #ffc107;
        }
        
        .filter-star.preview {
            color: #ffc107;
            transform: scale(1.15);
        }
        
        .star-filter-text {
            font-size: 13px;
            color: #888;
            font-family: 'Inter', sans-serif;
            margin-left: 6px;
            white-space: nowrap;
        }
        
        .star-clear-btn {
            margin-left: auto;
            background: none;
            border: none;
            color: #ccc;
            font-size: 15px;
            cursor: pointer;
            padding: 0 2px;
            line-height: 1;
            transition: color 0.2s;
            display: none;
        }
        
        .star-clear-btn:hover {
            color: #794046;
        }
        
        .star-clear-btn.visible {
            display: inline-block;
        }
        
        #ratingInput {
            display: none;
        }

        /* Control Bar */
        .control-bar {
            background: white;
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
        }

        .view-toggle {
            display: flex;
            gap: 5px;
            background: #f0f0f0;
            padding: 5px;
            border-radius: 10px;
        }

        .view-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            background: transparent;
            color: #666;
        }

        .view-btn.active {
            background: white;
            color: #654D87;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Resources Grid */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .resource-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .card-header {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            padding: 30px 20px;
            color: white;
            position: relative;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-icon {
            font-size: 52px;
            margin-bottom: 10px;
        }

        .file-type-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            font-family: 'Inter', sans-serif;
        }

        .resource-type-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
        }

        .resource-type-badge.notes {
            background: #28a745;
            color: white;
        }

        .resource-type-badge.question_paper {
            background: #dc3545;
            color: white;
        }

        .rating-badge {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: rgba(0,0,0,0.5);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            color: white;
            backdrop-filter: blur(5px);
            font-family: 'Inter', sans-serif;
        }

        .rating-badge i {
            color: #ffc107;
            margin-right: 3px;
        }

        .card-body {
            padding: 25px;
            flex: 1;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
        }

        .card-details {
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #666;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
        }

        .detail-item i {
            width: 20px;
            color: #654D87;
            font-size: 16px;
        }

        .description {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
        }

        .card-footer {
            padding: 18px 25px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .upload-date {
            font-size: 0.9rem;
            color: #888;
            font-family: 'Inter', sans-serif;
        }

        .upload-date i {
            margin-right: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            border: none;
            background: white;
            color: #654D87;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: white;
            transform: scale(1.1);
        }

        /* List View */
        .resources-list {
            display: none;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 40px;
        }
        
        .list-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.3s;
            cursor: pointer;
        }
        
        .list-item:hover {
            background: #f8f9fa;
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .list-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-right: 15px;
        }
        
        .list-content {
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
        }
        
        .list-title {
            min-width: 200px;
        }
        
        .list-title h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 3px;
            color: #333;
        }
        
        .list-title small {
            color: #888;
            font-size: 0.8rem;
        }
        
        .list-details {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            flex: 1;
        }
        
        .list-detail {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .list-detail i {
            color: #654D87;
            width: 16px;
        }
        
        .list-badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .list-badge.notes {
            background: #d4edda;
            color: #155724;
        }
        
        .list-badge.question_paper {
            background: #f8d7da;
            color: #721c24;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: white;
            border-bottom: none;
            padding: 20px 30px;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            padding: 30px;
        }

        .preview-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
        }

        .preview-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            font-family: 'Playfair Display', serif;
        }

        .preview-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            background: #f8f9fa;
            padding: 10px 18px;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
        }

        .meta-item i {
            color: #654D87;
        }

        .preview-description {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: #666;
            text-align: left;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
        }

        .preview-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .preview-btn {
            padding: 14px 35px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
        }

        .preview-btn.view {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: white;
        }

        .preview-btn.download {
            background: #28a745;
            color: white;
        }

        .preview-btn.rate {
            background: #ffc107;
            color: #333;
        }

        .preview-btn.rate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .preview-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Rating Stars in Modal */
        .rating-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .rating-star-lg {
            font-size: 40px;
            color: #e0e0e0;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .rating-star-lg:hover {
            transform: scale(1.2);
        }
        
        .rating-star-lg.active {
            color: #ffc107;
        }
        
        .rating-info {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
        }

        /* Rating Button Styles */
        .btn-theme {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
        }

        .btn-theme:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }

        .btn-theme:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }

        .empty-state i {
            font-size: 90px;
            color: #654D87;
            opacity: 0.5;
            margin-bottom: 25px;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
            font-family: 'Playfair Display', serif;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 25px;
            font-size: 18px;
            font-family: 'Inter', sans-serif;
        }

        /* Login Prompt Modal */
        .login-prompt {
            text-align: center;
            padding: 40px;
        }

        .login-prompt i {
            font-size: 70px;
            color: #654D87;
            margin-bottom: 20px;
        }

        .login-prompt h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 28px;
            font-family: 'Playfair Display', serif;
        }

        .login-prompt p {
            color: #666;
            margin-bottom: 30px;
            font-size: 18px;
            font-family: 'Inter', sans-serif;
        }

        .login-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn-login, .btn-signup {
            padding: 14px 40px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn-login {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: white;
        }

        .btn-signup {
            background: #28a745;
            color: white;
        }

        .btn-login:hover, .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: #fff;
            padding: 60px 0 20px;
            margin-top: 80px;
            position: relative;
            z-index: 10;
            font-family: 'Inter', sans-serif;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h4 {
            color: #fff;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 25px;
            position: relative;
            font-family: 'Playfair Display', serif;
        }

        .footer-section h4::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 50px;
            height: 2px;
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            border-radius: 2px;
        }

        .footer-section p {
            color: rgba(255,255,255,0.7);
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .footer-links a i {
            font-size: 12px;
            color: #667eea;
            transition: transform 0.3s;
        }

        .footer-links a:hover {
            color: #fff;
            transform: translateX(5px);
        }

        .footer-contact {
            list-style: none;
            padding: 0;
            margin: 0 0 20px;
        }

        .footer-contact li {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .footer-contact li i {
            width: 20px;
            color: #667eea;
        }

        .footer-bottom {
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .footer-bottom p {
            color: rgba(255,255,255,0.6);
            margin: 0;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
                flex-direction: column;
                padding: 15px;
                gap: 8px;
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            }
            
            .nav-links.show {
                display: flex;
            }
            
            .navbar-toggler {
                display: block;
            }
            
            .nav-link {
                width: 100%;
                text-align: center;
                padding: 12px !important;
            }
        }

        @media (max-width: 768px) {
            .hero-large-title {
                font-size: 64px;
            }
            
            .hero-description {
                font-size: 18px;
                padding: 0 20px;
            }
            
            .filter-btns {
                flex-direction: column;
            }
            
            .filter-btn {
                width: 100%;
            }
            
            .resources-grid {
                grid-template-columns: 1fr;
            }
            
            .preview-actions {
                flex-direction: column;
            }
            
            .preview-btn {
                width: 100%;
                justify-content: center;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Parallax Container -->
    <div class="parallax-container">
        <div class="parallax-bg" id="parallaxBg"></div>
        <div class="parallax-overlay"></div>
        
        <!-- Navbar -->
        <nav class="navbar" id="mainNavbar">
            <div class="container">
                <a class="navbar-brand" href="homepage.php">
                    <i class="fas fa-book-open"></i>
                    NoteStation
                </a>
                
                <button class="navbar-toggler" id="navbarToggler">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="nav-links" id="navLinks">
                    <a class="nav-link" href="homepage.php">Home</a>
                    
                    <?php if ($is_logged_in): ?>
                        <?php if (!$is_admin): ?>
                        <a class="nav-link" href="upload_form.php">Upload</a>
                        <a class="nav-link" href="my_uploads.php">My Uploads</a>
                        <?php endif; ?>
                        
                        <?php if ($is_admin): ?>
                            <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                        <?php endif; ?>
                        
                        <div class="dropdown d-inline-block">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> 
                                <?php echo htmlspecialchars($_SESSION['full_name'] ?? $user_full_name); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link" href="login_signup.php?form=login">Login</a>
                        <a class="nav-link" href="login_signup.php?form=register">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <div class="hero-small-title">Your Academic Resource Hub</div>
                <h1 class="hero-large-title">
                    Note <span class="station-text">Station</span>
                </h1>
                <p class="hero-description">
                    Find lecture notes & past papers in one place. Upload, download, and ace your exams — anytime, anywhere.
                </p>
            </div>
        </section>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Filter Section -->
            <div class="filter-section">
                <h5 class="filter-title"><i class="fas fa-filter me-2"></i>Filter Resources</h5>
                
                <form method="GET" action="homepage.php" id="filterForm" onsubmit="return false;">
                    <input type="hidden" name="type" id="typeInput" value="<?php echo htmlspecialchars($filter_type); ?>">
                    <input type="hidden" name="rating" id="ratingInput" value="<?php echo $filter_rating; ?>">
                    
                    <div class="filter-btns">
                        <button type="button" name="type" value="all" 
                                class="filter-btn <?php echo $filter_type == 'all' ? 'active' : ''; ?>"
                                onclick="setTypeAndSubmit('all')">
                            All Resources
                        </button>
                        <button type="button" name="type" value="notes" 
                                class="filter-btn <?php echo $filter_type == 'notes' ? 'active' : ''; ?>"
                                onclick="setTypeAndSubmit('notes')">
                            <i class="fas fa-book me-2"></i>Notes
                        </button>
                        <button type="button" name="type" value="question_paper" 
                                class="filter-btn <?php echo $filter_type == 'question_paper' ? 'active' : ''; ?>"
                                onclick="setTypeAndSubmit('question_paper')">
                            <i class="fas fa-file-alt me-2"></i>Question Papers
                        </button>
                        
                        <button type="button" class="filter-btn clear-filters-btn" id="clearFiltersBtn">
                            Clear All Filters
                        </button>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select name="course" class="filter-select" id="courseSelect">
                                <option value="">All Courses</option>
                                <?php 
                                if ($courses_result && $courses_result->num_rows > 0) {
                                    while($course = $courses_result->fetch_assoc()) {
                                        $selected = ($filter_course == $course['Course_name']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($course['Course_name']) . "' $selected>" . 
                                             htmlspecialchars($course['Course_name']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <select name="subject" class="filter-select" id="subjectSelect">
                                <option value="">All Subjects</option>
                                <?php 
                                if ($subjects_result && $subjects_result->num_rows > 0) {
                                    while($subject = $subjects_result->fetch_assoc()) {
                                        $selected = ($filter_subject == $subject['Subject_name']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($subject['Subject_name']) . "' $selected>" . 
                                             htmlspecialchars($subject['Subject_name']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3" id="universityCol" style="<?php echo $show_university_col ? '' : 'display:none;'; ?>">
                            <select name="university" class="filter-select" id="universitySelect">
                                <option value="">All Universities</option>
                                <?php 
                                if ($universities_result && $universities_result->num_rows > 0) {
                                    while($university = $universities_result->fetch_assoc()) {
                                        if (!empty($university['University_name'])) {
                                            $selected = ($filter_university == $university['University_name']) ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($university['University_name']) . "' $selected>" . 
                                                 htmlspecialchars($university['University_name']) . "</option>";
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="star-filter-row" id="starFilterRow">
                                <i class="fas fa-star filter-star" data-value="1"></i>
                                <i class="fas fa-star filter-star" data-value="2"></i>
                                <i class="fas fa-star filter-star" data-value="3"></i>
                                <i class="fas fa-star filter-star" data-value="4"></i>
                                <i class="fas fa-star filter-star" data-value="5"></i>
                                <span class="star-filter-text" id="starFilterText">Any</span>
                                <button type="button" class="star-clear-btn" id="starClearBtn" title="Clear rating filter">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Control Bar for View Toggle -->
            <?php if (!empty($all_resources)): ?>
            <div class="control-bar">
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
                    <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
                </div>
            </div><br/>
            <?php endif; ?>

            <!-- Resources Grid View -->
            <?php if (!empty($all_resources)): ?>
                <div class="resources-grid" id="gridView">
                    <?php foreach($all_resources as $row): 
                        $fileIcon = 'fa-file';
                        if ($row['File_type'] == 'pdf') $fileIcon = 'fa-file-pdf';
                        elseif ($row['File_type'] == 'docx') $fileIcon = 'fa-file-word';
                        elseif ($row['File_type'] == 'ppt') $fileIcon = 'fa-file-powerpoint';
                        
                        $uploadDate = date('d M Y', strtotime($row['Upload_date']));
                        $avgRating = round($row['avg_rating'], 1);
                        $ratingCount = $row['rating_count'];
                        
                        $resourceData = htmlspecialchars(json_encode([
                            'id' => $row['Resource_id'],
                            'title' => $row['Title'],
                            'subject' => $row['Subject_name'],
                            'course' => $row['Course_name'],
                            'university' => $row['University_name'],
                            'date' => $uploadDate,
                            'file_type' => $row['File_type'],
                            'description' => $row['Description'],
                            'resource_type' => $row['Resource_type'],
                            'avg_rating' => $avgRating,
                            'rating_count' => $ratingCount,
                            'uploader_id' => $row['uploader_id']
                        ]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="resource-card" 
                         data-id="<?php echo $row['Resource_id']; ?>" 
                         data-type="<?php echo $row['Resource_type']; ?>"
                         data-course="<?php echo strtolower(htmlspecialchars($row['Course_name'])); ?>"
                         data-subject="<?php echo strtolower(htmlspecialchars($row['Subject_name'])); ?>"
                         data-university="<?php echo strtolower(htmlspecialchars($row['University_name'])); ?>"
                         data-rating="<?php echo $avgRating; ?>"
                         data-resource='<?php echo $resourceData; ?>'>
                        <div class="card-header">
                            <div class="resource-type-badge <?php echo $row['Resource_type']; ?>">
                                <?php echo $row['Resource_type'] == 'notes' ? 'Notes' : 'Q.Paper'; ?>
                            </div>
                            <div class="file-type-badge">
                                <?php echo strtoupper($row['File_type']); ?>
                            </div>
                            <?php if ($ratingCount > 0): ?>
                            <div class="rating-badge">
                                <i class="fas fa-star"></i> <?php echo $avgRating; ?> (<?php echo $ratingCount; ?>)
                            </div>
                            <?php endif; ?>
                            <div>
                                <i class="fas <?php echo $fileIcon; ?> file-icon"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?php echo htmlspecialchars($row['Title']); ?></h3>
                            <div class="card-details">
                                <div class="detail-item">
                                    <i class="fas fa-book"></i>
                                    <span><?php echo htmlspecialchars($row['Course_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-tag"></i>
                                    <span><?php echo htmlspecialchars($row['Subject_name']); ?></span>
                                </div>
                                <?php if($row['University_name']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-university"></i>
                                    <span><?php echo htmlspecialchars($row['University_name']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if($row['Description']): ?>
                            <div class="description">
                                <i class="fas fa-quote-left me-1" style="color: #667eea; opacity: 0.5;"></i>
                                <?php echo htmlspecialchars($row['Description']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <div class="upload-date">
                                <i class="far fa-calendar-alt"></i> <?php echo $uploadDate; ?>
                            </div>
                            <div class="action-buttons">
                                <button class="action-btn view-resource" title="View Resource" data-id="<?php echo $row['Resource_id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="action-btn download-resource" title="Download" data-id="<?php echo $row['Resource_id']; ?>">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Resources List View -->
                <div class="resources-list" id="listView" style="display: none;">
                    <?php foreach($all_resources as $row): 
                        $fileIcon = 'fa-file';
                        if ($row['File_type'] == 'pdf') $fileIcon = 'fa-file-pdf';
                        elseif ($row['File_type'] == 'docx') $fileIcon = 'fa-file-word';
                        elseif ($row['File_type'] == 'ppt') $fileIcon = 'fa-file-powerpoint';
                        
                        $uploadDate = date('d M Y', strtotime($row['Upload_date']));
                        $avgRating = round($row['avg_rating'], 1);
                        $ratingCount = $row['rating_count'];
                        
                        $resourceData = htmlspecialchars(json_encode([
                            'id' => $row['Resource_id'],
                            'title' => $row['Title'],
                            'subject' => $row['Subject_name'],
                            'course' => $row['Course_name'],
                            'university' => $row['University_name'],
                            'date' => $uploadDate,
                            'file_type' => $row['File_type'],
                            'description' => $row['Description'],
                            'resource_type' => $row['Resource_type'],
                            'avg_rating' => $avgRating,
                            'rating_count' => $ratingCount,
                            'uploader_id' => $row['uploader_id']
                        ]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="list-item" 
                         data-id="<?php echo $row['Resource_id']; ?>" 
                         data-type="<?php echo $row['Resource_type']; ?>"
                         data-course="<?php echo strtolower(htmlspecialchars($row['Course_name'])); ?>"
                         data-subject="<?php echo strtolower(htmlspecialchars($row['Subject_name'])); ?>"
                         data-university="<?php echo strtolower(htmlspecialchars($row['University_name'])); ?>"
                         data-rating="<?php echo $avgRating; ?>"
                         data-resource='<?php echo $resourceData; ?>'>
                        <div class="list-icon">
                            <i class="fas <?php echo $fileIcon; ?>"></i>
                        </div>
                        <div class="list-content">
                            <div class="list-title">
                                <h4><?php echo htmlspecialchars($row['Title']); ?></h4>
                                <small><?php echo $uploadDate; ?></small>
                            </div>
                            <div class="list-details">
                                <span class="list-detail">
                                    <i class="fas fa-book"></i> <?php echo htmlspecialchars($row['Course_name']); ?>
                                </span>
                                <span class="list-detail">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['Subject_name']); ?>
                                </span>
                                <?php if($row['University_name']): ?>
                                <span class="list-detail">
                                    <i class="fas fa-university"></i> <?php echo htmlspecialchars($row['University_name']); ?>
                                </span>
                                <?php endif; ?>
                                <span class="list-badge <?php echo $row['Resource_type']; ?>">
                                    <?php echo $row['Resource_type'] == 'notes' ? 'Notes' : 'Question Paper'; ?>
                                </span>
                                <?php if ($ratingCount > 0): ?>
                                <span class="list-detail">
                                    <span class="rating-stars">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="color: <?php echo $i <= $avgRating ? '#ffc107' : '#e0e0e0'; ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                    <span class="rating-count">(<?php echo $ratingCount; ?>)</span>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="action-btn view-resource" title="View" data-id="<?php echo $row['Resource_id']; ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn download-resource" title="Download" data-id="<?php echo $row['Resource_id']; ?>">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>No Resources Found</h3>
                    <p>No study materials match your filters. Try adjusting your search criteria.</p>
                    <?php if (!$is_logged_in): ?>
                        <p class="text-muted">Login to upload and share your study materials!</p>
                    <?php else: ?>
                        <a href="upload_form.php" class="btn" style="background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%); color: white; padding: 12px 30px; border-radius: 10px; text-decoration: none;">
                            ➕ Upload Your First Resource
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resource View Modal -->
    <div class="modal fade" id="resourceModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-alt me-2"></i>
                        <span id="modalTitle">Resource Details</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="resource-preview">
                        <div class="preview-icon" id="modalIcon">
                            <i class="fas fa-file"></i>
                        </div>
                        <h3 class="preview-title" id="modalResourceTitle"></h3>
                        
                        <div class="preview-meta">
                            <span class="meta-item">
                                <i class="fas fa-tag"></i>
                                <span id="modalSubject"></span>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-book"></i>
                                <span id="modalCourse"></span>
                            </span>
                            <span class="meta-item" id="modalUniversityContainer">
                                <i class="fas fa-university"></i>
                                <span id="modalUniversity"></span>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span id="modalDate"></span>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-file"></i>
                                <span id="modalFileType"></span>
                            </span>
                        </div>
                        
                        <div class="preview-description" id="modalDescriptionContainer">
                            <strong>Description:</strong>
                            <p id="modalDescription" class="mt-2 mb-0"></p>
                        </div>
                        
                        <div class="preview-actions">
                            <button class="preview-btn view" id="viewFileBtn">
                                <i class="fas fa-eye"></i> View Resource
                            </button>
                            <button class="preview-btn download" id="downloadFileBtn">
                                <i class="fas fa-download"></i> Download
                            </button>
                            <?php if ($is_logged_in && !$is_admin): ?>
                                <button class="preview-btn rate" id="rateResourceBtn" data-bs-toggle="modal" data-bs-target="#ratingModal">
                                    <i class="fas fa-star"></i> Rate Resource
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating Modal -->
    <?php if ($is_logged_in && !$is_admin): ?>
    <div class="modal fade" id="ratingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-star me-2"></i>Rate this Resource
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <h4 id="ratingResourceTitle" class="mb-4"></h4>
                        
                        <div class="rating-container">
                            <i class="fas fa-star rating-star-lg" data-rating="1"></i>
                            <i class="fas fa-star rating-star-lg" data-rating="2"></i>
                            <i class="fas fa-star rating-star-lg" data-rating="3"></i>
                            <i class="fas fa-star rating-star-lg" data-rating="4"></i>
                            <i class="fas fa-star rating-star-lg" data-rating="5"></i>
                        </div>
                        
                        <div class="rating-info" id="ratingInfo">
                            Select a rating
                        </div>
                        
                        <textarea class="form-control mb-3" id="ratingReview" 
                                  rows="3" placeholder="Write a review (optional)"></textarea>
                        
                        <button class="btn btn-theme w-100" id="submitRating">
                            <i class="fas fa-check me-2"></i>Submit Rating
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Login Prompt Modal -->
    <div class="modal fade" id="loginPromptModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body login-prompt">
                    <h3>Login Required</h3>
                    <p>You need to be logged in to view and download resources. Please login or create an account to continue.</p>
                    <div class="login-buttons">
                        <a href="login_signup.php?form=login" class="btn btn-login">Login</a>
                        <a href="login_signup.php?form=register" class="btn btn-signup">Sign Up</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    // Save scroll position to sessionStorage
    function saveScroll() {
        sessionStorage.setItem('filterScroll', window.scrollY || document.documentElement.scrollTop);
    }

    // Apply filters without page refresh
    function applyFilters() {
        var filterType = $('#typeInput').val();
        var filterCourse = $('#courseSelect').val().toLowerCase();
        var filterSubject = $('#subjectSelect').val().toLowerCase();
        var filterUniversity = $('#universitySelect').val().toLowerCase();
        var filterRating = parseInt($('#ratingInput').val()) || 0;
        
        var visibleCount = 0;
        
        $('.resource-card, .list-item').each(function() {
            var $card = $(this);
            var show = true;
            
            if (filterType !== 'all' && filterType !== '') {
                if ($card.data('type') !== filterType) show = false;
            }
            
            if (show && filterCourse !== '') {
                var cardCourse = $card.data('course');
                if (!cardCourse || !cardCourse.includes(filterCourse)) show = false;
            }
            
            if (show && filterSubject !== '') {
                var cardSubject = $card.data('subject');
                if (!cardSubject || !cardSubject.includes(filterSubject)) show = false;
            }
            
            if (show && filterUniversity !== '') {
                var cardUniversity = $card.data('university') || '';
                if (cardUniversity !== filterUniversity) show = false;
            }
            
            if (show && filterRating > 0) {
                var cardRating = $card.data('rating') || 0;
                if (cardRating < filterRating) show = false;
            }
            
            if (show) {
                $card.show();
                visibleCount++;
            } else {
                $card.hide();
            }
        });
        
        if (visibleCount === 0 && $('.empty-state').length === 0) {
            var emptyHtml = '<div class="empty-state">' +
                '<i class="fas fa-cloud-upload-alt"></i>' +
                '<h3>No Resources Found</h3>' +
                '<p>No study materials match your filters. Try adjusting your search criteria.</p>' +
                '</div>';
            $('#gridView').after(emptyHtml);
        } else if (visibleCount > 0) {
            $('.empty-state').remove();
        }
    }

    function updateActiveFilters() {
        var currentType = $('#typeInput').val();
        $('.filter-btn').each(function() {
            if ($(this).val() === currentType) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
        
        if (currentType === 'question_paper') {
            $('#universityCol').show();
        } else {
            $('#universityCol').hide();
            $('#universitySelect').val('');
        }
    }

    window.setTypeAndSubmit = function(type) {
        $('#typeInput').val(type);
        updateActiveFilters();
        if (type !== 'question_paper') {
            $('#universitySelect').val('');
        }
        applyFilters();
        saveScroll();
    };

    $('#clearFiltersBtn').click(function(e) {
        e.preventDefault();
        $('#typeInput').val('all');
        $('#ratingInput').val(0);
        $('#courseSelect').val('');
        $('#subjectSelect').val('');
        $('#universitySelect').val('');
        $('#starFilterText').text('Any');
        $('.filter-star').removeClass('active');
        $('#starFilterRow').removeClass('has-selection');
        $('#starClearBtn').removeClass('visible');
        updateActiveFilters();
        $('#universityCol').hide();
        applyFilters();
        saveScroll();
    });

    $('#courseSelect, #subjectSelect, #universitySelect').on('change', function() {
        applyFilters();
        saveScroll();
    });

    const RATING_LABELS = ['', '1+ Star', '2+ Stars', '3+ Stars', '4+ Stars', '5 Stars'];
    let curFilter = <?php echo $filter_rating; ?>;

    function renderFilterStars(active) {
        $('.filter-star').each(function () {
            $(this).toggleClass('active', parseInt($(this).data('value')) <= active);
        });
        if (active > 0) {
            $('#starFilterText').text(RATING_LABELS[active]);
            $('#starFilterRow').addClass('has-selection');
            $('#starClearBtn').addClass('visible');
        } else {
            $('#starFilterText').text('Any');
            $('#starFilterRow').removeClass('has-selection');
            $('#starClearBtn').removeClass('visible');
        }
        $('#ratingInput').val(active);
    }

    renderFilterStars(curFilter);

    $('.filter-star').on('mouseenter', function () {
        const v = parseInt($(this).data('value'));
        $('.filter-star').each(function () {
            $(this).toggleClass('preview', parseInt($(this).data('value')) <= v);
        });
        $('#starFilterText').text(RATING_LABELS[v]);
    }).on('mouseleave', function () {
        $('.filter-star').removeClass('preview');
        $('#starFilterText').text(curFilter > 0 ? RATING_LABELS[curFilter] : 'Any');
    });

    $('.filter-star').on('click', function () {
        curFilter = parseInt($(this).data('value'));
        renderFilterStars(curFilter);
        applyFilters();
        saveScroll();
    });

    $('#starClearBtn').on('click', function (e) {
        e.stopPropagation();
        curFilter = 0;
        renderFilterStars(0);
        applyFilters();
        saveScroll();
    });

    window.addEventListener('load', function () {
        const saved = sessionStorage.getItem('filterScroll');
        if (saved) {
            setTimeout(function () {
                window.scrollTo({ top: parseInt(saved), behavior: 'instant' });
                sessionStorage.removeItem('filterScroll');
            }, 50);
        }
    });

    $(document).ready(function() {
        let currentResourceId = null;
        let selectedRating = 0;

        function showToast(message, type) {
            const el = $('<div class="message ' + type + '">' + message + '</div>');
            $('body').append(el);
            setTimeout(function() {
                el.css('transition', 'opacity 0.5s');
                el.css('opacity', '0');
                setTimeout(function() { el.remove(); }, 500);
            }, 3000);
        }
        
        $(window).scroll(function() {
            const scroll = $(this).scrollTop();
            $('#parallaxBg').css('transform', 'translateY(' + scroll * 0.5 + 'px)');
            
            if (scroll > 50) {
                $('#mainNavbar').addClass('scrolled');
            } else {
                $('#mainNavbar').removeClass('scrolled');
            }
        });
        
        $('#navbarToggler').click(function() {
            $('#navLinks').toggleClass('show');
        });
        
        $('.view-btn').click(function() {
            $('.view-btn').removeClass('active');
            $(this).addClass('active');
            
            if ($(this).data('view') === 'grid') {
                $('#gridView').show();
                $('#listView').hide();
            } else {
                $('#gridView').hide();
                $('#listView').show();
            }
        });

        <?php if (!$is_logged_in): ?>
        $('.view-resource, .download-resource').click(function(e) {
            e.stopPropagation();
            new bootstrap.Modal(document.getElementById('loginPromptModal')).show();
        });
        
        $('.resource-card, .list-item').click(function() {
            new bootstrap.Modal(document.getElementById('loginPromptModal')).show();
        });
        <?php else: ?>
        $('.view-resource').click(function(e) {
            e.stopPropagation();
            const resourceId = $(this).data('id');
            loadResourceDetails(resourceId);
        });
        
        $('.resource-card, .list-item').click(function(e) {
            if (!$(e.target).closest('.action-btn').length) {
                const resourceId = $(this).data('id');
                loadResourceDetails(resourceId);
            }
        });
        
        $('.download-resource').click(function(e) {
            e.stopPropagation();
            const resourceId = $(this).data('id');
            window.location.href = `download_resource.php?id=${resourceId}`;
        });
        
        function loadResourceDetails(resourceId) {
            const card = $(`.resource-card[data-id="${resourceId}"], .list-item[data-id="${resourceId}"]`).first();
            
            if (card.length) {
                const resourceData = card.data('resource');
                
                if (resourceData) {
                    currentResourceId = resourceId;
                    
                    if ($('#rateResourceBtn').length) {
                        const currentUserId = <?php echo $current_user_id; ?>;
                        if (resourceData.uploader_id && resourceData.uploader_id == currentUserId) {
                            $('#rateResourceBtn')
                                .prop('disabled', true)
                                .css('opacity', '0.6')
                                .attr('title', 'You cannot rate your own resource');
                            
                            if ($('#ownResourceMessage').length === 0) {
                                $('.preview-actions').before(
                                    '<div id="ownResourceMessage" class="alert alert-warning mb-3">' +
                                    '<i class="fas fa-exclamation-triangle me-2"></i>' +
                                    'You cannot rate your own resource.' +
                                    '</div>'
                                );
                            }
                        } else {
                            $('#rateResourceBtn')
                                .prop('disabled', false)
                                .css('opacity', '1')
                                .attr('title', 'Rate this resource');
                            $('#ownResourceMessage').remove();
                        }
                    }
                    
                    $('#modalResourceTitle').text(resourceData.title);
                    $('#modalSubject').text(resourceData.subject);
                    $('#modalCourse').text(resourceData.course);
                    $('#modalUniversity').text(resourceData.university || 'N/A');
                    $('#modalDate').text(resourceData.date);
                    $('#modalFileType').text(resourceData.file_type.toUpperCase());
                    
                    if (resourceData.description && resourceData.description.trim() !== '') {
                        $('#modalDescription').text(resourceData.description);
                        $('#modalDescriptionContainer').show();
                    } else {
                        $('#modalDescriptionContainer').hide();
                    }
                    
                    if (!resourceData.university) {
                        $('#modalUniversityContainer').hide();
                    } else {
                        $('#modalUniversityContainer').show();
                    }
                    
                    if ($('#ratingResourceTitle').length) {
                        $('#ratingResourceTitle').text(resourceData.title);
                    }
                    
                    let iconClass = 'fa-file';
                    if (resourceData.file_type === 'pdf') iconClass = 'fa-file-pdf';
                    else if (resourceData.file_type === 'docx') iconClass = 'fa-file-word';
                    else if (resourceData.file_type === 'ppt') iconClass = 'fa-file-powerpoint';
                    
                    $('#modalIcon i').attr('class', 'fas ' + iconClass);
                    
                    $('#viewFileBtn').data('id', resourceId);
                    $('#downloadFileBtn').data('id', resourceId);
                    if ($('#rateResourceBtn').length) {
                        $('#rateResourceBtn').data('id', resourceId);
                    }
                    
                    const fileType = resourceData.file_type.toLowerCase();
                    if (fileType === 'docx' || fileType === 'ppt') {
                        $('#viewFileBtn').hide();
                        if (!$('#viewMessage').length) {
                            $('.preview-actions').before('<div id="viewMessage" class="alert alert-info mb-3"><i class="fas fa-info-circle me-2"></i>This file type cannot be viewed in browser. Please download to view.</div>');
                        }
                    } else {
                        $('#viewFileBtn').show();
                        $('#viewMessage').remove();
                    }
                    
                    new bootstrap.Modal(document.getElementById('resourceModal')).show();
                }
            }
        }
        <?php endif; ?>
        
        <?php if ($is_logged_in && !$is_admin): ?>
        function highlightModalStars(n) { 
            $('.rating-star-lg').each(function(i) { 
                $(this).toggleClass('active', i < n); 
            }); 
        }
        
        function resetModalStars() { 
            $('.rating-star-lg').removeClass('active'); 
        }
        
        function getRatingText(n) { 
            return ['', 'Poor — not helpful at all', 'Fair — somewhat helpful', 'Good — helpful resource', 'Very Good — very helpful', 'Excellent — outstanding resource!'][n] || ''; 
        }
        
        resetModalStars();
        
        const ratingModalEl = document.getElementById('ratingModal');
        if (ratingModalEl) {
            ratingModalEl.addEventListener('show.bs.modal', function() {
                selectedRating = 0;
                resetModalStars();
                $('#ratingReview').val('');
                $('#ratingInfo').text('Select a rating');
            });
        }
        
        $('.rating-star-lg')
            .on('mouseenter', function() { 
                highlightModalStars($(this).data('rating')); 
                $('#ratingInfo').text(getRatingText($(this).data('rating'))); 
            })
            .on('mouseleave', function() {
                if (selectedRating === 0) {
                    resetModalStars();
                    $('#ratingInfo').text('Select a rating');
                } else {
                    highlightModalStars(selectedRating);
                    $('#ratingInfo').text(getRatingText(selectedRating));
                }
            })
            .on('click', function() { 
                selectedRating = parseInt($(this).data('rating')); 
                highlightModalStars(selectedRating); 
                $('#ratingInfo').text(getRatingText(selectedRating)); 
            });
        
        $('#submitRating').click(function() {
            if (selectedRating === 0) {
                showToast('Please select a rating', 'error');
                return;
            }
            
            $.ajax({
                url: 'submit_rating.php',
                type: 'POST',
                data: {
                    resource_id: currentResourceId,
                    rating: selectedRating,
                    review: $('#ratingReview').val()
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#ratingModal').modal('hide');
                        showToast(response.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function() {
                    showToast('Failed to submit rating', 'error');
                }
            });
        });
        <?php endif; ?>
        
        $('#viewFileBtn').click(function() {
            const resourceId = $(this).data('id');
            window.open(`view_resource.php?id=${resourceId}`, '_blank');
        });
        
        $('#downloadFileBtn').click(function() {
            const resourceId = $(this).data('id');
            window.location.href = `download_resource.php?id=${resourceId}`;
        });
        
        $('#rateResourceBtn').click(function(e) {
            if ($(this).prop('disabled')) {
                e.preventDefault();
                e.stopPropagation();
                showToast('You cannot rate your own resource', 'error');
                return false;
            }
        });
        
        $('.view-resource').each(function() {
            const card = $(this).closest('.resource-card, .list-item');
            let fileTypeBadge = '';
            const fileTypeBadgeElement = card.find('.file-type-badge');
            
            if (fileTypeBadgeElement.length > 0) {
                fileTypeBadge = fileTypeBadgeElement.text().toLowerCase().trim();
            } else {
                const resourceData = card.data('resource');
                if (resourceData && resourceData.file_type) {
                    fileTypeBadge = resourceData.file_type.toLowerCase().trim();
                }
            }
            
            if (fileTypeBadge === 'docx' || fileTypeBadge === 'ppt') {
                $(this).css({
                    'opacity': '0.5',
                    'cursor': 'not-allowed'
                }).attr('title', 'Viewing not available for this file type');
                
                $(this).off('click').on('click', function(e) {
                    e.stopPropagation();
                    showToast('This file type cannot be viewed in the browser. Please use the download button instead.', 'error');
                });
            }
        });
        
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.get('login') === 'success') {
                const url = new URL(window.location.href);
                url.searchParams.delete('login');
                window.history.replaceState({}, document.title, url.pathname);
                
                var div = $('<div>').addClass('message success').text('Logged in successfully! Welcome back!');
                $('body').append(div);
                setTimeout(function() {
                    div.css({ transition: 'opacity 0.5s', opacity: '0' });
                    setTimeout(function() { div.remove(); }, 3000);
                }, 3000);
            }
            
            if (urlParams.get('logout') === 'success') {
                const url = new URL(window.location.href);
                url.searchParams.delete('logout');
                window.history.replaceState({}, document.title, url.pathname);
                
                var div = $('<div>').addClass('message success').text('Logged out successfully!');
                $('body').append(div);
                setTimeout(function() {
                    div.css({ transition: 'opacity 0.5s', opacity: '0' });
                    setTimeout(function() { div.remove(); }, 500);
                }, 3000);
            }
        })();
    });
    </script>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>NoteStation</h4>
                    <p>Your one-stop destination for academic resources. Share notes, question papers, and study materials with fellow students.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="homepage.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="upload_form.php"><i class="fas fa-chevron-right"></i> Upload Resource</a></li>
                            <li><a href="my_uploads.php"><i class="fas fa-chevron-right"></i> My Uploads</a></li>
                        <?php else: ?>
                            <li><a href="login_signup.php"><i class="fas fa-chevron-right"></i> Login / Sign Up</a></li>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                            <li><a href="admin_dashboard.php"><i class="fas fa-chevron-right"></i> Admin Dashboard</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Resources</h4>
                    <ul class="footer-links">
                        <li><a href="homepage.php?type=notes"><i class="fas fa-chevron-right"></i> Study Notes</a></li>
                        <li><a href="homepage.php?type=question_paper"><i class="fas fa-chevron-right"></i> Question Papers</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> How to Upload</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <ul class="footer-contact">
                        <li><i class="fas fa-map-marker-alt"></i> Gujarat, India</li>
                        <li><i class="fas fa-envelope"></i> support@notestation.com</li>
                        <li><i class="fas fa-phone"></i> +91 123 456 7890</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="row">
                    <div class="col-md-6">
                        <p>&copy; <?php echo date('Y'); ?> NoteStation. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
<?php
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>