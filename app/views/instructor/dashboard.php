<?php
// app/views/instructor/dashboard.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';

// Use stats passed from controller
$totalCourses = $stats['total_courses'] ?? 0;
$totalStudents = $stats['total_students'] ?? 0;
$totalQuizzes = $stats['total_quizzes'] ?? 0;
?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-3 stat-card" style="border-left-color: #6366f1;">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-soft-primary me-3">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0">Total Courses</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $totalCourses; ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 stat-card" style="border-left-color: #ec4899;">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-light text-pink me-3" style="color: #ec4899; background-color: #fdf2f8;">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0">Total Students</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $totalStudents; ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 stat-card" style="border-left-color: #06b6d4;">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-soft-info me-3" style="background-color: #ecfeff; color: #0891b2;">
                    <i class="bi bi-patch-question"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0">Total Quizzes</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $totalQuizzes; ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">Assigned Courses</h5>
                    <a href="?page=create-quiz" class="btn btn-sm btn-primary">+ Create Assessment</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Enrollments</th>
                                <th>Quizzes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($courses)): ?>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <p class="fw-bold mb-0"><?php echo htmlspecialchars($course['CourseName']); ?></p>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($course['Description'], 0, 50)); ?>...</small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo $course['StudentCount']; ?> Students
                                            <?php if (!empty($course['StudentNames'])): ?>
                                                <ul class="small text-muted mt-1 mb-0 ps-3">
                                                    <?php foreach (explode('||', $course['StudentNames']) as $studentName): ?>
                                                        <li><?php echo htmlspecialchars($studentName); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="small text-muted mt-1">No students yet</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $course['QuizCount']; ?></td>
                                        <td>
                                            <a href="?page=course-quizzes&course_id=<?php echo $course['CourseID']; ?>" class="btn btn-sm btn-light border text-primary" title="Manage Quizzes"><i class="bi bi-patch-question"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No courses have been assigned to you yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Quick Actions</h5>
                <div class="d-grid gap-2 mb-4">
                    <a href="?page=create-quiz" class="btn btn-outline-dark text-start">
                        <i class="bi bi-plus-square me-2"></i> Create a Quiz
                    </a>
                    <a href="?page=create-quiz&type=midterm" class="btn btn-outline-dark text-start">
                        <i class="bi bi-file-earmark-text me-2"></i> Create a Midterm
                    </a>
                    <a href="?page=create-quiz&type=final" class="btn btn-outline-dark text-start">
                        <i class="bi bi-award me-2"></i> Create a Final
                    </a>
                    <a href="?page=create-quiz&type=assignment" class="btn btn-outline-dark text-start">
                        <i class="bi bi-pencil-square me-2"></i> Create an Assignment
                    </a>
                    <a href="?page=upload-content" class="btn btn-outline-dark text-start">
                        <i class="bi bi-cloud-upload me-2"></i> Add Course Content
                    </a>
                    <a href="?page=manage-content" class="btn btn-outline-dark text-start">
                        <i class="bi bi-folder-check me-2"></i> Manage Content
                    </a>
                    <a href="?page=course-results" class="btn btn-outline-dark text-start">
                        <i class="bi bi-bar-chart me-2"></i> View Performance
                    </a>
                </div>

                <h5 class="card-title mb-3">Assessment Breakdown</h5>
                <ul class="list-group list-group-flush border-top">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-journal-text text-warning me-2"></i> Standard Quizzes</span>
                        <span class="badge bg-light text-dark border"><?php echo $stats['standard_quizzes']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-file-earmark-text text-info me-2"></i> Midterms</span>
                        <span class="badge bg-light text-dark border"><?php echo $stats['midterms']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-award text-dark me-2"></i> Finals</span>
                        <span class="badge bg-light text-dark border"><?php echo $stats['finals']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-folder-plus text-success me-2"></i> Assignments</span>
                        <span class="badge bg-light text-dark border"><?php echo $stats['assignments']; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>

