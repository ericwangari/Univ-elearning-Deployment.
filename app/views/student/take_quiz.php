<?php
// app/views/student/take_quiz.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 quiz-container">
            <?php $quiz_completed = isset($last_result) && !$allow_retry; ?>
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <a href="?page=course-details&id=<?php echo isset($quiz['CourseID']) ? $quiz['CourseID'] : ''; ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Course
                </a>

                <?php if (!$quiz_completed): ?>
                    <!-- Sticky Timer -->
                    <div class="badge bg-danger rounded-pill px-3 py-2 animate__animated animate__pulse animate__infinite">
                        <i class="bi bi-clock me-1"></i> <span id="quiz-timer">30:00</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <!-- Quiz Header -->
                <div class="card-header bg-gradient p-5 text-white position-relative" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-bottom: 0;">
                    <!-- Decorative circles -->
                    <div class="position-absolute" style="top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                    <div class="position-absolute" style="bottom: -30px; right: 40px; width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                    
                    <div class="d-flex justify-content-between align-items-center position-relative" style="z-index: 1;">
                        <div>
                            <span class="badge bg-white text-primary mb-3 px-3 py-2 rounded-pill shadow-sm">Assessment</span>
                            <h2 class="fw-bold mb-2"><?php echo htmlspecialchars(($quiz && isset($quiz['QuizName'])) ? $quiz['QuizName'] : 'Quiz'); ?></h2>
                            <p class="mb-0 text-white-50"><i class="bi bi-info-circle me-1"></i> <?php echo htmlspecialchars(($quiz && isset($quiz['Description'])) ? $quiz['Description'] : 'Please answer all questions carefully.'); ?></p>
                        </div>
                        <div class="text-end bg-white bg-opacity-25 rounded-3 p-3 backdrop-blur shadow-sm">
                            <h4 class="text-white mb-1 fw-bold"><?php echo min((int)($quiz['TotalMarks'] ?? 100), 100); ?> <small class="fw-normal fs-6">Pts</small></h4>
                            <small class="text-white-50"><?php echo isset($questions) ? count($questions) : '0'; ?> Questions</small>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="progress rounded-0 bg-light" style="height: 6px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 10%" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="card-body p-5 bg-white">
                    <?php if ($quiz_completed): ?>
                        <div class="text-center py-5">
                            <h4 class="fw-bold mb-3">Quiz Completed</h4>
                            <p class="text-muted mb-4">Your previous attempt has been recorded. To take the quiz again, click the button below.</p>
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="card border-0 shadow-sm mb-4">
                                        <div class="card-body">
                                            <h5 class="mb-2">Last Attempt</h5>
                                            <?php $last_display_total = max(1, min((float)($last_result['TotalMarks'] ?? 100), 100)); $last_percentage = ($last_display_total > 0) ? round((($last_result['Score'] ?? 0) / $last_display_total) * 100, 1) : 0; ?>
                                            <p class="mb-1"><strong>Score:</strong> <?php echo number_format($last_percentage, 1); ?> / 100</p>
                                            <p class="mb-1"><strong>Percentage:</strong> <?php echo $last_percentage; ?>%</p>
                                            <p class="mb-0"><strong>Status:</strong> <?php echo $last_percentage >= 50 ? 'Passed' : 'Needs Improvement'; ?></p>
                                        </div>
                                    </div>
                                    <?php if ($last_percentage < 50): ?>
                                        <a href="?page=take-quiz&id=<?php echo $quiz['QuizID']; ?>&retry=1" class="btn btn-success btn-lg px-5">
                                            <i class="bi bi-arrow-repeat me-2"></i> Retake Quiz
                                        </a>
                                    <?php else: ?>
                                        <div class="alert alert-light border mt-2">You already scored 50% or higher, so this assessment cannot be retaken.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif (empty($questions)): ?>
                        <div class="text-center py-5">
                            <img src="https://illustrations.popsy.co/white/surreal-hourglass.svg" alt="Empty" height="150" class="mb-4">
                            <h4 class="text-muted fw-bold">No Questions Found</h4>
                            <p class="text-muted">This quiz is currently empty. Please contact your instructor.</p>
                            <a href="?page=course-details&id=<?php echo isset($quiz['CourseID']) ? $quiz['CourseID'] : ''; ?>" class="btn btn-primary mt-3 px-4">Return</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="needs-validation" novalidate id="quizForm">
                            
                            <?php foreach ($questions as $qindex => $question): ?>
                                <div class="question-group mb-5 animate__animated animate__fadeInUp" style="animation-delay: <?php echo $qindex * 0.1; ?>s;">
                                    <div class="d-flex mb-4">
                                        <div class="me-3">
                                            <span class="badge bg-soft-primary rounded-circle p-3 d-flex align-items-center justify-content-center fw-bold fs-5 shadow-sm" style="width: 45px; height: 45px;">
                                                <?php echo $qindex + 1; ?>
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-2 text-dark" style="line-height: 1.5;"><?php echo htmlspecialchars($question['QuestionText']); ?></h5>
                                            <span class="badge bg-light text-muted border px-2 py-1"><i class="bi bi-star-fill text-warning me-1"></i> <?php echo isset($question['Marks']) ? $question['Marks'] : '10'; ?> Points</span>
                                        </div>
                                    </div>

                                    <div class="ps-5 ms-2">
                                        <?php if (isset($question['QuestionType']) && $question['QuestionType'] === 'Multiple Choice'): ?>
                                            <?php if(isset($question['options'])): ?>
                                                <?php foreach ($question['options'] as $oindex => $option): ?>
                                                    <label class="quiz-option w-100 <?php echo (!empty($question['user_answer']) && is_array($question['user_answer']) && $question['user_answer']['SelectedOptionID'] == $option['OptionID']) ? 'selected' : ''; ?>" for="option_<?php echo $option['OptionID']; ?>">
                                                        <input type="radio" 
                                                               name="answer_<?php echo $question['QuestionID']; ?>" 
                                                               id="option_<?php echo $option['OptionID']; ?>"
                                                               value="<?php echo $option['OptionID']; ?>"
                                                               required
                                                               <?php echo (!empty($question['user_answer']) && is_array($question['user_answer']) && $question['user_answer']['SelectedOptionID'] == $option['OptionID']) ? 'checked' : ''; ?>>
                                                        <span class="fs-6 text-dark fw-medium"><?php echo htmlspecialchars($option['OptionText']); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        <?php elseif (isset($question['QuestionType']) && $question['QuestionType'] === 'True/False'): ?>
                                            <label class="quiz-option w-100" for="true_<?php echo $qindex; ?>">
                                                <input type="radio" name="answer_<?php echo $question['QuestionID']; ?>" id="true_<?php echo $qindex; ?>" value="1" required <?php echo (!empty($question['user_answer']) && isset($question['user_answer']['AnswerText']) && $question['user_answer']['AnswerText'] === '1') ? 'checked' : ''; ?> >
                                                <span class="fs-6 text-dark fw-medium">True</span>
                                            </label>
                                            <label class="quiz-option w-100" for="false_<?php echo $qindex; ?>">
                                                <input type="radio" name="answer_<?php echo $question['QuestionID']; ?>" id="false_<?php echo $qindex; ?>" value="0" required <?php echo (!empty($question['user_answer']) && isset($question['user_answer']['AnswerText']) && $question['user_answer']['AnswerText'] === '0') ? 'checked' : ''; ?> >
                                                <span class="fs-6 text-dark fw-medium">False</span>
                                            </label>
                                        <?php elseif (isset($question['QuestionType']) && $question['QuestionType'] === 'Short Answer'): ?>
                                            <div class="mb-3">
                                                <textarea class="form-control" name="answer_<?php echo $question['QuestionID']; ?>" rows="4" maxlength="500" placeholder="Type your short answer (max 500 characters)"><?php echo (!empty($question['user_answer']) && isset($question['user_answer']['AnswerText'])) ? htmlspecialchars($question['user_answer']['AnswerText']) : ''; ?></textarea>
                                            </div>
                                        <?php else: ?>
                                            <!-- Fallback Mock Options if Backend is missing -->
                                            <label class="quiz-option w-100" for="mock_true_<?php echo $qindex; ?>">
                                                <input type="radio" name="answer_<?php echo $question['QuestionID']; ?>" id="mock_true_<?php echo $qindex; ?>" value="true" required>
                                                <span class="fs-6 text-dark fw-medium">True</span>
                                            </label>
                                            <label class="quiz-option w-100" for="mock_false_<?php echo $qindex; ?>">
                                                <input type="radio" name="answer_<?php echo $question['QuestionID']; ?>" id="mock_false_<?php echo $qindex; ?>" value="false" required>
                                                <span class="fs-6 text-dark fw-medium">False</span>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if($qindex < count($questions) - 1): ?>
                                    <hr class="my-5 opacity-25">
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <div class="mt-5 pt-3 bg-light p-4 rounded-4 text-center shadow-sm">
                                <h5 class="fw-bold text-dark mb-3">Ready to submit?</h5>
                                <p class="text-muted small mb-4">Make sure you have answered all questions. You cannot change your answers after submission.</p>
                                <button type="button" class="btn btn-success btn-lg px-5 shadow fw-bold" onclick="submitQuizConfirm(event)">
                                    <i class="bi bi-send-check me-2"></i> Submit Assessment
                                </button>
                                <!-- Hidden real submit -->
                                <button type="submit" id="realSubmitBtn" name="submit_quiz" value="1" class="d-none"></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function submitQuizConfirm(e) {
    e.preventDefault();
    const form = document.getElementById('quizForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        showNotification('Incomplete', 'Please answer all questions before submitting.', 'warning');
        return;
    }
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Submit Quiz?',
            text: "Are you sure you want to lock in your answers?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, submit it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('realSubmitBtn').click();
            }
        });
    } else {
        if (confirm('Are you sure you want to submit?')) {
            document.getElementById('realSubmitBtn').click();
        }
    }
}

// Simple timer simulation
let timeLeft = <?php echo isset($time_left) ? (int)$time_left : 1800; ?>;
const timerEl = document.getElementById('quiz-timer');
let quizInProgress = true;

function beforeUnloadHandler(e) {
    if (!quizInProgress) return;
    e.preventDefault();
    e.returnValue = 'Leaving will end the quiz and may forfeit your answers.';
    return 'Leaving will end the quiz and may forfeit your answers.';
}

window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.reload();
    }
});

if (window.history && window.history.pushState) {
    history.pushState(null, null, location.href);
    window.addEventListener('popstate', function() {
        history.pushState(null, null, location.href);
        if (typeof showNotification !== 'undefined') {
            showNotification('Navigation blocked', 'Back navigation is disabled during the quiz.', 'warning');
        } else {
            alert('Back navigation is disabled during the quiz.');
        }
    });
}

window.addEventListener('beforeunload', beforeUnloadHandler);

const updateTimer = () => {
    if (!timerEl) return;
    const m = Math.floor(timeLeft / 60).toString().padStart(2, '0');
    const s = (timeLeft % 60).toString().padStart(2, '0');
    timerEl.textContent = `${m}:${s}`;
    if (timeLeft < 300) {
        timerEl.parentElement.classList.remove('bg-danger');
        timerEl.parentElement.classList.add('bg-warning');
    }
};

if(timerEl) {
    updateTimer();
    const timerInterval = setInterval(() => {
        if(timeLeft <= 0) {
            clearInterval(timerInterval);
            quizInProgress = false;
            if (typeof showNotification !== 'undefined') {
                showNotification('Time expired', 'Your quiz timer has ended. Please restart the attempt.', 'error');
            } else {
                alert('Your quiz timer has ended. Please restart the attempt.');
            }
            return;
        }
        timeLeft--;
        updateTimer();
    }, 1000);
}

function submitQuizConfirm(e) {
    e.preventDefault();
    const form = document.getElementById('quizForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        showNotification('Incomplete', 'Please answer all questions before submitting.', 'warning');
        return;
    }
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Submit Quiz?',
            text: "Are you sure you want to lock in your answers?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, submit it!'
        }).then((result) => {
            if (result.isConfirmed) {
                quizInProgress = false;
                window.removeEventListener('beforeunload', beforeUnloadHandler);
                document.getElementById('realSubmitBtn').click();
            }
        });
    } else {
        if (confirm('Are you sure you want to submit?')) {
            quizInProgress = false;
            window.removeEventListener('beforeunload', beforeUnloadHandler);
            document.getElementById('realSubmitBtn').click();
        }
    }
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
