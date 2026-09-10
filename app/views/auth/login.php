<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLanguage(), ENT_QUOTES, 'UTF-8'); ?>"<?php echo getCurrentLanguage() === 'ar' ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?></title>
    <link rel="icon" type="image/png" href="/images/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/images/icons/icon-192.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css?v=14">
    <!-- PWA Support -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#c5a059">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }
    </script>
</head>
<body class="bg-light">
    <?php
        $languageNames = [
            'en' => 'English',
            'es' => 'Espanol',
            'fr' => 'Francais',
            'de' => 'Deutsch',
            'ar' => 'Arabic',
            'sw' => 'Swahili',
        ];
    ?>
    <div class="auth-wrapper">
        <div class="row w-100 m-0">
            <!-- Left Side: Image/Branding -->
            <div class="col-lg-6 d-none d-lg-flex auth-bg align-items-center justify-content-center flex-column text-white p-5 animate__animated animate__fadeIn">
                <div style="z-index: 1;" class="text-center">
                    <img src="/images/books.png" alt="E-Learning" class="img-fluid mb-5" style="max-height: 350px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));">
                    <h1 class="display-5 fw-bold mb-3"><?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?></h1>
                    <p class="lead fw-normal text-white-50">Master new skills. Elevate your career.<br>Join thousands of learners worldwide.</p>
                </div>
            </div>
            
            <!-- Right Side: Form -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5 bg-white shadow-lg animate__animated animate__fadeInRight">
                <div class="w-100" style="max-width: 450px;">
                    <form method="GET" action="index.php" class="d-flex justify-content-end mb-3">
                        <input type="hidden" name="page" value="set-language">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php?page=login', ENT_QUOTES, 'UTF-8'); ?>">
                        <select name="lang" class="form-select form-select-sm" style="max-width: 150px;" aria-label="Language" onchange="this.form.submit()">
                            <?php foreach (AVAILABLE_LANGUAGES as $languageCode): ?>
                                <option value="<?php echo htmlspecialchars($languageCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo getCurrentLanguage() === $languageCode ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($languageNames[$languageCode] ?? strtoupper($languageCode), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <div class="text-center mb-5">
                        <div class="d-inline-flex align-items-center justify-content-center bg-soft-primary text-primary rounded-circle mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-person-circle fs-1"></i>
                        </div>
                        <h2 class="fw-bold text-dark"><?php echo __('login_title'); ?></h2>
                        <p class="text-muted"><?php echo __('login_subtitle'); ?></p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center animate__animated animate__headShake" role="alert">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                            <div>
                                <?php echo htmlspecialchars($error); ?>
                                <?php if (!empty($verification_email)): ?>
                                    <div class="mt-2">
                                        <a class="alert-link" href="?page=verify-email&email=<?php echo urlencode($verification_email); ?>">Enter or resend verification OTP</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill flex-shrink-0 me-2"></i>
                            <div><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="form-floating mb-4">
                            <input type="text" name="email" class="form-control" id="emailInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($remembered_email ?? ''); ?>" required>
                            <label for="emailInput"><i class="bi bi-envelope me-2 text-muted"></i><?php echo __('email_username'); ?></label>
                            <div class="invalid-feedback">Please provide a valid email or username.</div>
                        </div>
                        
                        <div class="form-floating mb-4 position-relative">
                            <input type="password" name="password" class="form-control pe-5" id="passwordInput" placeholder="Password" required>
                            <label for="passwordInput"><i class="bi bi-lock me-2 text-muted"></i><?php echo __('password'); ?></label>
                            <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-2 p-2 text-muted" id="togglePassword" aria-label="Show password" aria-pressed="false" style="z-index: 5;">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                            <div class="invalid-feedback">Password is required.</div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me" value="1" <?php echo !empty($remembered_email) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-muted small" for="rememberMe"><?php echo __('remember_me'); ?></label>
                            </div>
                            <a href="?page=forgot-password" class="small text-primary fw-semibold text-decoration-none"><?php echo __('forgot_password'); ?></a>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 mb-4 fw-bold shadow-sm rounded-3 fs-5 position-relative overflow-hidden group">
                            <span class="position-relative z-1"><?php echo __('sign_in'); ?></span>
                        </button>

                        <div class="text-center text-muted border-top pt-4">
                            <?php echo __('dont_have_account'); ?> <a href="?page=register" class="text-primary fw-bold text-decoration-none"><?php echo __('register_here'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/main.js?v=14"></script>
</body>
</html>
