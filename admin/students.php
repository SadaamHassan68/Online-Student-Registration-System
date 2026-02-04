<?php
/**
 * Admin - Student Management (Professional Design)
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/validation_functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_ADMIN);

$error = '';
$success = '';

// Get filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$status = sanitizeInput($_GET['status'] ?? '');
$admission_status = sanitizeInput($_GET['admission_status'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Handle actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        $action = $_POST['action'] ?? '';
        
        if (!verifyCSRFToken($csrfToken)) {
            $error = 'Invalid security token.';
        } else {
            if ($action === 'update_status') {
                $studentId = intval($_POST['student_id'] ?? 0);
                $newStatus = $_POST['status'] ?? '';
                
                if (in_array($newStatus, ['active', 'inactive'])) {
                    $stmt = $conn->prepare("UPDATE students SET status = ? WHERE student_id = ?");
                    $stmt->execute([$newStatus, $studentId]);
                    $success = 'Student status updated successfully.';
                }
            } elseif ($action === 'update_admission') {
                $studentId = intval($_POST['student_id'] ?? 0);
                $newAdmissionStatus = $_POST['admission_status'] ?? '';
                
                if (in_array($newAdmissionStatus, ['pending', 'approved', 'rejected'])) {
                    $stmt = $conn->prepare("UPDATE students SET admission_status = ? WHERE student_id = ?");
                    $stmt->execute([$newAdmissionStatus, $studentId]);
                    $success = 'Admission status updated successfully.';
                }
            }
        }
    }
    
    // Build query
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($status)) {
        $where[] = "status = ?";
        $params[] = $status;
    }
    
    if (!empty($admission_status)) {
        $where[] = "admission_status = ?";
        $params[] = $admission_status;
    }
    
    $whereClause = empty($where) ? '1=1' : implode(' AND ', $where);
    
    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE $whereClause");
    $countStmt->execute($params);
    $totalStudents = $countStmt->fetchColumn();
    $totalPages = ceil($totalStudents / $limit);
    
    // Get students
    $sql = "SELECT * FROM students WHERE $whereClause ORDER BY registration_date DESC LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    // Get statistics
    $stmt = $conn->query("SELECT COUNT(*) FROM students");
    $totalCount = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM students WHERE status = 'active'");
    $activeCount = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM students WHERE status = 'inactive'");
    $inactiveCount = $stmt->fetchColumn();
    
} catch (Exception $e) {
    error_log("Students management error: " . $e->getMessage());
    $students = [];
    $totalPages = 0;
    $totalCount = 0;
    $activeCount = 0;
    $inactiveCount = 0;
}

$pageTitle = 'Manage Students - ' . APP_NAME;
$csrfToken = generateCSRFToken();

include __DIR__ . '/../includes/header.php';
?>

<!-- Dashboard Header -->
<div class="dashboard-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h2 class="dashboard-title mb-1">Student Management</h2>
            <p class="dashboard-subtitle text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>! Manage all student accounts and registrations.</p>
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
                    <p class="stat-card-label">Active Students</p>
                    <h3 class="stat-card-value"><?php echo number_format($activeCount); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-success">
                            <i class="bi bi-arrow-up-circle"></i> Verified
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
                    <i class="bi bi-pause-circle-fill"></i>
                </div>
                <div class="stat-card-info">
                    <p class="stat-card-label">Inactive Students</p>
                    <h3 class="stat-card-value"><?php echo number_format($inactiveCount); ?></h3>
                    <div class="stat-card-footer">
                        <span class="stat-card-badge stat-badge-warning">
                            <i class="bi bi-exclamation-circle"></i> Suspended
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
                    <h3 class="stat-card-value"><?php echo number_format($totalStudents); ?></h3>
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
                            <i class="bi bi-search me-2"></i>Search Students
                        </label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by name or email...">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="status" class="form-label fw-semibold">
                            <i class="bi bi-person-check me-2"></i>Account Status
                        </label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="admission_status" class="form-label fw-semibold">
                            <i class="bi bi-shield-check me-2"></i>Admission Status
                        </label>
                        <select class="form-select" id="admission_status" name="admission_status">
                            <option value="">All Admissions</option>
                            <option value="pending" <?php echo $admission_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $admission_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $admission_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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

<!-- Students Table -->
<div class="row g-4">
    <div class="col-lg-12">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h5 class="admin-card-title">
                    <i class="bi bi-clock-history text-primary me-2"></i>
                    Student Records
                </h5>
                <span class="badge bg-primary rounded-pill"><?php echo number_format($totalStudents); ?> Results</span>
            </div>
            <div class="admin-card-body">
                <?php if (empty($students)): ?>
                    <div class="empty-state">
                        <i class="bi bi-people empty-state-icon"></i>
                        <p class="empty-state-text">No students found matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="table-modern-wrapper">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Academic Info</th>
                                    <th>Status</th>
                                    <th>Admission</th>
                                    <th class="text-end">Registration Date</th>
                                    <th class="text-end" style="width: 200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="student-avatar">
                                                    <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                                </div>
                                                <div class="ms-3">
                                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                                    <small class="text-muted">ID: #<?php echo $student['student_id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-medium"><?php echo htmlspecialchars($student['email']); ?></div>
                                            <div class="small text-primary fw-bold"><?php echo htmlspecialchars($student['intended_major'] ?? 'No Major Set'); ?></div>
                                            <small class="text-muted">GPA: <?php echo number_format($student['high_school_grade'] ?? 0, 2); ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge status-badge-<?php echo $student['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <i class="bi bi-<?php echo $student['status'] === 'active' ? 'check-circle' : 'pause-circle'; ?>"></i>
                                                <?php echo ucfirst($student['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $admClass = [
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger'
                                            ][$student['admission_status'] ?? 'pending'];
                                            ?>
                                            <span class="status-badge status-badge-<?php echo $admClass; ?>">
                                                <i class="bi bi-<?php echo $student['admission_status'] === 'approved' ? 'check-circle-fill' : ($student['admission_status'] === 'rejected' ? 'x-circle-fill' : 'hourglass-split'); ?>"></i>
                                                <?php echo ucfirst($student['admission_status'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-muted"><?php echo date('M d, Y', strtotime($student['registration_date'])); ?></span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex flex-column gap-1 align-items-end">
                                                <!-- Admission Action -->
                                                <?php if ($student['admission_status'] === 'pending'): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                            <input type="hidden" name="action" value="update_admission">
                                                            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                                            <input type="hidden" name="admission_status" value="approved">
                                                            <button type="submit" class="btn btn-success" title="Approve Admission">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                            <input type="hidden" name="action" value="update_admission">
                                                            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                                            <input type="hidden" name="admission_status" value="rejected">
                                                            <button type="submit" class="btn btn-danger" title="Reject Admission">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Account Action -->
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $student['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $student['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success'; ?> rounded-pill px-3 py-1">
                                                        <i class="bi bi-<?php echo $student['status'] === 'active' ? 'pause' : 'play'; ?>-fill"></i>
                                                        <?php echo $student['status'] === 'active' ? 'Suspend' : 'Activate'; ?>
                                                    </button>
                                                </form>
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
        <nav aria-label="Students pagination" class="mt-4">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
