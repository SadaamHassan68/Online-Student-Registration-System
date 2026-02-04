<?php
/**
 * Student Registration Page
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/validation_functions.php';
require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';
$formData = [];

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        try {
            // Collect and sanitize form data
        $formData = [
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'secondary_phone' => sanitizeInput($_POST['secondary_phone'] ?? ''),
            'address' => sanitizeInput($_POST['address'] ?? ''),
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'intended_major' => sanitizeInput($_POST['intended_major'] ?? ''),
            'high_school_name' => sanitizeInput($_POST['high_school_name'] ?? ''),
            'high_school_grade' => $_POST['high_school_grade'] ?? '',
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
        ];
        
        // Validation
        $errors = [];
        
        if (empty($formData['first_name'])) {
            $errors[] = 'First name is required.';
        }
        
        if (empty($formData['last_name'])) {
            $errors[] = 'Last name is required.';
        }
        
        if (empty($formData['email']) || !validateEmail($formData['email'])) {
            $errors[] = 'Valid email is required.';
        }
        
        if (!empty($formData['phone']) && !validatePhone($formData['phone'])) {
            $errors[] = 'Invalid phone number format.';
        }
        
        if (!empty($formData['secondary_phone']) && !validatePhone($formData['secondary_phone'])) {
            $errors[] = 'Invalid secondary phone number format.';
        }
        
        if (!empty($formData['high_school_grade'])) {
            $grade = floatval($formData['high_school_grade']);
            if ($grade < 0 || $grade > 4.0) {
                $errors[] = 'High school grade/GPA must be between 0.0 and 4.0.';
            }
        }
        
        if (empty($formData['date_of_birth'])) {
            $errors[] = 'Date of birth is required.';
        } else {
            $dobValidation = validateDateOfBirth($formData['date_of_birth'], 16);
            if ($dobValidation !== true) {
                $errors[] = $dobValidation;
            }
        }
        
        if (empty($formData['gender'])) {
            $errors[] = 'Gender is required.';
        }
        
        if (empty($formData['password'])) {
            $errors[] = 'Password is required.';
        } else {
            $passwordValidation = validatePassword($formData['password']);
            if ($passwordValidation !== true) {
                $errors = array_merge($errors, $passwordValidation);
            }
        }
        
        if ($formData['password'] !== $formData['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($errors)) {
            try {
                $db = new Database();
                $conn = $db->getConnection();
                
                // Check if email already exists
                $stmt = $conn->prepare("SELECT student_id FROM students WHERE email = ?");
                $stmt->execute([$formData['email']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Email already registered. Please use a different email or login.';
                } else {
                    // Generate verification token
                    $verificationToken = bin2hex(random_bytes(32));
                    $verificationExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    
                    // Insert student
                    $stmt = $conn->prepare("
                        INSERT INTO students (first_name, last_name, email, phone, secondary_phone, address, date_of_birth, gender, intended_major, high_school_name, high_school_grade, password_hash, verification_token, verification_token_expiry)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $passwordHash = hashPassword($formData['password']);
                    $highSchoolGrade = !empty($formData['high_school_grade']) ? floatval($formData['high_school_grade']) : null;
                    
                    $stmt->execute([
                        $formData['first_name'],
                        $formData['last_name'],
                        $formData['email'],
                        $formData['phone'] ?: null,
                        $formData['secondary_phone'] ?: null,
                        $formData['address'] ?: null,
                        $formData['date_of_birth'],
                        $formData['gender'],
                        $formData['intended_major'] ?: null,
                        $formData['high_school_name'] ?: null,
                        $highSchoolGrade,
                        $passwordHash,
                        $verificationToken,
                        $verificationExpiry
                    ]);
                    
                    // TODO: Send verification email (implement email service)
                    // For now, we'll mark as verified for development
                    $studentId = $conn->lastInsertId();
                    $stmt = $conn->prepare("UPDATE students SET email_verified = TRUE WHERE student_id = ?");
                    $stmt->execute([$studentId]);
                    
                    $success = 'Registration successful! You can now login.';
                    $formData = []; // Clear form
                }
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                if ($e->getCode() == 23000) {
                    $errors[] = 'Email already registered.';
                } else {
                    $errors[] = 'An error occurred during registration. Please try again.';
                }
            }
        }
        
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        }
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        $error = 'An error occurred. Please try again later.';
    }
}
}

$pageTitle = 'Register - ' . APP_NAME;
$csrfToken = generateCSRFToken();

include __DIR__ . '/../includes/header.php';
?>

<div class="register-page-wrapper">
    <div class="register-background">
        <div class="bg-shape shape-1"></div>
        <div class="bg-shape shape-2"></div>
        <div class="bg-shape shape-3"></div>
    </div>
    
    <div class="container">
        <div class="register-container">
            <!-- Header -->
            <div class="register-header">
                <div class="register-logo">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h1 class="register-title">Create Your Account</h1>
                <p class="register-subtitle">Join thousands of students on their academic journey</p>
            </div>
            
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step active" data-step="1">
                    <div class="step-circle">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <span class="step-label">Personal</span>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="2">
                    <div class="step-circle">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <span class="step-label">Academic</span>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="3">
                    <div class="step-circle">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <span class="step-label">Security</span>
                </div>
            </div>
            
            <!-- Form Card -->
            <div class="register-card">
                <?php if ($error): ?>
                    <div class="alert alert-danger register-alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success register-alert">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <?php echo htmlspecialchars($success); ?>
                            <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-success mt-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <form method="POST" action="" id="registerForm" class="register-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <!-- Step 1: Personal Information -->
                    <div class="form-step active" data-step="1">
                        <h3 class="step-title">
                            <i class="bi bi-person-circle me-2"></i>
                            Personal Information
                        </h3>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group-modern">
                                    <label for="first_name">First Name <span class="required">*</span></label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-person"></i>
                                        <input type="text" class="form-input" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>" 
                                               placeholder="John" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="input-group-modern">
                                    <label for="last_name">Last Name <span class="required">*</span></label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-person"></i>
                                        <input type="text" class="form-input" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>" 
                                               placeholder="Doe" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="input-group-modern">
                                    <label for="email">Email Address <span class="required">*</span></label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-envelope"></i>
                                        <input type="email" class="form-input" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" 
                                               placeholder="john.doe@example.com" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="input-group-modern">
                                    <label for="phone">Phone Number</label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-telephone"></i>
                                        <input type="tel" class="form-input" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>" 
                                               placeholder="+1 234 567 8900">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="input-group-modern">
                                    <label for="secondary_phone">Secondary Phone</label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-telephone"></i>
                                        <input type="tel" class="form-input" id="secondary_phone" name="secondary_phone" 
                                               value="<?php echo htmlspecialchars($formData['secondary_phone'] ?? ''); ?>" 
                                               placeholder="+1 234 567 8900">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="input-group-modern">
                                    <label for="address">Address</label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-geo-alt"></i>
                                        <textarea class="form-input" id="address" name="address" rows="2" 
                                                  placeholder="123 Main Street, City, State"><?php echo htmlspecialchars($formData['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="input-group-modern">
                                    <label for="date_of_birth">Date of Birth <span class="required">*</span></label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-calendar"></i>
                                        <input type="date" class="form-input" id="date_of_birth" name="date_of_birth" 
                                               value="<?php echo htmlspecialchars($formData['date_of_birth'] ?? ''); ?>" 
                                               max="<?php echo date('Y-m-d', strtotime('-16 years')); ?>" required>
                                    </div>
                                    <small class="input-hint">Must be at least 16 years old</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="input-group-modern">
                                    <label for="gender">Gender <span class="required">*</span></label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-gender-ambiguous"></i>
                                        <select class="form-input" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male" <?php echo ($formData['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo ($formData['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo ($formData['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-navigation">
                            <button type="button" class="btn btn-primary btn-next" onclick="nextStep()">
                                Next Step <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 2: Academic Information -->
                    <div class="form-step" data-step="2">
                        <h3 class="step-title">
                            <i class="bi bi-mortarboard me-2"></i>
                            Academic Information
                        </h3>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="input-group-modern">
                                    <label for="intended_major">Intended Major</label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-book"></i>
                                        <select class="form-input" id="intended_major" name="intended_major">
                                            <option value="">Select Major (Optional)</option>
                                            <option value="Computer Science" <?php echo ($formData['intended_major'] ?? '') === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                            <option value="Information Technology" <?php echo ($formData['intended_major'] ?? '') === 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                                            <option value="Mathematics" <?php echo ($formData['intended_major'] ?? '') === 'Mathematics' ? 'selected' : ''; ?>>Mathematics</option>
                                            <option value="Engineering" <?php echo ($formData['intended_major'] ?? '') === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                            <option value="Business Administration" <?php echo ($formData['intended_major'] ?? '') === 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                                            <option value="Other" <?php echo ($formData['intended_major'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="input-group-modern">
                                    <label for="high_school_name">High School Name</label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-building"></i>
                                        <input type="text" class="form-input" id="high_school_name" name="high_school_name" 
                                               value="<?php echo htmlspecialchars($formData['high_school_name'] ?? ''); ?>" 
                                               placeholder="Your High School">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="input-group-modern">
                                    <label for="high_school_grade">High School GPA</label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-award"></i>
                                        <input type="number" class="form-input" id="high_school_grade" name="high_school_grade" 
                                               value="<?php echo htmlspecialchars($formData['high_school_grade'] ?? ''); ?>" 
                                               step="0.01" min="0" max="4.0" placeholder="3.50">
                                    </div>
                                    <small class="input-hint">GPA on a 4.0 scale</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-navigation">
                            <button type="button" class="btn btn-secondary btn-prev" onclick="prevStep()">
                                <i class="bi bi-arrow-left me-2"></i> Previous
                            </button>
                            <button type="button" class="btn btn-primary btn-next" onclick="nextStep()">
                                Next Step <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Security -->
                    <div class="form-step" data-step="3">
                        <h3 class="step-title">
                            <i class="bi bi-shield-lock me-2"></i>
                            Account Security
                        </h3>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="input-group-modern">
                                    <label for="password">Password <span class="required">*</span></label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-lock"></i>
                                        <input type="password" class="form-input" id="password" name="password" 
                                               placeholder="Create a strong password" required 
                                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                        <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
                                            <i class="bi bi-eye" id="password-icon"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength-bar">
                                        <div class="strength-indicator"></div>
                                    </div>
                                    <small class="input-hint">
                                        At least <?php echo PASSWORD_MIN_LENGTH; ?> characters with uppercase, lowercase, number, and special character
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="input-group-modern">
                                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-lock-fill"></i>
                                        <input type="password" class="form-input" id="confirm_password" name="confirm_password" 
                                               placeholder="Re-enter your password" required>
                                        <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')">
                                            <i class="bi bi-eye" id="confirm_password-icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-navigation">
                            <button type="button" class="btn btn-secondary btn-prev" onclick="prevStep()">
                                <i class="bi bi-arrow-left me-2"></i> Previous
                            </button>
                            <button type="submit" class="btn btn-success btn-submit">
                                <i class="bi bi-check-circle me-2"></i> Create Account
                            </button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
                
                <div class="register-footer">
                    <p>Already have an account? <a href="<?php echo BASE_URL; ?>/auth/login.php" class="register-link">Sign in here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Register Page Wrapper */
.register-page-wrapper {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow: hidden;
    padding: 2rem 0;
}

.register-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 0;
}

.bg-shape {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
    animation: float 20s ease-in-out infinite;
}

.shape-1 {
    width: 600px;
    height: 600px;
    top: -200px;
    right: -100px;
    animation-delay: 0s;
}

.shape-2 {
    width: 400px;
    height: 400px;
    bottom: -150px;
    left: -100px;
    animation-delay: 5s;
}

.shape-3 {
    width: 300px;
    height: 300px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation-delay: 10s;
}

.register-container {
    max-width: 700px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* Header */
.register-header {
    text-align: center;
    margin-bottom: 2rem;
    animation: fadeInDown 0.6s ease-out;
}

.register-logo {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: #fff;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.register-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 0.5rem;
}

.register-subtitle {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.9);
}

/* Progress Steps */
.progress-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2rem;
    animation: fadeInUp 0.6s ease-out 0.2s both;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    position: relative;
}

.step-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: rgba(255, 255, 255, 0.6);
    transition: all 0.3s ease;
}

.step.active .step-circle {
    background: #fff;
    border-color: #fff;
    color: var(--primary);
    box-shadow: 0 10px 30px rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.step-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    transition: all 0.3s ease;
}

.step.active .step-label {
    color: #fff;
}

.step-line {
    width: 80px;
    height: 3px;
    background: rgba(255, 255, 255, 0.2);
    margin: 0 1rem;
}

/* Register Card */
.register-card {
    background: #fff;
    border-radius: 24px;
    padding: 2.5rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: fadeInUp 0.6s ease-out 0.4s both;
}

.register-alert {
    border-radius: 12px;
    padding: 1.25rem;
    border: none;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.register-alert i {
    font-size: 1.5rem;
    flex-shrink: 0;
}

/* Form Steps */
.form-step {
    display: none;
}

.form-step.active {
    display: block;
    animation: slideIn 0.4s ease-out;
}

.step-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--slate-900);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
}

.step-title i {
    color: var(--primary);
}

/* Modern Input Groups */
.input-group-modern {
    margin-bottom: 1.25rem;
}

.input-group-modern label {
    display: block;
    font-weight: 600;
    color: var(--slate-700);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.required {
    color: #ef4444;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-wrapper > i {
    position: absolute;
    left: 1rem;
    color: var(--slate-400);
    font-size: 1.1rem;
    z-index: 2;
    transition: color 0.2s;
}

.form-input {
    width: 100%;
    padding: 0.875rem 1rem 0.875rem 3rem;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #fff;
}

.form-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px var(--primary-light);
    outline: none;
}

.form-input:focus + .input-wrapper > i {
    color: var(--primary);
}

.input-wrapper:has(.form-input:focus) > i {
    color: var(--primary);
}

textarea.form-input {
    resize: vertical;
    min-height: 80px;
}

.input-hint {
    display: block;
    margin-top: 0.375rem;
    font-size: 0.8rem;
    color: var(--slate-500);
}

/* Password Toggle */
.password-toggle-btn {
    position: absolute;
    right: 1rem;
    background: none;
    border: none;
    color: var(--slate-400);
    cursor: pointer;
    padding: 0.5rem;
    transition: color 0.2s;
    z-index: 2;
}

.password-toggle-btn:hover {
    color: var(--primary);
}

/* Password Strength */
.password-strength-bar {
    height: 4px;
    background: var(--slate-200);
    border-radius: 2px;
    margin-top: 0.5rem;
    overflow: hidden;
}

.strength-indicator {
    height: 100%;
    width: 0;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-weak {
    width: 33%;
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.strength-medium {
    width: 66%;
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.strength-strong {
    width: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
}

/* Form Navigation */
.form-navigation {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
}

.btn {
    padding: 0.875rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-primary {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    flex: 1;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
}

.btn-secondary {
    background: var(--slate-200);
    color: var(--slate-700);
}

.btn-secondary:hover {
    background: var(--slate-300);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
    flex: 1;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
}

/* Footer */
.register-footer {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.register-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}

.register-link:hover {
    color: var(--primary-hover);
}

/* Animations */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
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

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(5deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .register-title {
        font-size: 2rem;
    }
    
    .register-card {
        padding: 1.5rem;
    }
    
    .step-circle {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
    
    .step-line {
        width: 40px;
        margin: 0 0.5rem;
    }
    
    .step-label {
        font-size: 0.75rem;
    }
    
    .form-navigation {
        flex-direction: column;
    }
}
</style>

<script>
let currentStep = 1;

function nextStep() {
    const currentStepEl = document.querySelector(`.form-step[data-step="${currentStep}"]`);
    const inputs = currentStepEl.querySelectorAll('input[required], select[required], textarea[required]');
    
    let isValid = true;
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            input.style.borderColor = '';
        }
    });
    
    if (!isValid) {
        alert('Please fill in all required fields');
        return;
    }
    
    if (currentStep < 3) {
        document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.remove('active');
        document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
        
        currentStep++;
        
        document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.add('active');
        document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function prevStep() {
    if (currentStep > 1) {
        document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.remove('active');
        document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
        
        currentStep--;
        
        document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.add('active');
        document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

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

// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const indicator = document.querySelector('.strength-indicator');
    
    if (password.length === 0) {
        indicator.className = 'strength-indicator';
        indicator.style.width = '0';
        return;
    }
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
    indicator.className = 'strength-indicator';
    if (strength <= 2) {
        indicator.classList.add('strength-weak');
    } else if (strength === 3) {
        indicator.classList.add('strength-medium');
    } else {
        indicator.classList.add('strength-strong');
    }
});

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    if (this.value !== password) {
        this.setCustomValidity('Passwords do not match');
        this.style.borderColor = '#ef4444';
    } else {
        this.setCustomValidity('');
        this.style.borderColor = '';
    }
});

// Clear border color on input
document.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('input', function() {
        if (this.value.trim()) {
            this.style.borderColor = '';
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
