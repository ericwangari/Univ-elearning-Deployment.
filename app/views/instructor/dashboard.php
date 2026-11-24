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
                                            <div class="d-flex flex-column align-items-start">
                                                <span class="fw-semibold text-dark">
                                                    <?php echo (int)$course['StudentCount']; ?> <?php echo (int)$course['StudentCount'] === 1 ? 'Student' : 'Students'; ?>
                                                </span>
                                                <?php if (!empty($course['StudentCount']) && (int)$course['StudentCount'] > 0): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 mt-1" style="font-size: 0.78rem;" data-bs-toggle="modal" data-bs-target="#studentsModal_<?php echo $course['CourseID']; ?>">
                                                        <i class="bi bi-people me-1"></i>View Students
                                                    </button>
                                                <?php else: ?>
                                                    <small class="text-muted">No students yet</small>
                                                <?php endif; ?>
                                            </div>
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

<?php if (!empty($courses)): ?>
    <?php foreach ($courses as $course): ?>
        <?php if (!empty($course['StudentCount']) && (int)$course['StudentCount'] > 0): ?>
            <div class="modal fade" id="studentsModal_<?php echo $course['CourseID']; ?>" tabindex="-1" aria-labelledby="studentsModalLabel_<?php echo $course['CourseID']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-light">
                            <div>
                                <h5 class="modal-title fw-bold" id="studentsModalLabel_<?php echo $course['CourseID']; ?>">
                                    <i class="bi bi-people text-primary me-2"></i>Enrolled Students
                                </h5>
                                <small class="text-muted"><?php echo htmlspecialchars($course['CourseName']); ?> &middot; <?php echo (int)$course['StudentCount']; ?> <?php echo (int)$course['StudentCount'] === 1 ? 'student' : 'students'; ?> enrolled</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <?php
                                $studentsList = !empty($course['StudentNames']) ? explode('||', $course['StudentNames']) : [];
                            ?>
                            <?php if (!empty($studentsList)): ?>
                                <div class="p-3 bg-white border-bottom">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0 ps-0 shadow-none" placeholder="Search student name..." onkeyup="filterStudentsModal(this, 'studentList_<?php echo $course['CourseID']; ?>')">
                                    </div>
                                </div>
                                <ul class="list-group list-group-flush" id="studentList_<?php echo $course['CourseID']; ?>" style="max-height: 350px; overflow-y: auto;">
                                    <?php foreach ($studentsList as $sName): ?>
                                        <li class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
                                            <div class="d-flex align-items-center">
                                                <span class="rounded-circle bg-soft-primary text-primary d-inline-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-size: 0.85rem; font-weight: 600;">
                                                    <?php echo strtoupper(substr(trim($sName), 0, 1)); ?>
                                                </span>
                                                <span class="fw-medium text-dark student-name-item"><?php echo htmlspecialchars(trim($sName)); ?></span>
                                            </div>
                                            <span class="badge bg-light text-muted border">Student</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    No students enrolled yet.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <span class="small text-muted me-auto"><?php echo count($studentsList); ?> total enrolled</span>
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function filterStudentsModal(input, listId) {
    const filter = input.value.toLowerCase();
    const list = document.getElementById(listId);
    if (!list) return;
    const items = list.getElementsByTagName('li');
    for (let i = 0; i < items.length; i++) {
        const nameEl = items[i].querySelector('.student-name-item');
        const text = nameEl ? nameEl.textContent || nameEl.innerText : '';
        items[i].style.display = text.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
    }
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>

