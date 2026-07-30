<?php

class Course {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllCourses() {
        $stmt = $this->pdo->query("SELECT * FROM courses ORDER BY CreatedAt DESC");
        return $stmt->fetchAll();
    }

    public function getCourseById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM courses WHERE CourseID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createCourse($name, $description, $price) {
        $stmt = $this->pdo->prepare("INSERT INTO courses (CourseName, Description, Price) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $description, $price]);
    }

    public function enrollUser($userId, $courseId) {
        // Check if already enrolled
        $stmt = $this->pdo->prepare("SELECT * FROM enrollments WHERE UserID = ? AND CourseID = ?");
        $stmt->execute([$userId, $courseId]);
        if ($stmt->fetch()) return true; // Already enrolled

        $stmt = $this->pdo->prepare("INSERT INTO enrollments (UserID, CourseID) VALUES (?, ?)");
        return $stmt->execute([$userId, $courseId]);
    }

    public function getEnrolledCourses($userId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, e.EnrollmentDate, e.CompletionStatus 
            FROM courses c 
            JOIN enrollments e ON c.CourseID = e.CourseID 
            WHERE e.UserID = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getCourseContent($courseId) {
        $stmt = $this->pdo->prepare("SELECT * FROM course_contents WHERE CourseID = ?");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }
}
?>
