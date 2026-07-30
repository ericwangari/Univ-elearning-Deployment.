<?php
// Session is started in config.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/CourseController.php';
require_once __DIR__ . '/../app/controllers/QuizController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/InstructorController.php';
require_once __DIR__ . '/../app/controllers/MessageController.php';
require_once __DIR__ . '/../app/controllers/FeedbackController.php';

$page = $_GET['page'] ?? 'login';

$auth = new AuthController($pdo);
$course = new CourseController($pdo);
$quiz = new QuizController($pdo);
$admin = new AdminController($pdo);
$instructor = new InstructorController($pdo);
$message = new MessageController($pdo);
$feedback = new FeedbackController($pdo);

switch ($page) {

    case 'login':
        $auth->login();
        break;
    case 'register':
        $auth->register();
        break;

    case 'verify-email':
        $auth->verifyEmail();
        break;

    case 'forgot-password':
        $auth->forgotPassword();
        break;

    case 'reset-password':
        $auth->resetPassword();
        break;

    case 'logout':
        $auth->logout();
        break;

    case 'courses':
        $course->courses();
        break;

    case 'course-details':
        $course->courseDetails();
        break;

    case 'my-enrollments':
        $course->myEnrollments();
        break;

    case 'results':
    case 'my-results':
        $quiz->myResults();
        break;

    case 'enroll':
        $course->enroll();
        break;

    case 'drop':
        $course->drop();
        break;

    case 'take-quiz':
        $quiz->takeQuiz();
        break;

    case 'quiz-detail':
        $quiz->quizDetail();
        break;

    // Admin routes
    case 'admin-dashboard':
        $admin->dashboard();
        break;

    case 'admin-users':
        $admin->users();
        break;

    case 'admin-edit-user':
        $admin->editUser();
        break;

    case 'admin-delete-user':
        $admin->deleteUser();
        break;

    case 'admin-courses':
        $admin->courses();
        break;

    case 'admin-create-course':
        $admin->createCourse();
        break;

    case 'admin-edit-course':
        $admin->editCourse();
        break;

    case 'admin-delete-course':
        $admin->deleteCourse();
        break;

    case 'admin-results':
    case 'all-results':
        $admin->allResults();
        break;

    case 'dashboard':
        if (!isLoggedIn()) redirect('?page=login');
        if ($_SESSION['user_type'] === 'Admin') {
            $admin->dashboard();
        } elseif ($_SESSION['user_type'] === 'Instructor') {
            $instructor->dashboard();
        } else {
            $course->dashboard();
        }
        break;

    case 'manage-instructors':
        $admin->manageInstructors();
        break;

    case 'approve-instructor':
        $admin->approveInstructor();
        break;

    case 'reject-instructor':
        $admin->rejectInstructor();
        break;

    // Instructor routes
    case 'instructor-dashboard':
        $instructor->dashboard();
        break;

    case 'create-course':
        if (!isLoggedIn()) redirect('?page=login');
        $instructor->createCourse();
        break;

    case 'manage-courses':
    case 'manage-course':
    case 'my-courses':
        if (!isLoggedIn()) redirect('?page=login');
        $instructor->manageCourses();
        break;

    case 'edit-course':
        if (!isLoggedIn()) redirect('?page=login');
        $instructor->editCourse();
        break;

    case 'delete-course':
        if (!isLoggedIn()) redirect('?page=login');
        $instructor->deleteCourse();
        break;

    case 'create-quiz':
        $instructor->createQuiz();
        break;

    case 'manage-quiz':
        $instructor->manageQuiz();
        break;
    case 'delete-quiz':
        $instructor->deleteQuiz();
        break;
    case 'delete-question':
        $instructor->deleteQuestion();
        break;
    case 'grade-short-answer':
        $instructor->gradeShortAnswer();
        break;

    case 'add-question':
        $instructor->addQuestion();
        break;

    case 'upload-content':
        $instructor->uploadContent();
        break;

    case 'manage-content':
        $instructor->manageContent();
        break;

    case 'edit-content':
        $instructor->editContent();
        break;

    case 'delete-content':
        $instructor->deleteContent();
        break;

    case 'course-results':
    case 'student-results':
        if (!isLoggedIn()) redirect('?page=login');
        $instructor->courseResults();
        break;

    case 'course-quizzes':
        $instructor->quizzesByCourse();
        break;

    case 'messages':
        if (!isLoggedIn()) redirect('?page=login');
        $message->chat();
        break;

    case 'api-get-messages':
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        $message->getMessages();
        break;

    case 'api-send-message':
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        $message->sendMessage();
        break;

    case 'send-feedback':
        $feedback->sendStudentFeedback();
        break;

    default:
        // Route based on user type
        if (isLoggedIn() && isset($_SESSION['user_type'])) {
            if ($_SESSION['user_type'] === 'Admin') {
                $admin->dashboard();
            } elseif ($_SESSION['user_type'] === 'Instructor') {
                $instructor->dashboard();
            } else {
                $course->dashboard();
            }
        } else {
            redirect('?page=login');
        }
}
