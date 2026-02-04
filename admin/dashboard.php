<?php
/**
 * Admin Dashboard - Professional Design
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_ADMIN);

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get comprehensive statistics
    $stats = [];
    
    // Total students
    $stmt = $conn->query("SELECT COUNT(*) FROM students");
    $stats['total_students'] = $stmt->fetchColumn();
    
    // Active students
    $stmt = $conn->query("SELECT COUNT(*) FROM students WHERE status = 'active'");
    $stats['active_students'] = $stmt->fetchColumn();
    
    // Total courses
    $stmt = $conn->query("SELECT COUNT(*) FROM courses");
    $stats['total_courses'] = $stmt->fetchColumn();
    
    // Active courses
    $stmt = $conn->query("SELECT COUNT(*) FROM courses WHERE status = 'active'");
    $stats['active_courses'] = $stmt->fetchColumn();
    
    // Total enrollments
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollments");
    $stats['total_enrollments'] = $stmt->fetchColumn();
    
    // Pending enrollments
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollments WHERE status = 'pending'");
    $stats['pending_enrollments'] = $stmt->fetchColumn();
    
    // Completed enrollments
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollments WHERE status = 'completed'");
    $stats['completed_enrollments'] = $stmt->fetchColumn();
    
    // Active enrollments
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollments WHERE status = 'enrolled'");
    $stats['active_enrollments'] = $stmt->fetchColumn();
    
    // Students registered this month
    $stmt = $conn->query("SELECT COUNT(*) FROM students WHERE MONTH(registration_date) = MONTH(CURRENT_DATE()) AND YEAR(registration_date) = YEAR(CURRENT_DATE())");
    $stats['students_this_month'] = $stmt->fetchColumn();
    
    // Enrollments this month
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollments WHERE MONTH(enrollment_date) = MONTH(CURRENT_DATE()) AND YEAR(enrollment_date) = YEAR(CURRENT_DATE())");
    $stats['enrollments_this_month'] = $stmt->fetchColumn();
    
    // Recent students
    $stmt = $conn->query("SELECT * FROM students ORDER BY registration_date DESC LIMIT 5");
    $recentStudents = $stmt->fetchAll();
    
    // Recent enrollments
    $stmt = $conn->query("
        SELECT e.*, s.first_name, s.last_name, s.email, c.course_code, c.course_name
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN courses c ON e.course_id = c.course_id
        ORDER BY e.enrollment_date DESC
        LIMIT 10
    ");
    $recentEnrollments = $stmt->fetchAll();
    
    // Top courses by enrollment
    $stmt = $conn->query("
        SELECT c.course_code, c.course_name, COUNT(e.enrollment_id) as enrollment_count
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        GROUP BY c.course_id
        ORDER BY enrollment_count DESC
        LIMIT 5
    ");
    $topCourses = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $stats = [
        'total_students' => 0, 
        'active_students' => 0, 
        'total_courses' => 0, 
        'active_courses' => 0, 
        'total_enrollments' => 0, 
        'pending_enrollments' => 0,
        'completed_enrollments' => 0,
        'active_enrollments' => 0,
        'students_this_month' => 0,
        'enrollments_this_month' => 0
    ];
    $recentStudents = [];
    $recentEnrollments = [];
    $topCourses = [];
}

$pageTitle = 'Admin Dashboard - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<!-- Dashboard Header -->
<div class="dashboard-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h2 class="dashboard-title mb-1">Dashboard Overview</h2>
            <p class="dashboard-subtitle text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>! Here's what's happening today.</p>
        </div>
        <div class="dashboard-date">
            <i class="bi bi-calendar3 me-2"></i>
            <span><?php echo date('l, F j, Y'); ?></span>
        </div>
    </div>
</div>

<!-- Key Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-primary">
            <div class="stat-card-content">
                <div class="stat-card-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-card-info">
                    <p class="stat-card-label">Total Students</p>
                    <h3 class="stat-card-value"><?php echo number_format($stats['total_students']); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-success">
                            <i class="bi bi-check-circle-fill"></i> <?php echo number_format($stats['active_students']); ?> Active
                        </span>
                    </div>
                </div>
            </div>
            <div class="stat-card-wave"></div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-info">
            <div class="stat-card-content">
                <div class="stat-card-icon">
                    <i class="bi bi-book-half"></i>
                </div>
                <div class="stat-card-info">
                    <p class="stat-card-label">Active Courses</p>
                    <h3 class="stat-card-value"><?php echo number_format($stats['active_courses']); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-info">
                            <i class="bi bi-journal-text"></i> <?php echo number_format($stats['total_courses']); ?> Total
                        </span>
                    </div>
                </div>
            </div>
            <div class="stat-card-wave"></div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-success">
            <div class="stat-card-content">
                <div class="stat-card-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="stat-card-info">
                    <p class="stat-card-label">Total Enrollments</p>
                    <h3 class="stat-card-value"><?php echo number_format($stats['total_enrollments']); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-success">
                            <i class="bi bi-arrow-up-circle"></i> <?php echo number_format($stats['enrollments_this_month']); ?> This Month
                        </span>
                    </div>
                </div>
            </div>
            <div class="stat-card-wave"></div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-warning">
            <div class="stat-card-content">
                <div class="stat-card-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="stat-card-info">
                    <p class="stat-card-label">Pending Actions</p>
                    <h3 class="stat-card-value"><?php echo number_format($stats['pending_enrollments']); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-warning">
                            <i class="bi bi-clock-history"></i> Requires Review
                        </span>
                    </div>
                </div>
            </div>
            <div class="stat-card-wave"></div>
        </div>
    </div>
</div>

<!-- Quick Actions & Additional Stats -->
<div class="row g-4 mb-4">
    <!-- Quick Actions -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title">
                    <i class="bi bi-lightning-charge-fill text-warning me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="admin-card-body">
                <div class="quick-actions-grid">
                    <a href="<?php echo BASE_URL; ?>/admin/students.php" class="quick-action-item">
                        <div class="quick-action-icon bg-primary">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Manage Students</h6>
                            <p>View and manage student records</p>
                        </div>
                        <i class="bi bi-chevron-right quick-action-arrow"></i>
                    </a>
                    
                    <a href="<?php echo BASE_URL; ?>/admin/courses.php" class="quick-action-item">
                        <div class="quick-action-icon bg-info">
                            <i class="bi bi-book"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Course Management</h6>
                            <p>Add or edit course information</p>
                        </div>
                        <i class="bi bi-chevron-right quick-action-arrow"></i>
                    </a>
                    
                    <a href="<?php echo BASE_URL; ?>/admin/enrollments.php" class="quick-action-item">
                        <div class="quick-action-icon bg-warning">
                            <i class="bi bi-list-check"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Process Enrollments</h6>
                            <p>Review and approve enrollment requests</p>
                        </div>
                        <i class="bi bi-chevron-right quick-action-arrow"></i>
                    </a>
                    
                    <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="quick-action-item">
                        <div class="quick-action-icon bg-success">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>View Reports</h6>
                            <p>Analytics and detailed reports</p>
                        </div>
                        <i class="bi bi-chevron-right quick-action-arrow"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Enrollment Status Summary -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title">
                    <i class="bi bi-pie-chart-fill text-primary me-2"></i>
                    Enrollment Status
                </h5>
            </div>
            <div class="admin-card-body">
                <div class="status-summary">
                    <div class="status-item">
                        <div class="status-indicator bg-success"></div>
                        <div class="status-details">
                            <span class="status-label">Enrolled</span>
                            <span class="status-value"><?php echo number_format($stats['active_enrollments']); ?></span>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator bg-warning"></div>
                        <div class="status-details">
                            <span class="status-label">Pending</span>
                            <span class="status-value"><?php echo number_format($stats['pending_enrollments']); ?></span>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator bg-info"></div>
                        <div class="status-details">
                            <span class="status-label">Completed</span>
                            <span class="status-value"><?php echo number_format($stats['completed_enrollments']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Tables Section -->
<div class="row g-4">
    <!-- Recent Students -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h5 class="admin-card-title">
                    <i class="bi bi-clock-history text-primary me-2"></i>
                    Recent Registrations
                </h5>
                <a href="<?php echo BASE_URL; ?>/admin/students.php" class="btn btn-sm btn-outline-primary rounded-pill">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="admin-card-body">
                <?php if (empty($recentStudents)): ?>
                    <div class="empty-state">
                        <i class="bi bi-people empty-state-icon"></i>
                        <p class="empty-state-text">No students registered yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-modern-wrapper">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th class="text-end">Registration Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentStudents as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="student-avatar">
                                                    <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                                </div>
                                                <div class="ms-3">
                                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-muted"><?php echo date('M d, Y', strtotime($student['registration_date'])); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Enrollments -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h5 class="admin-card-title">
                    <i class="bi bi-journal-check text-primary me-2"></i>
                    Latest Enrollment Activity
                </h5>
                <a href="<?php echo BASE_URL; ?>/admin/enrollments.php" class="btn btn-sm btn-outline-primary rounded-pill">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="admin-card-body">
                <?php if (empty($recentEnrollments)): ?>
                    <div class="empty-state">
                        <i class="bi bi-journal-x empty-state-icon"></i>
                        <p class="empty-state-text">No enrollment activity yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-modern-wrapper">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Student</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentEnrollments as $enrollment): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-primary"><?php echo htmlspecialchars($enrollment['course_code']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($enrollment['course_name']); ?></small>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-medium"><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></div>
                                        </td>
                                        <td class="text-end">
                                            <?php
                                            $badgeConfig = [
                                                'enrolled' => ['class' => 'badge-success', 'icon' => 'check-circle'],
                                                'pending' => ['class' => 'badge-warning', 'icon' => 'clock'],
                                                'completed' => ['class' => 'badge-info', 'icon' => 'check-all'],
                                                'dropped' => ['class' => 'badge-danger', 'icon' => 'x-circle']
                                            ];
                                            $status = $enrollment['status'];
                                            $config = $badgeConfig[$status] ?? ['class' => 'badge-secondary', 'icon' => 'circle'];
                                            ?>
                                            <span class="status-badge status-badge-<?php echo $config['class']; ?>">
                                                <i class="bi bi-<?php echo $config['icon']; ?>"></i>
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

