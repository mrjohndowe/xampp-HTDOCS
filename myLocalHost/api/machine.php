<?php

declare(strict_types=1);

header('Content-Type: application/json');

function formatBytes(int|float $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max(0, $bytes);

    $power = floor(($bytes ? log($bytes) : 0) / log(1024));

    $power = min($power, count($units) - 1);

    $bytes /= pow(1024, $power);

    return round($bytes, 2) . ' ' . $units[$power];
}

function getDisk(): array
{
    $drive = substr(__DIR__, 0, 2);

    $total = @disk_total_space($drive);

    $free = @disk_free_space($drive);

    $used = $total - $free;

    return [

        'total'   => formatBytes($total),

        'used'    => formatBytes($used),

        'free'    => formatBytes($free),

        'percent' => $total > 0
            ? round(($used / $total) * 100, 1)
            : 0

    ];
}

function getMemoryUsage(): string
{
    return formatBytes(memory_get_usage(true));
}

function getUptime(): array
{
    $seconds = 0;

    if (PHP_OS_FAMILY === 'Windows') {

        @exec('net statistics workstation', $output);

        foreach ($output as $line) {

            if (stripos($line, 'Statistics since') !== false) {

                $time = strtotime(

                    trim(

                        substr($line, strpos($line, 'since') + 5)

                    )

                );

                if ($time !== false) {

                    $seconds = time() - $time;

                }

                break;

            }

        }

    }

    return [

        'seconds' => $seconds,

        'human'   => match (true) {

            $seconds >= 86400 => floor($seconds / 86400) . 'd',

            $seconds >= 3600  => floor($seconds / 3600) . 'h',

            $seconds >= 60    => floor($seconds / 60) . 'm',

            default           => $seconds . 's'

        }

    ];
}

$uptime = getUptime();

echo json_encode(

    [

        'success' => true,

        'hostname' => gethostname(),

        'os' => php_uname(),

        'php_version' => PHP_VERSION,

        'php_sapi' => php_sapi_name(),

        'memory_limit' => ini_get('memory_limit'),

        'memory_usage' => getMemoryUsage(),

        'document_root' => str_replace(

            '\\',

            '/',

            $_SERVER['DOCUMENT_ROOT'] ?? ''

        ),

        'server_time' => date('Y-m-d H:i:s'),

        'uptime' => $uptime['human'],

        'uptime_seconds' => $uptime['seconds'],

        'disk' => getDisk()

    ],

    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES

);
