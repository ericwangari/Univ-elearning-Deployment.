<?php
require_once 'config/config.php';

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Connected successfully! Tables in database:\n";
    print_r($tables);

    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "\nUser count: " . $userCount . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
