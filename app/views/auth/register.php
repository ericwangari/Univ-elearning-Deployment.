<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?></title>
    <link rel="icon" type="image/png" href="public/images/icons/icon-192.png">
    <link rel="apple-touch-icon" href="public/images/icons/icon-192.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="public/css/style.css">
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
    <div class="auth-wrapper">
        <div class="row w-100 m-0">
            <!-- Left Side: Form -->
            <div class="col-lg-6 d-flex flex-column p-4 p-md-5 bg-white shadow-lg animate__animated animate__fadeInLeft">
                <div class="w-100 flex-grow-1 d-flex align-items-center justify-content-center">
                <div class="w-100" style="max-width: 480px;">
                    <div class="mb-4">
                        <a href="?page=login" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i> Back to login</a>
                    </div>
                    
                    <div class="mb-4 border-bottom pb-3">
                        <h2 class="fw-bold text-dark mb-2">Create an Account</h2>
                        <p class="text-muted">Join our community and start learning today.</p>
                    </div>

                    <?php if (isset($errors) && !empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center animate__animated animate__headShake" role="alert">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                                <div><?php echo $error; ?></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="username" class="form-control" id="usernameInput" placeholder="johndoe" required>
                                    <label for="usernameInput"><i class="bi bi-person me-2 text-muted"></i>Username</label>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="user_type" class="form-select" id="roleSelect" required>
                                        <option value="Student" selected>Student</option>
                                        <option value="Instructor">Instructor</option>
                                    </select>
                                    <label for="roleSelect"><i class="bi bi-person-badge me-2 text-muted"></i>I am a...</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" name="email" class="form-control" id="emailInput" placeholder="name@example.com" required>
                            <label for="emailInput"><i class="bi bi-envelope me-2 text-muted"></i>Email Address</label>
                            <div class="invalid-feedback">Please provide a valid email.</div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" name="password" class="form-control" id="passwordInput" placeholder="Password" required minlength="6">
                            <label for="passwordInput"><i class="bi bi-lock me-2 text-muted"></i>Password</label>
                            <div class="invalid-feedback">Password must be at least 6 characters.</div>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="password" name="confirm_password" class="form-control" id="confirmPasswordInput" placeholder="Confirm Password" required minlength="6">
                            <label for="confirmPasswordInput"><i class="bi bi-lock me-2 text-muted"></i>Confirm Password</label>
                            <div class="invalid-feedback">Password confirmation is required.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 mb-3 fw-bold shadow-sm rounded-3 fs-5 position-relative overflow-hidden group">
                            <span class="position-relative z-1">Create Account</span>
                        </button>

                    </form>
                </div>
                </div>

                <footer class="text-center text-muted small pt-4">
                    <i class="bi bi-c-circle me-1" aria-hidden="true"></i>
                    <?php echo date('Y'); ?> <?php echo defined('APP_NAME') ? APP_NAME : 'Univ E-Learning'; ?>. All rights reserved.
                </footer>
            </div>

            <!-- Right Side: Image/Branding -->
            <div class="col-lg-6 d-none d-lg-flex auth-bg align-items-center justify-content-center flex-column text-white p-5 animate__animated animate__fadeIn">
                <div style="z-index: 1;" class="text-center">
                    <img src="https://illustrations.popsy.co/white/freelancer.svg" alt="Register" class="img-fluid mb-5" style="max-height: 350px; drop-shadow: 0 10px 20px rgba(0,0,0,0.2);">
                    <h2 class="display-6 fw-bold mb-3">Unlock Your Potential</h2>
                    <p class="lead fw-normal text-white-50 px-5">Get unlimited access to top-tier courses, expert instructors, and a community of ambitious learners.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
</body>
</html>
