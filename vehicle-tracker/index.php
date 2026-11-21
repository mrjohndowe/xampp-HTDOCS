<?php
// Vehicle Tracker - entry point
declare(strict_types = 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Not installed yet: send to the onboarding wizard.
if (!file_exists(__DIR__ . '/config.php') || !file_exists(__DIR__ . '/config.lock')) {
    header('Location: install/');
    exit;
}

// Default currency/unit into session for views
if (!isset($_SESSION['vt_currency'])) {
    try {
        require_once __DIR__ . '/includes/db.php';
        /** @var PDO $pdo */
        $row = $pdo->query("SELECT currency, distance_unit, site_name FROM settings WHERE id = 1")->fetch();
        if ($row) {
            $_SESSION['vt_currency'] = $row['currency'];
            $_SESSION['vt_distance_unit'] = $row['distance_unit'];
            $_SESSION['vt_site_name'] = $row['site_name'];
        }
    } catch (Throwable $e) {
        // Not installed yet; installer will handle.
    }
}

require __DIR__ . '/views/dashboard.php';
