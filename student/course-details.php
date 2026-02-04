<?php
/**
 * Course Details Page
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_STUDENT);

$studentId = getCurrentUserId();
$courseId = intval($_GET['id'] ?? 0);

if (!$courseId) {
    header('Location: ' . BASE_URL . '/student/courses.php');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get course details
    $stmt = $conn->prepare("SELECT c.*, 
            (SELECT status FROM enrollments WHERE student_id = ? AND course_id = c.course_id) as enrollment_status
            FROM courses c 
            WHERE c.course_id = ? AND c.status != 'inactive'");
    $stmt->execute([$studentId, $courseId]);
    $course = $stmt->fetch();
    
    if (!$course) {
        header('Location: ' . BASE_URL . '/student/courses.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Course details error: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/student/courses.php');
    exit;
}

$pageTitle = 'Course Details - ' . $course['course_code'] . ' - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container main-content">
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/student/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/student/courses.php">Courses</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($course['course_code']); ?></li>
                </ol>
            </nav>
            <h2><?php echo htmlspecialchars($course['course_code']); ?>: <?php echo htmlspecialchars($course['course_name']); ?></h2>
            <p class="text-muted"><?php echo htmlspecialchars($course['department'] ?? 'General Academic'); ?></p>
        </div>
        <div class="col-md-4 text-end">
            <?php if ($course['enrollment_status'] === 'enrolled' || $course['enrollment_status'] === 'pending'): ?>
                <a href="<?php echo BASE_URL; ?>/student/enrollments.php" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i> Already Enrolled
                </a>
            <?php elseif ($course['status'] === 'full'): ?>
                <button class="btn btn-danger" disabled>Course Full</button>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/student/enroll.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-primary btn-lg px-5">
                    Enroll Now
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-3">Course Description</h5>
                    <p class="card-text fs-5" style="line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($course['description'] ?? 'No description available for this course.')); ?>
                    </p>
                    
                    <?php if ($course['prerequisites']): ?>
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="fw-bold"><i class="bi bi-info-circle me-2"></i>Prerequisites</h6>
                            <p class="mb-0"><?php echo htmlspecialchars($course['prerequisites']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-4">Class Information</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted"><i class="bi bi-person me-2"></i>Instructor</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($course['instructor'] ?? 'TBA'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted"><i class="bi bi-clock me-2"></i>Schedule</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($course['schedule'] ?? 'TBA'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted"><i class="bi bi-calendar3 me-2"></i>Semester</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($course['semester']); ?> <?php echo htmlspecialchars($course['academic_year']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted"><i class="bi bi-award me-2"></i>Credits</span>
                            <span class="fw-bold"><?php echo $course['credits']; ?> Units</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                            <span class="text-muted"><i class="bi bi-people me-2"></i>Capacity</span>
                            <span class="fw-bold"><?php echo $course['current_enrollment']; ?> / <?php echo $course['max_capacity']; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="d-grid">
                <a href="<?php echo BASE_URL; ?>/student/courses.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i> Back to Catalog
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/header.php'; ?>
