<?php
/**
 * Sample configuration. Copy to storage/config.php.
 * Placeholders only — never commit real credentials or keys.
 */
return [
    'app_name' => 'Haven',
    'driver' => 'sqlite', // sqlite | mysql
    'sqlite_path' => __DIR__ . '/haven.sqlite',
    'mysql' => [
        'host' => '127.0.0.1',
        'database' => 'haven',
        'user' => 'haven',
        'password' => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],
    'timezone' => 'UTC',
    'date_format' => 'YYYY-MM-DD',
    'registration_enabled' => false,
    'session_hours' => 12,
];
