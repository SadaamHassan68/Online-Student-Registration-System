<?php
/**
 * Admin - Enrollment Management
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/validation_functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_ADMIN);

$error = '';
$success = '';

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
            $adminId = getCurrentUserId();
            
            if ($action === 'approve' || $action === 'reject' || $action === 'drop') {
                $enrollmentId = intval($_POST['enrollment_id'] ?? 0);
                
                if ($action === 'approve') {
                    $stmt = $conn->prepare("
                        UPDATE enrollments 
                        SET status = 'enrolled', approved_by = ?, approved_at = NOW() 
                        WHERE enrollment_id = ?
                    ");
                    $stmt->execute([$adminId, $enrollmentId]);
                    $success = 'Enrollment approved successfully.';
                } elseif ($action === 'reject' || $action === 'drop') {
                    $stmt = $conn->prepare("
                        UPDATE enrollments 
                        SET status = 'dropped' 
                        WHERE enrollment_id = ?
                    ");
                    $stmt->execute([$enrollmentId]);
                    
                    // Update course enrollment count
                    $stmt = $conn->prepare("
                        SELECT course_id FROM enrollments WHERE enrollment_id = ?
                    ");
                    $stmt->execute([$enrollmentId]);
                    $enrollment = $stmt->fetch();
                    
                    if ($enrollment) {
                        $stmt = $conn->prepare("
                            UPDATE courses 
                            SET current_enrollment = GREATEST(0, current_enrollment - 1),
                                status = CASE WHEN current_enrollment - 1 < max_capacity THEN 'active' ELSE status END
                            WHERE course_id = ?
                        ");
                        $stmt->execute([$enrollment['course_id']]);
                    }
                    
                    $success = 'Enrollment ' . ($action === 'reject' ? 'rejected' : 'dropped') . ' successfully.';
                }
            } elseif ($action === 'update_grade') {
                $enrollmentId = intval($_POST['enrollment_id'] ?? 0);
                $grade = floatval($_POST['grade'] ?? 0);
                
                if ($grade >= 0 && $grade <= 100) {
                    $stmt = $conn->prepare("UPDATE enrollments SET grade = ? WHERE enrollment_id = ?");
                    $stmt->execute([$grade, $enrollmentId]);
                    $success = 'Grade updated successfully.';
                } else {
                    $error = 'Grade must be between 0 and 100.';
                }
            }
        } catch (Exception $e) {
            error_log("Enrollment management error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
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
        $where[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ? OR c.course_code LIKE ? OR c.course_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($status)) {
        $where[] = "e.status = ?";
        $params[] = $status;
    }
    
    $whereClause = empty($where) ? '1=1' : implode(' AND ', $where);
    
    // Get total count
    $countStmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN courses c ON e.course_id = c.course_id
        WHERE $whereClause
    ");
    $countStmt->execute($params);
    $totalEnrollments = $countStmt->fetchColumn();
    $totalPages = ceil($totalEnrollments / $limit);
    
    // Get enrollments
    $sql = "
        SELECT e.*, s.first_name, s.last_name, s.email, c.course_code, c.course_name, c.credits, c.instructor
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN courses c ON e.course_id = c.course_id
        WHERE $whereClause
        ORDER BY e.enrollment_date DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $enrollments = $stmt->fetchAll();
    
    // Get statistics
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollments");
    $totalCount = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollments WHERE status = 'enrolled'");
    $enrolledCount = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollments WHERE status = 'pending'");
    $pendingCount = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollments WHERE status = 'completed'");
    $completedCount = $stmt->fetchColumn();
    
} catch (Exception $e) {
    error_log("Enrollments error: " . $e->getMessage());
    $enrollments = [];
    $totalPages = 0;
    $totalCount = 0;
    $enrolledCount = 0;
    $pendingCount = 0;
    $completedCount = 0;
}

$pageTitle = 'Manage Enrollments - ' . APP_NAME;
$csrfToken = generateCSRFToken();

include __DIR__ . '/../includes/header.php';
?>

<!-- Dashboard Header -->
<div class="dashboard-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h2 class="dashboard-title mb-1">Enrollment Management</h2>
            <p class="dashboard-subtitle text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>! Authorize and manage student course registrations.</p>
        </div>
        <div class="dashboard-date">
            <i class="bi bi-calendar3 me-2"></i>
            <span><?php echo date('l, F j, Y'); ?></span>
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

<!-- Key Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-primary">
            <div class="stat-card-content">
                <div class="stat-card-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="stat-card-info">
                    <p class="stat-card-label">Total Enrollments</p>
                    <h3 class="stat-card-value"><?php echo number_format($totalCount); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-success">
                            <i class="bi bi-check-circle-fill"></i> <?php echo number_format($enrolledCount); ?> Enrolled
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
                    <p class="stat-card-label">Active Enrollments</p>
                    <h3 class="stat-card-value"><?php echo number_format($enrolledCount); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-success">
                            <i class="bi bi-arrow-up-circle"></i> Confirmed
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
                    <p class="stat-card-label">Pending Review</p>
                    <h3 class="stat-card-value"><?php echo number_format($pendingCount); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-warning">
                            <i class="bi bi-clock-history"></i> Requires Action
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
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div class="stat-card-info">
                    <p class="stat-card-label">Completed</p>
                    <h3 class="stat-card-value"><?php echo number_format($completedCount); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-info">
                            <i class="bi bi-check-all"></i> Finished
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
                            <i class="bi bi-search me-2"></i>Search Enrollments
                        </label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Student name, email, or course...">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="status" class="form-label fw-semibold">
                            <i class="bi bi-funnel me-2"></i>Status Filter
                        </label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="enrolled" <?php echo $status === 'enrolled' ? 'selected' : ''; ?>>Enrolled</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="dropped" <?php echo $status === 'dropped' ? 'selected' : ''; ?>>Dropped</option>
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

<!-- Enrollments Table -->
<div class="row g-4">
    <div class="col-lg-12">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h5 class="admin-card-title">
                    <i class="bi bi-journal-check text-primary me-2"></i>
                    Enrollment Records
                </h5>
                <span class="badge bg-primary rounded-pill"><?php echo number_format($totalEnrollments); ?> Results</span>
            </div>
            <div class="admin-card-body">
                <?php if (empty($enrollments)): ?>
                    <div class="empty-state">
                        <i class="bi bi-journal-x empty-state-icon"></i>
                        <p class="empty-state-text">No enrollment records found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-modern-wrapper">
                        <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Credits</th>
                                <th>Grade</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $enrollment): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($enrollment['email']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($enrollment['course_code']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($enrollment['course_name']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo $enrollment['credits']; ?> Cr</span>
                                    </td>
                                    <td>
                                        <?php if ($enrollment['status'] === 'completed' || $enrollment['status'] === 'enrolled'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="update_grade">
                                                <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                                                <div class="input-group input-group-sm" style="width: 100px;">
                                                    <input type="number" class="form-control border-end-0" name="grade" 
                                                           value="<?php echo $enrollment['grade'] ?? ''; ?>" 
                                                           min="0" max="100" step="0.01" style="border-radius: 8px 0 0 8px;">
                                                    <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 0 8px 8px 0;">Set</button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeConfig = [
                                            'enrolled' => ['class' => 'badge-success', 'icon' => 'check-circle'],
                                            'pending' => ['class' => 'badge-warning', 'icon' => 'clock'],
                                            'completed' => ['class' => 'badge-info', 'icon' => 'check-all'],
                                            'dropped' => ['class' => 'badge-danger', 'icon' => 'x-circle']
                                        ];
                                        $statusType = $enrollment['status'];
                                        $config = $badgeConfig[$statusType] ?? ['class' => 'badge-secondary', 'icon' => 'circle'];
                                        ?>
                                        <span class="status-badge status-badge-<?php echo $config['class']; ?>">
                                            <i class="bi bi-<?php echo $config['icon']; ?>"></i>
                                            <?php echo ucfirst($statusType); ?>
                                        </span>
                                        <div class="text-muted small mt-1">
                                            <?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($enrollment['status'] === 'pending'): ?>
                                            <div class="d-flex gap-2 justify-content-end">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">
                                                        <i class="bi bi-check-lg me-1"></i>Approve
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline" onsubmit="return confirmDelete('Are you sure?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                        <i class="bi bi-x-lg me-1"></i>Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php elseif ($enrollment['status'] === 'enrolled'): ?>
                                            <form method="POST" onsubmit="return confirmDelete('Are you sure you want to drop this?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="drop">
                                                <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                                    <i class="bi bi-trash me-1"></i>Drop
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
        <nav aria-label="Enrollments pagination" class="mt-4">
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

