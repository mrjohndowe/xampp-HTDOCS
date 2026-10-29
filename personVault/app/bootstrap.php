<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
const APP_MODULES = ['contacts', 'accounts', 'journal', 'intimacy'];

function app_headers(): void
{
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
    header('Cache-Control: no-store, private');
}

function app_json(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function app_config_path(): ?string
{
    $lock = APP_ROOT . '/var/install.lock';
    if (!is_file($lock)) return null;
    $path = trim((string)file_get_contents($lock));
    return $path !== '' && is_file($path . '/config.php') ? $path . '/config.php' : null;
}

function app_config(): array
{
    $path = app_config_path();
    if (!$path) throw new RuntimeException('PersonVault is not installed. Open install.php to begin.');
    $config = require $path;
    if (!is_array($config) || empty($config['database']) || empty($config['encryption_key_file'])) {
        throw new RuntimeException('Installation configuration is incomplete.');
    }
    if (!empty($config['timezone'])) {
        try { date_default_timezone_set((string)$config['timezone']); } catch (Throwable) { date_default_timezone_set('UTC'); }
    }
    return $config;
}

function app_db(): PDO
{
    if (($GLOBALS['personvault_pdo'] ?? null) instanceof PDO) return $GLOBALS['personvault_pdo'];
    $config = app_config();
    $db = $config['database'];
    if (($db['driver'] ?? '') === 'sqlite') {
        $pdo = new PDO('sqlite:' . $db['sqlite_path'], null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
    } elseif (($db['driver'] ?? '') === 'mysql') {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['mysql_host'], (int)$db['mysql_port'], $db['mysql_database']);
        $pdo = new PDO($dsn, $db['mysql_username'], $db['mysql_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    } else {
        throw new RuntimeException('Unsupported database driver.');
    }
    $GLOBALS['personvault_pdo'] = $pdo;
    return $pdo;
}

function app_db_close(): void { $GLOBALS['personvault_pdo'] = null; }

function app_encrypt(array $value): string
{
    $config = app_config();
    $key = @file_get_contents($config['encryption_key_file']);
    if ($key === false || strlen($key) !== 32) throw new RuntimeException('Encryption key is unavailable or invalid.');
    $iv = random_bytes(12);
    $tag = '';
    $plain = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, 'PersonVault:v1', 16);
    if ($cipher === false) throw new RuntimeException('Unable to encrypt this record.');
    return 'v1.' . base64_encode($iv . $tag . $cipher);
}

function app_decrypt(string $value): array
{
    if (!str_starts_with($value, 'v1.')) throw new RuntimeException('Unsupported encrypted record format.');
    $config = app_config();
    $key = @file_get_contents($config['encryption_key_file']);
    if ($key === false || strlen($key) !== 32) throw new RuntimeException('Encryption key is unavailable or invalid.');
    $packed = base64_decode(substr($value, 3), true);
    if ($packed === false || strlen($packed) < 29) throw new RuntimeException('Encrypted record is damaged.');
    $plain = openssl_decrypt(substr($packed, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($packed, 0, 12), substr($packed, 12, 16), 'PersonVault:v1');
    if ($plain === false) throw new RuntimeException('Encrypted record authentication failed.');
    $decoded = json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
    return is_array($decoded) ? $decoded : [];
}

function app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('personvault_session');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict']);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    session_start();
}

function app_csrf(): string
{
    app_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function app_user(): ?array
{
    app_session();
    if (empty($_SESSION['user_id'])) return null;
    $stmt = app_db()->prepare('SELECT id, email, role, theme, date_format FROM users WHERE id = ? AND disabled = 0');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) { $_SESSION = []; session_destroy(); return null; }
    return $user;
}

function app_require_user(): array
{
    $user = app_user();
    if (!$user) app_json(['error' => 'Sign in to continue.'], 401);
    return $user;
}

function app_input(): array
{
    $raw = file_get_contents('php://input');
    if (strlen($raw ?: '') > 1000000) app_json(['error' => 'Request is too large.'], 413);
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) app_json(['error' => 'Invalid request data.'], 400);
    return $data;
}

function app_check_csrf(): void
{
    app_session();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        app_json(['error' => 'Your session token expired. Reload the page and try again.'], 419);
    }
}

function app_now(): string { return gmdate('Y-m-d H:i:s'); }

function app_is_loopback(): bool
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $packed = @inet_pton($ip);
    if ($packed === false) return false;
    if (strlen($packed) === 4) return ord($packed[0]) === 127;
    return $packed === inet_pton('::1');
}
