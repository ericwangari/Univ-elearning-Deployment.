<?php
// app/views/instructor/create_quiz.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="mb-4">
        <a href="?page=dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white p-4">
                    <h4 class="card-title mb-0">Create New Assessment</h4>
                </div>

                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="quiz_name" class="form-label fw-bold">Assessment Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quiz_name" name="quiz_name" required>
                            <small class="text-muted">Enter the name of the assessment</small>
                        </div>

                        <div class="mb-3">
                            <label for="course_id" class="form-label fw-bold">Select Course <span class="text-danger">*</span></label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">-- Select a Course --</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['CourseID']; ?>" <?php echo (($_GET['course_id'] ?? '') == $course['CourseID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['CourseName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Choose which course this quiz belongs to</small>
                        </div>

                        <div class="mb-3">
                            <label for="quiz_type" class="form-label fw-bold">Assessment Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="quiz_type" name="quiz_type" required>
                                <option value="Quiz" <?php if (isset($_GET['type']) && $_GET['type'] == 'quiz') echo 'selected'; ?>>Quiz</option>
                                <option value="Midterm" <?php if (isset($_GET['type']) && $_GET['type'] == 'midterm') echo 'selected'; ?>>Midterm</option>
                                <option value="Final" <?php if (isset($_GET['type']) && $_GET['type'] == 'final') echo 'selected'; ?>>Final</option>
                                <option value="Assignment" <?php if (isset($_GET['type']) && $_GET['type'] == 'assignment') echo 'selected'; ?>>Assignment</option>
                            </select>
                            <small class="text-muted">Select the type of assessment</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Describe the assessment..."></textarea>
                            <small class="text-muted">Optional: Add instructions or details about the assessment</small>
                        </div>

                        <div class="mb-4">
                            <label for="total_marks" class="form-label fw-bold">Total Marks</label>
                            <input type="number" class="form-control" id="total_marks" name="total_marks" value="100" min="1" max="100">
                            <small class="text-muted">Maximum score for this assessment (capped at 100)</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-plus-circle me-2"></i> Create Assessment
                            </button>
                            <a href="?page=dashboard" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
