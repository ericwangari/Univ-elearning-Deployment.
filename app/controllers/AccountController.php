<?php

class AccountController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function deleteAccount() {
        requireLogin();

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $username = $_SESSION['username'] ?? '';
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/account/delete.php';
            return;
        }

        $confirmation = trim($_POST['confirmation'] ?? '');
        if ($confirmation !== 'DELETE') {
            $error = 'Type DELETE to confirm account deletion.';
            require __DIR__ . '/../views/account/delete.php';
            return;
        }

        try {
            $this->pdo->beginTransaction();

            if ($this->tableHasColumn('messages', 'SenderID') && $this->tableHasColumn('messages', 'ReceiverID')) {
                $stmt = $this->pdo->prepare("DELETE FROM messages WHERE SenderID = ? OR ReceiverID = ?");
                $stmt->execute([$userId, $userId]);
            }

            $this->deleteRows('email_verification_tokens', 'UserID', $userId);
            $this->deleteRows('password_reset_tokens', 'UserID', $userId);
            $this->deleteRows('user_answers', 'UserID', $userId);
            $this->deleteRows('quiz_attempts', 'UserID', $userId);
            $this->deleteRows('results', 'UserID', $userId);
            $this->deleteRows('course_progress', 'UserID', $userId);
            $this->deleteRows('enrollments', 'UserID', $userId);
            $this->deleteRows('instructor_courses', 'InstructorID', $userId);

            if ($this->tableHasColumn('course_contents', 'CreatedBy')) {
                $stmt = $this->pdo->prepare("UPDATE course_contents SET CreatedBy = NULL WHERE CreatedBy = ?");
                $stmt->execute([$userId]);
            }

            $stmt = $this->pdo->prepare("DELETE FROM users WHERE UserID = ?");
            $stmt->execute([$userId]);

            $this->pdo->commit();
            session_destroy();
            redirect('index.php?page=login');
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('Self account delete failed: ' . $e->getMessage());
            $error = 'Your account could not be deleted right now. Please contact support.';
            require __DIR__ . '/../views/account/delete.php';
        }
    }

    private function deleteRows($table, $column, $value) {
        if (!$this->tableHasColumn($table, $column)) {
            return;
        }

        $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$value]);
    }

    private function tableHasColumn($table, $column) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_name = ? AND lower(column_name) = lower(?)"
        );
        $stmt->execute([$table, $column]);
        return ((int)$stmt->fetchColumn()) > 0;
    }
}
