<?php
declare(strict_types=1);

// Keep this directory outside Apache's document root. No secrets live in the app folder.
function ic_private_dir(): string {
    $custom = getenv('INNERCIRCLE_DATA_DIR');
    if ($custom !== false && $custom !== '') return rtrim($custom, '/\\');
    $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
    if ($documentRoot === '') $documentRoot = dirname(__DIR__);
    return dirname($documentRoot) . DIRECTORY_SEPARATOR . 'innercircle-private-' . substr(hash('sha256', __DIR__), 0, 12);
}
function ic_prepare_dir(): void {
    $dir = ic_private_dir();
    if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('The private data directory could not be created. Set a writable INNERCIRCLE_DATA_DIR outside the web root.');
    if (!is_writable($dir)) throw new RuntimeException('The private data directory is not writable. Check its owner and permissions.');
}
function ic_config(): array {
    $file = ic_private_dir() . '/config.php';
    return is_file($file) ? require $file : [];
}
function ic_installed(): bool { return is_file(ic_private_dir() . '/config.php'); }
function ic_requirements(): void {
    if (PHP_VERSION_ID < 80200) throw new RuntimeException('PHP 8.2 or later is required. PHP 8.5 is supported.');
    foreach (['pdo_sqlite', 'sodium'] as $extension) if (!extension_loaded($extension)) throw new RuntimeException('Enable the ' . $extension . ' PHP extension, then restart Apache.');
}
function ic_db(): PDO {
    static $db;
    if ($db instanceof PDO) return $db;
    $db = new PDO('sqlite:' . ic_private_dir() . '/innercircle.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA busy_timeout=5000');
    $db->exec('CREATE TABLE IF NOT EXISTS ic_records (id VARCHAR(80) PRIMARY KEY, kind VARCHAR(20) NOT NULL, payload TEXT NOT NULL)');
    $db->exec('CREATE INDEX IF NOT EXISTS ic_records_kind ON ic_records(kind)');
    $db->exec('CREATE TABLE IF NOT EXISTS ic_meta (id VARCHAR(30) PRIMARY KEY, payload TEXT NOT NULL)');
    @chmod(ic_private_dir() . '/innercircle.sqlite', 0600);
    return $db;
}
function ic_meta(): array {
    $row = ic_db()->query("SELECT payload FROM ic_meta WHERE id = 'main'")->fetchColumn();
    return $row ? json_decode($row, true, 512, JSON_THROW_ON_ERROR) : [];
}
function ic_set_meta(array $settings): void {
    $stmt = ic_db()->prepare('INSERT INTO ic_meta (id, payload) VALUES (?, ?) ON CONFLICT(id) DO UPDATE SET payload = excluded.payload');
    $stmt->execute(['main', json_encode($settings, JSON_THROW_ON_ERROR)]);
}
function ic_all(string $kind): array {
    $stmt = ic_db()->prepare('SELECT payload FROM ic_records WHERE kind = ? ORDER BY rowid'); $stmt->execute([$kind]);
    return array_map(static fn(array $row): array => json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR), $stmt->fetchAll());
}
function ic_record(string $id, string $kind): ?array {
    $stmt = ic_db()->prepare('SELECT payload FROM ic_records WHERE id = ? AND kind = ?'); $stmt->execute([$id, $kind]); $row = $stmt->fetchColumn();
    return $row ? json_decode($row, true, 512, JSON_THROW_ON_ERROR) : null;
}
function ic_save(string $kind, array $record): void {
    $stmt = ic_db()->prepare('INSERT INTO ic_records (id, kind, payload) VALUES (?, ?, ?) ON CONFLICT(id) DO UPDATE SET kind = excluded.kind, payload = excluded.payload');
    $stmt->execute([$record['id'], $kind, json_encode($record, JSON_THROW_ON_ERROR)]);
}
function ic_delete(string $id, string $kind): void { $stmt = ic_db()->prepare('DELETE FROM ic_records WHERE id = ? AND kind = ?'); $stmt->execute([$id, $kind]); }
function ic_key(?array $config = null): string {
    $key = base64_decode(($config ?? ic_config())['encryptionKey'] ?? '', true);
    if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) throw new RuntimeException('The encryption key is missing. Restore the original private config.php; never replace the key for existing data.');
    return $key;
}
function ic_encrypt(array $data, ?array $config = null): string {
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return base64_encode($nonce . sodium_crypto_secretbox(json_encode($data, JSON_THROW_ON_ERROR), $nonce, ic_key($config)));
}
function ic_decrypt(string $value, ?array $config = null): array {
    $raw = base64_decode($value, true);
    if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) throw new RuntimeException('Invalid encrypted record.');
    $result = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), ic_key($config));
    if ($result === false) throw new RuntimeException('This backup does not match the original encryption key, or has been altered.');
    return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
}
function ic_password_input(string $password): string { return base64_encode(hash('sha384', $password, true)); }
function ic_start_session(): void {
    ini_set('session.use_strict_mode', '1'); ini_set('session.gc_maxlifetime', '7200');
    session_name('innercircle_' . substr(hash('sha256', __DIR__), 0, 10));
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
}
function ic_authorized(array $meta): bool {
    if (empty($_SESSION['ic_owner']) || !hash_equals((string)($meta['authVersion'] ?? ''), (string)($_SESSION['ic_auth_version'] ?? ''))) return false;
    $last = (int)($_SESSION['ic_last_active'] ?? 0);
    if (time() - $last >= ((int)($meta['autoLockMinutes'] ?? 15)) * 60) { unset($_SESSION['ic_owner']); return false; }
    $_SESSION['ic_last_active'] = time(); return true;
}
function ic_sign_in(): void { session_regenerate_id(true); $_SESSION['ic_owner'] = true; $_SESSION['ic_last_active'] = time(); $_SESSION['ic_auth_version'] = ic_meta()['authVersion'] ?? ''; }
function ic_login_attempt(string $mode): void {
    $file = fopen(ic_private_dir() . '/login-attempts.json', 'c+');
    if (!$file || !flock($file, LOCK_EX)) throw new RuntimeException('Could not verify login attempts.');
    try {
        $raw = stream_get_contents($file); $attempt = $raw ? json_decode($raw, true) : null;
        if (!is_array($attempt) || (int)$attempt['until'] < time()) $attempt = ['count' => 0, 'until' => time() + 600];
        if ($mode === 'check' && $attempt['count'] >= 10) throw new DomainException('Too many attempts. Please wait 10 minutes.', 429);
        if ($mode === 'failed') $attempt['count']++;
        if ($mode === 'reset') $attempt = ['count' => 0, 'until' => time() + 600];
        rewind($file); ftruncate($file, 0); fwrite($file, json_encode($attempt, JSON_THROW_ON_ERROR)); fflush($file);
    } finally { flock($file, LOCK_UN); fclose($file); }
}
function ic_text(mixed $value, int $max = 300): string { return is_string($value) ? substr(trim($value), 0, $max) : ''; }
function ic_email(string $value): void { if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) throw new DomainException('Please enter a valid email address.'); }
function ic_valid_date(string $date): bool { $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date); return $d !== false && $d->format('Y-m-d') === $date; }
function ic_age(string $dob, ?string $date = null): int {
    if (!ic_valid_date($dob)) return -1;
    $birth = new DateTimeImmutable($dob); $at = new DateTimeImmutable($date ?? 'today');
    return $birth > $at ? -1 : $birth->diff($at)->y;
}
function ic_mysql(array $config): PDO {
    if (!extension_loaded('pdo_mysql')) throw new DomainException('Enable pdo_mysql in php.ini to use MySQL backup.');
    $m = $config['mysql'] ?? [];
    if (!$m) throw new DomainException('MySQL backup is not configured for this installation.');
    $pdo = new PDO('mysql:host=' . $m['host'] . ';port=' . $m['port'] . ';dbname=' . $m['database'] . ';charset=utf8mb4', $m['username'], $m['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 5]);
    $pdo->exec('CREATE TABLE IF NOT EXISTS innercircle_backups (id VARCHAR(64) PRIMARY KEY, created_at VARCHAR(40) NOT NULL, encrypted_payload LONGTEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    return $pdo;
}
function ic_backup(): array {
    return ['format' => 'innercircle-php-v1', 'exportedAt' => gmdate('c'), 'note' => 'All records are encrypted. Restore requires the original private config.php encryption key. Store that file separately and securely.', 'encryptedPayload' => ic_encrypt(['workspace' => ic_meta(), 'contacts' => ic_all('contact'), 'accounts' => ic_all('account'), 'journals' => ic_all('journal')])];
}
function ic_headers(): void {
    header('Cache-Control: no-store, private'); header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: DENY'); header('Referrer-Policy: no-referrer');
}
function ic_render(bool $install = false): void {
    ic_headers(); $nonce = base64_encode(random_bytes(18));
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
    $prefix = $install ? '../' : '';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>innercircle — Your people, in one place.</title><link rel="icon" href="' . $prefix . 'assets/icon.svg"><link rel="stylesheet" href="' . $prefix . 'assets/app.css"></head><body><div id="root"></div><noscript>This private workspace needs JavaScript. Please enable it in your browser.</noscript><script nonce="' . $nonce . '">window.__PHP__=true;window.__INSTALL__=' . ($install ? 'true' : 'false') . ';</script><script src="' . $prefix . 'assets/app.js" defer></script></body></html>';
}
