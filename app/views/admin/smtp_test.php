<?php
// app/views/admin/smtp_test.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';

$smtpConfigured = (defined('SMTP_PASSWORD') && SMTP_PASSWORD !== '' && SMTP_PASSWORD !== 'GOOGLE_APP_PASSWORD_HERE');
?>

<div class="container-fluid p-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="bi bi-envelope-check text-primary me-2"></i>SMTP & Email Diagnostics
            </h3>
            <p class="text-muted small mb-0">Verify your mail server connection, test delivery, and debug handshake responses.</p>
        </div>
        <a href="?page=dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- Test Result Alert (if run) -->
    <?php if ($result !== null): ?>
        <?php if ($result['success']): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm p-4 mb-4" role="alert">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                    <div>
                        <h5 class="alert-heading fw-bold mb-1">SMTP Connection Successful!</h5>
                        <p class="mb-0">
                            A test email was successfully accepted by <strong><?php echo htmlspecialchars(SMTP_HOST ?: 'smtp.gmail.com'); ?></strong> 
                            and sent to <strong><?php echo htmlspecialchars($result['email']); ?></strong>.
                            Check your inbox (and spam/junk folder) to confirm receipt.
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php else: ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm p-4 mb-4" role="alert">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-exclamation-triangle-fill fs-3 text-danger"></i>
                    <div class="w-100">
                        <h5 class="alert-heading fw-bold mb-1">SMTP Delivery Failed</h5>
                        <p class="mb-2">
                            The server could not deliver the test email to <strong><?php echo htmlspecialchars($result['email']); ?></strong>.
                            Review the handshake transcript below for the exact error code.
                        </p>
                        <?php if (!empty($result['debug'])): ?>
                            <div class="p-3 bg-dark text-white rounded font-monospace small" style="max-height: 250px; overflow-y: auto;">
                                <pre class="mb-0 text-white" style="white-space: pre-wrap; font-size: 0.85rem;"><?php echo htmlspecialchars($result['debug']); ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Configuration Card -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold mb-0">Active Configuration</h5>
                    <?php if ($smtpConfigured): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i> Credentials Detected
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> Missing SMTP_PASSWORD
                        </span>
                    <?php endif; ?>
                </div>

                <div class="card-body p-4">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted ps-0" style="width: 35%;">SMTP Host:</th>
                                <td class="fw-bold font-monospace"><?php echo htmlspecialchars(SMTP_HOST ?: 'smtp.gmail.com (default)'); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted ps-0">SMTP Port:</th>
                                <td class="fw-bold font-monospace"><?php echo (int)(SMTP_PORT ?: 587); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted ps-0">Encryption:</th>
                                <td class="fw-bold font-monospace text-uppercase"><?php echo htmlspecialchars(SMTP_SECURE ?: 'tls'); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted ps-0">Username / Account:</th>
                                <td class="fw-bold font-monospace"><?php echo htmlspecialchars(SMTP_USERNAME ?: 'univelearning01@gmail.com'); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted ps-0">Password Status:</th>
                                <td>
                                    <?php if ($smtpConfigured): ?>
                                        <span class="text-success fw-bold font-monospace">•••••••••••••••• (Active)</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold">Not configured</span>
                                        <small class="text-muted d-block mt-1">Set <code>SMTP_PASSWORD</code> in Vercel</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted ps-0">Mail From:</th>
                                <td class="fw-bold"><?php echo htmlspecialchars(MAIL_FROM ?: 'univelearning01@gmail.com'); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted ps-0">Mail From Name:</th>
                                <td class="fw-bold"><?php echo htmlspecialchars(MAIL_FROM_NAME ?: APP_NAME); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted ps-0">Mailer Driver:</th>
                                <td>
                                    <span class="badge bg-primary">PHPMailer (Bundled)</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Live Tester Form -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0">Send Test Email</h5>
                </div>

                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Enter any accessible email address below to perform a live handshake and deliver a test message.
                    </p>

                    <form method="POST" action="?page=admin-smtp-test">
                        <div class="mb-3">
                            <label for="test_email" class="form-label fw-bold">Recipient Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="test_email" name="test_email" placeholder="e.g. your-email@gmail.com" required value="<?php echo htmlspecialchars($_POST['test_email'] ?? $_SESSION['email'] ?? ''); ?>">
                            <small class="text-muted">A test message will be sent to this inbox to verify deliverability.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold">
                                <i class="bi bi-send me-1"></i> Send Test Email Now
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-2">Setup Checklist:</h6>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2 d-flex align-items-start gap-2">
                            <i class="bi bi-shield-lock text-primary mt-1"></i>
                            <div><strong>Gmail 2-Step Verification:</strong> Must be turned ON for the sender account.</div>
                        </li>
                        <li class="mb-2 d-flex align-items-start gap-2">
                            <i class="bi bi-key text-primary mt-1"></i>
                            <div><strong>16-Character App Password:</strong> Generate at <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">myaccount.google.com/apppasswords</a>.</div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-cloud text-primary mt-1"></i>
                            <div><strong>Vercel Settings:</strong> Add <code>SMTP_PASSWORD</code> under Project Settings &rarr; Environment Variables.</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
