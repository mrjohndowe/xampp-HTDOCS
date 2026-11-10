<?php
declare(strict_types=1);

namespace LifeLedger;

use PDO;

final class Database
{
    private static ?PDO $sqlite = null;
    private static ?string $sqlitePath = null;

    public const SYNCED_TABLES = [
        'users', 'personal_profile', 'phone_history', 'email_history', 'contacts',
        'accounts', 'bank_accounts', 'cards', 'events', 'event_links', 'reminders',
        'vehicles', 'vehicle_documents', 'documents', 'lists', 'list_items',
        'inventory_items', 'chores', 'receipts', 'journal_entries', 'incidents',
        'recipes', 'meal_plans', 'work_shifts', 'music_tracks', 'playlists',
        'playlist_items', 'business_inventory', 'income_entries', 'appointments',
        'work_orders', 'work_order_items', 'work_order_deliveries', 'websites',
        'attachments', 'audit_log'
    ];

    public static function sqlite(array $cfg): PDO
    {
        $path = Config::resolveRel($cfg['sqlitePath'] ?? './data/lifeledger.sqlite');
        if (self::$sqlite instanceof PDO && self::$sqlitePath === $path) {
            return self::$sqlite;
        }
        @mkdir(dirname($path), 0770, true);
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA busy_timeout = 5000;');
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA foreign_keys = ON;');
        self::$sqlite = $pdo;
        self::$sqlitePath = $path;
        return $pdo;
    }

    public static function mysql(array $cfg): PDO
    {
        if (($cfg['dbMode'] ?? 'sqlite') !== 'dual') {
            throw new \RuntimeException('MySQL connection forbidden when dbMode is sqlite.');
        }
        $m = $cfg['mysql'];
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $m['host'], (int)$m['port'], $m['database']);
        return new PDO($dsn, $m['user'], $m['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 4,
        ]);
    }

    public static function detectInstallState(?string $cliPath = null): array
    {
        $chk = Config::check($cliPath);
        if ($chk['status'] === 'MISSING') {
            return ['state' => 'fresh', 'reason' => 'Configuration file is missing.'];
        }
        if ($chk['status'] === 'INVALID') {
            return ['state' => 'incomplete', 'reason' => 'Configuration file is invalid.'];
        }
        $cfg = $chk['config'];
        $sqlitePath = Config::resolveRel($cfg['sqlitePath']);
        if (!is_file($sqlitePath)) {
            return ['state' => 'incomplete', 'reason' => 'SQLite database file not found.'];
        }
        try {
            $pdo = self::sqlite($cfg);
            $ver = (int)$pdo->query('SELECT MAX(version) FROM schema_migrations')->fetchColumn();
            if ($ver < Bootstrap::SCHEMA_VERSION) {
                return ['state' => 'incomplete', 'reason' => 'Migrations not applied.'];
            }
            $user = $pdo->query('SELECT id FROM users WHERE deleted_at IS NULL LIMIT 1')->fetch();
            if (!$user) {
                return ['state' => 'incomplete', 'reason' => 'No user account configured.'];
            }
            $rerun = $pdo->query("SELECT state_value FROM app_state WHERE state_key = 'onboarding_rerun'")->fetchColumn();
            if ($rerun === '1') return ['state' => 'incomplete', 'reason' => 'Onboarding was reopened from Settings.', 'rerunning' => true];
            return ['state' => 'configured', 'reason' => 'Installation complete and verified.'];
        } catch (\Throwable $e) {
            return ['state' => 'incomplete', 'reason' => $e->getMessage()];
        }
    }

    public static function migrateSqlite(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version INTEGER PRIMARY KEY, name VARCHAR(191) NOT NULL, applied_at VARCHAR(40) NOT NULL)');
        $ver = (int)$pdo->query('SELECT COALESCE(MAX(version), 0) FROM schema_migrations')->fetchColumn();
        if ($ver > Bootstrap::SCHEMA_VERSION) {
            throw new \RuntimeException('Database schema version is newer than application code.');
        }
        if ($ver >= 1) return;
        $pdo->beginTransaction();
        try {
            foreach (self::ddlStatements('sqlite') as $sql) {
                $pdo->exec($sql);
            }
            $pdo->exec('CREATE TABLE IF NOT EXISTS app_state (state_key VARCHAR(100) PRIMARY KEY, state_value TEXT NOT NULL, updated_at VARCHAR(40) NOT NULL)');
            $pdo->exec('CREATE TABLE IF NOT EXISTS sync_queue (id VARCHAR(36) PRIMARY KEY, entity_table VARCHAR(64) NOT NULL, entity_id VARCHAR(36) NOT NULL, operation VARCHAR(20) NOT NULL, payload_json TEXT NOT NULL, revision INTEGER NOT NULL, attempts INTEGER NOT NULL DEFAULT 0, next_retry_at VARCHAR(40) NOT NULL, last_error TEXT NULL, created_at VARCHAR(40) NOT NULL, synced_at VARCHAR(40) NULL)');
            $stmt = $pdo->prepare('INSERT INTO schema_migrations (version, name, applied_at) VALUES (1, ?, ?)');
            $stmt->execute(['initial_portable_schema', gmdate('c')]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function mysqlTable(string $prefix, string $table): string
    {
        if (!Config::validMysqlTablePrefix($prefix) || ($table !== 'schema_migrations' && !in_array($table, self::SYNCED_TABLES, true))) {
            throw new \RuntimeException('Invalid MySQL table name.');
        }
        return chr(96) . $prefix . $table . chr(96);
    }

    public static function migrateMysql(PDO $pdo, string $prefix = ''): void
    {
        $migrationTable = self::mysqlTable($prefix, 'schema_migrations');
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$migrationTable} (version INTEGER PRIMARY KEY, name VARCHAR(191) NOT NULL, applied_at VARCHAR(40) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $ver = (int)$pdo->query("SELECT COALESCE(MAX(version), 0) FROM {$migrationTable}")->fetchColumn();
        if ($ver > Bootstrap::SCHEMA_VERSION) {
            throw new \RuntimeException('Schema drift: MySQL schema exceeds app schema.');
        }
        if ($ver >= 1) return;
        foreach (self::ddlStatements('mysql') as $sql) {
            if (!preg_match('/^(CREATE TABLE IF NOT EXISTS |CREATE INDEX [a-z_]+ ON )([a-z_]+)(?= |[(])/', $sql, $match)) {
                throw new \RuntimeException('Unrecognized MySQL migration statement.');
            }
            try {
                $pdo->exec($match[1] . self::mysqlTable($prefix, $match[2]) . substr($sql, strlen($match[0])));
            } catch (\PDOException $error) {
                if ((int)($error->errorInfo[1] ?? 0) !== 1061) throw $error;
            }
        }
        $stmt = $pdo->prepare("INSERT INTO {$migrationTable} (version, name, applied_at) VALUES (1, ?, ?)");
        $stmt->execute(['initial_portable_schema', gmdate('c')]);
    }

    public static function upsertLocalFirst(PDO $pdo, string $table, array $row, string $dbMode): array
    {
        if (!in_array($table, self::SYNCED_TABLES, true)) throw new \RuntimeException('Invalid entity.');
        Crypto::assertNoCvv($row);
        $now = gmdate('Y-m-d\TH:i:s.000\Z');
        $id = (string)($row['id'] ?? Crypto::uuid());
        $pdo->exec('BEGIN IMMEDIATE');
        try {
            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch() ?: [];
            $stmt->closeCursor();
            $row = array_replace($existing, $row, [
                'id' => $id,
                'revision' => isset($existing['revision']) ? ((int)$existing['revision'] + 1) : 1,
                'created_at' => $existing['created_at'] ?? $row['created_at'] ?? $now,
                'updated_at' => $now,
                'deleted_at' => $row['deleted_at'] ?? null,
            ]);
            $cols = array_keys($row);
            $placeholders = implode(', ', array_fill(0, count($cols), '?'));
            $updates = implode(', ', array_map(static fn(string $c): string => "{$c} = excluded.{$c}", array_filter($cols, static fn(string $c): bool => $c !== 'id')));
            $ins = $pdo->prepare("INSERT INTO {$table} (" . implode(', ', $cols) . ") VALUES ({$placeholders}) ON CONFLICT(id) DO UPDATE SET {$updates} RETURNING *");
            $ins->execute(array_values($row));
            $saved = $ins->fetch();
            $ins->closeCursor();
            if ($dbMode === 'dual') self::enqueue($pdo, $table, $saved);
            $pdo->exec('COMMIT');
            return $saved;
        } catch (\Throwable $e) {
            $pdo->exec('ROLLBACK');
            throw $e;
        }
    }

    public static function enqueue(PDO $db, string $table, array $row): void
    {
        $now = gmdate('c');
        $db->prepare('INSERT INTO sync_queue (id, entity_table, entity_id, operation, payload_json, revision, attempts, next_retry_at, last_error, created_at, synced_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?, NULL, ?, NULL)')
            ->execute([Crypto::uuid(), $table, $row['id'], $row['deleted_at'] === null ? 'upsert' : 'delete',
                json_encode($row, JSON_THROW_ON_ERROR), $row['revision'], $now, $now]);
    }

    public static function drainQueue(array $cfg, int $maxSeconds = 2): array
    {
        if ($cfg['dbMode'] !== 'dual') return ['processed' => 0, 'remaining' => 0, 'error' => null];
        $sqlite = self::sqlite($cfg);
        $count = static fn(): int => (int)$sqlite->query('SELECT COUNT(*) FROM sync_queue WHERE synced_at IS NULL')->fetchColumn();
        $outage = $sqlite->query("SELECT state_value FROM app_state WHERE state_key = 'simulated_mysql_outage'")->fetchColumn();
        if ($outage === '1' || $count() === 0) return ['processed' => 0, 'remaining' => $count(), 'error' => null];
        $lock = fopen(Bootstrap::path('storage', 'mirror.lock'), 'c');
        if (!$lock) return ['processed' => 0, 'remaining' => $count(), 'error' => 'Could not open mirror lock.'];
        $processed = 0;
        $error = null;
        $item = null;
        try {
            if (!flock($lock, LOCK_EX | LOCK_NB)) return ['processed' => 0, 'remaining' => $count(), 'error' => null];
            $deadline = microtime(true) + $maxSeconds;
            $pending = $sqlite->query('SELECT * FROM sync_queue WHERE synced_at IS NULL ORDER BY rowid LIMIT 250')->fetchAll();
            $mysql = self::mysql($cfg);
            $prefix = $cfg['mysql']['tablePrefix'] ?? '';
            self::migrateMysql($mysql, $prefix);
            foreach ($pending as $item) {
                if (microtime(true) > $deadline) break;
                $table = $item['entity_table'];
                if (!in_array($table, self::SYNCED_TABLES, true)) throw new \RuntimeException('Invalid mirror entity.');
                $current = $sqlite->prepare("SELECT * FROM {$table} WHERE id = ?");
                $current->execute([$item['entity_id']]);
                $payload = $current->fetch();
                $current->closeCursor();
                if ($payload) {
                    $columns = array_keys($payload);
                    $quoted = array_map(static fn(string $c): string => chr(96) . $c . chr(96), $columns);
                    $assignments = array_map(static fn(string $c): string => "{$c} = VALUES({$c})", array_slice($quoted, 1));
                    $mirrorTable = self::mysqlTable($prefix, $table);
                    $slots = implode(', ', array_fill(0, count($columns), '?'));
                    $mysql->prepare("INSERT INTO {$mirrorTable} (" . implode(', ', $quoted) . ") VALUES ({$slots}) ON DUPLICATE KEY UPDATE " . implode(', ', $assignments))
                        ->execute(array_values($payload));
                }
                $sqlite->prepare('UPDATE sync_queue SET synced_at = ?, last_error = NULL WHERE id = ?')->execute([gmdate('c'), $item['id']]);
                $processed++;
            }
        } catch (\Throwable) {
            $error = 'MySQL mirror is unavailable or could not apply a queued change.';
            if ($item) $sqlite->prepare('UPDATE sync_queue SET attempts = attempts + 1, last_error = ? WHERE id = ?')->execute([$error, $item['id']]);
        } finally {
            fclose($lock);
        }
        return ['processed' => $processed, 'remaining' => $count(), 'error' => $error];
    }

    public static function verify(array $cfg): array
    {
        if (($cfg['dbMode'] ?? 'sqlite') === 'sqlite') {
            return ['ok' => true, 'message' => 'Mode is sqlite — no MySQL mirror configured.'];
        }
        $sqlite = self::sqlite($cfg);
        $mysql = self::mysql($cfg);
        $drifted = [];
        foreach (self::SYNCED_TABLES as $table) {
            $sRows = $sqlite->query("SELECT * FROM {$table}")->fetchAll();
            $mRows = $mysql->query("SELECT * FROM " . self::mysqlTable($cfg['mysql']['tablePrefix'] ?? '', $table))->fetchAll();
            $sMap = [];
            foreach ($sRows as $r) $sMap[(string)$r['id']] = Crypto::canonicalHash($r);
            $mMap = [];
            foreach ($mRows as $r) $mMap[(string)$r['id']] = Crypto::canonicalHash($r);
            if ($sMap !== $mMap) {
                $drifted[] = $table;
            }
        }
        return [
            'ok' => count($drifted) === 0,
            'message' => count($drifted) === 0 ? 'All table row counts and canonical hashes match.' : 'Drift detected in: ' . implode(', ', $drifted),
            'driftedTables' => $drifted,
        ];
    }

    public static function backup(array $cfg): array
    {
        $sqlite = self::sqlite($cfg);
        $ts = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Ymd-His-v');
        $dir = Bootstrap::path('backups', $ts);
        if (!mkdir($dir, 0770, true)) throw new \RuntimeException('Could not create backup directory.');
        $snapPath = $dir . DIRECTORY_SEPARATOR . 'lifeledger.sqlite';
        $sqlite->exec("VACUUM INTO '" . str_replace("'", "''", $snapPath) . "'");
        $sqliteSha = hash_file('sha256', $snapPath);

        $rowCounts = [];
        foreach (self::SYNCED_TABLES as $t) {
            $rowCounts[$t] = (int)$sqlite->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
        }

        $mysqlDump = null;
        $mysqlSha = null;
        if (($cfg['dbMode'] ?? 'sqlite') === 'dual') {
            try {
                $mysql = self::mysql($cfg);
                $lines = ["-- LifeLedger Pure-PHP MySQL Dump", "-- Authoritative: SQLite"];
                foreach (self::SYNCED_TABLES as $t) {
                    $rows = $mysql->query("SELECT * FROM " . self::mysqlTable($cfg['mysql']['tablePrefix'] ?? '', $t))->fetchAll();
                    foreach ($rows as $r) {
                        $cols = implode(', ', array_keys($r));
                        $vals = implode(', ', array_map(static function ($value): string {
                            if ($value === null) return 'NULL';
                            if (is_int($value) || is_float($value)) return (string)$value;
                            return "'" . str_replace([chr(92), chr(10), chr(13), "'"], [chr(92) . chr(92), chr(92) . 'n', chr(92) . 'r', "''"], (string)$value) . "'";
                        }, array_values($r)));
                        $lines[] = "INSERT INTO {$t} ({$cols}) VALUES ({$vals});";
                    }
                }
                $dumpPath = $dir . DIRECTORY_SEPARATOR . 'mysql_dump.sql';
                file_put_contents($dumpPath, implode("\n", $lines) . "\n");
                $mysqlDump = 'mysql_dump.sql';
                $mysqlSha = hash_file('sha256', $dumpPath);
            } catch (\Throwable $e) {
                // MySQL offline during backup; SQLite authoritative snapshot succeeded
            }
        }

        $manifest = [
            'timestamp' => $ts,
            'createdAt' => gmdate('c'),
            'authoritative' => 'sqlite',
            'dbMode' => $cfg['dbMode'],
            'sqliteSnapshotFile' => 'lifeledger.sqlite',
            'sqliteSha256' => $sqliteSha,
            'mysqlDumpFile' => $mysqlDump,
            'mysqlSha256' => $mysqlSha,
            'rowCounts' => $rowCounts,
        ];
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT) . "\n");
        return $manifest;
    }

    public static function restore(array $cfg, string $timestamp): array
    {
        require_once __DIR__ . '/Api.php';
        return Api::restore($cfg, $timestamp);
    }

    public static function seed(array $cfg): array
    {
        require_once __DIR__ . '/Api.php';
        return Api::seed($cfg);
    }

    private static function ddlStatements(string $dialect): array
    {
        $migrations = json_decode(file_get_contents(__DIR__ . '/schema.json'), true, flags: JSON_THROW_ON_ERROR);
        return $migrations[0][$dialect . 'Statements'];
    }

}
