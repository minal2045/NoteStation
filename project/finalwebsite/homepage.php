<?php
require_once 'config.php';

// Check if connection is successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['maintain_scroll'])) {
    $_SESSION['saved_scroll'] = (int)$_POST['maintain_scroll'];
}

// Get saved scroll position for this page
$current_page = $_SERVER['REQUEST_URI'];
$saved_scroll = $_SESSION['saved_scroll_' . md5($current_page)] ?? 0;

// Start session and check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : 0;
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Get user's full name if logged in
$user_full_name = "Admin";
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
$sql = "SELECT r.*, 
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

// Prepare and execute the query with error handling
$result = null;
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing query: " . $conn->error . "<br>SQL: " . $sql);
}

if (!empty($params)) {
    $bind_result = $stmt->bind_param($types, ...$params);
    if ($bind_result === false) {
        die("Error binding parameters: " . $stmt->error);
    }
}

$execute_result = $stmt->execute();
if ($execute_result === false) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result === false) {
    die("Error getting result: " . $stmt->error);
}

// Get unique courses for filter dropdown
$courses_sql = "SELECT DISTINCT Course_name FROM Resources WHERE approval_status = 'approved' ORDER BY Course_name";
$courses_result = $conn->query($courses_sql);
if ($courses_result === false) {
    die("Error fetching courses: " . $conn->error);
}

// Get subjects based on current filter type for dropdown
$subjects_sql = "SELECT DISTINCT Subject_name FROM Resources WHERE approval_status = 'approved'";
if ($filter_type != 'all') {
    $subjects_sql .= " AND Resource_type = '" . $conn->real_escape_string($filter_type) . "'";
}
$subjects_sql .= " ORDER BY Subject_name";
$subjects_result = $conn->query($subjects_sql);
if ($subjects_result === false) {
    die("Error fetching subjects: " . $conn->error);
}

// Get universities based on current filter type for dropdown
$universities_sql = "SELECT DISTINCT University_name FROM Resources WHERE approval_status = 'approved' AND University_name IS NOT NULL AND University_name != ''";
if ($filter_type == 'question_paper') {
    $universities_sql .= " AND Resource_type = 'question_paper'";
}
$universities_sql .= " ORDER BY University_name";
$universities_result = $conn->query($universities_sql);
if ($universities_result === false) {
    die("Error fetching universities: " . $conn->error);
}
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
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-dark: #4a3f7a;
            --secondary-dark: #5a3d7c;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #9459CF;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --hover-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
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
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 0;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 0;
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

        /* Remove list styling from nav links */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 5px;
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }

        .nav-links a, 
        .nav-links div {
            list-style: none;
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

        .nav-link.active {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Bootstrap Dropdown Styles */
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

        .dropdown-divider {
            margin: 5px 0;
            border-top: 1px solid #f0f0f0;
        }

        .badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .navbar-toggler {
            display: none;
            background: transparent;
            border: 1px solid rgba(255,255,255,0.5);
            color: white;
            padding: 6px 10px;
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
            transform: translateY(0);
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

        .btn-get-started {
            display: inline-block;
            padding: 18px 55px;
            background: white;
            color: #654D87;
            border: none;
            border-radius: 60px;
            font-size: 20px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            font-family: 'Inter', sans-serif;
        }

        .btn-get-started:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            color: #654D87;
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

        .filter-btns-small {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            flex: 1;
        }

        .filter-btn-small {
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            background: #f0f0f0;
            color: #666;
            font-size: 14px;
        }

        .filter-btn-small:hover {
            background: #e0e0e0;
        }

        .filter-btn-small.active {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: white;
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

        .view-btn:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .view-btn.active {
            background: white;
            color: #654D87;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Resources Grid */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .rating-stars {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            color: #ffc107;
            margin-right: 5px;
        }
        
        .rating-count {
            color: #888;
            font-size: 0.8rem;
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

        .preview-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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

        .btn-login {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
        }

        .btn-signup {
            background: #28a745;
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
        }

        .btn-login:hover,
        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: #fff;
            padding: 60px 0 20px;
            margin-top: 80px;
            position: relative;
            z-index: 10;
            font-family: 'Inter', sans-serif;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: -50px;
            left: 0;
            right: 0;
            height: 50px;
            background: linear-gradient(135deg, transparent 0%, transparent 100%);
            pointer-events: none;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }

        .footer-section p {
            color: rgba(255,255,255,0.7);
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .social-links {
            display: flex;
            gap: 10px;
        }

        .social-link {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            transition: all 0.3s;
            text-decoration: none;
        }

        .social-link:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: translateY(-3px);
            color: #fff;
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

        .footer-links a:hover i {
            transform: translateX(3px);
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

        .footer-bottom .fa-heart {
            animation: heartbeat 1.5s ease infinite;
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Responsive */
        @media (max-width: 992px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(0,0,0,0.8);
                backdrop-filter: blur(10px);
                flex-direction: column;
                padding: 15px;
                gap: 8px;
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
                padding: 10px !important;
            }
            
            .dropdown-menu {
                position: static;
                width: 100%;
                margin-top: 5px;
                box-shadow: none;
                background: rgba(255,255,255,0.9);
            }
        }

        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 22px;
            }
            
            .navbar-brand i {
                font-size: 24px;
            }
            
            .hero-large-title {
                font-size: 64px;
            }
            
            .hero-description {
                font-size: 18px;
                padding: 0 20px;
            }
            
            .btn-get-started {
                padding: 16px 45px;
                font-size: 18px;
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
            
            .login-buttons {
                flex-direction: column;
            }
            
            .control-bar {
                flex-direction: column;
            }
            
            .filter-btns-small {
                width: 100%;
                justify-content: center;
            }
            
            .list-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .list-details {
                width: 100%;
            }
        }

        /* Responsive Footer */
        @media (max-width: 768px) {
            .footer {
                padding: 40px 0 20px;
                margin-top: 50px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .footer-section h4 {
                font-size: 1.2rem;
                margin-bottom: 20px;
            }
            
            .footer-section h4::after {
                width: 40px;
            }
            
            .footer-bottom .text-md-end {
                text-align: left !important;
                margin-top: 10px;
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
                    <a class="nav-link active" href="homepage.php">Home</a>
                    
                    
                    <?php if ($is_logged_in): ?>
                        <?php if (!$is_admin): ?>
                        <!-- My Uploads Link -->
                         <a class="nav-link" href="upload_form.php">Upload</a>
                        <a class="nav-link" href="my_uploads.php">My Uploads</a>
                        <?php endif; ?>
                        
                        <?php if ($is_admin): ?>
                            <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                        <?php endif; ?>
                        
                        <!-- Bootstrap Dropdown  -->
                        <div class="dropdown d-inline-block">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> 
                                <?php echo htmlspecialchars($_SESSION['full_name'] ?? $user_full_name); ?>
                                <!-- <?php if ($is_admin): ?>
                                    <span class="badge bg-warning text-dark ms-2">Admin</span>
                                <?php endif; ?> -->
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Links for non-logged-in users -->
                        <a class="nav-link" href="login_signup.php">Login</a>
                        <a class="nav-link" href="login_signup.php">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <div class="hero-small-title">Your Academic Resource Hub</div>
                <h1 class="hero-large-title">
                    Note <span class="station-text">  Station</span>
                </h1>
                <p class="hero-description">
                    Find lecture notes & past papers in one place. Upload, download, and ace your exams — anytime, anywhere.
                </p>
                <!-- <?php if (!$is_logged_in): ?>
                    <a href="login_signup.php" class="btn-get-started">
                        <i class="fas fa-rocket" style="margin-right: 8px;"></i>Get Started Free
                    </a>
                <?php endif; ?> -->
            </div>
        </section>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Filter Section -->
            <div class="filter-section">
                <h5 class="filter-title"><i class="fas fa-filter me-2"></i>Filter Resources</h5>
                
                <form method="GET" action="homepage.php" id="filterForm">
                    <?php if (!empty($search_query)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <?php endif; ?>
                    
                    <div class="filter-btns">
                        <button type="submit" name="type" value="all" 
                                class="filter-btn <?php echo $filter_type == 'all' ? 'active' : ''; ?>">
                            All Resources
                        </button>
                        <button type="submit" name="type" value="notes" 
                                class="filter-btn <?php echo $filter_type == 'notes' ? 'active' : ''; ?>">
                            <i class="fas fa-book me-2"></i>Notes
                        </button>
                        <button type="submit" name="type" value="question_paper" 
                                class="filter-btn <?php echo $filter_type == 'question_paper' ? 'active' : ''; ?>">
                            <i class="fas fa-file-alt me-2"></i>Question Papers
                        </button>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select name="course" class="filter-select" onchange="this.form.submit()">
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
                            <select name="subject" class="filter-select" onchange="this.form.submit()">
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
                        
                        <div class="col-md-3">
                            <select name="university" class="filter-select" id="universityDropdown" 
                                    style="<?php echo $filter_type == 'question_paper' ? 'display: block;' : 'display: none;'; ?>" 
                                    onchange="this.form.submit()">
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
                            <select name="rating" class="filter-select" onchange="this.form.submit()">
                                <option value="0">All Ratings</option>
                                <option value="1" <?php echo $filter_rating == 1 ? 'selected' : ''; ?>>★ 1+ Star</option>
                                <option value="2" <?php echo $filter_rating == 2 ? 'selected' : ''; ?>>★★ 2+ Stars</option>
                                <option value="3" <?php echo $filter_rating == 3 ? 'selected' : ''; ?>>★★★ 3+ Stars</option>
                                <option value="4" <?php echo $filter_rating == 4 ? 'selected' : ''; ?>>★★★★ 4+ Stars</option>
                                <option value="5" <?php echo $filter_rating == 5 ? 'selected' : ''; ?>>★★★★★ 5 Stars</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Control Bar for View Toggle -->
            <?php if ($result && $result->num_rows > 0): ?>
            <div class="control-bar">
                <!-- <div class="filter-btns-small">
                    <button class="filter-btn-small active" data-filter="all">All</button>
                    <button class="filter-btn-small" data-filter="notes">Notes</button>
                    <button class="filter-btn-small" data-filter="question_paper">Question Papers</button>
                </div>
                 -->
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
                    <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
                </div>
            </div><br/>
            <?php endif; ?>

            <!-- Resources Grid View -->
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="resources-grid" id="gridView">
                    <?php 
                    // Reset result pointer for grid view
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while($row = $result->fetch_assoc()): 
                        // Determine file icon
                        $fileIcon = 'fa-file';
                        if ($row['File_type'] == 'pdf') $fileIcon = 'fa-file-pdf';
                        elseif ($row['File_type'] == 'docx') $fileIcon = 'fa-file-word';
                        elseif ($row['File_type'] == 'ppt') $fileIcon = 'fa-file-powerpoint';
                        
                        // Format date
                        $uploadDate = date('d M Y', strtotime($row['Upload_date']));
                        
                        // Format rating
                        $avgRating = round($row['avg_rating'], 1);
                        $ratingCount = $row['rating_count'];
                        
                        // Create a JSON object with all resource data
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
                            'rating_count' => $ratingCount
                        ]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="resource-card" 
                         data-id="<?php echo $row['Resource_id']; ?>" 
                         data-type="<?php echo $row['Resource_type']; ?>"
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
                    <?php endwhile; ?>
                </div>

                <!-- Resources List View -->
                <div class="resources-list" id="listView" style="display: none;">
                    <?php 
                    // Reset result pointer for list view
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while($row = $result->fetch_assoc()): 
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
                            'rating_count' => $ratingCount
                        ]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="list-item" 
                         data-id="<?php echo $row['Resource_id']; ?>" 
                         data-type="<?php echo $row['Resource_type']; ?>"
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
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>No Resources Found</h3>
                    <p>No study materials match your filters. Try adjusting your search criteria.</p>
                    <?php if (!$is_logged_in): ?>
                        <p class="text-muted">Login to upload and share your study materials!</p>
                    <?php else: ?>
                        <a href="upload_form.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Upload Your First Resource
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

                        <div class="current-rating" id="currentRatingContainer" style="display: none;">
                            <i class="fas fa-star text-warning"></i>
                            <span id="currentRating"></span>
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
                        
                        <button class="btn btn-primary w-100" id="submitRating">
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
                    <i class="fas fa-lock"></i>
                    <h3>Login Required</h3>
                    <p>You need to be logged in to view and download resources. Please login or create an account to continue.</p>
                    <div class="login-buttons">
                        <a href="login_signup.php" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                        <a href="login_signup.php" class="btn btn-signup">
                            <i class="fas fa-user-plus me-2"></i>Sign Up
                        </a>
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
    $(document).ready(function() {
        let currentResourceId = null;
        let selectedRating = 0;
        
        // Parallax Effect
        $(window).scroll(function() {
            const scroll = $(this).scrollTop();
            $('#parallaxBg').css('transform', 'translateY(' + scroll * 0.5 + 'px)');
            
            // Add scrolled class to navbar
            if (scroll > 50) {
                $('#mainNavbar').addClass('scrolled');
            } else {
                $('#mainNavbar').removeClass('scrolled');
            }
        });
        
        // Mobile menu toggle
        $('#navbarToggler').click(function() {
            $('#navLinks').toggleClass('show');
        });
        
        // View toggle functionality
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
        
        // Filter functionality for the control bar
        $('.filter-btn-small').click(function() {
            $('.filter-btn-small').removeClass('active');
            $(this).addClass('active');
            
            const filter = $(this).data('filter');
            
            if (filter === 'all') {
                $('.resource-card, .list-item').show();
            } else {
                $('.resource-card, .list-item').each(function() {
                    if ($(this).data('type') === filter) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });
        
        <?php if (!$is_logged_in): ?>
        // Show login prompt for non-logged in users
        $('.view-resource, .download-resource').click(function(e) {
            e.stopPropagation();
            new bootstrap.Modal(document.getElementById('loginPromptModal')).show();
        });
        
        $('.resource-card, .list-item').click(function() {
            new bootstrap.Modal(document.getElementById('loginPromptModal')).show();
        });
        <?php else: ?>
        // View resource (open modal)
        $('.view-resource').click(function(e) {
            e.stopPropagation();
            const resourceId = $(this).data('id');
            loadResourceDetails(resourceId);
        });
        
        // Card click to view resource
        $('.resource-card, .list-item').click(function(e) {
            if (!$(e.target).closest('.action-btn').length) {
                const resourceId = $(this).data('id');
                loadResourceDetails(resourceId);
            }
        });
        
        // Download resource
        $('.download-resource').click(function(e) {
            e.stopPropagation();
            const resourceId = $(this).data('id');
            window.location.href = `download_resource.php?id=${resourceId}`;
        });
        
        // Function to load resource details from embedded data
        function loadResourceDetails(resourceId) {
            // Find the card with this ID and get its embedded data
            const card = $(`.resource-card[data-id="${resourceId}"], .list-item[data-id="${resourceId}"]`).first();
            
            if (card.length) {
                // Get the embedded JSON data
                const resourceData = card.data('resource');
                
                if (resourceData) {
                    currentResourceId = resourceId;
                    
                    // Set modal content using the embedded data
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
                    
                    // Set rating info for rating modal if needed
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
                    
                    // Show modal
                    new bootstrap.Modal(document.getElementById('resourceModal')).show();
                }
            }
        }
        
        <?php endif; ?>
        
        $('#viewFileBtn').click(function() {
            const resourceId = $(this).data('id');
            window.open(`view_resource.php?id=${resourceId}`, '_blank');
        });
        
        $('#downloadFileBtn').click(function() {
            const resourceId = $(this).data('id');
            window.location.href = `download_resource.php?id=${resourceId}`;
        });
        
        // Update view-resource buttons for DOCX/PPT
        $('.view-resource').each(function() {
            const card = $(this).closest('.resource-card, .list-item');
            const fileTypeBadge = card.find('.file-type-badge').text().toLowerCase().trim();
            
            if (fileTypeBadge === 'docx' || fileTypeBadge === 'ppt') {
                $(this).css({
                    'opacity': '0.5',
                    'cursor': 'not-allowed'
                }).attr('title', 'Viewing not available for this file type');
                
                $(this).off('click').on('click', function(e) {
                    e.stopPropagation();
                    alert('This file type cannot be viewed in the browser. Please use the download button instead.');
                });
            }
        });
        
        $('.filter-btn').click(function() {
            const type = $(this).val();
            if (type === 'question_paper') {
                $('#universityDropdown').show();
            } else {
                $('#universityDropdown').hide();
                $('select[name="university"]').val('');
            }
        });
    });

    function setRating(rating) {
        document.getElementById('ratingInput').value = rating;
        document.getElementById('filterForm').submit();
    }
    
    window.addEventListener('load', function() {
        const savedScroll = <?php echo $saved_scroll; ?>;
        if (savedScroll > 0) {
            setTimeout(function() {
                window.scrollTo(0, savedScroll);
            }, 10);
        }
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
                    <div class="col-md-6 text-md-end">
                        <p>Made with <i class="fas fa-heart" style="color: #ff4d4d;"></i> for students</p>
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