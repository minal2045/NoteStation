<?php
require_once 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_signup.php");
    exit();
}

// Get database statistics
$stats = [];

// Users count
$users_result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['users'] = $users_result->fetch_assoc()['count'];

// Resources count (only approved for public view, but admin sees all)
$resources_result = $conn->query("SELECT COUNT(*) as count FROM Resources");
$stats['resources'] = $resources_result->fetch_assoc()['count'];

// Pending approvals count
$pending_result = $conn->query("SELECT COUNT(*) as count FROM Resources WHERE approval_status = 'pending'");
$stats['pending'] = $pending_result->fetch_assoc()['count'];

// Ratings count
$ratings_result = $conn->query("SELECT COUNT(*) as count FROM Ratings");
$stats['ratings'] = $ratings_result->fetch_assoc()['count'];

// Notes count
$notes_result = $conn->query("SELECT COUNT(*) as count FROM Resources WHERE Resource_type = 'notes'");
$stats['notes'] = $notes_result->fetch_assoc()['count'];

// Question papers count
$papers_result = $conn->query("SELECT COUNT(*) as count FROM Resources WHERE Resource_type = 'question_paper'");
$stats['question_papers'] = $papers_result->fetch_assoc()['count'];

// Get all tables data
$users_data = $conn->query("SELECT * FROM users ORDER BY id DESC");

// Resources data with approval status - show all resources for admin
$resources_data = $conn->query("SELECT r.*, u.username as uploader_name FROM Resources r LEFT JOIN users u ON r.User_id = u.id ORDER BY 
    CASE r.approval_status 
        WHEN 'pending' THEN 1 
        WHEN 'approved' THEN 2 
        WHEN 'rejected' THEN 3 
    END, r.Upload_date DESC");

$ratings_data = $conn->query("SELECT rt.*, u.username as user_name, res.Title as resource_title FROM Ratings rt LEFT JOIN users u ON rt.user_id = u.id LEFT JOIN Resources res ON rt.resource_id = res.Resource_id ORDER BY rt.rating_id DESC");

// Handle user deletion
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delete_stmt->bind_param("i", $user_id);
    if ($delete_stmt->execute()) {
        $success_message = "User deleted successfully";
        // Refresh data
        $users_data = $conn->query("SELECT * FROM users ORDER BY id DESC");
    } else {
        $error_message = "Failed to delete user";
    }
}

// Handle resource approval/rejection
if (isset($_POST['action']) && isset($_POST['resource_id'])) {
    $resource_id = (int)$_POST['resource_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $update_stmt = $conn->prepare("UPDATE Resources SET approval_status = ? WHERE Resource_id = ?");
        $update_stmt->bind_param("si", $status, $resource_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Resource " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully";
            // Refresh data
            $resources_data = $conn->query("SELECT r.*, u.username as uploader_name FROM Resources r LEFT JOIN users u ON r.User_id = u.id ORDER BY 
                CASE r.approval_status 
                    WHEN 'pending' THEN 1 
                    WHEN 'approved' THEN 2 
                    WHEN 'rejected' THEN 3 
                END, r.Upload_date DESC");
        } else {
            $error_message = "Failed to update resource status";
        }
    }
}

// Handle resource deletion
if (isset($_POST['delete_resource']) && isset($_POST['resource_id'])) {
    $resource_id = (int)$_POST['resource_id'];
    
    // First get file path to delete the actual file
    $file_query = $conn->prepare("SELECT file_path FROM Resources WHERE Resource_id = ?");
    $file_query->bind_param("i", $resource_id);
    $file_query->execute();
    $file_result = $file_query->get_result();
    
    if ($file_result->num_rows > 0) {
        $file_row = $file_result->fetch_assoc();
        $file_path = $file_row['file_path'];
        
        // Delete from database (ratings will cascade due to foreign key)
        $delete_stmt = $conn->prepare("DELETE FROM Resources WHERE Resource_id = ?");
        $delete_stmt->bind_param("i", $resource_id);
        
        if ($delete_stmt->execute()) {
            // Delete actual file
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $success_message = "Resource deleted successfully";
            // Refresh data
            $resources_data = $conn->query("SELECT r.*, u.username as uploader_name FROM Resources r LEFT JOIN users u ON r.User_id = u.id ORDER BY 
                CASE r.approval_status 
                    WHEN 'pending' THEN 1 
                    WHEN 'approved' THEN 2 
                    WHEN 'rejected' THEN 3 
                END, r.Upload_date DESC");
        } else {
            $error_message = "Failed to delete resource";
        }
    }
}

// Get report data - REMOVED bar graph report
$report_type = isset($_GET['report']) ? $_GET['report'] : 'summary';

// Monthly uploads report - we'll keep this but won't display chart
$monthly_uploads = $conn->query("
    SELECT 
        DATE_FORMAT(Upload_date, '%Y-%m') as month,
        COUNT(*) as total,
        SUM(CASE WHEN Resource_type = 'notes' THEN 1 ELSE 0 END) as notes,
        SUM(CASE WHEN Resource_type = 'question_paper' THEN 1 ELSE 0 END) as papers
    FROM Resources 
    GROUP BY DATE_FORMAT(Upload_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");

// Top contributors
$top_contributors = $conn->query("
    SELECT 
        u.id,
        u.username,
        u.full_name,
        COUNT(r.Resource_id) as upload_count
    FROM users u
    LEFT JOIN Resources r ON u.id = r.User_id
    GROUP BY u.id
    ORDER BY upload_count DESC
    LIMIT 10
");

// Most rated resources
$most_rated = $conn->query("
    SELECT 
        r.Resource_id,
        r.Title,
        r.Course_name,
        r.Subject_name,
        COUNT(rt.rating_id) as rating_count,
        AVG(rt.rating) as avg_rating
    FROM Resources r
    LEFT JOIN Ratings rt ON r.Resource_id = rt.resource_id
    GROUP BY r.Resource_id
    HAVING rating_count > 0
    ORDER BY rating_count DESC
    LIMIT 10
");

// Pending approvals for quick view
$pending_approvals = $conn->query("
    SELECT r.*, u.username as uploader_name 
    FROM Resources r 
    LEFT JOIN users u ON r.User_id = u.id 
    WHERE r.approval_status = 'pending' 
    ORDER BY r.Upload_date DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NoteStation</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            padding: 15px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 8px 20px !important;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.2);
            color: white !important;
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 50px 50px;
        }
        
        .page-header h1 {
            font-weight: 700;
            font-size: 2.5rem;
        }
        
        /* Stats Cards */
        .stats-container {
            margin-top: -30px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 48px;
            opacity: 0.2;
            color: #667eea;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Pending Approvals Card */
        .pending-card {
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
            color: #333;
        }
        
        .pending-badge {
            background: #ffc107;
            color: #333;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Dashboard Tabs */
        .dashboard-tabs {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            display:none;
        }
        
        .nav-pills .nav-link {
            color: #666;
            border-radius: 10px;
            padding: 12px 20px;
            margin: 0 5px;
        }
        
        .nav-pills .nav-link.active {
            background: var(--primary-gradient);
            color: white;
        }
        
        .nav-pills .nav-link i {
            margin-right: 8px;
        }
        
        /* Tables */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        
        .table-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .table-title i {
            color: #667eea;
            margin-right: 10px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-top: none;
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 15px;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .badge-role {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: #dc3545;
            color: white;
        }
        
        .badge-user {
            background: #28a745;
            color: white;
        }
        
        .action-btn {
            padding: 5px 15px;
            border-radius: 5px;
            border: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin: 0 3px;
        }
        
        .action-btn.approve {
            background: #28a745;
            color: white;
        }
        
        .action-btn.approve:hover {
            background: #218838;
            transform: scale(1.05);
        }
        
        .action-btn.reject {
            background: #ffc107;
            color: #333;
        }
        
        .action-btn.reject:hover {
            background: #e0a800;
            transform: scale(1.05);
        }
        
        .action-btn.delete {
            background: #dc3545;
            color: white;
        }
        
        .action-btn.delete:hover {
            background: #c82333;
            transform: scale(1.05);
        }
        
        .action-btn.view {
            background: #17a2b8;
            color: white;
        }
        
        .action-btn.view:hover {
            background: #138496;
            transform: scale(1.05);
        }
        
        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Report Cards */
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            height: 100%;
        }
        
        .report-card h4 {
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }
        
        .report-list {
            list-style: none;
            padding: 0;
        }
        
        .report-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .report-list li:last-child {
            border-bottom: none;
        }
        
        .report-list .rank {
            width: 30px;
            height: 30px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            margin-right: 10px;
        }
        
        /* Alert Messages */
        .alert-custom {
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
            box-shadow: var(--card-shadow);
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .quick-action-btn {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            background: white;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: var(--card-shadow);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            color: #667eea;
        }
        
        .quick-action-btn i {
            font-size: 16px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .nav-pills .nav-link {
                margin: 5px 0;
            }
        }

        .dashboard-tabs .nav-pills {
            display: none;
        }

        /* Optional: Add some spacing or alternative navigation */
        .tab-content {
            margin-top: 20px;
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
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="homepage.php">
                <span style="font-size: 1.5em;">📖</span>NoteStation
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="homepage.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user-shield"></i> Admin: <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt me-3"></i>Admin Dashboard</h1>
            <p>Manage users, approve resources, and view system reports</p>
        </div>
    </div>

    <div class="container">
        <!-- Alert Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-custom alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-custom alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-value"><?php echo $stats['users']; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-value"><?php echo $stats['resources']; ?></div>
                        <div class="stat-label">Total Resources</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card pending-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending Approval</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-star"></i></div>
                        <div class="stat-value"><?php echo $stats['ratings']; ?></div>
                        <div class="stat-label">Total Ratings</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions / Table Navigation -->
        <div class="quick-actions">
            <a href="#users" class="quick-action-btn" onclick="document.getElementById('users-tab').click(); return false;">
                <i class="fas fa-users"></i> Users Table
            </a>
            <a href="#resources" class="quick-action-btn" onclick="document.getElementById('resources-tab').click(); return false;">
                <i class="fas fa-file-alt"></i> Resources Table
            </a>
            <a href="#ratings" class="quick-action-btn" onclick="document.getElementById('ratings-tab').click(); return false;">
                <i class="fas fa-star"></i> Ratings Table
            </a>
            <a href="#reports" class="quick-action-btn" onclick="document.getElementById('reports-tab').click(); return false;">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="#pending" class="quick-action-btn" style="background: #fff3cd; color: #856404;" onclick="document.getElementById('resources-tab').click(); filterPending(); return false;">
                <i class="fas fa-clock"></i> View Pending (<?php echo $stats['pending']; ?>)
            </a>
        </div>

        <!-- Pending Approvals Preview (if any) -->
        <?php if ($stats['pending'] > 0): ?>
        <div class="alert alert-warning alert-custom">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong><?php echo $stats['pending']; ?> resource(s) pending approval.</strong> 
            Please review them in the Resources tab.
            <button class="btn btn-sm btn-warning ms-3" onclick="document.getElementById('resources-tab').click(); filterPending();">
                View Pending
            </button>
        </div>
        <?php endif; ?>

        <!-- Dashboard Tabs -->
        <div class="dashboard-tabs">
            <ul class="nav nav-pills" id="dashboardTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="users-tab" data-bs-toggle="pill" data-bs-target="#users" type="button">
                        <i class="fas fa-users"></i> Users
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="resources-tab" data-bs-toggle="pill" data-bs-target="#resources" type="button">
                        <i class="fas fa-file-alt"></i> Resources
                        <?php if ($stats['pending'] > 0): ?>
                            <span class="badge bg-warning ms-2"><?php echo $stats['pending']; ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ratings-tab" data-bs-toggle="pill" data-bs-target="#ratings" type="button">
                        <i class="fas fa-star"></i> Ratings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reports-tab" data-bs-toggle="pill" data-bs-target="#reports" type="button">
                        <i class="fas fa-chart-bar"></i> Reports
                    </button>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Users Tab -->
            <div class="tab-pane fade show active" id="users">
                <div class="table-container">
                    <h4 class="table-title"><i class="fas fa-users"></i> User Management</h4>
                    <div class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <!-- <th>Registered</th> -->
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $users_data->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <!-- <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td> -->
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user? All their resources and ratings will also be deleted.');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="action-btn delete">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Resources Tab (with Approval) -->
            <div class="tab-pane fade" id="resources">
                <div class="table-container">
                    <h4 class="table-title"><i class="fas fa-file-alt"></i> Resource Management & Approval</h4>
                    
                    <!-- Filter buttons -->
                    <div class="mb-3">
                        <button class="btn btn-sm btn-outline-secondary filter-status-btn active" data-status="all">All</button>
                        <button class="btn btn-sm btn-warning filter-status-btn" data-status="pending">Pending</button>
                        <button class="btn btn-sm btn-success filter-status-btn" data-status="approved">Approved</button>
                        <button class="btn btn-sm btn-danger filter-status-btn" data-status="rejected">Rejected</button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="resourcesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Course</th>
                                    <th>Subject</th>
                                    <th>Uploader</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($resource = $resources_data->fetch_assoc()): 
                                    $status_class = '';
                                    $status_text = '';
                                    switch($resource['approval_status']) {
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            $status_text = 'Pending';
                                            break;
                                        case 'approved':
                                            $status_class = 'status-approved';
                                            $status_text = 'Approved';
                                            break;
                                        case 'rejected':
                                            $status_class = 'status-rejected';
                                            $status_text = 'Rejected';
                                            break;
                                        default:
                                            $status_class = 'status-pending';
                                            $status_text = 'Pending';
                                    }
                                ?>
                                <tr data-status="<?php echo $resource['approval_status']; ?>">
                                    <td><?php echo $resource['Resource_id']; ?></td>
                                    <td><?php echo htmlspecialchars($resource['Title']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $resource['Resource_type'] == 'notes' ? 'success' : 'danger'; ?>">
                                            <?php echo $resource['Resource_type'] == 'notes' ? 'Notes' : 'Q.Paper'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($resource['Course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($resource['Subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($resource['uploader_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo date('d M Y', strtotime($resource['Upload_date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_resource.php?id=<?php echo $resource['Resource_id']; ?>" target="_blank" class="action-btn view">
                                            <i class="fas fa-eye"></i> View
                                        </a><br/><br/>
                                        
                                        <?php if ($resource['approval_status'] != 'approved'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Approve this resource?');">
                                            <input type="hidden" name="resource_id" value="<?php echo $resource['Resource_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="action-btn approve">
                                                <i class="fas fa-check"></i> Approve
                                            </button><br/><br/>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($resource['approval_status'] != 'rejected'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this resource?');">
                                            <input type="hidden" name="resource_id" value="<?php echo $resource['Resource_id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="action-btn reject">
                                                <i class="fas fa-times"></i> Reject
                                            </button><br/><br/>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this resource? This will also delete all associated ratings and the file.');">
                                            <input type="hidden" name="resource_id" value="<?php echo $resource['Resource_id']; ?>">
                                            <button type="submit" name="delete_resource" class="action-btn delete">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Ratings Tab (View Only) -->
            <div class="tab-pane fade" id="ratings">
                <div class="table-container">
                    <h4 class="table-title"><i class="fas fa-star"></i> Ratings (View Only)</h4>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Ratings are view-only. No modifications allowed.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="ratingsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Resource</th>
                                    <th>Rating</th>
                                    <th>Review</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($rating = $ratings_data->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $rating['rating_id']; ?></td>
                                    <td><?php echo htmlspecialchars($rating['user_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($rating['resource_title'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="color: <?php echo $i <= $rating['rating'] ? '#ffc107' : '#e0e0e0'; ?>"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($rating['review'] ?? ''); ?></td>
                                    <td><?php echo date('d M Y', strtotime($rating['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Reports Tab (removed bar graph) -->
            <div class="tab-pane fade" id="reports">
                <div class="row">
                    <!-- Top Contributors -->
                    <div class="col-md-6 mb-4">
                        <div class="report-card">
                            <h4><i class="fas fa-trophy"></i> Top Contributors</h4>
                            <ul class="report-list">
                                <?php 
                                $rank = 1;
                                while($contributor = $top_contributors->fetch_assoc()): 
                                ?>
                                <li>
                                    <span>
                                        <span class="rank"><?php echo $rank++; ?></span>
                                        <?php echo htmlspecialchars($contributor['full_name']); ?> 
                                        <small>(@<?php echo htmlspecialchars($contributor['username']); ?>)</small>
                                    </span>
                                    <span class="badge bg-primary"><?php echo $contributor['upload_count']; ?> uploads</span>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Most Rated Resources -->
                    <div class="col-md-6 mb-4">
                        <div class="report-card">
                            <h4><i class="fas fa-star"></i> Most Rated Resources</h4>
                            <ul class="report-list">
                                <?php while($rated = $most_rated->fetch_assoc()): ?>
                                <li>
                                    <div>
                                        <strong><?php echo htmlspecialchars($rated['Title']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($rated['Course_name']); ?> - <?php echo htmlspecialchars($rated['Subject_name']); ?></small>
                                    </div>
                                    <div>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-star"></i> <?php echo number_format($rated['avg_rating'], 1); ?>
                                        </span>
                                        <span class="badge bg-info"><?php echo $rated['rating_count']; ?> ratings</span>
                                    </div>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Pending Approvals Report -->
                    <div class="col-md-12 mb-4">
                        <div class="report-card">
                            <h4><i class="fas fa-clock"></i> Pending Approvals</h4>
                            <?php if ($pending_approvals->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Uploader</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($pending = $pending_approvals->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pending['Title']); ?></td>
                                            <td><?php echo $pending['Resource_type'] == 'notes' ? 'Notes' : 'Q.Paper'; ?></td>
                                            <td><?php echo htmlspecialchars($pending['uploader_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($pending['Upload_date'])); ?></td>
                                            <td>
                                                <a href="view_resource.php?id=<?php echo $pending['Resource_id']; ?>" target="_blank" class="btn btn-sm btn-info">View</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">No pending approvals.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Summary Report -->
                    <div class="col-md-12">
                        <div class="report-card">
                            <h4><i class="fas fa-file-alt"></i> System Summary Report</h4>
                            <div class="row">
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <h3><?php echo $stats['users']; ?></h3>
                                        <p class="text-muted">Total Users</p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <h3><?php echo $stats['resources']; ?></h3>
                                        <p class="text-muted">Total Resources</p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <h3><?php echo $stats['pending']; ?></h3>
                                        <p class="text-muted">Pending Approval</p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <h3><?php echo $stats['ratings']; ?></h3>
                                        <p class="text-muted">Total Ratings</p>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <?php
                            // Get approval status distribution
                            $approval_stats = $conn->query("
                                SELECT 
                                    SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending,
                                    SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved,
                                    SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected
                                FROM Resources
                            ")->fetch_assoc();
                            ?>
                            
                            <h5 class="mt-3">Approval Status Distribution</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="progress mb-2" style="height: 25px;">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?php echo ($approval_stats['pending'] / $stats['resources']) * 100; ?>%">
                                            Pending: <?php echo $approval_stats['pending']; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="progress mb-2" style="height: 25px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo ($approval_stats['approved'] / $stats['resources']) * 100; ?>%">
                                            Approved: <?php echo $approval_stats['approved']; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="progress mb-2" style="height: 25px;">
                                        <div class="progress-bar bg-danger" role="progressbar" 
                                             style="width: <?php echo ($approval_stats['rejected'] / $stats['resources']) * 100; ?>%">
                                            Rejected: <?php echo $approval_stats['rejected']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <?php
                            // Get file type distribution
                            $file_types = $conn->query("
                                SELECT File_type, COUNT(*) as count 
                                FROM Resources 
                                GROUP BY File_type
                            ");
                            ?>
                            
                            <h5 class="mt-3">File Type Distribution</h5>
                            <div class="row">
                                <?php while($type = $file_types->fetch_assoc()): ?>
                                <div class="col-md-4">
                                    <div class="progress mb-2" style="height: 25px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?php echo ($type['count'] / $stats['resources']) * 100; ?>%">
                                            <?php echo strtoupper($type['File_type']); ?>: <?php echo $type['count']; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <hr>
                            
                            <?php
                            // Get active users (users who uploaded in last 30 days)
                            $active_users = $conn->query("
                                SELECT COUNT(DISTINCT User_id) as count 
                                FROM Resources 
                                WHERE Upload_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                            ")->fetch_assoc()['count'];
                            ?>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <p><strong>Active Users (Last 30 days):</strong> <?php echo $active_users; ?></p>
                                    <p><strong>Resources with Ratings:</strong> <?php echo $conn->query("SELECT COUNT(DISTINCT resource_id) FROM Ratings")->fetch_row()[0]; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Most Popular Course:</strong> 
                                        <?php 
                                        $popular_course = $conn->query("
                                            SELECT Course_name, COUNT(*) as count 
                                            FROM Resources 
                                            GROUP BY Course_name 
                                            ORDER BY count DESC 
                                            LIMIT 1
                                        ")->fetch_assoc();
                                        echo htmlspecialchars($popular_course['Course_name']) . ' (' . $popular_course['count'] . ' resources)';
                                        ?>
                                    </p>
                                    <p><strong>Most Popular Subject:</strong> 
                                        <?php 
                                        $popular_subject = $conn->query("
                                            SELECT Subject_name, COUNT(*) as count 
                                            FROM Resources 
                                            GROUP BY Subject_name 
                                            ORDER BY count DESC 
                                            LIMIT 1
                                        ")->fetch_assoc();
                                        echo htmlspecialchars($popular_subject['Subject_name']) . ' (' . $popular_subject['count'] . ' resources)';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTables
        $('#usersTable').DataTable({
            pageLength: 10,
            order: [[0, 'desc']]
        });
        
        $('#resourcesTable').DataTable({
            pageLength: 10,
            order: [[0, 'desc']]
        });
        
        $('#ratingsTable').DataTable({
            pageLength: 10,
            order: [[0, 'desc']]
        });
        
        // Filter functionality for resources table
        $('.filter-status-btn').click(function() {
            $('.filter-status-btn').removeClass('active');
            $(this).addClass('active');
            
            const status = $(this).data('status');
            const table = $('#resourcesTable').DataTable();
            
            if (status === 'all') {
                table.column(7).search('').draw(); // Clear filter
            } else {
                // Use regex to match exactly the status text
                let searchValue;
                if (status === 'pending') searchValue = 'Pending';
                else if (status === 'approved') searchValue = 'Approved';
                else if (status === 'rejected') searchValue = 'Rejected';
                
                table.column(7).search(searchValue, true, false).draw();
            }
        });
    });

    // Function to filter pending resources
    function filterPending() {
        // Wait a bit for tab to be visible
        setTimeout(function() {
            $('.filter-status-btn[data-status="pending"]').click();
        }, 200);
    }
    </script>
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>NoteStation</h4>
                    <p>Your one-stop destination for academic resources. Share notes, question papers, and study materials with fellow students.</p>
                    <!-- <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div> -->
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
                    <!-- <div class="newsletter">
                        <p>Subscribe for updates</p>
                        <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Newsletter feature coming soon!');">
                            <input type="email" placeholder="Your email" class="newsletter-input">
                            <button type="submit" class="newsletter-btn"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div> -->
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
<?php $conn->close(); ?>