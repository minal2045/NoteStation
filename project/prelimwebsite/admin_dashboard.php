<?php
require_once 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_signup.php");
    exit();
}

// --- Helper Functions ---
function redirectWithState($location, $activeTab = null, $resourceFilter = null) {
    if ($activeTab === null && isset($_SESSION['last_admin_tab'])) {
        $activeTab = $_SESSION['last_admin_tab'];
    }
    if ($resourceFilter === null && isset($_SESSION['last_resource_filter'])) {
        $resourceFilter = $_SESSION['last_resource_filter'];
    }
    
    $url = $location;
    $params = [];
    if ($activeTab) {
        $params['tab'] = $activeTab;
    }
    if ($resourceFilter) {
        $params['filter'] = $resourceFilter;
    }
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    header("Location: " . $url);
    exit();
}

// --- Get current state from URL (after redirect) ---
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : (isset($_SESSION['last_admin_tab']) ? $_SESSION['last_admin_tab'] : 'users');
$current_filter = isset($_GET['filter']) ? $_GET['filter'] : (isset($_SESSION['last_resource_filter']) ? $_SESSION['last_resource_filter'] : 'all');

// Save to session for next time (if not already set by redirect)
$_SESSION['last_admin_tab'] = $current_tab;
$_SESSION['last_resource_filter'] = $current_filter;

// --- Handle POST Requests (Redirect to avoid resubmission) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store the current state before processing
    $redirect_tab = $current_tab;
    $redirect_filter = $current_filter;
    $success_message = null;
    $error_message = null;
    
    // Handle user deletion
    if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->bind_param("i", $user_id);
        if ($delete_stmt->execute()) {
            $_SESSION['admin_success'] = "User deleted successfully";
        } else {
            $_SESSION['admin_error'] = "Failed to delete user";
        }
        $redirect_tab = 'users';
    }
    
    // Handle resource approval/rejection
    elseif (isset($_POST['action']) && isset($_POST['resource_id'])) {
        $resource_id = (int)$_POST['resource_id'];
        $action = $_POST['action'];
        
        if ($action === 'approve' || $action === 'reject') {
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            $update_stmt = $conn->prepare("UPDATE Resources SET approval_status = ? WHERE Resource_id = ?");
            $update_stmt->bind_param("si", $status, $resource_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['admin_success'] = "Resource " . ($action === 'approve' ? 'approved' : 'rejected'."!");
            } else {
                $_SESSION['admin_error'] = "Failed to update resource status";
            }
        }
        $redirect_tab = 'resources';
    }
    
    // Handle resource deletion
    elseif (isset($_POST['delete_resource']) && isset($_POST['resource_id'])) {
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
                $_SESSION['admin_success'] = "Resource deleted successfully";
            } else {
                $_SESSION['admin_error'] = "Failed to delete resource";
            }
        }
        $redirect_tab = 'resources';
    }
    
    // Redirect to the same page with state to prevent form resubmission
    redirectWithState('admin_dashboard.php', $redirect_tab, $redirect_filter);
}

// --- Fetch Fresh Data (after redirect) ---
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

// Get report data
$report_type = isset($_GET['report']) ? $_GET['report'] : 'summary';

// Monthly uploads report
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

// --- Get Session Messages and Clear them ---
$success_message = isset($_SESSION['admin_success']) ? $_SESSION['admin_success'] : null;
$error_message = isset($_SESSION['admin_error']) ? $_SESSION['admin_error'] : null;
unset($_SESSION['admin_success']);
unset($_SESSION['admin_error']);
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
            --primary-gradient: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --color-pending:        #F0A500;
            --color-pending-bg:     #FFF4E0;
            --color-pending-text:   #a86d00;
            --color-pending-border: #F0A500;
            --color-approved:        #2ECC8F;
            --color-approved-bg:     #D6F5EC;
            --color-approved-text:   #1a7a55;
            --color-approved-border: #2ECC8F;
            --color-rejected:        #E05C7A;
            --color-rejected-bg:     #FCE4EC;
            --color-rejected-text:   #a0324f;
            --color-rejected-border: #E05C7A;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Popup messages */
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
            min-width: 250px;
            text-align: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .message.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        @keyframes slideDown {
            from { top: -50px; opacity: 0; }
            to   { top: 20px;  opacity: 1; }
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
            color: #654D87;
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
            background: var(--color-pending);
            color: white;
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
            display: none;
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
            color: #654D87;
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
        
        /* ── Status Badges ── */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            border: 1px solid transparent;
        }

        .status-pending {
            background: var(--color-pending-bg);
            color: var(--color-pending-text);
            border-color: var(--color-pending-border);
        }

        .status-approved {
            background: var(--color-approved-bg);
            color: var(--color-approved-text);
            border-color: var(--color-approved-border);
        }

        .status-rejected {
            background: var(--color-rejected-bg);
            color: var(--color-rejected-text);
            border-color: var(--color-rejected-border);
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
        
        /* ── Action Buttons ── */
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
            background: var(--color-approved);
            color: white;
        }

        .action-btn.approve:hover {
            background: #25b07a;
            transform: scale(1.05);
        }

        .action-btn.reject {
            background: #e8747f;
            color: white;
        }

        .action-btn.reject:hover {
            background: #d45d68;
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

        /* Download button style */
        .action-btn.download {
            background: #17a2b8;
            color: white;
        }
        
        .action-btn.download:hover {
            background: #138496;
            transform: scale(1.05);
            color: white;
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
        
        /* ── Filter Status Buttons ── */
        .filter-status-btn {
            border: none;
            font-weight: 500;
            transition: opacity 0.2s, transform 0.2s;
        }

        .filter-status-btn:hover {
            opacity: 0.85;
            transform: scale(1.03);
        }

        .filter-status-btn[data-status="pending"] {
            background: var(--color-pending);
            color: white;
        }

        .filter-status-btn[data-status="approved"] {
            background: var(--color-approved);
            color: white;
        }

        .filter-status-btn[data-status="rejected"] {
            background: var(--color-rejected);
            color: white;
        }

        .filter-status-btn[data-status="all"] {
            background: #6c757d;
            color: white;
        }

        .filter-status-btn.active {
            box-shadow: 0 0 0 3px rgba(0,0,0,0.15);
        }

        /* ── View Pending alert button ── */
        .btn-view-pending {
            background: var(--color-pending);
            color: white;
            border: none;
            padding: 5px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-view-pending:hover {
            opacity: 0.85;
            color: white;
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
            border-left: 4px solid #654D87;
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
            color: #654D87;
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
            color: #654D87;
        }
        
        .quick-action-btn i {
            font-size: 16px;
        }

        .quick-action-btn.pending-action {
            background: var(--color-pending-bg);
            color: var(--color-pending-text);
        }

        .quick-action-btn.pending-action:hover {
            color: var(--color-pending-text);
        }
        
        /* Progress bar */
        .progress-wrapper {
            position: relative;
            width: 100%;
        }
        
        .progress {
            background-color: #e9ecef;
            height: 25px;
            margin: 0;
        }
        
        .progress-bar {
            line-height: 25px;
            z-index: 1;
        }
        
        .progress-text {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            text-align: center;
            color: #000000;
            font-weight: 400;
            line-height: 25px;
            z-index: 2;
            pointer-events: none;
            font-size:15px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 { font-size: 2rem; }
            .stat-value { font-size: 2rem; }
            .nav-pills .nav-link { margin: 5px 0; }
        }

        .dashboard-tabs .nav-pills { display: none; }

        .tab-content { margin-top: 20px; }

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

        .footer-links li { margin-bottom: 12px; }

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

        .footer-links a:hover { color: #fff; transform: translateX(5px); }
        .footer-links a:hover i { transform: translateX(3px); }

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

        .footer-contact li i { width: 20px; color: #667eea; }

        .footer-bottom {
            padding-top: 30px;
            padding-left: 450px;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .footer-bottom p {
            color: rgba(255,255,255,0.6);
            margin: 0;
            font-size: 0.9rem;
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .footer-bottom .fa-heart { animation: heartbeat 1.5s ease infinite; }

        @media (max-width: 768px) {
            .footer { padding: 40px 0 20px; margin-top: 50px; }
            .footer-content { grid-template-columns: 1fr; gap: 30px; }
            .footer-section h4 { font-size: 1.2rem; margin-bottom: 20px; }
            .footer-section h4::after { width: 40px; }
            .footer-bottom { padding-left: 0; }
        }
        /* Download button report*/
        .download-btn-hover {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .download-btn-hover:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="homepage.php">
                <i class="fas fa-book-open"></i> NoteStation
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
                            ⌨ Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            Admin
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Popup messages -->
    <?php if ($success_message): ?>
        <div class="message success" id="flashMessage"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error" id="flashMessage"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>Admin Dashboard</h1>
            <p>Manage users, approve resources, and view system reports</p>
        </div>
    </div>

    <div class="container">

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
            <a href="?tab=users" class="quick-action-btn <?php echo $current_tab == 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Users Table
            </a>
            <a href="?tab=resources&filter=<?php echo urlencode($current_filter); ?>" class="quick-action-btn <?php echo $current_tab == 'resources' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> Resources Table
            </a>
            <a href="?tab=ratings" class="quick-action-btn <?php echo $current_tab == 'ratings' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Ratings Table
            </a>
            <a href="?tab=reports" class="quick-action-btn <?php echo $current_tab == 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="?tab=resources&filter=pending" class="quick-action-btn" style="background: #fff3cd; color: #856404;">
                <i class="fas fa-clock"></i> View Pending (<?php echo $stats['pending']; ?>)
            </a>
        </div>

        <!-- Pending approvals inline notice -->
        <?php if ($stats['pending'] > 0): ?>
        <div class="message warning" style="
            position: static;
            transform: none;
            left: auto;
            top: auto;
            animation: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: unset;
            width: 100%;
            margin-bottom: 20px;
            border-radius: 8px;
        ">
            <span>
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong><?php echo $stats['pending']; ?> resource(s) pending approval.</strong>
                Please review them in the Resources tab.
            </span>
            <a href="?tab=resources&filter=pending" class="btn-view-pending ms-3" style="text-decoration: none;">
                View Pending
            </a>
        </div>
        <?php endif; ?>

        <!-- Dashboard Tabs -->
        <div class="dashboard-tabs">
            <ul class="nav nav-pills" id="dashboardTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $current_tab == 'users' ? 'active' : ''; ?>" id="users-tab" data-bs-toggle="pill" data-bs-target="#users" type="button">
                        <i class="fas fa-users"></i> Users
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $current_tab == 'resources' ? 'active' : ''; ?>" id="resources-tab" data-bs-toggle="pill" data-bs-target="#resources" type="button">
                        <i class="fas fa-file-alt"></i> Resources
                        <?php if ($stats['pending'] > 0): ?>
                            <span class="badge bg-warning ms-2"><?php echo $stats['pending']; ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $current_tab == 'ratings' ? 'active' : ''; ?>" id="ratings-tab" data-bs-toggle="pill" data-bs-target="#ratings" type="button">
                        <i class="fas fa-star"></i> Ratings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $current_tab == 'reports' ? 'active' : ''; ?>" id="reports-tab" data-bs-toggle="pill" data-bs-target="#reports" type="button">
                        <i class="fas fa-chart-bar"></i> Reports
                    </button>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Users Tab -->
            <div class="tab-pane fade <?php echo $current_tab == 'users' ? 'show active' : ''; ?>" id="users">
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
            <div class="tab-pane fade <?php echo $current_tab == 'resources' ? 'show active' : ''; ?>" id="resources">
                <div class="table-container">
                    <h4 class="table-title"><i class="fas fa-file-alt"></i> Resource Management & Approval</h4>
                    
                    <!-- Filter buttons -->
                    <div class="mb-3">
                        <a href="?tab=resources&filter=all" class="btn btn-sm filter-status-btn <?php echo $current_filter == 'all' ? 'active' : ''; ?>" data-status="all">All</a>
                        <a href="?tab=resources&filter=pending" class="btn btn-sm filter-status-btn <?php echo $current_filter == 'pending' ? 'active' : ''; ?>" data-status="pending">Pending</a>
                        <a href="?tab=resources&filter=approved" class="btn btn-sm filter-status-btn <?php echo $current_filter == 'approved' ? 'active' : ''; ?>" data-status="approved">Approved</a>
                        <a href="?tab=resources&filter=rejected" class="btn btn-sm filter-status-btn <?php echo $current_filter == 'rejected' ? 'active' : ''; ?>" data-status="rejected">Rejected</a>
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
                                <?php 
                                // Apply filter if needed
                                $filtered_resources = [];
                                while($resource = $resources_data->fetch_assoc()) {
                                    if ($current_filter == 'all' || $resource['approval_status'] == $current_filter) {
                                        $filtered_resources[] = $resource;
                                    }
                                }
                                foreach($filtered_resources as $resource): 
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
                                    <?php 
                                    // Check if file type is docx or ppt to show download instead of view
                                    $file_type = strtolower($resource['File_type']);
                                    $is_unviewable = ($file_type == 'docx' || $file_type == 'ppt');
                                    ?>
                                    
                                    <?php if ($is_unviewable): ?>
                                        <a href="download_resource.php?id=<?php echo $resource['Resource_id']; ?>" class="action-btn download" style="background: #17a2b8; color: white; text-decoration: none; display: inline-block; padding: 5px 15px; border-radius: 5px; margin: 0 3px;">
                                             Download
                                        </a><br/><br/>
                                    <?php else: ?>
                                        <a href="view_resource.php?id=<?php echo $resource['Resource_id']; ?>" target="_blank" class="action-btn download" style="background: #17a2b8; color: white; text-decoration: none; display: inline-block; padding: 5px 15px; border-radius: 5px; margin: 0 3px;">
                                            <i class="fas fa-eye"></i> View
                                        </a><br/><br/>
                                    <?php endif; ?>
                                    
                                    <?php if ($resource['approval_status'] != 'approved'): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Approve this resource?');">
                                        <input type="hidden" name="resource_id" value="<?php echo $resource['Resource_id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="action-btn approve">
                                            ✔Approve
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
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Ratings Tab (View Only) -->
            <div class="tab-pane fade <?php echo $current_tab == 'ratings' ? 'show active' : ''; ?>" id="ratings">
                <div class="table-container">
                    <h4 class="table-title"><i class="fas fa-star"></i> Ratings (View Only)</h4>

                    <!-- Ratings info notice -->
                    <div class="message info" style="
                        position: static;
                        transform: none;
                        left: auto;
                        top: auto;
                        animation: none;
                        min-width: unset;
                        width: 100%;
                        margin-bottom: 16px;
                        border-radius: 8px;
                        text-align: left;
                    ">
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

            <!-- Reports Tab -->
            <div class="tab-pane fade <?php echo $current_tab == 'reports' ? 'show active' : ''; ?>" id="reports">
                <div class="row">
                    <!-- Top Contributors -->
                    <div class="col-md-6 mb-4">
                        <div class="report-card">
                            <h4><i class="fas fa-trophy"></i> Top Contributors</h4>
                            <ul class="report-list">
                                <?php 
                                $rank = 1;
                                // Reset pointer for top_contributors
                                $top_contributors->data_seek(0);
                                while($contributor = $top_contributors->fetch_assoc()): 
                                ?>
                                <li>
                                    <span>
                                        <span class="rank"><?php echo $rank++; ?></span>
                                        <?php echo htmlspecialchars($contributor['full_name']); ?> 
                                        <small>(<?php echo htmlspecialchars($contributor['username']); ?>)</small>
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
                                <?php 
                                // Reset pointer for most_rated
                                $most_rated->data_seek(0);
                                while($rated = $most_rated->fetch_assoc()): 
                                ?>
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
                                        <?php 
                                        // Reset pointer for pending_approvals
                                        $pending_approvals->data_seek(0);
                                        while($pending = $pending_approvals->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pending['Title']); ?></td>
                                            <td><?php echo $pending['Resource_type'] == 'notes' ? 'Notes' : 'Q.Paper'; ?></td>
                                            <td><?php echo htmlspecialchars($pending['uploader_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($pending['Upload_date'])); ?></td>
                                            <td>
                                                <a href="?tab=resources&filter=pending" class="btn btn-sm btn-info">View</a>
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
                                <div class="col-md-4 mb-2">
                                    <div class="progress-wrapper">
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                 style="width: <?php echo $stats['resources'] > 0 ? ($approval_stats['pending'] / $stats['resources']) * 100 : 0; ?>%; height: 30px;">
                                            </div>
                                        </div>
                                        <div class="progress-text">Pending: <?php echo $approval_stats['pending']; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="progress-wrapper">
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $stats['resources'] > 0 ? ($approval_stats['approved'] / $stats['resources']) * 100 : 0; ?>%; height: 30px;">
                                            </div>
                                        </div>
                                        <div class="progress-text">Approved: <?php echo $approval_stats['approved']; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="progress-wrapper">
                                        <div class="progress">
                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                 style="width: <?php echo $stats['resources'] > 0 ? ($approval_stats['rejected'] / $stats['resources']) * 100 : 0; ?>%; height: 30px;">
                                            </div>
                                        </div>
                                        <div class="progress-text">Rejected: <?php echo $approval_stats['rejected']; ?></div>
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
                                <div class="col-md-4 mb-2">
                                    <div class="progress-wrapper">
                                        <div class="progress">
                                            <div class="progress-bar bg-info" role="progressbar" 
                                                 style="width: <?php echo $stats['resources'] > 0 ? ($type['count'] / $stats['resources']) * 100 : 0; ?>%; height: 30px;">
                                            </div>
                                        </div>
                                        <div class="progress-text"><?php echo strtoupper($type['File_type']); ?>: <?php echo $type['count']; ?></div>
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
        <!-- Download Section -->
        <div style="background: #f8f9fa; border-radius: 10px; padding: 15px 20px; margin: 20px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <span>
                <i class="fas fa-chart-line" style="color: #654D87;"></i>
                <strong style="margin-left: 8px;">Download Complete Admin Report:</strong>
                <span style="color: #666; font-size: 13px; margin-left: 10px;">All data in one file</span>
            </span>
            <div style="display: flex; gap: 10px;">
                <a href="download_full_report.php?format=csv" class="download-btn-hover" style="background: #28a745; color: white; padding: 6px 15px; border-radius: 5px; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
                <a href="download_full_report.php?format=excel" class="download-btn-hover" style="background: #1e7e34; color: white; padding: 6px 15px; border-radius: 5px; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="download_full_report.php?format=pdf" class="download-btn-hover" style="background: #dc3545; color: white; padding: 6px 15px; border-radius: 5px; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
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
        
        // Auto-hide flash message after 3 seconds
        var flash = document.getElementById('flashMessage');
        if (flash) {
            setTimeout(function() {
                flash.style.transition = 'opacity 0.5s';
                flash.style.opacity = '0';
                setTimeout(function() { flash.remove(); }, 500);
            }, 3000);
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
                            <li><a href="#"><i class="fas fa-chevron-right"></i> Upload Resource</a></li>
                            <li><a href="#"><i class="fas fa-chevron-right"></i> My Uploads</a></li>
                        <?php else: ?>
                            <li><a href="#"><i class="fas fa-chevron-right"></i> Login / Sign Up</a></li>
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
<?php $conn->close(); ?>
