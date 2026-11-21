<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
if (!is_file(__DIR__ . '/../config.php') || !is_file(__DIR__ . '/../config.lock')) {
    header('Location: install/');
    exit;
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$row = $pdo->query('SELECT currency, distance_unit, site_name FROM settings WHERE id = 1')->fetch();
if ($row) {
    $_SESSION['vt_currency'] = $row['currency'];
    $_SESSION['vt_distance_unit'] = $row['distance_unit'];
    $_SESSION['vt_site_name'] = $row['site_name'];
}
