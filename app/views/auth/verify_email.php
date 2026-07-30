<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - <?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?></title>
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
                    <img src="https://illustrations.popsy.co/white/message-sent.svg" alt="Verify email" class="img-fluid mb-5" style="max-height: 350px; drop-shadow: 0 10px 20px rgba(0,0,0,0.2);">
                    <h1 class="display-5 fw-bold mb-3"><?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?></h1>
                    <p class="lead fw-normal text-white-50">Enter the OTP sent to your Gmail address.</p>
                </div>
            </div>

            <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5 bg-white shadow-lg animate__animated animate__fadeInRight">
                <div class="w-100" style="max-width: 450px;">
                    <div class="mb-4">
                        <a href="?page=login" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i> Back to login</a>
                    </div>

                    <div class="text-center mb-5">
                        <div class="d-inline-flex align-items-center justify-content-center bg-soft-primary text-primary rounded-circle mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-patch-check fs-1"></i>
                        </div>
                        <h2 class="fw-bold text-dark">Verify Email</h2>
                        <p class="text-muted">Use the 6-digit OTP sent to your Gmail.</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center animate__animated animate__headShake" role="alert">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
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

                    <form method="POST" action="?page=verify-email" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="email" name="email" class="form-control" id="emailInput" placeholder="name@gmail.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            <label for="emailInput"><i class="bi bi-envelope me-2 text-muted"></i>Gmail Address</label>
                            <div class="invalid-feedback">Please provide your Gmail address.</div>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="text" name="otp" class="form-control" id="otpInput" placeholder="123456" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric">
                            <label for="otpInput"><i class="bi bi-key me-2 text-muted"></i>6-Digit OTP</label>
                            <div class="invalid-feedback">Enter the 6-digit OTP.</div>
                        </div>

                        <button type="submit" name="action" value="verify" class="btn btn-primary w-100 py-3 mb-3 fw-bold shadow-sm rounded-3 fs-5">
                            Verify Email
                        </button>

                        <button type="submit" name="action" value="resend" class="btn btn-link text-primary fw-semibold text-decoration-none p-0 w-100" formnovalidate>
                            Resend verification OTP
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
