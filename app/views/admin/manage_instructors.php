<?php
// app/views/admin/manage_instructors.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Instructor Status</h2>
            <p class="text-muted">Review instructor registrations and manage active or inactive access</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-alert="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-alert="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="btn-group" role="group">
                        <a href="?page=manage-instructors&status=" class="btn <?php echo (($_GET['status'] ?? null) === '') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            All
                        </a>
                        <a href="?page=manage-instructors&status=Pending" class="btn <?php echo (($_GET['status'] ?? 'Pending') === 'Pending') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Pending Approval
                        </a>
                        <a href="?page=manage-instructors&status=Approved" class="btn <?php echo (($_GET['status'] ?? '') === 'Approved') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Active
                        </a>
                        <a href="?page=manage-instructors&status=Rejected" class="btn <?php echo (($_GET['status'] ?? '') === 'Rejected') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Inactive
                        </a>
                    </div>
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
                            <th>Status</th>
                            <th>Registered Date</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($instructors)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <p class="text-muted mb-0">No instructors found for this status.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($instructors as $instructor): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($instructor['Username']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($instructor['Email']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($instructor['Status']) {
                                                'Approved' => 'bg-success',
                                                'Pending' => 'bg-warning text-dark',
                                                'Rejected' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                            <?php
                                                echo match($instructor['Status']) {
                                                    'Approved' => 'Active',
                                                    'Rejected' => 'Inactive',
                                                    default => $instructor['Status']
                                                };
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($instructor['CreatedAt'])); ?></td>
                                    <td class="pe-4 text-end">
                                        <?php if ($instructor['Status'] === 'Pending'): ?>
                                            <a href="?page=approve-instructor&id=<?php echo $instructor['UserID']; ?>" class="btn btn-sm btn-success me-1" onclick="return confirm('Approve this instructor?')">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </a>
                                            <a href="?page=reject-instructor&id=<?php echo $instructor['UserID']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this instructor?')">
                                                <i class="bi bi-x-lg"></i> Reject
                                            </a>
                                        <?php elseif ($instructor['Status'] === 'Approved'): ?>
                                            <a href="?page=reject-instructor&id=<?php echo $instructor['UserID']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Mark this instructor inactive?')">
                                                <i class="bi bi-x-lg"></i> Deactivate
                                            </a>
                                        <?php elseif ($instructor['Status'] === 'Rejected'): ?>
                                            <a href="?page=approve-instructor&id=<?php echo $instructor['UserID']; ?>" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-lg"></i> Activate
                                            </a>
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
