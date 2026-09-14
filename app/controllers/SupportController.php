<?php

class SupportController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function contact() {
        $prefill = $this->getCurrentUserDetails();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/auth/contact_support.php';
            return;
        }

        $name = trim($_POST['name'] ?? ($prefill['Username'] ?? ''));
        $email = strtolower(trim($_POST['email'] ?? ($prefill['Email'] ?? '')));
        $topic = trim($_POST['topic'] ?? 'General support');
        $message = trim($_POST['message'] ?? '');
        $errors = [];

        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if ($message === '' || strlen($message) < 10) {
            $errors[] = 'Please describe the issue in at least 10 characters.';
        }
        if (strlen($message) > 3000) {
            $errors[] = 'Support messages must be 3000 characters or less.';
        }

        if (empty($errors)) {
            if ($this->sendSupportEmail($name, $email, $topic, $message)) {
                $success_message = 'Thanks, your support request was sent.';
                $name = '';
                $email = '';
                $topic = 'General support';
                $message = '';
            } else {
                $errors[] = 'Support email could not be sent right now. You can email us directly instead.';
            }
        }

        require __DIR__ . '/../views/auth/contact_support.php';
    }

    private function getCurrentUserDetails() {
        if (!isLoggedIn()) {
            return [];
        }

        $stmt = $this->pdo->prepare("SELECT Username, Email, UserType FROM users WHERE UserID = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        return $user ?: [];
    }

    private function sendSupportEmail($name, $email, $topic, $supportMessage) {
        $to = defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : 'univelearning01@gmail.com';
        $fromName = str_replace(["\r", "\n"], '', MAIL_FROM_NAME);
        $fromEmail = str_replace(["\r", "\n"], '', MAIL_FROM);
        $replyTo = str_replace(["\r", "\n"], '', $email);
        $subject = APP_NAME . ' support request: ' . $this->cleanHeader($topic);
        $body = "A support request was submitted for " . APP_NAME . ".\n\n"
            . "Name: " . $name . "\n"
            . "Email: " . $email . "\n"
            . "Role: " . ($_SESSION['user_type'] ?? 'Guest') . "\n"
            . "Topic: " . $topic . "\n"
            . "Submitted: " . date('Y-m-d H:i:s') . "\n\n"
            . "Message:\n" . $supportMessage . "\n";

        if (SMTP_HOST !== '') {
            return $this->sendSmtpEmail($to, $subject, $body, $fromEmail, $fromName, $replyTo);
        }

        return $this->sendPhpMailerEmail($to, $subject, $body, $fromEmail, $fromName, $replyTo);
    }

    private function sendPhpMailerEmail($to, $subject, $message, $fromEmail, $fromName, $replyTo) {
        $mailConfigPath = __DIR__ . '/../../config/mail_config.php';
        if (!is_file($mailConfigPath)) {
            return false;
        }

        $mailConfig = require $mailConfigPath;
        if (($mailConfig['password'] ?? '') === '' || ($mailConfig['password'] ?? '') === 'GOOGLE_APP_PASSWORD_HERE') {
            return false;
        }

        if (!$this->loadPhpMailer()) {
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

            $mail->setFrom($mailConfig['from_email'] ?? $fromEmail, $mailConfig['from_name'] ?? $fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($replyTo);
            $mail->Subject = $subject;
            $mail->Body = $message;

            return $mail->send();
        } catch (Exception $e) {
            error_log('Support email failed: ' . $e->getMessage());
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

    private function sendSmtpEmail($to, $subject, $message, $fromEmail, $fromName, $replyTo) {
        $remote = (SMTP_SECURE === 'ssl' ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT;
        $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);

        if (!$socket) {
            error_log("Support SMTP connection failed: " . $errstr . " (" . $errno . ")");
            return false;
        }

        stream_set_timeout($socket, 20);

        try {
            $this->smtpExpect($socket, [220]);
            $serverName = parse_url(BASE_URL, PHP_URL_HOST) ?: 'localhost';

            $this->smtpCommand($socket, 'EHLO ' . $serverName, [250]);

            if (SMTP_SECURE === 'tls') {
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
                'Reply-To: <' . $replyTo . '>',
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
            error_log("Support SMTP send failed: " . $e->getMessage());
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

        return $this->cleanHeader($text);
    }

    private function cleanHeader($text) {
        return str_replace(["\r", "\n"], '', $text);
    }
}
