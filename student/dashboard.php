<?php
/**
 * Student Dashboard
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_STUDENT);

$studentId = getCurrentUserId();

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get student info
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    // Get enrollment stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_enrollments,
            SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as active_enrollments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_enrollments
        FROM enrollments 
        WHERE student_id = ?
    ");
    $stmt->execute([$studentId]);
    $stats = $stmt->fetch();
    
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
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $student = null;
    $stats = ['total_enrollments' => 0, 'active_enrollments' => 0, 'completed_enrollments' => 0];
    $recentEnrollments = [];
}

$pageTitle = 'Dashboard - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container main-content">
    <!-- Welcome Section with Premium Design -->
    <div class="student-welcome">
        <h2>Welcome back, <?php echo htmlspecialchars($student['first_name'] ?? 'Student'); ?>! 👋</h2>
        <p>Here's an overview of your academic journey</p>
        <?php 
        $status = $student['admission_status'] ?? 'pending';
        $statusClass = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger'
        ][$status];
        $statusIcon = [
            'pending' => 'hourglass-split',
            'approved' => 'check-circle-fill',
            'rejected' => 'x-circle-fill'
        ][$status];
        ?>
        <div class="alert alert-<?php echo $statusClass; ?> d-inline-flex align-items-center mt-3 px-4 py-2 border-0 shadow-sm rounded-pill admission-status-badge">
            <i class="bi bi-<?php echo $statusIcon; ?> me-2"></i>
            <div>
                <strong>Admission Status:</strong> <?php echo ucfirst($status); ?>
            </div>
        </div>
    </div>

    <?php if ($status === 'pending'): ?>
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle-fill"></i>
            <div>
                <strong>Note:</strong> Your admission application is currently under review. Some features, like course enrollment, may be limited until approval.
            </div>
        </div>
    <?php elseif ($status === 'rejected'): ?>
        <div class="alert alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Important:</strong> Your admission application has been rejected. Please contact the administration office for more details.
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards with Premium Design -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card bg-primary">
                <h3><?php echo $stats['total_enrollments'] ?? 0; ?></h3>
                <p><i class="bi bi-list-check"></i> Total Enrollments</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border: none; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);">
                <h3><?php echo $stats['active_enrollments'] ?? 0; ?></h3>
                <p><i class="bi bi-book"></i> Active Courses</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: #fff; border: none; box-shadow: 0 8px 24px rgba(14, 165, 233, 0.3);">
                <h3><?php echo $stats['completed_enrollments'] ?? 0; ?></h3>
                <p><i class="bi bi-check-circle"></i> Completed Courses</p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions with Modern Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="quick-actions-card card">
                <div class="card-header">
                    <h5><i class="bi bi-lightning-fill"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo BASE_URL; ?>/student/courses.php" class="btn btn-primary">
                        <i class="bi bi-search"></i> Browse Courses
                    </a>
                    <a href="<?php echo BASE_URL; ?>/student/enrollments.php" class="btn btn-success">
                        <i class="bi bi-list-check"></i> View Enrollments
                    </a>
                    <a href="<?php echo BASE_URL; ?>/student/profile.php" class="btn btn-info">
                        <i class="bi bi-person"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Enrollments with Premium Card -->
    <div class="row">
        <div class="col-md-12">
            <div class="recent-enrollments-card card">
                <div class="card-header">
                    <h5><i class="bi bi-clock-history"></i> Recent Enrollments</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentEnrollments)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: var(--slate-300);"></i>
                            <p class="text-muted mt-3">No enrollments yet. <a href="<?php echo BASE_URL; ?>/student/courses.php" class="text-decoration-none fw-bold">Browse courses</a> to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Instructor</th>
                                        <th>Credits</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentEnrollments as $enrollment): ?>
                                        <tr>
                                            <td><strong class="text-primary"><?php echo htmlspecialchars($enrollment['course_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['instructor'] ?? 'TBA'); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($enrollment['credits']); ?></span></td>
                                            <td>
                                                <?php
                                                $badgeClass = [
                                                    'enrolled' => 'success',
                                                    'pending' => 'warning',
                                                    'completed' => 'info',
                                                    'dropped' => 'danger'
                                                ][$enrollment['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $badgeClass; ?>">
                                                    <?php echo ucfirst($enrollment['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo BASE_URL; ?>/student/enrollments.php" class="btn btn-outline-primary">
                                View All Enrollments <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

