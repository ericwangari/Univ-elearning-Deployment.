<?php

class FeedbackController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function sendStudentFeedback() {
        if (!isLoggedIn() || ($_SESSION['user_type'] ?? '') !== 'Student') {
            http_response_code(403);
            $this->respond(['status' => 'error', 'message' => 'Only logged-in students can send feedback.']);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->respond(['status' => 'error', 'message' => 'Invalid request method.']);
        }

        $message = trim($_POST['message'] ?? '');
        if ($message === '' || strlen($message) < 5) {
            http_response_code(422);
            $this->respond(['status' => 'error', 'message' => 'Please write a little more feedback before sending.']);
        }

        if (strlen($message) > 2000) {
            http_response_code(422);
            $this->respond(['status' => 'error', 'message' => 'Feedback must be 2000 characters or less.']);
        }

        $stmt = $this->pdo->prepare("SELECT Username, Email FROM users WHERE UserID = ? AND UserType = 'Student' LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $student = $stmt->fetch();

        if (!$student) {
            http_response_code(404);
            $this->respond(['status' => 'error', 'message' => 'Student account could not be found.']);
        }

        $sent = $this->sendFeedbackEmail($student, $message);

        if (!$sent) {
            http_response_code(500);
            $this->respond(['status' => 'error', 'message' => 'Feedback could not be sent right now. Please try again.']);
        }

        $this->respond(['status' => 'success', 'message' => 'Thanks, your feedback was sent.']);
    }

    private function respond(array $payload) {
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode($payload);
            exit;
        }

        $_SESSION['feedback_flash'] = $payload;
        $redirectTo = $_SERVER['HTTP_REFERER'] ?? '?page=dashboard';
        if (strpos($redirectTo, "\r") !== false || strpos($redirectTo, "\n") !== false) {
            $redirectTo = '?page=dashboard';
        }

        header('Location: ' . $redirectTo);
        exit;
    }

    private function isAjaxRequest() {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    private function sendFeedbackEmail(array $student, $feedback) {
        $to = defined('PLATFORM_FEEDBACK_EMAIL') ? PLATFORM_FEEDBACK_EMAIL : 'univelearning01@gmail.com';
        $subject = APP_NAME . ' student feedback';
        $message = "A student sent feedback for " . APP_NAME . ".\n\n"
            . "Student: " . $student['Username'] . "\n"
            . "Email: " . $student['Email'] . "\n"
            . "Submitted: " . date('Y-m-d H:i:s') . "\n\n"
            . "Feedback:\n" . $feedback . "\n";

        $fromName = str_replace(["\r", "\n"], '', MAIL_FROM_NAME);
        $fromEmail = str_replace(["\r", "\n"], '', MAIL_FROM);
        $replyTo = str_replace(["\r", "\n"], '', $student['Email']);

        if (SMTP_HOST !== '') {
            return $this->sendSmtpEmail($to, $subject, $message, $fromEmail, $fromName, $replyTo);
        }

        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $replyTo,
            'Content-Type: text/plain; charset=UTF-8',
        ];

        return mail($to, $subject, $message, implode("\r\n", $headers));
    }

    private function sendSmtpEmail($to, $subject, $message, $fromEmail, $fromName, $replyTo) {
        $remote = (SMTP_SECURE === 'ssl' ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT;
        $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);

        if (!$socket) {
            error_log("Feedback SMTP connection failed: " . $errstr . " (" . $errno . ")");
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
            error_log("Feedback SMTP send failed: " . $e->getMessage());
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
