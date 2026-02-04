<?php
/**
 * Course Enrollment Page
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_STUDENT);

$studentId = getCurrentUserId();
$courseId = intval($_GET['course_id'] ?? 0);
$error = '';
$success = '';

if (!$courseId) {
    header('Location: ' . BASE_URL . '/student/courses.php');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get course details
    $stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = ? AND status = 'active'");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
    
    // Check admission status
    $stmt = $conn->prepare("SELECT admission_status FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $admission_status = $stmt->fetchColumn() ?: 'pending';
    
    if ($admission_status !== 'approved') {
        header('Location: ' . BASE_URL . '/student/courses.php?error=admission_required');
        exit;
    }
    
    if (!$course) {
        header('Location: ' . BASE_URL . '/student/courses.php');
        exit;
    }
    
    // Check if already enrolled (active or pending)
    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$studentId, $courseId]);
    $existingEnrollment = $stmt->fetch();
    
    // Handle enrollment
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!verifyCSRFToken($csrfToken)) {
            $error = 'Invalid security token.';
        } elseif ($existingEnrollment && in_array($existingEnrollment['status'], ['enrolled', 'pending'])) {
            $error = 'You are already enrolled or have a pending request for this course.';
        } elseif ($course['current_enrollment'] >= $course['max_capacity']) {
            $error = 'This course is full.';
        } else {
            if ($existingEnrollment) {
                // Update existing record (e.g., if previously dropped)
                $stmt = $conn->prepare("
                    UPDATE enrollments 
                    SET status = 'pending', enrollment_date = CURRENT_TIMESTAMP 
                    WHERE enrollment_id = ?
                ");
                $stmt->execute([$existingEnrollment['enrollment_id']]);
            } else {
                // Create new enrollment
                $stmt = $conn->prepare("
                    INSERT INTO enrollments (student_id, course_id, status)
                    VALUES (?, ?, 'pending')
                ");
                $stmt->execute([$studentId, $courseId]);
            }
            
            // Update course enrollment count
            $stmt = $conn->prepare("UPDATE courses SET current_enrollment = current_enrollment + 1 WHERE course_id = ?");
            $stmt->execute([$courseId]);
            
            // Check if course is now full
            if ($course['current_enrollment'] + 1 >= $course['max_capacity']) {
                $stmt = $conn->prepare("UPDATE courses SET status = 'full' WHERE course_id = ?");
                $stmt->execute([$courseId]);
            }
            
            $success = 'Enrollment request submitted successfully! It will be reviewed by the administration.';
            
            // Refresh existing enrollment data
            $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$studentId, $courseId]);
            $existingEnrollment = $stmt->fetch();
        }
    }
    
} catch (Exception $e) {
    error_log("Enrollment error: " . $e->getMessage());
    $error = 'An error occurred. Please try again later.';
    $course = null;
}

if (!$course) {
    header('Location: ' . BASE_URL . '/student/courses.php');
    exit;
}

$pageTitle = 'Enroll in Course - ' . APP_NAME;
$csrfToken = generateCSRFToken();

include __DIR__ . '/../includes/header.php';
?>

<div class="container main-content">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-3">
                        <a href="<?php echo BASE_URL; ?>/student/courses.php" class="btn btn-primary">Browse More Courses</a>
                        <a href="<?php echo BASE_URL; ?>/student/enrollments.php" class="btn btn-outline-primary">View My Enrollments</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h4><i class="bi bi-book"></i> Enroll in Course</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5><?php echo htmlspecialchars($course['course_code']); ?> - <?php echo htmlspecialchars($course['course_name']); ?></h5>
                            
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Course Code</th>
                                    <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                </tr>
                                <tr>
                                    <th>Course Name</th>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td><?php echo nl2br(htmlspecialchars($course['description'] ?? '')); ?></td>
                                </tr>
                                <tr>
                                    <th>Credits</th>
                                    <td><?php echo htmlspecialchars($course['credits']); ?></td>
                                </tr>
                                <tr>
                                    <th>Instructor</th>
                                    <td><?php echo htmlspecialchars($course['instructor'] ?? 'TBA'); ?></td>
                                </tr>
                                <tr>
                                    <th>Semester</th>
                                    <td><?php echo htmlspecialchars($course['semester']); ?> <?php echo htmlspecialchars($course['academic_year']); ?></td>
                                </tr>
                                <tr>
                                    <th>Schedule</th>
                                    <td><?php echo htmlspecialchars($course['schedule'] ?? 'TBA'); ?></td>
                                </tr>
                                <tr>
                                    <th>Enrollment</th>
                                    <td><?php echo $course['current_enrollment']; ?>/<?php echo htmlspecialchars($course['max_capacity']); ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <?php if ($existingEnrollment && in_array($existingEnrollment['status'], ['enrolled', 'pending'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill"></i> You have an active or pending enrollment for this course.
                                <a href="<?php echo BASE_URL; ?>/student/enrollments.php" class="alert-link">View your enrollments</a>
                            </div>
                        <?php elseif ($course['current_enrollment'] >= $course['max_capacity']): ?>
                            <div class="alert alert-warning">
                                This course is full. Please select another course.
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> By enrolling, you agree to attend all classes and complete all required coursework.
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">Confirm Enrollment</button>
                                    <a href="<?php echo BASE_URL; ?>/student/courses.php" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

