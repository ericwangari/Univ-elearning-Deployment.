<?php

class Quiz {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getQuizzesByCourse($courseId) {
        $stmt = $this->pdo->prepare("SELECT * FROM quizzes WHERE CourseID = ?");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    public function getQuizById($quizId) {
        $stmt = $this->pdo->prepare("SELECT * FROM quizzes WHERE QuizID = ?");
        $stmt->execute([$quizId]);
        return $stmt->fetch();
    }

    public function saveResult($userId, $courseId, $quizId, $score) {
        $stmt = $this->pdo->prepare("INSERT INTO results (UserID, CourseID, QuizID, Score) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $courseId, $quizId, $score]);
    }

    public function getUserResults($userId) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, q.QuizName, c.CourseName 
            FROM results r 
            JOIN quizzes q ON r.QuizID = q.QuizID 
            JOIN courses c ON r.CourseID = c.CourseID 
            WHERE r.UserID = ?
            ORDER BY r.SubmittedAt DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAllResults() {
        $stmt = $this->pdo->query("
            SELECT r.*, u.Username, q.QuizName, c.CourseName 
            FROM results r 
            JOIN users u ON r.UserID = u.UserID 
            JOIN quizzes q ON r.QuizID = q.QuizID 
            JOIN courses c ON r.CourseID = c.CourseID 
            ORDER BY r.SubmittedAt DESC
        ");
        return $stmt->fetchAll();
    }

    public function getQuestionsByQuiz($quizId) {
        $stmt = $this->pdo->prepare("SELECT * FROM questions WHERE QuizID = ? ORDER BY QuestionID");
        $stmt->execute([$quizId]);
        return $stmt->fetchAll();
    }

    public function getOptionsByQuestion($questionId) {
        $stmt = $this->pdo->prepare("SELECT * FROM question_options WHERE QuestionID = ? ORDER BY OptionID");
        $stmt->execute([$questionId]);
        return $stmt->fetchAll();
    }

    public function getOptionById($optionId) {
        $stmt = $this->pdo->prepare("SELECT * FROM question_options WHERE OptionID = ?");
        $stmt->execute([$optionId]);
        return $stmt->fetch();
    }

    public function saveUserAnswer($userId, $quizId, $questionId, $selectedOptionId, $isCorrect, $pointsEarned) {
        $stmt = $this->pdo->prepare("INSERT INTO user_answers (UserID, QuestionID, SelectedOptionID, IsCorrect)
                                     VALUES (?, ?, ?, ?)
                                     ON DUPLICATE KEY UPDATE SelectedOptionID = ?, IsCorrect = ?");
        return $stmt->execute([$userId, $questionId, $selectedOptionId, $isCorrect, $selectedOptionId, $isCorrect]);
    }

    public function logQuizAttempt($userId, $quizId, $score, $status = 'Graded') {
        $stmt = $this->pdo->prepare("INSERT INTO quiz_attempts (UserID, QuizID, Score, Status, SubmittedAt) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
        return $stmt->execute([$userId, $quizId, $score, $status]);
    }
}
?>
