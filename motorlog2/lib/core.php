<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
const CURRENCIES = ['USD'=>2,'CAD'=>2,'EUR'=>2,'GBP'=>2,'AUD'=>2,'NZD'=>2,'MXN'=>2,'BRL'=>2,'CHF'=>2,'INR'=>2,'JPY'=>0,'KRW'=>0,'KWD'=>3,'BHD'=>3,'AED'=>2,'ZAR'=>2];

final class HttpFault extends RuntimeException {
    public function __construct(public readonly int $status, string $message, public readonly string $errorCode = 'validation') { parent::__construct($message); }
}
function fail(string $message, int $status = 422, string $code = 'validation'): never { throw new HttpFault($status, $message, $code); }
function now_iso(): string { return gmdate('Y-m-d\TH:i:s\Z'); }
function today_iso(): string { return gmdate('Y-m-d'); }
function monday(string $day): string { return (new DateTimeImmutable($day, new DateTimeZone('UTC')))->modify('monday this week')->format('Y-m-d'); }
function next_monday(string $day): string { return (new DateTimeImmutable(monday($day), new DateTimeZone('UTC')))->modify('+7 days')->format('Y-m-d'); }
function json_text(mixed $value): string { return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE); }
function json_array(?string $value): array { $decoded = json_decode($value ?? '', true); return is_array($decoded) ? $decoded : []; }
function esc(mixed $v): string { return htmlspecialchars(is_scalar($v) ? (string)$v : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function installed_version(): string { return trim((string)file_get_contents(APP_ROOT . '/VERSION')); }
function preview_mode(): bool { return (PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli') && getenv('MOTORLOG_DEMO') === '1'; }
function normalized_path(string $value): string { $p = rtrim(str_replace('\\', '/', $value), '/'); return PHP_OS_FAMILY === 'Windows' ? strtolower($p) : $p; }

function private_root(): string {
    static $path;
    if (isset($path)) return $path;
    $app = realpath(APP_ROOT) ?: APP_ROOT;
    $documentRoot = (string)(getenv('MOTORLOG_DOCUMENT_ROOT') ?: ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($documentRoot === '') $documentRoot = dirname($app);
    $documentRoot = realpath($documentRoot) ?: $documentRoot;
    $custom = getenv('MOTORLOG_DATA_DIR');
    $desired = $custom ?: dirname($documentRoot) . '/motorlog-private/' . basename($app);
    if (!preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $desired) || preg_match('~(?:^|[\\\\/])\.\.(?:[\\\\/]|$)~', $desired)) fail('MOTORLOG_DATA_DIR must be an absolute path outside the document root.', 503, 'storage');
    $web = normalized_path($documentRoot);
    $appPath = normalized_path($app);
    $outside = static function (string $target) use ($web, $appPath): bool {
        $n = normalized_path($target);
        return $n !== $web && $n !== $appPath && !str_starts_with($n . '/', $web . '/') && !str_starts_with($n . '/', $appPath . '/');
    };
    if (!$outside($desired)) fail('Private data must be outside the public document root.', 503, 'storage');
    // Resolve the nearest existing ancestor before creating anything, including symlinks/junctions.
    $probe = $desired;
    while (!file_exists($probe) && dirname($probe) !== $probe) $probe = dirname($probe);
    $resolved = realpath($probe);
    if ($resolved !== false && !$outside($resolved . substr($desired, strlen($probe)))) fail('The private folder resolves inside the public directory.', 503, 'storage');
    if (!is_dir($desired) && !@mkdir($desired, 0700, true) && !is_dir($desired)) fail('Apache cannot create the private folder. See the installation permissions in README.', 503, 'storage');
    $real = realpath($desired);
    if (!$real || !$outside($real) || !is_writable($real)) fail('Apache needs a writable private directory outside the document root.', 503, 'storage');
    $path = $real;
    return $path;
}
function private_file(string $name): string { return private_root() . DIRECTORY_SEPARATOR . $name; }
function atomic_private(string $path, string $contents): void {
    $temp = tempnam(dirname($path), '.write-');
    if ($temp === false) throw new RuntimeException('Private file is not writable');
    try {
        @chmod($temp, 0600);
        if (file_put_contents($temp, $contents, LOCK_EX) !== strlen($contents)) throw new RuntimeException('Private file write failed');
        if (!@rename($temp, $path)) throw new RuntimeException('Private file replacement failed');
    } finally { if (is_file($temp)) @unlink($temp); }
}
function write_config(string $path, array $config): void { atomic_private($path, '<?php' . "\nreturn " . var_export($config, true) . ";\n"); }
function guarded(callable $call): mixed {
    $path = private_file('setup.guard');
    $file = fopen($path, 'c+b');
    if (!$file) fail('Cannot open the installation guard.', 503, 'storage');
    @chmod($path, 0600);
    try {
        if (!flock($file, LOCK_EX)) fail('Cannot acquire the installation guard.', 503, 'storage');
        clearstatcache();
        return $call();
    } finally { flock($file, LOCK_UN); fclose($file); }
}
function installed(): bool { return is_file(private_file('config.lock')) && is_file(private_file('config.php')); }
function setup_open(): void {
    if (is_file(private_file('config.lock'))) fail('Setup is locked. This workspace has already been installed.', 423, 'setup_locked');
    if (is_file(private_file('config.php')) || is_file(private_file('motorlog.sqlite'))) fail('Existing workspace data was found. Setup is blocked to protect it. Use the documented lock recovery procedure.', 423, 'recovery_required');
}
function config_read(string $file = 'config.php'): array {
    $path = private_file($file);
    if (!is_file($path)) fail('Website onboarding is not complete. Open the website to finish setup.', 503, 'setup_required');
    $data = require $path;
    if (!is_array($data) || !in_array($data['driver'] ?? '', ['sqlite','mysql'], true)) fail('The private configuration is invalid. See README.', 503, 'storage');
    return $data;
}
function connect_db(array $config, ?string $sqlitePath = null): PDO {
    if (($config['driver'] ?? '') === 'sqlite') {
        $path = $sqlitePath ?? private_file('motorlog.sqlite');
        if ($sqlitePath === null && !is_file($path)) fail('The SQLite database was not found. Restore your backup before continuing.', 503, 'storage');
        $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $db->exec('PRAGMA foreign_keys = ON');
        $db->exec('PRAGMA busy_timeout = 6000');
        return $db;
    }
    $dsn = 'mysql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'].';charset=utf8mb4';
    return new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false, PDO::ATTR_TIMEOUT=>5]);
}
function checked_db(): PDO {
    if (!installed()) {
        if (is_file(private_file('config.lock')) || is_file(private_file('config.php')) || is_file(private_file('motorlog.sqlite'))) fail('Workspace setup files are incomplete; the installer is blocked. See README for recovery.', 503, 'recovery_required');
        fail('Website onboarding is not complete. Open the website to finish setup.', 503, 'setup_required');
    }
    $db = connect_db(config_read());
    if (schema_version($db) !== SCHEMA_VERSION) fail('This database needs the versioned migration command in README before it can be opened.', 503, 'migration_required');
    return $db;
}

function statement(PDO $db, string $sql, array $params = []): PDOStatement { $st = $db->prepare($sql); $st->execute($params); return $st; }
function rows(PDO $db, string $sql, array $params = []): array { return statement($db, $sql, $params)->fetchAll(); }
function one(PDO $db, string $sql, array $params = []): ?array { $r = statement($db, $sql, $params)->fetch(); return $r === false ? null : $r; }
function val(PDO $db, string $sql, array $params = []): mixed { return statement($db, $sql, $params)->fetchColumn(); }
// Table/column names here are internal constants, never request parameters.
function insert_row(PDO $db, string $table, array $data): int {
    $cols = array_keys($data);
    statement($db, 'INSERT INTO '.$table.' ('.implode(',', $cols).') VALUES ('.implode(',', array_fill(0, count($cols), '?')).')', array_values($data));
    return (int)$db->lastInsertId();
}
function update_row(PDO $db, string $table, int $id, array $data): void {
    $cols = array_map(static fn($key) => "$key=?", array_keys($data));
    statement($db, 'UPDATE '.$table.' SET '.implode(',', $cols).' WHERE id=?', [...array_values($data), $id]);
}
function require_row(PDO $db, string $table, int $id, string $label): array {
    return one($db, 'SELECT * FROM '.$table.' WHERE id=?', [$id]) ?? fail("$label was not found.", 404, 'not_found');
}
function settings(PDO $db): array { return one($db, 'SELECT * FROM app_settings WHERE id=1') ?? fail('The workspace is missing its settings.', 503, 'recovery_required'); }

function text_field(mixed $value, string $label, int $limit = 200, bool $required = false): string {
    if ($value === null) $value = '';
    if (!is_string($value) && !is_int($value)) fail("$label must be text.");
    $v = trim((string)$value);
    if (!preg_match('//u', $v) || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $v)) fail("$label contains unsupported characters.");
    if ($required && $v === '') fail("$label is required.");
    $len = function_exists('mb_strlen') ? mb_strlen($v, 'UTF-8') : strlen($v);
    if ($len > $limit) fail("$label must be at most $limit characters.");
    return $v;
}
function date_field(mixed $value, string $label, bool $required = true): ?string {
    $v = text_field($value, $label, 10, $required);
    if ($v === '') return null;
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $v, new DateTimeZone('UTC'));
    if (!$parsed || $parsed->format('Y-m-d') !== $v || (int)substr($v, 0, 4) < 1900) fail("$label must be a real date in YYYY-MM-DD format.");
    return $v;
}
function clock_field(mixed $value, string $label, bool $required = false): string {
    $v = text_field($value, $label, 5, $required);
    if ($v !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $v)) fail("$label must be a valid time (HH:MM).");
    return $v;
}
function choice_field(mixed $value, array $allowed, string $label, bool $required = true): string {
    $v = text_field($value, $label, 100, $required);
    if ((!$required && $v === '') || in_array($v, $allowed, true)) return $v;
    fail("Choose a valid $label.");
}
function bool_field(mixed $v): bool { return in_array($v, [true, 1, '1', 'on'], true); }
function integer_field(mixed $value, string $label, int $min, int $max, bool $required = true): ?int {
    if (($value === '' || $value === null) && !$required) return null;
    if ((!is_int($value) && !is_string($value)) || !preg_match('/^\d+$/D', (string)$value) || (int)$value < $min || (int)$value > $max) fail("$label must be a whole number between $min and $max.");
    return (int)$value;
}
function id_field(mixed $v, string $label, bool $required = true): ?int { return integer_field($v, $label, 1, 2147483647, $required); }
function decimal_ticks(mixed $value, int $scale, string $label, int $maxWhole = 999999999, bool $required = true): ?int {
    if (($value === '' || $value === null) && !$required) return null;
    $string = is_string($value) || is_int($value) || (is_float($value) && is_finite($value)) ? trim((string)$value) : '';
    if (!preg_match('/^(\d*)(?:\.(\d+))?$/D', $string, $m) || $string === '' || strlen($string) > 25 || ($m[1] === '' && empty($m[2]) && !isset($m[2]))) fail("$label must be a nonnegative number.");
    $whole = (int)$m[1]; $fraction = rtrim($m[2] ?? '', '0');
    if ($whole > $maxWhole || strlen($fraction) > $scale) fail("$label must be at most $maxWhole with up to $scale decimal places.");
    return $whole * (10 ** $scale) + (int)str_pad($fraction, $scale, '0');
}
function decimal_string(int $ticks, int $scale): string {
    if ($scale === 0) return (string)$ticks;
    $divisor = 10 ** $scale;
    return intdiv($ticks, $divisor).'.'.str_pad((string)($ticks % $divisor), $scale, '0', STR_PAD_LEFT);
}
function odo_ticks(mixed $v, string $label = 'Odometer', bool $required = true): ?int { return decimal_ticks($v, 3, $label, 999999999, $required); }
function odo_field(mixed $v, string $label = 'Odometer', bool $required = true): ?string { $n = odo_ticks($v, $label, $required); return $n === null ? null : decimal_string($n, 3); }
function currency_field(mixed $v): string { return choice_field($v, array_keys(CURRENCIES), 'currency'); }
function money_value(mixed $v, string $currency, string $label = 'Amount', bool $required = true): ?int { return decimal_ticks($v, CURRENCIES[$currency], $label, 999999999, $required); }
function money_display(int $minor, string $currency): string { return decimal_string($minor, CURRENCIES[$currency]); }
function email_field(mixed $v): string { $mail = text_field($v, 'Email', 180); if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) fail('Enter a valid email address.'); return $mail; }

function setup_database_config(array $data): array {
    $driver = choice_field($data['driver'] ?? 'sqlite', ['sqlite','mysql'], 'database type');
    if ($driver === 'sqlite') return ['driver'=>'sqlite'];
    $host = text_field($data['host'] ?? '127.0.0.1', 'Database host', 150, true);
    $database = text_field($data['database'] ?? '', 'Database name', 63, true);
    if (!preg_match('/^[a-zA-Z0-9._-]+$/D', $host) || !preg_match('/^[a-zA-Z0-9_]+$/D', $database)) fail('Use a simple database host and a database name containing only letters, numbers and underscores.');
    if (in_array(strtolower($database), ['mysql','information_schema','performance_schema','sys','phpmyadmin'], true)) fail('Create a dedicated, empty database for Motorlog.');
    $user = text_field($data['db_user'] ?? '', 'Database user', 120, true);
    $password = $data['db_password'] ?? '';
    if (!is_string($password) || strlen($password) > 512 || str_contains($password, "\0")) fail('The database password is invalid.');
    return ['driver'=>'mysql','host'=>$host,'port'=>integer_field($data['port'] ?? 3306, 'Database port', 1, 65535), 'database'=>$database, 'user'=>$user,'password'=>$password];
}
function connected_for_setup(array $cfg, ?string $sqlitePath = null): PDO {
    try { return connect_db($cfg, $sqlitePath); }
    catch (PDOException $e) { fail('Could not connect. Check the database service, PDO driver, database name, permissions and credentials.', 422, 'database_connection'); }
}
function validate_password(mixed $v): string {
    if (!is_string($v) || strlen($v) < 12 || strlen($v) > 72 || str_contains($v, "\0")) fail('Use a password between 12 and 72 bytes.');
    return $v;
}
function setup_test(array $data): array {
    return guarded(static function () use ($data): array {
        setup_open();
        $cfg = setup_database_config($data);
        $db = connected_for_setup($cfg, $cfg['driver'] === 'sqlite' ? ':memory:' : null);
        $db->query('SELECT 1')->fetchColumn();
        if ($cfg['driver'] === 'mysql') protect_existing_mysql($db, $cfg);
        return ['message'=>$cfg['driver'] === 'sqlite' ? 'SQLite is available and the private folder is writable.' : 'MySQL/MariaDB connection verified.'];
    });
}
function protect_existing_mysql(PDO $db, array $cfg): void {
    $list = tables($db);
    if (!$list) return;
    $pending = is_file(private_file('setup.pending.php')) ? config_read('setup.pending.php') : [];
    foreach (['driver','host','port','database','user'] as $key) if (($pending[$key] ?? null) !== $cfg[$key]) fail('The selected database is not empty. Use a new database or recover the existing installation.', 409, 'database_not_empty');
    if (array_diff($list, known_install_tables())) fail('The selected database contains tables outside this installation.', 409, 'database_not_empty');
    foreach (['users','app_settings','vehicles','trips','expenses'] as $table) {
        if (in_array($table, $list, true) && (int)$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn() > 0) fail('An existing workspace was found. Recover the configuration instead of running setup again.', 423, 'recovery_required');
    }
}
function setup_install(array $data): array {
    return guarded(static function () use ($data): array {
        setup_open();
        $cfg = setup_database_config($data);
        $site = text_field($data['site_name'] ?? '', 'Site name', 100, true);
        $currency = currency_field($data['currency'] ?? 'USD');
        $unit = choice_field($data['unit'] ?? 'mi', ['mi','km'], 'distance unit');
        $username = strtolower(text_field($data['username'] ?? '', 'Administrator username', 100, true));
        if (!preg_match('/^[a-zA-Z0-9._@+-]{3,100}$/D', $username)) fail('Use at least three letters or numbers for the username; . _ @ + - are allowed.');
        $display = text_field($data['display_name'] ?? '', 'Administrator name', 120, true);
        $password = validate_password($data['password'] ?? null);
        $vehicle = vehicle_fields((array)($data['vehicle'] ?? []), true);
        $engine = initial_engine_fields((array)($data['vehicle'] ?? []), $vehicle['initial_odometer']);
        $temp = null;
        $db = null;
        $dbLock = null;
        try {
            if ($cfg['driver'] === 'sqlite') {
                if (is_file(private_file('setup.pending.php')) && config_read('setup.pending.php')['driver'] !== 'sqlite') fail('A previous MySQL setup needs attention. Follow the README before switching databases.', 423, 'recovery_required');
                $temp = tempnam(private_root(), 'setup-db-');
                if (!$temp) throw new RuntimeException('Cannot stage SQLite database');
                @chmod($temp, 0600);
                $db = connected_for_setup($cfg, $temp);
            } else {
                $db = connected_for_setup($cfg);
                $dbLock = 'motorlog-setup-'.substr(hash('sha256', $cfg['database']), 0, 40);
                if ((int)val($db, 'SELECT GET_LOCK(?, 5)', [$dbLock]) !== 1) fail('Another installer is using that database. Try again shortly.', 409, 'setup_busy');
                if (is_file(private_file('setup.pending.php')) && !tables($db)) {
                    $pending = config_read('setup.pending.php');
                    foreach (['driver','host','port','database','user'] as $key) if (($pending[$key] ?? null) !== $cfg[$key]) fail('Retry the original database or follow the documented recovery steps.', 423, 'recovery_required');
                }
                protect_existing_mysql($db, $cfg);
            }
            write_config(private_file('setup.pending.php'), $cfg);
            migrate($db);
            $db->beginTransaction();
            insert_row($db, 'app_settings', ['id'=>1,'site_name'=>$site,'currency'=>$currency,'unit'=>$unit,'installed_at'=>now_iso()]);
            $user = insert_row($db, 'users', ['username'=>$username,'display_name'=>$display,'password_hash'=>password_hash($password, PASSWORD_DEFAULT),'created_at'=>now_iso()]);
            $car = insert_row($db, 'vehicles', $vehicle);
            insert_row($db, 'engines', ['vehicle_id'=>$car,...$engine]);
            $db->commit();
            if ($temp !== null) {
                $db = null;
                if (!@rename($temp, private_file('motorlog.sqlite'))) throw new RuntimeException('Could not publish the SQLite database');
                $temp = null;
            }
            write_config(private_file('config.php'), $cfg);
            atomic_private(private_file('config.lock'), json_text(['installed_at'=>now_iso(),'format'=>1])."\n");
            @unlink(private_file('setup.pending.php'));
            return ['user_id'=>$user,'message'=>'Workspace installed successfully.'];
        } catch (Throwable $e) {
            if ($db instanceof PDO && $db->inTransaction()) $db->rollBack();
            if ($temp !== null && !is_file(private_file('motorlog.sqlite'))) @unlink(private_file('setup.pending.php'));
            if ($e instanceof HttpFault) throw $e;
            error_log('Motorlog setup: '.get_class($e).' / '.(string)$e->getCode());
            fail('Installation did not finish. Retry the same database settings. If workspace data exists, use the recovery procedure in README.', 503, 'install_failed');
        } finally {
            if ($dbLock && $db instanceof PDO) { try { val($db, 'SELECT RELEASE_LOCK(?)', [$dbLock]); } catch (Throwable) {} }
            $db = null;
            if ($temp !== null) @unlink($temp);
        }
    });
}

function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $folder = private_file('sessions');
    if (!is_dir($folder) && !@mkdir($folder, 0700, true) && !is_dir($folder)) fail('Could not create private session storage.', 503, 'storage');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '86400');
    session_save_path($folder);
    session_name('ml'.substr(hash('sha256', private_root()), 0, 12));
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((getenv('MOTORLOG_TRUST_PROXY') === '1') && (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'));
    session_set_cookie_params(['lifetime'=>0, 'path'=>'/', 'secure'=>$secure, 'httponly'=>true, 'samesite'=>'Lax']);
    session_start();
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function check_csrf(): void {
    start_session();
    $token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($token === '' || !hash_equals($_SESSION['csrf'], $token)) fail('This tab has expired. Reload and try again.', 403, 'csrf');
}
function set_browser_user(int $id): void { start_session(); session_regenerate_id(true); $_SESSION['user_id'] = $id; $_SESSION['signed_at'] = time(); $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
function browser_user(PDO $db): ?array {
    start_session();
    if (preview_mode() && getenv('MOTORLOG_DEMO_SIGNIN') === '1' && !isset($_SESSION['user_id']) && !isset($_SESSION['demo_signed_out'])) {
        $demoId = val($db, 'SELECT id FROM users WHERE username=?', ['demo']);
        if ($demoId) set_browser_user((int)$demoId);
    }
    $id = (int)($_SESSION['user_id'] ?? 0);
    if (!$id || time() - (int)($_SESSION['signed_at'] ?? 0) > 86400) return null;
    return one($db, 'SELECT id, username, display_name FROM users WHERE id=?', [$id]);
}
function request_actor(PDO $db, bool $write = false): array {
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($authorization !== '') {
        if (!preg_match('/^Bearer (ml_[0-9a-f]{64})$/iD', $authorization, $m)) fail('Enter a valid Motorlog API token.', 401, 'invalid_token');
        $user = one($db, 'SELECT u.id,u.username,u.display_name FROM api_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=? AND t.expires_at>? AND t.revoked_at IS NULL', [hash('sha256', $m[1]), now_iso()]);
        if (!$user) fail('This API token is invalid, expired, or revoked.', 401, 'invalid_token');
        return $user;
    }
    $user = browser_user($db);
    if (!$user) fail('Sign in or provide a valid API token.', 401, 'authentication_required');
    if ($write) check_csrf();
    session_write_close();
    return $user;
}
function sign_in(PDO $db, array $data): array {
    check_csrf();
    $username = strtolower(text_field($data['username'] ?? '', 'Username', 100, true));
    $password = $data['password'] ?? '';
    if (!is_string($password) || strlen($password) > 512 || str_contains($password, "\0")) fail('The username or password is incorrect.', 401, 'bad_login');
    $subject = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'local'));
    $db->beginTransaction();
    try {
        $db->exec('UPDATE app_settings SET id=id WHERE id=1');
        $limit = one($db, 'SELECT * FROM login_limits WHERE subject=?', [$subject]);
        if ($limit && (int)$limit['until_epoch'] > time() && (int)$limit['attempts'] >= 10) fail('Too many sign-in attempts. Wait a few minutes and try again.', 429, 'login_limit');
        $user = one($db, 'SELECT * FROM users WHERE username=?', [$username]);
        $valid = password_verify($password, $user['password_hash'] ?? '$2y$10$5b1QWkrPmLMQnEoONrdLyOdNHkqjkIv.tM6t7DP08P1GjZZATwSxm') && $user;
        if ($valid) {
            statement($db, 'DELETE FROM login_limits WHERE subject=?', [$subject]);
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) update_row($db, 'users', (int)$user['id'], ['password_hash'=>password_hash($password, PASSWORD_DEFAULT)]);
        } elseif ($limit && (int)$limit['until_epoch'] > time()) {
            statement($db, 'UPDATE login_limits SET attempts=attempts+1 WHERE subject=?', [$subject]);
        } elseif ($limit) {
            statement($db, 'UPDATE login_limits SET attempts=1,until_epoch=? WHERE subject=?', [time()+600,$subject]);
        } else {
            insert_row($db, 'login_limits', ['subject'=>$subject,'attempts'=>1,'until_epoch'=>time()+600]);
        }
        $db->commit();
        if (!$valid) fail('The username or password is incorrect.', 401, 'bad_login');
        set_browser_user((int)$user['id']);
        return ['message'=>'Signed in.'];
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
}
function mutate(PDO $db, array $actor, string $key, string $raw, callable $fn): array {
    if (!preg_match('/^[A-Za-z0-9_-]{12,100}$/D', $key)) fail('Send a unique Idempotency-Key for this write.', 400, 'idempotency_required');
    $hash = hash('sha256', $raw);
    $db->beginTransaction();
    $GLOBALS['motorlog_staged_upload'] = null;
    try {
        $db->exec('UPDATE app_settings SET id=id WHERE id=1');
        $id = (int)$actor['id'];
        $old = one($db, 'SELECT request_hash,response_json FROM idempotency WHERE user_id=? AND request_key=?', [$id, $key]);
        if ($old) {
            if (!hash_equals($old['request_hash'], $hash)) fail('This retry key already belongs to a different submission. Save again as a new request.', 409, 'idempotency_conflict');
            $db->commit(); return json_array($old['response_json']);
        }
        $result = $fn();
        // Never persist a raw token in the idempotency journal; a lost token can be revoked and reissued.
        $stored = $result;
        if (isset($stored['token'])) { unset($stored['token']); $stored['message'] = 'Token already created. A raw token is only shown once; revoke and regenerate it if needed.'; }
        insert_row($db, 'idempotency', ['user_id'=>$id,'request_key'=>$key,'request_hash'=>$hash,'response_json'=>json_text($stored),'created_at'=>now_iso()]);
        $db->commit();
        $GLOBALS['motorlog_staged_upload'] = null;
        return $result;
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        if (is_string($GLOBALS['motorlog_staged_upload'])) @unlink($GLOBALS['motorlog_staged_upload']);
        $GLOBALS['motorlog_staged_upload'] = null;
        throw $e;
    }
}

require_once __DIR__.'/schema.php';
