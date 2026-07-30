<?php
// app/views/student/quiz_detail.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';

$display_total = max(1, min((float)($result['TotalMarks'] ?? 100), 100));
$percentage = ($display_total > 0) ? round((($result['Score'] ?? 0) / $display_total) * 100, 1) : 0;
$pass_threshold = 50;
$is_pass = $percentage >= $pass_threshold;
?>

<div class="container-fluid p-4">
    <div class="mb-4">
        <a href="?page=my-results" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Results
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header <?php echo $is_pass ? 'bg-success' : 'bg-danger'; ?> text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-1"><?php echo htmlspecialchars($result['QuizName']); ?></h4>
                            <small><?php echo htmlspecialchars($result['CourseName']); ?></small>
                        </div>
                        <div class="text-end">
                            <h2 class="mb-0"><?php echo $percentage; ?>%</h2>
                            <small><?php echo number_format($percentage, 1); ?>% of 100 points</small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="alert <?php echo $is_pass ? 'alert-success' : 'alert-danger'; ?>">
                        <i class="bi bi-<?php echo $is_pass ? 'check-circle' : 'x-circle'; ?> me-2"></i>
                        <strong><?php echo $is_pass ? 'Passed!' : 'Did not pass'; ?></strong>
                        <?php echo $is_pass ? 'Great job! You scored 50% or higher.' : 'You scored below 50%, so you can retake this assessment.'; ?>
                    </div>

                    <hr>
                    <div class="alert alert-info">
                        <i class="bi bi-shield-lock-fill me-2"></i>
                        Question details are hidden after submission to protect quiz integrity.
                    </div>
                    <?php if (!$is_pass): ?>
                        <a href="?page=take-quiz&id=<?php echo $result['QuizID']; ?>&retry=1" class="btn btn-success w-100 mb-3">
                            <i class="bi bi-arrow-repeat me-1"></i> Retake Quiz
                        </a>
                    <?php else: ?>
                        <div class="alert alert-light border">You have already scored 50% or higher, so retaking this assessment is not available.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Quiz Summary</h5>

                    <div class="mb-3">
                        <label class="text-muted small d-block mb-1">Total Score</label>
                        <h4 class="mb-0"><?php echo number_format($percentage, 1); ?> / 100</h4>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small d-block mb-1">Percentage</label>
                        <h4 class="mb-0"><?php echo $percentage; ?>%</h4>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small d-block mb-1">Status</label>
                        <span class="badge <?php echo $is_pass ? 'bg-success' : 'bg-danger'; ?> p-2">
                            <?php echo $is_pass ? 'Passed' : 'Failed'; ?>
                        </span>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small d-block mb-1">Submitted</label>
                        <small><?php echo date('M d, Y', strtotime($result['SubmittedAt'])); ?></small>
                    </div>

                    <hr>

                    <a href="?page=course-details&id=<?php echo $result['CourseID']; ?>" class="btn btn-primary w-100 btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Course
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
