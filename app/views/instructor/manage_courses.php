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
                                        <span class="badge bg-soft-primary"><?php echo $course['StudentCount']; ?></span>
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

<?php include __DIR__ . '/../partials/footer.php'; ?>

