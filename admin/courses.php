<?php
/**
 * Admin - Course Management
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/validation_functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_ADMIN);

$error = '';
$success = '';
$editCourse = null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid security token.';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            if ($action === 'add_course' || $action === 'edit_course') {
                $course_code = strtoupper(sanitizeInput($_POST['course_code'] ?? ''));
                $course_name = sanitizeInput($_POST['course_name'] ?? '');
                $description = sanitizeInput($_POST['description'] ?? '');
                $credits = intval($_POST['credits'] ?? 3);
                $max_capacity = intval($_POST['max_capacity'] ?? 30);
                $schedule = sanitizeInput($_POST['schedule'] ?? '');
                $instructor = sanitizeInput($_POST['instructor'] ?? '');
                $semester = sanitizeInput($_POST['semester'] ?? '');
                $academic_year = sanitizeInput($_POST['academic_year'] ?? '');
                $department = sanitizeInput($_POST['department'] ?? '');
                $prerequisites = sanitizeInput($_POST['prerequisites'] ?? '');
                
                if (empty($course_code) || empty($course_name) || empty($semester) || empty($academic_year)) {
                    $error = 'Please fill in all required fields.';
                } elseif (!validateCourseCode($course_code)) {
                    $error = 'Invalid course code format (e.g., CS101, MATH201).';
                } else {
                    if ($action === 'add_course') {
                        $stmt = $conn->prepare("
                            INSERT INTO courses (course_code, course_name, description, credits, max_capacity, schedule, instructor, semester, academic_year, department, prerequisites)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$course_code, $course_name, $description ?: null, $credits, $max_capacity, $schedule ?: null, $instructor ?: null, $semester, $academic_year, $department ?: null, $prerequisites ?: null]);
                        $success = 'Course added successfully.';
                    } else {
                        $course_id = intval($_POST['course_id'] ?? 0);
                        $stmt = $conn->prepare("
                            UPDATE courses 
                            SET course_code = ?, course_name = ?, description = ?, credits = ?, max_capacity = ?, 
                                schedule = ?, instructor = ?, semester = ?, academic_year = ?, department = ?, prerequisites = ?
                            WHERE course_id = ?
                        ");
                        $stmt->execute([$course_code, $course_name, $description ?: null, $credits, $max_capacity, $schedule ?: null, $instructor ?: null, $semester, $academic_year, $department ?: null, $prerequisites ?: null, $course_id]);
                        $success = 'Course updated successfully.';
                    }
                }
            } elseif ($action === 'delete_course') {
                $course_id = intval($_POST['course_id'] ?? 0);
                $stmt = $conn->prepare("UPDATE courses SET status = 'inactive' WHERE course_id = ?");
                $stmt->execute([$course_id]);
                $success = 'Course deactivated successfully.';
            }
        } catch (PDOException $e) {
            error_log("Course management error: " . $e->getMessage());
            if ($e->getCode() == 23000) {
                $error = 'Course code already exists.';
            } else {
                $error = 'An error occurred. Please try again.';
            }
        } catch (Exception $e) {
            error_log("Course management error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Get course to edit
if (isset($_GET['edit'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = ?");
        $stmt->execute([intval($_GET['edit'])]);
        $editCourse = $stmt->fetch();
    } catch (Exception $e) {
        $editCourse = null;
    }
}

// Get filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$status = sanitizeInput($_GET['status'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Build query
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(course_code LIKE ? OR course_name LIKE ? OR description LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($status)) {
        $where[] = "status = ?";
        $params[] = $status;
    } else {
        $where[] = "status != 'inactive'";
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
    
    // Get statistics
    $stmt = $conn->query("SELECT COUNT(*) FROM courses");
    $totalCount = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM courses WHERE status = 'active'");
    $activeCount = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM courses WHERE status = 'full'");
    $fullCount = $stmt->fetchColumn();
    
} catch (Exception $e) {
    error_log("Courses error: " . $e->getMessage());
    $courses = [];
    $totalPages = 0;
    $totalCount = 0;
    $activeCount = 0;
    $fullCount = 0;
}

$pageTitle = 'Manage Courses - ' . APP_NAME;
$csrfToken = generateCSRFToken();

include __DIR__ . '/../includes/header.php';
?>

<!-- Dashboard Header -->
<div class="dashboard-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h2 class="dashboard-title mb-1">Course Management</h2>
            <p class="dashboard-subtitle text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>! Configure and manage academic course catalogs.</p>
        </div>
        <div class="d-flex gap-2">
            <div class="dashboard-date">
                <i class="bi bi-calendar3 me-2"></i>
                <span><?php echo date('l, F j, Y'); ?></span>
            </div>
            <button class="btn btn-primary rounded-pill px-4" type="button" data-bs-toggle="collapse" data-bs-target="#courseFormCollapse">
                <i class="bi bi-plus-lg me-2"></i> New Course
            </button>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
    
    <!-- Add/Edit Course Form -->
    <div class="collapse <?php echo $editCourse ? 'show' : ''; ?> mb-4" id="courseFormCollapse">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title">
                    <i class="bi bi-<?php echo $editCourse ? 'pencil-square' : 'plus-circle-fill'; ?> me-2"></i>
                    <?php echo $editCourse ? 'Modify' : 'Create New'; ?> Course
                </h5>
            </div>
            <div class="admin-card-body">
                <form method="POST" id="courseForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="<?php echo $editCourse ? 'edit_course' : 'add_course'; ?>">
                    <?php if ($editCourse): ?>
                        <input type="hidden" name="course_id" value="<?php echo $editCourse['course_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="course_code" class="form-label small fw-bold">Course Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="course_code" name="course_code" 
                                   value="<?php echo htmlspecialchars($editCourse['course_code'] ?? ''); ?>" 
                                   placeholder="e.g., CS101" pattern="[A-Z]{2,4}[0-9]{3,4}" required>
                        </div>
                        
                        <div class="col-md-8 mb-3">
                            <label for="course_name" class="form-label small fw-bold">Course Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="course_name" name="course_name" 
                                   value="<?php echo htmlspecialchars($editCourse['course_name'] ?? ''); ?>" placeholder="Full Descriptive Name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label small fw-bold">Description</label>
                        <textarea class="form-control rounded-3" id="description" name="description" rows="2" placeholder="Course overview..."><?php echo htmlspecialchars($editCourse['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="credits" class="form-label small fw-bold">Credits</label>
                            <input type="number" class="form-control rounded-3" id="credits" name="credits" 
                                   value="<?php echo htmlspecialchars($editCourse['credits'] ?? 3); ?>" min="1" max="6" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="max_capacity" class="form-label small fw-bold">Capacity</label>
                            <input type="number" class="form-control rounded-3" id="max_capacity" name="max_capacity" 
                                   value="<?php echo htmlspecialchars($editCourse['max_capacity'] ?? 30); ?>" min="1" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="semester" class="form-label small fw-bold">Semester</label>
                            <select class="form-select rounded-3" id="semester" name="semester" required>
                                <option value="">Select...</option>
                                <option value="Fall" <?php echo ($editCourse['semester'] ?? '') === 'Fall' ? 'selected' : ''; ?>>Fall</option>
                                <option value="Spring" <?php echo ($editCourse['semester'] ?? '') === 'Spring' ? 'selected' : ''; ?>>Spring</option>
                                <option value="Summer" <?php echo ($editCourse['semester'] ?? '') === 'Summer' ? 'selected' : ''; ?>>Summer</option>
                                <option value="Winter" <?php echo ($editCourse['semester'] ?? '') === 'Winter' ? 'selected' : ''; ?>>Winter</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="academic_year" class="form-label small fw-bold">Academic Year</label>
                            <input type="text" class="form-control rounded-3" id="academic_year" name="academic_year" 
                                   value="<?php echo htmlspecialchars($editCourse['academic_year'] ?? date('Y')); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="instructor" class="form-label small fw-bold">Lead Instructor</label>
                            <input type="text" class="form-control rounded-3" id="instructor" name="instructor" 
                                   value="<?php echo htmlspecialchars($editCourse['instructor'] ?? ''); ?>" placeholder="Dr. Name">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label small fw-bold">Academic Department</label>
                            <input type="text" class="form-control rounded-3" id="department" name="department" 
                                   value="<?php echo htmlspecialchars($editCourse['department'] ?? ''); ?>" placeholder="Computer Science">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <?php if ($editCourse): ?>
                            <a href="/admin/courses.php" class="btn btn-light rounded-pill px-4">Discard Changes</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary rounded-pill px-5"><?php echo $editCourse ? 'Update Course' : 'Create Course'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
<!-- Key Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-primary">
            <div class="stat-card-content">
                <div class="stat-card-icon">
                    <i class="bi bi-book-half"></i>
                </div>
                <div class="stat-card-info">
                    <p class="stat-card-label">Total Courses</p>
                    <h3 class="stat-card-value"><?php echo number_format($totalCount); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-success">
                            <i class="bi bi-check-circle-fill"></i> <?php echo number_format($activeCount); ?> Active
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
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-card-info">
                    <p class="stat-card-label">Active Courses</p>
                    <h3 class="stat-card-value"><?php echo number_format($activeCount); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-success">
                            <i class="bi bi-arrow-up-circle"></i> Available
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
                    <p class="stat-card-label">Full Capacity</p>
                    <h3 class="stat-card-value"><?php echo number_format($fullCount); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-warning">
                            <i class="bi bi-people-fill"></i> At Capacity
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
                    <i class="bi bi-funnel-fill"></i>
                </div>
                <div class="stat-card-info">
                    <p class="stat-card-label">Filtered Results</p>
                    <h3 class="stat-card-value"><?php echo number_format($totalCourses); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-info">
                            <i class="bi bi-list-ul"></i> Current View
                        </span>
                    </div>
                </div>
            </div>
            <div class="stat-card-wave"></div>
        </div>
    </div>
</div>

<!-- Filters & Search -->
<div class="row g-4 mb-4">
    <div class="col-lg-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title">
                    <i class="bi bi-funnel-fill text-primary me-2"></i>
                    Search & Filter
                </h5>
            </div>
            <div class="admin-card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label fw-semibold">
                            <i class="bi bi-search me-2"></i>Search Courses
                        </label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Course code, name, or description...">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="status" class="form-label fw-semibold">
                            <i class="bi bi-funnel me-2"></i>Status Filter
                        </label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="full" <?php echo $status === 'full' ? 'selected' : ''; ?>>Full</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    
<!-- Courses Table -->
<div class="row g-4">
    <div class="col-lg-12">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h5 class="admin-card-title">
                    <i class="bi bi-journal-check text-primary me-2"></i>
                    Course Catalog
                </h5>
                <span class="badge bg-primary rounded-pill"><?php echo number_format($totalCourses); ?> Results</span>
            </div>
            <div class="admin-card-body">
                <?php if (empty($courses)): ?>
                    <div class="empty-state">
                        <i class="bi bi-book empty-state-icon"></i>
                        <p class="empty-state-text">No courses found matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="table-modern-wrapper">
                        <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>Course Details</th>
                                <th>Dept & Instructor</th>
                                <th>Schedule</th>
                                <th>Scale</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($course['course_code']); ?></div>
                                        <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                        <div class="text-muted small"><?php echo $course['credits']; ?> Credits • <?php echo htmlspecialchars($course['semester']); ?> <?php echo htmlspecialchars($course['academic_year']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($course['department'] ?? 'General'); ?></div>
                                        <div class="text-muted small"><i class="bi bi-person me-1"></i> <?php echo htmlspecialchars($course['instructor'] ?? 'TBA'); ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-muted"><?php echo htmlspecialchars($course['schedule'] ?? 'Not set'); ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="small me-2"><?php echo $course['enrolled_count']; ?>/<?php echo $course['max_capacity']; ?></div>
                                            <div class="progress flex-grow-1" style="height: 4px; max-width: 60px;">
                                                <?php $pct = ($course['enrolled_count'] / $course['max_capacity']) * 100; ?>
                                                <div class="progress-bar bg-<?php echo $pct > 90 ? 'danger' : ($pct > 70 ? 'warning' : 'success'); ?>" style="width: <?php echo $pct; ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Enrollment Load</div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-badge-<?php echo $course['status'] === 'active' ? 'success' : ($course['status'] === 'full' ? 'warning' : 'secondary'); ?>">
                                            <i class="bi bi-<?php echo $course['status'] === 'active' ? 'check-circle' : ($course['status'] === 'full' ? 'exclamation-circle' : 'pause-circle'); ?>"></i>
                                            <?php echo ucfirst($course['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i> Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/courses.php?edit=<?php echo $course['course_id']; ?>"><i class="bi bi-pencil me-2"></i> Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" onsubmit="return confirmDelete('Are you sure you want to deactivate this course?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="delete_course">
                                                        <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i> Deactivate</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
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
            
<!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Courses pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                            <i class="bi bi-chevron-left"></i> Previous
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

