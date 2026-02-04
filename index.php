<?php
/**
 * Home/Index Page
 */

require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth_functions.php';

startSecureSession();

// Redirect based on user role if logged in
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    if ($role === ROLE_STUDENT) {
        header('Location: ' . BASE_URL . '/student/dashboard.php');
    } elseif ($role === ROLE_ADMIN || $role === ROLE_REGISTRAR) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    }
    exit;
}

$pageTitle = APP_NAME;
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section with Premium Design -->
<div class="landing-hero">
    <div class="container">
        <div class="row align-items-center min-vh-100 py-5">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="hero-content">
                    <div class="hero-badge mb-4">
                        <i class="bi bi-mortarboard-fill me-2"></i>
                        <span>Education Excellence</span>
                    </div>
                    <h1 class="hero-title mb-4">
                        Welcome to<br>
                        <span class="gradient-text"><?php echo APP_NAME; ?></span>
                    </h1>
                    <p class="hero-subtitle mb-5">
                        Your gateway to seamless student registration and course enrollment. 
                        Manage your academic journey with our modern, secure, and user-friendly platform.
                    </p>
                    <div class="hero-buttons">
                        <a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-primary btn-lg hero-btn">
                            <i class="bi bi-person-plus me-2"></i>
                            Get Started
                        </a>
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-outline-primary btn-lg hero-btn">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Sign In
                        </a>
                    </div>
                    
                    <!-- Stats Section -->
                    <div class="hero-stats mt-5">
                        <div class="row g-4">
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="stat-number">1000+</h3>
                                    <p class="stat-label">Students</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="stat-number">150+</h3>
                                    <p class="stat-label">Courses</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="stat-number">99%</h3>
                                    <p class="stat-label">Satisfaction</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="hero-illustration">
                    <div class="illustration-card card-1">
                        <i class="bi bi-book-fill"></i>
                        <span>Browse Courses</span>
                    </div>
                    <div class="illustration-card card-2">
                        <i class="bi bi-calendar-check-fill"></i>
                        <span>Enroll Online</span>
                    </div>
                    <div class="illustration-card card-3">
                        <i class="bi bi-trophy-fill"></i>
                        <span>Track Progress</span>
                    </div>
                    <div class="hero-circle circle-1"></div>
                    <div class="hero-circle circle-2"></div>
                    <div class="hero-circle circle-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="features-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Why Choose Us?</h2>
            <p class="section-subtitle">Everything you need for a seamless academic experience</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon icon-primary">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <h4 class="feature-title">Student Portal</h4>
                    <p class="feature-description">
                        Complete registration process, manage your profile, and access all your academic information in one place.
                    </p>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Easy Registration</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Profile Management</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Real-time Updates</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="feature-card featured">
                    <div class="featured-badge">Most Popular</div>
                    <div class="feature-icon icon-success">
                        <i class="bi bi-book-fill"></i>
                    </div>
                    <h4 class="feature-title">Course Management</h4>
                    <p class="feature-description">
                        Browse extensive course catalog, view detailed schedules, and manage all your enrollments effortlessly.
                    </p>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Course Catalog</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Smart Enrollment</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Schedule Viewer</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon icon-info">
                        <i class="bi bi-shield-check-fill"></i>
                    </div>
                    <h4 class="feature-title">Secure & Reliable</h4>
                    <p class="feature-description">
                        Your data is protected with industry-standard security measures and encrypted connections.
                    </p>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Data Encryption</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Secure Authentication</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Privacy Protection</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="cta-section">
    <div class="container">
        <div class="cta-card">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <h2 class="cta-title">Ready to Start Your Journey?</h2>
                    <p class="cta-description">
                        Join thousands of students who are already managing their academic life with ease.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-light btn-lg px-5">
                        <i class="bi bi-rocket-takeoff me-2"></i>
                        Register Now
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Landing Page Premium Styles */
.landing-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow: hidden;
}

.landing-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 800px;
    height: 800px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    animation: float 20s ease-in-out infinite;
}

.landing-hero::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -10%;
    width: 600px;
    height: 600px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 50%;
    animation: float 15s ease-in-out infinite reverse;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 50px;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    animation: fadeInUp 0.8s ease-out;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.2;
    animation: fadeInUp 0.8s ease-out 0.2s both;
}

.gradient-text {
    background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.8;
    animation: fadeInUp 0.8s ease-out 0.4s both;
}

.hero-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    animation: fadeInUp 0.8s ease-out 0.6s both;
}

.hero-btn {
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.hero-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.hero-stats {
    animation: fadeInUp 0.8s ease-out 0.8s both;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin: 0;
}

/* Hero Illustration */
.hero-illustration {
    position: relative;
    height: 500px;
    animation: fadeIn 1s ease-out 0.5s both;
}

.illustration-card {
    position: absolute;
    background: #fff;
    padding: 1.5rem 2rem;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 1rem;
    animation: float 6s ease-in-out infinite;
}

.illustration-card i {
    font-size: 2rem;
    color: var(--primary);
}

.illustration-card span {
    font-weight: 600;
    color: var(--slate-900);
}

.card-1 {
    top: 10%;
    left: 10%;
    animation-delay: 0s;
}

.card-2 {
    top: 40%;
    right: 10%;
    animation-delay: 1s;
}

.card-3 {
    bottom: 15%;
    left: 20%;
    animation-delay: 2s;
}

.hero-circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    animation: pulse 4s ease-in-out infinite;
}

.circle-1 {
    width: 200px;
    height: 200px;
    top: 5%;
    right: 15%;
}

.circle-2 {
    width: 150px;
    height: 150px;
    bottom: 20%;
    right: 5%;
    animation-delay: 1s;
}

.circle-3 {
    width: 100px;
    height: 100px;
    top: 50%;
    left: 5%;
    animation-delay: 2s;
}

/* Features Section */
.features-section {
    background: var(--bg-main);
}

.section-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--slate-900);
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.25rem;
    color: var(--slate-600);
}

.feature-card {
    background: #fff;
    padding: 2.5rem;
    border-radius: 20px;
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
    border-color: var(--primary);
}

.feature-card.featured {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    transform: scale(1.05);
}

.feature-card.featured:hover {
    transform: scale(1.08) translateY(-10px);
}

.featured-badge {
    position: absolute;
    top: -15px;
    right: 20px;
    background: #fbbf24;
    color: #000;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
}

.feature-icon {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
}

.icon-primary {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: #fff;
}

.icon-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
}

.icon-info {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: #fff;
}

.feature-card.featured .feature-icon {
    background: rgba(255, 255, 255, 0.2);
}

.feature-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--slate-900);
}

.feature-card.featured .feature-title {
    color: #fff;
}

.feature-description {
    color: var(--slate-600);
    line-height: 1.8;
    margin-bottom: 1.5rem;
}

.feature-card.featured .feature-description {
    color: rgba(255, 255, 255, 0.9);
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-list li {
    padding: 0.5rem 0;
    color: var(--slate-700);
}

.feature-card.featured .feature-list li {
    color: rgba(255, 255, 255, 0.95);
}

/* CTA Section */
.cta-section {
    padding: 5rem 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.cta-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    padding: 3rem;
}

.cta-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 1rem;
}

.cta-description {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.3; }
    50% { transform: scale(1.1); opacity: 0.5; }
}

/* Responsive */
@media (max-width: 991px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-illustration {
        height: 400px;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .cta-title {
        font-size: 2rem;
    }
}

@media (max-width: 767px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1rem;
    }
    
    .hero-buttons {
        flex-direction: column;
    }
    
    .hero-btn {
        width: 100%;
    }
    
    .hero-illustration {
        display: none;
    }
    
    .cta-card {
        padding: 2rem;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>

