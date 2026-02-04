<?php
/**
 * Header Template
 */
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}

startSecureSession();
checkSessionTimeout();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
    
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo htmlspecialchars($css); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php if (isLoggedIn()): ?>
        <?php if (hasRole(ROLE_ADMIN) || hasRole(ROLE_REGISTRAR)): ?>
            <!-- Admin Layout -->
            <div class="admin-wrapper">
                <!-- Sidebar Overlay -->
                <div class="sidebar-overlay" id="sidebarOverlay"></div>
                
                <!-- Sidebar -->
                <aside class="sidebar" id="sidebar">
                    <div class="sidebar-header">
                        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="sidebar-brand">
                            <i class="bi bi-mortarboard-fill me-2"></i> REGISTRAR
                        </a>
                    </div>
                    <div class="sidebar-menu">
                        <div class="menu-label">Main Console</div>
                        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="nav-link-item <?php echo strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        
                        <div class="menu-label mt-4">Management</div>
                        <a href="<?php echo BASE_URL; ?>/admin/students.php" class="nav-link-item <?php echo strpos($_SERVER['PHP_SELF'], 'students.php') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i> Students
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/courses.php" class="nav-link-item <?php echo strpos($_SERVER['PHP_SELF'], 'courses.php') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-book"></i> Courses
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/enrollments.php" class="nav-link-item <?php echo strpos($_SERVER['PHP_SELF'], 'enrollments.php') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-list-check"></i> Enrollments
                        </a>
                        
                        <div class="menu-label mt-4">Analytics</div>
                        <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="nav-link-item <?php echo strpos($_SERVER['PHP_SELF'], 'reports.php') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                        
                        <div class="mt-5 pt-5 px-3">
                            <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="btn btn-outline-light btn-sm w-100 rounded-pill">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </aside>
                
                <!-- Main Panel -->
                <main class="main-panel">
                    <!-- Top Bar -->
                    <header class="top-bar">
                        <div class="d-flex align-items-center">
                            <button class="sidebar-toggle btn btn-link text-dark me-3" type="button" id="adminSidebarToggle">
                                <i class="bi bi-list fs-4"></i>
                            </button>
                            <div class="top-bar-title">
                                <?php echo $pageTitle; ?>
                            </div>
                        </div>
                        <div class="top-bar-user d-flex align-items-center">
                            <div class="me-3 text-end d-none d-md-block">
                                <div class="fw-bold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?php echo ucfirst($_SESSION['user_role'] ?? 'Staff'); ?></div>
                            </div>
                            <div class="dropdown">
                                <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" id="userMenu" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle fs-3 text-primary"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenu">
                                    <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </header>
                    <div class="page-container">
        <?php else: ?>
            <!-- Student Premium Navbar -->
            <nav class="student-navbar">
                <div class="container">
                    <div class="navbar-content">
                        <!-- Logo/Brand -->
                        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/index.php">
                            <div class="brand-logo">
                                <i class="bi bi-mortarboard-fill"></i>
                            </div>
                            <span class="brand-text"><?php echo APP_NAME; ?></span>
                        </a>
                        
                        <!-- Mobile Toggle -->
                        <button class="mobile-toggle" type="button" id="mobileMenuToggle">
                            <span class="toggle-line"></span>
                            <span class="toggle-line"></span>
                            <span class="toggle-line"></span>
                        </button>
                        
                        <!-- Navigation Menu -->
                        <div class="navbar-menu" id="navbarMenu">
                            <ul class="nav-links">
                                <li class="nav-item">
                                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? 'active' : ''; ?>" 
                                       href="<?php echo BASE_URL; ?>/student/dashboard.php">
                                        <i class="bi bi-house-door-fill"></i>
                                        <span>Dashboard</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'courses.php') !== false ? 'active' : ''; ?>" 
                                       href="<?php echo BASE_URL; ?>/student/courses.php">
                                        <i class="bi bi-book-fill"></i>
                                        <span>Courses</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'enrollments.php') !== false ? 'active' : ''; ?>" 
                                       href="<?php echo BASE_URL; ?>/student/enrollments.php">
                                        <i class="bi bi-list-check"></i>
                                        <span>Enrollments</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'profile.php') !== false ? 'active' : ''; ?>" 
                                       href="<?php echo BASE_URL; ?>/student/profile.php">
                                        <i class="bi bi-person-fill"></i>
                                        <span>Profile</span>
                                    </a>
                                </li>
                            </ul>
                            
                            <!-- User Menu -->
                            <div class="user-menu">
                                <div class="user-dropdown">
                                    <button class="user-btn" id="userDropdownBtn">
                                        <div class="user-avatar">
                                            <i class="bi bi-person-circle"></i>
                                        </div>
                                        <div class="user-info">
                                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                                            <span class="user-role">Student</span>
                                        </div>
                                        <i class="bi bi-chevron-down dropdown-icon"></i>
                                    </button>
                                    <div class="dropdown-menu-custom" id="userDropdownMenu">
                                        <a href="<?php echo BASE_URL; ?>/student/profile.php" class="dropdown-item-custom">
                                            <i class="bi bi-person"></i>
                                            <span>My Profile</span>
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="dropdown-item-custom logout">
                                            <i class="bi bi-box-arrow-right"></i>
                                            <span>Logout</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            
            <style>
            /* Premium Student Navbar */
            .student-navbar {
                background: #fff;
                box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
                position: sticky;
                top: 0;
                z-index: 1000;
                padding: 0.75rem 0;
                transition: all 0.3s ease;
            }
            
            .student-navbar.scrolled {
                box-shadow: 0 4px 30px rgba(0, 0, 0, 0.12);
            }
            
            .navbar-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 2rem;
            }
            
            /* Brand */
            .navbar-brand {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                text-decoration: none;
                transition: all 0.3s ease;
            }
            
            .brand-logo {
                width: 45px;
                height: 45px;
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 1.5rem;
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
                transition: all 0.3s ease;
            }
            
            .navbar-brand:hover .brand-logo {
                transform: scale(1.05);
                box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
            }
            
            .brand-text {
                font-size: 1.25rem;
                font-weight: 800;
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            /* Mobile Toggle */
            .mobile-toggle {
                display: none;
                flex-direction: column;
                gap: 5px;
                background: none;
                border: none;
                padding: 0.5rem;
                cursor: pointer;
            }
            
            .toggle-line {
                width: 25px;
                height: 3px;
                background: var(--slate-700);
                border-radius: 2px;
                transition: all 0.3s ease;
            }
            
            .mobile-toggle.active .toggle-line:nth-child(1) {
                transform: rotate(45deg) translateY(8px);
            }
            
            .mobile-toggle.active .toggle-line:nth-child(2) {
                opacity: 0;
            }
            
            .mobile-toggle.active .toggle-line:nth-child(3) {
                transform: rotate(-45deg) translateY(-8px);
            }
            
            /* Navigation Menu */
            .navbar-menu {
                display: flex;
                align-items: center;
                gap: 2rem;
                flex: 1;
            }
            
            .nav-links {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                list-style: none;
                margin: 0;
                padding: 0;
                flex: 1;
            }
            
            .nav-item {
                position: relative;
            }
            
            .nav-link {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1.25rem;
                border-radius: 10px;
                text-decoration: none;
                color: var(--slate-600);
                font-weight: 600;
                font-size: 0.95rem;
                transition: all 0.3s ease;
                position: relative;
            }
            
            .nav-link i {
                font-size: 1.1rem;
            }
            
            .nav-link:hover {
                background: var(--slate-50);
                color: var(--primary);
            }
            
            .nav-link.active {
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                color: #fff;
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            }
            
            /* User Menu */
            .user-menu {
                position: relative;
            }
            
            .user-dropdown {
                position: relative;
            }
            
            .user-btn {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.5rem 1rem;
                background: var(--slate-50);
                border: 2px solid var(--border-color);
                border-radius: 12px;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .user-btn:hover {
                border-color: var(--primary);
                background: #fff;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 1.5rem;
            }
            
            .user-info {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-name {
                font-weight: 700;
                color: var(--slate-900);
                font-size: 0.9rem;
            }
            
            .user-role {
                font-size: 0.75rem;
                color: var(--slate-500);
            }
            
            .dropdown-icon {
                color: var(--slate-400);
                transition: transform 0.3s ease;
            }
            
            .user-dropdown.active .dropdown-icon {
                transform: rotate(180deg);
            }
            
            /* Dropdown Menu */
            .dropdown-menu-custom {
                position: absolute;
                top: calc(100% + 0.5rem);
                right: 0;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                min-width: 220px;
                opacity: 0;
                visibility: hidden;
                transform: translateY(-10px);
                transition: all 0.3s ease;
                z-index: 100;
                overflow: hidden;
            }
            
            .user-dropdown.active .dropdown-menu-custom {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            
            .dropdown-item-custom {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.875rem 1.25rem;
                color: var(--slate-700);
                text-decoration: none;
                transition: all 0.2s ease;
            }
            
            .dropdown-item-custom:hover {
                background: var(--slate-50);
                color: var(--primary);
            }
            
            .dropdown-item-custom.logout {
                color: #ef4444;
            }
            
            .dropdown-item-custom.logout:hover {
                background: rgba(239, 68, 68, 0.1);
                color: #dc2626;
            }
            
            .dropdown-divider {
                height: 1px;
                background: var(--border-color);
                margin: 0.5rem 0;
            }
            
            /* Responsive */
            @media (max-width: 991px) {
                .mobile-toggle {
                    display: flex;
                }
                
                .navbar-menu {
                    position: fixed;
                    top: 70px;
                    left: 0;
                    right: 0;
                    background: #fff;
                    flex-direction: column;
                    align-items: stretch;
                    padding: 1.5rem;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                    transform: translateY(-100%);
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.3s ease;
                }
                
                .navbar-menu.active {
                    transform: translateY(0);
                    opacity: 1;
                    visibility: visible;
                }
                
                .nav-links {
                    flex-direction: column;
                    gap: 0.5rem;
                    width: 100%;
                }
                
                .nav-link {
                    width: 100%;
                    justify-content: flex-start;
                }
                
                .user-menu {
                    width: 100%;
                    margin-top: 1rem;
                    padding-top: 1rem;
                    border-top: 1px solid var(--border-color);
                }
                
                .user-btn {
                    width: 100%;
                    justify-content: space-between;
                }
                
                .dropdown-menu-custom {
                    position: static;
                    opacity: 1;
                    visibility: visible;
                    transform: none;
                    box-shadow: none;
                    margin-top: 0.5rem;
                    display: none;
                }
                
                .user-dropdown.active .dropdown-menu-custom {
                    display: block;
                }
            }
            
            @media (max-width: 576px) {
                .brand-text {
                    display: none;
                }
                
                .user-info {
                    display: none;
                }
            }
            </style>
            
            <script>
            // User dropdown toggle
            document.getElementById('userDropdownBtn')?.addEventListener('click', function(e) {
                e.stopPropagation();
                this.closest('.user-dropdown').classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                const dropdown = document.querySelector('.user-dropdown');
                if (dropdown && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('active');
                }
            });
            
            // Mobile menu toggle
            document.getElementById('mobileMenuToggle')?.addEventListener('click', function() {
                this.classList.toggle('active');
                document.getElementById('navbarMenu').classList.toggle('active');
            });
            
            // Navbar scroll effect
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.student-navbar');
                if (window.scrollY > 50) {
                    navbar?.classList.add('scrolled');
                } else {
                    navbar?.classList.remove('scrolled');
                }
            });
            </script>
            
            <div class="container py-4">
        <?php endif; ?>
    <?php endif; ?>

