<?php
/**
 * Student/Admin Login Page
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/validation_functions.php';
require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    if ($role === ROLE_STUDENT) {
        header('Location: ' . BASE_URL . '/student/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    }
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Check if it's an admin/registrar first (they can use username or email)
            $stmt = $conn->prepare("SELECT user_id, username, email, password_hash, role, status FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account has been deactivated.';
                } else {
                    loginUser($user['user_id'], $user['username'], $user['role'], $user['email']);
                    header('Location: ' . BASE_URL . '/admin/dashboard.php');
                    exit;
                }
            } else {
                // Check if it's a student (they use email)
                // Only check if input looks like an email
                if (validateEmail($email)) {
                    $stmt = $conn->prepare("SELECT student_id, email, password_hash, status, email_verified FROM students WHERE email = ?");
                    $stmt->execute([$email]);
                    $student = $stmt->fetch();
                    
                    if ($student && verifyPassword($password, $student['password_hash'])) {
                        if ($student['status'] !== 'active') {
                            $error = 'Your account has been deactivated. Please contact administration.';
                        } elseif (!$student['email_verified']) {
                            $error = 'Please verify your email before logging in.';
                        } else {
                            loginUser($student['student_id'], $student['email'], ROLE_STUDENT, $student['email']);
                            header('Location: ' . BASE_URL . '/student/dashboard.php');
                            exit;
                        }
                    } else {
                        $error = 'Invalid email/username or password.';
                    }
                } else {
                    $error = 'Invalid email/username or password.';
                }
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}

$pageTitle = 'Login - ' . APP_NAME;
$csrfToken = generateCSRFToken();

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center py-5">
            <div class="col-lg-10">
                <div class="auth-card">
                    <div class="row g-0">
                        <!-- Left Side - Branding -->
                        <div class="col-lg-6 auth-branding">
                            <div class="auth-branding-content">
                                <div class="auth-logo mb-4">
                                    <i class="bi bi-mortarboard-fill"></i>
                                </div>
                                <h2 class="auth-brand-title">Welcome Back!</h2>
                                <p class="auth-brand-subtitle">Sign in to continue your academic journey</p>
                                
                                <div class="auth-features mt-5">
                                    <div class="auth-feature-item">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Access Your Courses</span>
                                    </div>
                                    <div class="auth-feature-item">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Track Your Progress</span>
                                    </div>
                                    <div class="auth-feature-item">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Manage Enrollments</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Side - Login Form -->
                        <div class="col-lg-6">
                            <div class="auth-form-wrapper">
                                <div class="auth-form-header">
                                    <h3 class="auth-form-title">Sign In</h3>
                                    <p class="auth-form-subtitle">Enter your credentials to access your account</p>
                                </div>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-danger auth-alert">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($success): ?>
                                    <div class="alert alert-success auth-alert">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <?php echo htmlspecialchars($success); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="" id="loginForm" class="auth-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    
                                    <div class="form-group">
                                        <label for="email" class="form-label">
                                            <i class="bi bi-envelope me-2"></i>Email or Username
                                        </label>
                                        <input type="text" class="form-control auth-input" id="email" name="email" 
                                               placeholder="Enter your email or username" required autofocus>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="password" class="form-label">
                                            <i class="bi bi-lock me-2"></i>Password
                                        </label>
                                        <div class="password-input-wrapper">
                                            <input type="password" class="form-control auth-input" id="password" name="password" 
                                                   placeholder="Enter your password" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                                <i class="bi bi-eye" id="password-icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-options">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                            <label class="form-check-label" for="remember">Remember me</label>
                                        </div>
                                        <a href="<?php echo BASE_URL; ?>/auth/forgot-password.php" class="forgot-link">
                                            Forgot password?
                                        </a>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary auth-submit-btn">
                                        <span>Sign In</span>
                                        <i class="bi bi-arrow-right ms-2"></i>
                                    </button>
                                </form>
                                
                                <div class="auth-footer">
                                    <p>Don't have an account? 
                                        <a href="<?php echo BASE_URL; ?>/auth/register.php" class="auth-link">Register here</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Auth Page Styles */
.auth-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}

.auth-page::before {
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

.auth-card {
    background: #fff;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: fadeInUp 0.6s ease-out;
}

.auth-branding {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    padding: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.auth-branding::before {
    content: '';
    position: absolute;
    top: -30%;
    left: -20%;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.auth-branding-content {
    position: relative;
    z-index: 2;
    color: #fff;
}

.auth-logo {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
}

.auth-brand-title {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.auth-brand-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
}

.auth-features {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.auth-feature-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1.1rem;
}

.auth-feature-item i {
    font-size: 1.5rem;
}

.auth-form-wrapper {
    padding: 3rem;
}

.auth-form-header {
    margin-bottom: 2rem;
}

.auth-form-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--slate-900);
    margin-bottom: 0.5rem;
}

.auth-form-subtitle {
    color: var(--slate-600);
    font-size: 1rem;
}

.auth-alert {
    border-radius: 12px;
    padding: 1rem 1.25rem;
    border: none;
    display: flex;
    align-items: center;
}

.auth-form {
    margin-top: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: var(--slate-700);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.auth-input {
    padding: 0.875rem 1.25rem;
    border-radius: 12px;
    border: 2px solid var(--border-color);
    font-size: 1rem;
    transition: all 0.3s ease;
}

.auth-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px var(--primary-light);
}

.password-input-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--slate-500);
    cursor: pointer;
    padding: 0.5rem;
    transition: color 0.2s;
}

.password-toggle:hover {
    color: var(--primary);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.forgot-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}

.forgot-link:hover {
    color: var(--primary-hover);
}

.auth-submit-btn {
    width: 100%;
    padding: 1rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.1rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.auth-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
}

.auth-footer {
    text-align: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
}

.auth-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}

.auth-link:hover {
    color: var(--primary-hover);
}

@media (max-width: 991px) {
    .auth-branding {
        display: none;
    }
    
    .auth-form-wrapper {
        padding: 2rem;
    }
}

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
</style>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
