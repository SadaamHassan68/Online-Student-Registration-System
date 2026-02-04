<?php
/**
 * Student Enrollments - View all enrollments
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_STUDENT);

$studentId = getCurrentUserId();
$error = '';
$success = '';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Handle drop enrollment
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drop_enrollment'])) {
        $csrfToken = $_POST['csrf_token'] ?? '';
        $enrollmentId = intval($_POST['enrollment_id'] ?? 0);
        
        if (!verifyCSRFToken($csrfToken)) {
            $error = 'Invalid security token.';
        } else {
            // Verify enrollment belongs to student
            $stmt = $conn->prepare("SELECT * FROM enrollments WHERE enrollment_id = ? AND student_id = ?");
            $stmt->execute([$enrollmentId, $studentId]);
            $enrollment = $stmt->fetch();
            
            if ($enrollment && $enrollment['status'] === 'enrolled') {
                // Update enrollment status
                $stmt = $conn->prepare("UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
                $stmt->execute([$enrollmentId]);
                
                // Update course enrollment count
                $stmt = $conn->prepare("UPDATE courses SET current_enrollment = GREATEST(0, current_enrollment - 1) WHERE course_id = ?");
                $stmt->execute([$enrollment['course_id']]);
                
                $success = 'Course dropped successfully.';
            } else {
                $error = 'Invalid enrollment or cannot drop this course.';
            }
        }
    }
    
    // Get all enrollments
    $stmt = $conn->prepare("
        SELECT e.*, c.course_code, c.course_name, c.credits, c.instructor, c.semester, c.academic_year, c.schedule
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.student_id = ?
        ORDER BY e.enrollment_date DESC
    ");
    $stmt->execute([$studentId]);
    $enrollments = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Enrollments error: " . $e->getMessage());
    $enrollments = [];
    $error = 'An error occurred. Please try again later.';
}

$pageTitle = 'My Enrollments - ' . APP_NAME;
$csrfToken = generateCSRFToken();

include __DIR__ . '/../includes/header.php';
?>

<div class="container main-content">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="fw-bold" style="color: var(--slate-900);">📋 My Enrollments</h2>
        <p class="text-muted">View and manage your course enrollments</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <div><?php echo htmlspecialchars($success); ?></div>
        </div>
    <?php endif; ?>
    
    <?php if (empty($enrollments)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 5rem; color: var(--slate-300);"></i>
                <h4 class="mt-4 mb-2" style="color: var(--slate-700);">No Enrollments Yet</h4>
                <p class="text-muted mb-4">You haven't enrolled in any courses. Start your learning journey today!</p>
                <a href="<?php echo BASE_URL; ?>/student/courses.php" class="btn btn-primary">
                    <i class="bi bi-search me-2"></i>Browse Courses
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="enrollment-table-wrapper">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Instructor</th>
                            <th>Credits</th>
                            <th>Semester</th>
                            <th>Status</th>
                            <th>Grade</th>
                            <th>Enrollment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td><strong class="text-primary"><?php echo htmlspecialchars($enrollment['course_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['instructor'] ?? 'TBA'); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($enrollment['credits']); ?></span></td>
                                <td><?php echo htmlspecialchars($enrollment['semester']); ?> <?php echo htmlspecialchars($enrollment['academic_year']); ?></td>
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
                                <td><?php echo $enrollment['grade'] ? number_format($enrollment['grade'], 2) : '-'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                <td>
                                    <?php if ($enrollment['status'] === 'enrolled'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirmDelete('Are you sure you want to drop this course?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                                            <button type="submit" name="drop_enrollment" class="btn btn-sm btn-danger">
                                                <i class="bi bi-x-circle me-1"></i>Drop
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

