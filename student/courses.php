<?php
/**
 * Course Catalog - Browse and Search Courses
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

// Get filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$semester = sanitizeInput($_GET['semester'] ?? '');
$department = sanitizeInput($_GET['department'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

$admission_status = 'pending'; // Default

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Build query
    $where = ["status = 'active'"];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(course_code LIKE ? OR course_name LIKE ? OR description LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($semester)) {
        $where[] = "semester = ?";
        $params[] = $semester;
    }
    
    if (!empty($department)) {
        $where[] = "department = ?";
        $params[] = $department;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM courses WHERE $whereClause");
    $countStmt->execute($params);
    $totalCourses = $countStmt->fetchColumn();
    $totalPages = ceil($totalCourses / $limit);
    
    // Get courses
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id AND status IN ('enrolled', 'pending')) as enrolled_count
            FROM courses c 
            WHERE $whereClause 
            ORDER BY course_code ASC 
            LIMIT $limit OFFSET $offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();
    
    // Get available semesters and departments for filter
    $semesterStmt = $conn->query("SELECT DISTINCT semester FROM courses WHERE status = 'active' ORDER BY semester DESC");
    $semesters = $semesterStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $deptStmt = $conn->query("SELECT DISTINCT department FROM courses WHERE status = 'active' AND department IS NOT NULL ORDER BY department");
    $departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get student's enrolled courses
    $enrolledStmt = $conn->prepare("SELECT course_id FROM enrollments WHERE student_id = ? AND status IN ('enrolled', 'pending')");
    $enrolledStmt->execute([$studentId]);
    $enrolledCourses = $enrolledStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get admission status
    $statusStmt = $conn->prepare("SELECT admission_status FROM students WHERE student_id = ?");
    $statusStmt->execute([$studentId]);
    $admission_status = $statusStmt->fetchColumn() ?: 'pending';
    
} catch (Exception $e) {
    error_log("Courses error: " . $e->getMessage());
    $courses = [];
    $semesters = [];
    $departments = [];
    $enrolledCourses = [];
    $totalPages = 0;
}

$pageTitle = 'Course Catalog - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container main-content">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="fw-bold" style="color: var(--slate-900);">📚 Course Catalog</h2>
        <p class="text-muted">Browse and enroll in available courses</p>
    </div>
    
    <!-- Filters with Premium Design -->
    <div class="filter-section">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label"><i class="bi bi-search me-2"></i>Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Course code, name, or description">
            </div>
            
            <div class="col-md-3">
                <label for="semester" class="form-label"><i class="bi bi-calendar me-2"></i>Semester</label>
                <select class="form-select" id="semester" name="semester">
                    <option value="">All Semesters</option>
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?php echo htmlspecialchars($sem); ?>" <?php echo $semester === $sem ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sem); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="department" class="form-label"><i class="bi bi-building me-2"></i>Department</label>
                <select class="form-select" id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department === $dept ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <?php if ($admission_status !== 'approved'): ?>
        <div class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Enrollment Restricted:</strong> Your admission status is currently <strong><?php echo ucfirst($admission_status); ?></strong>. 
                Enrollment is only available for students with <strong>Approved</strong> admission status.
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Course Results Header -->
    <?php if (!empty($courses)): ?>
        <div class="results-header mb-4">
            <div class="results-info">
                <h5 class="results-count">
                    <i class="bi bi-grid-3x3-gap me-2"></i>
                    <?php echo $totalCourses; ?> Course<?php echo $totalCourses != 1 ? 's' : ''; ?> Found
                </h5>
                <p class="results-subtitle">Page <?php echo $page; ?> of <?php echo $totalPages; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Courses Grid -->
    <?php if (empty($courses)): ?>
        <div class="empty-state-card">
            <div class="empty-state-icon">
                <i class="bi bi-inbox"></i>
            </div>
            <h4 class="empty-state-title">No Courses Found</h4>
            <p class="empty-state-text">Try adjusting your search filters to find more courses</p>
        </div>
    <?php else: ?>
        <div class="courses-grid">
            <?php foreach ($courses as $course): ?>
                <?php 
                $enrollmentPercent = ($course['enrolled_count'] / $course['max_capacity']) * 100;
                $isFull = $course['current_enrollment'] >= $course['max_capacity'];
                $isEnrolled = in_array($course['course_id'], $enrolledCourses);
                ?>
                <div class="course-card-wrapper">
                    <div class="course-card-premium">
                        <!-- Status Badge -->
                        <?php if ($isFull): ?>
                            <div class="course-status-badge badge-full">
                                <i class="bi bi-x-circle-fill"></i>
                                <span>Full</span>
                            </div>
                        <?php elseif ($isEnrolled): ?>
                            <div class="course-status-badge badge-enrolled">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Enrolled</span>
                            </div>
                        <?php else: ?>
                            <div class="course-status-badge badge-available">
                                <i class="bi bi-circle-fill"></i>
                                <span>Available</span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Course Header -->
                        <div class="course-header">
                            <div class="course-code-badge">
                                <?php echo htmlspecialchars($course['course_code']); ?>
                            </div>
                            <div class="course-credits">
                                <i class="bi bi-award-fill"></i>
                                <?php echo htmlspecialchars($course['credits']); ?> Credits
                            </div>
                        </div>
                        
                        <!-- Course Title -->
                        <h3 class="course-title">
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </h3>
                        
                        <!-- Course Description -->
                        <p class="course-description">
                            <?php echo htmlspecialchars(substr($course['description'] ?? 'No description available', 0, 120)); ?>...
                        </p>
                        
                        <!-- Course Details -->
                        <div class="course-details">
                            <div class="detail-item">
                                <i class="bi bi-person-fill"></i>
                                <div class="detail-content">
                                    <span class="detail-label">Instructor</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($course['instructor'] ?? 'TBA'); ?></span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="bi bi-calendar-event"></i>
                                <div class="detail-content">
                                    <span class="detail-label">Semester</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($course['semester']); ?> <?php echo htmlspecialchars($course['academic_year']); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($course['schedule']): ?>
                            <div class="detail-item">
                                <i class="bi bi-clock-fill"></i>
                                <div class="detail-content">
                                    <span class="detail-label">Schedule</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($course['schedule']); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Enrollment Progress -->
                        <div class="enrollment-progress">
                            <div class="progress-header">
                                <span class="progress-label">Enrollment</span>
                                <span class="progress-value"><?php echo $course['enrolled_count']; ?>/<?php echo $course['max_capacity']; ?></span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill <?php echo $enrollmentPercent >= 90 ? 'almost-full' : ($enrollmentPercent >= 70 ? 'filling' : ''); ?>" 
                                     style="width: <?php echo min($enrollmentPercent, 100); ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="course-actions">
                            <?php if ($isEnrolled): ?>
                                <button class="btn-course btn-enrolled" disabled>
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Already Enrolled</span>
                                </button>
                            <?php elseif ($isFull): ?>
                                <button class="btn-course btn-full" disabled>
                                    <i class="bi bi-x-circle-fill"></i>
                                    <span>Course Full</span>
                                </button>
                            <?php elseif ($admission_status !== 'approved'): ?>
                                <button class="btn-course btn-disabled" disabled title="Admission approval required">
                                    <i class="bi bi-lock-fill"></i>
                                    <span>Enroll Now</span>
                                </button>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>/student/enroll.php?course_id=<?php echo $course['course_id']; ?>" class="btn-course btn-enroll">
                                    <i class="bi bi-plus-circle-fill"></i>
                                    <span>Enroll Now</span>
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?php echo BASE_URL; ?>/student/course-details.php?id=<?php echo $course['course_id']; ?>" class="btn-course btn-details">
                                <i class="bi bi-info-circle"></i>
                                <span>View Details</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Premium Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="pagination-nav" aria-label="Course pagination">
                <ul class="pagination-premium">
                    <?php if ($page > 1): ?>
                        <li class="page-item-premium">
                            <a class="page-link-premium" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester); ?>&department=<?php echo urlencode($department); ?>">
                                <i class="bi bi-chevron-left"></i>
                                <span>Previous</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= min($totalPages, 5); $i++): ?>
                        <li class="page-item-premium <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link-premium" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester); ?>&department=<?php echo urlencode($department); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($totalPages > 5): ?>
                        <li class="page-item-premium disabled">
                            <span class="page-link-premium">...</span>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item-premium">
                            <a class="page-link-premium" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester); ?>&department=<?php echo urlencode($department); ?>">
                                <span>Next</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
    
    <style>
    /* Results Header */
    .results-header {
        background: #fff;
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        border: 1px solid var(--border-color);
    }
    
    .results-count {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--slate-900);
        margin: 0;
        display: flex;
        align-items: center;
    }
    
    .results-count i {
        color: var(--primary);
    }
    
    .results-subtitle {
        color: var(--slate-500);
        margin: 0.25rem 0 0 0;
        font-size: 0.9rem;
    }
    
    /* Empty State */
    .empty-state-card {
        background: #fff;
        border-radius: 20px;
        padding: 4rem 2rem;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    
    .empty-state-icon {
        font-size: 5rem;
        color: var(--slate-300);
        margin-bottom: 1.5rem;
    }
    
    .empty-state-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--slate-700);
        margin-bottom: 0.5rem;
    }
    
    .empty-state-text {
        color: var(--slate-500);
        font-size: 1rem;
    }
    
    /* Courses Grid */
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    /* Premium Course Card */
    .course-card-premium {
        background: #fff;
        border-radius: 20px;
        padding: 1.75rem;
        border: 2px solid var(--border-color);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .course-card-premium::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .course-card-premium:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(99, 102, 241, 0.15);
        border-color: var(--primary);
    }
    
    .course-card-premium:hover::before {
        opacity: 1;
    }
    
    /* Status Badge */
    .course-status-badge {
        position: absolute;
        top: 1.25rem;
        right: 1.25rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.375rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .badge-available {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
    }
    
    .badge-enrolled {
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        color: #fff;
    }
    
    .badge-full {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #fff;
    }
    
    /* Course Header */
    .course-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-right: 6rem;
    }
    
    .course-code-badge {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #fff;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.875rem;
        letter-spacing: 0.5px;
    }
    
    .course-credits {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        color: var(--slate-600);
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .course-credits i {
        color: #fbbf24;
    }
    
    /* Course Title */
    .course-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--slate-900);
        margin-bottom: 0.75rem;
        line-height: 1.4;
    }
    
    /* Course Description */
    .course-description {
        color: var(--slate-600);
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 1.25rem;
        flex-grow: 1;
    }
    
    /* Course Details */
    .course-details {
        background: var(--slate-50);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .detail-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .detail-item > i {
        color: var(--primary);
        font-size: 1.1rem;
        margin-top: 0.125rem;
        flex-shrink: 0;
    }
    
    .detail-content {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        flex: 1;
    }
    
    .detail-label {
        font-size: 0.75rem;
        color: var(--slate-500);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .detail-value {
        font-size: 0.9rem;
        color: var(--slate-900);
        font-weight: 600;
    }
    
    /* Enrollment Progress */
    .enrollment-progress {
        margin-bottom: 1.25rem;
    }
    
    .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .progress-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--slate-700);
    }
    
    .progress-value {
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--primary);
    }
    
    .progress-bar-container {
        height: 8px;
        background: var(--slate-200);
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        border-radius: 10px;
        transition: width 0.6s ease;
    }
    
    .progress-bar-fill.filling {
        background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
    }
    
    .progress-bar-fill.almost-full {
        background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
    }
    
    /* Course Actions */
    .course-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    
    .btn-course {
        padding: 0.875rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        text-decoration: none;
    }
    
    .btn-enroll {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #fff;
        grid-column: 1 / -1;
    }
    
    .btn-enroll:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        color: #fff;
    }
    
    .btn-enrolled {
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        color: #fff;
        grid-column: 1 / -1;
        opacity: 0.7;
    }
    
    .btn-full {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #fff;
        grid-column: 1 / -1;
        opacity: 0.7;
    }
    
    .btn-disabled {
        background: var(--slate-300);
        color: var(--slate-600);
        grid-column: 1 / -1;
    }
    
    .btn-details {
        background: #fff;
        color: var(--primary);
        border: 2px solid var(--primary);
        grid-column: 1 / -1;
    }
    
    .btn-details:hover {
        background: var(--primary);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
    }
    
    /* Premium Pagination */
    .pagination-nav {
        margin-top: 3rem;
    }
    
    .pagination-premium {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .page-item-premium {
        display: inline-block;
    }
    
    .page-link-premium {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        background: #fff;
        border: 2px solid var(--border-color);
        border-radius: 12px;
        color: var(--slate-700);
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .page-link-premium:hover {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    
    .page-item-premium.active .page-link-premium {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        border-color: transparent;
        color: #fff;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    
    .page-item-premium.disabled .page-link-premium {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .courses-grid {
            grid-template-columns: 1fr;
        }
        
        .course-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
            padding-right: 0;
        }
        
        .course-status-badge {
            top: 1rem;
            right: 1rem;
        }
        
        .pagination-premium {
            flex-wrap: wrap;
        }
    }
    </style>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

