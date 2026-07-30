<?php
require_once __DIR__ . '/config/config.php';

function feedback_response($status, $message) {
    $_SESSION['feedback_flash'] = [
        'status' => $status,
        'message' => $message,
    ];

    $redirectTo = $_POST['redirect_to'] ?? 'index.php?page=dashboard';
    if ($redirectTo === '' || strpos($redirectTo, "\r") !== false || strpos($redirectTo, "\n") !== false || preg_match('/^https?:\/\//i', $redirectTo)) {
        $redirectTo = 'index.php?page=dashboard';
    }

    header('Location: ' . $redirectTo);
    exit;
}

function feedback_smtp_command($socket, $command, array $expectedCodes) {
    fwrite($socket, $command . "\r\n");
    return feedback_smtp_expect($socket, $expectedCodes);
}

function feedback_smtp_expect($socket, array $expectedCodes) {
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

function feedback_format_email($email, $name) {
    $cleanName = addcslashes(str_replace(["\r", "\n", '"'], '', $name), '\\');
    return '"' . $cleanName . '" <' . $email . '>';
}

function feedback_encode_header($text) {
    if (preg_match('/[^\x20-\x7E]/', $text)) {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }

    return str_replace(["\r", "\n"], '', $text);
}

function feedback_send_smtp($to, $subject, $message, $fromEmail, $fromName, $replyTo) {
    if (SMTP_HOST === '') {
        return false;
    }

    $remote = (SMTP_SECURE === 'ssl' ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT;
    $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);

    if (!$socket) {
        error_log("Feedback SMTP connection failed: " . $errstr . " (" . $errno . ")");
        return false;
    }

    stream_set_timeout($socket, 20);

    try {
        feedback_smtp_expect($socket, [220]);
        $serverName = parse_url(BASE_URL, PHP_URL_HOST) ?: 'localhost';

        feedback_smtp_command($socket, 'EHLO ' . $serverName, [250]);

        if (SMTP_SECURE === 'tls') {
            feedback_smtp_command($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('Could not enable SMTP TLS encryption.');
            }
            feedback_smtp_command($socket, 'EHLO ' . $serverName, [250]);
        }

        if (SMTP_USERNAME !== '') {
            feedback_smtp_command($socket, 'AUTH LOGIN', [334]);
            feedback_smtp_command($socket, base64_encode(SMTP_USERNAME), [334]);
            feedback_smtp_command($socket, base64_encode(SMTP_PASSWORD), [235]);
        }

        feedback_smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        feedback_smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        feedback_smtp_command($socket, 'DATA', [354]);

        $headers = [
            'Date: ' . date('r'),
            'From: ' . feedback_format_email($fromEmail, $fromName),
            'To: <' . $to . '>',
            'Reply-To: <' . $replyTo . '>',
            'Subject: ' . feedback_encode_header($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        $body = implode("\r\n", $headers) . "\r\n\r\n" . $message;
        $body = str_replace(["\r\n.", "\n."], ["\r\n..", "\n.."], $body);
        fwrite($socket, str_replace("\n", "\r\n", str_replace("\r\n", "\n", $body)) . "\r\n.\r\n");

        feedback_smtp_expect($socket, [250]);
        feedback_smtp_command($socket, 'QUIT', [221]);
        fclose($socket);
        return true;
    } catch (Exception $e) {
        error_log("Feedback SMTP send failed: " . $e->getMessage());
        fclose($socket);
        return false;
    }
}

if (!isLoggedIn() || ($_SESSION['user_type'] ?? '') !== 'Student') {
    feedback_response('error', 'Please sign in as a student to send feedback.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    feedback_response('error', 'Invalid feedback request.');
}

$feedback = trim($_POST['message'] ?? '');
if ($feedback === '' || strlen($feedback) < 5) {
    feedback_response('error', 'Please write a little more feedback before sending.');
}

if (strlen($feedback) > 2000) {
    feedback_response('error', 'Feedback must be 2000 characters or less.');
}

$stmt = $pdo->prepare("SELECT Username, Email FROM users WHERE UserID = ? AND UserType = 'Student' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    feedback_response('error', 'Student account could not be found.');
}

$to = defined('PLATFORM_FEEDBACK_EMAIL') ? PLATFORM_FEEDBACK_EMAIL : 'univelearning01@gmail.com';
$fromName = str_replace(["\r", "\n"], '', MAIL_FROM_NAME);
$fromEmail = str_replace(["\r", "\n"], '', MAIL_FROM);
$replyTo = str_replace(["\r", "\n"], '', $student['Email']);
$subject = APP_NAME . ' student feedback';
$body = "A student sent feedback for " . APP_NAME . ".\n\n"
    . "Student: " . $student['Username'] . "\n"
    . "Email: " . $student['Email'] . "\n"
    . "Submitted: " . date('Y-m-d H:i:s') . "\n\n"
    . "Feedback:\n" . $feedback . "\n";

if (!feedback_send_smtp($to, $subject, $body, $fromEmail, $fromName, $replyTo)) {
    feedback_response('error', 'Feedback could not be sent right now. Please try again.');
}

feedback_response('success', 'Thanks, your feedback was sent.');
