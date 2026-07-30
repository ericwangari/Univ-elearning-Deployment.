<?php
require_once 'config/config.php';
require_once 'app/controllers/AuthController.php';
require_once 'app/controllers/CourseController.php';
require_once 'app/controllers/QuizController.php';
require_once 'app/controllers/AdminController.php';
require_once 'app/controllers/InstructorController.php';
require_once 'app/controllers/MessageController.php';
require_once 'app/controllers/FeedbackController.php';

// Route handling. Opening the app should always start at login unless a page is requested.
$page = $_GET['page'] ?? 'login';

// Controller Initialization
$auth = new AuthController($pdo);
$courseCtrl = new CourseController($pdo);
$quizCtrl = new QuizController($pdo);
$adminCtrl = new AdminController($pdo);
$instructorCtrl = new InstructorController($pdo);
$messageCtrl = new MessageController($pdo);
$feedbackCtrl = new FeedbackController($pdo);

// Simple Router
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

    case 'dashboard':
        if (!isLoggedIn()) redirect('?page=login');
        
        $user_type = $_SESSION['user_type'];
        if ($user_type === 'Admin') {
            $adminCtrl->dashboard();
        } elseif ($user_type === 'Instructor') {
            $instructorCtrl->dashboard();
        } else {
            $courseCtrl->dashboard();
        }
        break;

    case 'courses':
        if (!isLoggedIn()) redirect('?page=login');
        $courseCtrl->courses();
        break;

    case 'course-details':
        if (!isLoggedIn()) redirect('?page=login');
        $courseId = $_GET['id'] ?? null;
        if ($courseId) $courseCtrl->courseDetails($courseId);
        break;

    case 'enroll':
        if (!isLoggedIn()) redirect('?page=login');
        $courseCtrl->enroll();
        break;

    case 'drop':
        if (!isLoggedIn()) redirect('?page=login');
        $courseCtrl->drop();
        break;

    case 'my-enrollments':
        if (!isLoggedIn()) redirect('?page=login');
        $courseCtrl->myEnrollments();
        break;

    case 'manage-courses':
    case 'manage-course':
    case 'my-courses': // Alias for instructor
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->manageCourses();
        break;

    case 'create-course':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->createCourse();
        break;

    case 'take-quiz':
        if (!isLoggedIn()) redirect('?page=login');
        $quizId = $_GET['id'] ?? null;
        if ($quizId) $quizCtrl->takeQuiz($quizId);
        break;

    case 'my-results':
        if (!isLoggedIn()) redirect('?page=login');
        $quizCtrl->myResults();
        break;

    case 'all-results':
        if (!isLoggedIn()) redirect('?page=login');
        $adminCtrl->allResults();
        break;

    case 'admin-users':
    case 'manage-users': // Alias
        if (!isLoggedIn()) redirect('?page=login');
        $adminCtrl->users();
        break;

    case 'admin-edit-user':
        if (!isLoggedIn()) redirect('?page=login');
        $adminCtrl->editUser();
        break;

    case 'admin-delete-user':
        if (!isLoggedIn()) redirect('?page=login');
        $adminCtrl->deleteUser();
        break;

    case 'admin-courses':
        if (!isLoggedIn()) redirect('?page=login');
        $adminCtrl->courses();
        break;

    case 'admin-create-course':
        if (!isLoggedIn()) redirect('?page=login');
        $adminCtrl->createCourse();
        break;

    case 'admin-edit-course':
        if (!isLoggedIn()) redirect('?page=login');
        $adminCtrl->editCourse();
        break;

    case 'admin-delete-course':
        if (!isLoggedIn()) redirect('?page=login');
        $adminCtrl->deleteCourse();
        break;

    case 'manage-instructors':
        if (!isLoggedIn()) redirect('?page=login');
        $adminCtrl->manageInstructors();
        break;

    case 'approve-instructor':
        if (!isLoggedIn()) redirect('?page=login');
        $adminCtrl->approveInstructor();
        break;

    case 'reject-instructor':
        if (!isLoggedIn()) redirect('?page=login');
        $adminCtrl->rejectInstructor();
        break;

    case 'course-results':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->courseResults();
        break;

    case 'course-quizzes':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->quizzesByCourse();
        break;

    case 'student-results':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->courseResults(); // Instructors should see their own course results
        break;

    case 'create-quiz':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->createQuiz();
        break;

    case 'manage-quiz':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->manageQuiz();
        break;

    case 'delete-quiz':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->deleteQuiz();
        break;

    case 'upload-content':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->uploadContent();
        break;

    case 'manage-content':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->manageContent();
        break;

    case 'edit-content':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->editContent();
        break;

    case 'delete-content':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->deleteContent();
        break;

    case 'edit-course':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->editCourse();
        break;

    case 'delete-course':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->deleteCourse();
        break;

    case 'add-question':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->addQuestion();
        break;

    case 'delete-question':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->deleteQuestion();
        break;

    case 'grade-short-answer':
        if (!isLoggedIn()) redirect('?page=login');
        $instructorCtrl->gradeShortAnswer();
        break;

    case 'quiz-detail':
        if (!isLoggedIn()) redirect('?page=login');
        $quizCtrl->quizDetail();
        break;

    case 'messages':
        if (!isLoggedIn()) redirect('?page=login');
        $messageCtrl->chat();
        break;

    case 'api-get-messages':
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        $messageCtrl->getMessages();
        break;

    case 'api-send-message':
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        $messageCtrl->sendMessage();
        break;

    case 'send-feedback':
        $feedbackCtrl->sendStudentFeedback();
        break;

    default:
        // Handle 404
        echo "404 Page Not Found";
        break;
}
?>
