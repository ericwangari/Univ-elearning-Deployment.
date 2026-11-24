<?php
// app/views/instructor/manage_courses.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Courses</h2>
            <p class="text-muted">Create quizzes, midterms, and finals for assigned courses</p>
        </div>
        <a href="?page=create-quiz" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> New Assessment
        </a>
    </div>

    <form method="GET" class="row g-3 mb-4 align-items-end">
        <input type="hidden" name="page" value="manage-courses">
        <div class="col-md-4">
            <label class="form-label small text-uppercase text-muted">Course</label>
            <select name="course_id" class="form-select">
                <option value="">All courses</option>
                <?php foreach ($courseOptions as $courseOption): ?>
                    <option value="<?php echo (int)$courseOption['CourseID']; ?>" <?php echo (($selected_course_id ?? '') == $courseOption['CourseID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($courseOption['CourseName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label small text-uppercase text-muted">Search</label>
            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Search course or student">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span class="text-muted">Filtered Courses</span>
                    <span class="h4 mb-0 fw-bold"><?php echo count($courses); ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span class="text-muted">Students in View</span>
                    <span class="h4 mb-0 fw-bold"><?php echo (int)($filtered_student_total ?? 0); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Course Name</th>
                            <th>Students</th>
                            <th>Quizzes</th>

                            <th>Status</th>
                            <th class="pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <p class="text-muted mb-0">No courses have been assigned to you yet.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($course['CourseName']); ?></td>
                                    <td>
                                        <div class="d-flex flex-column align-items-start">
                                            <span class="badge bg-soft-primary px-2 py-1 mb-1">
                                                <?php echo (int)$course['StudentCount']; ?> <?php echo (int)$course['StudentCount'] === 1 ? 'Student' : 'Students'; ?>
                                            </span>
                                            <?php if (!empty($course['StudentCount']) && (int)$course['StudentCount'] > 0): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.78rem;" data-bs-toggle="modal" data-bs-target="#manageStudentsModal_<?php echo $course['CourseID']; ?>">
                                                    <i class="bi bi-people me-1"></i>View Students
                                                </button>
                                            <?php else: ?>
                                                <small class="text-muted">No students yet</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-success"><?php echo isset($course['QuizCount']) ? $course['QuizCount'] : 0; ?></span>
                                    </td>

                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td class="pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=create-quiz&course_id=<?php echo $course['CourseID']; ?>" class="btn btn-outline-success">
                                                <i class="bi bi-plus"></i> Assessment
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($courses)): ?>
    <?php foreach ($courses as $course): ?>
        <?php if (!empty($course['StudentCount']) && (int)$course['StudentCount'] > 0): ?>
            <div class="modal fade" id="manageStudentsModal_<?php echo $course['CourseID']; ?>" tabindex="-1" aria-labelledby="manageStudentsModalLabel_<?php echo $course['CourseID']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-light">
                            <div>
                                <h5 class="modal-title fw-bold" id="manageStudentsModalLabel_<?php echo $course['CourseID']; ?>">
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
                                        <input type="text" class="form-control border-start-0 ps-0 shadow-none" placeholder="Search student name..." onkeyup="filterStudentsModal(this, 'manageStudentList_<?php echo $course['CourseID']; ?>')">
                                    </div>
                                </div>
                                <ul class="list-group list-group-flush" id="manageStudentList_<?php echo $course['CourseID']; ?>" style="max-height: 350px; overflow-y: auto;">
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

