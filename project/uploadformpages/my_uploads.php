<?php
require_once 'config.php';

// Get current user's uploads
$user_id = $current_user_id;
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
    <title>My Uploads - Study Material Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Lightbox for file preview -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
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
        
        /* Header Section */
        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
            border-radius: 0 0 50px 50px;
            box-shadow: var(--card-shadow);
        }
        
        .page-header h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* Stats Cards */
        .stats-container {
            margin-top: -60px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
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
            width: 60px;
            height: 60px;
            line-height: 60px;
            border-radius: 15px;
            margin: 0 auto 15px;
            font-size: 24px;
            color: white;
        }
        
        .stat-icon.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.notes { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.papers { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Control Bar */
        .control-bar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .filter-btns {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
            background: var(--primary-gradient);
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
            color: #667eea;
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
            background: var(--primary-gradient);
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
            background: var(--primary-gradient);
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
            color: #667eea;
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
            margin-bottom: 30px;
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
            margin-bottom: 10px;
        }
        
        .preview-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 30px;
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
            margin-bottom: 30px;
            color: #666;
        }
        
        .preview-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
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
        
        .empty-state .btn {
            background: var(--primary-gradient);
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
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
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
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-cloud-upload-alt me-3"></i>My Uploads</h1>
                    <p>Manage and view all your shared study materials</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="upload_form.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus me-2"></i>Upload New
                    </a>
                </div>
            </div>
        </div>
    </div>
    
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
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by title, subject, course...">
            </div>
            
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
        
        // Create a JSON object with all resource data for easy access
        $resourceData = htmlspecialchars(json_encode([
            'id' => $row['Resource_id'],
            'title' => $row['Title'],
            'subject' => $row['Subject_name'],
            'course' => $row['Course_name'],
            'university' => $row['University_name'],
            'date' => $uploadDate,
            'file_type' => $row['File_type'],
            'description' => $row['Description'],
            'resource_type' => $row['Resource_type']
        ]), ENT_QUOTES, 'UTF-8');
    ?>
    <div class="resource-card" 
         data-id="<?php echo $row['Resource_id']; ?>" 
         data-type="<?php echo $row['Resource_type']; ?>"
         data-title="<?php echo strtolower(htmlspecialchars($row['Title'])); ?>"
         data-subject="<?php echo strtolower(htmlspecialchars($row['Subject_name'])); ?>"
         data-course="<?php echo strtolower(htmlspecialchars($row['Course_name'])); ?>"
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
            <div class="list-item" data-id="<?php echo $row['Resource_id']; ?>"
                 data-type="<?php echo $row['Resource_type']; ?>"
                 data-title="<?php echo strtolower(htmlspecialchars($row['Title'])); ?>"
                 data-subject="<?php echo strtolower(htmlspecialchars($row['Subject_name'])); ?>"
                 data-course="<?php echo strtolower(htmlspecialchars($row['Course_name'])); ?>">
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
    
    // Search functionality
    $('#searchInput').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.resource-card, .list-item').each(function() {
            const title = $(this).data('title') || '';
            const subject = $(this).data('subject') || '';
            const course = $(this).data('course') || '';
            
            if (title.includes(searchTerm) || subject.includes(searchTerm) || course.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
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
            
            // Show modal
            new bootstrap.Modal(document.getElementById('resourceModal')).show();
        }
    }
}
    
    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }
    
    // View file (open in new tab)
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
</body>
</html>