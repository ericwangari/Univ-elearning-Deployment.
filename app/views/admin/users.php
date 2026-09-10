<?php
// app/views/admin/users.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">User Management</h2>
            <p class="text-muted">Manage system users and their roles</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-8">
                    <form method="GET" class="input-group">
                        <input type="hidden" name="page" value="admin-users">
                        <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <?php if (!empty($type)): ?>
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                        <?php endif; ?>
                        <?php if (!empty($sort)): ?>
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                        <?php endif; ?>
                        <?php if (!empty($direction)): ?>
                            <input type="hidden" name="direction" value="<?php echo htmlspecialchars($direction); ?>">
                        <?php endif; ?>
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </form>
                </div>
                <div class="col-md-4">
                    <select class="form-select" onchange="window.location='?page=admin-users<?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?><?php echo !empty($direction) ? '&direction=' . urlencode($direction) : ''; ?>&type=' + encodeURIComponent(this.value)">
                        <option value="">All User Types</option>
                        <option value="Admin" <?php echo (($_GET['type'] ?? '') === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="Instructor" <?php echo (($_GET['type'] ?? '') === 'Instructor') ? 'selected' : ''; ?>>Instructor</option>
                        <option value="Student" <?php echo (($_GET['type'] ?? '') === 'Student') ? 'selected' : ''; ?>>Student</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Username</th>
                            <th>Email</th>
                            <?php
                                $roleDirection = (($sort ?? '') === 'role' && ($direction ?? 'desc') === 'asc') ? 'desc' : 'asc';
                                $roleSortParams = [
                                    'page' => 'admin-users',
                                    'sort' => 'role',
                                    'direction' => $roleDirection,
                                ];
                                if (!empty($search)) $roleSortParams['search'] = $search;
                                if (!empty($type)) $roleSortParams['type'] = $type;
                                $roleSortUrl = '?' . http_build_query($roleSortParams);
                            ?>
                            <th>
                                <a href="<?php echo htmlspecialchars($roleSortUrl); ?>" class="text-reset text-decoration-none d-inline-flex align-items-center gap-1" aria-label="Sort by role <?php echo $roleDirection === 'asc' ? 'ascending' : 'descending'; ?>">
                                    Role
                                    <i class="bi <?php echo (($sort ?? '') === 'role' && ($direction ?? '') === 'asc') ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up-alt'; ?>" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th>Joined Date</th>
                            <th class="pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <p class="text-muted mb-0">No users found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($user['Username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($user['UserType']) {
                                                'Admin' => 'bg-danger',
                                                'Instructor' => 'bg-primary',
                                                'Student' => 'bg-secondary',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                            <?php echo $user['UserType']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['CreatedAt'])); ?></td>
                                    <td class="pe-4">
                                        <a href="?page=admin-edit-user&id=<?php echo $user['UserID']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php if ($user['UserID'] != $_SESSION['user_id']): ?>
                                            <form method="POST" action="?page=admin-delete-user" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
