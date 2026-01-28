<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note Station | Collaborative Academic Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #1abc9c;
            --light-color: #f8f9fa;
            --dark-color: #2c3e50;
            --gray-color: #7f8c8d;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Header Styles */
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 2rem;
            color: var(--accent-color);
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .logo span {
            color: var(--accent-color);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
            font-size: 1rem;
        }

        nav a:hover {
            color: var(--accent-color);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.6rem 1.5rem;
            border-radius: 5px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-outline {
            background-color: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background-color: white;
            color: var(--primary-color);
        }

        .btn-accent {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-accent:hover {
            background-color: #16a085;
        }

        .btn-small {
            padding: 0.4rem 1rem;
            font-size: 0.9rem;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary-color), #1a2530);
            color: white;
            padding: 5rem 0;
            text-align: center;
        }

        .hero h2 {
            font-size: 2.8rem;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 2rem;
            color: #ecf0f1;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        /* Features Section */
        .section {
            padding: 4rem 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .section-title p {
            color: var(--gray-color);
            max-width: 700px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background-color: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        /* Subjects Section */
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
        }

        .subject-card {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
        }

        .subject-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .subject-icon {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .subject-card h3 {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .file-count {
            font-size: 0.9rem;
            color: var(--gray-color);
        }

        /* Resources Section */
        .resources-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 992px) {
            .resources-container {
                grid-template-columns: 1fr;
            }
        }

        .resources-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .resource-card {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .resource-info h4 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .resource-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: var(--gray-color);
        }

        .resource-actions {
            display: flex;
            gap: 0.5rem;
        }

        .upload-form {
            background-color: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .upload-form h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        /* Footer */
        footer {
            background-color: var(--primary-color);
            color: white;
            padding: 3rem 0 1.5rem;
        }

        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-col h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--accent-color);
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 0.8rem;
        }

        .footer-col a {
            color: #ecf0f1;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-col a:hover {
            color: var(--accent-color);
        }

        .copyright {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #bdc3c7;
            font-size: 0.9rem;
        }

        /* Mobile Navigation */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
            }

            nav {
                width: 100%;
                order: 3;
                margin-top: 1rem;
                display: none;
            }

            nav.active {
                display: block;
            }

            nav ul {
                flex-direction: column;
                gap: 1rem;
            }

            .mobile-menu-btn {
                display: block;
            }

            .auth-buttons {
                margin-left: auto;
            }

            .hero h2 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
        }

        /* Stats Section */
        .stats {
            background-color: var(--light-color);
            padding: 3rem 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item h3 {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        /* Search Bar */
        .search-container {
            max-width: 800px;
            margin: 2rem auto;
        }

        .search-box {
            display: flex;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-radius: 50px;
            overflow: hidden;
        }

        .search-box input {
            flex: 1;
            padding: 1.2rem 1.5rem;
            border: none;
            font-size: 1rem;
        }

        .search-box button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 0 2rem;
            cursor: pointer;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h1>Note<span>Station</span></h1>
            </div>
            
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav id="mainNav">
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#subjects">Subjects</a></li>
                    <li><a href="#resources">Resources</a></li>
                    <li><a href="#upload">Upload</a></li>
                </ul>
            </nav>
            
            <div class="auth-buttons">
                <a href="#login" class="btn btn-outline">Log In</a>
                <a href="#signup" class="btn btn-primary">Sign Up</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <h2>Streamline Your Academic Success</h2>
            <p>Note Station is a collaborative academic hub that centralizes high-quality lecture notes and past papers into one organized digital library. Equalizing learning for everyone.</p>
            
            <div class="search-container">
                <div class="search-box">
                    <input type="text" placeholder="Search for lecture notes, past papers, or study materials...">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </div>
            </div>
            
            <div class="hero-buttons">
                <a href="#resources" class="btn btn-accent">Browse Resources</a>
                <a href="#upload" class="btn btn-outline">Contribute Notes</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>2,500+</h3>
                    <p>Study Resources</p>
                </div>
                <div class="stat-item">
                    <h3>150+</h3>
                    <p>Academic Subjects</p>
                </div>
                <div class="stat-item">
                    <h3>8,000+</h3>
                    <p>Active Users</p>
                </div>
                <div class="stat-item">
                    <h3>15,000+</h3>
                    <p>Downloads</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section" id="features">
        <div class="container">
            <div class="section-title">
                <h2>How Note Station Works</h2>
                <p>Our platform eliminates the need to search through scattered websites or fragmented chat groups</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h3>Seamless Contribution</h3>
                    <p>Users can easily upload their own lecture notes, past papers, and study materials to help others succeed.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3>Centralized Library</h3>
                    <p>All resources are organized in one digital library, categorized by subject, course, and university.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3>Easy Downloads</h3>
                    <p>Retrieve materials instantly with our simple download system. No registration required for downloading.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community-Driven</h3>
                    <p>A collaborative platform where students help each other by sharing quality resources for free.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Subjects Section -->
    <section class="section" id="subjects" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="section-title">
                <h2>Browse By Subject</h2>
                <p>Access field-specific tools to improve exam readiness and study anywhere, anytime</p>
            </div>
            
            <div class="subjects-grid">
                <div class="subject-card">
                    <div class="subject-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3>Mathematics</h3>
                    <p class="file-count">320+ resources</p>
                </div>
                
                <div class="subject-card">
                    <div class="subject-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <h3>Chemistry</h3>
                    <p class="file-count">245+ resources</p>
                </div>
                
                <div class="subject-card">
                    <div class="subject-icon">
                        <i class="fas fa-dna"></i>
                    </div>
                    <h3>Biology</h3>
                    <p class="file-count">280+ resources</p>
                </div>
                
                <div class="subject-card">
                    <div class="subject-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Physics</h3>
                    <p class="file-count">310+ resources</p>
                </div>
                
                <div class="subject-card">
                    <div class="subject-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3>Computer Science</h3>
                    <p class="file-count">420+ resources</p>
                </div>
                
                <div class="subject-card">
                    <div class="subject-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <h3>Law</h3>
                    <p class="file-count">180+ resources</p>
                </div>
                
                <div class="subject-card">
                    <div class="subject-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Business</h3>
                    <p class="file-count">260+ resources</p>
                </div>
                
                <div class="subject-card">
                    <div class="subject-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>Literature</h3>
                    <p class="file-count">190+ resources</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Resources & Upload Section -->
    <section class="section" id="resources">
        <div class="container">
            <div class="section-title">
                <h2>Recent Study Resources</h2>
                <p>High-quality lecture notes and past papers contributed by students worldwide</p>
            </div>
            
            <div class="resources-container">
                <div class="resources-list">
                    <!-- Resource 1 -->
                    <div class="resource-card">
                        <div class="resource-info">
                            <h4>Calculus I - Complete Lecture Notes</h4>
                            <div class="resource-meta">
                                <span><i class="far fa-user"></i> Alex Johnson</span>
                                <span><i class="far fa-calendar"></i> 2 days ago</span>
                                <span><i class="fas fa-download"></i> 142 downloads</span>
                            </div>
                        </div>
                        <div class="resource-actions">
                            <a href="#" class="btn btn-primary btn-small">Download</a>
                            <a href="#" class="btn btn-outline btn-small">Preview</a>
                        </div>
                    </div>
                    
                    <!-- Resource 2 -->
                    <div class="resource-card">
                        <div class="resource-info">
                            <h4>Organic Chemistry Past Papers (2020-2023)</h4>
                            <div class="resource-meta">
                                <span><i class="far fa-user"></i> Maria Chen</span>
                                <span><i class="far fa-calendar"></i> 1 week ago</span>
                                <span><i class="fas fa-download"></i> 89 downloads</span>
                            </div>
                        </div>
                        <div class="resource-actions">
                            <a href="#" class="btn btn-primary btn-small">Download</a>
                            <a href="#" class="btn btn-outline btn-small">Preview</a>
                        </div>
                    </div>
                    
                    <!-- Resource 3 -->
                    <div class="resource-card">
                        <div class="resource-info">
                            <h4>Introduction to Programming - Lab Solutions</h4>
                            <div class="resource-meta">
                                <span><i class="far fa-user"></i> Tech Students Club</span>
                                <span><i class="far fa-calendar"></i> 2 weeks ago</span>
                                <span><i class="fas fa-download"></i> 210 downloads</span>
                            </div>
                        </div>
                        <div class="resource-actions">
                            <a href="#" class="btn btn-primary btn-small">Download</a>
                            <a href="#" class="btn btn-outline btn-small">Preview</a>
                        </div>
                    </div>
                    
                    <!-- Resource 4 -->
                    <div class="resource-card">
                        <div class="resource-info">
                            <h4>Microeconomics Exam Prep Guide</h4>
                            <div class="resource-meta">
                                <span><i class="far fa-user"></i> David Wilson</span>
                                <span><i class="far fa-calendar"></i> 3 weeks ago</span>
                                <span><i class="fas fa-download"></i> 167 downloads</span>
                            </div>
                        </div>
                        <div class="resource-actions">
                            <a href="#" class="btn btn-primary btn-small">Download</a>
                            <a href="#" class="btn btn-outline btn-small">Preview</a>
                        </div>
                    </div>
                </div>
                
                <!-- Upload Form -->
                <div class="upload-form" id="upload">
                    <h3>Contribute Your Notes</h3>
                    <form action="upload.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Resource Title</label>
                            <input type="text" id="title" name="title" class="form-control" placeholder="e.g., Calculus I Lecture Notes" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <select id="subject" name="subject" class="form-control" required>
                                <option value="">Select a subject</option>
                                <option value="mathematics">Mathematics</option>
                                <option value="physics">Physics</option>
                                <option value="chemistry">Chemistry</option>
                                <option value="biology">Biology</option>
                                <option value="computer-science">Computer Science</option>
                                <option value="business">Business</option>
                                <option value="law">Law</option>
                                <option value="literature">Literature</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Brief description of the resource"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="file">Upload File</label>
                            <input type="file" id="file" name="file" class="form-control" required>
                            <small>Supported formats: PDF, DOC, DOCX, PPT, PPTX (Max 20MB)</small>
                        </div>
                        
                        <button type="submit" class="btn btn-accent" style="width: 100%;">Upload Resource</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-container">
                <div class="footer-col">
                    <h3>Note Station</h3>
                    <p>A community-driven platform that equalizes learning, ensuring every user has free, instant access to field-specific tools to improve exam readiness.</p>
                    <div style="margin-top: 1.5rem;">
                        <a href="#" class="btn btn-outline" style="margin-right: 10px;"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline" style="margin-right: 10px;"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-outline" style="margin-right: 10px;"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="btn btn-outline"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#subjects">Subjects</a></li>
                        <li><a href="#resources">Resources</a></li>
                        <li><a href="#upload">Upload Notes</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="#privacy">Privacy Policy</a></li>
                        <li><a href="#terms">Terms of Service</a></li>
                        <li><a href="#cookies">Cookie Policy</a></li>
                        <li><a href="#guidelines">Community Guidelines</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-envelope"></i> support@notestation.edu</li>
                        <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Academic Street, Edu City</li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; 2023 Note Station. All rights reserved. | Designed to equalize learning for everyone</p>
            </div>
        </div>
    </footer>

    <!-- PHP Backend Simulation -->
    <?php
    // In a real application, this would be in a separate PHP file
    // This is a simulation of what the PHP backend would look like
    echo '<!-- PHP backend simulation -->';
    echo '<div style="display:none;">';
    
    // Simulate file upload handling
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
        $title = htmlspecialchars($_POST['title']);
        $subject = htmlspecialchars($_POST['subject']);
        $description = htmlspecialchars($_POST['description']);
        
        // In a real application, you would handle file upload here
        // $file = $_FILES['file'];
        // move_uploaded_file($file['tmp_name'], "uploads/" . $file['name']);
        
        echo "<p>File uploaded successfully: $title</p>";
    }
    
    echo '</div>';
    ?>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mainNav = document.getElementById('mainNav');
        
        mobileMenuBtn.addEventListener('click', () => {
            mainNav.classList.toggle('active');
            mobileMenuBtn.innerHTML = mainNav.classList.contains('active') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                    
                    // Close mobile menu if open
                    if (mainNav.classList.contains('active')) {
                        mainNav.classList.remove('active');
                        mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                    }
                }
            });
        });
        
        // Form submission simulation
        const uploadForm = document.querySelector('.upload-form form');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form values
                const title = document.getElementById('title').value;
                const subject = document.getElementById('subject').value;
                
                // In a real app, this would be an AJAX request to the server
                // For demo purposes, we'll just show an alert
                alert(`Thank you for contributing!\n\nYour "${title}" resource for ${subject} has been submitted for review.`);
                
                // Reset form
                this.reset();
            });
        }
        
        // Search functionality simulation
        const searchInput = document.querySelector('.search-box input');
        const searchButton = document.querySelector('.search-box button');
        
        searchButton.addEventListener('click', function() {
            const query = searchInput.value.trim();
            if (query) {
                alert(`Searching for: "${query}"\n\nIn a real application, this would show search results.`);
            }
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = searchInput.value.trim();
                if (query) {
                    alert(`Searching for: "${query}"\n\nIn a real application, this would show search results.`);
                }
            }
        });
    </script>
</body>
</html>