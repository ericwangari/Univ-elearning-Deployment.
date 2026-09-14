<?php
require_once __DIR__ . '/../models/Quiz.php';

class QuizController {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function takeQuiz() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $quiz_id = $_GET['id'] ?? null;

        // Get quiz details
        $stmt = $this->pdo->prepare("SELECT q.* FROM quizzes q WHERE q.QuizID = ?");
        $stmt->execute([$quiz_id]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            header("Location: index.php?page=courses");
            exit;
        }

        // Check if user is enrolled in the course
        $stmt = $this->pdo->prepare("SELECT * FROM enrollments WHERE UserID = ? AND CourseID = ?");
        $stmt->execute([$user_id, $quiz['CourseID']]);
        if (!$stmt->fetch()) {
            header("Location: index.php?page=courses");
            exit;
        }

        // Detect the latest completed result for this quiz and whether the user is choosing to retry.
        $stmt = $this->pdo->prepare("SELECT * FROM results WHERE UserID = ? AND QuizID = ? ORDER BY SubmittedAt DESC LIMIT 1");
        $stmt->execute([$user_id, $quiz_id]);
        $last_result = $stmt->fetch();
        $allow_retry = isset($_GET['retry']) && $_GET['retry'] === '1';

        if ($last_result && !$allow_retry && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $questions = [];
            $time_left = 0;
            require __DIR__ . '/../views/student/take_quiz.php';
            return;
        }

        // Prevent browser caching and back-navigation for active quiz sessions
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Track quiz timer in the session so refresh/back does not reset time
        $duration = 1800; // 30 minutes
        if (!isset($_SESSION['quiz_timer'][$quiz_id])) {
            $_SESSION['quiz_timer'][$quiz_id] = [
                'start' => time(),
                'duration' => $duration,
            ];
        }

        $elapsed = time() - $_SESSION['quiz_timer'][$quiz_id]['start'];
        if ($elapsed >= $_SESSION['quiz_timer'][$quiz_id]['duration']) {
            // Quiz time expired. Start a fresh attempt and clear previous answers for this quiz.
            $_SESSION['quiz_timer'][$quiz_id] = [
                'start' => time(),
                'duration' => $duration,
            ];
            $elapsed = 0;
            $stmt = $this->pdo->prepare(
                "DELETE ua FROM user_answers ua
                 JOIN questions q ON ua.QuestionID = q.QuestionID
                 WHERE ua.UserID = ? AND q.QuizID = ?"
            );
            $stmt->execute([$user_id, $quiz_id]);
        }

        $time_left = max(0, $_SESSION['quiz_timer'][$quiz_id]['duration'] - $elapsed);

        // Clear any previous answer records for this quiz when starting a new attempt or retry.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $allow_retry) {
            unset($_SESSION['quiz_timer'][$quiz_id]);
            $_SESSION['quiz_timer'][$quiz_id] = [
                'start' => time(),
                'duration' => $duration,
            ];

            $stmt = $this->pdo->prepare(
                "DELETE ua FROM user_answers ua
                 JOIN questions q ON ua.QuestionID = q.QuestionID
                 WHERE ua.UserID = ? AND q.QuizID = ?"
            );
            $stmt->execute([$user_id, $quiz_id]);
        }

        // Handle quiz submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
            // Validate timer server-side
            $elapsed = time() - $_SESSION['quiz_timer'][$quiz_id]['start'];
            if ($elapsed > $_SESSION['quiz_timer'][$quiz_id]['duration']) {
                $_SESSION['error'] = 'Your quiz time has expired. Please start a new attempt.';
                header("Location: index.php?page=take-quiz&id=" . $quiz_id);
                exit;
            }

            // Process all answers (supports multiple choice, true/false and short answer)
            $MAX_SHORT_ANSWER = 500;
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'answer_') === 0) {
                    $question_id = (int)substr($key, 7); // Remove 'answer_' prefix
                    if ($question_id <= 0) continue;

                    // Fetch question type to determine how to save the answer
                    $stmt = $this->pdo->prepare("SELECT QuestionType FROM questions WHERE QuestionID = ?");
                    $stmt->execute([$question_id]);
                    $qrow = $stmt->fetch();
                    $qtype = $qrow ? $qrow['QuestionType'] : 'Multiple Choice';

                    if ($qtype === 'Short Answer') {
                        $answer_text = trim($value);
                        if (strlen($answer_text) > $MAX_SHORT_ANSWER) {
                            $answer_text = substr($answer_text, 0, $MAX_SHORT_ANSWER);
                        }

                        // Save or update short answer (grading may be manual later)
                        $stmt = $this->pdo->prepare("INSERT INTO user_answers (UserID, QuestionID, AnswerText, IsCorrect) VALUES (?, ?, ?, NULL) 
                                                     ON DUPLICATE KEY UPDATE AnswerText = ?, IsCorrect = NULL");
                        $stmt->execute([$user_id, $question_id, $answer_text, $answer_text]);

                    } elseif ($qtype === 'True/False') {
                        $answer_text = ((string)$value === '1') ? 'True' : 'False';
                        $stmt = $this->pdo->prepare("SELECT OptionID, IsCorrect FROM question_options WHERE QuestionID = ? AND OptionText = ? LIMIT 1");
                        $stmt->execute([$question_id, $answer_text]);
                        $option = $stmt->fetch();

                        if ($option) {
                            $stmt = $this->pdo->prepare("INSERT INTO user_answers (UserID, QuestionID, SelectedOptionID, AnswerText, IsCorrect)
                                                         VALUES (?, ?, ?, ?, ?)
                                                         ON DUPLICATE KEY UPDATE SelectedOptionID = ?, AnswerText = ?, IsCorrect = ?");
                            $stmt->execute([
                                $user_id, $question_id, $option['OptionID'], (string)$value, $option['IsCorrect'],
                                $option['OptionID'], (string)$value, $option['IsCorrect']
                            ]);
                        }

                    } else {
                        // treat as option id (multiple choice / true-false)
                        $option_id = $value;
                        // Get the option to check if correct
                        $stmt = $this->pdo->prepare("SELECT IsCorrect FROM question_options WHERE OptionID = ?");
                        $stmt->execute([$option_id]);
                        $option = $stmt->fetch();

                        if ($option) {
                            // Save or update answer
                            $stmt = $this->pdo->prepare("INSERT INTO user_answers (UserID, QuestionID, SelectedOptionID, IsCorrect) 
                                                         VALUES (?, ?, ?, ?) 
                                                         ON DUPLICATE KEY UPDATE SelectedOptionID = ?, IsCorrect = ?");
                            $stmt->execute([
                                $user_id, $question_id, $option_id, $option['IsCorrect'],
                                $option_id, $option['IsCorrect']
                            ]);
                        }
                    }
                }
            }

            // Calculate score and submit
            $this->submitQuiz($user_id, $quiz_id);
            unset($_SESSION['quiz_timer'][$quiz_id]);
            header("Location: index.php?page=my-results");
            exit;
        }

        // Get questions
        $stmt = $this->pdo->prepare("SELECT * FROM questions WHERE QuizID = ? ORDER BY QuestionID");
        $stmt->execute([$quiz_id]);
        $questions = $stmt->fetchAll();

        // Get options for each question and user's previous answers
        foreach ($questions as $key => $question) {
            $stmt = $this->pdo->prepare("SELECT * FROM question_options WHERE QuestionID = ?");
            $stmt->execute([$question['QuestionID']]);
            $questions[$key]['options'] = $stmt->fetchAll();

            $stmt = $this->pdo->prepare("SELECT * FROM user_answers WHERE UserID = ? AND QuestionID = ?");
            $stmt->execute([$user_id, $question['QuestionID']]);
            $questions[$key]['user_answer'] = $stmt->fetch();
        }

        require __DIR__ . '/../views/student/take_quiz.php';
    }

    private function submitQuiz($user_id, $quiz_id) {
        // Calculate score
        $stmt = $this->pdo->prepare("SELECT SUM(q.Marks) as total_marks FROM questions q WHERE q.QuizID = ?");
        $stmt->execute([$quiz_id]);
        $result = $stmt->fetch();
        $question_total = ($result && isset($result['total_marks'])) ? (float)$result['total_marks'] : 0;

        // Count correct answers
        $stmt = $this->pdo->prepare("SELECT SUM(q.Marks) as score FROM user_answers ua 
                                     JOIN questions q ON ua.QuestionID = q.QuestionID 
                                     WHERE ua.UserID = ? AND q.QuizID = ? AND ua.IsCorrect = 1");
        $stmt->execute([$user_id, $quiz_id]);
        $result = $stmt->fetch();
        $correct_marks = ($result && isset($result['score'])) ? (float)$result['score'] : 0;

        // Get course ID and normalize all results to a 100-point scale.
        $stmt = $this->pdo->prepare("SELECT CourseID FROM quizzes WHERE QuizID = ?");
        $stmt->execute([$quiz_id]);
        $quiz_data = $stmt->fetch();
        $course_id = $quiz_data ? $quiz_data['CourseID'] : null;

        $score = ($question_total > 0) ? round(($correct_marks / $question_total) * 100, 2) : 0;
        $score = min($score, 100);

        // Save result
        $stmt = $this->pdo->prepare("INSERT INTO results (UserID, CourseID, QuizID, Score) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $course_id, $quiz_id, $score]);

        $status = $this->hasPendingManualAnswers($user_id, $quiz_id) ? 'Submitted' : 'Graded';
        $stmt = $this->pdo->prepare("INSERT INTO quiz_attempts (UserID, QuizID, Score, Status, SubmittedAt) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$user_id, $quiz_id, $score, $status]);
    }

    public function myResults() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $course_id = $_GET['course_id'] ?? null;
        $selected_course_id = $course_id;
        $search = trim($_GET['search'] ?? '');

        $stmt = $this->pdo->prepare("SELECT DISTINCT c.CourseID, c.CourseName
                                     FROM results r
                                     JOIN courses c ON r.CourseID = c.CourseID
                                     WHERE r.UserID = ?
                                     ORDER BY c.CourseName");
        $stmt->execute([$user_id]);
        $courses = $stmt->fetchAll();

        $sql = "SELECT r.*, c.CourseName, q.QuizID, q.QuizName, q.QuizType, q.TotalMarks,
                (SELECT COUNT(*) FROM results r2 WHERE r2.UserID = r.UserID AND r2.QuizID = r.QuizID) AS AttemptCount,
                (SELECT COUNT(*) FROM results r2
                 JOIN quizzes q2 ON r2.QuizID = q2.QuizID
                 WHERE r2.UserID = r.UserID
                   AND r2.QuizID = r.QuizID
                   AND q2.TotalMarks > 0
                   AND ((r2.Score / q2.TotalMarks) * 100) < 50) AS FailedAttempts
                FROM results r
                JOIN courses c ON r.CourseID = c.CourseID
                JOIN quizzes q ON r.QuizID = q.QuizID
                WHERE r.UserID = ?";
        $params = [$user_id];

        if ($course_id) {
            $sql .= " AND r.CourseID = ?";
            $params[] = $course_id;
        }

        if ($search !== '') {
            $sql .= " AND (c.CourseName LIKE ? OR q.QuizName LIKE ?)
                     ";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql .= " ORDER BY r.SubmittedAt DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        $stats = ['Quiz' => 0, 'Midterm' => 0, 'Final' => 0, 'Assignment' => 0];
        foreach ($results as $r) {
            if (isset($stats[$r['QuizType']])) {
                $stats[$r['QuizType']]++;
            }
        }

        require __DIR__ . '/../views/student/results.php';
    }

    public function quizDetail() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $result_id = $_GET['id'] ?? null;

        $stmt = $this->pdo->prepare("SELECT r.*, c.CourseName, q.QuizName, q.QuizType, q.TotalMarks 
                                     FROM results r 
                                     JOIN courses c ON r.CourseID = c.CourseID 
                                     JOIN quizzes q ON r.QuizID = q.QuizID 
                                     WHERE r.ResultID = ? AND r.UserID = ?");
        $stmt->execute([$result_id, $user_id]);
        $result = $stmt->fetch();

        if (!$result) {
            header("Location: index.php?page=my-results");
            exit;
        }

        // Get questions and user answers
        $stmt = $this->pdo->prepare("SELECT q.*, ua.SelectedOptionID, ua.IsCorrect, 
                                     qo.OptionText as SelectedOption 
                                     FROM questions q 
                                     LEFT JOIN user_answers ua ON q.QuestionID = ua.QuestionID AND ua.UserID = ?
                                     LEFT JOIN question_options qo ON ua.SelectedOptionID = qo.OptionID
                                     WHERE q.QuizID = ? 
                                     ORDER BY q.QuestionID");
        $stmt->execute([$user_id, $result['QuizID']]);
        $questions = $stmt->fetchAll();

        // Get options for each question
        foreach ($questions as $key => $question) {
            $stmt = $this->pdo->prepare("SELECT * FROM question_options WHERE QuestionID = ?");
            $stmt->execute([$question['QuestionID']]);
            $questions[$key]['options'] = $stmt->fetchAll();
        }

        require __DIR__ . '/../views/student/quiz_detail.php';
    }

    private function hasPendingManualAnswers($user_id, $quiz_id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_answers ua
                                     JOIN questions q ON ua.QuestionID = q.QuestionID
                                     WHERE ua.UserID = ? AND q.QuizID = ? AND q.QuestionType = 'Short Answer' AND ua.IsCorrect IS NULL");
        $stmt->execute([$user_id, $quiz_id]);
        return ((int)$stmt->fetchColumn()) > 0;
    }
}
