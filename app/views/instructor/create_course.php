<?php
// app/views/instructor/create_course.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="mb-4">
    <h2 class="fw-bold">Create New Course</h2>
    <p class="text-muted">Fill in the details below to launch your new course.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4">
            <form method="POST">
                <div class="mb-4">
                    <label class="form-label fw-bold">Course Title</label>
                    <input type="text" name="course_name" class="form-control form-control-lg" required placeholder="e.g. Advanced PHP and MySQL Mastery">
                    <div class="form-text">Choose a descriptive name that invites students to learn.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="6" required placeholder="Describe what the course covers, who it's for, and what students will achieve..."></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Create Course</button>
                    <a href="?page=manage-courses" class="btn btn-light border px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card bg-soft-primary border-0 p-4">
            <h5 class="fw-bold"><i class="bi bi-lightbulb me-2"></i> Quick Tips</h5>
            <ul class="small mt-3">
                <li class="mb-2">Keep your title under 60 characters.</li>
                <li class="mb-2">Use a compelling description to increase enrollments.</li>
                <li class="mb-2">You can add lessons and quizzes after creating the initial course.</li>
            </ul>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>

