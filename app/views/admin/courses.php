<?php
// app/views/admin/courses.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Course Management</h2>
            <p class="text-muted">Create courses and assign them to instructors</p>
        </div>
        <a href="?page=admin-create-course" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> New Course
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Course Name</th>
                            <th>Instructor</th>
                            <th>Students Enrolled</th>

                            <th>Quizzes</th>
                            <th class="pe-4">Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <p class="text-muted mb-0">No courses found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($course['CourseName']); ?></td>
                                    <td><?php echo htmlspecialchars($course['InstructorNames'] ?: 'Unassigned'); ?></td>
                                    <td>
                                        <span class="badge bg-soft-primary"><?php echo $course['EnrollmentCount']; ?></span>
                                    </td>

                                    <td>
                                        <span class="badge bg-soft-info"><?php echo $course['QuizCount']; ?></span>
                                    </td>
                                    <td class="pe-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <span><?php echo date('M d, Y', strtotime($course['CreatedAt'])); ?></span>
                                            <a href="?page=admin-edit-course&id=<?php echo $course['CourseID']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="?page=admin-delete-course" class="d-inline">
                                                <input type="hidden" name="course_id" value="<?php echo $course['CourseID']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this course?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
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
