<?php
require_once 'config/config.php';

function setupMessage($text, $ok = true) {
    $color = $ok ? 'green' : 'red';
    echo "<p style='color: {$color};'>" . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . "</p>";
}

function tableExists($pdo, $table) {
    if (DB_DRIVER === 'pgsql') {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = lower(?)");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return $stmt->rowCount() > 0;
}

function columnExists($pdo, $table, $column) {
    if (DB_DRIVER === 'pgsql') {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = lower(?) AND column_name = lower(?)");
        $stmt->execute([$table, $column]);
        return (bool) $stmt->fetchColumn();
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
    return $stmt->rowCount() > 0;
}

echo "<h1>Deployment Setup Utility</h1>";

try {
    $pdo->query("SELECT 1");
    setupMessage('Database connection successful.');
} catch (Exception $e) {
    setupMessage('Database connection failed: ' . $e->getMessage(), false);
    echo "<p>Please check DATABASE_URL for Supabase, or DB_HOST/DB_NAME/DB_USER/DB_PASS for MySQL.</p>";
    exit;
}

$requiredTables = ['users', 'courses', 'enrollments'];
$missing = false;
foreach ($requiredTables as $table) {
    if (!tableExists($pdo, $table)) {
        $missing = true;
        break;
    }
}

if ($missing) {
    $schemaFile = DB_DRIVER === 'pgsql' ? 'database/schema_pgsql.sql' : 'database/schema.sql';
    echo "<p>Tables are missing. Importing {$schemaFile}...</p>";
    $sql = file_get_contents($schemaFile);

    if (DB_DRIVER !== 'pgsql') {
        $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS.*;/i', '', $sql);
        $sql = preg_replace('/USE .*;/i', '', $sql);
    }

    try {
        $pdo->exec($sql);
        setupMessage($schemaFile . ' imported successfully.');
    } catch (Exception $e) {
        setupMessage('Error importing schema: ' . $e->getMessage(), false);
    }
} else {
    setupMessage('Tables already exist. No import needed.');
}

try {
    if (DB_DRIVER === 'pgsql') {
        if (!columnExists($pdo, 'users', 'EmailVerifiedAt')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN EmailVerifiedAt TIMESTAMP NULL DEFAULT NULL');
            $pdo->exec('UPDATE users SET EmailVerifiedAt = NOW() WHERE EmailVerifiedAt IS NULL');
            setupMessage('Added users.EmailVerifiedAt column.');
        }
        if (!columnExists($pdo, 'users', 'LastActiveAt')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN LastActiveAt TIMESTAMP NULL DEFAULT NULL');
            setupMessage('Added users.LastActiveAt column.');
        }
    } else {
        if (!columnExists($pdo, 'users', 'Status')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN Status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Approved' AFTER UserType");
            setupMessage('Added users.Status column.');
        }

        $pdo->exec("UPDATE users SET Status = 'Approved' WHERE Status IS NULL OR Status = ''");

        $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
            TokenID INT AUTO_INCREMENT PRIMARY KEY,
            UserID INT NOT NULL,
            TokenHash CHAR(64) NOT NULL UNIQUE,
            ExpiresAt DATETIME NOT NULL,
            UsedAt DATETIME NULL DEFAULT NULL,
            CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_reset_user (UserID),
            INDEX idx_password_reset_expires (ExpiresAt),
            FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE
        ) ENGINE=InnoDB;");
        setupMessage('Password reset token table is ready.');

        if (!columnExists($pdo, 'users', 'EmailVerifiedAt')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN EmailVerifiedAt DATETIME NULL DEFAULT NULL AFTER Status");
            $pdo->exec("UPDATE users SET EmailVerifiedAt = NOW() WHERE EmailVerifiedAt IS NULL");
            setupMessage('Added users.EmailVerifiedAt column.');
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS email_verification_tokens (
            TokenID INT AUTO_INCREMENT PRIMARY KEY,
            UserID INT NOT NULL,
            TokenHash CHAR(64) NOT NULL UNIQUE,
            ExpiresAt DATETIME NOT NULL,
            UsedAt DATETIME NULL DEFAULT NULL,
            CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_verification_user (UserID),
            INDEX idx_email_verification_expires (ExpiresAt),
            FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE
        ) ENGINE=InnoDB;");
        setupMessage('Email verification token table is ready.');
    }
} catch (Exception $e) {
    setupMessage('Error applying schema updates: ' . $e->getMessage(), false);
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ((int) $stmt->fetchColumn() <= 0 && DB_DRIVER !== 'pgsql') {
        echo "<p>No seed data found. Importing database/seed.sql...</p>";
        $pdo->exec(file_get_contents('database/seed.sql'));
        setupMessage('Seed data imported successfully.');
    }
} catch (Exception $e) {
    setupMessage('Error checking seed data: ' . $e->getMessage(), false);
}

echo "<h2>Environment Status</h2>";
echo "<ul>";
echo "<li><strong>APP_NAME:</strong> " . APP_NAME . "</li>";
echo "<li><strong>BASE_URL:</strong> " . BASE_URL . "</li>";
echo "<li><strong>DB_DRIVER:</strong> " . DB_DRIVER . "</li>";
echo "<li><strong>DB_HOST:</strong> " . DB_HOST . "</li>";
echo "<li><strong>DB_NAME:</strong> " . DB_NAME . "</li>";
echo "</ul>";
echo "<p><a href='index.php'>Go to Homepage</a></p>";
?>
