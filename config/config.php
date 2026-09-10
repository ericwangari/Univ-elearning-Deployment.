<?php
require_once __DIR__ . '/database_compat.php';
require_once __DIR__ . '/CookieSessionHandler.php';
require_once __DIR__ . '/languages.php';

/* -----------------------------
   LOAD ENV FILE (IF EXISTS)
------------------------------*/
$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (getenv($name) === false) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

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
define('SUPPORT_EMAIL', $localConfig['support_email'] ?? getenv('SUPPORT_EMAIL') ?: PLATFORM_FEEDBACK_EMAIL);

$isVercelRuntime = getenv('VERCEL') === '1' || getenv('VERCEL_URL') !== false || getenv('VERCEL_PROJECT_PRODUCTION_URL') !== false;
define('IS_LOCAL_DEV', !$isVercelRuntime && in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1', '::1'], true));

/* -----------------------------
   START SESSION (STATELESS FOR VERCEL)
------------------------------*/
if (session_status() === PHP_SESSION_NONE) {
    if ($isVercelRuntime) {
        $sessionSecret = getenv('APP_SECRET') ?: getenv('DATABASE_URL') ?: 'univ_elearning_secret_key_2026';
        $cookieHandler = new CookieSessionHandler($sessionSecret);
        session_set_save_handler($cookieHandler, true);
    } else {
        $baseUrlParts = parse_url(BASE_URL);
        $basePath = $baseUrlParts['path'] ?? '/';
        $cookiePath = rtrim($basePath, '/');
        $cookiePath = $cookiePath === '' ? '/' : $cookiePath . '/';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookiePath,
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
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
        $supabaseRef = strtolower($matches[1]);
        $directHost = 'db.' . $supabaseRef . '.supabase.co';
        try {
            $pdo = createAppPdoConnection(DB_DRIVER, $directHost, '5432', DB_NAME, 'postgres', DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $fallbackException) {
            $msg = "Database Connection Failed:\n";
            $msg .= "• Primary connection attempt (" . DB_HOST . ":" . DB_PORT . ") failed: " . $e->getMessage() . "\n";
            $msg .= "• Fallback connection attempt (" . $directHost . ":5432) failed: " . $fallbackException->getMessage() . "\n\n";
            $msg .= "Troubleshooting Tips:\n";
            $msg .= "1. Verify database credentials in .env or config/hosting.local.php.\n";
            $msg .= "2. Note: Direct Supabase hostnames (db.<ref>.supabase.co) only support IPv6 unless an IPv4 add-on is active. Ensure you use the Supabase Connection Pooler hostname (e.g. aws-0-[region].pooler.supabase.com) on IPv4 networks.\n";
            $msg .= "3. Confirm that your Supabase database project is active and not paused in the Supabase Dashboard.";
            die($msg);
        }
    } else {
        $msg = "Database Connection Failed: " . $e->getMessage() . "\n\n";
        $msg .= "Troubleshooting Tips:\n";
        $msg .= "1. Check DB_HOST, DB_PORT, DB_NAME, DB_USER, and DB_PASS in .env or config/hosting.local.php.\n";
        $msg .= "2. For Supabase, check DATABASE_URL in .env or set environment variables in your deployment settings.";
        die($msg);
    }
}


// Schema maintenance: add missing tables the app depends on.
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
        "CREATE TABLE IF NOT EXISTS messages (
            MessageID INT AUTO_INCREMENT PRIMARY KEY,
            SenderID INT NOT NULL,
            ReceiverID INT NOT NULL,
            MessageText TEXT NOT NULL,
            SentAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            IsRead TINYINT(1) NOT NULL DEFAULT 0,
            INDEX idx_messages_sender_receiver (SenderID, ReceiverID),
            INDEX idx_messages_receiver_read (ReceiverID, IsRead),
            INDEX idx_messages_sent_at (SentAt),
            FOREIGN KEY (SenderID) REFERENCES users(UserID) ON DELETE CASCADE,
            FOREIGN KEY (ReceiverID) REFERENCES users(UserID) ON DELETE CASCADE
        ) ENGINE=InnoDB;",
    ];

    foreach ($schemaUpdates as $schemaUpdate) {
        try {
            $pdo->exec($schemaUpdate);
        } catch (Exception $e) {
            error_log("Schema maintenance warning: " . $e->getMessage());
        }
    }
} else {
    $schemaUpdates = [
        "CREATE TABLE IF NOT EXISTS instructor_courses (
            instructorcourseid SERIAL PRIMARY KEY,
            instructorid INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
            courseid INT NOT NULL REFERENCES courses(courseid) ON DELETE CASCADE,
            assignedat TIMESTAMP NOT NULL DEFAULT NOW(),
            UNIQUE (instructorid, courseid)
        )",
        "CREATE TABLE IF NOT EXISTS messages (
            messageid SERIAL PRIMARY KEY,
            senderid INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
            receiverid INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
            messagetext TEXT NOT NULL,
            sentat TIMESTAMP NOT NULL DEFAULT NOW(),
            isread BOOLEAN NOT NULL DEFAULT FALSE
        )",
    ];

    foreach ($schemaUpdates as $schemaUpdate) {
        try {
            $pdo->exec($schemaUpdate);
        } catch (Exception $e) {
            error_log("PostgreSQL schema maintenance warning: " . $e->getMessage());
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

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // Allow both relative and absolute URLs
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        header("Location: " . $url);
    } else {
        $target = ltrim($url, '/');
        header("Location: " . $target);
    }
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['user_type']) && strcasecmp($_SESSION['user_type'], $role) === 0;
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
