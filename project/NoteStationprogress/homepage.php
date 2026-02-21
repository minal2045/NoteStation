<?php
require_once 'config.php';

// Start session and check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : 0;
$is_admin = false;

// Check if user is admin (you can modify this based on your users table structure)
// if ($is_logged_in) {
//     $admin_sql = "SELECT is_admin FROM users WHERE user_id = ?";
//     $admin_stmt = $conn->prepare($admin_sql);
//     $admin_stmt->bind_param("i", $current_user_id);
//     $admin_stmt->execute();
//     $admin_result = $admin_stmt->get_result();
//     if ($admin_result->num_rows > 0) {
//         $admin_data = $admin_result->fetch_assoc();
//         $is_admin = isset($admin_data['is_admin']) && $admin_data['is_admin'] == 1;
//     }
// }

// Get filter parameters
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$filter_course = isset($_GET['course']) ? $_GET['course'] : '';
$filter_subject = isset($_GET['subject']) ? $_GET['subject'] : '';
$filter_rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query based on filters
$sql = "SELECT r.*, 
        COALESCE(AVG(rt.rating), 0) as avg_rating,
        COUNT(rt.rating_id) as rating_count
        FROM Resources r 
        LEFT JOIN Ratings rt ON r.Resource_id = rt.resource_id";

$conditions = [];
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

if ($filter_rating > 0) {
    $conditions[] = "r.Resource_id IN (
        SELECT resource_id FROM Ratings 
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
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get unique courses for filter dropdown
$courses_sql = "SELECT DISTINCT Course_name FROM Resources ORDER BY Course_name";
$courses_result = $conn->query($courses_sql);

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total_resources,
                SUM(CASE WHEN Resource_type = 'notes' THEN 1 ELSE 0 END) as total_notes,
                SUM(CASE WHEN Resource_type = 'question_paper' THEN 1 ELSE 0 END) as total_papers,
                COUNT(DISTINCT Course_name) as total_courses
              FROM Resources";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
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
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --hover-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Navbar Styles */
        .navbar {
            background: var(--primary-gradient) !important;
            padding: 15px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: white !important;
        }
        
        .navbar-brand i {
            margin-right: 10px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 8px 20px !important;
            margin: 0 5px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.2);
            color: white !important;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.3);
            color: white !important;
        }
        
        .user-menu {
            background: rgba(255,255,255,0.2);
            border-radius: 30px;
            padding: 5px 5px 5px 20px;
        }
        
        .user-menu .nav-link {
            display: inline-block;
            padding: 8px 15px !important;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-weight: 600;
            margin-left: 10px;
        }
        
        /* Hero Section */
        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 80px 0;
            margin-bottom: 60px;
            border-radius: 0 0 50px 50px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" fill-opacity="0.3" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,170.7C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: cover;
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .stats-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 15px 30px;
            border-radius: 50px;
            margin-right: 15px;
            margin-bottom: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .stats-badge i {
            margin-right: 10px;
            font-size: 24px;
        }
        
        .stats-badge .stat-number {
            font-size: 24px;
            font-weight: 700;
            display: block;
        }
        
        .stats-badge .stat-label {
            font-size: 14px;
            opacity: 0.8;
        }
        
        /* Search Section */
        .search-section {
            margin-top: -40px;
            margin-bottom: 40px;
            position: relative;
            z-index: 10;
        }
        
        .search-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--card-shadow);
        }
        
        .search-input-group {
            position: relative;
        }
        
        .search-input {
            height: 60px;
            border-radius: 15px !important;
            border: 2px solid #e0e0e0;
            padding-left: 50px;
            font-size: 16px;
        }
        
        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 18px;
            z-index: 10;
        }
        
        .search-btn {
            height: 60px;
            border-radius: 15px;
            background: var(--primary-gradient);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0 40px;
            transition: all 0.3s;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        /* Disabled view button styling */
        .action-btn.view-disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #e0e0e0;
            color: #999;
        }

        .action-btn.view-disabled:hover {
            background: #e0e0e0;
            color: #999;
            transform: none;
            box-shadow: none;
        }

        /* File type message */
        .file-type-message {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
            padding: 5px;
            background: #f8f9fa;
            border-radius: 5px;
            display: inline-block;
        }

        /* Info message in modal */
        #viewMessage {
            font-size: 0.95rem;
            padding: 12px 15px;
            border-left: 4px solid #17a2b8;
        }

        /* Card hover effect for disabled view */
        .resource-card.disabled-view {
            cursor: default;
        }

        .resource-card.disabled-view:hover {
            transform: none;
            box-shadow: var(--card-shadow);
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }
        
        .filter-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        
        .filter-btn {
            padding: 10px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            background: #f0f0f0;
            color: #666;
        }
        
        .filter-btn:hover {
            background: #e0e0e0;
        }
        
        .filter-btn.active {
            background: var(--primary-gradient);
            color: white;
        }
        
        .filter-select {
            padding: 10px 25px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-weight: 500;
            color: #666;
            min-width: 200px;
        }
        
        .rating-filter {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .rating-star {
            font-size: 20px;
            color: #ffc107;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .rating-star:hover {
            transform: scale(1.2);
        }
        
        .rating-star.inactive {
            color: #e0e0e0;
        }
        
        /* Resources Grid */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
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
            background: var(--primary-gradient);
            padding: 20px;
            color: white;
            position: relative;
            min-height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .file-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .file-type-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .resource-type-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: white;
            backdrop-filter: blur(5px);
        }
        
        .rating-badge i {
            color: #ffc107;
            margin-right: 3px;
        }
        
        .card-body {
            padding: 20px;
            flex: 1;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .card-details {
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .detail-item i {
            width: 18px;
            color: #667eea;
            font-size: 14px;
        }
        
        .description {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .card-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .upload-date {
            font-size: 0.8rem;
            color: #888;
        }
        
        .upload-date i {
            margin-right: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: none;
            background: white;
            color: #667eea;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn:hover {
            background: var(--primary-gradient);
            color: white;
            transform: scale(1.1);
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
            color: #ffc107;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .rating-star-lg:hover {
            transform: scale(1.2);
        }
        
        .rating-star-lg.inactive {
            color: #e0e0e0;
        }
        
        .rating-info {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Login Prompt Modal */
        .login-prompt {
            text-align: center;
            padding: 30px;
        }
        
        .login-prompt i {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .login-prompt h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .login-prompt p {
            color: #666;
            margin-bottom: 25px;
        }
        
        .login-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .login-buttons .btn {
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-login {
            background: var(--primary-gradient);
            color: white;
            border: none;
        }
        
        .btn-signup {
            background: #28a745;
            color: white;
            border: none;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }
        
        .empty-state i {
            font-size: 80px;
            color: #667eea;
            opacity: 0.5;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Modal Styles */
        .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }
        
        .modal-header {
            background: var(--primary-gradient);
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
        
        .resource-preview {
            text-align: center;
        }
        
        .preview-icon {
            width: 100px;
            height: 100px;
            background: var(--primary-gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
        }
        
        .preview-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
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
            padding: 8px 15px;
            border-radius: 8px;
        }
        
        .meta-item i {
            color: #667eea;
        }
        
        .preview-description {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: #666;
            text-align: left;
        }
        
        .current-rating {
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: #856404;
        }
        
        .preview-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .preview-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .preview-btn.view {
            background: var(--primary-gradient);
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
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .resource-card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .stats-badge {
                display: block;
                margin-right: 0;
            }
            
            .filter-group {
                flex-direction: column;
            }
            
            .filter-select {
                width: 100%;
            }
            
            .preview-actions {
                flex-direction: column;
            }
            
            .preview-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="homepage.php">
                <i class="fas fa-cloud-upload-alt"></i>
                NoteStation
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="homepage.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="upload_form.php">
                                <i class="fas fa-cloud-upload-alt"></i> Upload
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_uploads.php">
                                <i class="fas fa-file-alt"></i> My Uploads
                            </a>
                        </li>
                        <?php if ($is_admin): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin_dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Admin
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> Profile
                                <span class="user-avatar">
                                    <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                                <li><a class="dropdown-item" href="my_uploads.php"><i class="fas fa-file-alt"></i> My Uploads</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login_signup.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login_signup.php">
                                <i class="fas fa-user-plus"></i> Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="hero-title">Find lecture notes & past papers in one place.</h1>
                    <p class="hero-subtitle">Upload, download, and ace your exams - anytime, anywhere.</p>
                    
                    <!-- <div class="mt-5">
                        <div class="stats-badge">
                            <i class="fas fa-file-alt"></i>
                            <span class="stat-number"><?php echo number_format($stats['total_resources'] ?? 0); ?>+</span>
                            <span class="stat-label">Resources</span>
                        </div>
                        <div class="stats-badge">
                            <i class="fas fa-users"></i>
                            <span class="stat-number">5K+</span>
                            <span class="stat-label">Active Students</span>
                        </div>
                        <div class="stats-badge">
                            <i class="fas fa-book"></i>
                            <span class="stat-number"><?php echo number_format($stats['total_courses'] ?? 0); ?>+</span>
                            <span class="stat-label">Subjects</span>
                        </div>
                    </div> -->
                </div>
                <!-- <div class="col-lg-4 text-center">
                    <?php if (!$is_logged_in): ?>
                        <a href="register.php" class="btn btn-light btn-lg px-5 py-3 rounded-pill">
                            <i class="fas fa-rocket me-2"></i>Get Started Free
                        </a>
                    <?php endif; ?>
                </div> -->
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <div class="container search-section">
        <div class="search-card">
            <form method="GET" action="homepage.php">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="search-input-group">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="form-control search-input" 
                                   name="search" placeholder="Search for notes, question papers, subjects..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn search-btn w-100">
                            <i class="fas fa-search me-2"></i>Search Resources
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="container">
        <div class="filter-section">
            <h5 class="filter-title"><i class="fas fa-filter me-2"></i>Filter Resources</h5>
            
            <form method="GET" action="homepage.php" id="filterForm">
                <?php if (!empty($search_query)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                <?php endif; ?>
                
                <div class="filter-group">
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
                    
                    <input type="text" name="subject" class="filter-select" 
                           placeholder="Subject name" value="<?php echo htmlspecialchars($filter_subject); ?>"
                           onchange="this.form.submit()">
                    
                    <div class="rating-filter">
                        <span class="me-2">Min Rating:</span>
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star rating-star <?php echo $filter_rating < $i ? 'inactive' : ''; ?>" 
                               onclick="setRating(<?php echo $i; ?>)"></i>
                        <?php endfor; ?>
                        <input type="hidden" name="rating" id="ratingInput" value="<?php echo $filter_rating; ?>">
                    </div>
                </div>
            </form>
        </div>

        <!-- Resources Grid -->
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="resources-grid">
                <?php while($row = $result->fetch_assoc()): 
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
                ?>
                <div class="resource-card" data-id="<?php echo $row['Resource_id']; ?>">
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
                            <button class="action-btn view-resource" title="View Resource">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn download-resource" title="Download">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
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

                        <!-- Rating Section -->
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
                            <?php if ($is_logged_in): ?>
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
    <?php if ($is_logged_in): ?>
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
                        <a href="login_signup.html" class="btn btn-signup">
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
    
    <?php if (!$is_logged_in): ?>
    // Show login prompt for non-logged in users when they try to access resources
    $('.view-resource, .download-resource').click(function(e) {
        e.stopPropagation();
        new bootstrap.Modal(document.getElementById('loginPromptModal')).show();
    });
    
    $('.resource-card').click(function() {
        new bootstrap.Modal(document.getElementById('loginPromptModal')).show();
    });
    <?php else: ?>
    // View resource (open modal)
    $('.view-resource').click(function(e) {
        e.stopPropagation();
        const card = $(this).closest('.resource-card');
        const resourceId = card.data('id');
        loadResourceDetails(resourceId);
    });
    
    // Card click to view resource
    $('.resource-card').click(function(e) {
        if (!$(e.target).closest('.action-btn').length) {
            const resourceId = $(this).data('id');
            loadResourceDetails(resourceId);
        }
    });
    
    // Download resource
    $('.download-resource').click(function(e) {
        e.stopPropagation();
        const card = $(this).closest('.resource-card');
        const resourceId = card.data('id');
        window.location.href = `download_resource.php?id=${resourceId}`;
    });
    
    // Function to load resource details via AJAX
    function loadResourceDetails(resourceId) {
        currentResourceId = resourceId;
        
        $.ajax({
            url: `view_resource.php?id=${resourceId}&format=json`,
            type: 'GET',
            success: function(data) {
                // Set modal content
                $('#modalResourceTitle').text(data.Title);
                $('#modalSubject').text(data.Subject_name);
                $('#modalCourse').text(data.Course_name);
                $('#modalUniversity').text(data.University_name || 'N/A');
                $('#modalDate').text(formatDate(data.Upload_date));
                $('#modalFileType').text(data.File_type.toUpperCase());
                
                if (data.Description && data.Description.trim() !== '') {
                    $('#modalDescription').text(data.Description);
                    $('#modalDescriptionContainer').show();
                } else {
                    $('#modalDescriptionContainer').hide();
                }
                
                if (!data.University_name) {
                    $('#modalUniversityContainer').hide();
                } else {
                    $('#modalUniversityContainer').show();
                }
                
                // Set icon based on file type
                let iconClass = 'fa-file';
                if (data.File_type === 'pdf') iconClass = 'fa-file-pdf';
                else if (data.File_type === 'docx') iconClass = 'fa-file-word';
                else if (data.File_type === 'ppt') iconClass = 'fa-file-powerpoint';
                
                $('#modalIcon i').attr('class', 'fas ' + iconClass);
                
                // Store resource ID for actions
                $('#viewFileBtn').data('id', resourceId);
                $('#downloadFileBtn').data('id', resourceId);
                $('#rateResourceBtn').data('id', resourceId);
                $('#ratingResourceTitle').text(data.Title);
                
                // Check if user is the owner (by comparing with session user_id from PHP)
                <?php if ($is_logged_in): ?>
                if (data.user_id == <?php echo $current_user_id; ?>) {
                    $('#rateResourceBtn').hide();
                    // Add message if not already present
                    if (!$('#ownerMessage').length) {
                        $('.preview-actions').before('<div id="ownerMessage" class="alert alert-warning mb-3"><i class="fas fa-exclamation-triangle me-2"></i>You cannot rate your own resource.</div>');
                    }
                } else {
                    $('#rateResourceBtn').show();
                    $('#ownerMessage').remove();
                }
                <?php endif; ?>
                
                // Hide view button for DOCX and PPT files
                const fileType = data.File_type.toLowerCase();
                if (fileType === 'docx' || fileType === 'ppt') {
                    $('#viewFileBtn').hide();
                    // Add message if not already present
                    if (!$('#viewMessage').length) {
                        $('.preview-actions').before('<div id="viewMessage" class="alert alert-info mb-3"><i class="fas fa-info-circle me-2"></i>This file type cannot be viewed in browser. Please download to view.</div>');
                    }
                } else {
                    $('#viewFileBtn').show();
                    $('#viewMessage').remove();
                }
                
                // Load current rating
                loadUserRating(resourceId);
                
                // Show modal
                new bootstrap.Modal(document.getElementById('resourceModal')).show();
            },
            error: function() {
                alert('Failed to load resource details');
            }
        });
    }
    
    // Function to load user's rating
    function loadUserRating(resourceId) {
        $.ajax({
            url: `get_rating.php?id=${resourceId}`,
            type: 'GET',
            success: function(data) {
                if (data.rating) {
                    $('#currentRating').text(`Your rating: ${data.rating}/5 - ${data.review || ''}`);
                    $('#currentRatingContainer').show();
                } else {
                    $('#currentRatingContainer').hide();
                }
            }
        });
    }
    
    // Rating star hover effect
    $('.rating-star-lg').hover(
        function() {
            const rating = $(this).data('rating');
            highlightStars(rating);
            $('#ratingInfo').text(getRatingText(rating));
        },
        function() {
            if (selectedRating === 0) {
                resetStars();
                $('#ratingInfo').text('Select a rating');
            } else {
                highlightStars(selectedRating);
                $('#ratingInfo').text(getRatingText(selectedRating));
            }
        }
    );
    
    // Rating star click
    $('.rating-star-lg').click(function() {
        selectedRating = $(this).data('rating');
        highlightStars(selectedRating);
        $('#ratingInfo').text(getRatingText(selectedRating));
    });
    
    // Submit rating
    $('#submitRating').click(function() {
        if (selectedRating === 0) {
            alert('Please select a rating');
            return;
        }
        
        const review = $('#ratingReview').val();
        
        $.ajax({
            url: 'submit_rating.php',
            type: 'POST',
            data: {
                resource_id: currentResourceId,
                rating: selectedRating,
                review: review
            },
            success: function(response) {
                if (response.success) {
                    // Close rating modal
                    bootstrap.Modal.getInstance(document.getElementById('ratingModal')).hide();
                    
                    // Reset rating
                    selectedRating = 0;
                    resetStars();
                    $('#ratingReview').val('');
                    
                    // Show success message
                    alert('Rating submitted successfully!');
                    
                    // Reload resource details to show updated rating
                    loadResourceDetails(currentResourceId);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Failed to submit rating');
            }
        });
    });
    
    // Helper function to highlight stars
    function highlightStars(rating) {
        $('.rating-star-lg').each(function(index) {
            if (index < rating) {
                $(this).removeClass('inactive');
            } else {
                $(this).addClass('inactive');
            }
        });
    }
    
    // Helper function to reset stars
    function resetStars() {
        $('.rating-star-lg').addClass('inactive');
    }
    
    // Helper function to get rating text
    function getRatingText(rating) {
        const texts = [
            '',
            'Poor - Not helpful at all',
            'Fair - Somewhat helpful',
            'Good - Helpful resource',
            'Very Good - Very helpful',
            'Excellent - Outstanding resource!'
        ];
        return texts[rating];
    }
    
    // Update view-resource buttons to be visually disabled for DOCX/PPT
    $('.view-resource').each(function() {
        const card = $(this).closest('.resource-card');
        const fileTypeBadge = card.find('.file-type-badge').text().toLowerCase().trim();
        
        if (fileTypeBadge === 'docx' || fileTypeBadge === 'ppt') {
            $(this).css({
                'opacity': '0.5',
                'cursor': 'not-allowed'
            }).attr('title', 'Viewing not available for this file type');
            
            // Change click behavior
            $(this).off('click').on('click', function(e) {
                e.stopPropagation();
                const card = $(this).closest('.resource-card');
                const resourceId = card.data('id');
                loadResourceDetails(resourceId); // This will still open modal but with view button hidden
            });
        }
    });
    
    <?php endif; ?>
    
    // View file
    $('#viewFileBtn').click(function() {
        const resourceId = $(this).data('id');
        window.open(`view_resource.php?id=${resourceId}`, '_blank');
    });
    
    // Download file
    $('#downloadFileBtn').click(function() {
        const resourceId = $(this).data('id');
        window.location.href = `download_resource.php?id=${resourceId}`;
    });
    
    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }
});

// Function to set rating filter
function setRating(rating) {
    document.getElementById('ratingInput').value = rating;
    document.getElementById('filterForm').submit();
}
</script>
</body>
</html>
<?php
$conn->close();
?>