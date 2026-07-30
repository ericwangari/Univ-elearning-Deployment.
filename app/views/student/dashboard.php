<?php
// app/views/student/dashboard.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';

// Calculate completed courses
$completed_count = 0;
foreach ($enrolled_courses as $course) {
    if ($course['CompletionStatus'] === 'Completed') {
        $completed_count++;
    }
}

// Average score is provided by the controller
$average_score = $average_score ?? 0;
?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-3 stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-soft-primary me-3">
                    <i class="bi bi-journal-check"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0">Enrolled Courses</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $enrolled_count; ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 stat-card" style="border-left-color: #10b981;">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-soft-success me-3">
                    <i class="bi bi-trophy"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0">Completed</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $completed_count; ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 stat-card" style="border-left-color: #f59e0b;">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-soft-warning me-3">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0">Average Score</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $average_score; ?>%</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <h5 class="card-title mb-0">My Recent Courses</h5>
                    <div class="d-flex gap-2">
                        <form method="GET" class="input-group input-group-sm dashboard-course-search">
                            <input type="hidden" name="page" value="dashboard">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" name="course_search" class="form-control" placeholder="Search my courses" value="<?php echo htmlspecialchars($course_search ?? ''); ?>">
                        </form>
                        <a href="?page=my-enrollments" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($enrolled_courses)): ?>
                                <?php foreach (array_slice($enrolled_courses, 0, 5) as $course): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="p-2 bg-light rounded me-2">
                                                    <i class="bi bi-book text-primary"></i>
                                                </div>
                                                <span><?php echo htmlspecialchars($course['CourseName']); ?></span>
                                            </div>
                                        </td>
                                        <td style="width: 200px;">
                                            <?php $progress = (int)($course['Progress'] ?? 0); ?>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar <?php echo ($course['CompletionStatus'] === 'Completed') ? 'bg-success' : 'bg-primary'; ?>" style="width: <?php echo $progress; ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo $progress; ?>%</small>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = ($course['CompletionStatus'] === 'Completed') ? 'bg-soft-success' : 'bg-soft-primary';
                                            $badge_text = ($course['CompletionStatus'] === 'Completed') ? 'Completed' : 'In Progress';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?> px-2 py-1"><?php echo $badge_text; ?></span>
                                        </td>
                                        <td>
                                            <a href="?page=course-details&id=<?php echo $course['CourseID']; ?>" class="btn btn-sm btn-light">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No courses enrolled yet. <a href="?page=courses">Browse courses</a>
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
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">Recent Assessments</h5>
                    <a href="?page=my-results" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                
                <?php if (!empty($recent_results)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_results as $result): ?>
                            <?php 
                                $type_class = 'bg-secondary';
                                switch($result['QuizType']) {
                                    case 'Midterm': $type_class = 'bg-info'; break;
                                    case 'Final': $type_class = 'bg-dark'; break;
                                    case 'Assignment': $type_class = 'bg-primary'; break;
                                    case 'Quiz': $type_class = 'bg-warning text-dark'; break;
                                }
                            ?>
                            <div class="list-group-item px-0 border-0 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="fw-bold mb-0 text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($result['QuizName']); ?></h6>
                                    <span class="badge <?php echo $type_class; ?> ms-2"><?php echo $result['QuizType']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><?php echo date('M d', strtotime($result['SubmittedAt'])); ?></small>
                                    <span class="fw-bold <?php echo ($result['Score'] >= ($result['TotalMarks'] * 0.7)) ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $result['Score']; ?>/<?php echo $result['TotalMarks']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <img src="https://illustrations.popsy.co/white/student-going-to-school.svg" alt="Learn" class="img-fluid mb-3" style="max-height: 100px;">
                        <p class="text-muted small">No assessments taken yet.</p>
                        <a href="?page=courses" class="btn btn-sm btn-primary">Start Learning</a>
                    </div>
                <?php endif; ?>

                <h5 class="card-title mt-4 mb-3">My Performance</h5>
                <ul class="list-group list-group-flush border-top">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-journal-text text-warning me-2"></i> Quizzes Done</span>
                        <span class="badge bg-soft-warning text-dark border"><?php echo $assessment_stats['quizzes']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-file-earmark-text text-info me-2"></i> Midterms Done</span>
                        <span class="badge bg-soft-info text-dark border"><?php echo $assessment_stats['midterms']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-award text-dark me-2"></i> Finals Done</span>
                        <span class="badge bg-soft-dark text-dark border"><?php echo $assessment_stats['finals']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-clipboard-check text-primary me-2"></i> Assignments Done</span>
                        <span class="badge bg-soft-primary text-dark border"><?php echo $assessment_stats['assignments']; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>

