<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/core.php';
require dirname(__DIR__) . '/src/api.php';

if (($_GET['r'] ?? '') === 'api' || ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json'))) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin) {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $oh = parse_url($origin, PHP_URL_HOST);
        $op = parse_url($origin, PHP_URL_PORT);
        $originHost = $op ? $oh . ':' . $op : $oh;
        if ($originHost && $host && strcasecmp($originHost, $host) !== 0) {
            haven_json(['ok' => false, 'error' => 'Cross-origin request blocked'], 403);
        }
    }
    haven_handle_api();
}

$installed = haven_installed();
require dirname(__DIR__) . '/pages/ALLPAGES.php';
