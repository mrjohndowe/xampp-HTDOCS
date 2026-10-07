<?php

declare(strict_types=1);

require_once __DIR__ . '/../assets/includes/functions.php';

$dbDir = __DIR__ . '/../database';

if (!is_dir($dbDir)) {
    mkdir($dbDir, 0775, true);
}

$dbFile = $dbDir . '/insurance.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS insurance_companies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            address TEXT,
            city TEXT,
            state TEXT,
            zip TEXT,
            phone TEXT,
            website TEXT,
            logo TEXT,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS insurance_cards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company_id INTEGER NOT NULL,
            insured_name TEXT NOT NULL,
            policy_number TEXT NOT NULL UNIQUE,
            vehicle_year INTEGER,
            vehicle_make TEXT,
            vehicle_model TEXT,
            vin TEXT,
            license_plate TEXT,
            effective_date TEXT NOT NULL,
            expiration_date TEXT NOT NULL,
            liability_bod TEXT,
            property_damage TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES insurance_companies(id) ON DELETE RESTRICT
        )
    ");

} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed: ' . e($e->getMessage()));
}
