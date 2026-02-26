<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login_signup.php');
    exit();
}

$current_user_id = $_SESSION['user_id']; 
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Get user's full name for navbar
$user_full_name = "User";
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

// Get current user's uploads
$sql = "SELECT * FROM Resources WHERE User_id = ? ORDER BY Upload_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Get upload statistics
$stats_sql = "SELECT 
                COUNT(*) as total_uploads,
                SUM(CASE WHEN Resource_type = 'notes' THEN 1 ELSE 0 END) as total_notes,
                SUM(CASE WHEN Resource_type = 'question_paper' THEN 1 ELSE 0 END) as total_question_papers
              FROM Resources WHERE User_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Uploads - NoteStation</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Lightbox for file preview -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-top: 0; /* Removed padding-top to eliminate gap */
        }

        /* Navbar - Solid gradient matching homepage and upload page */
        .navbar {
            position: relative; /* Changed from fixed to relative */
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
            font-weight: 600;
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

        .badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
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

        .navbar-toggler:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Header Section - Now directly below navbar */
        .page-header {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 50px 50px;
            box-shadow: var(--card-shadow);
        }
        
        .page-header h1 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 5px;
            font-family: 'Playfair Display', serif;
        }
        
        .page-header p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        /* Stats Cards */
        .stats-container {
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            line-height: 50px;
            border-radius: 12px;
            margin: 0 auto 10px;
            font-size: 20px;
            color: white;
        }
        
        .stat-icon.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.notes { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.papers { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
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
        
        .filter-btns {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            flex: 1;
        }
        
        .filter-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
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
        
        /* Grid View */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            padding: 20px;
            color: white;
            position: relative;
            min-height: 120px;
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
            color: #654D87;
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
            color: #654D87;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn:hover {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: white;
            transform: scale(1.1);
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .status-badge.approved {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-badge.rejected {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        
        .resource-preview {
            text-align: center;
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
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
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
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .meta-item i {
            color: #654D87;
        }
        
        .preview-description {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            color: #666;
            text-align: left;
        }
        
        .preview-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .preview-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .preview-btn.view {
            background: var(--primary-gradient);
            color: white;
        }
        
        .preview-btn.download {
            background: #28a745;
            color: white;
        }
        
        .preview-btn.delete {
            background: #dc3545;
            color: white;
        }
        
        .preview-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
            color: #654D87;
            opacity: 0.5;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .empty-state .btn {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .control-bar {
                flex-direction: column;
            }
            
            .filter-btns {
                width: 100%;
                justify-content: center;
            }
            
            .preview-meta {
                flex-direction: column;
                align-items: center;
            }
            
            .list-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .list-details {
                width: 100%;
            }
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
    <!-- Navbar with Solid Gradient - Now relative positioned with no gap -->
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
                <a class="nav-link" href="upload_form.php">Upload</a>
                <a class="nav-link active" href="my_uploads.php">My Uploads</a>
                
                <?php if ($is_admin): ?>
                    <a class="nav-link" href="admin_dashboard.php">Admin</a>
                <?php endif; ?>
                
                <!-- Bootstrap Dropdown - Using div instead of li to avoid dots -->
                <div class="dropdown d-inline-block">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> 
                        <?php echo htmlspecialchars($user_full_name); ?>
                        <?php if ($is_admin): ?>
                            <span class="badge bg-warning text-dark ms-2">Admin</span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav><br/><br/>

    <!-- Header Section - Now directly below navbar with no gap -->
    <!-- <div class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1><i class="fas fa-cloud-upload-alt me-3"></i>My Uploads</h1>
                    <p>Manage and view all your shared study materials</p>
                </div>
            </div>
        </div>
    </div> -->
    
    <div class="container">
        <!-- Statistics Cards -->
        <?php if ($result->num_rows > 0): ?>
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_uploads']; ?></div>
                        <div class="stat-label">Total Uploads</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon notes">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_notes']; ?></div>
                        <div class="stat-label">Notes</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon papers">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_question_papers']; ?></div>
                        <div class="stat-label">Question Papers</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Control Bar -->
        <div class="control-bar">
            <div class="filter-btns">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="notes">Notes</button>
                <button class="filter-btn" data-filter="question_paper">Question Papers</button>
            </div>
            
            <div class="view-toggle">
                <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
                <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
            </div>
        </div>
        
        <!-- Grid View -->
        <div class="resources-grid" id="gridView">
            <?php 
            $counter = 0;
            while($row = $result->fetch_assoc()): 
                $counter++;
                // Determine file icon
                $fileIcon = 'fa-file';
                if ($row['File_type'] == 'pdf') $fileIcon = 'fa-file-pdf';
                elseif ($row['File_type'] == 'docx') $fileIcon = 'fa-file-word';
                elseif ($row['File_type'] == 'ppt') $fileIcon = 'fa-file-powerpoint';
                
                // Format date
                $uploadDate = date('d M Y', strtotime($row['Upload_date']));
                
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
                    'approval_status' => $row['approval_status']
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
                    <div>
                        <i class="fas <?php echo $fileIcon; ?> file-icon"></i>
                    </div>
                </div>
                <div class="card-body">
                    <?php 
                    $status_class = '';
                    $status_text = '';
                    switch($row['approval_status']) {
                        case 'pending':
                            $status_class = 'pending';
                            $status_text = 'Pending Approval';
                            break;
                        case 'approved':
                            $status_class = 'approved';
                            $status_text = 'Approved';
                            break;
                        case 'rejected':
                            $status_class = 'rejected';
                            $status_text = 'Rejected';
                            break;
                        default:
                            $status_class = 'pending';
                            $status_text = 'Pending';
                    }
                    ?>
                    <div class="status-badge <?php echo $status_class; ?>">
                        <i class="fas fa-<?php echo $status_class == 'approved' ? 'check-circle' : ($status_class == 'pending' ? 'clock' : 'exclamation-circle'); ?> me-1"></i>
                        <?php echo $status_text; ?>
                    </div>
                    
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
                        <button class="action-btn delete-resource" title="Delete" data-id="<?php echo $row['Resource_id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <!-- List View -->
        <div class="resources-list" id="listView" style="display: none;">
            <?php 
            // Reset result pointer
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()): 
                $fileIcon = 'fa-file';
                if ($row['File_type'] == 'pdf') $fileIcon = 'fa-file-pdf';
                elseif ($row['File_type'] == 'docx') $fileIcon = 'fa-file-word';
                elseif ($row['File_type'] == 'ppt') $fileIcon = 'fa-file-powerpoint';
                
                $uploadDate = date('d M Y', strtotime($row['Upload_date']));
            ?>
            <div class="list-item" data-id="<?php echo $row['Resource_id']; ?>" data-type="<?php echo $row['Resource_type']; ?>">
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
                        <span class="status-badge <?php echo $row['approval_status']; ?>">
                            <?php echo ucfirst($row['approval_status']); ?>
                        </span>
                    </div>
                </div>
                <div class="action-buttons">
                    <button class="action-btn view-resource" title="View" data-id="<?php echo $row['Resource_id']; ?>">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn download-resource" title="Download" data-id="<?php echo $row['Resource_id']; ?>">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="action-btn delete-resource" title="Delete" data-id="<?php echo $row['Resource_id']; ?>">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fas fa-cloud-upload-alt"></i>
            <h3>No Uploads Yet</h3>
            <p>Start sharing your study materials with fellow students</p>
            <a href="upload_form.php" class="btn">
                <i class="fas fa-plus me-2"></i>Upload Your First Resource
            </a>
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
                        
                        <div class="preview-actions">
                            <button class="preview-btn view" id="viewFileBtn">
                                <i class="fas fa-eye"></i> View Resource
                            </button>
                            <button class="preview-btn download" id="downloadFileBtn">
                                <i class="fas fa-download"></i> Download
                            </button>
                            <button class="preview-btn delete" id="deleteFileBtn">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Lightbox -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Navbar scroll effect
        $(window).scroll(function() {
            if ($(this).scrollTop() > 50) {
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
        
        // Filter functionality
        $('.filter-btn').click(function() {
            $('.filter-btn').removeClass('active');
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
        
        // Function to load resource details from embedded data
        function loadResourceDetails(resourceId) {
            // Find the card with this ID and get its embedded data
            const card = $(`.resource-card[data-id="${resourceId}"]`);
            
            if (card.length) {
                // Get the embedded JSON data
                const resourceData = card.data('resource');
                
                if (resourceData) {
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
                    
                    // Set icon based on file type
                    let iconClass = 'fa-file';
                    if (resourceData.file_type === 'pdf') iconClass = 'fa-file-pdf';
                    else if (resourceData.file_type === 'docx') iconClass = 'fa-file-word';
                    else if (resourceData.file_type === 'ppt') iconClass = 'fa-file-powerpoint';
                    
                    $('#modalIcon i').attr('class', 'fas ' + iconClass);
                    
                    // Store resource ID for actions
                    $('#viewFileBtn').data('id', resourceId);
                    $('#downloadFileBtn').data('id', resourceId);
                    $('#deleteFileBtn').data('id', resourceId);
                    
                    // Hide view button for DOCX and PPT files
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
        
        // Delete resource
        $('#deleteFileBtn').click(function() {
            const resourceId = $(this).data('id');
            
            if (confirm('Are you sure you want to delete this resource? This action cannot be undone.')) {
                $.ajax({
                    url: 'delete_resource.php',
                    type: 'POST',
                    data: { resource_id: resourceId },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                // Remove the card/list item
                                $(`.resource-card[data-id="${resourceId}"]`).fadeOut(300, function() {
                                    $(this).remove();
                                });
                                $(`.list-item[data-id="${resourceId}"]`).fadeOut(300, function() {
                                    $(this).remove();
                                });
                                
                                // Close modal
                                bootstrap.Modal.getInstance(document.getElementById('resourceModal')).hide();
                                
                                // Show success message
                                alert('Resource deleted successfully!');
                                
                                // Reload if no resources left
                                if ($('.resource-card').length === 0) {
                                    location.reload();
                                }
                            } else {
                                alert('Error: ' + result.message);
                            }
                        } catch(e) {
                            alert('Error deleting resource');
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                    }
                });
            }
        });
        
        // Download from action button
        $('.download-resource').click(function(e) {
            e.stopPropagation();
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
        
        // Delete from action button
        $('.delete-resource').click(function(e) {
            e.stopPropagation();
            const resourceId = $(this).data('id');
            
            if (confirm('Are you sure you want to delete this resource? This action cannot be undone.')) {
                $.ajax({
                    url: 'delete_resource.php',
                    type: 'POST',
                    data: { resource_id: resourceId },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                $(`.resource-card[data-id="${resourceId}"]`).fadeOut(300, function() {
                                    $(this).remove();
                                });
                                $(`.list-item[data-id="${resourceId}"]`).fadeOut(300, function() {
                                    $(this).remove();
                                });
                                
                                if ($('.resource-card').length === 0) {
                                    location.reload();
                                }
                            } else {
                                alert('Error: ' + result.message);
                            }
                        } catch(e) {
                            alert('Error deleting resource');
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                    }
                });
            }
        });
    });
    </script>
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