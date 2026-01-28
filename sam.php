<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notestation - Ahmedabad's Academic Resource Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Base Styles */
        :root {
            --primary-blue: #2C3E50;
            --secondary-blue: #34495E;
            --accent-green: #27AE60;
            --accent-orange: #E67E22;
            --accent-red: #E74C3C;
            --light-gray: #ECF0F1;
            --medium-gray: #BDC3C7;
            --dark-gray: #7F8C8D;
            --white: #FFFFFF;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        /* Header & Navigation */
        header {
            background-color: var(--primary-blue);
            color: var(--white);
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--white);
        }

        .logo-icon {
            color: var(--accent-green);
            font-size: 1.8rem;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            transition: background-color 0.3s;
        }

        nav a:hover, nav a.active {
            background-color: var(--secondary-blue);
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .search-bar {
            position: relative;
            width: 300px;
        }

        .search-bar input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 2.5rem;
            border-radius: 20px;
            border: none;
            background-color: var(--white);
            font-size: 0.9rem;
        }

        .search-bar i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-gray);
        }

        .notification, .user-profile {
            position: relative;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--accent-red);
            color: var(--white);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Main Layout */
        .container {
            display: flex;
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
            gap: 2rem;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            flex-shrink: 0;
        }

        .sidebar-nav {
            background-color: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem 0;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 0.2rem;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.9rem 1.5rem;
            color: var(--secondary-blue);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .sidebar-nav a:hover, .sidebar-nav a.active {
            background-color: #f0f7ff;
            border-left: 4px solid var(--accent-green);
            color: var(--primary-blue);
        }

        .sidebar-nav i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: var(--white);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .welcome-banner h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .quick-stats {
            display: flex;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .stat-box {
            background-color: rgba(255, 255, 255, 0.15);
            padding: 1rem;
            border-radius: var(--radius);
            text-align: center;
            flex: 1;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Dashboard Sections */
        .dashboard-section {
            background-color: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .section-header h3 {
            font-size: 1.3rem;
            color: var(--primary-blue);
        }

        .view-all {
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Resource Cards */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .resource-card {
            border: 1px solid var(--light-gray);
            border-radius: var(--radius);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .resource-header {
            padding: 1rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid var(--light-gray);
        }

        .resource-title {
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: var(--primary-blue);
        }

        .resource-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--dark-gray);
        }

        .resource-body {
            padding: 1rem;
        }

        .resource-description {
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            height: 60px;
            overflow: hidden;
        }

        .resource-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background-color: #f8f9fa;
            border-top: 1px solid var(--light-gray);
        }

        .resource-actions {
            display: flex;
            gap: 0.8rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--accent-green);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: #219653;
        }

        .btn-secondary {
            background-color: var(--secondary-blue);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: #2C3E50;
        }

        .btn-outline {
            background-color: transparent;
            color: var(--secondary-blue);
            border: 1px solid var(--medium-gray);
        }

        .btn-outline:hover {
            background-color: #f0f0f0;
        }

        /* University Cards */
        .university-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
        }

        .university-card {
            background-color: #f8f9fa;
            border-radius: var(--radius);
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid transparent;
        }

        .university-card:hover {
            background-color: #e8f4ff;
            border-color: var(--accent-green);
        }

        .university-logo {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 0.8rem;
        }

        .university-name {
            font-weight: 600;
            color: var(--primary-blue);
        }

        /* Upload Wizard */
        .upload-wizard {
            background-color: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .wizard-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .wizard-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .wizard-steps:before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--light-gray);
            z-index: 1;
        }

        .wizard-step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: 600;
        }

        .step-circle.active {
            background-color: var(--accent-green);
            color: var(--white);
        }

        .step-circle.completed {
            background-color: var(--accent-green);
            color: var(--white);
        }

        .step-title {
            font-size: 0.9rem;
            color: var(--dark-gray);
        }

        .step-title.active {
            color: var(--primary-blue);
            font-weight: 600;
        }

        .wizard-content {
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--primary-blue);
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--medium-gray);
            border-radius: var(--radius);
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-green);
        }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        .upload-area {
            border: 2px dashed var(--medium-gray);
            border-radius: var(--radius);
            padding: 3rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-area:hover {
            border-color: var(--accent-green);
            background-color: #f9f9f9;
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--medium-gray);
            margin-bottom: 1rem;
        }

        .upload-area:hover .upload-icon {
            color: var(--accent-green);
        }

        .wizard-footer {
            display: flex;
            justify-content: space-between;
        }

        /* Admin Panel */
        .admin-dashboard {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .admin-stat {
            background-color: var(--white);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .admin-stat.alert {
            border-top: 4px solid var(--accent-red);
        }

        .admin-stat h4 {
            font-size: 0.9rem;
            color: var(--dark-gray);
            margin-bottom: 0.5rem;
        }

        .admin-stat .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .admin-table {
            width: 100%;
            background-color: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .admin-table th {
            background-color: var(--primary-blue);
            color: var(--white);
            padding: 1rem;
            text-align: left;
        }

        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .admin-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--white);
            border-radius: var(--radius);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .modal-header h3 {
            color: var(--primary-blue);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dark-gray);
        }

        .modal-body {
            padding: 1.5rem;
        }

        /* Footer */
        footer {
            background-color: var(--primary-blue);
            color: var(--white);
            padding: 3rem 2rem 1.5rem;
            margin-top: 3rem;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }

        .footer-section h4 {
            font-size: 1.1rem;
            margin-bottom: 1.2rem;
            color: var(--white);
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section li {
            margin-bottom: 0.5rem;
        }

        .footer-section a {
            color: var(--light-gray);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: var(--accent-green);
        }

        .copyright {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--light-gray);
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .sidebar-nav ul {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .sidebar-nav li {
                flex: 1;
                min-width: 150px;
            }
            
            .resources-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-bar {
                width: 100%;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .quick-stats {
                flex-wrap: wrap;
            }
            
            .stat-box {
                flex: 1 0 calc(50% - 0.75rem);
            }
            
            .admin-dashboard {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        @media (max-width: 480px) {
            .resources-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-dashboard {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .university-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-graduation-cap logo-icon"></i>
                <h1>Notestation</h1>
            </div>
            
            <nav>
                <ul>
                    <li><a href="#home" class="active"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="#browse"><i class="fas fa-search"></i> Browse</a></li>
                    <li><a href="#papers"><i class="fas fa-file-alt"></i> Papers</a></li>
                    <li><a href="#forum"><i class="fas fa-comments"></i> Forum</a></li>
                    <li><a href="#universities"><i class="fas fa-university"></i> Universities</a></li>
                </ul>
            </nav>
            
            <div class="user-actions">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search resources, courses, papers...">
                </div>
                
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                
                <div class="user-profile">
                    <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-nav">
                <ul>
                    <li><a href="#dashboard" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="#my-uploads"><i class="fas fa-upload"></i> My Uploads</a></li>
                    <li><a href="#my-downloads"><i class="fas fa-download"></i> My Downloads</a></li>
                    <li><a href="#collections"><i class="fas fa-folder"></i> Collections</a></li>
                    <li><a href="#requests"><i class="fas fa-hand-paper"></i> Requests</a></li>
                    <li><a href="#leaderboard"><i class="fas fa-trophy"></i> Leaderboard</a></li>
                    <li><a href="#settings"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="#help"><i class="fas fa-question-circle"></i> Help</a></li>
                </ul>
            </div>
            
            <!-- Quick Upload Button -->
            <button class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 0.8rem;" onclick="openUploadWizard()">
                <i class="fas fa-plus"></i> Upload Resource
            </button>
            
            <!-- University Badge -->
            <div class="dashboard-section" style="margin-top: 1.5rem;">
                <h4 style="margin-bottom: 0.5rem; color: var(--primary-blue);">Your University</h4>
                <div style="display: flex; align-items: center; gap: 10px; padding: 0.5rem; background-color: #f0f7ff; border-radius: var(--radius);">
                    <i class="fas fa-university" style="color: var(--accent-green);"></i>
                    <div>
                        <div style="font-weight: 600;">Gujarat Technological University</div>
                        <div style="font-size: 0.85rem; color: var(--dark-gray);">B.Tech Computer Engineering</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Welcome Banner -->
            <section class="welcome-banner">
                <h2>Welcome back, Rahul!</h2>
                <p>Access resources, previous papers, and connect with peers across Ahmedabad universities.</p>
                
                <div class="quick-stats">
                    <div class="stat-box">
                        <div class="stat-value">12</div>
                        <div class="stat-label">Uploads</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value">45</div>
                        <div class="stat-label">Downloads</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value">230</div>
                        <div class="stat-label">Points</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value">#45</div>
                        <div class="stat-label">Rank</div>
                    </div>
                </div>
            </section>

            <!-- Recommended Resources -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-star" style="color: var(--accent-orange); margin-right: 8px;"></i> Recommended For You</h3>
                    <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="resources-grid">
                    <!-- Resource Card 1 -->
                    <div class="resource-card">
                        <div class="resource-header">
                            <div class="resource-title">Operating Systems Complete Notes</div>
                            <div class="resource-meta">
                                <span>GTU • Sem 5</span>
                                <span><i class="fas fa-star" style="color: #F1C40F;"></i> 4.5</span>
                            </div>
                        </div>
                        <div class="resource-body">
                            <div class="resource-description">
                                Comprehensive notes covering all OS topics - processes, threads, synchronization, memory management, file systems.
                            </div>
                        </div>
                        <div class="resource-footer">
                            <div class="resource-actions">
                                <button class="btn btn-outline" style="padding: 0.3rem 0.6rem;"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-primary" style="padding: 0.3rem 0.6rem;"><i class="fas fa-download"></i></button>
                            </div>
                            <span style="font-size: 0.85rem; color: var(--dark-gray);">PDF • 2.4 MB</span>
                        </div>
                    </div>
                    
                    <!-- Resource Card 2 -->
                    <div class="resource-card">
                        <div class="resource-header">
                            <div class="resource-title">Database Management 2023 Paper</div>
                            <div class="resource-meta">
                                <span>AU • Sem 4</span>
                                <span><i class="fas fa-star" style="color: #F1C40F;"></i> 4.2</span>
                            </div>
                        </div>
                        <div class="resource-body">
                            <div class="resource-description">
                                End semester question paper with solved answers. Covers normalization, SQL queries, transactions, and indexing.
                            </div>
                        </div>
                        <div class="resource-footer">
                            <div class="resource-actions">
                                <button class="btn btn-outline" style="padding: 0.3rem 0.6rem;"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-primary" style="padding: 0.3rem 0.6rem;"><i class="fas fa-download"></i></button>
                            </div>
                            <span style="font-size: 0.85rem; color: var(--dark-gray);">PDF • 1.8 MB</span>
                        </div>
                    </div>
                    
                    <!-- Resource Card 3 -->
                    <div class="resource-card">
                        <div class="resource-header">
                            <div class="resource-title">Computer Networks Lab Manual</div>
                            <div class="resource-meta">
                                <span>Nirma • Sem 6</span>
                                <span><i class="fas fa-star" style="color: #F1C40F;"></i> 4.7</span>
                            </div>
                        </div>
                        <div class="resource-body">
                            <div class="resource-description">
                                Complete lab manual with experiments on socket programming, routing algorithms, network simulation.
                            </div>
                        </div>
                        <div class="resource-footer">
                            <div class="resource-actions">
                                <button class="btn btn-outline" style="padding: 0.3rem 0.6rem;"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-primary" style="padding: 0.3rem 0.6rem;"><i class="fas fa-download"></i></button>
                            </div>
                            <span style="font-size: 0.85rem; color: var(--dark-gray);">PDF • 3.1 MB</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Ahmedabad Universities -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-university" style="color: var(--primary-blue); margin-right: 8px;"></i> Ahmedabad Universities</h3>
                    <a href="#" class="view-all">All Universities <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="university-grid">
                    <div class="university-card">
                        <div class="university-logo">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="university-name">GTU</div>
                        <div style="font-size: 0.85rem; color: var(--dark-gray); margin-top: 0.3rem;">4,230 resources</div>
                    </div>
                    
                    <div class="university-card">
                        <div class="university-logo">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="university-name">Ahmedabad University</div>
                        <div style="font-size: 0.85rem; color: var(--dark-gray); margin-top: 0.3rem;">2,150 resources</div>
                    </div>
                    
                    <div class="university-card">
                        <div class="university-logo">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="university-name">Nirma University</div>
                        <div style="font-size: 0.85rem; color: var(--dark-gray); margin-top: 0.3rem;">1,870 resources</div>
                    </div>
                    
                    <div class="university-card">
                        <div class="university-logo">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="university-name">DA-IICT</div>
                        <div style="font-size: 0.85rem; color: var(--dark-gray); margin-top: 0.3rem;">1,540 resources</div>
                    </div>
                </div>
            </section>
            
            <!-- Recent Activity -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-history" style="color: var(--accent-green); margin-right: 8px;"></i> Recent Activity</h3>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 0.8rem; background-color: #f8f9fa; border-radius: var(--radius);">
                        <div style="background-color: #e8f4ff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-upload" style="color: var(--accent-green);"></i>
                        </div>
                        <div>
                            <div style="font-weight: 500;">You uploaded "Data Structures Notes"</div>
                            <div style="font-size: 0.85rem; color: var(--dark-gray);">2 hours ago • Pending approval</div>
                        </div>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 0.8rem; background-color: #f8f9fa; border-radius: var(--radius);">
                        <div style="background-color: #fff0e6; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-download" style="color: var(--accent-orange);"></i>
                        </div>
                        <div>
                            <div style="font-weight: 500;">You downloaded "Microprocessors PPT"</div>
                            <div style="font-size: 0.85rem; color: var(--dark-gray);">1 day ago • Rated 5 stars</div>
                        </div>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 0.8rem; background-color: #f8f9fa; border-radius: var(--radius);">
                        <div style="background-color: #e6f7ff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-comment" style="color: var(--secondary-blue);"></i>
                        </div>
                        <div>
                            <div style="font-weight: 500;">You commented on "CN Lab Solutions"</div>
                            <div style="font-size: 0.85rem; color: var(--dark-gray);">2 days ago • "Very helpful, thanks!"</div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Upload Wizard Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload Resource</h3>
                <button class="close-modal" onclick="closeUploadWizard()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="upload-wizard">
                    <div class="wizard-header">
                        <h2 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Share Your Knowledge</h2>
                        <p style="color: var(--dark-gray);">Upload notes, question papers, or other academic resources</p>
                    </div>
                    
                    <div class="wizard-steps">
                        <div class="wizard-step">
                            <div class="step-circle active">1</div>
                            <div class="step-title active">Select Type</div>
                        </div>
                        <div class="wizard-step">
                            <div class="step-circle">2</div>
                            <div class="step-title">Academic Details</div>
                        </div>
                        <div class="wizard-step">
                            <div class="step-circle">3</div>
                            <div class="step-title">Upload File</div>
                        </div>
                        <div class="wizard-step">
                            <div class="step-circle">4</div>
                            <div class="step-title">Add Details</div>
                        </div>
                        <div class="wizard-step">
                            <div class="step-circle">5</div>
                            <div class="step-title">Submit</div>
                        </div>
                    </div>
                    
                    <div class="wizard-content">
                        <h3 style="margin-bottom: 1rem; color: var(--primary-blue);">Select Resource Type</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                            <div class="resource-type-option" onclick="selectResourceType('notes')" style="border: 2px solid var(--accent-green); padding: 1.5rem; text-align: center; border-radius: var(--radius); cursor: pointer; background-color: #f0f7ff;">
                                <i class="fas fa-file-alt" style="font-size: 2rem; color: var(--accent-green); margin-bottom: 0.5rem;"></i>
                                <div style="font-weight: 600;">Notes</div>
                            </div>
                            
                            <div class="resource-type-option" onclick="selectResourceType('paper')" style="border: 2px solid var(--medium-gray); padding: 1.5rem; text-align: center; border-radius: var(--radius); cursor: pointer;">
                                <i class="fas fa-file-pdf" style="font-size: 2rem; color: var(--medium-gray); margin-bottom: 0.5rem;"></i>
                                <div style="font-weight: 600;">Question Paper</div>
                            </div>
                            
                            <div class="resource-type-option" onclick="selectResourceType('presentation')" style="border: 2px solid var(--medium-gray); padding: 1.5rem; text-align: center; border-radius: var(--radius); cursor: pointer;">
                                <i class="fas fa-file-powerpoint" style="font-size: 2rem; color: var(--medium-gray); margin-bottom: 0.5rem;"></i>
                                <div style="font-weight: 600;">Presentation</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="wizard-footer">
                        <button class="btn btn-outline" onclick="closeUploadWizard()">Cancel</button>
                        <button class="btn btn-primary" onclick="nextWizardStep()">Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Panel Modal -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Admin Panel</h3>
                <button class="close-modal" onclick="closeAdminPanel()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="admin-dashboard">
                    <div class="admin-stat">
                        <h4>Total Users</h4>
                        <div class="value">10,245</div>
                    </div>
                    <div class="admin-stat">
                        <h4>Total Resources</h4>
                        <div class="value">45,678</div>
                    </div>
                    <div class="admin-stat alert">
                        <h4>Pending Moderation</h4>
                        <div class="value">124</div>
                    </div>
                    <div class="admin-stat">
                        <h4>Today's Uploads</h4>
                        <div class="value">342</div>
                    </div>
                </div>
                
                <h3 style="margin: 2rem 0 1rem; color: var(--primary-blue);">Moderation Queue</h3>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Resource</th>
                            <th>Uploader</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>OS Notes - Sem 5</td>
                            <td>Rahul Patel</td>
                            <td>2 hours ago</td>
                            <td>
                                <button class="btn btn-primary" style="padding: 0.3rem 0.6rem; margin-right: 0.5rem;"><i class="fas fa-check"></i></button>
                                <button class="btn" style="background-color: var(--accent-red); color: white; padding: 0.3rem 0.6rem;"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>DBMS 2023 Paper</td>
                            <td>Priya Sharma</td>
                            <td>3 hours ago</td>
                            <td>
                                <button class="btn btn-primary" style="padding: 0.3rem 0.6rem; margin-right: 0.5rem;"><i class="fas fa-check"></i></button>
                                <button class="btn" style="background-color: var(--accent-red); color: white; padding: 0.3rem 0.6rem;"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>CN Lab Manual</td>
                            <td>Amit Kumar</td>
                            <td>5 hours ago</td>
                            <td>
                                <button class="btn btn-primary" style="padding: 0.3rem 0.6rem; margin-right: 0.5rem;"><i class="fas fa-check"></i></button>
                                <button class="btn" style="background-color: var(--accent-red); color: white; padding: 0.3rem 0.6rem;"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                    <button class="btn btn-secondary" onclick="closeAdminPanel()">Close Admin Panel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>Notestation</h4>
                <p style="color: var(--light-gray); margin-bottom: 1rem;">Ahmedabad's premier academic resource sharing platform for students and faculty.</p>
                <div style="display: flex; gap: 1rem; font-size: 1.2rem;">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Universities</h4>
                <ul>
                    <li><a href="#">Gujarat Technological University</a></li>
                    <li><a href="#">Ahmedabad University</a></li>
                    <li><a href="#">Nirma University</a></li>
                    <li><a href="#">DA-IICT</a></li>
                    <li><a href="#">LD College of Engineering</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Resources</h4>
                <ul>
                    <li><a href="#">Notes</a></li>
                    <li><a href="#">Question Papers</a></li>
                    <li><a href="#">Presentations</a></li>
                    <li><a href="#">Lab Manuals</a></li>
                    <li><a href="#">Books & References</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Community Guidelines</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            &copy; 2023 Notestation. All rights reserved. Designed for Ahmedabad's academic community.
        </div>
    </footer>

    <script>
        // Open Upload Wizard
        function openUploadWizard() {
            document.getElementById('uploadModal').style.display = 'flex';
        }
        
        // Close Upload Wizard
        function closeUploadWizard() {
            document.getElementById('uploadModal').style.display = 'none';
        }
        
        // Open Admin Panel (hidden feature for demo)
        function openAdminPanel() {
            document.getElementById('adminModal').style.display = 'flex';
        }
        
        // Close Admin Panel
        function closeAdminPanel() {
            document.getElementById('adminModal').style.display = 'none';
        }
        
        // Select Resource Type in Upload Wizard
        function selectResourceType(type) {
            const options = document.querySelectorAll('.resource-type-option');
            options.forEach(option => {
                option.style.borderColor = 'var(--medium-gray)';
                option.style.backgroundColor = 'transparent';
                const icon = option.querySelector('i');
                icon.style.color = 'var(--medium-gray)';
            });
            
            const selectedOption = event.currentTarget;
            selectedOption.style.borderColor = 'var(--accent-green)';
            selectedOption.style.backgroundColor = '#f0f7ff';
            const selectedIcon = selectedOption.querySelector('i');
            selectedIcon.style.color = 'var(--accent-green)';
            
            console.log('Selected resource type:', type);
        }
        
        // Next step in wizard
        function nextWizardStep() {
            alert('In a real application, this would proceed to the next step of the upload wizard.');
        }
        
        // Simulate login (demo only)
        function simulateLogin() {
            alert('Welcome to Notestation! In a real app, this would be a proper login system.');
        }
        
        // Initialize with some interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Make notification bell clickable
            document.querySelector('.notification').addEventListener('click', function() {
                alert('You have 3 notifications:\n1. Your upload was approved\n2. New resource in your course\n3. Weekly digest available');
            });
            
            // Make user profile clickable
            document.querySelector('.user-profile').addEventListener('click', function() {
                alert('User Profile Menu:\n• View Profile\n• My Uploads\n• Settings\n• Logout');
            });
            
            // Admin access (hidden - double click logo)
            let clickCount = 0;
            let lastClickTime = 0;
            document.querySelector('.logo').addEventListener('click', function() {
                const currentTime = new Date().getTime();
                if (currentTime - lastClickTime < 500) {
                    clickCount++;
                } else {
                    clickCount = 1;
                }
                lastClickTime = currentTime;
                
                if (clickCount === 2) {
                    openAdminPanel();
                    clickCount = 0;
                }
            });
            
            // Search functionality
            document.querySelector('.search-bar input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const query = this.value;
                    if (query.trim()) {
                        alert(`Searching for: "${query}"\nIn a real app, this would show search results.`);
                    }
                }
            });
            
            // Download buttons
            const downloadButtons = document.querySelectorAll('.btn-primary');
            downloadButtons.forEach(button => {
                if (button.textContent.includes('Download')) {
                    button.addEventListener('click', function() {
                        alert('Download started! In a real app, this would download the file.');
                    });
                }
            });
            
            // Preview buttons
            const previewButtons = document.querySelectorAll('.btn-outline');
            previewButtons.forEach(button => {
                if (button.textContent.includes('eye')) {
                    button.addEventListener('click', function() {
                        alert('Opening preview... In a real app, this would show a document preview.');
                    });
                }
            });
        });
    </script>
</body>
</html>