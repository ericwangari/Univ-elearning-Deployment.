<?php

$smtpPassword = getenv('SMTP_PASSWORD') ?: getenv('FEEDBACK_SMTP_PASSWORD') ?: 'GOOGLE_APP_PASSWORD_HERE';
$smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
$smtpEncryption = getenv('SMTP_SECURE') ?: ($smtpPort === 465 ? 'ssl' : 'tls');
$smtpUsername = getenv('SMTP_USERNAME') ?: 'univelearning01@gmail.com';
$mailFrom = getenv('MAIL_FROM') ?: $smtpUsername;
$mailFromName = getenv('MAIL_FROM_NAME') ?: 'Univ E-Learning';
$feedbackTo = getenv('PLATFORM_FEEDBACK_EMAIL') ?: 'univelearning01@gmail.com';

return [
    'host' => $smtpHost,
    'port' => $smtpPort,
    'encryption' => $smtpEncryption,
    'username' => $smtpUsername,
    'password' => $smtpPassword,
    'from_email' => $mailFrom,
    'from_name' => $mailFromName,
    'to_email' => $feedbackTo,
    'to_name' => 'Univ E-Learning Feedback',
];
