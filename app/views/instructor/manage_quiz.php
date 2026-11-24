<?php
// app/views/instructor/manage_quiz.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';

$currentMarks = $currentMarks ?? 0;
$availableMarks = $availableMarks ?? max(0, (int)$quiz['TotalMarks'] - $currentMarks);
$courseLessons = $courseLessons ?? [];
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
                    <div>
                        <h4 class="card-title mb-1">Manage <?php echo htmlspecialchars($quiz['QuizType']); ?>: <?php echo htmlspecialchars($quiz['QuizName']); ?></h4>
                        <small class="opacity-75"><?php echo htmlspecialchars($course['CourseName'] ?? 'Course Assessment'); ?></small>
                    </div>
                    <form method="POST" action="?page=delete-quiz" class="mb-0" onsubmit="return confirm('Are you sure you want to delete this entire assessment? This cannot be undone.');">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz['QuizID']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash me-1"></i> Delete Quiz
                        </button>
                    </form>
                </div>

                <div class="card-body p-4">
                    <div class="mb-4 p-3 bg-light rounded">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block mb-1">Total Questions</small>
                                <h5 class="mb-0 fw-bold"><?php echo count($questions); ?></h5>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block mb-1">Assessment Type</small>
                                <h5 class="mb-0"><span class="badge bg-secondary"><?php echo htmlspecialchars($quiz['QuizType']); ?></span></h5>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block mb-1">Marks Assigned</small>
                                <h5 class="mb-0 fw-bold">
                                    <span class="<?php echo $availableMarks <= 0 ? 'text-success' : 'text-primary'; ?>"><?php echo $currentMarks; ?></span> / <?php echo (int)$quiz['TotalMarks']; ?>
                                </h5>
                                <small class="text-muted"><?php echo $availableMarks > 0 ? "({$availableMarks} remaining)" : "(Full)"; ?></small>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block mb-1">Description</small>
                                <p class="mb-0 text-truncate"><small><?php echo htmlspecialchars($quiz['Description'] ?: 'No description provided'); ?></small></p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Action Bar -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold mb-0">Questions (<?php echo count($questions); ?>)</h5>
                            <small class="text-muted">Review, edit, or delete existing questions below</small>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn text-white shadow-sm px-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); font-weight: 500;" data-bs-toggle="modal" data-bs-target="#aiGeneratorModal">
                                <i class="bi bi-stars me-1"></i> Generate with AI Teacher
                            </button>
                            <button type="button" class="btn btn-outline-primary shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#importQuestionsModal">
                                <i class="bi bi-file-earmark-arrow-up me-1"></i> Import Questions
                            </button>
                        </div>
                    </div>

                    <?php if (empty($questions)): ?>
                        <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <i class="bi bi-info-circle me-2"></i>
                                No questions added yet. Use the <strong>AI Teacher</strong> to generate questions from course materials, <strong>import</strong> a file/list, or add them manually on the right.
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#aiGeneratorModal">
                                    <i class="bi bi-stars me-1"></i> Try AI Teacher
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importQuestionsModal">
                                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <?php foreach ($questions as $qindex => $question): ?>
                                <div class="card mb-3 border-0 bg-light shadow-xs">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2 gap-3">
                                            <div>
                                                <h6 class="fw-bold mb-1">Q<?php echo $qindex + 1; ?>: <?php echo htmlspecialchars($question['QuestionText']); ?></h6>
                                                <div class="d-flex flex-wrap gap-2 align-items-center mt-1">
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($question['QuestionType']); ?></span>
                                                    <span class="badge bg-light text-dark border"><i class="bi bi-award me-1"></i><?php echo (int)$question['Marks']; ?> <?php echo (int)$question['Marks'] === 1 ? 'Mark' : 'Marks'; ?></span>
                                                </div>
                                            </div>
                                            <form method="POST" action="?page=delete-question">
                                                <input type="hidden" name="question_id" value="<?php echo $question['QuestionID']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this question?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <?php if (!empty($question['options'])): ?>
                                            <div class="mt-2 pt-2 border-top">
                                                <small class="text-muted d-block mb-1">Answer Options:</small>
                                                <ul class="list-unstyled mb-0">
                                                    <?php foreach ($question['options'] as $option): ?>
                                                        <li class="small mb-1 d-flex align-items-center gap-2">
                                                            <?php if ($option['IsCorrect']): ?>
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                                    <i class="bi bi-check-circle-fill me-1"></i> Correct Answer
                                                                </span>
                                                                <strong class="text-success"><?php echo htmlspecialchars($option['OptionText']); ?></strong>
                                                            <?php else: ?>
                                                                <span class="badge bg-light text-muted border px-2 py-1">Option</span>
                                                                <span class="text-secondary"><?php echo htmlspecialchars($option['OptionText']); ?></span>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($question['QuestionType'] === 'Short Answer'): ?>
                                            <div class="mt-3 border-top pt-3">
                                                <small class="text-muted d-block mb-2">Student Submissions:</small>
                                                <?php if (empty($question['short_answers'])): ?>
                                                    <span class="badge bg-light text-dark border">No student submissions yet</span>
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
            <!-- Fast Batch Assistant Banner -->
            <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #f8faff 0%, #eef2ff 100%); border-left: 4px solid #6366f1 !important;">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-1 text-primary"><i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Fast Question Creator</h6>
                    <p class="small text-muted mb-2">Save time by importing your existing questions or having AI Teacher craft them from your course syllabus.</p>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-sm text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);" data-bs-toggle="modal" data-bs-target="#aiGeneratorModal">
                            <i class="bi bi-stars me-1"></i> Generate with AI Teacher
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importQuestionsModal">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import Questions (CSV / Text)
                        </button>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white p-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fs-6 fw-bold"><i class="bi bi-pencil-square me-2"></i>Add Question Manually</h5>
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
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0">Options</label>
                                <small class="text-muted">Select radio for correct answer</small>
                            </div>
                            <div id="options_container">
                                <?php for ($i = 0; $i < 4; $i++): ?>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text bg-white">
                                            <input class="form-check-input mt-0" type="radio" name="correct_option" value="<?php echo $i; ?>" <?php echo $i === 0 ? 'checked' : ''; ?> title="Mark as correct">
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
                            <input type="number" class="form-control" id="marks" name="marks" value="<?php echo min(1, max(1, $availableMarks)); ?>" min="1" max="<?php echo max(1, $availableMarks); ?>" required>
                            <small class="text-muted">Available capacity: <?php echo $availableMarks; ?> / <?php echo (int)$quiz['TotalMarks']; ?></small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success" <?php echo $availableMarks <= 0 ? 'disabled' : ''; ?>>
                                <i class="bi bi-plus-circle me-2"></i> Add Question
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 1. IMPORT QUESTIONS MODAL                   -->
<!-- ========================================== -->
<div class="modal fade" id="importQuestionsModal" tabindex="-1" aria-labelledby="importQuestionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?page=import-questions" enctype="multipart/form-data">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz['QuizID']; ?>">

                <div class="modal-header bg-light">
                    <div>
                        <h5 class="modal-title fw-bold" id="importQuestionsModalLabel">
                            <i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>Import Questions
                        </h5>
                        <small class="text-muted">Batch import questions from text or files into <strong><?php echo htmlspecialchars($quiz['QuizName']); ?></strong></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Nav Tabs: Text vs File -->
                    <ul class="nav nav-pills mb-3" id="importTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="paste-tab" data-bs-toggle="pill" data-bs-target="#tab-paste" type="button" role="tab">
                                <i class="bi bi-clipboard-check me-1"></i> Paste Questions (Aiken / Plain Text / CSV)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="file-tab" data-bs-toggle="pill" data-bs-target="#tab-file" type="button" role="tab">
                                <i class="bi bi-upload me-1"></i> Upload File (.csv, .json, .txt)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="importTabContent">
                        <!-- Tab 1: Paste Text -->
                        <div class="tab-pane fade show active" id="tab-paste" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="import_text" class="form-label fw-bold mb-0">Question Content</label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" onclick="loadSampleAiken()">Sample Aiken</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="loadSampleCsv()">Sample CSV</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="loadSampleJson()">Sample JSON</button>
                                </div>
                            </div>
                            <textarea class="form-control font-monospace" id="import_text" name="import_text" rows="11" placeholder="Paste your questions here...

What is Python?
A. A programming language
B. A database system
C. An operating system
D. A web browser
ANSWER: A
MARKS: 2

True or False: Python is an interpreted language.
ANSWER: True
MARKS: 1

Explain the purpose of functions in programming.
TYPE: Short Answer
MARKS: 3"></textarea>
                            <div class="mt-2 text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                Supports <strong>Aiken format</strong> (Multiple Choice with <code>ANSWER: X</code>), <strong>True/False</strong> (with <code>ANSWER: True</code>), <strong>Short Answer</strong>, or standard <strong>CSV / JSON</strong>. Separate multiple questions with a blank line.
                            </div>
                        </div>

                        <!-- Tab 2: File Upload -->
                        <div class="tab-pane fade" id="tab-file" role="tabpanel">
                            <div class="mb-3">
                                <label for="import_file" class="form-label fw-bold">Select File (.csv, .json, .txt)</label>
                                <input class="form-control" type="file" id="import_file" name="import_file" accept=".csv,.json,.txt">
                            </div>
                            <div class="p-3 bg-light rounded border">
                                <h6 class="fw-bold mb-2">Download Sample Templates:</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="downloadSampleFile('aiken')">
                                        <i class="bi bi-download me-1"></i> Aiken Text (.txt)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="downloadSampleFile('csv')">
                                        <i class="bi bi-download me-1"></i> CSV Template (.csv)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadSampleFile('json')">
                                        <i class="bi bi-download me-1"></i> JSON Template (.json)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row align-items-center g-3">
                        <div class="col-md-6">
                            <label for="default_marks" class="form-label small fw-bold mb-1">Default Marks per Question (if unspecified)</label>
                            <input type="number" class="form-control form-control-sm" id="default_marks" name="default_marks" value="1" min="1" max="50">
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted d-block">Assessment Total: <strong><?php echo (int)$quiz['TotalMarks']; ?></strong></small>
                            <span class="badge bg-light text-dark border">
                                Available to Allocate: <strong><?php echo $availableMarks; ?> marks</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> Import Questions Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. AI TEACHER QUESTION GENERATOR MODAL      -->
<!-- ========================================== -->
<div class="modal fade" id="aiGeneratorModal" tabindex="-1" aria-labelledby="aiGeneratorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold" id="aiGeneratorModalLabel">
                        <i class="bi bi-stars text-primary me-2"></i>Generate Questions with AI Teacher
                    </h5>
                    <small class="text-muted">Grounded in <strong><?php echo htmlspecialchars($course['CourseName'] ?? 'Course Materials'); ?></strong></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- STEP 1: CONFIGURATION FORM -->
                <div id="ai-generator-config">
                    <div class="p-3 mb-4 rounded border" style="background: linear-gradient(135deg, #f8faff 0%, #eef2ff 100%);">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); font-size: 1.5rem;">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">AI Teacher Assessment Generator</h6>
                                <p class="small text-muted mb-0">The AI Teacher reads your course lessons and materials to generate rigorous, balanced questions tailored to your students.</p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="ai_topic_select" class="form-label fw-bold">Target Topic / Lesson Material</label>
                            <select class="form-select" id="ai_topic_select">
                                <option value="">-- All Course Materials & Syllabus --</option>
                                <?php foreach ($courseLessons as $cl): ?>
                                    <option value="<?php echo htmlspecialchars($cl['title']); ?>"><?php echo htmlspecialchars($cl['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Focus on a specific module or generate across the entire curriculum.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="ai_count_select" class="form-label fw-bold">Number of Questions</label>
                            <select class="form-select" id="ai_count_select">
                                <option value="3">3 Questions (Quick check)</option>
                                <option value="5" selected>5 Questions (Standard)</option>
                                <option value="10">10 Questions (Comprehensive)</option>
                            </select>
                            <small class="text-muted">You will be able to preview, edit, or uncheck any question before adding.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="ai_type_select" class="form-label fw-bold">Question Format</label>
                            <select class="form-select" id="ai_type_select">
                                <option value="all" selected>Mixed (Multiple Choice, True/False, Short Answer)</option>
                                <option value="multiple_choice">Multiple Choice Only (4 Options)</option>
                                <option value="true_false">True/False Only</option>
                                <option value="short_answer">Short Answer / Conceptual Only</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="ai_difficulty_select" class="form-label fw-bold">Focus & Rigor</label>
                            <select class="form-select" id="ai_difficulty_select">
                                <option value="standard" selected>Standard (Balanced recall & comprehension)</option>
                                <option value="conceptual">Conceptual Understanding & Core Rules</option>
                                <option value="applied">Practical Application & Problem Solving</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded border mb-4">
                        <div>
                            <span class="small text-muted d-block">Assessment Point Pool</span>
                            <strong><?php echo $currentMarks; ?> / <?php echo (int)$quiz['TotalMarks']; ?> points assigned</strong>
                        </div>
                        <div>
                            <span class="badge <?php echo $availableMarks > 0 ? 'bg-success' : 'bg-warning text-dark'; ?> px-3 py-2 fs-6">
                                <?php echo $availableMarks; ?> marks available
                            </span>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="button" id="btn-generate-ai" class="btn text-white py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                            <i class="bi bi-stars me-2"></i>Generate Questions with AI Teacher
                        </button>
                    </div>
                </div>

                <!-- LOADING SPINNER STATE -->
                <div id="ai-generator-loading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="fw-bold mb-1">AI Teacher is analyzing course materials...</h5>
                    <p class="text-muted small mb-0">Crafting curriculum-aligned assessment questions with verified answers and distractors.</p>
                </div>

                <!-- STEP 2: PREVIEW & SELECTION -->
                <div id="ai-generator-preview" class="d-none">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                        <div>
                            <h6 class="fw-bold mb-0">Generated Questions Review</h6>
                            <small class="text-muted">Review, edit question text or marks, and uncheck any you wish to exclude.</small>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleSelectAllAi(true)">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleSelectAllAi(false)">Deselect All</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="aiBackToConfig()">
                                <i class="bi bi-arrow-repeat me-1"></i> Adjust / Regenerate
                            </button>
                        </div>
                    </div>

                    <div id="ai-questions-list" class="mb-4" style="max-height: 480px; overflow-y: auto;">
                        <!-- Injected via JavaScript -->
                    </div>

                    <div class="p-3 bg-light rounded border d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <span class="fw-bold me-2">Selected:</span>
                            <span class="badge bg-primary fs-6 me-2" id="ai-selected-count">0</span> questions
                            <span class="text-muted ms-2">(Total <strong id="ai-selected-marks">0</strong> marks)</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary" onclick="aiBackToConfig()">Cancel</button>
                            <button type="button" id="btn-save-ai-questions" class="btn btn-success fw-semibold">
                                <i class="bi bi-check2-circle me-1"></i> Add Selected Questions to Assessment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const QUIZ_ID = <?php echo json_encode($quiz['QuizID']); ?>;
const AVAILABLE_MARKS = <?php echo (int)$availableMarks; ?>;

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
            <div class="input-group-text bg-white">
                <input class="form-check-input mt-0" type="radio" name="correct_option" value="${count}">
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

// Samples for import
function loadSampleAiken() {
    document.getElementById('import_text').value = `What is the primary role of an index in a database?
A. It provides automatic encrypted backup of tables
B. It accelerates data retrieval queries at the cost of slight write overhead
C. It deletes duplicate rows upon insertion
D. It prevents any foreign key constraints from being checked
ANSWER: B
MARKS: 2

True or False: SQL is a declarative language where the user specifies what data to retrieve rather than how to retrieve it.
ANSWER: True
MARKS: 1

Explain how normalization reduces data redundancy in a relational database.
TYPE: Short Answer
MARKS: 3`;
}

function loadSampleCsv() {
    document.getElementById('import_text').value = `Question,Option A,Option B,Option C,Option D,Correct Answer,Marks
"In Python, which function counts elements in a list?","count()","len()","size()","length()","B",2
"True or False: In Python, strings are mutable.","True","False","","","False",1
"Explain the difference between a shallow copy and a deep copy.","","","","","",3`;
}

function loadSampleJson() {
    const sample = [
        {
            "question": "Which data structure operates on a Last-In, First-Out (LIFO) principle?",
            "type": "Multiple Choice",
            "marks": 2,
            "options": [
                {"text": "Stack", "correct": true},
                {"text": "Queue", "correct": false},
                {"text": "Hash Table", "correct": false},
                {"text": "Binary Search Tree", "correct": false}
            ]
        },
        {
            "question": "True or False: An algorithm with O(1) time complexity runs in constant time regardless of input size.",
            "type": "True/False",
            "marks": 1,
            "correct": true
        },
        {
            "question": "Describe one practical scenario where a Queue data structure is preferred over a Stack.",
            "type": "Short Answer",
            "marks": 3
        }
    ];
    document.getElementById('import_text').value = JSON.stringify(sample, null, 2);
}

function downloadSampleFile(format) {
    let content = '';
    let filename = '';
    let mime = 'text/plain';

    if (format === 'aiken') {
        content = `What is the primary role of an index in a database?
A. It provides automatic backup copies
B. It accelerates data retrieval queries
C. It deletes duplicate rows
D. It disables table constraints
ANSWER: B
MARKS: 2

True or False: SQL is a declarative programming language.
ANSWER: True
MARKS: 1

Explain the concept of encapsulation in object-oriented programming.
TYPE: Short Answer
MARKS: 3`;
        filename = 'sample_questions_aiken.txt';
    } else if (format === 'csv') {
        content = `Question,Option A,Option B,Option C,Option D,Correct Answer,Marks
"In Python, which function returns the number of items in a list?","count()","len()","size()","length()","B",2
"True or False: Strings in Python are immutable.","True","False","","","True",1
"Explain the purpose of the virtual environment in Python.","","","","","",3`;
        filename = 'sample_questions.csv';
        mime = 'text/csv';
    } else if (format === 'json') {
        const sample = [
            {
                "question": "What does CSS stand for?",
                "type": "Multiple Choice",
                "marks": 2,
                "options": [
                    {"text": "Cascading Style Sheets", "correct": true},
                    {"text": "Computer System Software", "correct": false},
                    {"text": "Creative Style Syntax", "correct": false},
                    {"text": "Coded Sheet System", "correct": false}
                ]
            },
            {
                "question": "True or False: HTML5 includes semantic elements like <header>, <footer>, and <article>.",
                "type": "True/False",
                "marks": 1,
                "correct": true
            }
        ];
        content = JSON.stringify(sample, null, 2);
        filename = 'sample_questions.json';
        mime = 'application/json';
    }

    const blob = new Blob([content], { type: mime });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// AI Generator logic
let currentAiQuestions = [];

document.getElementById('btn-generate-ai')?.addEventListener('click', function() {
    const topic = document.getElementById('ai_topic_select').value;
    const count = document.getElementById('ai_count_select').value;
    const type = document.getElementById('ai_type_select').value;
    const difficulty = document.getElementById('ai_difficulty_select').value;

    document.getElementById('ai-generator-config').classList.add('d-none');
    document.getElementById('ai-generator-loading').classList.remove('d-none');
    document.getElementById('ai-generator-preview').classList.add('d-none');

    const url = `?page=generate-quiz-questions&quiz_id=${encodeURIComponent(QUIZ_ID)}&topic=${encodeURIComponent(topic)}&count=${encodeURIComponent(count)}&type=${encodeURIComponent(type)}&difficulty=${encodeURIComponent(difficulty)}`;

    fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            document.getElementById('ai-generator-loading').classList.add('d-none');
            if (data.error) {
                alert('Generation note: ' + data.error);
                document.getElementById('ai-generator-config').classList.remove('d-none');
                return;
            }

            if (!data.questions || data.questions.length === 0) {
                alert('No questions could be generated. Please try a different topic or setting.');
                document.getElementById('ai-generator-config').classList.remove('d-none');
                return;
            }

            currentAiQuestions = data.questions;
            renderAiQuestionsPreview(currentAiQuestions);
            document.getElementById('ai-generator-preview').classList.remove('d-none');
        })
        .catch(err => {
            document.getElementById('ai-generator-loading').classList.add('d-none');
            document.getElementById('ai-generator-config').classList.remove('d-none');
            alert('Failed to connect to AI Teacher. Please check your connection and try again.');
        });
});

function aiBackToConfig() {
    document.getElementById('ai-generator-preview').classList.add('d-none');
    document.getElementById('ai-generator-loading').classList.add('d-none');
    document.getElementById('ai-generator-config').classList.remove('d-none');
}

function renderAiQuestionsPreview(questions) {
    const container = document.getElementById('ai-questions-list');
    container.innerHTML = '';

    questions.forEach((q, idx) => {
        let optionsHtml = '';
        if (q.question_type === 'Multiple Choice' && q.options && q.options.length > 0) {
            optionsHtml = '<div class="mt-2 pt-2 border-top"><small class="text-muted d-block mb-1">Options:</small><ul class="list-unstyled mb-0">';
            q.options.forEach(opt => {
                optionsHtml += `
                    <li class="small mb-1 d-flex align-items-center gap-2">
                        ${opt.is_correct ? '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>Correct</span>' : '<span class="badge bg-light text-muted border">Option</span>'}
                        <span class="${opt.is_correct ? 'fw-bold text-success' : 'text-secondary'}">${escapeHtml(opt.text)}</span>
                    </li>
                `;
            });
            optionsHtml += '</ul></div>';
        } else if (q.question_type === 'True/False') {
            const isTrue = q.options && q.options.length > 0 ? q.options.find(o => o.text === 'True')?.is_correct : true;
            optionsHtml = `
                <div class="mt-2 pt-2 border-top small">
                    <span class="text-muted me-2">Correct Answer:</span>
                    <span class="badge ${isTrue ? 'bg-success' : 'bg-primary'}">${isTrue ? 'True' : 'False'}</span>
                </div>
            `;
        } else {
            optionsHtml = '<div class="mt-2 pt-2 border-top small text-muted"><i class="bi bi-pencil me-1"></i>Short Answer (students answer in their own words; graded by instructor)</div>';
        }

        const card = document.createElement('div');
        card.className = 'card mb-3 border shadow-xs bg-white';
        card.innerHTML = `
            <div class="card-body p-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="form-check mt-1">
                        <input class="form-check-input ai-q-checkbox" type="checkbox" data-index="${idx}" id="ai_q_${idx}" checked onchange="updateAiSummary()">
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                            <span class="badge bg-secondary">${escapeHtml(q.question_type)}</span>
                            <div class="d-flex align-items-center gap-1">
                                <label for="ai_marks_${idx}" class="small text-muted mb-0">Marks:</label>
                                <input type="number" id="ai_marks_${idx}" class="form-control form-control-sm text-center ai-q-marks" data-index="${idx}" value="${q.marks || 1}" min="1" max="50" style="width: 65px;" onchange="updateAiSummary()">
                            </div>
                        </div>
                        <textarea class="form-control form-control-sm mb-1 ai-q-text" data-index="${idx}" rows="2">${escapeHtml(q.question_text)}</textarea>
                        ${optionsHtml}
                    </div>
                </div>
            </div>
        `;
        container.appendChild(card);
    });

    updateAiSummary();
}

function updateAiSummary() {
    const checkboxes = document.querySelectorAll('.ai-q-checkbox');
    let selectedCount = 0;
    let selectedMarks = 0;

    checkboxes.forEach(cb => {
        if (cb.checked) {
            selectedCount++;
            const idx = cb.getAttribute('data-index');
            const marksInput = document.getElementById(`ai_marks_${idx}`);
            selectedMarks += parseInt(marksInput ? marksInput.value : 1, 10) || 1;
        }
    });

    const countElem = document.getElementById('ai-selected-count');
    const marksElem = document.getElementById('ai-selected-marks');
    if (countElem) countElem.textContent = selectedCount;
    if (marksElem) marksElem.textContent = selectedMarks;

    const saveBtn = document.getElementById('btn-save-ai-questions');
    if (saveBtn) {
        saveBtn.disabled = (selectedCount === 0);
    }
}

function toggleSelectAllAi(checked) {
    const checkboxes = document.querySelectorAll('.ai-q-checkbox');
    checkboxes.forEach(cb => cb.checked = checked);
    updateAiSummary();
}

document.getElementById('btn-save-ai-questions')?.addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.ai-q-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one question to add.');
        return;
    }

    const payload = [];
    checkboxes.forEach(cb => {
        const idx = parseInt(cb.getAttribute('data-index'), 10);
        const original = currentAiQuestions[idx];
        const textElem = document.querySelector(`.ai-q-text[data-index="${idx}"]`);
        const marksElem = document.querySelector(`.ai-q-marks[data-index="${idx}"]`);

        payload.push({
            question_text: textElem ? textElem.value.trim() : original.question_text,
            question_type: original.question_type,
            marks: marksElem ? parseInt(marksElem.value, 10) : original.marks,
            options: original.options || [],
            correct_is_true: original.correct_is_true ?? true
        });
    });

    const btn = document.getElementById('btn-save-ai-questions');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Adding Questions...';

    fetch('?page=import-questions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            quiz_id: QUIZ_ID,
            questions: payload
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error adding questions: ' + (data.error || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Add Selected Questions to Assessment';
        }
    })
    .catch(err => {
        alert('Failed to save questions. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Add Selected Questions to Assessment';
    });
});

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', toggleOptions);
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
