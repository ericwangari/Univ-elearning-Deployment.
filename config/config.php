<?php
require_once __DIR__ . '/database_compat.php';
/* -----------------------------
   DATABASE CONFIGURATION
------------------------------*/
$localConfigPath = __DIR__ . '/hosting.local.php';
$localConfig = [];

if (is_file($localConfigPath)) {
    $loadedLocalConfig = require $localConfigPath;

    if (is_array($loadedLocalConfig)) {
        $localConfig = $loadedLocalConfig;
    }
}

// Support postgresql:// or postgres:// (Supabase) as well as mysql:// (Railway / local)
$databaseUrl = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL') ?: getenv('MYSQL_URL') ?: '';
$databaseConfig = [];
$dbDriver = 'mysql'; // default

if ($databaseUrl) {
    $parsedDatabaseUrl = parse_url($databaseUrl);

    if ($parsedDatabaseUrl !== false) {
        $scheme = strtolower($parsedDatabaseUrl['scheme'] ?? 'mysql');
        if ($scheme === 'postgresql' || $scheme === 'postgres' || $scheme === 'pgsql') {
            $dbDriver = 'pgsql';
        }

        $databaseConfig = [
            'host' => $parsedDatabaseUrl['host'] ?? null,
            'port' => $parsedDatabaseUrl['port'] ?? null,
            'name' => isset($parsedDatabaseUrl['path']) ? ltrim($parsedDatabaseUrl['path'], '/') : null,
            'user' => isset($parsedDatabaseUrl['user']) ? rawurldecode($parsedDatabaseUrl['user']) : null,
            'pass' => isset($parsedDatabaseUrl['pass']) ? rawurldecode($parsedDatabaseUrl['pass']) : null,
        ];
    }
}

// Allow explicit override via env for Supabase direct connections
if (getenv('DB_DRIVER') === 'pgsql' || getenv('PGSQL_HOST') || getenv('PGHOST')) {
    $dbDriver = 'pgsql';
}

define('DB_DRIVER', $localConfig['db_driver'] ?? $dbDriver);
define('DB_HOST',   $localConfig['db_host'] ?? $databaseConfig['host'] ?? getenv('PGHOST') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT',   $localConfig['db_port'] ?? $databaseConfig['port'] ?? getenv('PGPORT') ?: getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: (DB_DRIVER === 'pgsql' ? '5432' : '3306'));
define('DB_NAME',   $localConfig['db_name'] ?? $databaseConfig['name'] ?? getenv('PGDATABASE') ?: getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'univ_elearning');
define('DB_USER',   $localConfig['db_user'] ?? $databaseConfig['user'] ?? getenv('PGUSER') ?: getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
define('DB_PASS',   $localConfig['db_pass'] ?? $databaseConfig['pass'] ?? getenv('PGPASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');

/* -----------------------------
   APP CONFIGURATION
------------------------------*/
define('APP_NAME', 'Univ E-Learning');
$vercelHost = getenv('VERCEL_PROJECT_PRODUCTION_URL') ?: getenv('VERCEL_URL') ?: '';
$defaultBaseUrl = $vercelHost !== '' ? 'https://' . $vercelHost . '/' : 'http://localhost/univ_elearning/';
define('BASE_URL', $localConfig['base_url'] ?? getenv('BASE_URL') ?: $defaultBaseUrl);

$mailFrom = $localConfig['mail_from'] ?? '';
if ($mailFrom === '') $mailFrom = getenv('MAIL_FROM') ?: 'no-reply@univ-elearning.local';

$mailFromName = $localConfig['mail_from_name'] ?? '';
if ($mailFromName === '') $mailFromName = getenv('MAIL_FROM_NAME') ?: APP_NAME;

$smtpHost = $localConfig['smtp_host'] ?? '';
if ($smtpHost === '') $smtpHost = getenv('SMTP_HOST') ?: '';

$smtpPort = $localConfig['smtp_port'] ?? '';
if ($smtpPort === '') $smtpPort = getenv('SMTP_PORT') ?: 587;

$smtpUsername = $localConfig['smtp_username'] ?? '';
if ($smtpUsername === '') $smtpUsername = getenv('SMTP_USERNAME') ?: '';

$smtpPassword = $localConfig['smtp_password'] ?? '';
if ($smtpPassword === '') $smtpPassword = getenv('SMTP_PASSWORD') ?: '';

$smtpSecure = $localConfig['smtp_secure'] ?? '';
if ($smtpSecure === '') $smtpSecure = getenv('SMTP_SECURE') ?: 'tls';

define('MAIL_FROM', $mailFrom);
define('MAIL_FROM_NAME', $mailFromName);
define('SMTP_HOST', $smtpHost);
define('SMTP_PORT', (int) $smtpPort);
define('SMTP_USERNAME', $smtpUsername);
define('SMTP_PASSWORD', $smtpPassword);
define('SMTP_SECURE', strtolower($smtpSecure));
define('PLATFORM_FEEDBACK_EMAIL', $localConfig['platform_feedback_email'] ?? getenv('PLATFORM_FEEDBACK_EMAIL') ?: 'univelearning01@gmail.com');

/* -----------------------------
   START SESSION (SAFE)
------------------------------*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -----------------------------
   PDO DATABASE CONNECTION
------------------------------*/
function createAppPdoConnection($driver, $host, $port, $name, $user, $pass) {
    if ($driver === 'pgsql') {
        $dsn = "pgsql:host=" . $host . ";port=" . $port . ";dbname=" . $name . ";sslmode=require";
        $pdoOptions = [PDO::ATTR_EMULATE_PREPARES => true];
    } else {
        $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $name . ";charset=utf8mb4";
        $pdoOptions = [];
    }

    return new AppPDO($dsn, $user, $pass, $pdoOptions, $driver);
}

try {
    $pdo = createAppPdoConnection(DB_DRIVER, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $canRetrySupabaseDirect = DB_DRIVER === 'pgsql'
        && stripos(DB_HOST, 'pooler.supabase.com') !== false
        && preg_match('/^postgres\.([a-z0-9]+)$/i', DB_USER, $matches);

    if ($canRetrySupabaseDirect) {
        try {
            $supabaseRef = strtolower($matches[1]);
            $pdo = createAppPdoConnection(DB_DRIVER, 'db.' . $supabaseRef . '.supabase.co', '5432', DB_NAME, 'postgres', DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $fallbackException) {
            die("Database Connection Failed: " . $fallbackException->getMessage());
        }
    } else {
        die("Database Connection Failed: " . $e->getMessage());
    }
}


// Schema maintenance: add missing tables on MySQL only.
// On PostgreSQL (Supabase) these are already created via schema_pgsql.sql.
if (DB_DRIVER !== 'pgsql') {
    $schemaUpdates = [
        "CREATE TABLE IF NOT EXISTS instructor_courses (
            InstructorCourseID INT AUTO_INCREMENT PRIMARY KEY,
            InstructorID INT NOT NULL,
            CourseID INT NOT NULL,
            AssignedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_instructor_course (InstructorID, CourseID),
            FOREIGN KEY (InstructorID) REFERENCES users(UserID) ON DELETE CASCADE,
            FOREIGN KEY (CourseID) REFERENCES courses(CourseID) ON DELETE CASCADE
        ) ENGINE=InnoDB;",
        "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            TokenID INT AUTO_INCREMENT PRIMARY KEY,
            UserID INT NOT NULL,
            TokenHash CHAR(64) NOT NULL UNIQUE,
            ExpiresAt DATETIME NOT NULL,
            UsedAt DATETIME NULL DEFAULT NULL,
            CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_reset_user (UserID),
            INDEX idx_password_reset_expires (ExpiresAt),
            FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE
        ) ENGINE=InnoDB;",
        "CREATE TABLE IF NOT EXISTS email_verification_tokens (
            TokenID INT AUTO_INCREMENT PRIMARY KEY,
            UserID INT NOT NULL,
            TokenHash CHAR(64) NOT NULL UNIQUE,
            ExpiresAt DATETIME NOT NULL,
            UsedAt DATETIME NULL DEFAULT NULL,
            CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_verification_user (UserID),
            INDEX idx_email_verification_expires (ExpiresAt),
            FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE
        ) ENGINE=InnoDB;",
    ];

    foreach ($schemaUpdates as $schemaUpdate) {
        try {
            $pdo->exec($schemaUpdate);
        } catch (Exception $e) {
            error_log("Schema maintenance warning: " . $e->getMessage());
        }
    }
}


// Schema maintenance - uses information_schema so it works on both MySQL and PostgreSQL
try {
    $colCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
          WHERE table_name = 'users' AND column_name = :col"
    );
    $columnName = DB_DRIVER === 'pgsql' ? 'emailverifiedat' : 'EmailVerifiedAt';
    $colCheck->execute([':col' => $columnName]);
    if ((int)$colCheck->fetchColumn() === 0) {
        if (DB_DRIVER === 'pgsql') {
            $pdo->exec('ALTER TABLE users ADD COLUMN emailverifiedat TIMESTAMP NULL DEFAULT NULL');
            $pdo->exec('UPDATE users SET emailverifiedat = NOW() WHERE emailverifiedat IS NULL');
        } else {
            $pdo->exec("ALTER TABLE users ADD COLUMN EmailVerifiedAt DATETIME NULL DEFAULT NULL AFTER Status");
            $pdo->exec("UPDATE users SET EmailVerifiedAt = NOW() WHERE EmailVerifiedAt IS NULL");
        }
    }
} catch (Exception $e) {
    error_log("Email verification column warning: " . $e->getMessage());
}

try {
    $colCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
          WHERE table_name = 'users' AND column_name = :col"
    );
    $columnName = DB_DRIVER === 'pgsql' ? 'lastactiveat' : 'LastActiveAt';
    $colCheck->execute([':col' => $columnName]);
    if ((int)$colCheck->fetchColumn() === 0) {
        if (DB_DRIVER === 'pgsql') {
            $pdo->exec('ALTER TABLE users ADD COLUMN lastactiveat TIMESTAMP NULL DEFAULT NULL');
        } else {
            $pdo->exec("ALTER TABLE users ADD COLUMN LastActiveAt DATETIME NULL DEFAULT NULL AFTER CreatedAt");
        }
    }
} catch (Exception $e) {
    error_log("Presence tracking column warning: " . $e->getMessage());
}

/* -----------------------------
   HELPER FUNCTIONS
------------------------------*/

function redirect($url) {
    if (strpos($url, 'page=login') !== false) {
        $currentPage = $_GET['page'] ?? '';
        if (!empty($currentPage) && $currentPage !== 'login' && $currentPage !== 'logout') {
            $queryString = $_SERVER['QUERY_STRING'] ?? '';
            $_SESSION['return_to'] = $queryString ? 'index.php?' . $queryString : 'index.php?page=' . $currentPage;
        }
    }

    // Allow both relative and absolute URLs
    if (strpos($url, 'http') === 0) {
        header("Location: " . $url);
    } else {
        header("Location: " . BASE_URL . $url);
    }
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('?page=login');
    }
}

function requireRole($role) {
    requireLogin();

    if (!hasRole($role)) {
        http_response_code(403);
        die("403 Unauthorized Access");
    }
}

function sendStudentFeedbackEmail($student, $feedbackMessage) {
    if (SMTP_HOST === '') {
        return false;
    }

    $to = defined('PLATFORM_FEEDBACK_EMAIL') ? PLATFORM_FEEDBACK_EMAIL : 'univelearning01@gmail.com';
    $fromEmail = str_replace(["\r", "\n"], '', MAIL_FROM);
    $fromName = str_replace(["\r", "\n"], '', MAIL_FROM_NAME);
    $replyTo = str_replace(["\r", "\n"], '', $student['Email']);
    $subject = APP_NAME . ' student feedback';
    $body = "A student sent feedback for " . APP_NAME . ".\n\n"
        . "Student: " . $student['Username'] . "\n"
        . "Email: " . $student['Email'] . "\n"
        . "Submitted: " . date('Y-m-d H:i:s') . "\n\n"
        . "Feedback:\n" . $feedbackMessage . "\n";

    $remote = (SMTP_SECURE === 'ssl' ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT;
    $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);

    if (!$socket) {
        error_log("Feedback SMTP connection failed: " . $errstr . " (" . $errno . ")");
        return false;
    }

    stream_set_timeout($socket, 20);

    try {
        smtpFeedbackExpect($socket, [220]);
        $serverName = parse_url(BASE_URL, PHP_URL_HOST) ?: 'localhost';

        smtpFeedbackCommand($socket, 'EHLO ' . $serverName, [250]);

        if (SMTP_SECURE === 'tls') {
            smtpFeedbackCommand($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('Could not enable SMTP TLS encryption.');
            }
            smtpFeedbackCommand($socket, 'EHLO ' . $serverName, [250]);
        }

        if (SMTP_USERNAME !== '') {
            smtpFeedbackCommand($socket, 'AUTH LOGIN', [334]);
            smtpFeedbackCommand($socket, base64_encode(SMTP_USERNAME), [334]);
            smtpFeedbackCommand($socket, base64_encode(SMTP_PASSWORD), [235]);
        }

        smtpFeedbackCommand($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        smtpFeedbackCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtpFeedbackCommand($socket, 'DATA', [354]);

        $headers = [
            'Date: ' . date('r'),
            'From: ' . formatFeedbackEmailAddress($fromEmail, $fromName),
            'To: <' . $to . '>',
            'Reply-To: <' . $replyTo . '>',
            'Subject: ' . encodeFeedbackHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        $message = str_replace(["\r\n.", "\n."], ["\r\n..", "\n.."], $message);
        fwrite($socket, str_replace("\n", "\r\n", str_replace("\r\n", "\n", $message)) . "\r\n.\r\n");

        smtpFeedbackExpect($socket, [250]);
        smtpFeedbackCommand($socket, 'QUIT', [221]);
        fclose($socket);
        return true;
    } catch (Exception $e) {
        error_log("Feedback SMTP send failed: " . $e->getMessage());
        fclose($socket);
        return false;
    }
}

function smtpFeedbackCommand($socket, $command, array $expectedCodes) {
    fwrite($socket, $command . "\r\n");
    return smtpFeedbackExpect($socket, $expectedCodes);
}

function smtpFeedbackExpect($socket, array $expectedCodes) {
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

function formatFeedbackEmailAddress($email, $name) {
    $cleanName = addcslashes(str_replace(["\r", "\n", '"'], '', $name), '\\');
    return '"' . $cleanName . '" <' . $email . '>';
}

function encodeFeedbackHeader($text) {
    if (preg_match('/[^\x20-\x7E]/', $text)) {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }

    return str_replace(["\r", "\n"], '', $text);
}
