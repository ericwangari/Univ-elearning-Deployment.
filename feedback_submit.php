<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/config/config.php';

function feedbackJsonResponse($success, $message, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
    ]);
    exit;
}

function feedbackCleanText($value) {
    return trim(str_replace(["\r", "\n", "\0"], '', (string) $value));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    feedbackJsonResponse(false, 'Invalid request method.', 405);
}

$message = trim((string) ($_POST['message'] ?? ''));

$errors = [];
if ($message === '') {
    $errors[] = 'Message is required.';
}

if ($errors) {
    feedbackJsonResponse(false, implode(' ', $errors), 422);
}

$mailConfig = require __DIR__ . '/config/mail_config.php';
if (($mailConfig['password'] ?? '') === '' || ($mailConfig['password'] ?? '') === 'GOOGLE_APP_PASSWORD_HERE') {
    feedbackJsonResponse(false, 'Feedback email is not configured yet. Add the Gmail App Password in config/mail_config.php or set FEEDBACK_SMTP_PASSWORD.', 500);
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
$manualPhpMailerPath = __DIR__ . '/PHPMailer/src';

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
} else {
    feedbackJsonResponse(false, 'PHPMailer is not installed. Upload vendor/ from Composer or PHPMailer/src before deploying this feature.', 500);
}

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $mailConfig['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $mailConfig['username'];
    $mail->Password = $mailConfig['password'];
    $mail->SMTPSecure = strtolower($mailConfig['encryption'] ?? 'tls') === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) $mailConfig['port'];
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
    $mail->addAddress($mailConfig['to_email'], $mailConfig['to_name']);

    $mail->Subject = 'Website Feedback';
    $mail->Body = $message;

    $mail->send();
    feedbackJsonResponse(true, 'Feedback sent successfully.');
} catch (Exception $e) {
    error_log('Feedback email failed: ' . $e->getMessage());
    feedbackJsonResponse(false, 'Feedback could not be sent right now. Please try again later.', 500);
}
