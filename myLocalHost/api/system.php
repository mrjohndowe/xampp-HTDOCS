<?php

declare(strict_types=1);

header('Content-Type: application/json');

date_default_timezone_set('America/Denver');

$response = [

    "success" => true,

    "hostname" => gethostname(),

    "server" => php_uname(),

    "php" => [

        "version" => PHP_VERSION,

        "sapi" => php_sapi_name()

    ],

    "cpu" => [],

    "memory" => [],

    "disk" => []

];

/*
|--------------------------------------------------------------------------
| CPU Usage
|--------------------------------------------------------------------------
*/

$response["cpu"] = [
    "usage" => null
];

if (stripos(PHP_OS, "WIN") === 0) {

    @exec('wmic cpu get loadpercentage /value', $output);

    foreach ($output as $line) {

        if (preg_match('/LoadPercentage=(\d+)/', $line, $match)) {

            $response["cpu"]["usage"] = (int)$match[1];

            break;
        }
    }
} elseif (function_exists("sys_getloadavg")) {

    $cpu = sys_getloadavg();

    $response["cpu"] = [
        "1min" => $cpu[0] ?? 0,
        "5min" => $cpu[1] ?? 0,
        "15min" => $cpu[2] ?? 0
    ];
}

/*
|--------------------------------------------------------------------------
| Memory
|--------------------------------------------------------------------------
*/

$response["memory"] = [

    "usage" => memory_get_usage(true),

    "peak" => memory_get_peak_usage(true),

    "limit" => ini_get("memory_limit")

];

/*
|--------------------------------------------------------------------------
| Disk
|--------------------------------------------------------------------------
*/

$root = realpath($_SERVER["DOCUMENT_ROOT"]);

$total = disk_total_space($root);

$free = disk_free_space($root);

$used = $total - $free;

$response["disk"] = [

    "total" => $total,

    "free" => $free,

    "used" => $used,

    "percent" => round(($used / $total) * 100, 1)

];

/*
|--------------------------------------------------------------------------
| Uptime
|--------------------------------------------------------------------------
*/

$response["uptime"] = null;

if (stripos(PHP_OS, "WIN") === 0) {

    @exec("net statistics workstation", $output);

    foreach ($output as $line) {

        if (stripos($line, "Statistics since") !== false) {

            $response["uptime"] = trim($line);

            break;
        }
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
