<?php
// app/views/partials/sidebar_v2.php
$user_type = $_SESSION['user_type'] ?? 'Student';
$current_page = $_GET['page'] ?? '';

// If no page is set but we are on the default route, consider it dashboard
if (empty($current_page)) {
    $current_page = 'dashboard';
}
?>
<!-- Sidebar -->
<div id="wrapper" class="d-flex">
    <div class="bg-dark text-white border-end" id="sidebar-wrapper" style="min-width: 250px; min-height: 100vh;">
        <div class="sidebar-heading border-bottom p-3 fs-5 fw-bold text-center text-primary">
            <img src="public/images/icons/icon-192.png" alt="UnivLearn" class="sidebar-logo me-2" width="32" height="32" style="width:32px;height:32px;max-width:32px;max-height:32px;object-fit:cover;">
            <span class="sidebar-brand-text">Univ<span class="text-white">Learn</span></span>
            <button type="button" class="btn btn-sm btn-outline-light sidebar-close-btn d-lg-none" id="sidebar-close" aria-label="Close navigation">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    <div class="list-group list-group-flush p-3">
        <a href="?page=dashboard" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo (in_array($current_page, ['dashboard', 'instructor-dashboard', 'admin-dashboard'])) ? 'active bg-primary' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        
        <?php if ($user_type === 'Admin'): ?>
            <a href="?page=admin-users" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo ($current_page == 'admin-users') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-people me-2"></i> Manage Users
            </a>
            <a href="?page=manage-instructors" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo ($current_page == 'manage-instructors') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-person-check me-2"></i> Instructor Approvals
            </a>
            <a href="?page=admin-courses" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo ($current_page == 'admin-courses') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-book me-2"></i> Manage Courses
            </a>
        <?php elseif ($user_type === 'Instructor'): ?>
            <a href="?page=manage-courses" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo ($current_page == 'manage-courses') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-journal-text me-2"></i> My Courses
            </a>
            <a href="?page=student-results" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo ($current_page == 'student-results') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-award me-2"></i> Student Results
            </a>
            <a href="?page=messages" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo ($current_page == 'messages') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-chat-dots me-2"></i> Messages
            </a>
        <?php elseif ($user_type === 'Student'): ?>
            <a href="?page=courses" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo ($current_page == 'courses') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-search me-2"></i> Browse Courses
            </a>
            <a href="?page=my-enrollments" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo ($current_page == 'my-enrollments') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-mortarboard me-2"></i> My Learning
            </a>
            <a href="?page=my-results" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo ($current_page == 'my-results') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-card-checklist me-2"></i> My Results
            </a>
            <a href="?page=messages" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo ($current_page == 'messages') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-chat-dots me-2"></i> Messages
            </a>
        <?php endif; ?>

        <hr class="bg-light">
        <a href="?page=logout" class="list-group-item list-group-item-action bg-dark text-danger border-0 py-3 rounded">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>
<!-- /#sidebar-wrapper -->

<!-- Page Content -->
<div id="page-content-wrapper" class="w-100">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom p-3">
        <div class="container-fluid">
                <button class="btn btn-outline-secondary btn-sm me-3" id="menu-toggle" aria-label="Toggle navigation" aria-controls="sidebar-wrapper" aria-expanded="false">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-text fw-medium">
                    Welcome back, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></span> (<?php echo $_SESSION['user_type'] ?? 'Student'; ?>)
                </span>
            <div class="ms-auto">
                <span class="text-muted small"><?php echo date('D, M j, Y'); ?></span>
            </div>
        </div>
    </nav>
    <div class="container-fluid p-4">
