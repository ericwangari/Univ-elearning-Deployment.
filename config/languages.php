<?php
/**
 * Language Configuration for Univ E-Learning
 * Provides internationalization support for multiple languages
 */

// Current language (can be set from session, cookies, or config)
$current_language = $_SESSION['language'] ?? $_COOKIE['language'] ?? 'en';

// Define available languages
define('AVAILABLE_LANGUAGES', ['en', 'es', 'fr', 'de', 'ar', 'sw']);

// Language auto-detection based on Accept-Language header
if (!isset($_SESSION['language']) && !isset($_COOKIE['language'])) {
    $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $lang_parts = explode(',', $accept_language);
    $client_lang = substr($lang_parts[0], 0, 2);
    if (in_array($client_lang, AVAILABLE_LANGUAGES)) {
        $current_language = $client_lang;
    }
}

// Language strings
$lang = [
    'en' => [
        // Navigation
        'nav_dashboard' => 'Dashboard',
        'nav_courses' => 'Courses',
        'nav_messages' => 'Messages',
        'nav_results' => 'Results',
        'nav_admin' => 'Admin Panel',
        'nav_profile' => 'Profile',
        'nav_logout' => 'Logout',
        
        // Common
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'add' => 'Add',
        'submit' => 'Submit',
        'back' => 'Back',
        'search' => 'Search',
        'loading' => 'Loading...',
        'error' => 'Error',
        'success' => 'Success',
        'warning' => 'Warning',
        'info' => 'Information',
        
        // Login/Auth
        'login_title' => 'Welcome Back',
        'login_subtitle' => 'Sign in to continue your learning journey.',
        'email_username' => 'Email Address or Username',
        'password' => 'Password',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot password?',
        'sign_in' => 'Sign In',
        'register_here' => 'Register here',
        'dont_have_account' => "Don't have an account?",
        'sign_up' => 'Sign Up',
        'have_account' => 'Already have an account?',
        'verify_email' => 'Verify Email',
        'enter_otp' => 'Enter or resend verification OTP',
        
        // Courses
        'course_catalog' => 'Course Catalog',
        'explore_courses' => 'Explore our collection of world-class learning content.',
        'no_courses_found' => 'No courses found matching your criteria.',
        'enroll' => 'Enroll',
        'enrolled' => 'Enrolled',
        'continue_learning' => 'Continue Learning',
        'course_details' => 'Course Details',
        'instructor' => 'Instructor',
        'students' => 'Students',
        'total_quizzes' => 'Total Quizzes',
        
        // Messaging
        'messages' => 'Messages',
        'your_messages' => 'Your Messages',
        'type_message' => 'Type a message...',
        'send' => 'Send',
        'message_sent' => 'Message sent',
        'message_failed' => 'Failed to send message',
        'no_messages' => 'No messages yet. Say hello to start the conversation!',
        
        // User Management
        'user_management' => 'User Management',
        'all_users' => 'All Users',
        'username' => 'Username',
        'email' => 'Email',
        'role' => 'Role',
        'joined_date' => 'Joined Date',
        'actions' => 'Actions',
        'admin' => 'Admin',
        'instructor' => 'Instructor',
        'student' => 'Student',
        'all_user_types' => 'All User Types',
        
        // Results
        'results' => 'Results',
        'student_results' => 'Student Results',
        'quiz_results' => 'Quiz Results',
        'score' => 'Score',
        'failed_attempts' => 'Failed Attempts',
        'no_results_yet' => 'No results recorded yet for your courses.',
        
        // Time
        'just_now' => 'Just now',
        'minutes_ago' => 'minutes ago',
        'hours_ago' => 'hours ago',
        'days_ago' => 'days ago',
    ],
    'es' => [
        'nav_dashboard' => 'Panel de Control',
        'nav_courses' => 'Cursos',
        'nav_messages' => 'Mensajes',
        'login_title' => 'Bienvenido de vuelta',
        'sign_in' => 'Iniciar sesión',
        // Add more Spanish translations as needed
    ],
    'fr' => [
        'nav_dashboard' => 'Tableau de bord',
        'nav_courses' => 'Cours',
        'nav_messages' => 'Messages',
        'login_title' => 'Bienvenue',
        'sign_in' => 'Se connecter',
        // Add more French translations as needed
    ],
    'de' => [
        'nav_dashboard' => 'Dashboard',
        'nav_courses' => 'Kurse',
        'nav_messages' => 'Nachrichten',
        'login_title' => 'Willkommen zurück',
        'sign_in' => 'Anmelden',
        // Add more German translations as needed
    ],
    'ar' => [
        'nav_dashboard' => 'لوحة التحكم',
        'nav_courses' => 'الدورات',
        'nav_messages' => 'الرسائل',
        'login_title' => 'أهلا وسهلا',
        'sign_in' => 'تسجيل الدخول',
        // Add more Arabic translations as needed
    ],
    'sw' => [
        'nav_dashboard' => 'Dashibohdi',
        'nav_courses' => 'Kozi',
        'nav_messages' => 'Ujumbe',
        'login_title' => 'Karibu tena',
        'sign_in' => 'Ingia',
        // Add more Swahili translations as needed
    ],
];

/**
 * Get a translated string
 * @param string $key The language key
 * @param string $language The language code (optional)
 * @return string The translated string or the key if not found
 */
function __($key, $language = null) {
    global $lang, $current_language;
    
    $lang_to_use = $language ?? $current_language;
    
    // Return translation or fall back to English
    if (isset($lang[$lang_to_use][$key])) {
        return $lang[$lang_to_use][$key];
    } elseif (isset($lang['en'][$key])) {
        return $lang['en'][$key];
    } else {
        return $key; // Return the key if no translation found
    }
}

/**
 * Set the current language
 * @param string $language The language code
 */
function setLanguage($language) {
    global $current_language;
    if (in_array($language, AVAILABLE_LANGUAGES)) {
        $current_language = $language;
        $_SESSION['language'] = $language;
        setcookie('language', $language, time() + (365 * 24 * 60 * 60), '/');
    }
}

/**
 * Get the current language
 * @return string The current language code
 */
function getCurrentLanguage() {
    global $current_language;
    return $current_language;
}
