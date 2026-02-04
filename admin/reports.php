<?php
/**
 * Admin - Reports and Analytics
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_ADMIN);

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Admission status breakdown
    $stmt = $conn->query("
        SELECT admission_status, COUNT(*) as count
        FROM students
        GROUP BY admission_status
    ");
    $admissionStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Top Intended Majors
    $stmt = $conn->query("
        SELECT intended_major, COUNT(*) as count
        FROM students
        WHERE intended_major IS NOT NULL
        GROUP BY intended_major
        ORDER BY count DESC
        LIMIT 5
    ");
    $majorStats = $stmt->fetchAll();

    // Enrollment statistics by course
    $stmt = $conn->query("
        SELECT c.course_code, c.course_name, c.max_capacity, 
               COUNT(e.enrollment_id) as enrolled_count,
               (c.max_capacity - COUNT(e.enrollment_id)) as available_spots,
               ROUND((COUNT(e.enrollment_id) / c.max_capacity * 100), 2) as utilization_rate
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'enrolled'
        WHERE c.status = 'active'
        GROUP BY c.course_id
        ORDER BY enrolled_count DESC
    ");
    $courseStats = $stmt->fetchAll();
    
    // Enrollment statistics by department
    $stmt = $conn->query("
        SELECT c.department, 
               COUNT(DISTINCT c.course_id) as course_count,
               COUNT(e.enrollment_id) as enrollment_count,
               COUNT(DISTINCT e.student_id) as student_count
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'enrolled'
        WHERE c.status = 'active' AND c.department IS NOT NULL
        GROUP BY c.department
        ORDER BY enrollment_count DESC
    ");
    $departmentStats = $stmt->fetchAll();
    
    // Student demographics
    $stmt = $conn->query("
        SELECT gender, COUNT(*) as count
        FROM students
        GROUP BY gender
    ");
    $genderStats = $stmt->fetchAll();
    
    // Enrollment status breakdown
    $stmt = $conn->query("
        SELECT status, COUNT(*) as count
        FROM enrollments
        GROUP BY status
    ");
    $enrollmentStatusStats = $stmt->fetchAll();
    
    // Get summary statistics
    $stmt = $conn->query("SELECT COUNT(*) FROM students");
    $totalStudents = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM courses WHERE status = 'active'");
    $totalCourses = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollments WHERE status = 'enrolled'");
    $totalEnrollments = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM students WHERE admission_status = 'pending'");
    $pendingAdmissions = $stmt->fetchColumn();

} catch (Exception $e) {
    error_log("Reports error: " . $e->getMessage());
    $courseStats = [];
    $departmentStats = [];
    $genderStats = [];
    $enrollmentStatusStats = [];
    $admissionStats = [];
    $majorStats = [];
    $totalStudents = 0;
    $totalCourses = 0; 
    $totalEnrollments = 0;
    $pendingAdmissions = 0;
}

// Prepare chart data
$chartData = [
    'admission' => [
        'labels' => array_map('ucfirst', array_keys($admissionStats)),
        'values' => array_values($admissionStats)
    ],
    'majors' => [
        'labels' => array_column($majorStats, 'intended_major'),
        'values' => array_column($majorStats, 'count')
    ],
    'enrollment' => [
        'labels' => array_map(function($stat) { return ucfirst($stat['status']); }, $enrollmentStatusStats),
        'values' => array_column($enrollmentStatusStats, 'count')
    ],
    'gender' => [
        'labels' => array_map(function($stat) { return ucfirst($stat['gender']); }, $genderStats),
        'values' => array_column($genderStats, 'count')
    ]
];

$pageTitle = 'Reports & Analytics - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        --success-gradient: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
        --info-gradient: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
        --warning-gradient: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
        --danger-gradient: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
    }

    .report-container {
        padding: 2rem;
        background-color: #f8f9fc;
    }

    .stat-card-new {
        border: none;
        border-radius: 15px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }

    .stat-card-new:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }

    .card-icon-bg {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.5rem;
    }

    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    .modern-table thead th {
        background-color: #f8f9fc;
        text-transform: uppercase;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        color: #4e73df;
        border-bottom: 2px solid #e3e6f0;
    }

    @media print {
        .btn, .sidebar, .navbar { display: none !important; }
        .report-container { padding: 0; background: white; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    }
</style>

<div class="report-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold">Executive Analytics Dashboard</h1>
            <p class="text-muted">Academic year insight and operational performance metrics.</p>
        </div>
        <div class="d-print-none">
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-download me-2"></i> Export Report
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card-new shadow-sm h-100 border-start border-primary border-5">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalStudents); ?></div>
                    </div>
                    <div class="card-icon-bg bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stat-card-new shadow-sm h-100 border-start border-warning border-5">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Admissions</div>
                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($pendingAdmissions); ?></div>
                    </div>
                    <div class="card-icon-bg bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stat-card-new shadow-sm h-100 border-start border-success border-5">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Courses</div>
                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalCourses); ?></div>
                    </div>
                    <div class="card-icon-bg bg-success bg-opacity-10 text-success">
                        <i class="bi bi-book"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stat-card-new shadow-sm h-100 border-start border-info border-5">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Enrollments</div>
                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalEnrollments); ?></div>
                    </div>
                    <div class="card-icon-bg bg-info bg-opacity-10 text-info">
                        <i class="bi bi-check2-all"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Hub -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0 d-flex align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line text-primary me-2"></i> Interest Enrollment Pattern</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="majorsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-shield-check text-success me-2"></i> Admission Mix</h5>
                </div>
                <div class="card-body d-flex align-items-center">
                    <div class="chart-container">
                        <canvas id="admissionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tertiary Charts & Tables -->
    <div class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-info me-2"></i> Enrollment Status</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="enrollmentStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-gender-ambiguous text-warning me-2"></i> Gender Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-building text-secondary me-2"></i> Departmental Load</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle modern-table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">Department</th>
                                    <th class="text-center">Courses</th>
                                    <th class="text-center">Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departmentStats as $stat): ?>
                                <tr>
                                    <td class="ps-3 fw-medium"><?php echo htmlspecialchars($stat['department']); ?></td>
                                    <td class="text-center small"><?php echo $stat['course_count']; ?></td>
                                    <td class="text-center"><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3"><?php echo $stat['enrollment_count']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Detailed Analysis -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-table text-primary me-2"></i> Course Capacity Analytics</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle modern-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Course Code & Name</th>
                            <th>Enrolled</th>
                            <th>Max Capacity</th>
                            <th style="width: 30%;">Utilization</th>
                            <th class="text-end pe-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courseStats as $stat): 
                            $badgeType = ($stat['utilization_rate'] > 90) ? 'danger' : ($stat['utilization_rate'] > 75 ? 'warning' : 'success');
                        ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($stat['course_code']); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($stat['course_name']); ?></div>
                            </td>
                            <td><span class="fw-bold"><?php echo $stat['enrolled_count']; ?></span></td>
                            <td><?php echo $stat['max_capacity']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1" style="height: 6px; border-radius: 10px;">
                                        <div class="progress-bar bg-<?php echo $badgeType; ?>" role="progressbar" style="width: <?php echo $stat['utilization_rate']; ?>%"></div>
                                    </div>
                                    <span class="ms-3 small fw-bold text-<?php echo $badgeType; ?>"><?php echo $stat['utilization_rate']; ?>%</span>
                                </div>
                            </td>
                            <td class="text-end pe-3">
                                <?php if ($stat['available_spots'] <= 0): ?>
                                    <span class="badge bg-danger">At Capacity</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border"><?php echo $stat['available_spots']; ?> spots left</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const chartConfig = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
        }
    };

    // Admission Chart
    new Chart(document.getElementById('admissionChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chartData['admission']['labels']); ?>,
            datasets: [{
                data: <?php echo json_encode($chartData['admission']['values']); ?>,
                backgroundColor: ['#f6c23e', '#1cc88a', '#e74a3b'],
                hoverOffset: 10
            }]
        },
        options: { ...chartConfig, cutout: '70%' }
    });

    // Majors Chart
    new Chart(document.getElementById('majorsChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartData['majors']['labels']); ?>,
            datasets: [{
                label: 'Enrolled Interest',
                data: <?php echo json_encode($chartData['majors']['values']); ?>,
                backgroundColor: '#4e73df',
                borderRadius: 8,
                barThickness: 35
            }]
        },
        options: {
            ...chartConfig,
            indexAxis: 'y',
            scales: { x: { grid: { display: false } }, y: { grid: { display: false } } }
        }
    });

    // Enrollment Status Chart
    new Chart(document.getElementById('enrollmentStatusChart'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($chartData['enrollment']['labels']); ?>,
            datasets: [{
                data: <?php echo json_encode($chartData['enrollment']['values']); ?>,
                backgroundColor: ['#1cc88a', '#f6c23e', '#36b9cc', '#e74a3b']
            }]
        },
        options: chartConfig
    });

    // Gender Chart
    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chartData['gender']['labels']); ?>,
            datasets: [{
                data: <?php echo json_encode($chartData['gender']['values']); ?>,
                backgroundColor: ['#4e73df', '#f686bd', '#36b9cc']
            }]
        },
        options: { ...chartConfig, cutout: '65%' }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

