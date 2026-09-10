<?php
require_once __DIR__ . '/../models/User.php';

class AuthController {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isLoggedIn()) {
            header("Location: index.php?page=dashboard");
            exit;
        }

        $remembered_email = $_COOKIE['remembered_login'] ?? '';
        $success_message = $_SESSION['auth_success_message'] ?? null;
        unset($_SESSION['auth_success_message']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            // Passwords are opaque values. Registration does not trim them, so
            // trimming here would make otherwise valid passwords impossible to use.
            $password = $_POST['password'] ?? '';

            // Try to find user by email first, then by username
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE Email = ? OR Username = ?");
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['Password'])) {
                if (array_key_exists('EmailVerifiedAt', $user) && empty($user['EmailVerifiedAt'])) {
                    $error = "Please verify your email address before signing in. Check your inbox for the OTP code.";
                    $verification_email = $user['Email'];
                } elseif ($user['Status'] === 'Pending') {
                    $error = "Your account is currently waiting for admin approval. Please check back later.";
                } elseif ($user['Status'] === 'Rejected') {
                    $error = "Your account registration has been rejected. Please contact support.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['UserID'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['user_type'] = $user['UserType'];

                    $rememberCookieOptions = [
                        'expires' => !empty($_POST['remember_me']) ? time() + (30 * 24 * 60 * 60) : time() - 3600,
                        'path' => defined('COOKIE_PATH') ? COOKIE_PATH : '/',
                        'secure' => defined('COOKIE_SECURE') ? COOKIE_SECURE : false,
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ];

                    if (!empty($_POST['remember_me'])) {
                        setcookie('remembered_login', $email, $rememberCookieOptions);
                    } else {
                        setcookie('remembered_login', '', $rememberCookieOptions);
                    }

                    header("Location: index.php?page=dashboard");
                    exit;
                }
            } else {
                $error = "Invalid email/username or password";
            }
        }

        require __DIR__ . '/../views/auth/login.php';
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isLoggedIn()) {
            header("Location: index.php?page=dashboard");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $user_type = $_POST['user_type'] ?? 'Student';

            $errors = [];

            // Validation
            if (empty($username)) {
                $errors[] = "Username is required";
            }
            if (empty($email)) {
                $errors[] = "Email is required";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/i', $email)) {
                $errors[] = "Please register with a real Gmail address so you can receive your OTP code.";
            }
            if (empty($password)) {
                $errors[] = "Password is required";
            }
            if ($password !== $confirm_password) {
                $errors[] = "Passwords do not match";
            }
            if (strlen($password) < 6) {
                $errors[] = "Password must be at least 6 characters";
            }
            if ($user_type === 'Admin') {
                $errors[] = "Administrator accounts cannot be created via public registration.";
            }

            if (empty($errors)) {
                try {
                    // Check if username exists
                    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE Username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $errors[] = "Username already exists";
                    }

                    // Check if email exists
                    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE Email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $errors[] = "Email already exists";
                    }

                    if (empty($errors)) {
                        $this->pdo->beginTransaction();
                        try {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $status = ($user_type === 'Instructor') ? 'Pending' : 'Approved';

                            $userId = $this->createUser($username, $email, $hashed_password, $user_type, $status);

                            $verificationEmailSent = $this->createAndSendEmailVerificationOtp($userId, $email, $username);
                            $localAutoVerified = !$verificationEmailSent && defined('IS_LOCAL_DEV') && IS_LOCAL_DEV;

                            if (!$verificationEmailSent && !$localAutoVerified) {
                                throw new Exception("We could not send the verification OTP. Please confirm your Gmail address and try again.");
                            }

                            if ($localAutoVerified) {
                                $stmt = $this->pdo->prepare("UPDATE users SET EmailVerifiedAt = NOW() WHERE UserID = ?");
                                $stmt->execute([$userId]);
                            }

                            $this->pdo->commit();

                            if ($localAutoVerified) {
                                $_SESSION['auth_success_message'] = $status === 'Pending'
                                    ? "Your local instructor account was created and verified. Wait for admin approval before signing in."
                                    : "Your local account was created and verified. You can sign in now.";
                                redirect('index.php?page=login');
                            }

                            $success_message = $status === 'Pending'
                                ? "Your account has been created. Enter the OTP sent to your Gmail, then wait for admin approval before signing in."
                                : "Your account has been created. Enter the OTP sent to your Gmail to verify your email.";
                            require __DIR__ . '/../views/auth/verify_email.php';
                            exit;
                        } catch (Exception $e) {
                            $this->pdo->rollBack();
                            $errors[] = $e->getMessage();
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = "Registration failed: " . $e->getMessage();
                }
            }
        }

        require __DIR__ . '/../views/auth/register.php';
    }

    public function verifyEmail() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $email = trim($_GET['email'] ?? '');
            require __DIR__ . '/../views/auth/verify_email.php';
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $action = $_POST['action'] ?? 'verify';

        if ($action === 'resend') {
            $this->resendEmailVerificationOtp($email);
            return;
        }

        $otp = trim($_POST['otp'] ?? '');
        $tokenRecord = $this->getValidEmailVerificationOtp($email, $otp);

        if (!$tokenRecord) {
            $error = "This OTP is invalid or has expired.";
            require __DIR__ . '/../views/auth/verify_email.php';
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET EmailVerifiedAt = NOW() WHERE UserID = ?");
            $stmt->execute([$tokenRecord['UserID']]);

            $stmt = $this->pdo->prepare("UPDATE email_verification_tokens SET UsedAt = NOW() WHERE TokenID = ?");
            $stmt->execute([$tokenRecord['TokenID']]);

            $stmt = $this->pdo->prepare("DELETE FROM email_verification_tokens WHERE UserID = ? AND TokenID <> ?");
            $stmt->execute([$tokenRecord['UserID'], $tokenRecord['TokenID']]);

            $this->pdo->commit();
            $_SESSION['auth_success_message'] = "Your email has been verified. You can now sign in.";
            header("Location: index.php?page=login");
            exit;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Email verification failed: " . $e->getMessage());
            $error = "Email verification failed. Please try again.";
        }

        require __DIR__ . '/../views/auth/login.php';
    }

    private function resendEmailVerificationOtp($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Enter your Gmail address so we can resend the OTP.";
            require __DIR__ . '/../views/auth/verify_email.php';
            return;
        }

        $stmt = $this->pdo->prepare("
            SELECT UserID, Username, Email, EmailVerifiedAt
            FROM users
            WHERE Email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "No account was found for that email address.";
            require __DIR__ . '/../views/auth/verify_email.php';
            return;
        }

        if (!empty($user['EmailVerifiedAt'])) {
            $_SESSION['auth_success_message'] = "This email is already verified. You can sign in.";
            header("Location: index.php?page=login");
            exit;
        }

        if ($this->createAndSendEmailVerificationOtp((int) $user['UserID'], $user['Email'], $user['Username'])) {
            $success_message = "A new verification OTP has been sent to your Gmail address.";
        } else {
            $error = "We could not send a new OTP right now. Please try again in a moment.";
        }

        require __DIR__ . '/../views/auth/verify_email.php';
    }

    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isLoggedIn()) {
            header("Location: index.php?page=dashboard");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $success_message = "If an account exists for that email address, a password reset OTP has been sent.";

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Password reset is available to every account type: Admin, Instructor, and Student.
                $stmt = $this->pdo->prepare("SELECT UserID, Username, Email, UserType FROM users WHERE Email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    $this->createAndSendPasswordResetOtp($user);
                }
            }
        }

        require __DIR__ . '/../views/auth/forgot_password.php';
    }

    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isLoggedIn()) {
            header("Location: index.php?page=dashboard");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/auth/reset_password.php';
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $otp = trim($_POST['otp'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $tokenRecord = $this->getValidPasswordResetOtp($email, $otp);
        $errors = [];

        if (!$tokenRecord) {
            $errors[] = "This OTP is invalid or has expired.";
        }
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $this->pdo->beginTransaction();
            try {
                $stmt = $this->pdo->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
                $stmt->execute([$hashedPassword, $tokenRecord['UserID']]);

                $stmt = $this->pdo->prepare("UPDATE password_reset_tokens SET UsedAt = NOW() WHERE TokenID = ?");
                $stmt->execute([$tokenRecord['TokenID']]);

                $stmt = $this->pdo->prepare("DELETE FROM password_reset_tokens WHERE UserID = ? AND TokenID <> ?");
                $stmt->execute([$tokenRecord['UserID'], $tokenRecord['TokenID']]);

                $this->pdo->commit();
            } catch (Exception $e) {
                $this->pdo->rollBack();
                $errors[] = "Password reset failed. Please try again.";
            }

            if (empty($errors)) {
                $success_message = "Your password has been reset. You can now sign in with your new password.";
                require __DIR__ . '/../views/auth/login.php';
                return;
            }
        }

        require __DIR__ . '/../views/auth/reset_password.php';
    }

    public function googleLogin() {
        if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
            $error = "Google sign-in is not configured yet. Please contact support.";
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        $requestedRole = $_GET['role'] ?? 'Student';
        $role = $requestedRole === 'Instructor' ? 'Instructor' : 'Student';
        $state = bin2hex(random_bytes(24));

        $_SESSION['google_oauth_state'] = $state;
        $_SESSION['google_oauth_role'] = $role;

        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ];

        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
        exit;
    }

    public function googleCallback() {
        if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
            $error = "Google sign-in is not configured yet. Please contact support.";
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        $state = $_GET['state'] ?? '';
        $expectedState = $_SESSION['google_oauth_state'] ?? '';
        $role = $_SESSION['google_oauth_role'] ?? 'Student';
        unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_role']);

        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            $error = "Google sign-in could not be verified. Please try again.";
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        if (!empty($_GET['error'])) {
            $error = "Google sign-in was cancelled or denied.";
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $error = "Google sign-in did not return a valid code. Please try again.";
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        $tokenResponse = $this->httpPostJson('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'grant_type' => 'authorization_code',
        ]);

        $accessToken = $tokenResponse['access_token'] ?? '';
        if ($accessToken === '') {
            error_log('Google sign-in token exchange failed: ' . json_encode($tokenResponse));
            $error = "Google sign-in failed. Please try again.";
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        $profile = $this->httpGetJson('https://www.googleapis.com/oauth2/v3/userinfo', [
            'Authorization: Bearer ' . $accessToken,
        ]);

        $email = strtolower(trim($profile['email'] ?? ''));
        $emailVerified = filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $displayName = trim($profile['name'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$emailVerified) {
            $error = "Google did not confirm a verified email address for this account.";
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        try {
            $user = $this->findUserByEmail($email);
            if (!$user) {
                $username = $this->makeUniqueUsername($displayName !== '' ? $displayName : $email);
                $status = $role === 'Instructor' ? 'Pending' : 'Approved';
                $password = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
                $userId = $this->createUser($username, $email, $password, $role, $status, true);
                $user = $this->findUserById($userId);
            } elseif (empty($user['EmailVerifiedAt'])) {
                $stmt = $this->pdo->prepare("UPDATE users SET EmailVerifiedAt = NOW() WHERE UserID = ?");
                $stmt->execute([$user['UserID']]);
                $user = $this->findUserById((int) $user['UserID']);
            }

            if (!$user) {
                throw new Exception('Could not load Google user account.');
            }

            if ($user['Status'] === 'Pending') {
                $_SESSION['auth_success_message'] = "Your instructor account email is verified. Wait for admin approval before signing in.";
                header("Location: index.php?page=login");
                exit;
            }

            if ($user['Status'] === 'Rejected') {
                $error = "Your account registration has been rejected. Please contact support.";
                require __DIR__ . '/../views/auth/login.php';
                return;
            }

            $this->signInUser($user);
            header("Location: index.php?page=dashboard");
            exit;
        } catch (Exception $e) {
            error_log('Google sign-in failed: ' . $e->getMessage());
            $error = "Google sign-in failed. Please try again.";
            require __DIR__ . '/../views/auth/login.php';
        }
    }

    public function logout() {
        session_destroy();
        header("Location: index.php?page=login");
        exit;
    }

    private function getValidPasswordResetOtp($email, $otp) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6}$/', $otp)) {
            return false;
        }

        $tokenHash = hash('sha256', $otp);
        $stmt = $this->pdo->prepare("
            SELECT prt.TokenID, prt.UserID
            FROM password_reset_tokens prt
            INNER JOIN users u ON u.UserID = prt.UserID
            WHERE u.Email = ?
              AND prt.TokenHash = ?
              AND prt.UsedAt IS NULL
              AND prt.ExpiresAt > NOW()
            LIMIT 1
        ");
        try {
            $stmt->execute([$email, $tokenHash]);
        } catch (Exception $e) {
            error_log("Password reset token lookup failed: " . $e->getMessage());
            return false;
        }

        return $stmt->fetch();
    }

    private function deleteExistingPasswordResetTokens($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM password_reset_tokens WHERE UserID = ? OR ExpiresAt <= NOW()");
        $stmt->execute([$userId]);
    }

    private function createUser($username, $email, $password, $userType, $status, $emailVerified = false) {
        if (DB_DRIVER === 'pgsql') {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (Username, Email, Password, UserType, Status, EmailVerifiedAt)
                VALUES (?, ?, ?, ?, ?, " . ($emailVerified ? "NOW()" : "NULL") . ")
                RETURNING UserID
            ");
            $stmt->execute([$username, $email, $password, $userType, $status]);
            return (int) $stmt->fetchColumn();
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO users (Username, Email, Password, UserType, Status, EmailVerifiedAt)
            VALUES (?, ?, ?, ?, ?, " . ($emailVerified ? "NOW()" : "NULL") . ")
        ");
        $stmt->execute([$username, $email, $password, $userType, $status]);
        return (int) $this->pdo->lastInsertId();
    }

    private function findUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    private function findUserById($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE UserID = ? LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    private function makeUniqueUsername($source) {
        $base = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '', explode('@', $source)[0]));
        if ($base === '') {
            $base = 'user';
        }

        $base = substr($base, 0, 32);
        $candidate = $base;
        $suffix = 1;

        while ($this->usernameExists($candidate)) {
            $suffix++;
            $candidate = substr($base, 0, 32 - strlen((string) $suffix)) . $suffix;
        }

        return $candidate;
    }

    private function usernameExists($username) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE Username = ?");
        $stmt->execute([$username]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function signInUser($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['user_type'] = $user['UserType'];
    }

    private function httpPostJson($url, array $fields) {
        return $this->httpJson($url, [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($fields),
        ]);
    }

    private function httpGetJson($url, array $headers = []) {
        return $this->httpJson($url, [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
        ]);
    }

    private function httpJson($url, array $options) {
        $context = stream_context_create([
            'http' => array_merge([
                'timeout' => 20,
                'ignore_errors' => true,
            ], $options),
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return [];
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function createAndSendEmailVerificationOtp($userId, $email, $username) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM email_verification_tokens WHERE UserID = ? OR ExpiresAt <= NOW()");
            $stmt->execute([$userId]);

            $otp = (string) random_int(100000, 999999);
            $tokenHash = hash('sha256', $otp);
            $expiresAt = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');

            $stmt = $this->pdo->prepare("INSERT INTO email_verification_tokens (UserID, TokenHash, ExpiresAt) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $tokenHash, $expiresAt]);

            return $this->sendEmailVerificationEmail($email, $username, $otp);
        } catch (Exception $e) {
            error_log("Email verification request failed: " . $e->getMessage());
            return false;
        }
    }

    private function getValidEmailVerificationOtp($email, $otp) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6}$/', $otp)) {
            return false;
        }

        $tokenHash = hash('sha256', $otp);
        $stmt = $this->pdo->prepare("
            SELECT evt.TokenID, evt.UserID
            FROM email_verification_tokens evt
            INNER JOIN users u ON u.UserID = evt.UserID
            WHERE u.Email = ?
              AND evt.TokenHash = ?
              AND evt.UsedAt IS NULL
              AND evt.ExpiresAt > NOW()
            LIMIT 1
        ");

        try {
            $stmt->execute([$email, $tokenHash]);
        } catch (Exception $e) {
            error_log("Email verification token lookup failed: " . $e->getMessage());
            return false;
        }

        return $stmt->fetch();
    }

    private function createAndSendPasswordResetOtp($user) {
        try {
            $this->deleteExistingPasswordResetTokens((int) $user['UserID']);

            $otp = (string) random_int(100000, 999999);
            $tokenHash = hash('sha256', $otp);
            $expiresAt = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');

            $stmt = $this->pdo->prepare("INSERT INTO password_reset_tokens (UserID, TokenHash, ExpiresAt) VALUES (?, ?, ?)");
            $stmt->execute([$user['UserID'], $tokenHash, $expiresAt]);

            return $this->sendPasswordResetEmail($user['Email'], $user['Username'], $otp);
        } catch (Exception $e) {
            error_log("Password reset request failed: " . $e->getMessage());
            return false;
        }
    }

    private function sendPasswordResetEmail($email, $username, $otp) {
        $subject = APP_NAME . ' password reset';
        $message = "Hello " . $username . ",\n\n"
            . "We received a request to reset your password for " . APP_NAME . ".\n\n"
            . "Your password reset OTP is: " . $otp . "\n\n"
            . "This code expires in 15 minutes. If you did not request this, you can ignore this email.\n";
        $fromName = str_replace(["\r", "\n"], '', MAIL_FROM_NAME);
        $fromEmail = str_replace(["\r", "\n"], '', MAIL_FROM);

        return $this->sendConfiguredEmail($email, $subject, $message, $fromEmail, $fromName);
    }

    private function sendEmailVerificationEmail($email, $username, $otp) {
        $subject = 'Verify your ' . APP_NAME . ' email';
        $message = "Hello " . $username . ",\n\n"
            . "Thanks for registering for " . APP_NAME . ".\n\n"
            . "Your email verification OTP is: " . $otp . "\n\n"
            . "This code expires in 15 minutes. You will need to verify your email before signing in.\n";
        $fromName = str_replace(["\r", "\n"], '', MAIL_FROM_NAME);
        $fromEmail = str_replace(["\r", "\n"], '', MAIL_FROM);

        return $this->sendConfiguredEmail($email, $subject, $message, $fromEmail, $fromName);
    }

    private function sendConfiguredEmail($to, $subject, $message, $fromEmail, $fromName) {
        if (SMTP_HOST !== '') {
            return $this->sendSmtpEmail($to, $subject, $message, $fromEmail, $fromName);
        }

        return $this->sendPhpMailerEmail($to, $subject, $message);
    }

    private function sendPhpMailerEmail($to, $subject, $message) {
        $mailConfigPath = __DIR__ . '/../../config/mail_config.php';
        if (!is_file($mailConfigPath)) {
            error_log('Auth email failed: config/mail_config.php not found.');
            return false;
        }

        $mailConfig = require $mailConfigPath;
        if (($mailConfig['password'] ?? '') === '' || ($mailConfig['password'] ?? '') === 'GOOGLE_APP_PASSWORD_HERE') {
            error_log('Auth email failed: Gmail App Password is not configured.');
            return false;
        }

        if (!$this->loadPhpMailer()) {
            error_log('Auth email failed: PHPMailer is not installed.');
            return false;
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            $mail->SMTPSecure = strtolower($mailConfig['encryption'] ?? 'tls') === 'ssl'
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) $mailConfig['port'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $message;

            return $mail->send();
        } catch (Exception $e) {
            error_log('Auth email failed: ' . $e->getMessage());
            return false;
        }
    }

    private function loadPhpMailer() {
        if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            return true;
        }

        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        $manualPhpMailerPath = __DIR__ . '/../../PHPMailer/src';

        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
        } elseif (
            is_file($manualPhpMailerPath . '/Exception.php') &&
            is_file($manualPhpMailerPath . '/PHPMailer.php') &&
            is_file($manualPhpMailerPath . '/SMTP.php')
        ) {
            require_once $manualPhpMailerPath . '/Exception.php';
            require_once $manualPhpMailerPath . '/PHPMailer.php';
            require_once $manualPhpMailerPath . '/SMTP.php';
        }

        return class_exists('\PHPMailer\PHPMailer\PHPMailer');
    }

    private function sendSmtpEmail($to, $subject, $message, $fromEmail, $fromName) {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $secure = SMTP_SECURE;
        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);

        if (!$socket) {
            error_log("SMTP connection failed: " . $errstr . " (" . $errno . ")");
            return false;
        }

        stream_set_timeout($socket, 20);

        try {
            $this->smtpExpect($socket, [220]);
            $serverName = parse_url(BASE_URL, PHP_URL_HOST) ?: 'localhost';

            $this->smtpCommand($socket, 'EHLO ' . $serverName, [250]);

            if ($secure === 'tls') {
                $this->smtpCommand($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception('Could not enable SMTP TLS encryption.');
                }
                $this->smtpCommand($socket, 'EHLO ' . $serverName, [250]);
            }

            if (SMTP_USERNAME !== '') {
                $this->smtpCommand($socket, 'AUTH LOGIN', [334]);
                $this->smtpCommand($socket, base64_encode(SMTP_USERNAME), [334]);
                $this->smtpCommand($socket, base64_encode(SMTP_PASSWORD), [235]);
            }

            $this->smtpCommand($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
            $this->smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->smtpCommand($socket, 'DATA', [354]);

            $headers = [
                'Date: ' . date('r'),
                'From: ' . $this->formatEmailAddress($fromEmail, $fromName),
                'To: <' . $to . '>',
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];
            $body = implode("\r\n", $headers) . "\r\n\r\n" . $message;
            $body = str_replace(["\r\n.", "\n."], ["\r\n..", "\n.."], $body);
            fwrite($socket, str_replace("\n", "\r\n", str_replace("\r\n", "\n", $body)) . "\r\n.\r\n");
            $this->smtpExpect($socket, [250]);
            $this->smtpCommand($socket, 'QUIT', [221]);
            fclose($socket);

            return true;
        } catch (Exception $e) {
            error_log("SMTP send failed: " . $e->getMessage());
            fclose($socket);
            return false;
        }
    }

    private function smtpCommand($socket, $command, array $expectedCodes) {
        fwrite($socket, $command . "\r\n");
        return $this->smtpExpect($socket, $expectedCodes);
    }

    private function smtpExpect($socket, array $expectedCodes) {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new Exception(trim($response));
        }

        return $response;
    }

    private function formatEmailAddress($email, $name) {
        $cleanName = addcslashes(str_replace(["\r", "\n", '"'], '', $name), '\\');
        return '"' . $cleanName . '" <' . $email . '>';
    }

    private function encodeHeader($text) {
        if (preg_match('/[^\x20-\x7E]/', $text)) {
            return '=?UTF-8?B?' . base64_encode($text) . '?=';
        }

        return str_replace(["\r", "\n"], '', $text);
    }
}
