<?php
// Vehicle Tracker - serves the Python desktop app download
declare(strict_types = 1);

$APP_DIR = __DIR__;
$file = $_GET['file'] ?? '';

// Allow only the python entry point directly by name
$allowed = ['vehicle_tracker.py'];
$name = basename($file);
if (!in_array($name, $allowed, true)) {
    http_response_code(404);
    exit('Not found');
}

$path = $APP_DIR . '/desktop/' . $name;
if (!is_file($path)) {
    http_response_code(404);
    exit('File not found');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
