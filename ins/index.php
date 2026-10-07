<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$viewpage = trim((string)($_GET['p'] ?? 'dashboard'));

$allowedPages = [
    'dashboard' => 'dashboard.php',
    'create' => 'create.php',
    'view' => 'view.php',
    'edit' => 'edit.php',
    'delete' => 'delete.php',
    'print' => 'print.php',
    'admin' => 'admin.php',
    'companies' => 'companies.php',
    'vehicle-models' => 'vehicle-models.php'
];

if (!isset($allowedPages[$viewpage])) {
    http_response_code(404);
    $viewpage = 'dashboard';
}

$pageFile = __DIR__ . '/pages/' . $allowedPages[$viewpage];

if (!is_file($pageFile)) {
    http_response_code(500);
    exit('Page not found.');
}

require $pageFile;
