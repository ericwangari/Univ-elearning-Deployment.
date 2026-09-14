<?php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="mb-4">
        <a href="?page=admin-courses" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Courses
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white p-4">
                    <h4 class="card-title mb-0">Edit Course</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="course_id" value="<?php echo $course['CourseID']; ?>">

                        <div class="mb-3">
                            <label for="course_name" class="form-label fw-bold">Course Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo htmlspecialchars($course['CourseName']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($course['Description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="instructor_id" class="form-label fw-bold">Assign Instructor</label>
                            <select class="form-select" id="instructor_id" name="instructor_id">
                                <option value="">Unassigned</option>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?php echo $instructor['UserID']; ?>" <?php echo ($course['InstructorID'] == $instructor['UserID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($instructor['Username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i> Save Course
                            </button>
                            <a href="?page=admin-courses" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
