<?php
require_once __DIR__ . '/../models/User.php';

class AdminController {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Admin Dashboard with statistics
    public function dashboard() {
        requireRole('Admin');

        $overviewPeriod = $_GET['period'] ?? 'month';
        if (!in_array($overviewPeriod, ['day', 'month', 'year'], true)) {
            $overviewPeriod = 'month';
        }

        $periodRange = $this->getPeriodRange($overviewPeriod);

        // Get statistics
        $stats = [
            'total_users' => $this->getTotalUsers(),
            'total_instructors' => $this->countUsersByType('Instructor'),
            'pending_instructors' => $this->countUsersByStatus('Instructor', 'Pending'),
            'total_students' => $this->countUsersByType('Student'),
            'total_courses' => $this->getTotalCourses(),
            'total_enrollments' => $this->getTotalEnrollments(),
            'total_quizzes' => $this->getTotalQuizzes(),
        ];

        $periodStats = [
            'total_users' => $this->getTotalUsers($periodRange),
            'total_instructors' => $this->countUsersByType('Instructor', $periodRange),
            'pending_instructors' => $this->countUsersByStatus('Instructor', 'Pending', $periodRange),
            'total_students' => $this->countUsersByType('Student', $periodRange),
            'total_courses' => $this->getTotalCourses($periodRange),
            'total_enrollments' => $this->getTotalEnrollments($periodRange),
            'total_quizzes' => $this->getTotalQuizzes($periodRange),
        ];

        require __DIR__ . '/../views/admin/dashboard.php';
    }

    // View all users
    public function users() {
        requireRole('Admin');

        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? '';
        $sort = $_GET['sort'] ?? 'created';
        $direction = strtolower($_GET['direction'] ?? 'desc');

        if (!in_array($sort, ['created', 'role'], true)) {
            $sort = 'created';
        }
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query = "SELECT * FROM users WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (Username LIKE ? OR Email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($type)) {
            $query .= " AND UserType = ?";
            $params[] = $type;
        }

        if ($sort === 'role') {
            $query .= " ORDER BY UserType " . strtoupper($direction) . ", Username ASC";
        } else {
            $query .= " ORDER BY CreatedAt " . strtoupper($direction);
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        require __DIR__ . '/../views/admin/users.php';
    }

    // Edit user type
    public function editUser() {
        requireRole('Admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_POST['user_id'];
            $user_type = $_POST['user_type'];

            $stmt = $this->pdo->prepare("UPDATE users SET UserType = ? WHERE UserID = ?");
            $stmt->execute([$user_type, $user_id]);

            redirect('index.php?page=admin-users');
        }

        $user_id = $_GET['id'] ?? null;
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE UserID = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        require __DIR__ . '/../views/admin/edit_user.php';
    }

    // Delete user
    public function deleteUser() {
        requireRole('Admin');

        $user_id = $_POST['user_id'] ?? null;

        if ($user_id && $user_id != $_SESSION['user_id']) {
            try {
                $this->pdo->beginTransaction();

                if ($this->tableHasColumn('messages', 'SenderID') && $this->tableHasColumn('messages', 'ReceiverID')) {
                    $stmt = $this->pdo->prepare("DELETE FROM messages WHERE SenderID = ? OR ReceiverID = ?");
                    $stmt->execute([$user_id, $user_id]);
                }

                $this->deleteUserRows('email_verification_tokens', 'UserID', $user_id);
                $this->deleteUserRows('password_reset_tokens', 'UserID', $user_id);
                $this->deleteUserRows('user_answers', 'UserID', $user_id);
                $this->deleteUserRows('quiz_attempts', 'UserID', $user_id);
                $this->deleteUserRows('results', 'UserID', $user_id);
                $this->deleteUserRows('course_progress', 'UserID', $user_id);
                $this->deleteUserRows('enrollments', 'UserID', $user_id);
                $this->deleteUserRows('instructor_courses', 'InstructorID', $user_id);

                if ($this->tableHasColumn('course_contents', 'CreatedBy')) {
                    $stmt = $this->pdo->prepare("UPDATE course_contents SET CreatedBy = NULL WHERE CreatedBy = ?");
                    $stmt->execute([$user_id]);
                }

                $stmt = $this->pdo->prepare("DELETE FROM users WHERE UserID = ?");
                $stmt->execute([$user_id]);

                $this->pdo->commit();
                $_SESSION['success'] = "Account deleted successfully.";
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                error_log("Admin user delete failed: " . $e->getMessage());
                $_SESSION['error'] = "Could not delete that account. Please try again.";
            }
        }

        redirect('index.php?page=admin-users');
    }

    // View all courses
    public function courses() {
        requireRole('Admin');

        $stmt = $this->pdo->query("SELECT c.*, 
                                   COUNT(DISTINCT e.EnrollmentID) as EnrollmentCount,
                                   COUNT(DISTINCT q.QuizID) as QuizCount,
                                   GROUP_CONCAT(DISTINCT u.Username ORDER BY u.Username SEPARATOR ', ') as InstructorNames
                                   FROM courses c 
                                   LEFT JOIN enrollments e ON c.CourseID = e.CourseID 
                                   LEFT JOIN quizzes q ON c.CourseID = q.CourseID
                                   LEFT JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                   LEFT JOIN users u ON ic.InstructorID = u.UserID
                                   GROUP BY c.CourseID 
                                   ORDER BY c.CreatedAt DESC");
        $courses = $stmt->fetchAll();

        require __DIR__ . '/../views/admin/courses.php';
    }

    // Create a course and optionally assign it to an approved instructor
    public function createCourse() {
        requireRole('Admin');

        $instructors = $this->getApprovedInstructors();
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course_name = trim($_POST['course_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $instructor_id = $_POST['instructor_id'] ?? '';

            if ($course_name === '') {
                $errors[] = "Course name is required.";
            }

            if (empty($errors)) {
                $course_id = $this->insertCourse($course_name, $description);

                if ($instructor_id !== '') {
                    $stmt = $this->pdo->prepare("INSERT IGNORE INTO instructor_courses (InstructorID, CourseID) VALUES (?, ?)");
                    $stmt->execute([$instructor_id, $course_id]);
                }

                redirect('index.php?page=admin-courses');
            }
        }

        require __DIR__ . '/../views/admin/create_course.php';
    }

    // Edit a course and its instructor assignment
    public function editCourse() {
        requireRole('Admin');

        $course_id = $_GET['id'] ?? ($_POST['course_id'] ?? null);
        $instructors = $this->getApprovedInstructors();
        $errors = [];

        $stmt = $this->pdo->prepare("SELECT c.*, ic.InstructorID FROM courses c
                                     LEFT JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                     WHERE c.CourseID = ?
                                     LIMIT 1");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();

        if (!$course) {
            redirect('index.php?page=admin-courses');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course_name = trim($_POST['course_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $instructor_id = $_POST['instructor_id'] ?? '';

            if ($course_name === '') {
                $errors[] = "Course name is required.";
            }

            if (empty($errors)) {
                $stmt = $this->pdo->prepare("UPDATE courses SET CourseName = ?, Description = ? WHERE CourseID = ?");
                $stmt->execute([$course_name, $description, $course_id]);

                $stmt = $this->pdo->prepare("DELETE FROM instructor_courses WHERE CourseID = ?");
                $stmt->execute([$course_id]);

                if ($instructor_id !== '') {
                    $stmt = $this->pdo->prepare("INSERT INTO instructor_courses (InstructorID, CourseID) VALUES (?, ?)");
                    $stmt->execute([$instructor_id, $course_id]);
                }

                redirect('index.php?page=admin-courses');
            }
        }

        require __DIR__ . '/../views/admin/edit_course.php';
    }

    // Delete a course
    public function deleteCourse() {
        requireRole('Admin');

        $course_id = $_POST['course_id'] ?? null;

        if ($course_id) {
            $stmt = $this->pdo->prepare("DELETE FROM courses WHERE CourseID = ?");
            $stmt->execute([$course_id]);
        }

        redirect('index.php?page=admin-courses');
    }

    // View all results
    public function allResults() {
        requireRole('Admin');

        $filter_course = $_GET['course'] ?? '';

        $query = "SELECT r.*, u.Username, c.CourseName, q.QuizName, q.QuizType, q.TotalMarks FROM results r
                  JOIN users u ON r.UserID = u.UserID 
                  JOIN courses c ON r.CourseID = c.CourseID 
                  JOIN quizzes q ON r.QuizID = q.QuizID WHERE 1=1";
        
        $params = [];

        if (!empty($filter_course)) {
            $query .= " AND r.CourseID = ?";
            $params[] = $filter_course;
        }

        $query .= " ORDER BY r.SubmittedAt DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Get courses for filter dropdown
        $stmt = $this->pdo->query("SELECT * FROM courses ORDER BY CourseName");
        $courses = $stmt->fetchAll();

        require __DIR__ . '/../views/admin/all_results.php';
    }

    // Manage instructors (Approval flow)
    public function manageInstructors() {
        requireRole('Admin');

        $status_filter = $_GET['status'] ?? 'Pending';
        
        $query = "SELECT * FROM users WHERE UserType = 'Instructor'";
        $params = [];

        if (!empty($status_filter)) {
            $query .= " AND Status = ?";
            $params[] = $status_filter;
        }

        $query .= " ORDER BY CreatedAt DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $instructors = $stmt->fetchAll();

        require __DIR__ . '/../views/admin/manage_instructors.php';
    }

    // Approve instructor
    public function approveInstructor() {
        requireRole('Admin');

        $user_id = $_GET['id'] ?? null;
        if ($user_id) {
            $userModel = new User($this->pdo);
            if ($userModel->updateStatus($user_id, 'Approved')) {
                $_SESSION['success'] = "Instructor is now active.";
            } else {
                $_SESSION['error'] = "Failed to update instructor status.";
            }
        }

        redirect('index.php?page=manage-instructors');
    }

    // Reject instructor
    public function rejectInstructor() {
        requireRole('Admin');

        $user_id = $_GET['id'] ?? null;
        if ($user_id) {
            $userModel = new User($this->pdo);
            if ($userModel->updateStatus($user_id, 'Rejected')) {
                $_SESSION['error'] = "Instructor is now inactive.";
            } else {
                $_SESSION['error'] = "Failed to update instructor status.";
            }
        }

        redirect('index.php?page=manage-instructors');
    }

    // Helper methods
    private function getPeriodRange($period) {
        switch ($period) {
            case 'day':
                return [
                    'start' => date('Y-m-d 00:00:00'),
                    'end' => date('Y-m-d 00:00:00', strtotime('+1 day')),
                ];
            case 'year':
                return [
                    'start' => date('Y-01-01 00:00:00'),
                    'end' => date('Y-01-01 00:00:00', strtotime('+1 year')),
                ];
            case 'month':
            default:
                return [
                    'start' => date('Y-m-01 00:00:00'),
                    'end' => date('Y-m-01 00:00:00', strtotime('+1 month')),
                ];
        }
    }

    private function applyDateRange(&$query, &$params, $column, $range) {
        if (!$range) {
            return;
        }

        $query .= " AND {$column} >= ? AND {$column} < ?";
        $params[] = $range['start'];
        $params[] = $range['end'];
    }

    private function tableHasColumn($table, $column) {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$column]);
            return (bool) $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

    private function deleteUserRows($table, $column, $userId) {
        if (!$this->tableHasColumn($table, $column)) {
            return;
        }

        $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$userId]);
    }

    private function getTotalUsers($range = null) {
        $query = "SELECT COUNT(*) FROM users WHERE 1=1";
        $params = [];
        $this->applyDateRange($query, $params, 'CreatedAt', $range);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function countUsersByType($type, $range = null) {
        $query = "SELECT COUNT(*) FROM users WHERE UserType = ?";
        $params = [$type];
        $this->applyDateRange($query, $params, 'CreatedAt', $range);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function countUsersByStatus($type, $status, $range = null) {
        $query = "SELECT COUNT(*) FROM users WHERE UserType = ? AND Status = ?";
        $params = [$type, $status];
        $this->applyDateRange($query, $params, 'CreatedAt', $range);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function getTotalCourses($range = null) {
        $query = "SELECT COUNT(*) FROM courses WHERE 1=1";
        $params = [];
        $this->applyDateRange($query, $params, 'CreatedAt', $range);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function getTotalEnrollments($range = null) {
        $query = "SELECT COUNT(*) FROM enrollments WHERE 1=1";
        $params = [];
        $this->applyDateRange($query, $params, 'EnrollmentDate', $range);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function getTotalQuizzes($range = null) {
        if ($range && !$this->tableHasColumn('quizzes', 'CreatedAt')) {
            $query = "SELECT COUNT(q.QuizID) FROM quizzes q
                      JOIN courses c ON q.CourseID = c.CourseID
                      WHERE 1=1";
            $params = [];
            $this->applyDateRange($query, $params, 'c.CreatedAt', $range);
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        }

        $query = "SELECT COUNT(*) FROM quizzes WHERE 1=1";
        $params = [];
        if ($range && $this->tableHasColumn('quizzes', 'CreatedAt')) {
            $this->applyDateRange($query, $params, 'CreatedAt', $range);
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function getApprovedInstructors() {
        $stmt = $this->pdo->query("SELECT UserID, Username FROM users 
                                   WHERE UserType = 'Instructor' AND Status = 'Approved' 
                                   ORDER BY Username");
        return $stmt->fetchAll();
    }

    private function insertCourse($courseName, $description) {
        if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {
            $stmt = $this->pdo->prepare("INSERT INTO courses (CourseName, Description) VALUES (?, ?) RETURNING CourseID");
            $stmt->execute([$courseName, $description]);
            $row = $stmt->fetch();
            return $row['CourseID'] ?? null;
        }

        $stmt = $this->pdo->prepare("INSERT INTO courses (CourseName, Description) VALUES (?, ?)");
        $stmt->execute([$courseName, $description]);
        return $this->pdo->lastInsertId();
    }
}
