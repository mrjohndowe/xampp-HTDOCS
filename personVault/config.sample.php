<?php
// Documentation template only. The installer writes live configuration outside
// the web root and keeps the encryption key in a separate file.
return [
    'database' => [
        'driver' => 'sqlite', // sqlite or mysql
        'sqlite_path' => 'REPLACE_WITH_PRIVATE_PATH',
        'mysql_host' => 'REPLACE_WITH_HOST',
        'mysql_port' => 3306,
        'mysql_database' => 'REPLACE_WITH_DATABASE',
        'mysql_username' => 'REPLACE_WITH_USERNAME',
        'mysql_password' => 'REPLACE_WITH_PASSWORD',
        'mysql_charset' => 'utf8mb4',
    ],
    'encryption_key_file' => 'REPLACE_WITH_PRIVATE_KEY_FILE_PATH',
    'app_name' => 'PersonVault',
    'timezone' => 'UTC',
];
