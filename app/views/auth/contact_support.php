<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLanguage(), ENT_QUOTES, 'UTF-8'); ?>"<?php echo getCurrentLanguage() === 'ar' ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - <?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?></title>
    <link rel="icon" type="image/png" href="/images/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/images/icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="/css/style.css?v=14">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#c5a059">
</head>
<body class="bg-light">
    <?php
        $currentName = $name ?? ($prefill['Username'] ?? '');
        $currentEmail = $email ?? ($prefill['Email'] ?? '');
        $currentTopic = $topic ?? 'General support';
        $currentMessage = $message ?? '';
        $backUrl = isLoggedIn() ? '?page=dashboard' : '?page=login';
    ?>
    <div class="auth-wrapper">
        <div class="row w-100 m-0">
            <div class="col-lg-6 d-none d-lg-flex auth-bg align-items-center justify-content-center flex-column text-white p-5 animate__animated animate__fadeIn">
                <div style="z-index: 1;" class="text-center">
                    <img src="/images/books.png" alt="Support" class="img-fluid mb-5" style="max-height: 350px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));">
                    <h1 class="display-5 fw-bold mb-3"><?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?></h1>
                    <p class="lead fw-normal text-white-50">Get help with sign-in, courses, results, or account access.</p>
                </div>
            </div>

            <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5 bg-white shadow-lg animate__animated animate__fadeInRight">
                <div class="w-100" style="max-width: 500px;">
                    <div class="mb-4">
                        <a href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none text-muted">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </a>
                    </div>

                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-soft-primary text-primary rounded-circle mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-headset fs-1"></i>
                        </div>
                        <h2 class="fw-bold text-dark">Contact Support</h2>
                        <p class="text-muted mb-0">
                            Email us directly at
                            <a href="mailto:<?php echo htmlspecialchars(SUPPORT_EMAIL, ENT_QUOTES, 'UTF-8'); ?>" class="text-primary fw-semibold text-decoration-none">
                                <?php echo htmlspecialchars(SUPPORT_EMAIL, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $supportError): ?>
                            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                                <div><?php echo htmlspecialchars($supportError, ENT_QUOTES, 'UTF-8'); ?></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill flex-shrink-0 me-2"></i>
                            <div><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="?page=contact-support" class="needs-validation" novalidate>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="name" class="form-control" id="supportName" placeholder="Your name" value="<?php echo htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <label for="supportName"><i class="bi bi-person me-2 text-muted"></i>Name</label>
                                    <div class="invalid-feedback">Name is required.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" name="email" class="form-control" id="supportEmail" placeholder="name@example.com" value="<?php echo htmlspecialchars($currentEmail, ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <label for="supportEmail"><i class="bi bi-envelope me-2 text-muted"></i>Email</label>
                                    <div class="invalid-feedback">A valid email is required.</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <select name="topic" class="form-select" id="supportTopic">
                                <?php foreach (['General support', 'Login or verification', 'Course access', 'Results or quizzes', 'Instructor account', 'Technical issue'] as $topicOption): ?>
                                    <option value="<?php echo htmlspecialchars($topicOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $currentTopic === $topicOption ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($topicOption, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="supportTopic"><i class="bi bi-life-preserver me-2 text-muted"></i>Topic</label>
                        </div>

                        <div class="form-floating mb-4">
                            <textarea name="message" class="form-control" id="supportMessage" placeholder="How can we help?" style="height: 150px;" minlength="10" maxlength="3000" required><?php echo htmlspecialchars($currentMessage, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <label for="supportMessage"><i class="bi bi-chat-left-text me-2 text-muted"></i>How can we help?</label>
                            <div class="invalid-feedback">Please describe the issue in at least 10 characters.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm rounded-3 fs-5">
                            <i class="bi bi-send me-2" aria-hidden="true"></i>Send Support Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/main.js?v=14"></script>
</body>
</html>
