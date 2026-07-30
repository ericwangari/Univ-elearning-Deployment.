<?php
// app/views/admin/edit_user.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="mb-4">
        <a href="?page=admin-users" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Users
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white p-4">
                    <h4 class="card-title mb-0">Edit User</h4>
                </div>

                <div class="card-body p-4">
                    <?php if (!empty($user) && is_array($user)): ?>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">

                            <div class="mb-3">
                                <label for="username" class="form-label fw-bold">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['Username']); ?>" disabled>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['Email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>

                            <div class="mb-4">
                                <label for="user_type" class="form-label fw-bold">User Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="user_type" name="user_type" required>
                                    <option value="Student" <?php echo ($user['UserType'] === 'Student') ? 'selected' : ''; ?>>Student</option>
                                    <option value="Instructor" <?php echo ($user['UserType'] === 'Instructor') ? 'selected' : ''; ?>>Instructor</option>
                                    <option value="Admin" <?php echo ($user['UserType'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i> Update User
                                </button>
                                <a href="?page=admin-users" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            User not found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
