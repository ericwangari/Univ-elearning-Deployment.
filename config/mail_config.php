<?php

$gmailAppPassword = getenv('FEEDBACK_SMTP_PASSWORD') ?: 'GOOGLE_APP_PASSWORD_HERE';

return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'univelearning01@gmail.com',

    // Put the Gmail App Password in FEEDBACK_SMTP_PASSWORD, or replace GOOGLE_APP_PASSWORD_HERE below.
    // Use a Gmail App Password, not the normal Gmail login password.
    // Keep this file private and do not commit a real password to a public repository.
    'password' => $gmailAppPassword,

    'from_email' => 'univelearning01@gmail.com',
    'from_name' => 'Univ E-Learning',
    'to_email' => 'univelearning01@gmail.com',
    'to_name' => 'Univ E-Learning Feedback',
];
