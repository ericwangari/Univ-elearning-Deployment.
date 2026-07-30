<?php
// app/views/instructor/manage_quiz.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="mb-4">
        <a href="?page=course-quizzes&course_id=<?php echo $quiz['CourseID']; ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Assessments
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white p-4 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Manage Quiz: <?php echo htmlspecialchars($quiz['QuizName']); ?></h4>
                    <form method="POST" action="?page=delete-quiz" class="mb-0" onsubmit="return confirm('Are you sure you want to delete this entire assessment? This cannot be undone.');">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz['QuizID']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash me-1"></i> Delete Quiz
                        </button>
                    </form>
                </div>

                <div class="card-body p-4">
                    <div class="mb-4 p-3 bg-light rounded">
                        <div class="row">
                            <div class="col-md-3">
                                <small class="text-muted d-block mb-1">Total Questions</small>
                                <h5 class="mb-0"><?php echo count($questions); ?></h5>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block mb-1">Assessment Type</small>
                                <h5 class="mb-0"><span class="badge bg-secondary"><?php echo htmlspecialchars($quiz['QuizType']); ?></span></h5>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block mb-1">Total Marks</small>
                                <h5 class="mb-0"><?php echo (int)$quiz['TotalMarks']; ?></h5>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block mb-1">Description</small>
                                <p class="mb-0 text-truncate"><small><?php echo htmlspecialchars($quiz['Description']); ?></small></p>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="fw-bold mb-3">Questions</h5>

                    <?php if (empty($questions)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            No questions added yet. Add your first question below.
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <?php foreach ($questions as $qindex => $question): ?>
                                <div class="card mb-3 border-0 bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2 gap-3">
                                            <div>
                                                <h6 class="fw-bold mb-1">Q<?php echo $qindex + 1; ?>: <?php echo htmlspecialchars($question['QuestionText']); ?></h6>
                                                <small class="text-muted">Type: <?php echo htmlspecialchars($question['QuestionType']); ?> | Marks: <?php echo (int)$question['Marks']; ?></small>
                                            </div>
                                            <form method="POST" action="?page=delete-question">
                                                <input type="hidden" name="question_id" value="<?php echo $question['QuestionID']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this question?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <?php if (!empty($question['options'])): ?>
                                            <div class="mt-2">
                                                <small class="text-muted d-block mb-1">Options:</small>
                                                <ul class="list-unstyled mb-0">
                                                    <?php foreach ($question['options'] as $option): ?>
                                                        <li class="small mb-1">
                                                            <?php echo $option['IsCorrect'] ? '<strong class="text-success">Correct: ' : 'Option: '; ?>
                                                            <?php echo htmlspecialchars($option['OptionText']); ?>
                                                            <?php echo $option['IsCorrect'] ? '</strong>' : ''; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($question['QuestionType'] === 'Short Answer'): ?>
                                            <div class="mt-3 border-top pt-3">
                                                <small class="text-muted d-block mb-2">Student answers:</small>
                                                <?php if (empty($question['short_answers'])): ?>
                                                    <span class="badge bg-light text-dark border">No submissions yet</span>
                                                <?php else: ?>
                                                    <?php foreach ($question['short_answers'] as $answer): ?>
                                                        <div class="p-3 bg-white rounded border mb-2">
                                                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                                                                <div>
                                                                    <div class="fw-bold small"><?php echo htmlspecialchars($answer['Username']); ?></div>
                                                                    <div class="text-muted small"><?php echo nl2br(htmlspecialchars($answer['AnswerText'] ?? '')); ?></div>
                                                                </div>
                                                                <?php if ($answer['IsCorrect'] === null): ?>
                                                                    <form method="POST" action="?page=grade-short-answer" class="d-flex gap-2">
                                                                        <input type="hidden" name="answer_id" value="<?php echo $answer['AnswerID']; ?>">
                                                                        <button type="submit" name="is_correct" value="1" class="btn btn-sm btn-outline-success">Correct</button>
                                                                        <button type="submit" name="is_correct" value="0" class="btn btn-sm btn-outline-danger">Incorrect</button>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <span class="badge <?php echo $answer['IsCorrect'] ? 'bg-success' : 'bg-secondary'; ?>">Done</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white p-4">
                    <h5 class="card-title mb-0">Add New Question</h5>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="?page=add-question">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz['QuizID']; ?>">

                        <div class="mb-3">
                            <label for="question_text" class="form-label fw-bold">Question <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="question_text" name="question_text" rows="3" required placeholder="Enter the question..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="question_type" class="form-label fw-bold">Question Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="question_type" name="question_type" onchange="toggleOptions()">
                                <option value="Multiple Choice">Multiple Choice</option>
                                <option value="True/False">True/False</option>
                                <option value="Short Answer">Short Answer</option>
                            </select>
                        </div>

                        <div id="options_section" class="mb-3">
                            <label class="form-label fw-bold">Options</label>
                            <div id="options_container">
                                <?php for ($i = 0; $i < 4; $i++): ?>
                                    <div class="input-group mb-2">
                                        <div class="form-check me-2 mt-2">
                                            <input class="form-check-input" type="radio" name="correct_option" value="<?php echo $i; ?>" <?php echo $i === 0 ? 'checked' : ''; ?>>
                                        </div>
                                        <input type="text" class="form-control" name="options[]" placeholder="Option <?php echo $i + 1; ?>">
                                        <button type="button" class="btn btn-outline-secondary" onclick="removeOption(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOption()">+ Add Option</button>
                        </div>

                        <div id="true_false_section" class="mb-3 d-none">
                            <label for="true_false_answer" class="form-label fw-bold">Correct Answer</label>
                            <select class="form-select" id="true_false_answer" name="true_false_answer">
                                <option value="1">True</option>
                                <option value="0">False</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="marks" class="form-label fw-bold">Marks <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="marks" name="marks" value="1" min="1" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-plus-circle me-2"></i> Add Question
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleOptions() {
    const type = document.getElementById('question_type').value;
    const section = document.getElementById('options_section');
    const trueFalseSection = document.getElementById('true_false_section');
    section.style.display = (type === 'Multiple Choice') ? 'block' : 'none';
    trueFalseSection.classList.toggle('d-none', type !== 'True/False');
}

function addOption() {
    const container = document.getElementById('options_container');
    const count = container.children.length;
    const html = `
        <div class="input-group mb-2">
            <div class="form-check me-2 mt-2">
                <input class="form-check-input" type="radio" name="correct_option" value="${count}">
            </div>
            <input type="text" class="form-control" name="options[]" placeholder="Option ${count + 1}">
            <button type="button" class="btn btn-outline-secondary" onclick="removeOption(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

function removeOption(button) {
    button.parentElement.remove();
}

document.addEventListener('DOMContentLoaded', toggleOptions);
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
