<?php
$page_title = 'Delete My Account';
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <span class="badge bg-danger mb-3">Permanent Action</span>
                    <h2 class="fw-bold mb-3">Delete My Account</h2>
                    <p class="text-muted">This will remove your account and related personal activity where possible. Course administration or audit records may remain where required.</p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <strong>Before you continue:</strong> this action cannot be undone. You will be signed out immediately after deletion.
                    </div>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <label for="deleteConfirm" class="form-label fw-semibold">Type DELETE to confirm</label>
                            <input type="text" class="form-control" id="deleteConfirm" name="confirmation" required pattern="DELETE" autocomplete="off">
                            <div class="invalid-feedback">Type DELETE exactly to continue.</div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="?page=dashboard" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash me-1"></i> Delete My Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
