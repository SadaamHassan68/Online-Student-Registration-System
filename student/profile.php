<?php
/**
 * Student Profile Management
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/validation_functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_STUDENT);

$studentId = getCurrentUserId();
$error = '';
$success = '';
$student = null;
$analytics = [
    'total_enrollments' => 0,
    'active_enrollments' => 0,
    'completed_enrollments' => 0,
    'pending_enrollments' => 0,
    'dropped_enrollments' => 0,
    'total_credits' => 0,
    'completed_credits' => 0,
    'current_gpa' => null,
    'graded_courses' => 0
];
$recentEnrollments = [];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get student info
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header('Location: ' . BASE_URL . '/student/dashboard.php');
        exit;
    }
    
    // Get enrollment analytics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_enrollments,
            SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as active_enrollments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_enrollments,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_enrollments,
            SUM(CASE WHEN status = 'dropped' THEN 1 ELSE 0 END) as dropped_enrollments,
            SUM(CASE WHEN status IN ('enrolled', 'completed') THEN c.credits ELSE 0 END) as total_credits,
            SUM(CASE WHEN status = 'completed' THEN c.credits ELSE 0 END) as completed_credits,
            AVG(CASE WHEN status = 'completed' AND e.grade IS NOT NULL THEN e.grade ELSE NULL END) as current_gpa,
            COUNT(CASE WHEN status = 'completed' AND e.grade IS NOT NULL THEN 1 ELSE NULL END) as graded_courses
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.student_id = ?
    ");
    $stmt->execute([$studentId]);
    $analytics = $stmt->fetch() ?: $analytics;
    
    // Get recent enrollments
    $stmt = $conn->prepare("
        SELECT e.*, c.course_code, c.course_name, c.credits, c.instructor
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.student_id = ?
        ORDER BY e.enrollment_date DESC
        LIMIT 5
    ");
    $stmt->execute([$studentId]);
    $recentEnrollments = $stmt->fetchAll();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!verifyCSRFToken($csrfToken)) {
            $error = 'Invalid security token. Please try again.';
        } else {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'update_profile') {
                $first_name = sanitizeInput($_POST['first_name'] ?? '');
                $last_name = sanitizeInput($_POST['last_name'] ?? '');
                $phone = sanitizeInput($_POST['phone'] ?? '');
                $secondary_phone = sanitizeInput($_POST['secondary_phone'] ?? '');
                $address = sanitizeInput($_POST['address'] ?? '');
                $intended_major = sanitizeInput($_POST['intended_major'] ?? '');
                $high_school_name = sanitizeInput($_POST['high_school_name'] ?? '');
                $high_school_grade = $_POST['high_school_grade'] ?? '';
                
                if (empty($first_name) || empty($last_name)) {
                    $error = 'First name and last name are required.';
                } elseif (!empty($phone) && !validatePhone($phone)) {
                    $error = 'Invalid phone number format.';
                } elseif (!empty($secondary_phone) && !validatePhone($secondary_phone)) {
                    $error = 'Invalid secondary phone number format.';
                } else {
                    $highSchoolGrade = null;
                    if (!empty($high_school_grade)) {
                        $grade = floatval($high_school_grade);
                        if ($grade < 0 || $grade > 4.0) {
                            $error = 'High school GPA must be between 0.0 and 4.0.';
                        } else {
                            $highSchoolGrade = $grade;
                        }
                    }
                    
                    if (empty($error)) {
                        $stmt = $conn->prepare("
                            UPDATE students 
                            SET first_name = ?, last_name = ?, phone = ?, secondary_phone = ?, 
                                address = ?, intended_major = ?, high_school_name = ?, high_school_grade = ?
                            WHERE student_id = ?
                        ");
                        $stmt->execute([
                            $first_name, $last_name, $phone ?: null, $secondary_phone ?: null, 
                            $address ?: null, $intended_major ?: null, $high_school_name ?: null, 
                            $highSchoolGrade, $studentId
                        ]);
                        $success = 'Profile updated successfully!';
                        
                        // Reload student data
                        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
                        $stmt->execute([$studentId]);
                        $student = $stmt->fetch();
                    }
                }
            } elseif ($action === 'change_password') {
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = 'All password fields are required.';
                } elseif (!verifyPassword($current_password, $student['password_hash'])) {
                    $error = 'Current password is incorrect.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                } else {
                    $passwordValidation = validatePassword($new_password);
                    if ($passwordValidation !== true) {
                        $error = implode('<br>', $passwordValidation);
                    } else {
                        $passwordHash = hashPassword($new_password);
                        $stmt = $conn->prepare("UPDATE students SET password_hash = ? WHERE student_id = ?");
                        $stmt->execute([$passwordHash, $studentId]);
                        $success = 'Password changed successfully!';
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $error = 'An error occurred while loading your profile. Please try refreshing the page.';
}

$pageTitle = 'My Profile - ' . APP_NAME;
$csrfToken = generateCSRFToken();

include __DIR__ . '/../includes/header.php';
?>

<div class="profile-page-wrapper">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="profile-header-content">
                <div class="profile-avatar-section">
                    <div class="profile-avatar-wrapper">
                        <?php if (!empty($student['profile_picture'])): ?>
                            <img src="<?php echo BASE_URL; ?>/public/uploads/profile_pictures/<?php echo htmlspecialchars($student['profile_picture']); ?>" 
                                 class="profile-avatar" alt="Profile Picture">
                        <?php else: ?>
                            <div class="profile-avatar-placeholder">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        <?php endif; ?>
                        <div class="profile-status-badge">
                            <?php
                            $statusColors = [
                                'approved' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger'
                            ];
                            $statusColor = $statusColors[$student['admission_status']] ?? 'secondary';
                            ?>
                            <span class="status-dot status-<?php echo $statusColor; ?>"></span>
                            <?php echo ucfirst($student['admission_status']); ?>
                        </div>
                    </div>
                </div>
                <div class="profile-info-section">
                    <h1 class="profile-name">
                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                    </h1>
                    <p class="profile-email">
                        <i class="bi bi-envelope"></i>
                        <?php echo htmlspecialchars($student['email']); ?>
                    </p>
                    <div class="profile-meta">
                        <div class="meta-item">
                            <i class="bi bi-calendar-check"></i>
                            <span>Joined <?php echo date('F Y', strtotime($student['registration_date'])); ?></span>
                        </div>
                        <?php if ($student['intended_major']): ?>
                        <div class="meta-item">
                            <i class="bi bi-mortarboard"></i>
                            <span><?php echo htmlspecialchars($student['intended_major']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-modern">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?php echo $error; ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-modern">
                <i class="bi bi-check-circle-fill"></i>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
        <?php endif; ?>

        <!-- Academic Analytics -->
        <div class="analytics-grid mb-4">
            <div class="analytics-card card-primary">
                <div class="analytics-icon">
                    <i class="bi bi-list-check"></i>
                </div>
                <div class="analytics-content">
                    <h3 class="analytics-value"><?php echo $analytics['total_enrollments'] ?? 0; ?></h3>
                    <p class="analytics-label">Total Enrollments</p>
                </div>
            </div>
            
            <div class="analytics-card card-success">
                <div class="analytics-icon">
                    <i class="bi bi-book"></i>
                </div>
                <div class="analytics-content">
                    <h3 class="analytics-value"><?php echo $analytics['active_enrollments'] ?? 0; ?></h3>
                    <p class="analytics-label">Active Courses</p>
                </div>
            </div>
            
            <div class="analytics-card card-info">
                <div class="analytics-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="analytics-content">
                    <h3 class="analytics-value"><?php echo $analytics['completed_enrollments'] ?? 0; ?></h3>
                    <p class="analytics-label">Completed</p>
                </div>
            </div>
            
            <div class="analytics-card card-warning">
                <div class="analytics-icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div class="analytics-content">
                    <h3 class="analytics-value"><?php echo number_format($analytics['current_gpa'] ?? 0, 2); ?></h3>
                    <p class="analytics-label">Current GPA</p>
                    <small><?php echo ($analytics['graded_courses'] ?? 0) > 0 ? ($analytics['graded_courses'] . ' graded') : 'No grades yet'; ?></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Profile Form -->
            <div class="col-lg-8 mb-4">
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h5><i class="bi bi-person-circle me-2"></i>Personal Information</h5>
                    </div>
                    <div class="profile-card-body">
                        <form method="POST" class="profile-form">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label>First Name <span class="required">*</span></label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-person"></i>
                                            <input type="text" class="form-control-modern" name="first_name" 
                                                   value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label>Last Name <span class="required">*</span></label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-person"></i>
                                            <input type="text" class="form-control-modern" name="last_name" 
                                                   value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label>Email Address</label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-envelope"></i>
                                            <input type="email" class="form-control-modern" 
                                                   value="<?php echo htmlspecialchars($student['email']); ?>" disabled>
                                        </div>
                                        <small class="form-hint">Email cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label>Phone Number</label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-telephone"></i>
                                            <input type="tel" class="form-control-modern" name="phone" 
                                                   value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" 
                                                   placeholder="+1234567890">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label>Secondary Phone</label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-telephone"></i>
                                            <input type="tel" class="form-control-modern" name="secondary_phone" 
                                                   value="<?php echo htmlspecialchars($student['secondary_phone'] ?? ''); ?>" 
                                                   placeholder="+1234567890">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label>Address</label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-geo-alt"></i>
                                            <textarea class="form-control-modern" name="address" rows="2" 
                                                      placeholder="Your address"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <h6 class="section-subtitle"><i class="bi bi-mortarboard me-2"></i>Academic Information</h6>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label>Intended Major</label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-book"></i>
                                            <select class="form-control-modern" name="intended_major">
                                                <option value="">Select Major</option>
                                                <option value="Computer Science" <?php echo ($student['intended_major'] ?? '') === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                                <option value="Information Technology" <?php echo ($student['intended_major'] ?? '') === 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                                                <option value="Mathematics" <?php echo ($student['intended_major'] ?? '') === 'Mathematics' ? 'selected' : ''; ?>>Mathematics</option>
                                                <option value="Engineering" <?php echo ($student['intended_major'] ?? '') === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                                <option value="Business Administration" <?php echo ($student['intended_major'] ?? '') === 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label>High School GPA</label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-award"></i>
                                            <input type="number" class="form-control-modern" name="high_school_grade" 
                                                   value="<?php echo htmlspecialchars($student['high_school_grade'] ?? ''); ?>" 
                                                   step="0.01" min="0" max="4.0" placeholder="0.00 - 4.00">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label>High School Name</label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-building"></i>
                                            <input type="text" class="form-control-modern" name="high_school_name" 
                                                   value="<?php echo htmlspecialchars($student['high_school_name'] ?? ''); ?>" 
                                                   placeholder="Your high school">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-modern btn-primary mt-4">
                                <i class="bi bi-check-circle me-2"></i>
                                Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="profile-card mt-4">
                    <div class="profile-card-header">
                        <h5><i class="bi bi-shield-lock me-2"></i>Change Password</h5>
                    </div>
                    <div class="profile-card-body">
                        <form method="POST" class="profile-form">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label>Current Password <span class="required">*</span></label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-lock"></i>
                                            <input type="password" class="form-control-modern" name="current_password" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label>New Password <span class="required">*</span></label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-lock-fill"></i>
                                            <input type="password" class="form-control-modern" name="new_password" 
                                                   id="new_password" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label>Confirm New Password <span class="required">*</span></label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-lock-fill"></i>
                                            <input type="password" class="form-control-modern" name="confirm_password" 
                                                   id="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-modern btn-warning mt-4">
                                <i class="bi bi-key me-2"></i>
                                Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-lg-4">
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h5><i class="bi bi-clock-history me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="profile-card-body">
                        <?php if (empty($recentEnrollments)): ?>
                            <div class="empty-state-small">
                                <i class="bi bi-inbox"></i>
                                <p>No recent enrollments</p>
                            </div>
                        <?php else: ?>
                            <div class="activity-list">
                                <?php foreach ($recentEnrollments as $enrollment): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="bi bi-book"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h6><?php echo htmlspecialchars($enrollment['course_code']); ?></h6>
                                            <p><?php echo htmlspecialchars($enrollment['course_name']); ?></p>
                                            <div class="activity-meta">
                                                <?php
                                                $statusColors = [
                                                    'enrolled' => 'success',
                                                    'pending' => 'warning',
                                                    'completed' => 'info',
                                                    'dropped' => 'danger'
                                                ];
                                                $statusColor = $statusColors[$enrollment['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge badge-<?php echo $statusColor; ?>">
                                                    <?php echo ucfirst($enrollment['status']); ?>
                                                </span>
                                                <span class="activity-date">
                                                    <?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Profile Page Styles */
.profile-page-wrapper {
    background: var(--bg-main);
    min-height: 100vh;
}

.profile-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    padding: 3rem 0 2rem;
    margin-bottom: 2rem;
}

.profile-header-content {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.profile-avatar-wrapper {
    position: relative;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 20px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    object-fit: cover;
}

.profile-avatar-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: #fff;
    border: 4px solid rgba(255, 255, 255, 0.3);
}

.profile-status-badge {
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: #fff;
    padding: 0.375rem 0.875rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-success { background: #10b981; }
.status-warning { background: #f59e0b; }
.status-danger { background: #ef4444; }

.profile-info-section {
    flex: 1;
    color: #fff;
}

.profile-name {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}

.profile-email {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.profile-meta {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    opacity: 0.9;
}

/* Analytics Grid */
.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.analytics-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.75rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
}

.analytics-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.analytics-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: #fff;
}

.card-primary .analytics-icon { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
.card-success .analytics-icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.card-info .analytics-icon { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }
.card-warning .analytics-icon { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

.analytics-value {
    font-size: 2rem;
    font-weight: 800;
    color: var(--slate-900);
    margin: 0;
}

.analytics-label {
    color: var(--slate-600);
    font-size: 0.9rem;
    margin: 0;
}

.analytics-content small {
    color: var(--slate-500);
    font-size: 0.75rem;
}

/* Profile Card */
.profile-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.profile-card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.profile-card-header h5 {
    margin: 0;
    font-weight: 700;
    color: var(--slate-900);
    display: flex;
    align-items: center;
}

.profile-card-body {
    padding: 1.5rem;
}

/* Modern Form */
.form-group-modern {
    margin-bottom: 1.25rem;
}

.form-group-modern label {
    display: block;
    font-weight: 600;
    color: var(--slate-700);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.required {
    color: #ef4444;
}

.input-with-icon {
    position: relative;
}

.input-with-icon > i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--slate-400);
    font-size: 1.1rem;
    z-index: 2;
}

.form-control-modern {
    width: 100%;
    padding: 0.875rem 1rem 0.875rem 3rem;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #fff;
}

.form-control-modern:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px var(--primary-light);
    outline: none;
}

.form-control-modern:disabled {
    background: var(--slate-100);
    cursor: not-allowed;
}

.form-hint {
    display: block;
    margin-top: 0.375rem;
    font-size: 0.8rem;
    color: var(--slate-500);
}

.section-subtitle {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--slate-900);
    margin: 1.5rem 0 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

/* Modern Buttons */
.btn-modern {
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
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
}

/* Activity List */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.activity-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--slate-50);
    border-radius: 12px;
    transition: all 0.2s ease;
}

.activity-item:hover {
    background: var(--slate-100);
}

.activity-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
}

.activity-content h6 {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--slate-900);
    margin: 0 0 0.25rem 0;
}

.activity-content p {
    font-size: 0.85rem;
    color: var(--slate-600);
    margin: 0 0 0.5rem 0;
}

.activity-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-success { background: #10b981; color: #fff; }
.badge-warning { background: #f59e0b; color: #fff; }
.badge-info { background: #0ea5e9; color: #fff; }
.badge-danger { background: #ef4444; color: #fff; }

.activity-date {
    font-size: 0.75rem;
    color: var(--slate-500);
}

/* Empty State */
.empty-state-small {
    text-align: center;
    padding: 2rem 1rem;
}

.empty-state-small i {
    font-size: 3rem;
    color: var(--slate-300);
    margin-bottom: 0.75rem;
}

.empty-state-small p {
    color: var(--slate-500);
    margin: 0;
}

/* Alert Modern */
.alert-modern {
    border-radius: 12px;
    padding: 1.25rem;
    border: none;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.alert-modern i {
    font-size: 1.5rem;
    flex-shrink: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .profile-header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-meta {
        justify-content: center;
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Confirm password validation
document.getElementById('confirm_password')?.addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    if (this.value !== password) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
