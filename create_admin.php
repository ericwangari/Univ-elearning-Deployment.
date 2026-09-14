<?php
require_once __DIR__ . '/config/config.php';

$message = '';
$error = '';

// Auto-create users table if it does not exist
try {
    if (DB_DRIVER === 'pgsql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                userid           SERIAL PRIMARY KEY,
                username         VARCHAR(50)  NOT NULL UNIQUE,
                email            VARCHAR(100) NOT NULL UNIQUE,
                password         VARCHAR(255) NOT NULL,
                usertype         VARCHAR(20)  NOT NULL DEFAULT 'Student'
                                       CHECK (usertype IN ('Admin','Instructor','Student')),
                status           VARCHAR(20)  NOT NULL DEFAULT 'Approved'
                                       CHECK (status IN ('Pending','Approved','Rejected')),
                emailverifiedat  TIMESTAMP    NULL DEFAULT NULL,
                createdat        TIMESTAMP    NOT NULL DEFAULT NOW(),
                lastactiveat     TIMESTAMP    NULL DEFAULT NULL
            );
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                UserID INT AUTO_INCREMENT PRIMARY KEY,
                Username VARCHAR(50) NOT NULL UNIQUE,
                Email VARCHAR(100) NOT NULL UNIQUE,
                Password VARCHAR(255) NOT NULL,
                UserType ENUM('Admin', 'Instructor', 'Student') NOT NULL DEFAULT 'Student',
                Status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Approved',
                EmailVerifiedAt DATETIME NULL DEFAULT NULL,
                CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                LastActiveAt DATETIME NULL DEFAULT NULL
            ) ENGINE=InnoDB;
        ");
    }
} catch (Exception $e) {
    $error = "Schema check error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Please fill in all fields (username, email, and password).";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Check if user exists by username or email
            $stmt = $pdo->prepare("SELECT " . (DB_DRIVER === 'pgsql' ? 'userid' : 'UserID') . " FROM users WHERE lower(Username) = lower(?) OR lower(Email) = lower(?) LIMIT 1");
            $stmt->execute([$username, $email]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                $userId = $existingUser[DB_DRIVER === 'pgsql' ? 'userid' : 'UserID'];
                $stmt = $pdo->prepare("UPDATE users SET Password = ?, UserType = 'Admin', Status = 'Approved', EmailVerifiedAt = NOW() WHERE " . (DB_DRIVER === 'pgsql' ? 'userid' : 'UserID') . " = ?");
                $stmt->execute([$hashedPassword, $userId]);
                $message = "Admin account ('" . htmlspecialchars($username) . "') updated successfully with your new password!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (Username, Email, Password, UserType, Status, EmailVerifiedAt) VALUES (?, ?, ?, 'Admin', 'Approved', NOW())");
                $stmt->execute([$username, $email, $hashedPassword]);
                $message = "New Admin account ('" . htmlspecialchars($username) . "') created successfully!";
            }
        } catch (Exception $e) {
            $error = "Error creating admin account: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Custom Admin Account - Univ E-Learning</title>
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: white; border-radius: 12px; padding: 32px; width: 100%; max-width: 440px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; color: #1e293b; font-size: 1.5rem; text-align: center; }
        p.subtitle { text-align: center; color: #64748b; font-size: 0.9rem; margin-bottom: 24px; }
        .group { margin-bottom: 18px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; color: #334155; font-size: 0.9rem; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; outline: none; transition: border 0.2s;
        }
        input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
        button { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        button:hover { background: #1d4ed8; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .footer-link { text-align: center; margin-top: 20px; }
        .footer-link a { color: #2563eb; text-decoration: none; font-weight: 600; }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Create Custom Admin</h2>
        <p class="subtitle">Set your own Admin username, email, and password</p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?= $message ?>
                <div style="margin-top: 10px;">
                    <a href="index.php?page=login" style="color: #15803d; font-weight: bold;">Click here to Login &rarr;</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="group">
                <label for="username">Admin Username</label>
                <input type="text" id="username" name="username" placeholder="e.g. admin" required value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>">
            </div>

            <div class="group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" placeholder="e.g. admin@univ.edu" required value="<?= htmlspecialchars($_POST['email'] ?? 'admin@univ.edu') ?>">
            </div>

            <div class="group">
                <label for="password">New Admin Password</label>
                <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required value="<?= htmlspecialchars($_POST['password'] ?? 'admin123') ?>">
            </div>

            <button type="submit">Create / Update Admin Account</button>
        </form>

        <div class="footer-link">
            <a href="index.php?page=login">&larr; Back to Login Page</a>
        </div>
    </div>
</body>
</html>
