<?php
declare(strict_types=1);

function haven_storage(): string {
    $dir = dirname(__DIR__) . '/storage';
    if (!is_dir($dir)) mkdir($dir, 0700, true);
    return $dir;
}

function haven_config(): array {
    $file = haven_storage() . '/config.php';
    if (!is_file($file)) {
        return [
            'app_name' => 'Haven',
            'driver' => 'sqlite',
            'sqlite_path' => haven_storage() . '/haven.sqlite',
            'mysql' => ['host' => '127.0.0.1', 'database' => 'haven', 'user' => 'root', 'password' => '', 'charset' => 'utf8mb4'],
            'timezone' => 'UTC',
            'date_format' => 'YYYY-MM-DD',
            'registration_enabled' => false,
            'session_hours' => 12,
        ];
    }
    return require $file;
}

function haven_write_config(array $cfg): void {
    $export = var_export($cfg, true);
    file_put_contents(haven_storage() . '/config.php', "<?php\nreturn {$export};\n", LOCK_EX);
}

function haven_secret_path(): string {
    return haven_storage() . '/app.secret';
}

function haven_app_secret(): string {
    $env = getenv('APP_SECRET');
    if (is_string($env) && $env !== '') return hash('sha256', $env, true);
    $path = haven_secret_path();
    if (!is_file($path)) throw new RuntimeException('APP_SECRET missing');
    $hex = trim((string)file_get_contents($path));
    return ctype_xdigit($hex) && strlen($hex) === 64 ? hex2bin($hex) : hash('sha256', $hex, true);
}

function haven_generate_secret(): string {
    $hex = bin2hex(random_bytes(32));
    file_put_contents(haven_secret_path(), $hex, LOCK_EX);
    @chmod(haven_secret_path(), 0600);
    return $hex;
}

function haven_encrypt(string $plain, string $key): string {
    if ($plain === '') return '';
    $iv = random_bytes(12);
    $tag = '';
    $enc = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($enc === false) throw new RuntimeException('encrypt failed');
    return 'v1:' . base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($enc);
}

function haven_decrypt(string $payload, string $key): string {
    if ($payload === '') return '';
    $parts = explode(':', $payload);
    if (count($parts) !== 4 || $parts[0] !== 'v1') throw new RuntimeException('bad ciphertext');
    $iv = base64_decode($parts[1], true);
    $tag = base64_decode($parts[2], true);
    $data = base64_decode($parts[3], true);
    $plain = openssl_decrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plain === false) throw new RuntimeException('decrypt failed');
    return $plain;
}

function haven_pdo(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $cfg = haven_config();
    if (($cfg['driver'] ?? 'sqlite') === 'mysql') {
        $m = $cfg['mysql'];
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $m['host'], $m['database'], $m['charset'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $m['user'], $m['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } else {
        $path = $cfg['sqlite_path'] ?? haven_storage() . '/haven.sqlite';
        $pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}

function haven_schema(): void {
    $pdo = haven_pdo();
    $id = 'VARCHAR(64)';
    $pdo->exec("CREATE TABLE IF NOT EXISTS install_state (id $id PRIMARY KEY, installed INTEGER NOT NULL DEFAULT 0, app_name VARCHAR(120), timezone VARCHAR(80), date_format VARCHAR(32), registration_enabled INTEGER NOT NULL DEFAULT 0, session_hours INTEGER NOT NULL DEFAULT 12, installer_unlocked_until VARCHAR(40), installed_at VARCHAR(40))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (id $id PRIMARY KEY, email VARCHAR(190) UNIQUE NOT NULL, username VARCHAR(80) UNIQUE NOT NULL, display_name VARCHAR(120) NOT NULL, password_hash VARCHAR(255) NOT NULL, kek_salt VARCHAR(128) NOT NULL, wrapped_dek TEXT NOT NULL, role VARCHAR(16) NOT NULL DEFAULT 'user', theme VARCHAR(16) NOT NULL DEFAULT 'system', timezone VARCHAR(80) NOT NULL DEFAULT 'UTC', date_format VARCHAR(32) NOT NULL DEFAULT 'YYYY-MM-DD', is_active INTEGER NOT NULL DEFAULT 1, vault_locked INTEGER NOT NULL DEFAULT 0, created_at VARCHAR(40), last_login_at VARCHAR(40))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (id $id PRIMARY KEY, user_id VARCHAR(64) NOT NULL, token_hash VARCHAR(64) UNIQUE NOT NULL, dek_enc TEXT NOT NULL, reauth_until VARCHAR(40), expires_at VARCHAR(40) NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS contacts (id $id PRIMARY KEY, owner_id VARCHAR(64) NOT NULL, first_name VARCHAR(120) DEFAULT '', middle_name VARCHAR(120) DEFAULT '', last_name VARCHAR(120) DEFAULT '', display_name VARCHAR(160) DEFAULT '', date_of_birth VARCHAR(10), email VARCHAR(190) DEFAULT '', phone VARCHAR(80) DEFAULT '', phone_secondary VARCHAR(80) DEFAULT '', street VARCHAR(255) DEFAULT '', city VARCHAR(120) DEFAULT '', state VARCHAR(120) DEFAULT '', zip VARCHAR(32) DEFAULT '', relationship VARCHAR(80) DEFAULT 'Other', company VARCHAR(160) DEFAULT '', job_title VARCHAR(160) DEFAULT '', notes_enc TEXT, tags TEXT, created_at VARCHAR(40), updated_at VARCHAR(40), deleted_at VARCHAR(40))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS accounts (id $id PRIMARY KEY, owner_id VARCHAR(64) NOT NULL, contact_id VARCHAR(64), name VARCHAR(190) DEFAULT '', type VARCHAR(80) DEFAULT 'Website or online service', url VARCHAR(255) DEFAULT '', username VARCHAR(190) DEFAULT '', login_email VARCHAR(190) DEFAULT '', password_enc TEXT, recovery_email VARCHAR(190) DEFAULT '', security_qa_enc TEXT, recovery_codes_enc TEXT, notes_enc TEXT, tags TEXT, show_banking_fields INTEGER DEFAULT 0, bank_name VARCHAR(190) DEFAULT '', account_holder VARCHAR(190) DEFAULT '', routing_number_enc TEXT, account_number_enc TEXT, bank_account_type VARCHAR(80) DEFAULT '', banking_notes_enc TEXT, created_at VARCHAR(40), updated_at VARCHAR(40), deleted_at VARCHAR(40))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS journal_entries (id $id PRIMARY KEY, owner_id VARCHAR(64) NOT NULL, title VARCHAR(255) DEFAULT '', entry_at VARCHAR(40), category VARCHAR(80) DEFAULT 'Personal', body_enc TEXT, contact_id VARCHAR(64), tags TEXT, mood INTEGER, private_notes_enc TEXT, created_at VARCHAR(40), updated_at VARCHAR(40), deleted_at VARCHAR(40))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS intimacy_events (id $id PRIMARY KEY, owner_id VARCHAR(64) NOT NULL, event_date VARCHAR(10) NOT NULL, start_time VARCHAR(16) DEFAULT '', end_time VARCHAR(16) DEFAULT '', duration_minutes INTEGER, event_type VARCHAR(80) DEFAULT 'Date', location_enc TEXT, privacy_level VARCHAR(40) DEFAULT 'Private', description_enc TEXT, notes_enc TEXT, tags TEXT, rating_overall INTEGER, rating_enjoyment INTEGER, rating_emotional INTEGER, rating_communication INTEGER, rating_comfort INTEGER, rating_trust INTEGER, rating_satisfaction INTEGER, rating_after_feelings INTEGER, questionnaire_enc TEXT, optional_details_enc TEXT, created_at VARCHAR(40), updated_at VARCHAR(40), deleted_at VARCHAR(40), contact_ids TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_lists (id $id PRIMARY KEY, owner_id VARCHAR(64) NOT NULL, kind VARCHAR(40) NOT NULL, value VARCHAR(255) NOT NULL, sort_order INTEGER DEFAULT 0, active INTEGER DEFAULT 1)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_contacts_owner ON contacts(owner_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_accounts_owner ON accounts(owner_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_journal_owner ON journal_entries(owner_id, entry_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_intimacy_owner ON intimacy_events(owner_id, event_date)");
}

function haven_id(): string { return bin2hex(random_bytes(16)); }
function haven_now(): string { return gmdate('c'); }

function haven_installed(): bool {
    $lock = haven_storage() . '/installed.lock';
    if (!is_file($lock) || !is_file(haven_secret_path())) return false;
    try {
        haven_schema();
        $row = haven_pdo()->query("SELECT installed FROM install_state WHERE id='default'")->fetch(PDO::FETCH_ASSOC);
        return $row && (int)$row['installed'] === 1;
    } catch (Throwable) {
        return false;
    }
}

function haven_lock_install(): void {
    file_put_contents(haven_storage() . '/installed.lock', haven_now(), LOCK_EX);
}

function haven_json($data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data);
    exit;
}

function haven_age(?string $dob): ?int {
    if (!$dob || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) return null;
    $d = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$d) return null;
    return (int)$d->diff(new DateTime('now'))->y;
}

function haven_seed_lists(PDO $pdo, string $owner): void {
    $lists = [
        'relationship' => ['Mother','Father','Brother','Sister','Son','Daughter','Friend','Best-Friend','Family','Work','Girl-Friend','Boy-Friend','Partner','Ex','Fling','Friends with Benefits','One Night Stand','Other'],
        'account_type' => ['Website or online service','Email account','Social media account','Gaming account','Subscription','Banking account','Credit or debit card record','Investment account','Utility account','Other'],
        'journal_category' => ['Personal','Work','Health','Gratitude','Dreams','Travel','Family','Reflection','Other'],
        'event_type' => ['Date','Romantic encounter','Affectionate activity','Intimate encounter','Sexual activity','Other'],
    ];
    $st = $pdo->prepare('INSERT INTO user_lists (id, owner_id, kind, value, sort_order, active) VALUES (?,?,?,?,?,1)');
    foreach ($lists as $kind => $values) {
        foreach ($values as $i => $value) $st->execute([haven_id(), $owner, $kind, $value, $i]);
    }
}

function haven_create_user(array $input, string $role = 'user'): array {
    $pdo = haven_pdo();
    $dek = random_bytes(32);
    $salt = random_bytes(16);
    $kek = hash_pbkdf2('sha256', $input['password'], $salt, 120000, 32, true);
    $id = haven_id();
    $pdo->prepare('INSERT INTO users (id,email,username,display_name,password_hash,kek_salt,wrapped_dek,role,created_at) VALUES (?,?,?,?,?,?,?,?,?)')
        ->execute([
            $id,
            strtolower(trim($input['email'])),
            trim($input['username']),
            trim($input['displayName'] ?? $input['username']),
            password_hash($input['password'], PASSWORD_DEFAULT),
            base64_encode($salt),
            haven_encrypt(base64_encode($dek), $kek),
            $role,
            haven_now(),
        ]);
    haven_seed_lists($pdo, $id);
    return ['id' => $id, 'dek' => $dek];
}

function haven_current(): ?array {
    $token = $_COOKIE['haven_sid'] ?? '';
    if ($token === '') return null;
    $pdo = haven_pdo();
    $st = $pdo->prepare('SELECT s.*, u.email, u.username, u.display_name, u.role, u.theme, u.is_active, u.vault_locked FROM sessions s JOIN users u ON u.id = s.user_id WHERE s.token_hash = ?');
    $st->execute([hash('sha256', $token)]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row || $row['expires_at'] < haven_now() || !(int)$row['is_active']) return null;
    try {
        $dek = base64_decode(haven_decrypt($row['dek_enc'], haven_app_secret()), true);
    } catch (Throwable) {
        return null;
    }
    return ['user' => $row, 'dek' => $dek, 'reauth' => $row['reauth_until'] && $row['reauth_until'] > haven_now()];
}

function haven_require(): array {
    $auth = haven_current();
    if (!$auth) haven_json(['ok' => false, 'error' => 'Unauthorized'], 401);
    return $auth;
}

function haven_env_report(): array {
    return [
        'php' => PHP_VERSION,
        'phpOk' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'pdo_sqlite' => extension_loaded('pdo_sqlite'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'openssl' => extension_loaded('openssl'),
        'storageWritable' => is_writable(haven_storage()),
        'storagePath' => haven_storage(),
    ];
}
