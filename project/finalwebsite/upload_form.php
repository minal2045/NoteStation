<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login_signup.php');
    exit();
}

$is_logged_in = true; // Since we already checked above, user is logged in
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

// Fetch courses from database
$courses_query = "SELECT DISTINCT course_name FROM courses ORDER BY course_name";
$courses_result = $conn->query($courses_query);

// Fetch universities
$universities_query = "SELECT university_name FROM universities ORDER BY university_name";
$universities_result = $conn->query($universities_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NoteStation - Upload Study Material</title>
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-top: 0;
            padding-bottom: 40px;
        }
        
        /* Navbar Styles - Solid gradient matching my_uploads.php */
        .navbar {
            position: relative; /* Changed from fixed to relative to eliminate gap */
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

        /* Upload Container */
        .upload-container {
            background: white;
            border-radius: 30px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            margin: 30px auto 0;
            padding: 40px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .upload-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .upload-header h2 {
            color: #333;
            font-weight: 700;
            font-size: 32px;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }
        
        .upload-header p {
            color: #666;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .upload-header p i {
            color: #654D87;
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
            font-size: 15px;
        }
        
        .required-field::after {
            content: " *";
            color: #ff4d4d;
        }
        
        .form-control, .form-select {
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background-color: #fff;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #654D87;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        .form-control::placeholder {
            color: #999;
            font-size: 14px;
        }
        
        .btn-upload {
            background: linear-gradient(135deg, rgba(74, 63, 122, 0.85) 0%, rgba(90, 61, 124, 0.85) 100%);
            border: none;
            padding: 14px 30px;
            font-weight: 600;
            color: white;
            width: 100%;
            border-radius: 50px;
            font-size: 16px;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-upload i {
            margin-right: 8px;
        }
        
        .file-info {
            font-size: 12px;
            color: #6c757d;
            margin-top: 8px;
        }
        
        .university-section {
            transition: all 0.3s ease;
        }
        
        .hidden {
            display: none;
        }
        
        /* Suggestion Box */
        .suggestion-box {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            margin-top: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: none;
        }
        
        .suggestion-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 14px;
        }
        
        .suggestion-item:hover {
            background: #f5f5f5;
        }
        
        .input-suggestion-wrapper {
            position: relative;
        }
        
        /* File Preview */
        .preview-file {
            margin-top: 12px;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 12px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #e0e0e0;
        }
        
        .preview-file i {
            color: #654D87;
            font-size: 20px;
        }
        
        /* Loading Spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .loading-spinner.show {
            display: block;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: #654D87;
        }
        
        /* Upload Status */
        .upload-status {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            border-radius: 12px;
            display: none;
        }
        
        .upload-status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        
        .upload-status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
        
        .container {
            margin-top: 0;
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
            
            .upload-container {
                margin: 20px 15px 0;
                padding: 25px;
            }
            
            .upload-header h2 {
                font-size: 28px;
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
    <!-- Navbar with Solid Gradient - Same as my_uploads.php -->
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
                <a class="nav-link active" href="upload_form.php">Upload</a>
                
                <?php if ($is_logged_in): ?>
                    <!-- My Uploads Link -->
                    <a class="nav-link" href="my_uploads.php">My Uploads</a>
                    
                    <?php if ($is_admin): ?>
                        <a class="nav-link" href="admin_dashboard.php">Admin</a>
                    <?php endif; ?>
                    
                    <!-- Bootstrap Dropdown -->
                    <div class="dropdown d-inline-block">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> 
                            <?php echo htmlspecialchars($_SESSION['full_name'] ?? $user_full_name); ?>
                            <?php if ($is_admin): ?>
                                <span class="badge bg-warning text-dark ms-2">Admin</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- This should never show since we redirect non-logged in users at the top -->
                    <a class="nav-link" href="login_signup.php">Login</a>
                    <a class="nav-link" href="login_signup.php">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="upload-container">
            <div class="upload-header">
                <h2>Upload Study Material</h2>
                <p>
                    <i class="fas fa-share-alt"></i>
                    Share your notes and help fellow students
                </p>
            </div>
            
            <!-- Loading Spinner -->
            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Processing your upload...</p>
            </div>
            
            <!-- Upload Form -->
            <form id="uploadForm" enctype="multipart/form-data" method="POST" action="process_upload.php">
                <!-- Title Field -->
                <div class="form-section">
                    <div class="mb-4">
                        <label for="title" class="form-label required-field">Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               placeholder="e.g., Data Structures Lecture 5 Notes" maxlength="100" required>
                    </div>
                    
                    <!-- Subject and Type Row -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="subjectName" class="form-label required-field">Subject</label>
                            <div class="input-suggestion-wrapper">
                                <input type="text" class="form-control" id="subjectName" name="subject_name" 
                                       placeholder="Enter subject" maxlength="100" required autocomplete="off">
                                <div class="suggestion-box" id="suggestionBox"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="resourceType" class="form-label required-field">Type</label>
                            <select class="form-select" id="resourceType" name="resource_type" required>
                                <option value="">Select type</option>
                                <option value="notes">Notes</option>
                                <option value="question_paper">Question Paper</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Course Field -->
                    <div class="mb-4">
                        <label for="courseName" class="form-label required-field">Course</label>
                        <select class="form-select" id="courseName" name="course_name" required>
                            <option value="">Select course</option>
                            <?php
                            if ($courses_result && $courses_result->num_rows > 0) {
                                while($course = $courses_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($course['course_name']) . "'>" . 
                                         htmlspecialchars($course['course_name']) . "</option>";
                                }
                            } else {
                                // Sample courses
                                $sample_courses = [
                                    "B.Sc Computer Science",
                                    "B.Sc Information Technology",
                                    "B.Sc Mathematics",
                                    "B.Sc Physics",
                                    "B.Sc Chemistry",
                                    "B.Sc Biotechnology",
                                    "B.Sc Microbiology",
                                    "B.Com",
                                    "B.Com (Honours)",
                                    "B.A Economics",
                                    "B.A English Literature",
                                    "B.A Psychology",
                                    "BBA",
                                    "BCA",
                                    "MCA",
                                    "MBA",
                                    "M.Sc Computer Science",
                                    "M.Sc Information Technology",
                                    "M.Com",
                                    "MA Economics"
                                ];
                                foreach($sample_courses as $course) {
                                    echo "<option value='" . htmlspecialchars($course) . "'>" . 
                                         htmlspecialchars($course) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- University Section - Shows only for Question Papers -->
                    <div class="mb-4 university-section hidden" id="universitySection">
                        <label for="universityName" class="form-label required-field">University</label>
                        <select class="form-select" id="universityName" name="university_name">
                            <option value="">Select university</option>
                            <?php
                            if ($universities_result && $universities_result->num_rows > 0) {
                                while($university = $universities_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($university['university_name']) . "'>" . 
                                         htmlspecialchars($university['university_name']) . "</option>";
                                }
                            } else {
                                // Sample universities
                                $sample_universities = [
                                    "Gujarat University",
                                    "Ahmedabad University",
                                    "Nirma University",
                                    "Gujarat Technological University",
                                    "CEPT University",
                                    "GLS University",
                                    "JG University",
                                    "Karnavati University",
                                    "Indus University",
                                    "Silver Oak University",
                                    "Dr. Babasaheb Ambedkar Open University",
                                    "Ganpat University - Ahmedabad Campus",
                                    "Pandit Deendayal Energy University"
                                ];
                                foreach($sample_universities as $university) {
                                    echo "<option value='" . htmlspecialchars($university) . "'>" . 
                                         htmlspecialchars($university) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Description Field -->
                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" placeholder="Brief description of the material..." maxlength="500"></textarea>
                    </div>
                    
                    <!-- File Upload Field -->
                    <div class="mb-3">
                        <label for="fileUpload" class="form-label required-field">File</label>
                        <input type="file" class="form-control" id="fileUpload" name="file_upload" 
                               accept=".ppt,.pptx,.pdf,.docx" required>
                        <div class="file-info">
                            <i class="fas fa-info-circle me-1"></i>
                            Allowed formats: PPT, PDF, DOCX. Max size: 50MB
                        </div>
                        <div class="preview-file" id="filePreview" style="display: none;">
                            <i class="fas fa-file"></i>
                            <span id="fileName"></span>
                        </div>
                    </div>
                    
                    <!-- Hidden Fields -->
                    <input type="hidden" name="file_type" id="fileType">
                    <input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>">
                    <input type="hidden" name="upload_date" id="uploadDate">
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-upload" id="submitBtn">
                    <i class="fas fa-upload"></i>Upload Resource
                </button>
            </form>
            
            <!-- Upload Status -->
            <div class="upload-status" id="uploadStatus"></div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
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
        
        // Set current date
        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        $('#uploadDate').val(formattedDate);
        
        // Handle Resource Type change
        $('#resourceType').change(function() {
            const resourceType = $(this).val();
            const universitySection = $('#universitySection');
            const universitySelect = $('#universityName');
            
            if (resourceType === 'question_paper') {
                universitySection.removeClass('hidden');
                universitySelect.prop('required', true);
            } else {
                universitySection.addClass('hidden');
                universitySelect.prop('required', false);
                universitySelect.val('');
            }
        });
        
        // Subject suggestions
        const commonSubjects = [
            "Data Structures",
            "Algorithms",
            "Database Management Systems",
            "Operating Systems",
            "Computer Networks",
            "Software Engineering",
            "Web Development",
            "Object Oriented Programming",
            "Discrete Mathematics",
            "Calculus",
            "Linear Algebra",
            "Probability and Statistics",
            "Financial Accounting",
            "Cost Accounting",
            "Microeconomics",
            "Macroeconomics",
            "Organizational Behavior",
            "Marketing Management",
            "Human Resource Management",
            "Business Law",
            "Taxation",
            "Auditing",
            "Corporate Finance",
            "Python Programming",
            "Java Programming",
            "C++ Programming",
            "Artificial Intelligence",
            "Machine Learning",
            "Data Science",
            "Cloud Computing",
            "Cyber Security",
            "Digital Marketing",
            "Business Analytics",
            "Physics",
            "Chemistry",
            "Biology",
            "Mathematics",
            "Statistics"
        ];
        
        $('#subjectName').on('input', function() {
            const input = $(this).val().toLowerCase();
            const suggestionBox = $('#suggestionBox');
            
            if (input.length < 2) {
                suggestionBox.hide();
                return;
            }
            
            const matches = commonSubjects.filter(subject => 
                subject.toLowerCase().includes(input)
            );
            
            if (matches.length > 0) {
                let html = '';
                matches.slice(0, 8).forEach(match => {
                    html += `<div class="suggestion-item">${match}</div>`;
                });
                suggestionBox.html(html).show();
            } else {
                suggestionBox.hide();
            }
        });
        
        // Handle suggestion click
        $(document).on('click', '.suggestion-item', function() {
            $('#subjectName').val($(this).text());
            $('#suggestionBox').hide();
        });
        
        // Hide suggestions when clicking outside
        $(document).click(function(e) {
            if (!$(e.target).closest('.input-suggestion-wrapper').length) {
                $('#suggestionBox').hide();
            }
        });
        
        // File upload preview and type detection
        $('#fileUpload').change(function() {
            const file = this.files[0];
            if (file) {
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const fileExt = fileName.split('.').pop().toLowerCase();
                
                // Validate file type
                const allowedTypes = ['ppt', 'pptx', 'pdf', 'docx'];
                if (!allowedTypes.includes(fileExt)) {
                    alert('Invalid file type. Only PPT, PDF, and DOCX files are allowed.');
                    $(this).val('');
                    $('#filePreview').hide();
                    return;
                }
                
                // Validate file size (50MB)
                if (fileSize > 50) {
                    alert('File size exceeds 50MB limit.');
                    $(this).val('');
                    $('#filePreview').hide();
                    return;
                }
                
                // Set file type hidden field
                let fileType = 'ppt';
                if (fileExt === 'pdf') fileType = 'pdf';
                else if (fileExt === 'docx') fileType = 'docx';
                $('#fileType').val(fileType);
                
                // Show file preview
                $('#fileName').text(fileName + ' (' + fileSize + ' MB)');
                $('#filePreview').show();
            } else {
                $('#filePreview').hide();
                $('#fileType').val('');
            }
        });
        
        // Form submission
        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            
            // Validate file type selection
            if (!$('#fileType').val()) {
                alert('Please select a valid file.');
                return;
            }
            
            // Show loading spinner
            $('#loadingSpinner').addClass('show');
            $('#submitBtn').prop('disabled', true);
            $('#uploadStatus').hide();
            
            // Validate form
            let isValid = true;
            let errorMessage = '';
            
            const resourceType = $('#resourceType').val();
            const courseName = $('#courseName').val();
            const subjectName = $('#subjectName').val().trim();
            const title = $('#title').val().trim();
            const fileUpload = $('#fileUpload')[0].files[0];
            
            if (!resourceType) {
                isValid = false;
                errorMessage += 'Please select Resource Type.<br>';
            }
            
            if (!courseName) {
                isValid = false;
                errorMessage += 'Please select Course.<br>';
            }
            
            if (!subjectName) {
                isValid = false;
                errorMessage += 'Please enter Subject.<br>';
            }
            
            if (resourceType === 'question_paper' && !$('#universityName').val()) {
                isValid = false;
                errorMessage += 'Please select University.<br>';
            }
            
            if (!title) {
                isValid = false;
                errorMessage += 'Please enter Title.<br>';
            }
            
            if (!fileUpload) {
                isValid = false;
                errorMessage += 'Please select a file to upload.<br>';
            }
            
            if (!isValid) {
                $('#loadingSpinner').removeClass('show');
                $('#submitBtn').prop('disabled', false);
                $('#uploadStatus').removeClass('success').addClass('error').html(errorMessage).show();
                return;
            }
            
            // Create FormData and submit
            const formData = new FormData(this);
            
            $.ajax({
                url: 'process_upload.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#loadingSpinner').removeClass('show');
                    $('#submitBtn').prop('disabled', false);
                    
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            $('#uploadStatus').removeClass('error').addClass('success')
                                .html('<i class="fas fa-check-circle me-2"></i>' + result.message)
                                .show();
                            
                            // Reset form
                            $('#uploadForm')[0].reset();
                            $('#filePreview').hide();
                            $('#fileType').val('');
                            $('#universitySection').addClass('hidden');
                            
                            // Redirect after 3 seconds
                            setTimeout(function() {
                                window.location.href = 'my_uploads.php';
                            }, 1000);
                        } else {
                            $('#uploadStatus').removeClass('success').addClass('error')
                                .html('<i class="fas fa-exclamation-circle me-2"></i>' + result.message)
                                .show();
                        }
                    } catch(e) {
                        $('#uploadStatus').removeClass('success').addClass('error')
                            .html('<i class="fas fa-exclamation-circle me-2"></i>An error occurred during upload.')
                            .show();
                    }
                },
                error: function() {
                    $('#loadingSpinner').removeClass('show');
                    $('#submitBtn').prop('disabled', false);
                    $('#uploadStatus').removeClass('success').addClass('error')
                        .html('<i class="fas fa-exclamation-circle me-2"></i>Network error. Please try again.')
                        .show();
                }
            });
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