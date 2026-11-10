<?php
declare(strict_types=1);

namespace LifeLedger;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;
use Throwable;

final class Api
{
    private const SECRETS = [
        'personal_profile' => ['ssn', 'drivers_license'],
        'accounts' => ['password', 'security_answer'],
        'bank_accounts' => ['ssn', 'password', 'pin', 'account_number', 'routing_number'],
        'cards' => ['card_number'],
    ];
    private const EXPORTS = [
        'events', 'contacts', 'vehicles', 'vehicle_documents', 'documents', 'lists',
        'list_items', 'inventory_items', 'chores', 'receipts', 'journal_entries',
        'incidents', 'recipes', 'meal_plans', 'work_shifts', 'music_tracks', 'playlists',
        'business_inventory', 'income_entries', 'appointments', 'work_orders',
        'work_order_items', 'work_order_deliveries', 'websites',
    ];
    private const USER_FIELDS = 'id, email, display_name, theme, time_zone, currency';

    public static function theme(mixed $value): string
    {
        return in_array($value, ['light', 'dark', 'system'], true) ? $value : 'system';
    }

    public static function screen(): array
    {
        $install = Database::detectInstallState();
        $check = Config::check();
        $onboarding = ['screen' => 'onboarding', 'props' => [
            'installState' => $install['state'], 'installReason' => $install['reason'],
        ]];
        $rerunning = !empty($install['rerunning']);
        if ((!$rerunning && $install['state'] !== 'configured') || $check['status'] !== 'OK') return $onboarding;
        $cfg = $check['config'];
        $user = self::one(Database::sqlite($cfg), 'SELECT ' . self::USER_FIELDS . ' FROM users WHERE deleted_at IS NULL LIMIT 1');
        if ($user && self::uid($cfg) === $user['id']) {
            return $rerunning ? $onboarding : ['screen' => 'app', 'props' => ['user' => $user]];
        }
        return ['screen' => 'login', 'props' => [
            'userEmail' => $user['email'] ?? '', 'displayName' => $user['display_name'] ?? '',
            'initialTheme' => self::theme($user['theme'] ?? null), 'dbMode' => $cfg['dbMode'],
        ]];
    }

    public static function dispatch(string $route, string $method): void
    {
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        try {
            self::checkOrigin($method);
            if ($route === 'onboarding') {
                self::onboarding($method);
            }
            $check = Config::check();
            if ($route === 'health' && $method === 'GET' && $check['status'] !== 'OK') {
                $install = Database::detectInstallState();
                self::json(['ok' => true, 'installState' => $install['state'], 'installReason' => $install['reason'],
                    'configStatus' => $check['status'], 'mode' => 'unconfigured', 'resolvedDbMode' => null,
                    'sqlite' => ['ready' => false, 'engine' => 'sqlite'], 'mysqlReachability' => 'not_configured',
                    'queueDepth' => 0, 'failedCount' => 0, 'lastSyncAt' => null, 'lastError' => null]);
            }
            if ($check['status'] !== 'OK') self::fail('Application is not configured.', 400);
            $cfg = $check['config'];
            $db = Database::sqlite($cfg);
            $uid = self::uid($cfg);
            if ($route === 'auth') self::auth($method, $db, $cfg, $uid);
            if (!$uid || !self::one($db, 'SELECT id FROM users WHERE id = ? AND deleted_at IS NULL', [$uid])) self::fail('Unauthorized', 401);
            if (preg_match('~^entities/([a-z_]+)$~D', $route, $match)) {
                self::entities($method, $db, $cfg, $match[1]);
            }
            if ($route === 'files' && $method === 'POST') self::upload($db, $cfg);
            if ($method === 'GET' && preg_match('~^files/([a-f0-9-]+)$~iD', $route, $match)) self::file($db, $match[1]);
            if ($route === 'vault/reveal' && $method === 'POST') self::reveal($db, $cfg, $uid);
            if ($route === 'search' && $method === 'GET') self::search($db);
            if ($route === 'export' && $method === 'GET') self::export($db, $cfg);
            if ($route === 'sync') self::sync($method, $db, $cfg, $check, $uid);
            if ($route === 'health' && $method === 'GET') {
                $install = Database::detectInstallState();
                $status = self::status($db, $cfg);
                self::json(['ok' => true, 'installState' => $install['state'], 'installReason' => $install['reason'],
                    'configStatus' => 'OK', 'configSource' => $check['source'], 'resolvedDbMode' => $cfg['dbMode'],
                    'sqlite' => ['ready' => true, 'engine' => 'sqlite', 'path' => $status['sqlitePath'], 'schemaVersion' => $status['schemaVersion']],
                    'config' => Config::redacted($check)] + $status);
            }
            self::fail('Endpoint not found.', 404);
        } catch (Throwable $error) {
            error_log('LifeLedger request failed (' . get_class($error) . ')');
            self::fail('LifeLedger could not complete the request.', 500);
        }
    }

    private static function checkOrigin(string $method): void
    {
        if (in_array($method, ['GET', 'HEAD'], true)) return;
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site' ||
            ($origin !== null && strcasecmp($origin, $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '')) !== 0)) {
            self::fail('Cross-site request denied.', 403);
        }
    }

    private static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        exit;
    }

    private static function fail(string $message, int $status): never
    {
        self::json(['ok' => false, 'error' => $message], $status);
    }

    private static function body(): array
    {
        $value = json_decode((string)file_get_contents('php://input', length: 1024 * 1024), true);
        if (!is_array($value)) self::fail('Expected a JSON object.', 400);
        return $value;
    }

    private static function one(PDO $db, string $sql, array $args = []): ?array
    {
        $stmt = $db->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetch() ?: null;
    }

    private static function all(PDO $db, string $sql, array $args = []): array
    {
        $stmt = $db->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    }

    private static function cookie(string $name, string $value, int $lifetime, bool $httpOnly): void
    {
        setcookie($name, $value, [
            'expires' => time() + $lifetime,
            'path' => Bootstrap::baseUrl() . '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => $httpOnly,
            'samesite' => 'Lax',
        ]);
    }

    private static function signIn(array $user, array $cfg): void
    {
        $lifetime = 7 * 86400;
        $data = $user['id'] . ':' . (time() + $lifetime);
        self::cookie('lifeledger_php_session', $data . ':' . hash_hmac('sha256', $data, $cfg['app']['sessionSecret']), $lifetime, true);
        self::cookie('lifeledger_theme', self::theme($user['theme']), 365 * 86400, false);
    }

    private static function uid(array $cfg): ?string
    {
        $parts = explode(':', $_COOKIE['lifeledger_php_session'] ?? '');
        if (count($parts) === 3 && ctype_digit($parts[1]) && (int)$parts[1] >= time() &&
            hash_equals(hash_hmac('sha256', $parts[0] . ':' . $parts[1], $cfg['app']['sessionSecret']), $parts[2])) {
            return $parts[0];
        }
        return null;
    }

    private static function audit(PDO $db, array $cfg, string $action, string $type, ?string $id, string $details): void
    {
        Database::upsertLocalFirst($db, 'audit_log', ['id' => Crypto::uuid(), 'action' => $action,
            'entity_type' => $type, 'entity_id' => $id, 'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'], $cfg['dbMode']);
        self::scheduleDrain($cfg);
    }

    private static function scheduleDrain(array $cfg): void
    {
        static $registered = false;
        if ($registered || $cfg['dbMode'] !== 'dual') return;
        $registered = true;
        register_shutdown_function(static function () use ($cfg): void {
            try { Database::drainQueue($cfg, 2); } catch (Throwable) {}
        });
    }

    private static function checkPassword(PDO $db, array $user, string $password, string $scope): bool
    {
        $key = $scope . ':' . $user['id'];
        $now = time();
        $stmt = $db->prepare('INSERT INTO app_state (state_key,state_value,updated_at) VALUES (?, ?, ?) '
            . "ON CONFLICT(state_key) DO UPDATE SET state_value = CASE WHEN CAST(updated_at AS INTEGER) > ? THEN CAST(state_value AS INTEGER) + 1 ELSE 1 END, "
            . "updated_at = CASE WHEN CAST(updated_at AS INTEGER) > ? THEN updated_at ELSE excluded.updated_at END RETURNING state_value");
        $stmt->execute([$key, '1', (string)$now, $now - 60, $now - 60]);
        $attempts = (int)$stmt->fetchColumn();
        $stmt->closeCursor();
        if ($attempts > 8) self::fail('Too many password verification attempts. Try again in 60s.', 429);
        if (!password_verify($password, $user['password_hash'])) return false;
        $db->prepare('DELETE FROM app_state WHERE state_key = ?')->execute([$key]);
        return true;
    }

    private static function auth(string $method, PDO $db, array $cfg, ?string $uid): never
    {
        if ($method === 'DELETE') {
            self::cookie('lifeledger_php_session', '', -3600, true);
            if ($uid) self::audit($db, $cfg, 'logout', 'users', $uid, 'User signed out');
            self::json(['ok' => true]);
        }
        if ($method === 'POST') {
            $body = self::body();
            $user = self::one($db, 'SELECT * FROM users WHERE deleted_at IS NULL LIMIT 1');
            if (!$user) self::fail('No user account found. Complete onboarding first.', 400);
            if (!self::checkPassword($db, $user, (string)($body['password'] ?? ''), 'auth:login')) {
                self::audit($db, $cfg, 'failed_login', 'users', $user['id'], 'Failed login attempt (invalid password)');
                self::fail('Invalid password.', 401);
            }
            self::signIn($user, $cfg);
            self::audit($db, $cfg, 'login', 'users', $user['id'], 'User signed in');
            self::json(['ok' => true, 'user' => self::publicUser($user)]);
        }
        if (!$uid) self::fail('Unauthorized', 401);
        $user = self::one($db, 'SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$uid]);
        if (!$user) self::fail('Unauthorized', 401);
        if ($method === 'PATCH') {
            $body = self::body();
            if (isset($body['theme'])) $user['theme'] = self::theme($body['theme']);
            if (isset($body['time_zone']) && self::validZone((string)$body['time_zone'])) $user['time_zone'] = $body['time_zone'];
            if (!empty($body['currency'])) $user['currency'] = substr(strtoupper(trim((string)$body['currency'])), 0, 8);
            if (!empty($body['display_name'])) $user['display_name'] = trim((string)$body['display_name']);
            $user = Database::upsertLocalFirst($db, 'users', $user, $cfg['dbMode']);
            self::cookie('lifeledger_theme', $user['theme'], 365 * 86400, false);
            self::scheduleDrain($cfg);
        } elseif ($method !== 'GET') {
            self::fail('Method not allowed.', 405);
        }
        self::json(['ok' => true, 'authenticated' => true, 'user' => self::publicUser($user)]);
    }

    private static function publicUser(array $user): array
    {
        return array_intersect_key($user, array_flip(explode(', ', self::USER_FIELDS)));
    }

    private static function validZone(string $zone): bool
    {
        return in_array($zone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true);
    }

    private static function onboarding(string $method): never
    {
        $install = Database::detectInstallState();
        $draftPath = Bootstrap::path('storage', 'onboarding-draft.json');
        $existingConfig = Config::check()['config'] ?? null;
        if ($method === 'GET') {
            $draft = $install['state'] !== 'configured' && is_file($draftPath)
                ? json_decode((string)file_get_contents($draftPath), true) : null;
            self::json(['ok' => true, 'installState' => $install['state'], 'installReason' => $install['reason'],
                'suggestedEncryptionKey' => Crypto::generateKey(), 'dataDirWritable' => is_writable(Bootstrap::path('data')), 'draft' => $draft]);
        }
        if ($method !== 'POST') self::fail('Method not allowed.', 405);
        if ($install['state'] === 'configured') self::fail('LifeLedger is already configured.', 409);
        if (!empty($install['rerunning']) && !self::uid($existingConfig)) self::fail('Sign in to reconfigure an existing installation.', 401);
        $body = self::body();
        $action = $body['action'] ?? 'complete';
        if ($action === 'save_draft') {
            $keys = ['step', 'dbMode', 'sqlitePath', 'mysqlHost', 'mysqlPort', 'mysqlDatabase', 'mysqlTablePrefix',
                'mysqlUser', 'displayName', 'email', 'timeZone', 'currency', 'theme', 'loadSeed'];
            file_put_contents($draftPath, json_encode(array_intersect_key($body, array_flip($keys)), JSON_THROW_ON_ERROR), LOCK_EX);
            self::json(['ok' => true]);
        }
        $prefixEnv = getenv('MYSQL_TABLE_PREFIX');
        $prefix = $prefixEnv !== false ? $prefixEnv : ($body['mysqlTablePrefix'] ?? '');
        if (!Config::validMysqlTablePrefix($prefix)) self::fail('Invalid MySQL table prefix.', 400);
        $mirror = ['host' => trim((string)($body['mysqlHost'] ?? '127.0.0.1')), 'port' => (int)($body['mysqlPort'] ?? 3306),
            'database' => trim((string)($body['mysqlDatabase'] ?? 'lifeledger')), 'user' => trim((string)($body['mysqlUser'] ?? 'lifeledger')),
            'password' => (string)($body['mysqlPassword'] ?? ''), 'tablePrefix' => $prefix];
        // Delimiters in PDO's DSN can change the server or database being tested.
        foreach ([$mirror['host'], $mirror['database']] as $part) {
            if ($part === '' || preg_match('/[;\x00-\x1f]/', $part)) self::fail('Invalid MySQL connection setting.', 400);
        }
        if ($mirror['port'] < 1 || $mirror['port'] > 65535) self::fail('Invalid MySQL port.', 400);
        if ($action === 'test_mysql') {
            try {
                Database::mysql(['dbMode' => 'dual', 'mysql' => $mirror])->query('SELECT 1');
                self::json(['ok' => true, 'reachable' => true, 'error' => null]);
            } catch (Throwable) {
                self::json(['ok' => true, 'reachable' => false, 'error' => 'Could not connect to the MySQL mirror.']);
            }
        }
        if ($action !== 'complete') self::fail('Unknown onboarding action.', 400);
        $mode = $body['dbMode'] ?? 'sqlite';
        if (!in_array($mode, ['sqlite', 'dual'], true)) self::fail("SQLite must be the primary database. Choose 'sqlite' or 'dual'.", 400);
        $key = trim((string)($body['encryptionKey'] ?? ''));
        $decoded = base64_decode($key, true);
        if ($decoded === false || strlen($decoded) !== 32) self::fail('The encryption key must decode to 32 bytes.', 400);
        $password = (string)($body['password'] ?? '');
        if (strlen($password) < 8 || $password !== ($body['confirmPassword'] ?? null)) {
            self::fail('App password must be at least 8 characters and match confirmation.', 400);
        }
        $zone = trim((string)($body['timeZone'] ?? 'UTC'));
        if (!self::validZone($zone)) self::fail('Invalid IANA time zone.', 400);
        $path = trim((string)($body['sqlitePath'] ?? './data/lifeledger.sqlite'));
        if ($path === '') self::fail('SQLite file path is required.', 400);
        if ($existingConfig && ($oldPath = realpath(Config::resolveRel($existingConfig['sqlitePath']))) !== false
            && realpath(Config::resolveRel($path)) === $oldPath) {
            $key = $existingConfig['app']['encryptionKey'];
        }
        $theme = self::theme($body['theme'] ?? 'system');
        $cfg = ['version' => 1, 'dbMode' => $mode, 'sqlitePath' => $path, 'mysql' => $mirror, 'app' => [
            'encryptionKey' => $key, 'sessionSecret' => bin2hex(random_bytes(32)), 'port' => 3000,
            'timeZone' => $zone, 'theme' => $theme,
        ]];
        $lock = fopen(Bootstrap::path('storage', 'onboarding.lock'), 'c');
        if (!$lock || !flock($lock, LOCK_EX)) self::fail('Could not start installation.', 500);
        try {
            if (Database::detectInstallState()['state'] === 'configured') self::fail('LifeLedger is already configured.', 409);
            $db = Database::sqlite($cfg);
            Database::migrateSqlite($db);
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $user = Database::upsertLocalFirst($db, 'users', [
                'id' => '10000000-0000-4000-8000-000000000001', 'password_hash' => $hash, 'theme' => $theme,
                'display_name' => trim((string)($body['displayName'] ?? '')) ?: 'Alex Rivera',
                'email' => trim((string)($body['email'] ?? '')) ?: 'alex.rivera@example.test',
                'time_zone' => $zone, 'currency' => strtoupper(trim((string)($body['currency'] ?? 'USD'))) ?: 'USD',
            ], $mode);
            $loadSeed = !empty($body['loadSeed']);
            if ($loadSeed) {
                self::seed($cfg, $user);
            } else {
                Database::upsertLocalFirst($db, 'personal_profile', [
                    'id' => '10000000-0000-4000-8000-000000000002', 'legal_name' => $user['display_name'],
                    'preferred_name' => $user['display_name'], 'current_email' => $user['email'],
                    'tags_json' => '["personal"]', 'notes' => '',
                ], $mode);
            }
            self::audit($db, $cfg, 'onboarding_complete', 'system', null, 'Completed onboarding in ' . $mode . ' mode');
            $written = Config::writeAtomic($cfg);
            $db->exec("DELETE FROM app_state WHERE state_key = 'onboarding_rerun'");
            if (is_file($draftPath)) unlink($draftPath);
            self::signIn($user, $cfg);
        } finally {
            fclose($lock);
        }
        self::json(['ok' => true, 'configPath' => $written, 'dbMode' => $mode, 'mysqlMigrated' => false, 'loadSeed' => $loadSeed]);
    }

    public static function seed(array $cfg, ?array $user = null): array
    {
        $db = Database::sqlite($cfg);
        Database::migrateSqlite($db);
        $fixture = json_decode(file_get_contents(__DIR__ . '/demo.json'), true, flags: JSON_THROW_ON_ERROR);
        $user ??= self::one($db, 'SELECT * FROM users WHERE deleted_at IS NULL LIMIT 1');
        if (!$user) {
            $user = Database::upsertLocalFirst($db, 'users', [
                'id' => '10000000-0000-4000-8000-000000000001', 'email' => 'alex.rivera@example.test', 'display_name' => 'Alex Rivera',
                'password_hash' => password_hash('LifeLedger!Demo2026', PASSWORD_BCRYPT, ['cost' => 12]),
                'theme' => $cfg['app']['theme'], 'time_zone' => $cfg['app']['timeZone'], 'currency' => 'USD',
            ], $cfg['dbMode']);
        }
        foreach ($fixture['tables'] as $table => $rows) {
            foreach ($rows as $row) {
                foreach (self::SECRETS[$table] ?? [] as $field) {
                    $column = $field . '_enc';
                    $row[$column] = Crypto::encrypt($row[$column], $cfg['app']['encryptionKey'], $table, $row['id'], $field);
                }
                unset($row['created_at'], $row['updated_at'], $row['revision']);
                if ($table === 'personal_profile') $row['current_email'] = $user['email'];
                Database::upsertLocalFirst($db, $table, $row, $cfg['dbMode']);
            }
        }
        self::audit($db, $cfg, 'seed_loaded', 'system', null, 'Loaded synthetic demo seed for year ' . $fixture['seedYear']);
        return ['seedYear' => $fixture['seedYear'], 'seededCounts' => $fixture['seededCounts']];
    }

    private static function entities(string $method, PDO $db, array $cfg, string $table): never
    {
        if ($table === 'users' || !in_array($table, Database::SYNCED_TABLES, true)) self::fail('Unknown entity.', 404);
        if ($method === 'GET') {
            $limit = min(2000, max(1, (int)($_GET['limit'] ?? 500)));
            $order = match ($table) {
                'events' => 'start_at ASC',
                'list_items', 'playlist_items' => 'sort_order ASC, created_at ASC',
                default => 'created_at DESC',
            };
            $rows = self::all($db, "SELECT * FROM {$table} WHERE deleted_at IS NULL ORDER BY {$order} LIMIT {$limit}");
            self::json(['ok' => true, 'items' => array_map(static fn(array $row): array => self::sanitize($table, $row), $rows)]);
        }
        if ($table === 'audit_log') self::fail('Audit records are read only.', 400);
        if ($method === 'DELETE') {
            $id = (string)($_GET['id'] ?? '');
            if ($id === '') self::fail('Missing record id.', 400);
            $row = self::one($db, "SELECT * FROM {$table} WHERE id = ?", [$id]);
            if ($row) {
                $row['deleted_at'] = self::now();
                Database::upsertLocalFirst($db, $table, $row, $cfg['dbMode']);
                self::scheduleDrain($cfg);
            }
            self::json(['ok' => true, 'deleted' => (bool)$row]);
        }
        if ($method !== 'POST') self::fail('Method not allowed.', 405);
        $body = self::body();
        try {
            foreach ($body as $key => $value) {
                $key = preg_replace('/[^a-z0-9_]/', '', strtolower((string)$key));
                if (in_array($key, ['cvv', 'cvv2', 'cvc', 'cvc2', 'cid', 'securitycode', 'security_code', 'cardsecuritycode', 'card_security_code'], true)
                    && $value !== null && $value !== '') {
                    self::fail('Security policy violation: Card security codes (CVV/CVC) cannot be stored.', 400);
                }
            }
            $id = (string)($body['id'] ?? '') ?: Crypto::uuid();
            $row = self::one($db, "SELECT * FROM {$table} WHERE id = ?", [$id]) ?? [];
            unset($row['revision'], $row['created_at'], $row['updated_at'], $row['deleted_at']);
            $columns = array_column(self::all($db, "PRAGMA table_info({$table})"), 'name');
            foreach ($columns as $column) {
                if (in_array($column, ['id', 'revision', 'created_at', 'updated_at', 'deleted_at'], true) || str_ends_with($column, '_enc')) continue;
                if ($table === 'attachments' && !in_array($column, ['linked_type', 'linked_id', 'notes'], true)) continue;
                if (array_key_exists($column, $body) && (is_scalar($body[$column]) || $body[$column] === null)) {
                    $row[$column] = is_bool($body[$column]) ? (int)$body[$column] : $body[$column];
                }
            }
            $row['id'] = $id;
            foreach (self::SECRETS[$table] ?? [] as $field) {
                $plain = is_string($body[$field] ?? null) ? trim($body[$field]) : '';
                if ($plain === '') continue;
                $row[$field . '_enc'] = Crypto::encrypt($plain, $cfg['app']['encryptionKey'], $table, $id, $field);
                if ($field === 'card_number') $row['card_last4'] = substr(preg_replace('/\D/', '', $plain), -4);
                if ($field === 'account_number') $row['account_last4'] = substr(preg_replace('/\D/', '', $plain), -4);
            }
            if (in_array('search_text', $columns, true)) {
                $parts = array_filter($row, static fn($value, $key): bool =>
                    is_string($value) && !in_array($key, ['id', 'search_text'], true) && !str_ends_with($key, '_enc'), ARRAY_FILTER_USE_BOTH);
                $row['search_text'] = mb_strtolower(implode(' ', $parts));
            }
            foreach (['notes' => '', 'description' => '', 'tags_json' => '[]'] as $column => $default) {
                if (in_array($column, $columns, true)) $row[$column] ??= $default;
            }
            $saved = Database::upsertLocalFirst($db, $table, $row, $cfg['dbMode']);
            $item = self::one($db, "SELECT * FROM {$table} WHERE id = ?", [$saved['id']]) ?? $saved;
        } catch (Throwable) {
            self::fail('Could not save this record. Check required fields.', 400);
        }
        self::scheduleDrain($cfg);
        self::json(['ok' => true, 'item' => self::sanitize($table, $item)]);
    }

    private static function sanitize(string $table, array $row): array
    {
        foreach (self::SECRETS[$table] ?? [] as $field) {
            $row['has_' . $field] = !empty($row[$field . '_enc']);
        }
        foreach (array_keys($row) as $column) {
            if (str_ends_with($column, '_enc') || $column === 'password_hash') unset($row[$column]);
        }
        return $row;
    }

    private static function reveal(PDO $db, array $cfg, string $uid): never
    {
        $body = self::body();
        $table = (string)($body['table'] ?? '');
        $id = (string)($body['recordId'] ?? '');
        $field = (string)($body['field'] ?? '');
        if ($id === '' || !in_array($field, self::SECRETS[$table] ?? [], true)) self::fail('Invalid vault target specified.', 400);
        $user = self::one($db, 'SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$uid]);
        if (!$user) self::fail('User not found.', 404);
        if (!self::checkPassword($db, $user, (string)($body['password'] ?? ''), 'vault:reveal')) {
            self::audit($db, $cfg, 'vault_reveal_denied', $table, $id, 'Denied secret reveal for ' . $table . '.' . $field);
            self::fail('Incorrect app password. Secret reveal denied.', 401);
        }
        $column = $field . '_enc';
        $row = self::one($db, "SELECT {$column} FROM {$table} WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$row) self::fail('Record not found.', 404);
        $value = Crypto::decrypt($row[$column], $cfg['app']['encryptionKey'], $table, $id, $field);
        self::audit($db, $cfg, 'vault_reveal', $table, $id, 'Revealed ' . $table . '.' . $field . ' after password re-authentication');
        self::json(['ok' => true, 'value' => $value ?? '', 'autoHideSeconds' => 30]);
    }

    private static function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');
    }

    private static function upload(PDO $db, array $cfg): never
    {
        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) self::fail('No file uploaded or the PHP upload limit was exceeded.', 400);
        if ($file['size'] > 10 * 1024 * 1024) self::fail('File exceeds maximum size of 10 MB.', 400);
        $bytes = file_get_contents($file['tmp_name'], length: 12) ?: '';
        $type = match (true) {
            strlen($bytes) < 8 => null,
            str_starts_with($bytes, '%PDF') => ['application/pdf', 'pdf'],
            str_starts_with($bytes, "\x89PNG") => ['image/png', 'png'],
            str_starts_with($bytes, "\xff\xd8\xff") => ['image/jpeg', 'jpg'],
            str_starts_with($bytes, 'GIF') => ['image/gif', 'gif'],
            str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => ['image/webp', 'webp'],
            default => null,
        };
        if (!$type) self::fail('Unsupported file format. Upload a PNG, JPEG, GIF, WebP or PDF.', 400);
        $id = Crypto::uuid();
        $storedName = $id . '.' . $type[1];
        $target = Bootstrap::path('uploads', $storedName);
        if (!move_uploaded_file($file['tmp_name'], $target)) self::fail('Could not store uploaded file.', 500);
        try {
            $row = Database::upsertLocalFirst($db, 'attachments', [
                'id' => $id, 'original_name' => mb_substr((string)$file['name'], 0, 200),
                'stored_name' => $storedName, 'mime_type' => $type[0], 'size_bytes' => (int)$file['size'],
                'sha256' => hash_file('sha256', $target), 'linked_type' => (string)($_POST['linked_type'] ?? 'general'),
                'linked_id' => $_POST['linked_id'] ?? null, 'notes' => (string)($_POST['notes'] ?? ''),
            ], $cfg['dbMode']);
        } catch (Throwable $error) {
            unlink($target);
            throw $error;
        }
        self::scheduleDrain($cfg);
        self::json(['ok' => true, 'item' => $row]);
    }

    private static function file(PDO $db, string $id): never
    {
        $row = self::one($db, 'SELECT * FROM attachments WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$row || !preg_match('/^[a-f0-9-]+\.(pdf|png|jpg|gif|webp)$/iD', $row['stored_name'])) self::fail('File not found.', 404);
        $path = Bootstrap::path('uploads', $row['stored_name']);
        if (!is_file($path)) self::fail('File missing on disk.', 404);
        $types = ['application/pdf', 'image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        if (!in_array($row['mime_type'], $types, true)) self::fail('File type not supported.', 404);
        header('Content-Type: ' . $row['mime_type']);
        header('Content-Disposition: ' . (str_starts_with($row['mime_type'], 'image/') ? 'inline' : 'attachment')
            . "; filename*=UTF-8''" . rawurlencode($row['original_name']));
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    private static function search(PDO $db): never
    {
        $query = mb_strtolower(trim((string)($_GET['q'] ?? '')));
        $filter = (string)($_GET['module'] ?? 'all');
        $results = [];
        if ($query === '') self::json(['ok' => true, 'results' => $results]);
        $sources = [
            ['events', 'events', '/timeline', 'start_at DESC'],
            ['contacts', 'contacts', '/contacts', 'id'],
            ['documents', 'documents', '/organizers', 'id'],
            ['lists', 'list_items', '/organizers', 'id'],
            ['music', 'music_tracks', '/music', 'id'],
            ['journal', 'journal_entries', '/life-admin', 'entry_date DESC'],
            ['business', 'work_orders', '/business', 'id'],
            ['business', 'business_inventory', '/business', 'id'],
            ['websites', 'websites', '/websites', 'id'],
        ];
        foreach ($sources as [$module, $table, $route, $order]) {
            if ($filter !== 'all' && $filter !== $module) continue;
            $limit = $module === 'business' ? 15 : 20;
            $rows = self::all($db, "SELECT * FROM {$table} WHERE deleted_at IS NULL AND search_text LIKE ? ORDER BY {$order} LIMIT {$limit}", ['%' . $query . '%']);
            foreach ($rows as $r) {
                [$title, $subtitle, $snippet] = match ($table) {
                    'events' => [$r['title'], strtoupper($r['category']) . ' • ' . substr($r['start_at'], 0, 10) . ($r['location'] ? ' • ' . $r['location'] : ''), $r['description']],
                    'contacts' => [$r['name'], ($r['relationship'] ?: 'Contact') . ' • ' . ($r['phone'] ?: $r['email']), $r['notes']],
                    'documents' => [$r['title'], $r['category'] . ' • ' . ($r['issue_date'] ?: 'No date'), $r['notes']],
                    'list_items' => [$r['title'], 'List Item • Qty ' . $r['quantity'] . ($r['category'] ? ' • ' . $r['category'] : ''), ''],
                    'music_tracks' => [$r['title'], $r['artist'] . ' • ' . ($r['album'] ?: 'Single'), $r['genre']],
                    'journal_entries' => [$r['title'], $r['entry_date'] . ($r['mood'] ? ' • Mood: ' . $r['mood'] : ''), $r['content']],
                    'work_orders' => ['Work Order: ' . $r['client'], $r['order_date'] . ' • Status: ' . $r['status'], $r['notes']],
                    'business_inventory' => [$r['item_name'], 'SKU: ' . $r['sku'] . ' • In stock: ' . $r['quantity'], $r['location']],
                    'websites' => [$r['title'], $r['category'] . ' • ' . $r['url'], $r['notes']],
                };
                $results[] = ['id' => $r['id'], 'module' => $module, 'title' => $title, 'subtitle' => $subtitle,
                    'snippet' => mb_substr((string)$snippet, 0, 140), 'route' => $route];
            }
        }
        self::json(['ok' => true, 'query' => $query, 'results' => $results]);
    }

    private static function export(PDO $db, array $cfg): never
    {
        $strip = static fn(array $row): array => array_filter($row, static fn(string $key): bool =>
            $key !== 'password_hash' && $key !== 'search_text' && !str_ends_with($key, '_enc'), ARRAY_FILTER_USE_KEY);
        if (strtolower((string)($_GET['format'] ?? 'json')) === 'csv') {
            $table = strtolower((string)($_GET['module'] ?? 'events'));
            if (!in_array($table, self::EXPORTS, true)) $table = 'events';
            $rows = self::all($db, "SELECT * FROM {$table} WHERE deleted_at IS NULL");
            self::audit($db, $cfg, 'export', $table, null, 'Exported non-secret records as CSV');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="lifeledger-' . $table . '.csv"');
            $output = fopen('php://output', 'w');
            if ($rows) fputcsv($output, array_keys($strip($rows[0])), ',', '"', '');
            foreach ($rows as $row) fputcsv($output, array_values($strip($row)), ',', '"', '');
            fclose($output);
            exit;
        }
        $modules = [];
        foreach (self::EXPORTS as $table) {
            $modules[$table] = array_map($strip, self::all($db, "SELECT * FROM {$table} WHERE deleted_at IS NULL"));
        }
        self::audit($db, $cfg, 'export', 'all', null, 'Exported full non-secret JSON snapshot');
        header('Content-Disposition: attachment; filename="lifeledger-export.json"');
        self::json(['exportedAt' => self::now(), 'notice' => 'Non-secret data export. Vault secrets are omitted per security policy.', 'modules' => $modules]);
    }

    private static function status(PDO $db, array $cfg): array
    {
        $schema = (int)$db->query('SELECT MAX(version) FROM schema_migrations')->fetchColumn();
        $outage = (bool)$db->query("SELECT state_value FROM app_state WHERE state_key = 'simulated_mysql_outage'")->fetchColumn();
        $status = ['mode' => $cfg['dbMode'], 'sqliteReady' => true, 'sqlitePath' => Config::resolveRel($cfg['sqlitePath']),
            'schemaVersion' => $schema, 'mysqlSchemaVersion' => null, 'simulatedOutage' => $outage,
            'queueDepth' => (int)$db->query('SELECT COUNT(*) FROM sync_queue WHERE synced_at IS NULL')->fetchColumn(),
            'failedCount' => (int)$db->query('SELECT COUNT(*) FROM sync_queue WHERE synced_at IS NULL AND attempts > 0')->fetchColumn(),
            'lastSyncAt' => $db->query('SELECT MAX(synced_at) FROM sync_queue')->fetchColumn() ?: null,
            'lastError' => $db->query('SELECT last_error FROM sync_queue WHERE last_error IS NOT NULL ORDER BY created_at DESC LIMIT 1')->fetchColumn() ?: null,
            'mysqlReachability' => 'not_configured'];
        if ($cfg['dbMode'] === 'dual') {
            $status['mysqlReachability'] = $outage ? 'simulated_outage' : 'degraded';
            if (!$outage) {
                try {
                    $mysql = Database::mysql($cfg);
                    $status['mysqlReachability'] = 'connected';
                    $table = Database::mysqlTable($cfg['mysql']['tablePrefix'] ?? '', 'schema_migrations');
                    $status['mysqlSchemaVersion'] = (int)$mysql->query("SELECT MAX(version) FROM {$table}")->fetchColumn();
                    if ($status['mysqlSchemaVersion'] !== $schema) $status['mysqlReachability'] = 'schema_drift';
                } catch (Throwable) {}
            }
        }
        return $status;
    }

    private static function backups(): array
    {
        $backups = [];
        $files = glob(Bootstrap::path('backups', '*', 'manifest.json')) ?: [];
        rsort($files);
        foreach ($files as $file) {
            $manifest = json_decode((string)file_get_contents($file), true);
            if (is_array($manifest)) $backups[] = $manifest;
        }
        return $backups;
    }

    private static function sync(string $method, PDO $db, array $cfg, array $check, string $uid): never
    {
        if ($method === 'GET') self::json(['ok' => true, 'status' => self::status($db, $cfg),
            'backups' => self::backups(), 'config' => Config::redacted($check)]);
        if ($method !== 'POST') self::fail('Method not allowed.', 405);
        $body = self::body();
        switch ($body['action'] ?? '') {
            case 'drain':
                $result = Database::drainQueue($cfg, 8);
                self::json(['ok' => true, 'result' => $result, 'status' => self::status($db, $cfg)]);
            case 'verify':
            case 'repair':
                $report = self::verify($db, $cfg, $body['action'] === 'repair');
                self::json(['ok' => true, 'report' => $report, 'status' => self::status($db, $cfg)]);
            case 'toggle_outage':
                $enabled = !(bool)$db->query("SELECT state_value FROM app_state WHERE state_key = 'simulated_mysql_outage'")->fetchColumn();
                $db->prepare("INSERT INTO app_state (state_key,state_value,updated_at) VALUES ('simulated_mysql_outage',?,?) "
                    . 'ON CONFLICT(state_key) DO UPDATE SET state_value = excluded.state_value, updated_at = excluded.updated_at')
                    ->execute([$enabled ? '1' : '0', self::now()]);
                if (!$enabled) self::scheduleDrain($cfg);
                self::json(['ok' => true, 'simulatedOutage' => $enabled, 'status' => self::status($db, $cfg)]);
            case 'backup':
                $manifest = Database::backup($cfg);
                self::audit($db, $cfg, 'backup_created', 'system', null, 'Created verified backup snapshot');
                self::json(['ok' => true, 'manifest' => $manifest, 'backups' => self::backups()]);
            case 'restore':
                $timestamp = (string)($body['timestamp'] ?? '');
                if (!preg_match('/^\d{8}-\d{6}(?:-\d{3})?$/D', $timestamp)) self::fail('Invalid backup timestamp.', 400);
                $result = self::restore($cfg, $timestamp, !empty($body['fromMysqlDump']));
                self::json(['ok' => true, 'result' => $result]);
            case 'seed':
                self::json(['ok' => true, 'result' => self::seed($cfg)]);
            case 'reset_onboarding':
                $user = self::one($db, 'SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$uid]);
                if (!$user || !self::checkPassword($db, $user, (string)($body['password'] ?? ''), 'onboarding:reset')) {
                    self::fail('Incorrect password. Onboarding reset denied.', 401);
                }
                self::audit($db, $cfg, 'onboarding_rerun_requested', 'system', null, 'Requested onboarding from Settings');
                // Keep the encryption key and connection details available for the same database.
                $db->exec("INSERT INTO app_state (state_key,state_value,updated_at) VALUES ('onboarding_rerun','1','') "
                    . "ON CONFLICT(state_key) DO UPDATE SET state_value = '1'");
                self::json(['ok' => true]);
            default:
                self::fail('Unknown sync/admin action.', 400);
        }
    }

    private static function verify(PDO $db, array $cfg, bool $repair): array
    {
        $status = self::status($db, $cfg);
        $report = ['ok' => false, 'mode' => $cfg['dbMode'], 'schemaDrift' => false,
            'sqliteSchemaVersion' => $status['schemaVersion'], 'mysqlSchemaVersion' => $status['mysqlSchemaVersion'],
            'tables' => [], 'repairedCount' => 0];
        if ($cfg['dbMode'] === 'sqlite') {
            $report['ok'] = true;
            return $report + ['message' => 'Mode is sqlite — no MySQL mirror configured.'];
        }
        if ($status['mysqlReachability'] !== 'connected') {
            $report['schemaDrift'] = $status['mysqlReachability'] === 'schema_drift';
            return $report + ['message' => 'MySQL mirror is unavailable or its schema version differs.'];
        }
        $mysql = Database::mysql($cfg);
        $report['ok'] = true;
        foreach (Database::SYNCED_TABLES as $table) {
            $name = Database::mysqlTable($cfg['mysql']['tablePrefix'] ?? '', $table);
            $primary = array_column(self::all($db, "SELECT * FROM {$table}"), null, 'id');
            $mirror = array_column(self::all($mysql, "SELECT * FROM {$name}"), null, 'id');
            $missing = array_keys(array_diff_key($primary, $mirror));
            $extra = array_keys(array_diff_key($mirror, $primary));
            $mismatch = [];
            foreach (array_intersect_key($primary, $mirror) as $id => $row) {
                if (Crypto::canonicalHash($row) !== Crypto::canonicalHash($mirror[$id])) $mismatch[] = $id;
            }
            if ($repair) {
                foreach (array_merge($missing, $mismatch) as $id) {
                    Database::enqueue($db, $table, $primary[$id]);
                    $report['repairedCount']++;
                }
                foreach ($extra as $id) {
                    $mysql->prepare("DELETE FROM {$name} WHERE id = ?")->execute([$id]);
                    $report['repairedCount']++;
                }
            }
            $matches = !$missing && !$extra && !$mismatch;
            $report['ok'] = $report['ok'] && $matches;
            $report['tables'][] = ['table' => $table, 'sqliteCount' => count($primary), 'mysqlCount' => count($mirror),
                'mismatchedIds' => $mismatch, 'missingInMysql' => $missing, 'extraInMysql' => $extra, 'ok' => $matches];
        }
        if ($repair && $report['repairedCount']) {
            Database::drainQueue($cfg, 8);
            $checked = self::verify($db, $cfg, false);
            $checked['repairedCount'] = $report['repairedCount'];
            return $checked;
        }
        return $report + ['message' => $report['ok'] ? 'All SQLite and MySQL table counts and canonical row hashes match.'
            : 'Replication drift detected between SQLite primary and MySQL mirror.'];
    }

    public static function restore(array $cfg, string $timestamp, bool $fromMysql = false): array
    {
        if (!preg_match('/^\d{8}-\d{6}(?:-\d{3})?$/D', $timestamp)) throw new RuntimeException('Invalid backup timestamp.');
        $dir = Bootstrap::path('backups', $timestamp);
        $manifestPath = $dir . '/manifest.json';
        if (!is_file($manifestPath)) throw new RuntimeException('Backup does not exist.');
        $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $temporary = null;
        try {
            if ($fromMysql) {
                $dump = $dir . '/mysql_dump.sql';
                self::checkBackup($dump, $manifest['mysqlSha256'] ?? '');
                $temporary = tempnam(Bootstrap::path('storage'), 'restore-');
                if ($temporary === false) throw new RuntimeException('Could not prepare restore.');
                $staged = new PDO('sqlite:' . $temporary);
                Database::migrateSqlite($staged);
                self::importDump($staged, $dump);
                if (!$staged->query('SELECT id FROM users WHERE deleted_at IS NULL LIMIT 1')->fetchColumn()) {
                    throw new RuntimeException('Mirror backup is missing the primary account.');
                }
                $staged = null;
                $sourcePath = $temporary;
            } else {
                $sourcePath = $dir . '/lifeledger.sqlite';
                self::checkBackup($sourcePath, $manifest['sqliteSha256'] ?? '');
            }
            $source = new \SQLite3($sourcePath, SQLITE3_OPEN_READONLY);
            $target = new \SQLite3(Config::resolveRel($cfg['sqlitePath']), SQLITE3_OPEN_READWRITE);
            $target->busyTimeout(5000);
            if (!$source->backup($target)) throw new RuntimeException('Could not restore the backup.');
            $source->close();
            $target->close();
            $db = Database::sqlite($cfg);
            if ($cfg['dbMode'] === 'dual') {
                foreach (Database::SYNCED_TABLES as $table) {
                    foreach (self::all($db, "SELECT * FROM {$table}") as $row) Database::enqueue($db, $table, $row);
                }
                self::scheduleDrain($cfg);
            }
            self::audit($db, $cfg, 'backup_restored', 'system', null, 'Restored backup ' . $timestamp);
        } finally {
            if (is_string($temporary) && is_file($temporary)) unlink($temporary);
        }
        return ['timestamp' => $timestamp, 'restoredFrom' => $fromMysql ? 'mysql_dump' : 'sqlite_snapshot'];
    }

    private static function checkBackup(string $path, string $hash): void
    {
        if ($hash === '' || !is_file($path) || !hash_equals($hash, hash_file('sha256', $path))) {
            throw new RuntimeException('Backup checksum verification failed.');
        }
    }

    private static function importDump(PDO $db, string $path): void
    {
        $columns = [];
        $lines = fopen($path, 'r');
        if (!$lines) throw new RuntimeException('Could not read the mirror dump.');
        // Parse only LifeLedger's INSERT format. Bind all values instead of executing dump SQL.
        try {
            $db->beginTransaction();
            while (($line = fgets($lines)) !== false) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '--')) continue;
                if (!preg_match('/^INSERT INTO ([a-z_]+) \(([a-z_, ]+)\) VALUES \((.*)\);$/D', $line, $match)) {
                    throw new RuntimeException('Invalid mirror dump record.');
                }
                [, $table, $names, $raw] = $match;
                if (!in_array($table, Database::SYNCED_TABLES, true)) throw new RuntimeException('Invalid dump table.');
                $names = array_map('trim', explode(',', $names));
                $columns[$table] ??= array_column(self::all($db, "PRAGMA table_info({$table})"), 'name');
                if (array_diff($names, $columns[$table])) throw new RuntimeException('Invalid dump column.');
                $values = self::dumpValues($raw);
                if (count($values) !== count($names)) throw new RuntimeException('Invalid dump value count.');
                $slots = implode(', ', array_fill(0, count($values), '?'));
                $list = implode(', ', $names);
                $db->prepare("INSERT INTO {$table} ({$list}) VALUES ({$slots})")->execute($values);
            }
            $db->commit();
        } finally {
            if ($db->inTransaction()) $db->rollBack();
            fclose($lines);
        }
    }

    private static function dumpValues(string $raw): array
    {
        $values = [];
        $token = <<<'PATTERN'
~\G\s*(?:'((?:''|\\.|[^'\\])*)'|(NULL|[+-]?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?))\s*(,|$)~D
PATTERN;
        for ($offset = 0, $length = strlen($raw); $offset < $length; $offset += strlen($match[0])) {
            if (!preg_match($token, $raw, $match, PREG_UNMATCHED_AS_NULL, $offset)) throw new RuntimeException('Invalid dump value.');
            if ($match[1] !== null) {
                $values[] = preg_replace_callback("~''|\\\\(.)~", static fn(array $part): string =>
                    $part[0] === "''" ? "'" : match ($part[1]) { 'n' => "\n", 'r' => "\r", default => $part[1] }, $match[1]);
            } else {
                $values[] = $match[2] === 'NULL' ? null : $match[2];
            }
        }
        return $values;
    }

    public static function downloadRelease(): never
    {
        if (!class_exists(\ZipArchive::class)) self::fail('Enable the PHP zip extension to download the XAMPP release.', 503);
        $path = tempnam(sys_get_temp_dir(), 'lifeledger-zip-');
        if ($path === false) self::fail('Could not prepare the release archive.', 500);
        register_shutdown_function(static fn() => unlink($path));
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) self::fail('Could not create the release archive.', 500);
        $files = ['.htaccess', 'index.php', 'README.txt', 'lifeledger.config.example.json',
            'bin/lifeledger', 'assets/app.js', 'assets/app.css',
            'src/Api.php', 'src/Bootstrap.php', 'src/Config.php', 'src/Crypto.php', 'src/Database.php',
            'src/schema.json', 'src/demo.json'];
        foreach (['config', 'data', 'backups', 'uploads', 'storage'] as $folder) {
            $files[] = $folder . '/.htaccess';
            $files[] = $folder . '/index.php';
        }
        foreach ($files as $file) {
            if (!$zip->addFile(Bootstrap::path($file), 'lifeledger/' . $file)) self::fail('Could not package the release.', 500);
        }
        if (!$zip->close()) self::fail('Could not package the release.', 500);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="lifeledger-1.0.0.zip"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
