<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?></title>
    <link rel="icon" type="image/png" href="public/images/icons/icon-192.png">
    <link rel="apple-touch-icon" href="public/images/icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#c5a059">
</head>
<body class="bg-light">
    <div class="auth-wrapper">
        <div class="row w-100 m-0">
            <div class="col-lg-6 d-none d-lg-flex auth-bg align-items-center justify-content-center flex-column text-white p-5 animate__animated animate__fadeIn">
                <div style="z-index: 1;" class="text-center">
                    <img src="https://illustrations.popsy.co/white/student.svg" alt="Reset password" class="img-fluid mb-5" style="max-height: 350px; drop-shadow: 0 10px 20px rgba(0,0,0,0.2);">
                    <h1 class="display-5 fw-bold mb-3"><?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?></h1>
                    <p class="lead fw-normal text-white-50">Choose a new password for your account.</p>
                </div>
            </div>

            <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5 bg-white shadow-lg animate__animated animate__fadeInRight">
                <div class="w-100" style="max-width: 450px;">
                    <div class="mb-4">
                        <a href="?page=login" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i> Back to login</a>
                    </div>

                    <div class="text-center mb-5">
                        <div class="d-inline-flex align-items-center justify-content-center bg-soft-primary text-primary rounded-circle mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-shield-lock fs-1"></i>
                        </div>
                        <h2 class="fw-bold text-dark">Reset Password</h2>
                        <p class="text-muted">Enter the OTP sent to your email and choose a new password.</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center animate__animated animate__headShake" role="alert">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($errors) && !empty($errors)): ?>
                        <?php foreach ($errors as $resetError): ?>
                            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center animate__animated animate__headShake" role="alert">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                                <div><?php echo htmlspecialchars($resetError); ?></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill flex-shrink-0 me-2"></i>
                            <div><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                            <div class="form-floating mb-3">
                                <input type="email" name="email" class="form-control" id="emailInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                <label for="emailInput"><i class="bi bi-envelope me-2 text-muted"></i>Email Address</label>
                                <div class="invalid-feedback">Email is required.</div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" name="otp" class="form-control" id="otpInput" placeholder="123456" value="<?php echo htmlspecialchars($otp ?? ''); ?>" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric">
                                <label for="otpInput"><i class="bi bi-key me-2 text-muted"></i>6-Digit OTP</label>
                                <div class="invalid-feedback">Enter the 6-digit OTP.</div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" name="password" class="form-control" id="passwordInput" placeholder="Password" required minlength="6">
                                <label for="passwordInput"><i class="bi bi-lock me-2 text-muted"></i>New Password</label>
                                <div class="invalid-feedback">Password must be at least 6 characters.</div>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="password" name="confirm_password" class="form-control" id="confirmPasswordInput" placeholder="Confirm Password" required minlength="6">
                                <label for="confirmPasswordInput"><i class="bi bi-lock me-2 text-muted"></i>Confirm Password</label>
                                <div class="invalid-feedback">Password confirmation is required.</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 mb-4 fw-bold shadow-sm rounded-3 fs-5">
                                Reset Password
                            </button>
                        </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
</body>
</html>
