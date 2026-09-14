<?php
// app/views/instructor/edit_course.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="mb-4">
        <a href="?page=manage-courses" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white p-4">
                    <h4 class="card-title mb-0">Edit Course</h4>
                </div>

                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="course_name" class="form-label fw-bold">Course Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo htmlspecialchars($course['CourseName']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($course['Description']); ?></textarea>
                        </div>



                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i> Update Course
                            </button>
                            <a href="?page=manage-courses" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
