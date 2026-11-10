<?php
declare(strict_types=1);

namespace LifeLedger;

ini_set('display_errors', '0');

final class Bootstrap
{
    public const VERSION = '1.0.0';
    public const SCHEMA_VERSION = 1;

    public static function baseDir(): string
    {
        return dirname(__DIR__);
    }

    public static function path(string ...$segments): string
    {
        $base = rtrim(self::baseDir(), '/\\');
        $clean = array_map(static fn(string $s): string => trim($s, '/\\'), $segments);
        return $base . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $clean);
    }

    public static function checkExtensions(): array
    {
        $required = [
            'pdo_sqlite' => 'extension=pdo_sqlite',
            'sqlite3'    => 'extension=sqlite3',
            'pdo_mysql'  => 'extension=pdo_mysql',
            'openssl'    => 'extension=openssl',
            'mbstring'   => 'extension=mbstring',
            'fileinfo'   => 'extension=fileinfo',
            'json'       => 'Built-in JSON extension',
        ];
        $missing = [];
        foreach ($required as $ext => $iniLine) {
            if (!extension_loaded($ext)) {
                $missing[] = ['ext' => $ext, 'ini' => $iniLine];
            }
        }
        return $missing;
    }

    public static function baseUrl(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir = str_replace('\\', '/', dirname($scriptName));
        if ($dir === '/' || $dir === '.') {
            return '';
        }
        return rtrim($dir, '/');
    }

    public static function url(string $query = ''): string
    {
        $base = self::baseUrl();
        $prefix = $base === '' ? 'index.php' : $base . '/index.php';
        return $query === '' ? $prefix : $prefix . '?' . ltrim($query, '?');
    }
}
