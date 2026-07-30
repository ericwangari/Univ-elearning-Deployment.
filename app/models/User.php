<?php

class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function register($username, $email, $password, $user_type) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (Username, Email, Password, UserType) VALUES (?, ?, ?, ?)");
        try {
            return $stmt->execute([$username, $email, $hashed_password, $user_type]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password'])) {
            return $user;
        }
        return false;
    }

    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE UserID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getPendingInstructors() {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE UserType = 'Instructor' AND Status = 'Pending' ORDER BY CreatedAt DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllInstructors() {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE UserType = 'Instructor' ORDER BY CreatedAt DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus($userId, $status) {
        $stmt = $this->pdo->prepare("UPDATE users SET Status = ? WHERE UserID = ?");
        return $stmt->execute([$status, $userId]);
    }
}
?>
