<?php
require_once __DIR__ . '/../models/Course.php';

class InstructorController {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Instructor Dashboard
    public function dashboard() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $instructor_id = $_SESSION['user_id'];

        // Get courses assigned by admins to this instructor
        $stmt = $this->pdo->prepare("SELECT c.*, 
                         COUNT(DISTINCT e.UserID) as StudentCount,
                         GROUP_CONCAT(DISTINCT u.Username ORDER BY u.Username SEPARATOR '||') as StudentNames,
                         COUNT(DISTINCT q.QuizID) as QuizCount 
                         FROM courses c 
                         JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                         LEFT JOIN enrollments e ON c.CourseID = e.CourseID 
                         LEFT JOIN users u ON e.UserID = u.UserID
                         LEFT JOIN quizzes q ON c.CourseID = q.CourseID 
                         WHERE ic.InstructorID = ? 
                         GROUP BY c.CourseID");
        $stmt->execute([$instructor_id]);
        $courses = $stmt->fetchAll();

        $stats = [
            'total_courses' => count($courses),
            'total_students' => $this->getTotalStudents($instructor_id),
            'total_quizzes' => $this->getTotalQuizzes($instructor_id),
            'midterms' => $this->countQuizzesByType($instructor_id, 'Midterm'),
            'finals' => $this->countQuizzesByType($instructor_id, 'Final'),
            'assignments' => $this->countQuizzesByType($instructor_id, 'Assignment'),
            'standard_quizzes' => $this->countQuizzesByType($instructor_id, 'Quiz'),
        ];

        require __DIR__ . '/../views/instructor/dashboard.php';
    }

    // Create new course
    public function createCourse() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $departments = getAllDepartments($this->pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course_name = trim($_POST['course_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $department = normalizeDepartmentInput($_POST['department'] ?? '', $course_name, $description);

            if (!empty($course_name)) {
                $course_id = $this->insertCourse($course_name, $description, $department);

                // Assign course to instructor
                $stmt = $this->pdo->prepare("INSERT IGNORE INTO instructor_courses (InstructorID, CourseID) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $course_id]);

                header("Location: index.php?page=manage-courses");
                exit;
            }
        }

        require __DIR__ . '/../views/instructor/create_course.php';
    }

    // Manage courses
    public function manageCourses() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $instructor_id = $_SESSION['user_id'];
        $course_id = $_GET['course_id'] ?? null;
        $search = trim($_GET['search'] ?? '');
        $selected_course_id = $course_id;

        $stmt = $this->pdo->prepare("SELECT c.CourseID, c.CourseName
                                     FROM courses c
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                     WHERE ic.InstructorID = ?
                                     ORDER BY c.CourseName");
        $stmt->execute([$instructor_id]);
        $courseOptions = $stmt->fetchAll();

        $sql = "SELECT c.*,
                                     COUNT(DISTINCT e.EnrollmentID) as StudentCount,
                                     GROUP_CONCAT(DISTINCT u.Username ORDER BY u.Username SEPARATOR '||') as StudentNames,
                                     COUNT(DISTINCT q.QuizID) as QuizCount
                                     FROM courses c 
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                     LEFT JOIN enrollments e ON c.CourseID = e.CourseID 
                                     LEFT JOIN users u ON e.UserID = u.UserID
                                     LEFT JOIN quizzes q ON c.CourseID = q.CourseID 
                                     WHERE ic.InstructorID = ?";
        $params = [$instructor_id];

        if ($course_id) {
            $sql .= " AND c.CourseID = ?";
            $params[] = $course_id;
        }

        if ($search !== '') {
            $sql .= " AND (c.CourseName LIKE ? OR u.Username LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql .= " GROUP BY c.CourseID ORDER BY c.CourseName";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $courses = $stmt->fetchAll();
        $filtered_student_total = array_sum(array_map(function ($course) {
            return (int)($course['StudentCount'] ?? 0);
        }, $courses));

        require __DIR__ . '/../views/instructor/manage_courses.php';
    }

    // Edit course
    public function editCourse() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $course_id = $_GET['id'] ?? null;
        $instructor_id = $_SESSION['user_id'];
        $departments = getAllDepartments($this->pdo);

        // Verify course belongs to instructor
        $stmt = $this->pdo->prepare("SELECT c.* FROM courses c 
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID 
                                     WHERE c.CourseID = ? AND ic.InstructorID = ?");
        $stmt->execute([$course_id, $instructor_id]);
        $course = $stmt->fetch();

        if (!$course) {
            header("Location: index.php?page=manage-courses");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course_name = trim($_POST['course_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $department = normalizeDepartmentInput($_POST['department'] ?? '', $course_name, $description);

            $stmt = $this->pdo->prepare("UPDATE courses SET CourseName = ?, Description = ?, Department = ? WHERE CourseID = ?");
            $stmt->execute([$course_name, $description, $department, $course_id]);

            header("Location: index.php?page=manage-courses");
            exit;
        }

        require __DIR__ . '/../views/instructor/edit_course.php';
    }

    // Delete course
    public function deleteCourse() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $course_id = $_POST['course_id'] ?? null;
        $instructor_id = $_SESSION['user_id'];

        // Verify course belongs to instructor
        $stmt = $this->pdo->prepare("SELECT * FROM instructor_courses WHERE CourseID = ? AND InstructorID = ?");
        $stmt->execute([$course_id, $instructor_id]);

        if ($stmt->fetch()) {
            $stmt = $this->pdo->prepare("DELETE FROM courses WHERE CourseID = ?");
            $stmt->execute([$course_id]);
        }

        header("Location: index.php?page=manage-courses");
        exit;
    }

    // Create quiz
    public function createQuiz() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $instructor_id = $_SESSION['user_id'];

        // Get only courses belonging to this instructor
        $stmt = $this->pdo->prepare("SELECT c.* FROM courses c 
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                     WHERE ic.InstructorID = ?
                                     ORDER BY c.CourseName");
        $stmt->execute([$instructor_id]);
        $courses = $stmt->fetchAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $quiz_name = $_POST['quiz_name'] ?? '';
            $course_id = $_POST['course_id'] ?? null;
            $quiz_type = $_POST['quiz_type'] ?? 'Quiz';
            $description = $_POST['description'] ?? '';
            $total_marks = $_POST['total_marks'] ?? 100;

            // Enforce allowed quiz types and clamp total marks to a safe maximum
            if (!in_array($quiz_type, ['Quiz', 'Midterm', 'Final', 'Assignment'], true)) {
                $quiz_type = 'Quiz';
            }

            $MAX_TOTAL_MARKS = 100; // all assessments are graded on a 100-point scale
            $total_marks = (int)$total_marks;
            if ($total_marks < 1) $total_marks = 1;
            if ($total_marks > $MAX_TOTAL_MARKS) $total_marks = $MAX_TOTAL_MARKS;

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM instructor_courses WHERE InstructorID = ? AND CourseID = ?");
            $stmt->execute([$instructor_id, $course_id]);
            if (!$stmt->fetchColumn()) {
                $_SESSION['error'] = 'Invalid course selection or you are not assigned to that course.';
                header("Location: index.php?page=create-quiz");
                exit;
            }

            if (DB_DRIVER === 'pgsql') {
                $stmt = $this->pdo->prepare("INSERT INTO quizzes (QuizName, CourseID, QuizType, Description, TotalMarks) VALUES (?, ?, ?, ?, ?) RETURNING QuizID");
                $stmt->execute([$quiz_name, $course_id, $quiz_type, $description, $total_marks]);
                $quiz_id = $stmt->fetchColumn();
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO quizzes (QuizName, CourseID, QuizType, Description, TotalMarks) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$quiz_name, $course_id, $quiz_type, $description, $total_marks]);
                $quiz_id = $this->pdo->lastInsertId();
            }
            $_SESSION['success'] = 'Assessment created successfully.';
            if ((int)$_POST['total_marks'] > 100) {
                $_SESSION['success'] = 'Assessment created successfully. Total marks were capped at the maximum allowed value of 100.';
            }

            header("Location: index.php?page=manage-quiz&id=$quiz_id");
            exit;
        }

        require __DIR__ . '/../views/instructor/create_quiz.php';
    }

    // Manage quiz questions
    public function manageQuiz() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $quiz_id = $_GET['id'] ?? null;
        $instructor_id = $_SESSION['user_id'];

        // Verify quiz belongs to instructor's course
        $stmt = $this->pdo->prepare("SELECT q.* FROM quizzes q 
                                     JOIN courses c ON q.CourseID = c.CourseID 
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID 
                                     WHERE q.QuizID = ? AND ic.InstructorID = ?");
        $stmt->execute([$quiz_id, $instructor_id]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            header("Location: index.php?page=dashboard");
            exit;
        }

        $page_title = 'Manage ' . htmlspecialchars($quiz['QuizType']) . ': ' . htmlspecialchars($quiz['QuizName']);

        // Get course details & lessons for AI and materials context
        $stmt = $this->pdo->prepare("SELECT * FROM courses WHERE CourseID = ?");
        $stmt->execute([$quiz['CourseID']]);
        $course = $stmt->fetch();

        $aiTeacher = new AiTeacherController($this->pdo);
        $courseLessons = $aiTeacher->getCourseLessons($quiz['CourseID'], $course['CourseName'] ?? '');

        // Calculate marks remaining
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(Marks), 0) FROM questions WHERE QuizID = ?");
        $stmt->execute([$quiz_id]);
        $currentMarks = (int)$stmt->fetchColumn();
        $availableMarks = max(0, (int)$quiz['TotalMarks'] - $currentMarks);

        // Get questions
        $stmt = $this->pdo->prepare("SELECT * FROM questions WHERE QuizID = ? ORDER BY QuestionID");
        $stmt->execute([$quiz_id]);
        $questions = $stmt->fetchAll();

        // Get options for each question
        foreach ($questions as $key => $question) {
            $stmt = $this->pdo->prepare("SELECT * FROM question_options WHERE QuestionID = ?");
            $stmt->execute([$question['QuestionID']]);
            $questions[$key]['options'] = $stmt->fetchAll();

            if ($question['QuestionType'] === 'Short Answer') {
                $stmt = $this->pdo->prepare("SELECT ua.*, u.Username FROM user_answers ua
                                             JOIN users u ON ua.UserID = u.UserID
                                             WHERE ua.QuestionID = ?
                                             ORDER BY ua.SubmittedAt DESC");
                $stmt->execute([$question['QuestionID']]);
                $questions[$key]['short_answers'] = $stmt->fetchAll();
            } else {
                $questions[$key]['short_answers'] = [];
            }
        }

        require __DIR__ . '/../views/instructor/manage_quiz.php';
    }

    // List quizzes for a specific course
    public function quizzesByCourse() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $course_id = $_GET['course_id'] ?? null;
        $instructor_id = $_SESSION['user_id'];

        // Get course details only if assigned to this instructor
        $stmt = $this->pdo->prepare("SELECT c.* FROM courses c
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                     WHERE c.CourseID = ? AND ic.InstructorID = ?");
        $stmt->execute([$course_id, $instructor_id]);
        $course = $stmt->fetch();

        if (!$course) {
            header("Location: index.php?page=dashboard");
            exit;
        }

        // Get quizzes
        $stmt = $this->pdo->prepare("SELECT * FROM quizzes WHERE CourseID = ? ORDER BY QuizID");
        $stmt->execute([$course_id]);
        $quizzes = $stmt->fetchAll();

        require __DIR__ . '/../views/instructor/course_quizzes.php';
    }

    public function deleteQuiz() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $quiz_id = $_POST['quiz_id'] ?? null;
        $instructor_id = $_SESSION['user_id'];

        $stmt = $this->pdo->prepare("SELECT q.CourseID FROM quizzes q
                                     JOIN instructor_courses ic ON q.CourseID = ic.CourseID
                                     WHERE q.QuizID = ? AND ic.InstructorID = ?");
        $stmt->execute([$quiz_id, $instructor_id]);
        $quiz = $stmt->fetch();

        if ($quiz) {
            $stmt = $this->pdo->prepare("DELETE FROM quizzes WHERE QuizID = ?");
            $stmt->execute([$quiz_id]);
            $_SESSION['success'] = 'Assessment deleted successfully.';
            header("Location: index.php?page=course-quizzes&course_id=" . $quiz['CourseID']);
            exit;
        }

        header("Location: index.php?page=manage-courses");
        exit;
    }

    // Add question to quiz
    public function addQuestion() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $quiz_id = $_POST['quiz_id'] ?? null;
        $question_text = trim($_POST['question_text'] ?? '');
        $question_type = $_POST['question_type'] ?? 'Multiple Choice';
        $marks = $_POST['marks'] ?? 1;
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php?page=manage-courses';

        if (!empty($question_text)) {
            if (!in_array($question_type, ['Multiple Choice', 'True/False', 'Short Answer'], true)) {
                $question_type = 'Multiple Choice';
            }

            // Ensure we don't exceed the quiz total when adding questions
            $stmt = $this->pdo->prepare("SELECT TotalMarks FROM quizzes WHERE QuizID = ?");
            $stmt->execute([$quiz_id]);
            $quizInfo = $stmt->fetch();
            $quizTotal = $quizInfo ? (int)$quizInfo['TotalMarks'] : 0;

            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(Marks), 0) as sum_marks FROM questions WHERE QuizID = ?");
            $stmt->execute([$quiz_id]);
            $currentSum = (int)$stmt->fetchColumn();

            $available = $quizTotal - $currentSum;
            if ($available <= 0) {
                // No space left for more marks
                $_SESSION['error'] = 'This assessment already has the full total marks assigned. Remove or adjust existing questions before adding more.';
                header("Location: " . $redirect);
                exit;
            }

            $marks = (int)$marks;
            if ($marks < 1) $marks = 1;
            $clamped = false;
            if ($marks > $available) {
                $marks = $available; // clamp to remaining available marks
                $clamped = true;
            }

            $options = [];
            $correct_option = $_POST['correct_option'] ?? null;

            if ($question_type === 'Multiple Choice') {
                foreach (($_POST['options'] ?? []) as $index => $option_text) {
                    $option_text = trim((string)$option_text);
                    if ($option_text !== '') {
                        $options[(string)$index] = $option_text;
                    }
                }

                if (count($options) < 2) {
                    $_SESSION['error'] = 'Multiple-choice questions need at least two answer options.';
                    header("Location: " . $redirect);
                    exit;
                }

                if ($correct_option === null || !array_key_exists((string)$correct_option, $options)) {
                    $_SESSION['error'] = 'Choose one correct answer for the multiple-choice question.';
                    header("Location: " . $redirect);
                    exit;
                }
            }

            try {
                $this->pdo->beginTransaction();
                $question_id = $this->insertQuestion($quiz_id, $question_text, $question_type, $marks);

                // Add options if multiple choice
                if ($question_type === 'Multiple Choice') {
                    foreach ($options as $index => $option_text) {
                        $this->insertQuestionOption($question_id, $option_text, (string)$correct_option === (string)$index);
                    }
                } elseif ($question_type === 'True/False') {
                    $correct_answer = $_POST['true_false_answer'] ?? '1';
                    $this->insertQuestionOption($question_id, 'True', $correct_answer === '1');
                    $this->insertQuestionOption($question_id, 'False', $correct_answer === '0');
                }

                $this->pdo->commit();
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                error_log('Question add failed: ' . $e->getMessage());
                $_SESSION['error'] = 'Question could not be added. Please check the question details and try again.';
                header("Location: " . $redirect);
                exit;
            }

            if ($clamped) {
                $_SESSION['success'] = "Question added successfully, but marks were reduced to {$marks} so the assessment stays within the total {$quizTotal} points.";
            } else {
                $_SESSION['success'] = 'Question added successfully.';
            }
        }

        header("Location: " . $redirect);
        exit;
    }

    // Import questions in batch (Aiken format, CSV, JSON, or from AI Teacher Generator)
    public function importQuestions() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            header("Location: index.php?page=login");
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        $quiz_id = $_POST['quiz_id'] ?? $jsonData['quiz_id'] ?? null;
        $instructor_id = $_SESSION['user_id'];
        $redirect = "index.php?page=manage-quiz&id={$quiz_id}";

        // Verify quiz belongs to instructor's course
        $stmt = $this->pdo->prepare("SELECT q.* FROM quizzes q 
                                     JOIN courses c ON q.CourseID = c.CourseID 
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID 
                                     WHERE q.QuizID = ? AND ic.InstructorID = ?");
        $stmt->execute([$quiz_id, $instructor_id]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Assessment not found or access denied.']);
                exit;
            }
            $_SESSION['error'] = 'Assessment not found or access denied.';
            header("Location: index.php?page=manage-courses");
            exit;
        }

        // Calculate available marks
        $quizTotal = (int)$quiz['TotalMarks'];
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(Marks), 0) FROM questions WHERE QuizID = ?");
        $stmt->execute([$quiz_id]);
        $currentSum = (int)$stmt->fetchColumn();
        $availableMarks = max(0, $quizTotal - $currentSum);

        if ($availableMarks <= 0) {
            $msg = "This assessment already has all its total marks assigned ({$quizTotal} points). Remove or adjust existing questions before adding more.";
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => $msg]);
                exit;
            }
            $_SESSION['error'] = $msg;
            header("Location: " . $redirect);
            exit;
        }

        $defaultMarks = max(1, (int)($_POST['default_marks'] ?? $jsonData['default_marks'] ?? 1));
        $questionsToImport = [];

        // 1. Direct array passed via JSON (e.g. from AI generator)
        if (!empty($jsonData['questions']) && is_array($jsonData['questions'])) {
            $questionsToImport = $this->parseImportedQuestions(json_encode($jsonData['questions']), $defaultMarks);
        }
        // 2. Direct JSON string passed in form
        elseif (!empty($_POST['json_questions'])) {
            $questionsToImport = $this->parseImportedQuestions($_POST['json_questions'], $defaultMarks);
        }
        // 3. Uploaded file
        elseif (!empty($_FILES['import_file']['tmp_name']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
            $fileContent = file_get_contents($_FILES['import_file']['tmp_name']);
            $questionsToImport = $this->parseImportedQuestions($fileContent, $defaultMarks);
        }
        // 4. Text pasted in textarea
        elseif (!empty($_POST['import_text'])) {
            $questionsToImport = $this->parseImportedQuestions($_POST['import_text'], $defaultMarks);
        }

        if (empty($questionsToImport)) {
            $msg = 'No valid questions could be detected. Please check your text/file format and try again.';
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => $msg]);
                exit;
            }
            $_SESSION['error'] = $msg;
            header("Location: " . $redirect);
            exit;
        }

        // Insert questions in a transaction
        $insertedCount = 0;
        $totalMarksAllocated = 0;
        $clamped = false;

        try {
            $this->pdo->beginTransaction();

            foreach ($questionsToImport as $q) {
                if ($availableMarks <= 0) {
                    $clamped = true;
                    break;
                }

                $marks = max(1, (int)($q['marks'] ?? $defaultMarks));
                if ($marks > $availableMarks) {
                    $marks = $availableMarks;
                    $clamped = true;
                }
                $availableMarks -= $marks;
                $totalMarksAllocated += $marks;

                $qType = $q['type'] ?? 'Multiple Choice';
                $qText = trim($q['text'] ?? '');
                if ($qText === '') continue;

                $questionId = $this->insertQuestion($quiz_id, $qText, $qType, $marks);

                if ($qType === 'Multiple Choice' && !empty($q['options'])) {
                    foreach ($q['options'] as $opt) {
                        $this->insertQuestionOption($questionId, $opt['text'], !empty($opt['is_correct']));
                    }
                } elseif ($qType === 'True/False') {
                    $isTrueCorrect = !empty($q['correct_is_true']);
                    $this->insertQuestionOption($questionId, 'True', $isTrueCorrect);
                    $this->insertQuestionOption($questionId, 'False', !$isTrueCorrect);
                }

                $insertedCount++;
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Batch question import failed: ' . $e->getMessage());
            $errMsg = 'Failed to import questions: ' . $e->getMessage();
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => $errMsg]);
                exit;
            }
            $_SESSION['error'] = $errMsg;
            header("Location: " . $redirect);
            exit;
        }

        $successMsg = "Successfully imported {$insertedCount} question(s) ({$totalMarksAllocated} total marks added).";
        if ($clamped) {
            $successMsg .= " Total marks were capped to respect the {$quizTotal}-point maximum.";
        }

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'count'   => $insertedCount,
                'message' => $successMsg,
                'redirect' => $redirect,
            ]);
            exit;
        }

        $_SESSION['success'] = $successMsg;
        header("Location: " . $redirect);
        exit;
    }

    // Generate questions for a quiz using the AI Teacher
    public function generateQuizQuestions() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        header('Content-Type: application/json');

        $quiz_id = $_GET['quiz_id'] ?? $_POST['quiz_id'] ?? null;
        $instructor_id = $_SESSION['user_id'];

        $stmt = $this->pdo->prepare("SELECT q.*, c.CourseName, c.Description, c.Department FROM quizzes q 
                                     JOIN courses c ON q.CourseID = c.CourseID 
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID 
                                     WHERE q.QuizID = ? AND ic.InstructorID = ?");
        $stmt->execute([$quiz_id, $instructor_id]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            echo json_encode(['error' => 'Assessment not found or access denied.']);
            exit;
        }

        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(Marks), 0) FROM questions WHERE QuizID = ?");
        $stmt->execute([$quiz_id]);
        $currentSum = (int)$stmt->fetchColumn();
        $availableMarks = max(0, (int)$quiz['TotalMarks'] - $currentSum);

        $topic = trim((string)($_GET['topic'] ?? $_POST['topic'] ?? ''));
        $count = max(1, min(15, (int)($_GET['count'] ?? $_POST['count'] ?? 5)));
        $type = trim((string)($_GET['type'] ?? $_POST['type'] ?? 'all'));
        $difficulty = trim((string)($_GET['difficulty'] ?? $_POST['difficulty'] ?? 'standard'));

        $aiTeacher = new AiTeacherController($this->pdo);
        $questions = $aiTeacher->generateAssessmentQuestions($quiz, $topic, $count, $type, $difficulty);

        echo json_encode([
            'success'         => true,
            'questions'       => $questions,
            'available_marks' => $availableMarks,
            'total_marks'     => (int)$quiz['TotalMarks'],
            'current_marks'   => $currentSum,
        ]);
        exit;
    }

    private function parseImportedQuestions($rawInput, $defaultMarks = 1) {
        $raw = trim((string)$rawInput);
        if ($raw === '') return [];

        // 1. Try JSON
        $firstChar = substr($raw, 0, 1);
        if ($firstChar === '[' || $firstChar === '{') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $items = isset($decoded['questions']) && is_array($decoded['questions']) ? $decoded['questions'] : $decoded;
                $results = [];
                foreach ($items as $item) {
                    $text = trim((string)($item['question_text'] ?? $item['question'] ?? $item['text'] ?? ''));
                    if ($text === '') continue;

                    $rawType = trim((string)($item['question_type'] ?? $item['type'] ?? 'Multiple Choice'));
                    if (stripos($rawType, 'true') !== false || stripos($rawType, 'false') !== false) {
                        $type = 'True/False';
                    } elseif (stripos($rawType, 'short') !== false) {
                        $type = 'Short Answer';
                    } else {
                        $type = 'Multiple Choice';
                    }

                    $marks = max(1, (int)($item['marks'] ?? $defaultMarks));
                    $options = [];
                    $correctIsTrue = true;

                    if ($type === 'Multiple Choice' && !empty($item['options']) && is_array($item['options'])) {
                        $correctIndex = $item['correct_option'] ?? $item['correct'] ?? null;
                        foreach ($item['options'] as $idx => $opt) {
                            $optText = is_array($opt) ? trim((string)($opt['text'] ?? $opt['option'] ?? '')) : trim((string)$opt);
                            $isCorr = is_array($opt) ? (!empty($opt['is_correct']) || !empty($opt['correct'])) : ($correctIndex !== null && ((string)$correctIndex === (string)$idx || strcasecmp((string)$correctIndex, $optText) === 0));
                            if ($optText !== '') {
                                $options[] = ['text' => $optText, 'is_correct' => (bool)$isCorr];
                            }
                        }
                        // Default first if none marked
                        $hasCorr = false;
                        foreach ($options as $o) { if ($o['is_correct']) { $hasCorr = true; break; } }
                        if (!$hasCorr && !empty($options)) $options[0]['is_correct'] = true;
                    } elseif ($type === 'True/False') {
                        $corr = $item['correct_answer'] ?? $item['correct'] ?? $item['answer'] ?? true;
                        if (is_string($corr) && stripos($corr, 'f') === 0) {
                            $correctIsTrue = false;
                        } elseif (is_bool($corr)) {
                            $correctIsTrue = $corr;
                        }
                    }

                    $results[] = [
                        'text'            => $text,
                        'type'            => $type,
                        'marks'           => $marks,
                        'options'         => $options,
                        'correct_is_true' => $correctIsTrue,
                    ];
                }
                if (!empty($results)) return $results;
            }
        }

        // 2. Check if Aiken Format (blocks separated by blank lines with ANSWER:)
        if (preg_match('/ANSWER:\s*/i', $raw)) {
            $blocks = preg_split('/\r?\n\s*\r?\n/', $raw);
            $results = [];

            foreach ($blocks as $block) {
                $block = trim($block);
                if ($block === '') continue;

                $lines = preg_split('/\r?\n/', $block);
                $qTextLines = [];
                $optionsMap = [];
                $answerVal = null;
                $typeVal = null;
                $marksVal = $defaultMarks;

                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed === '') continue;

                    if (preg_match('/^ANSWER:\s*(.+)$/i', $trimmed, $m)) {
                        $answerVal = trim($m[1]);
                    } elseif (preg_match('/^MARKS:\s*([0-9]+)$/i', $trimmed, $m)) {
                        $marksVal = max(1, (int)$m[1]);
                    } elseif (preg_match('/^TYPE:\s*(.+)$/i', $trimmed, $m)) {
                        $typeVal = trim($m[1]);
                    } elseif (preg_match('/^([A-Z])[\.\)]\s*(.+)$/i', $trimmed, $m)) {
                        $optionsMap[strtoupper($m[1])] = trim($m[2]);
                    } else {
                        $qTextLines[] = $trimmed;
                    }
                }

                $questionText = implode(' ', $qTextLines);
                if ($questionText === '') continue;

                // Determine type
                if ($typeVal !== null) {
                    if (stripos($typeVal, 'short') !== false) {
                        $type = 'Short Answer';
                    } elseif (stripos($typeVal, 'true') !== false || stripos($typeVal, 'false') !== false) {
                        $type = 'True/False';
                    } else {
                        $type = 'Multiple Choice';
                    }
                } elseif (!empty($optionsMap)) {
                    $type = 'Multiple Choice';
                } elseif ($answerVal !== null && in_array(strtolower($answerVal), ['true', 'false', 't', 'f'], true)) {
                    $type = 'True/False';
                } else {
                    $type = 'Short Answer';
                }

                $options = [];
                $correctIsTrue = true;

                if ($type === 'Multiple Choice') {
                    $ansUpper = strtoupper((string)$answerVal);
                    foreach ($optionsMap as $letter => $optText) {
                        $isCorr = ($letter === $ansUpper || strcasecmp($optText, (string)$answerVal) === 0);
                        $options[] = ['text' => $optText, 'is_correct' => $isCorr];
                    }
                    $hasCorr = false;
                    foreach ($options as $o) { if ($o['is_correct']) { $hasCorr = true; break; } }
                    if (!$hasCorr && !empty($options)) $options[0]['is_correct'] = true;
                } elseif ($type === 'True/False') {
                    if ($answerVal !== null && (stripos($answerVal, 'f') === 0 || strcasecmp($answerVal, '0') === 0)) {
                        $correctIsTrue = false;
                    }
                }

                $results[] = [
                    'text'            => $questionText,
                    'type'            => $type,
                    'marks'           => $marksVal,
                    'options'         => $options,
                    'correct_is_true' => $correctIsTrue,
                ];
            }

            if (!empty($results)) return $results;
        }

        // 3. Try CSV (comma-separated or tab-separated)
        $lines = preg_split('/\r?\n/', $raw);
        $results = [];

        foreach ($lines as $i => $line) {
            $line = trim($line);
            if ($line === '') continue;

            $cols = str_getcsv($line);
            if (empty($cols) || count($cols) < 2) continue;

            // Check if header row
            if ($i === 0 && (stripos($cols[0], 'question') !== false || stripos($cols[0], 'prompt') !== false)) {
                continue;
            }

            $qText = trim($cols[0]);
            if ($qText === '') continue;

            // Default column mapping: Question, OptionA, OptionB, OptionC, OptionD, Answer, Marks
            if (count($cols) >= 5) {
                $type = 'Multiple Choice';
                $optA = trim($cols[1] ?? '');
                $optB = trim($cols[2] ?? '');
                $optC = trim($cols[3] ?? '');
                $optD = trim($cols[4] ?? '');
                $answer = trim($cols[5] ?? 'A');
                $marks = isset($cols[6]) && is_numeric(trim($cols[6])) ? max(1, (int)$cols[6]) : $defaultMarks;

                $rawOpts = [$optA, $optB, $optC, $optD];
                $options = [];
                $ansUpper = strtoupper($answer);

                foreach ($rawOpts as $idx => $t) {
                    if ($t === '') continue;
                    $letter = chr(65 + $idx); // A, B, C, D
                    $isCorr = ($letter === $ansUpper || (string)($idx + 1) === $answer || strcasecmp($t, $answer) === 0);
                    $options[] = ['text' => $t, 'is_correct' => $isCorr];
                }
                $hasCorr = false;
                foreach ($options as $o) { if ($o['is_correct']) { $hasCorr = true; break; } }
                if (!$hasCorr && !empty($options)) $options[0]['is_correct'] = true;

                $results[] = [
                    'text'            => $qText,
                    'type'            => $type,
                    'marks'           => $marks,
                    'options'         => $options,
                    'correct_is_true' => true,
                ];
            } elseif (count($cols) >= 2) {
                // True/False or Short Answer
                $secondCol = trim($cols[1]);
                $marks = isset($cols[2]) && is_numeric(trim($cols[2])) ? max(1, (int)$cols[2]) : $defaultMarks;

                if (in_array(strtolower($secondCol), ['true', 'false', 't', 'f'], true)) {
                    $results[] = [
                        'text'            => $qText,
                        'type'            => 'True/False',
                        'marks'           => $marks,
                        'options'         => [],
                        'correct_is_true' => (stripos($secondCol, 't') === 0),
                    ];
                } else {
                    $results[] = [
                        'text'            => $qText,
                        'type'            => 'Short Answer',
                        'marks'           => $marks,
                        'options'         => [],
                        'correct_is_true' => true,
                    ];
                }
            }
        }

        return $results;
    }

    private function isAjaxRequest() {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (!empty($_POST['is_ajax']))
            || (!empty($_GET['is_ajax']));
    }

    private function insertQuestion($quiz_id, $question_text, $question_type, $marks) {
        if (DB_DRIVER === 'pgsql') {
            $stmt = $this->pdo->prepare("INSERT INTO questions (QuizID, QuestionText, QuestionType, Marks) VALUES (?, ?, ?, ?) RETURNING QuestionID");
            $stmt->execute([$quiz_id, $question_text, $question_type, $marks]);
            return $stmt->fetchColumn();
        }

        $stmt = $this->pdo->prepare("INSERT INTO questions (QuizID, QuestionText, QuestionType, Marks) VALUES (?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $question_text, $question_type, $marks]);
        return $this->pdo->lastInsertId();
    }

    private function insertQuestionOption($question_id, $option_text, $is_correct) {
        $isCorrectValue = DB_DRIVER === 'pgsql' ? (bool)$is_correct : ($is_correct ? 1 : 0);
        $stmt = $this->pdo->prepare("INSERT INTO question_options (QuestionID, OptionText, IsCorrect) VALUES (?, ?, ?)");
        $stmt->execute([$question_id, $option_text, $isCorrectValue]);
    }

    public function deleteQuestion() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $question_id = $_POST['question_id'] ?? null;
        $instructor_id = $_SESSION['user_id'];

        $stmt = $this->pdo->prepare("SELECT q.QuizID FROM questions q
                                     JOIN quizzes z ON q.QuizID = z.QuizID
                                     JOIN instructor_courses ic ON z.CourseID = ic.CourseID
                                     WHERE q.QuestionID = ? AND ic.InstructorID = ?");
        $stmt->execute([$question_id, $instructor_id]);
        $question = $stmt->fetch();

        if ($question) {
            $stmt = $this->pdo->prepare("DELETE FROM questions WHERE QuestionID = ?");
            $stmt->execute([$question_id]);
            $_SESSION['success'] = 'Question deleted successfully.';
            header("Location: index.php?page=manage-quiz&id=" . $question['QuizID']);
            exit;
        }

        header("Location: index.php?page=dashboard");
        exit;
    }

    public function gradeShortAnswer() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $answer_id = $_POST['answer_id'] ?? null;
        $is_correct = isset($_POST['is_correct']) ? (int)$_POST['is_correct'] : 0;
        $instructor_id = $_SESSION['user_id'];

        $stmt = $this->pdo->prepare("SELECT ua.UserID, ua.QuestionID, q.QuizID, q.Marks, z.CourseID, z.TotalMarks
                                     FROM user_answers ua
                                     JOIN questions q ON ua.QuestionID = q.QuestionID
                                     JOIN quizzes z ON q.QuizID = z.QuizID
                                     JOIN instructor_courses ic ON z.CourseID = ic.CourseID
                                     WHERE ua.AnswerID = ? AND q.QuestionType = 'Short Answer' AND ic.InstructorID = ?");
        $stmt->execute([$answer_id, $instructor_id]);
        $answer = $stmt->fetch();

        if ($answer) {
            $stmt = $this->pdo->prepare("UPDATE user_answers SET IsCorrect = ? WHERE AnswerID = ?");
            $stmt->execute([DB_DRIVER === 'pgsql' ? (bool)$is_correct : $is_correct, $answer_id]);

            $correctCondition = DB_DRIVER === 'pgsql' ? 'ua.IsCorrect = TRUE' : 'ua.IsCorrect = 1';
            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(q.Marks), 0) FROM user_answers ua
                                         JOIN questions q ON ua.QuestionID = q.QuestionID
                                         WHERE ua.UserID = ? AND q.QuizID = ? AND {$correctCondition}");
            $stmt->execute([$answer['UserID'], $answer['QuizID']]);
            $correctMarks = (float)$stmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(Marks), 0) FROM questions WHERE QuizID = ?");
            $stmt->execute([$answer['QuizID']]);
            $questionTotal = (float)$stmt->fetchColumn();
            $score = ($questionTotal > 0) ? (int) round(($correctMarks / $questionTotal) * 100) : 0;
            $score = min($score, 100);

            $stmt = $this->pdo->prepare("UPDATE results SET Score = ? WHERE UserID = ? AND QuizID = ?");
            $stmt->execute([$score, $answer['UserID'], $answer['QuizID']]);

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_answers ua
                                         JOIN questions q ON ua.QuestionID = q.QuestionID
                                         WHERE ua.UserID = ? AND q.QuizID = ? AND q.QuestionType = 'Short Answer' AND ua.IsCorrect IS NULL");
            $stmt->execute([$answer['UserID'], $answer['QuizID']]);
            $status = ((int)$stmt->fetchColumn()) > 0 ? 'Submitted' : 'Graded';

            $stmt = $this->pdo->prepare("UPDATE quiz_attempts SET Score = ?, Status = ? WHERE UserID = ? AND QuizID = ?");
            $stmt->execute([$score, $status, $answer['UserID'], $answer['QuizID']]);

            $_SESSION['success'] = 'Short answer marked done.';
            header("Location: index.php?page=manage-quiz&id=" . $answer['QuizID']);
            exit;
        }

        header("Location: index.php?page=dashboard");
        exit;
    }

    public function courseResults() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $course_id = $_GET['id'] ?? $_GET['course_id'] ?? null;
        $search = trim($_GET['search'] ?? '');
        $assessment_type = trim($_GET['assessment_type'] ?? '');
        $instructor_id = $_SESSION['user_id'];

        $stmt = $this->pdo->prepare("SELECT c.CourseID, c.CourseName FROM courses c
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                     WHERE ic.InstructorID = ?
                                     ORDER BY c.CourseName");
        $stmt->execute([$instructor_id]);
        $courses = $stmt->fetchAll();

        $selected_course_id = $course_id;
        $course = null;

        if ($selected_course_id) {
            $stmt = $this->pdo->prepare("SELECT c.* FROM courses c
                                         JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                         WHERE c.CourseID = ? AND ic.InstructorID = ?");
            $stmt->execute([$selected_course_id, $instructor_id]);
            $course = $stmt->fetch();

            if (!$course) {
                header("Location: index.php?page=dashboard");
                exit;
            }
        }

        $stats = ['Quiz' => 0, 'Midterm' => 0, 'Final' => 0, 'Assignment' => 0];

        $stats_sql = "SELECT q.QuizType, COUNT(*) as count
                      FROM results r
                      JOIN quizzes q ON r.QuizID = q.QuizID
                      JOIN courses c ON q.CourseID = c.CourseID
                      JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                      WHERE ic.InstructorID = ?";
        $stats_params = [$instructor_id];
        if ($selected_course_id) {
            $stats_sql .= " AND q.CourseID = ?";
            $stats_params[] = $selected_course_id;
        }
        if ($assessment_type !== '') {
            $stats_sql .= " AND q.QuizType = ?";
            $stats_params[] = $assessment_type;
        }
        if ($search !== '') {
            $stats_sql .= " AND (r.UserID IN (SELECT UserID FROM users WHERE Username LIKE ?) OR c.CourseName LIKE ?)";
            $stats_params[] = '%' . $search . '%';
            $stats_params[] = '%' . $search . '%';
        }
        $stats_sql .= " GROUP BY q.QuizType";

        $stmt = $this->pdo->prepare($stats_sql);
        $stmt->execute($stats_params);
        foreach ($stmt->fetchAll() as $row) {
            if (isset($stats[$row['QuizType']])) {
                $stats[$row['QuizType']] = (int)$row['count'];
            }
        }

        $results_sql = "SELECT u.UserID, u.Username, c.CourseID, c.CourseName,
                        SUM(CASE WHEN q.QuizType = 'Quiz' THEN best.Score ELSE 0 END) AS QuizScore,
                        SUM(CASE WHEN q.QuizType = 'Quiz' THEN 100 ELSE 0 END) AS QuizTotal,
                        SUM(CASE WHEN q.QuizType = 'Midterm' THEN best.Score ELSE 0 END) AS MidtermScore,
                        SUM(CASE WHEN q.QuizType = 'Midterm' THEN 100 ELSE 0 END) AS MidtermTotal,
                        SUM(CASE WHEN q.QuizType = 'Final' THEN best.Score ELSE 0 END) AS FinalScore,
                        SUM(CASE WHEN q.QuizType = 'Final' THEN 100 ELSE 0 END) AS FinalTotal,
                        SUM(CASE WHEN q.QuizType = 'Assignment' THEN best.Score ELSE 0 END) AS AssignmentScore,
                        SUM(CASE WHEN q.QuizType = 'Assignment' THEN 100 ELSE 0 END) AS AssignmentTotal,
                        COUNT(*) AS TotalAttempts,
                        (SELECT COUNT(*)
                         FROM results fr
                         JOIN quizzes fq ON fr.QuizID = fq.QuizID
                         WHERE fr.UserID = u.UserID
                           AND fr.CourseID = c.CourseID
                           AND fr.Score < 50) AS FailedAttempts
                        FROM (
                            SELECT UserID, QuizID, CourseID, MAX(Score) AS Score
                            FROM results
                            GROUP BY UserID, CourseID, QuizID
                        ) best
                        JOIN users u ON best.UserID = u.UserID
                        JOIN quizzes q ON best.QuizID = q.QuizID
                        JOIN courses c ON best.CourseID = c.CourseID AND q.CourseID = c.CourseID
                        JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                        WHERE ic.InstructorID = ?";
        $results_params = [$instructor_id];
        if ($selected_course_id) {
            $results_sql .= " AND best.CourseID = ?";
            $results_params[] = $selected_course_id;
        }
        if ($assessment_type !== '') {
            $results_sql .= " AND q.QuizType = ?";
            $results_params[] = $assessment_type;
        }
        if ($search !== '') {
            $results_sql .= " AND (u.Username LIKE ? OR c.CourseName LIKE ?)";
            $results_params[] = '%' . $search . '%';
            $results_params[] = '%' . $search . '%';
        }
        $results_sql .= " GROUP BY u.UserID, u.Username, c.CourseID, c.CourseName ORDER BY u.Username, c.CourseName";

        $stmt = $this->pdo->prepare($results_sql);
        $stmt->execute($results_params);
        $results = $stmt->fetchAll();
        $page_title = $selected_course_id && $course ? 'Results for ' . $course['CourseName'] : 'Student Results';

        require __DIR__ . '/../views/instructor/course_results.php';
    }

    // Upload content
    public function uploadContent() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $instructor_id = $_SESSION['user_id'];

        // Load only courses belonging to this instructor for the upload dropdown
        $stmt = $this->pdo->prepare("SELECT c.* FROM courses c 
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                     WHERE ic.InstructorID = ?
                                     ORDER BY c.CourseName");
        $stmt->execute([$instructor_id]);
        $courses = $stmt->fetchAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course_id = $_POST['course_id'] ?? null;
            $title = trim($_POST['content_title'] ?? '');
            $type = $_POST['content_type'] ?? 'Text';
            $url = trim($_POST['content_url'] ?? '');
            $article_text = trim($_POST['content_text'] ?? '');
            $content_url = $url;

            if (isset($_FILES['content_file']) && is_uploaded_file($_FILES['content_file']['tmp_name']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../public/uploads/course_contents';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $original_name = basename($_FILES['content_file']['name']);
                $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
                $file_name = $safe_name . '-' . time() . '-' . bin2hex(random_bytes(4)) . ($extension ? '.' . $extension : '');
                $target_path = $upload_dir . '/' . $file_name;

                if (move_uploaded_file($_FILES['content_file']['tmp_name'], $target_path)) {
                    $content_url = 'uploads/course_contents/' . $file_name;
                    if (empty($url) && $type === 'PDF') {
                        $content_url = 'uploads/course_contents/' . $file_name;
                    }
                }
            }

            if (empty($content_url) && !empty($article_text)) {
                $content_url = $article_text;
            }

            if ($course_id && !empty($title)) {
                if ($this->tableHasColumn('course_contents', 'CreatedBy')) {
                    $stmt = $this->pdo->prepare("INSERT INTO course_contents (CourseID, ContentType, ContentTitle, ContentURL, CreatedBy) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$course_id, $type, $title, $content_url, $instructor_id]);
                } else {
                    $stmt = $this->pdo->prepare("INSERT INTO course_contents (CourseID, ContentType, ContentTitle, ContentURL) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$course_id, $type, $title, $content_url]);
                }

                header("Location: index.php?page=manage-content");
                exit;
            }
        }

        require __DIR__ . '/../views/instructor/upload_content.php';
    }

    // Manage course content
    public function manageContent() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $instructor_id = $_SESSION['user_id'];
        $createdAtSelect = $this->tableHasColumn('course_contents', 'CreatedAt') ? 'cc.CreatedAt' : 'NULL';
        $createdAtOrder = $this->tableHasColumn('course_contents', 'CreatedAt') ? 'cc.CreatedAt DESC,' : '';

        // Get all content for courses belonging to this instructor
        $stmt = $this->pdo->prepare("SELECT cc.*, c.CourseName, {$createdAtSelect} AS ContentCreatedAt
                                     FROM course_contents cc
                                     JOIN courses c ON cc.CourseID = c.CourseID
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                     WHERE ic.InstructorID = ?
                                     ORDER BY {$createdAtOrder} cc.ContentID DESC");
        $stmt->execute([$instructor_id]);
        $contents = $stmt->fetchAll();

        require __DIR__ . '/../views/instructor/manage_content.php';
    }

    // Edit content
    public function editContent() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $instructor_id = $_SESSION['user_id'];
        $content_id = $_GET['id'] ?? ($_POST['content_id'] ?? null);

        // Verify content belongs to instructor's course
        $stmt = $this->pdo->prepare("SELECT cc.* FROM course_contents cc
                                     JOIN courses c ON cc.CourseID = c.CourseID
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                     WHERE cc.ContentID = ? AND ic.InstructorID = ?");
        $stmt->execute([$content_id, $instructor_id]);
        $content = $stmt->fetch();

        if (!$content) {
            header("Location: index.php?page=manage-content");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['content_title'] ?? '';
            $type = $_POST['content_type'] ?? 'Text';
            $url = $_POST['content_url'] ?? '';

            if (!empty($title)) {
                $stmt = $this->pdo->prepare("UPDATE course_contents SET ContentTitle = ?, ContentType = ?, ContentURL = ? WHERE ContentID = ?");
                $stmt->execute([$title, $type, $url, $content_id]);

                header("Location: index.php?page=manage-content");
                exit;
            }
        }

        require __DIR__ . '/../views/instructor/edit_content.php';
    }

    // Delete content
    public function deleteContent() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
            header("Location: index.php?page=login");
            exit;
        }

        $instructor_id = $_SESSION['user_id'];
        $content_id = $_POST['content_id'] ?? null;

        if ($content_id) {
            // Verify content belongs to instructor's course
            $stmt = $this->pdo->prepare("SELECT cc.* FROM course_contents cc
                                         JOIN courses c ON cc.CourseID = c.CourseID
                                         JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                         WHERE cc.ContentID = ? AND ic.InstructorID = ?");
            $stmt->execute([$content_id, $instructor_id]);

            if ($stmt->fetch()) {
                $stmt = $this->pdo->prepare("DELETE FROM course_contents WHERE ContentID = ?");
                $stmt->execute([$content_id]);
            }
        }

        header("Location: index.php?page=manage-content");
        exit;
    }

    // Helper methods
    private function tableHasColumn($table, $column) {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$column]);
            return (bool) $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

    private function getTotalStudents($instructor_id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT e.UserID) FROM enrollments e 
                                     JOIN courses c ON e.CourseID = c.CourseID 
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID 
                                     WHERE ic.InstructorID = ?");
        $stmt->execute([$instructor_id]);
        return $stmt->fetchColumn();
    }

    private function getTotalQuizzes($instructor_id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(q.QuizID) FROM quizzes q 
                                     JOIN courses c ON q.CourseID = c.CourseID 
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID 
                                     WHERE ic.InstructorID = ?");
        $stmt->execute([$instructor_id]);
        return $stmt->fetchColumn();
    }

    private function countQuizzesByType($instructor_id, $type) {
        $stmt = $this->pdo->prepare("SELECT COUNT(q.QuizID) FROM quizzes q 
                                     JOIN courses c ON q.CourseID = c.CourseID 
                                     JOIN instructor_courses ic ON c.CourseID = ic.CourseID 
                                     WHERE ic.InstructorID = ? AND q.QuizType = ?");
        $stmt->execute([$instructor_id, $type]);
        return $stmt->fetchColumn();
    }

    private function insertCourse($courseName, $description, $department = null) {
        if ($department === null || $department === '') {
            $department = guessCourseDepartment($courseName, $description);
        }

        if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {
            $stmt = $this->pdo->prepare("INSERT INTO courses (CourseName, Description, Department) VALUES (?, ?, ?) RETURNING CourseID");
            $stmt->execute([$courseName, $description, $department]);
            $row = $stmt->fetch();
            return $row['CourseID'] ?? null;
        }

        $stmt = $this->pdo->prepare("INSERT INTO courses (CourseName, Description, Department) VALUES (?, ?, ?)");
        $stmt->execute([$courseName, $description, $department]);
        return $this->pdo->lastInsertId();
    }
}
