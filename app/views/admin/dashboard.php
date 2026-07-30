<?php
// app/views/admin/dashboard.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';

// Use stats passed from controller
$totalUsers = $stats['total_users'] ?? 0;
$totalCourses = $stats['total_courses'] ?? 0;
$totalEnrollments = $stats['total_enrollments'] ?? 0;
$totalQuizzes = $stats['total_quizzes'] ?? 0;
$totalInstructors = $stats['total_instructors'] ?? 0;
$totalStudents = $stats['total_students'] ?? 0;
$pendingInstructors = $stats['pending_instructors'] ?? 0;
$overviewPeriod = $overviewPeriod ?? 'month';
$periodStats = $periodStats ?? $stats;
$periodLabels = [
    'day' => 'Today',
    'month' => 'This Month',
    'year' => 'This Year',
];
$periodTitle = $periodLabels[$overviewPeriod] ?? $periodLabels['month'];
$periodUsers = $periodStats['total_users'] ?? 0;
$periodCourses = $periodStats['total_courses'] ?? 0;
$periodEnrollments = $periodStats['total_enrollments'] ?? 0;
$periodQuizzes = $periodStats['total_quizzes'] ?? 0;
$periodInstructors = $periodStats['total_instructors'] ?? 0;
$periodStudents = $periodStats['total_students'] ?? 0;
$periodPendingInstructors = $periodStats['pending_instructors'] ?? 0;
?>

<div class="row g-4 mb-4">
    <?php if ($pendingInstructors > 0): ?>
    <div class="col-12">
        <div class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center mb-0">
            <div>
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                There are <strong><?php echo $pendingInstructors; ?></strong> instructor registration(s) waiting for your approval.
            </div>
            <a href="?page=manage-instructors" class="btn btn-warning btn-sm fw-bold">Review Now</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-md-3">
        <a href="?page=admin-users" class="card p-3 stat-card stat-card-link text-decoration-none text-reset" style="border-left-color: #6366f1;" aria-label="Open user management">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-soft-primary me-3">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0">Total Users</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $totalUsers; ?></h3>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?page=admin-courses" class="card p-3 stat-card stat-card-link text-decoration-none text-reset" style="border-left-color: #10b981;" aria-label="Open course management">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-soft-success me-3">
                    <i class="bi bi-book"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0">Courses</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $totalCourses; ?></h3>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?page=admin-courses" class="card p-3 stat-card stat-card-link text-decoration-none text-reset" style="border-left-color: #f59e0b;" aria-label="Open courses with enrollment counts">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-soft-warning me-3">
                    <i class="bi bi-person-check"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0">Enrollments</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $totalEnrollments; ?></h3>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?page=all-results" class="card p-3 stat-card stat-card-link text-decoration-none text-reset" style="border-left-color: #ef4444;" aria-label="Open assessment results">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-soft-danger me-3">
                    <i class="bi bi-patch-question"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0">Total Quizzes</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $totalQuizzes; ?></h3>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="card-title mb-0">System Overview</h5>
                        <div class="small text-muted"><?php echo $periodTitle; ?></div>
                    </div>
                    <div class="btn-group" role="group">
                        <a href="?page=dashboard&period=day" class="btn btn-sm btn-outline-secondary <?php echo $overviewPeriod === 'day' ? 'active' : ''; ?>">Day</a>
                        <a href="?page=dashboard&period=month" class="btn btn-sm btn-outline-secondary <?php echo $overviewPeriod === 'month' ? 'active' : ''; ?>">Month</a>
                        <a href="?page=dashboard&period=year" class="btn btn-sm btn-outline-secondary <?php echo $overviewPeriod === 'year' ? 'active' : ''; ?>">Year</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">Total Instructors</td>
                                <td><?php echo $periodInstructors; ?></td>
                                <td><?php echo $periodUsers > 0 ? round(($periodInstructors / $periodUsers) * 100, 1) : 0; ?>%</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Total Students</td>
                                <td><?php echo $periodStudents; ?></td>
                                <td><?php echo $periodUsers > 0 ? round(($periodStudents / $periodUsers) * 100, 1) : 0; ?>%</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-warning">Pending Instructors</td>
                                <td class="text-warning fw-bold"><?php echo $periodPendingInstructors; ?></td>
                                <td><?php echo $periodUsers > 0 ? round(($periodPendingInstructors / $periodUsers) * 100, 1) : 0; ?>%</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Average Students per Course</td>
                                <td><?php echo $periodCourses > 0 ? round($periodEnrollments / $periodCourses, 1) : 0; ?></td>
                                <td>Per Course</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Average Quizzes per Course</td>
                                <td><?php echo $periodCourses > 0 ? round($periodQuizzes / $periodCourses, 1) : 0; ?></td>
                                <td>Per Course</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>

