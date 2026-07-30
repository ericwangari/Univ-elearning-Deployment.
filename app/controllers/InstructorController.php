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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course_name = $_POST['course_name'] ?? '';
            $description = $_POST['description'] ?? '';
            if (!empty($course_name)) {
                $stmt = $this->pdo->prepare("INSERT INTO courses (CourseName, Description) VALUES (?, ?)");
                $stmt->execute([$course_name, $description]);

                $course_id = $this->pdo->lastInsertId();

                // Assign course to instructor
                $stmt = $this->pdo->prepare("INSERT INTO instructor_courses (InstructorID, CourseID) VALUES (?, ?)");
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
            $course_name = $_POST['course_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $stmt = $this->pdo->prepare("UPDATE courses SET CourseName = ?, Description = ? WHERE CourseID = ?");
            $stmt->execute([$course_name, $description, $course_id]);

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

            $stmt = $this->pdo->prepare("INSERT INTO quizzes (QuizName, CourseID, QuizType, Description, TotalMarks) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$quiz_name, $course_id, $quiz_type, $description, $total_marks]);

            $quiz_id = $this->pdo->lastInsertId();
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
        $question_text = $_POST['question_text'] ?? '';
        $question_type = $_POST['question_type'] ?? 'Multiple Choice';
        $marks = $_POST['marks'] ?? 1;

        if (!empty($question_text)) {
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
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }

            $marks = (int)$marks;
            if ($marks < 1) $marks = 1;
            $clamped = false;
            if ($marks > $available) {
                $marks = $available; // clamp to remaining available marks
                $clamped = true;
            }

            $stmt = $this->pdo->prepare("INSERT INTO questions (QuizID, QuestionText, QuestionType, Marks) VALUES (?, ?, ?, ?)");
            $stmt->execute([$quiz_id, $question_text, $question_type, $marks]);

            if ($clamped) {
                $_SESSION['success'] = "Question added successfully, but marks were reduced to {$marks} so the assessment stays within the total {$quizTotal} points.";
            } else {
                $_SESSION['success'] = 'Question added successfully.';
            }

            $question_id = $this->pdo->lastInsertId();

            // Add options if multiple choice
            if ($question_type === 'Multiple Choice' && isset($_POST['options'])) {
                foreach ($_POST['options'] as $index => $option_text) {
                    if (!empty($option_text)) {
                        $is_correct = isset($_POST['correct_option']) && $_POST['correct_option'] == $index;
                        $stmt = $this->pdo->prepare("INSERT INTO question_options (QuestionID, OptionText, IsCorrect) VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, $option_text, $is_correct]);
                    }
                }
            } elseif ($question_type === 'True/False') {
                $correct_answer = $_POST['true_false_answer'] ?? '1';
                $stmt = $this->pdo->prepare("INSERT INTO question_options (QuestionID, OptionText, IsCorrect) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, 'True', $correct_answer === '1' ? 1 : 0]);
                $stmt->execute([$question_id, 'False', $correct_answer === '0' ? 1 : 0]);
            }
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
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
            $stmt->execute([$is_correct, $answer_id]);

            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(q.Marks), 0) FROM user_answers ua
                                         JOIN questions q ON ua.QuestionID = q.QuestionID
                                         WHERE ua.UserID = ? AND q.QuizID = ? AND ua.IsCorrect = 1");
            $stmt->execute([$answer['UserID'], $answer['QuizID']]);
            $correctMarks = (float)$stmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(Marks), 0) FROM questions WHERE QuizID = ?");
            $stmt->execute([$answer['QuizID']]);
            $questionTotal = (float)$stmt->fetchColumn();
            $score = ($questionTotal > 0) ? round(($correctMarks / $questionTotal) * 100, 2) : 0;
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
                      JOIN courses c ON r.CourseID = c.CourseID
                      JOIN users u ON r.UserID = u.UserID
                      JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                      WHERE ic.InstructorID = ?";
        $stats_params = [$instructor_id];
        if ($selected_course_id) {
            $stats_sql .= " AND r.CourseID = ?";
            $stats_params[] = $selected_course_id;
        }
        if ($assessment_type !== '') {
            $stats_sql .= " AND q.QuizType = ?";
            $stats_params[] = $assessment_type;
        }
        if ($search !== '') {
            $stats_sql .= " AND (u.Username LIKE ? OR c.CourseName LIKE ?)";
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
                        SUM(CASE WHEN q.QuizType = 'Quiz' THEN q.TotalMarks ELSE 0 END) AS QuizTotal,
                        SUM(CASE WHEN q.QuizType = 'Midterm' THEN best.Score ELSE 0 END) AS MidtermScore,
                        SUM(CASE WHEN q.QuizType = 'Midterm' THEN q.TotalMarks ELSE 0 END) AS MidtermTotal,
                        SUM(CASE WHEN q.QuizType = 'Final' THEN best.Score ELSE 0 END) AS FinalScore,
                        SUM(CASE WHEN q.QuizType = 'Final' THEN q.TotalMarks ELSE 0 END) AS FinalTotal,
                        SUM(CASE WHEN q.QuizType = 'Assignment' THEN best.Score ELSE 0 END) AS AssignmentScore,
                        SUM(CASE WHEN q.QuizType = 'Assignment' THEN q.TotalMarks ELSE 0 END) AS AssignmentTotal,
                        COUNT(*) AS TotalAttempts,
                        (SELECT COUNT(*)
                         FROM results fr
                         JOIN quizzes fq ON fr.QuizID = fq.QuizID
                         WHERE fr.UserID = best.UserID
                           AND fr.CourseID = best.CourseID
                           AND fq.TotalMarks > 0
                           AND ((fr.Score / fq.TotalMarks) * 100) < 50) AS FailedAttempts
                        FROM (
                            SELECT UserID, QuizID, CourseID, MAX(Score) AS Score
                            FROM results
                            GROUP BY UserID, QuizID, CourseID
                        ) best
                        JOIN users u ON best.UserID = u.UserID
                        JOIN quizzes q ON best.QuizID = q.QuizID
                        JOIN courses c ON best.CourseID = c.CourseID
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
}
