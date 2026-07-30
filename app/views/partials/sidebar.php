<?php
// app/views/partials/sidebar.php
$user_type = $_SESSION['user_type'] ?? 'Student';
?>
<!-- Sidebar -->
<div class="bg-dark text-white border-end" id="sidebar-wrapper" style="min-width: 250px; min-height: 100vh;">
    <div class="sidebar-heading border-bottom p-4 fs-4 fw-bold text-center text-primary">
        Univ<span class="text-white">Learn</span>
    </div>
    <div class="list-group list-group-flush p-3">
        <a href="?page=dashboard" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo (in_array($_GET['page'] ?? 'dashboard', ['dashboard', 'instructor-dashboard', 'admin-dashboard'])) ? 'active bg-primary' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        
        <?php if ($user_type === 'Admin'): ?>
            <a href="?page=admin-users" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo (($_GET['page'] ?? '') == 'admin-users') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-people me-2"></i> Manage Users
            </a>
            <a href="?page=manage-instructors" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo (($_GET['page'] ?? '') == 'manage-instructors') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-person-check me-2"></i> Instructor Approvals
            </a>
            <a href="?page=admin-courses" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo (($_GET['page'] ?? '') == 'admin-courses') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-book me-2"></i> Manage Courses
            </a>
        <?php elseif ($user_type === 'Instructor'): ?>
            <a href="?page=manage-courses" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo (($_GET['page'] ?? '') == 'manage-courses') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-journal-text me-2"></i> My Courses
            </a>
            <a href="?page=student-results" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo (($_GET['page'] ?? '') == 'student-results') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-award me-2"></i> Student Results
            </a>
        <?php elseif ($user_type === 'Student'): ?>
            <a href="?page=courses" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo (($_GET['page'] ?? '') == 'courses') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-search me-2"></i> Browse Courses
            </a>
            <a href="?page=my-enrollments" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo (($_GET['page'] ?? '') == 'my-enrollments') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-mortarboard me-2"></i> My Learning
            </a>
            <a href="?page=my-results" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 rounded mb-1 <?php echo (($_GET['page'] ?? '') == 'my-results') ? 'active bg-primary' : ''; ?>">
                <i class="bi bi-card-checklist me-2"></i> My Results
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
            <span class="navbar-text fw-medium">
                Welcome back, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['username']); ?></span> (<?php echo $_SESSION['user_type']; ?>)
            </span>
            <div class="ms-auto">
                <span class="text-muted small"><?php echo date('l, jS F Y'); ?></span>
            </div>
        </div>
    </nav>
    <div class="container-fluid p-4">
