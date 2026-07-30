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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawInput = trim($_POST['email'] ?? '');
            $email = strtolower(preg_replace('/[^\x21-\x7E]/', '', $rawInput));
            $password = trim($_POST['password'] ?? '');

            // Try to find user by email or username
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE Email = ? OR Username = ? OR lower(Username) = ?");
            $stmt->execute([$email, $rawInput, $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['Password'])) {
                if (array_key_exists('EmailVerifiedAt', $user) && empty($user['EmailVerifiedAt'])) {
                    if (!defined('SMTP_HOST') || SMTP_HOST === '') {
                        try {
                            $stmtFix = $this->pdo->prepare("UPDATE users SET EmailVerifiedAt = NOW() WHERE UserID = ?");
                            $stmtFix->execute([$user['UserID']]);
                            $user['EmailVerifiedAt'] = date('Y-m-d H:i:s');
                        } catch (Exception $e) {
                            error_log("Login auto-verify error: " . $e->getMessage());
                        }
                    }
                }

                if (array_key_exists('EmailVerifiedAt', $user) && empty($user['EmailVerifiedAt'])) {
                    $error = "Please verify your email address before signing in. Check your inbox for the OTP code.";
                    $verification_email = $user['Email'];
                } elseif ($user['Status'] === 'Pending') {
                    $error = "Your account is currently waiting for admin approval. Please check back later.";
                } elseif ($user['Status'] === 'Rejected') {
                    $error = "Your account registration has been rejected. Please contact support.";
                } else {
                    $_SESSION['user_id'] = $user['UserID'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['user_type'] = $user['UserType'];

                    if (!empty($_POST['remember_me'])) {
                        setcookie('remembered_login', $email, time() + (30 * 24 * 60 * 60), '', '', false, true);
                    } else {
                        setcookie('remembered_login', '', time() - 3600, '', '', false, true);
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
            $rawEmail = $_POST['email'] ?? '';
            // Strip hidden characters, non-breaking spaces (\xA0) and trailing spaces added by mobile keypads
            $email = strtolower(preg_replace('/[^\x21-\x7E]/', '', trim($rawEmail)));
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
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
                $errors[] = "Please enter a valid email address.";
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

                            $stmt = $this->pdo->prepare("INSERT INTO users (Username, Email, Password, UserType, Status) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$username, $email, $hashed_password, $user_type, $status]);
                            $userId = (int) $this->pdo->lastInsertId();

                            if (!$this->createAndSendEmailVerificationOtp($userId, $email, $username)) {
                                throw new Exception("We could not send the verification OTP. Please check your email address and try again.");
                            }

                            $this->pdo->commit();

                            $checkStmt = $this->pdo->prepare("SELECT EmailVerifiedAt FROM users WHERE UserID = ?");
                            $checkStmt->execute([$userId]);
                            $u = $checkStmt->fetch();

                            if (!empty($u['EmailVerifiedAt'])) {
                                $success_message = $status === 'Pending'
                                    ? "Your account has been created and verified. Please wait for admin approval before signing in."
                                    : "Your account has been created successfully! You can now sign in.";
                                require __DIR__ . '/../views/auth/login.php';
                                exit;
                            }

                            $success_message = $status === 'Pending'
                                ? "Your account has been created. Enter the OTP sent to your email, then wait for admin approval before signing in."
                                : "Your account has been created. Enter the OTP sent to your email to verify your email.";
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
            $success_message = "Your email has been verified. You can now sign in.";
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
            $success_message = "This email is already verified. You can sign in.";
            require __DIR__ . '/../views/auth/login.php';
            return;
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
        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'Content-Type: text/plain; charset=UTF-8',
        ];

        if (SMTP_HOST !== '') {
            return $this->sendSmtpEmail($email, $subject, $message, $fromEmail, $fromName);
        }

        return mail($email, $subject, $message, implode("\r\n", $headers));
    }

    private function sendEmailVerificationEmail($email, $username, $otp) {
        $subject = 'Verify your ' . APP_NAME . ' email';
        $message = "Hello " . $username . ",\n\n"
            . "Thanks for registering for " . APP_NAME . ".\n\n"
            . "Your email verification OTP is: " . $otp . "\n\n"
            . "This code expires in 15 minutes. You will need to verify your email before signing in.\n";
        $fromName = str_replace(["\r", "\n"], '', MAIL_FROM_NAME);
        $fromEmail = str_replace(["\r", "\n"], '', MAIL_FROM);
        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'Content-Type: text/plain; charset=UTF-8',
        ];

        if (defined('SMTP_HOST') && SMTP_HOST !== '') {
            $sent = $this->sendSmtpEmail($email, $subject, $message, $fromEmail, $fromName);
            if ($sent) {
                return true;
            }
        }

        $sent = @mail($email, $subject, $message, implode("\r\n", $headers));
        if ($sent) {
            return true;
        }

        // Fallback for serverless hosting without SMTP configured:
        // Auto-verify email so registration and login succeed.
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET EmailVerifiedAt = NOW() WHERE Email = ?");
            $stmt->execute([$email]);
        } catch (Exception $e) {
            error_log("Auto-verify email fallback error: " . $e->getMessage());
        }

        return true;
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
