<?php
// Vehicle Tracker - database connection (PDO, prepared statements everywhere)
// Supports SQLite (primary, XAMPP-friendly) and MySQL.

declare(strict_types = 1);

$configFile = __DIR__ . '/../config.php';
if (!is_file($configFile)) {
    throw new RuntimeException('Configuration missing. Complete the onboarding wizard first.');
}
$config = require $configFile;

if (!isset($config) || !is_array($config)) {
    throw new RuntimeException('Configuration missing. Complete the onboarding wizard first.');
}

$driver = $config['driver'] ?? 'sqlite';

try {
    if ($driver === 'mysql') {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'] ?? '127.0.0.1',
            (int)($config['port'] ?? 3306),
            $config['dbname']
        );
        $pdo = new PDO(
            $dsn,
            $config['username'] ?? '',
            $config['password'] ?? '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))",
            ]
        );
    } else {
        $database = $config['database'] ?? __DIR__ . '/../data/vehicles.sqlite';
        if (!is_dir(dirname($database))) {
            @mkdir(dirname($database), 0777, true);
        }
        $dsn = 'sqlite:' . $database;
        $pdo = new PDO(
            $dsn,
            null,
            null,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        // Enforce foreign keys in SQLite
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

return $pdo;
