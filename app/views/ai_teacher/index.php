<?php
$page_title = 'AI Teacher';
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4">
        <div>
            <span class="badge bg-soft-primary text-primary mb-2">AI Learning Assistant</span>
            <h1 class="fw-bold mb-1">AI Teacher</h1>
            <p class="text-muted mb-0">Generate explanations, study plans, mini-lessons, and practice questions for your courses.</p>
        </div>
        <div class="ai-teacher-orb mt-3 mt-lg-0" aria-hidden="true">
            <i class="bi bi-stars"></i>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card p-4 h-100">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="aiCourse" class="form-label fw-semibold">Course context</label>
                        <select class="form-select" id="aiCourse" name="course_id">
                            <option value="">General learning support</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['CourseID'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string)$selectedCourseId === (string)$course['CourseID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['CourseName'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="aiMode" class="form-label fw-semibold">What should the AI teacher create?</label>
                        <select class="form-select" id="aiMode" name="mode">
                            <option value="explain" <?php echo $mode === 'explain' ? 'selected' : ''; ?>>Explain a concept</option>
                            <option value="plan" <?php echo $mode === 'plan' ? 'selected' : ''; ?>>Study plan</option>
                            <option value="content" <?php echo $mode === 'content' ? 'selected' : ''; ?>>Mini-lesson content</option>
                            <option value="practice" <?php echo $mode === 'practice' ? 'selected' : ''; ?>>Practice questions</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="aiQuestion" class="form-label fw-semibold">Question or topic</label>
                        <textarea class="form-control" id="aiQuestion" name="question" rows="7" required placeholder="Example: Explain database joins with a simple example"><?php echo htmlspecialchars($question ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <div class="invalid-feedback">Enter a question or topic for the AI teacher.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-3">
                        <i class="bi bi-magic me-2" aria-hidden="true"></i>Generate Learning Support
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Teacher Response</h4>
                    <span class="badge bg-light text-muted border"><?php echo AI_TEACHER_API_KEY !== '' ? 'AI enabled' : 'Built-in mode'; ?></span>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-warning"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <?php if (!empty($ai_response)): ?>
                    <div class="ai-teacher-response">
                        <?php echo nl2br(htmlspecialchars($ai_response, ENT_QUOTES, 'UTF-8')); ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-robot display-4 text-primary"></i>
                        <h5 class="fw-bold mt-3">Ask for help with any topic</h5>
                        <p class="mb-0">The AI teacher can explain ideas, generate mini-lessons, create practice questions, and build study plans.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
