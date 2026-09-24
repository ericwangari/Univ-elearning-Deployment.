<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLanguage(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Agreement - <?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?></title>
    <link rel="icon" type="image/png" href="/images/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/images/icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/style.css?v=14">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="mx-auto" style="max-width: 900px;">
            <div class="mb-4">
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <span class="badge bg-soft-primary text-primary mb-3">User Agreement</span>
                    <h1 class="fw-bold mb-3"><?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?> Terms of Use</h1>
                    <p class="text-muted">Last updated: <?php echo date('F j, Y'); ?></p>

                    <p>By creating an account or using this platform, you agree to use it responsibly for learning, teaching, assessment, and course communication.</p>

                    <h5 class="fw-bold mt-4">Acceptable Use</h5>
                    <ul>
                        <li>Use your own account and keep your login details private.</li>
                        <li>Submit your own work and avoid cheating, impersonation, or unauthorized collaboration on assessments.</li>
                        <li>Use messages, feedback, and support forms respectfully.</li>
                        <li>Do not upload harmful files, malicious links, spam, abusive content, or content you do not have permission to share.</li>
                        <li>Do not attempt to bypass security controls, access another user account, disrupt the service, or misuse platform data.</li>
                    </ul>

                    <h5 class="fw-bold mt-4">Learning Content And AI Tools</h5>
                    <p>Course content, AI Teacher responses, feedback, and generated study materials are provided to support learning. AI-generated content may be incomplete or inaccurate, so students and instructors should review important information before relying on it.</p>

                    <h5 class="fw-bold mt-4">Account Status</h5>
                    <p>Student accounts may be approved after email verification. Instructor accounts may require administrator approval before full access is granted. Accounts may be restricted, rejected, or deleted if they are misused.</p>

                    <h5 class="fw-bold mt-4">Privacy And Communication</h5>
                    <p>The platform stores account details, course activity, quiz results, messages, support requests, and related learning records needed to operate the service. Support and verification emails may be sent to the address attached to your account.</p>

                    <h5 class="fw-bold mt-4">Account Deletion</h5>
                    <p>Users may request account deletion from inside the platform. Deletion removes the account and related personal activity where possible, but some records may remain if needed for security, audit, course administration, or legal compliance.</p>

                    <h5 class="fw-bold mt-4">Changes</h5>
                    <p>These terms may be updated as the platform changes. Continued use of the platform after updates means you accept the updated terms.</p>

                    <div class="alert alert-light border mt-4 mb-0">
                        For support or account questions, contact
                        <a href="mailto:<?php echo htmlspecialchars(SUPPORT_EMAIL, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars(SUPPORT_EMAIL, ENT_QUOTES, 'UTF-8'); ?>
                        </a>.
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
