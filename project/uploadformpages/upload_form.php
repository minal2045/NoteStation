<?php
require_once 'config.php';

// Fetch courses from database (you can populate this table with Ahmedabad courses)
$courses_query = "SELECT DISTINCT course_name FROM courses WHERE city = 'Ahmedabad' ORDER BY course_name";
$courses_result = $conn->query($courses_query);

// Fetch universities in Ahmedabad
$universities_query = "SELECT university_name FROM universities WHERE city = 'Ahmedabad' ORDER BY university_name";
$universities_result = $conn->query($universities_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Resource - Study Material Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        
        .upload-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .upload-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
        }
        
        .upload-header h2 {
            color: #333;
            font-weight: 700;
        }
        
        .upload-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .form-section h5 {
            color: #495057;
            margin-bottom: 20px;
            font-weight: 600;
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .required-field::after {
            content: " *";
            color: red;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-upload {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: white;
            width: 100%;
            border-radius: 10px;
            transition: transform 0.3s;
        }
        
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-upload:disabled {
            opacity: 0.6;
            transform: none;
        }
        
        .file-info {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .invalid-feedback {
            display: block;
            font-size: 13px;
        }
        
        .university-section {
            transition: all 0.3s ease;
        }
        
        .hidden {
            display: none;
            opacity: 0;
            visibility: hidden;
        }
        
        .visible {
            display: block;
            opacity: 1;
            visibility: visible;
        }
        
        .upload-status {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
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
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .loading-spinner.show {
            display: block;
        }
        
        .preview-file {
            margin-top: 10px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .suggestion-box {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .suggestion-item {
            padding: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .suggestion-item:hover {
            background: #f0f0f0;
        }
        
        .input-suggestion-wrapper {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-container">
            <div class="upload-header">
                <h2><i class="fas fa-cloud-upload-alt me-2"></i>Upload Resource</h2>
                <p>Share your study materials with fellow students in Ahmedabad</p>
            </div>
            
            <!-- Loading Spinner -->
            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Processing your upload...</p>
            </div>
            
            <!-- Upload Form -->
            <form id="uploadForm" enctype="multipart/form-data" method="POST" action="process_upload.php">
                <!-- Section 1: Basic Information -->
                <div class="form-section">
                    <h5><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                    
                    <div class="mb-3">
                        <label for="resourceType" class="form-label required-field">Resource Type</label>
                        <select class="form-select" id="resourceType" name="resource_type" required>
                            <option value="">Select Resource Type</option>
                            <option value="notes">Notes</option>
                            <option value="question_paper">Question Paper</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="courseName" class="form-label required-field">Course Name</label>
                        <select class="form-select" id="courseName" name="course_name" required>
                            <option value="">Select Course</option>
                            <?php
                            if ($courses_result->num_rows > 0) {
                                while($course = $courses_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($course['course_name']) . "'>" . 
                                         htmlspecialchars($course['course_name']) . "</option>";
                                }
                            } else {
                                // Sample courses for Ahmedabad if database is empty
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
                    
                    <!-- Subject Name as Text Input with Suggestions -->
                    <div class="mb-3">
                        <label for="subjectName" class="form-label required-field">Subject Name</label>
                        <div class="input-suggestion-wrapper">
                            <input type="text" class="form-control" id="subjectName" name="subject_name" 
                                   placeholder="Enter subject name (e.g., Data Structures, Calculus)" 
                                   maxlength="100" required autocomplete="off">
                            <div class="suggestion-box" id="suggestionBox"></div>
                        </div>
                        <small class="text-muted">Type the subject name - suggestions will appear as you type</small>
                    </div>
                    
                    <!-- University Section - Shows only for Question Papers -->
                    <div class="mb-3 university-section hidden" id="universitySection">
                        <label for="universityName" class="form-label required-field">University Name</label>
                        <select class="form-select" id="universityName" name="university_name">
                            <option value="">Select University</option>
                            <?php
                            if ($universities_result->num_rows > 0) {
                                while($university = $universities_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($university['university_name']) . "'>" . 
                                         htmlspecialchars($university['university_name']) . "</option>";
                                }
                            } else {
                                // Sample universities in Ahmedabad
                                $sample_universities = [
                                    "Gujarat University",
                                    "Ahmedabad University",
                                    "Nirma University",
                                    "Gujarat Technological University",
                                    "CEPT University",
                                    "GLS University",
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
                </div>
                
                <!-- Section 2: Resource Details -->
                <div class="form-section">
                    <h5><i class="fas fa-file-alt me-2"></i>Resource Details</h5>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label required-field">Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               placeholder="Enter a descriptive title" maxlength="100" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" maxlength="100" placeholder="Brief description of the resource"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fileType" class="form-label required-field">File Type</label>
                        <select class="form-select" id="fileType" name="file_type" required>
                            <option value="">Select File Type</option>
                            <option value="ppt">PowerPoint (PPT)</option>
                            <option value="pdf">PDF Document</option>
                            <option value="docx">Word Document (DOCX)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fileUpload" class="form-label required-field">Upload File</label>
                        <input type="file" class="form-control" id="fileUpload" name="file_upload" 
                               accept=".ppt,.pptx,.pdf,.docx" required>
                        <div class="file-info">
                            <i class="fas fa-info-circle me-1"></i>
                            Allowed formats: PPT, PDF, DOCX only. Maximum file size: 50MB
                        </div>
                        <div class="preview-file" id="filePreview" style="display: none;">
                            <i class="fas fa-file me-2"></i>
                            <span id="fileName"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden Fields -->
                <input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>">
                <input type="hidden" name="upload_date" id="uploadDate">
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-upload" id="submitBtn">
                    <i class="fas fa-upload me-2"></i>Upload Resource
                </button>
            </form>
            
            <!-- Upload Status -->
            <div class="upload-status" id="uploadStatus"></div>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
    $(document).ready(function() {
        // Set current date automatically
        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        $('#uploadDate').val(formattedDate);
        
        // Handle Resource Type change
        $('#resourceType').change(function() {
            const resourceType = $(this).val();
            const universitySection = $('#universitySection');
            const universitySelect = $('#universityName');
            
            if (resourceType === 'question_paper') {
                // Show university section for question papers
                universitySection.removeClass('hidden').addClass('visible');
                universitySelect.prop('required', true);
                universitySelect.prop('disabled', false);
            } else {
                // Hide university section for notes
                universitySection.removeClass('visible').addClass('hidden');
                universitySelect.prop('required', false);
                universitySelect.prop('disabled', true);
                universitySelect.val(''); // Clear selection
            }
        });
        
        // Subject name suggestions
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
            "Investment Analysis",
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
            "Supply Chain Management",
            "Project Management",
            "Research Methodology",
            "Technical Communication",
            "Engineering Mathematics",
            "Physics",
            "Chemistry",
            "Biology",
            "Biotechnology",
            "Microbiology",
            "Genetics",
            "Cell Biology",
            "Molecular Biology",
            "Biochemistry",
            "Environmental Science"
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
        
        // File upload preview
        $('#fileUpload').change(function() {
            const file = this.files[0];
            if (file) {
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // Size in MB
                const fileExt = fileName.split('.').pop().toLowerCase();
                
                // Validate file type
                const allowedTypes = ['ppt', 'pptx', 'pdf', 'docx'];
                if (!allowedTypes.includes(fileExt)) {
                    alert('Invalid file type. Only PPT, PDF, and DOCX files are allowed.');
                    $(this).val('');
                    $('#filePreview').hide();
                    return;
                }
                
                // Validate file size (50MB max)
                if (fileSize > 50) {
                    alert('File size exceeds 50MB limit.');
                    $(this).val('');
                    $('#filePreview').hide();
                    return;
                }
                
                // Show file preview
                $('#fileName').text(fileName + ' (' + fileSize + ' MB)');
                $('#filePreview').show();
            } else {
                $('#filePreview').hide();
            }
        });
        
        // Form validation and submission
        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            
            // Show loading spinner
            $('#loadingSpinner').addClass('show');
            $('#submitBtn').prop('disabled', true);
            $('#uploadStatus').hide();
            
            // Validate form
            let isValid = true;
            let errorMessage = '';
            
            // Check required fields
            const resourceType = $('#resourceType').val();
            const courseName = $('#courseName').val();
            const subjectName = $('#subjectName').val().trim();
            const title = $('#title').val().trim();
            const fileType = $('#fileType').val();
            const fileUpload = $('#fileUpload')[0].files[0];
            
            if (!resourceType) {
                isValid = false;
                errorMessage += 'Please select Resource Type.<br>';
            }
            
            if (!courseName) {
                isValid = false;
                errorMessage += 'Please select Course Name.<br>';
            }
            
            if (!subjectName) {
                isValid = false;
                errorMessage += 'Please enter Subject Name.<br>';
            }
            
            if (resourceType === 'question_paper') {
                const universityName = $('#universityName').val();
                if (!universityName) {
                    isValid = false;
                    errorMessage += 'Please select University Name for Question Paper.<br>';
                }
            }
            
            if (!title) {
                isValid = false;
                errorMessage += 'Please enter Title.<br>';
            }
            
            if (!fileType) {
                isValid = false;
                errorMessage += 'Please select File Type.<br>';
            }
            
            if (!fileUpload) {
                isValid = false;
                errorMessage += 'Please select a file to upload.<br>';
            }
            
            if (!isValid) {
                $('#loadingSpinner').removeClass('show');
                $('#submitBtn').prop('disabled', false);
                $('#uploadStatus').removeClass('success error').addClass('error').html(errorMessage).show();
                return;
            }
            
            // Create FormData object
            const formData = new FormData(this);
            
            // AJAX form submission
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
                            $('#universitySection').removeClass('visible').addClass('hidden');
                            
                            // Redirect after 3 seconds
                            setTimeout(function() {
                                window.location.href = 'my_uploads.php';
                            }, 3000);
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
        
        // Live validation for file type selection
        $('#fileType').change(function() {
            const selectedType = $(this).val();
            const fileInput = $('#fileUpload');
            
            if (selectedType) {
                let acceptTypes = '';
                if (selectedType === 'ppt') {
                    acceptTypes = '.ppt,.pptx';
                } else if (selectedType === 'pdf') {
                    acceptTypes = '.pdf';
                } else if (selectedType === 'docx') {
                    acceptTypes = '.docx';
                }
                fileInput.attr('accept', acceptTypes);
            }
        });
    });
    </script>
</body>
</html>