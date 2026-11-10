<?php
declare(strict_types=1);

namespace LifeLedger;

final class Config
{
    public static function lookupCandidates(?string $cliPath = null): array
    {
        $list = [];
        if ($cliPath !== null && $cliPath !== '') {
            $list[] = ['path' => self::resolveRel($cliPath), 'source' => 'cli:' . $cliPath];
        }
        $envCfg = getenv('LIFELEDGER_CONFIG');
        if ($envCfg !== false && $envCfg !== '') {
            $list[] = ['path' => self::resolveRel($envCfg), 'source' => 'env:LIFELEDGER_CONFIG'];
        }
        $list[] = ['path' => Bootstrap::path('lifeledger.config.json'), 'source' => 'file:./lifeledger.config.json'];
        $list[] = ['path' => Bootstrap::path('config', 'lifeledger.config.json'), 'source' => 'file:./config/lifeledger.config.json'];
        return $list;
    }

    public static function resolveRel(string $p): string
    {
        if (str_starts_with($p, chr(92)) || str_starts_with($p, '/') || preg_match('/^[A-Za-z]:/', $p)) {
            return $p;
        }
        return Bootstrap::path($p);
    }

    public static function resolveEnvRef(mixed $val): string
    {
        if (!is_string($val)) return '';
        if (str_starts_with($val, 'env:')) {
            $v = getenv(trim(substr($val, 4)));
            return $v === false ? '' : $v;
        }
        return $val;
    }

    public static function check(?string $cliPath = null): array
    {
        $found = null;
        foreach (self::lookupCandidates($cliPath) as $c) {
            if (is_file($c['path'])) {
                $found = $c;
                break;
            }
        }
        if ($found === null) {
            return [
                'status' => 'MISSING',
                'resolvedPath' => null,
                'source' => 'none',
                'errors' => [['key' => 'configFile', 'message' => 'Configuration file is missing. Run onboarding.']],
            ];
        }

        $raw = @file_get_contents($found['path']);
        if ($raw === false || trim($raw) === '') {
            return [
                'status' => 'INVALID',
                'resolvedPath' => $found['path'],
                'source' => $found['source'],
                'errors' => [['key' => 'configFile', 'message' => 'Configuration file is empty or unreadable.']],
            ];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [
                'status' => 'INVALID',
                'resolvedPath' => $found['path'],
                'source' => $found['source'],
                'errors' => [['key' => 'configFile', 'message' => 'Configuration file contains invalid JSON.']],
            ];
        }

        $errors = [];
        $dbMode = getenv('DB_MODE') ?: ($data['dbMode'] ?? '');
        if ($dbMode === 'mysql') {
            $errors[] = ['key' => 'dbMode', 'message' => 'DB_MODE=mysql is forbidden. SQLite is the only permitted primary database.'];
        } elseif ($dbMode !== 'sqlite' && $dbMode !== 'dual') {
            $errors[] = ['key' => 'dbMode', 'message' => 'dbMode must be "sqlite" or "dual".'];
        }

        $sqlitePath = getenv('SQLITE_PATH') ?: ($data['sqlitePath'] ?? './data/lifeledger.sqlite');
        $resolvedSqlite = self::resolveRel((string)$sqlitePath);
        $parent = dirname($resolvedSqlite);
        if (!is_dir($parent)) {
            @mkdir($parent, 0770, true);
        }
        if (!is_writable($parent)) {
            $errors[] = ['key' => 'sqlitePath', 'message' => 'SQLite parent directory is not writable.'];
        }

        $app = is_array($data['app'] ?? null) ? $data['app'] : [];
        $mysql = is_array($data['mysql'] ?? null) ? $data['mysql'] : [];

        $prefixEnv = getenv('MYSQL_TABLE_PREFIX');
        $tablePrefix = $prefixEnv !== false ? $prefixEnv : ($mysql['tablePrefix'] ?? '');
        if (!self::validMysqlTablePrefix($tablePrefix)) {
            $errors[] = ['key' => 'mysql.tablePrefix', 'message' => 'Invalid MySQL table prefix.'];
        }

        $encKey = getenv('APP_ENCRYPTION_KEY') ?: self::resolveEnvRef($app['encryptionKey'] ?? '');
        $decodedKey = base64_decode((string)$encKey, true);
        if ($decodedKey === false || strlen($decodedKey) !== 32) {
            $errors[] = ['key' => 'app.encryptionKey', 'message' => 'encryptionKey must be valid base64 decoding to 32 bytes.'];
        }

        $sessSecret = getenv('SESSION_SECRET') ?: self::resolveEnvRef($app['sessionSecret'] ?? '');
        if (strlen((string)$sessSecret) < 16) {
            $errors[] = ['key' => 'app.sessionSecret', 'message' => 'sessionSecret must be at least 16 characters.'];
        }

        $tz = getenv('TZ') ?: ($app['timeZone'] ?? 'UTC');
        if (!in_array($tz, timezone_identifiers_list(), true)) {
            $errors[] = ['key' => 'app.timeZone', 'message' => 'Invalid IANA time zone.'];
        }

        if (count($errors) > 0) {
            return [
                'status' => 'INVALID',
                'resolvedPath' => $found['path'],
                'source' => $found['source'],
                'errors' => $errors,
            ];
        }

        return [
            'status' => 'OK',
            'resolvedPath' => $found['path'],
            'source' => $found['source'],
            'errors' => [],
            'config' => [
                'version' => (int)($data['version'] ?? 1),
                'dbMode' => $dbMode,
                'sqlitePath' => (string)$sqlitePath,
                'mysql' => [
                    'host' => getenv('MYSQL_HOST') ?: ($mysql['host'] ?? '127.0.0.1'),
                    'port' => (int)(getenv('MYSQL_PORT') ?: ($mysql['port'] ?? 3306)),
                    'database' => getenv('MYSQL_DATABASE') ?: ($mysql['database'] ?? 'lifeledger'),
                    'tablePrefix' => $tablePrefix,
                    'user' => getenv('MYSQL_USER') ?: ($mysql['user'] ?? 'lifeledger'),
                    'password' => getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : self::resolveEnvRef($mysql['password'] ?? ''),
                ],
                'app' => [
                    'encryptionKey' => (string)$encKey,
                    'sessionSecret' => (string)$sessSecret,
                    'port' => (int)(getenv('PORT') ?: ($app['port'] ?? 3000)),
                    'timeZone' => (string)$tz,
                    'theme' => (string)($app['theme'] ?? 'system'),
                ],
            ],
        ];
    }

    public static function validMysqlTablePrefix($prefix): bool
    {
        return is_string($prefix) && ($prefix === '' || preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,31}$/D', $prefix) === 1);
    }

    public static function writeAtomic(array $cfg): string
    {
        $dest = Bootstrap::path('config', 'lifeledger.config.json');
        @mkdir(dirname($dest), 0770, true);
        $tmp = $dest . '.tmp.' . getmypid();
        file_put_contents($tmp, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        rename($tmp, $dest);
        @chmod($dest, 0600);
        return $dest;
    }

    public static function redacted(array $check): array
    {
        if (!isset($check['config'])) {
            return $check;
        }
        $c = $check['config'];
        $c['mysql']['password'] = $c['mysql']['password'] !== '' ? '***' : '(empty)';
        $c['app']['encryptionKey'] = '***';
        $c['app']['sessionSecret'] = '***';
        return [
            'status' => $check['status'],
            'resolvedPath' => $check['resolvedPath'],
            'source' => $check['source'],
            'config' => $c,
        ];
    }
}
