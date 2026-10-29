<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
app_headers();
app_session();

function api_module(string $module): string
{
    if (!in_array($module, APP_MODULES, true)) app_json(['error' => 'Unknown section.'], 404);
    return $module;
}

function api_record(PDO $pdo, int $owner, string $module, string $id, bool $includeDeleted = false): ?array
{
    $sql = 'SELECT * FROM records WHERE id = ? AND owner_id = ? AND module = ?' . ($includeDeleted ? '' : ' AND deleted_at IS NULL');
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $owner, $module]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function api_safe_data(string $module, array $data): array
{
    $sensitive = ['password', 'secret', 'routing_number', 'account_number', 'security_answers', 'recovery_codes'];
    if ($module !== 'accounts') return $data;
    foreach ($sensitive as $key) {
        if (array_key_exists($key, $data)) {
            $data['has_' . $key] = $data[$key] !== '' && $data[$key] !== null;
            if (in_array($key, ['routing_number', 'account_number'], true) && $data[$key] !== '') {
                $digits = preg_replace('/\D/', '', (string)$data[$key]);
                $data['masked_' . $key] = $digits === '' ? 'Saved' : '•••• ' . substr($digits, -4);
            }
            unset($data[$key]);
        }
    }
    return $data;
}

function api_upload(PDO $pdo, array $user, array $data): never
{
    $module = api_module((string)($data['module'] ?? ''));
    $values = $data['data'] ?? null;
    if (!is_array($values)) app_json(['error' => 'Record data is required.'], 422);
    $id = (string)($data['id'] ?? '');
    $now = app_now();
    $contactId = isset($values['contact_id']) && $values['contact_id'] !== '' ? (string)$values['contact_id'] : null;
    unset($values['id'], $values['_meta']);
    $allowedFields = [
        'contacts' => ['first_name','middle_name','last_name','display_name','date_of_birth','email','phone','secondary_phone','relationship','company','job_title','street','city','state','zip_code','notes','tags'],
        'accounts' => ['contact_id','service_name','account_type','website','username','login_email','password','secret','recovery_email','security_answers','recovery_codes','bank_name','account_holder','routing_number','account_number','bank_account_type','notes','tags'],
        'journal' => ['contact_id','title','entry_date','category','body','mood','private_notes','tags'],
        'intimacy' => ['contact_id','event_date','start_time','end_time','event_type','location','privacy','description','personal_notes','question_1','question_2','question_3','question_4','question_5','question_6','question_7','question_8','question_9','question_10','question_11','question_12','question_13','question_14','question_15','question_16','activity_details','preferences','boundaries','communication_notes','emotional_reactions','relationship_context','follow_up','custom_qa','rating_overall','rating_enjoyment','rating_emotional_connection','rating_communication','rating_comfort','rating_trust','rating_satisfaction','rating_after_event','tags'],
    ];
    foreach ($values as $key => $value) {
        if (!in_array((string)$key, $allowedFields[$module], true)) app_json(['error' => 'This record contains an unsupported field.'], 422);
        if ((string)$key === 'tags') {
            if (!is_array($value)) $value = array_filter(array_map('trim', explode(',', (string)$value)));
            $value = array_slice(array_values(array_filter(array_map(static fn($tag) => is_scalar($tag) ? substr(trim((string)$tag), 0, 64) : '', $value))), 0, 50);
            $values[$key] = $value;
        } else {
            if (!is_scalar($value) && $value !== null) app_json(['error' => 'Record values must be text or numbers.'], 422);
            if (strlen((string)$value) > 60000) app_json(['error' => 'A field is too long.'], 422);
            if (is_string($value)) $values[$key] = trim($value);
        }
    }

    if ($contactId !== null) {
        $contact = api_record($pdo, (int)$user['id'], 'contacts', $contactId);
        if (!$contact) app_json(['error' => 'Choose a contact that belongs to your account.'], 422);
        if ($module === 'contacts') app_json(['error' => 'A contact cannot be linked to itself.'], 422);
    }
    if ($module === 'accounts') {
        $type = strtolower(trim((string)($values['account_type'] ?? 'other')));
        if ($type === '' || strlen($type) > 64) app_json(['error' => 'Enter an account type of 1 to 64 characters.'], 422);
        $values['account_type'] = $type;
    }
    if ($module === 'contacts') {
        $email = trim((string)($values['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) app_json(['error' => 'Enter a valid email address.'], 422);
        if (isset($values['date_of_birth']) && $values['date_of_birth'] !== '') {
            $dob = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$values['date_of_birth']);
            if (!$dob || $dob->format('Y-m-d') !== (string)$values['date_of_birth'] || $dob > new DateTimeImmutable('today')) app_json(['error' => 'Enter a valid date of birth.'], 422);
        }
    }
    if ($module === 'journal' && isset($values['entry_date']) && $values['entry_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}(?:T\d{2}:\d{2})?$/', (string)$values['entry_date'])) app_json(['error' => 'Enter a valid journal date and time.'], 422);
    if ($module === 'intimacy' && isset($values['event_date']) && $values['event_date'] !== '') {
        $eventDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$values['event_date']);
        if (!$eventDate || $eventDate->format('Y-m-d') !== (string)$values['event_date']) app_json(['error' => 'Enter a valid event date.'], 422);
    }
    if ($module === 'intimacy') {
        foreach (['question_2','question_3'] as $consentKey) {
            $consent = $values[$consentKey] ?? '';
            if ($consent !== '' && !in_array($consent, ['yes','no','unknown','not_recorded'], true)) app_json(['error' => 'Choose a valid answer for the consent question.'], 422);
        }
        foreach ($values as $key => $value) if ((str_starts_with((string)$key, 'rating_') || preg_match('/^question_[4-8]$/', (string)$key)) && $value !== '' && (!ctype_digit((string)$value) || (int)$value < 1 || (int)$value > 10)) app_json(['error' => 'Ratings must be whole numbers from 1 to 10.'], 422);
    }

    if ($id !== '') {
        $existing = api_record($pdo, (int)$user['id'], $module, $id);
        if (!$existing) app_json(['error' => 'That record is unavailable.'], 404);
        $old = app_decrypt($existing['payload']);
        $clear = $data['clear_sensitive'] ?? [];
        foreach (['password','secret','routing_number','account_number','security_answers','recovery_codes'] as $key) {
            if (in_array($key, $clear, true)) unset($old[$key]);
            elseif (!array_key_exists($key, $values) || $values[$key] === '' || $values[$key] === null) {
                if (array_key_exists($key, $old)) $values[$key] = $old[$key];
            }
        }
        $values = array_replace($old, $values);
        $stmt = $pdo->prepare('UPDATE records SET payload = ?, contact_id = ?, updated_at = ? WHERE id = ? AND owner_id = ? AND module = ?');
        $stmt->execute([app_encrypt($values), $contactId, $now, $id, (int)$user['id'], $module]);
    } else {
        $id = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare('INSERT INTO records(id, owner_id, module, contact_id, payload, created_at, updated_at) VALUES(?,?,?,?,?,?,?)');
        $stmt->execute([$id, (int)$user['id'], $module, $contactId, app_encrypt($values), $now, $now]);
    }
    app_json(['ok' => true, 'id' => $id, 'updated_at' => $now]);
}

try {
    $action = (string)($_GET['action'] ?? 'bootstrap');
    if ($action === 'bootstrap') {
        $installed = app_config_path() !== null;
        $user = $installed ? app_user() : null;
        $counts = [];
        if ($user) {
            foreach (APP_MODULES as $module) {
                $s = app_db()->prepare('SELECT COUNT(*) FROM records WHERE owner_id = ? AND module = ? AND deleted_at IS NULL');
                $s->execute([(int)$user['id'], $module]); $counts[$module] = (int)$s->fetchColumn();
            }
        }
        app_json(['installed' => $installed, 'user' => $user, 'csrf' => app_csrf(), 'app_name' => $installed ? (app_config()['app_name'] ?? 'PersonVault') : 'PersonVault', 'counts' => $counts]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') app_json(['error' => 'Use POST for this action.'], 405);
    app_check_csrf();
    $body = app_input();

    if ($action === 'login') {
        $pdo = app_db();
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $stmt = $pdo->prepare('SELECT id, email, role, password_hash, theme, date_format FROM users WHERE email = ? AND disabled = 0');
        $stmt->execute([$email]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify((string)($body['password'] ?? ''), $row['password_hash'])) app_json(['error' => 'Email or password was not recognized.'], 401);
        session_regenerate_id(true); $_SESSION['user_id'] = (int)$row['id']; $_SESSION['csrf'] = bin2hex(random_bytes(32));
        app_json(['ok' => true, 'csrf' => $_SESSION['csrf']]);
    }
    if ($action === 'logout') {
        $_SESSION = []; session_regenerate_id(true); $_SESSION['csrf'] = bin2hex(random_bytes(32));
        app_json(['ok' => true]);
    }

    if ($action === 'verify_database') {
        if (!app_is_loopback()) app_json(['error' => 'First-time installation checks are accepted from localhost only.'], 403);
        if (!is_dir(APP_ROOT . '/var') || !is_writable(APP_ROOT . '/var')) app_json(['error' => 'The project var folder must be writable for the installation lock.'], 500);
        $settings = $body['settings'] ?? [];
        if (!is_array($settings)) app_json(['error' => 'Installer settings are invalid.'], 422);
        $driver = (string)($settings['driver'] ?? 'sqlite');
        $private = trim((string)($settings['private_dir'] ?? ''));
        $docroot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__ . '/public')));
        if (!in_array($driver, ['sqlite','mysql'], true)) app_json(['error' => 'Choose SQLite or MySQL.'], 422);
        if (!preg_match('/^(?:[A-Za-z]:[\\\\\/]|\\\\\\\\|\/)/', $private)) app_json(['error' => 'Choose an absolute private storage path.'], 422);
        $normalized = rtrim(str_replace('\\', '/', $private), '/'); $rootNormalized = $docroot ? rtrim(str_replace('\\', '/', $docroot), '/') : '';
        if ($rootNormalized !== '' && (strcasecmp($normalized, $rootNormalized) === 0 || stripos($normalized . '/', $rootNormalized . '/') === 0)) app_json(['error' => 'Private storage must be outside the web document folder.'], 422);
        if ($driver === 'sqlite') {
            if (!extension_loaded('pdo_sqlite')) app_json(['error' => 'PDO SQLite is not enabled.'], 422);
            $dataDir = $private . DIRECTORY_SEPARATOR . 'data';
            if (!is_dir($dataDir) && !mkdir($dataDir, 0700, true) && !is_dir($dataDir)) app_json(['error' => 'Could not create the private database folder.'], 500);
            $probe = $dataDir . DIRECTORY_SEPARATOR . '.personvault-check';
            if (file_put_contents($probe, 'ok', LOCK_EX) === false) app_json(['error' => 'The private database folder is not writable.'], 500);
            @unlink($probe);
            $pdo = new PDO('sqlite:' . $dataDir . DIRECTORY_SEPARATOR . 'personvault.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->query('SELECT 1')->fetchColumn();
        } else {
            if (!extension_loaded('pdo_mysql')) app_json(['error' => 'PDO MySQL is not enabled.'], 422);
            $host = trim((string)($settings['mysql_host'] ?? '127.0.0.1')); $port = (int)($settings['mysql_port'] ?? 3306);
            $name = trim((string)($settings['mysql_database'] ?? '')); $username = (string)($settings['mysql_username'] ?? '');
            $password = (string)($settings['mysql_password'] ?? '');
            if ($host === '' || !preg_match('/^[A-Za-z0-9_$-]+$/', $name) || $port < 1 || $port > 65535 || $username === '') app_json(['error' => 'Enter a valid MySQL host, port, database, and username.'], 422);
            $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name), $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
            $pdo->query('SELECT 1')->fetchColumn();
            $privateData = $private . DIRECTORY_SEPARATOR . 'data';
            if (!is_dir($privateData) && !mkdir($privateData, 0700, true) && !is_dir($privateData)) app_json(['error' => 'Could not create the private storage folder.'], 500);
        }
        app_json(['ok' => true, 'message' => 'Database connection and private storage are ready.']);
    }

    if ($action === 'install') {
        if (!app_is_loopback()) app_json(['error' => 'First-time installation is accepted from localhost only.'], 403);
        if (app_config_path() !== null) app_json(['error' => 'PersonVault is already installed.'], 409);
        $settings = $body['settings'] ?? [];
        if (!is_array($settings)) app_json(['error' => 'Installer settings are invalid.'], 422);
        $driver = (string)($settings['driver'] ?? 'sqlite');
        if (!in_array($driver, ['sqlite','mysql'], true)) app_json(['error' => 'Choose SQLite or MySQL.'], 422);
        if (PHP_VERSION_ID < 80200 || !extension_loaded('openssl') || !extension_loaded('pdo')) app_json(['error' => 'PHP 8.2+ with OpenSSL and PDO is required. PHP 8.5 is recommended.'], 422);
        if ($driver === 'sqlite' && !extension_loaded('pdo_sqlite')) app_json(['error' => 'The PDO SQLite extension is required.'], 422);
        if ($driver === 'mysql' && !extension_loaded('pdo_mysql')) app_json(['error' => 'The PDO MySQL extension is required.'], 422);
        $email = strtolower(trim((string)($settings['admin_email'] ?? '')));
        $password = (string)($settings['admin_password'] ?? '');
        $confirm = (string)($settings['admin_confirm'] ?? '');
        $appName = trim((string)($settings['app_name'] ?? 'PersonVault'));
        $tz = (string)($settings['timezone'] ?? 'UTC');
        $dateFormat = (string)($settings['date_format'] ?? 'Y-m-d');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || !hash_equals($password, $confirm)) app_json(['error' => 'Enter a valid admin email and matching passwords of at least 12 characters.'], 422);
        if ($appName === '' || strlen($appName) > 80) app_json(['error' => 'Application name must be between 1 and 80 characters.'], 422);
        try { new DateTimeZone($tz); } catch (Throwable) { app_json(['error' => 'Choose a valid time zone.'], 422); }
        if (!in_array($dateFormat, ['Y-m-d','m/d/Y','d/m/Y'], true)) app_json(['error' => 'Choose a supported date format.'], 422);

        $docroot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__ . '/public')));
        $private = trim((string)($settings['private_dir'] ?? ''));
        if ($private === '') $private = dirname($docroot ?: dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'personVault_private';
        if (!preg_match('/^(?:[A-Za-z]:[\\\\\/]|\\\\\\\\|\/)/', $private)) app_json(['error' => 'Choose an absolute private storage path outside the website folder.'], 422);
        $normalized = rtrim(str_replace('\\', '/', $private), '/');
        $rootNormalized = $docroot ? rtrim(str_replace('\\', '/', $docroot), '/') : '';
        if ($rootNormalized !== '' && (strcasecmp($normalized, $rootNormalized) === 0 || stripos($normalized . '/', $rootNormalized . '/') === 0)) app_json(['error' => 'Private storage must be outside the web document folder.'], 422);
        if (app_config_path() !== null) app_json(['error' => 'PersonVault is already installed.'], 409);
        $lockPath = APP_ROOT . '/var/install.lock';
        if (is_file($lockPath)) app_json(['error' => 'Installation lock exists but its configuration is unavailable. Follow the recovery steps in SECURITY.md.'], 409);
        $dataDir = $private . DIRECTORY_SEPARATOR . 'data';
        $keyDir = $private . DIRECTORY_SEPARATOR . 'keys';
        if (!is_dir($dataDir) && !mkdir($dataDir, 0700, true) && !is_dir($dataDir)) app_json(['error' => 'Could not create the private database folder.'], 500);
        if (!is_dir($keyDir) && !mkdir($keyDir, 0700, true) && !is_dir($keyDir)) app_json(['error' => 'Could not create the encryption-key folder.'], 500);
        $keyPath = $keyDir . DIRECTORY_SEPARATOR . 'personvault.key';
        if (is_file($keyPath)) app_json(['error' => 'A key already exists at the selected path. Choose a fresh private storage directory.'], 409);
        $key = random_bytes(32);
        if (file_put_contents($keyPath, $key, LOCK_EX) !== 32) app_json(['error' => 'Could not securely write the encryption key.'], 500);
        @chmod($keyPath, 0600);

        $db = ['driver' => $driver];
        if ($driver === 'sqlite') {
            $db['sqlite_path'] = $dataDir . DIRECTORY_SEPARATOR . 'personvault.sqlite';
            $pdo = new PDO('sqlite:' . $db['sqlite_path'], null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $host = trim((string)($settings['mysql_host'] ?? '127.0.0.1'));
            $port = (int)($settings['mysql_port'] ?? 3306);
            $name = trim((string)($settings['mysql_database'] ?? ''));
            $username = (string)($settings['mysql_username'] ?? '');
            $db['mysql_host'] = $host; $db['mysql_port'] = $port; $db['mysql_database'] = $name;
            $db['mysql_username'] = $username; $db['mysql_password'] = (string)($settings['mysql_password'] ?? ''); $db['mysql_charset'] = 'utf8mb4';
            if ($host === '' || !preg_match('/^[A-Za-z0-9_$-]+$/', $name) || $port < 1 || $port > 65535 || $username === '') app_json(['error' => 'Enter a valid MySQL host, port, database, and username.'], 422);
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);
            $pdo = new PDO($dsn, $username, $db['mysql_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
        }
        require dirname(__DIR__) . '/app/migrate.php';
        app_migrate($pdo);
        $exists = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($exists !== 0) app_json(['error' => 'The selected database already has users. Choose an empty database to avoid replacing or reinitializing accounts.'], 409);
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO users(email,password_hash,role,date_format,created_at) VALUES(?,?,?,?,?)');
            $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), 'admin', $dateFormat, app_now()]);
            $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
        $config = ['database' => $db, 'encryption_key_file' => $keyPath, 'app_name' => $appName, 'timezone' => $tz, 'date_format' => $dateFormat, 'installed_at' => app_now()];
        $configText = "<?php\ndeclare(strict_types=1);\nreturn " . var_export($config, true) . ";\n";
        if (file_put_contents($private . DIRECTORY_SEPARATOR . 'config.php', $configText, LOCK_EX) === false) app_json(['error' => 'Could not write the private configuration file. Keep the private folder and follow the recovery steps.'], 500);
        @chmod($private . DIRECTORY_SEPARATOR . 'config.php', 0600);
        if (file_put_contents($lockPath, $private, LOCK_EX) === false) app_json(['error' => 'Could not write the installation lock. The database and private files are present; follow the recovery steps in SECURITY.md.'], 500);
        app_json(['ok' => true, 'message' => 'Installation complete. You can now sign in.']);
    }

    $pdo = app_db();
    $user = app_require_user();
    $methodAction = (string)($body['action'] ?? $action);
    if ($methodAction === 'preferences') {
        $theme = (string)($body['theme'] ?? 'system');
        if (!in_array($theme, ['light','dark','system'], true)) app_json(['error' => 'Choose light, dark, or system theme.'], 422);
        $dateFormat = (string)($body['date_format'] ?? $user['date_format']);
        if (!in_array($dateFormat, ['Y-m-d','m/d/Y','d/m/Y'], true)) app_json(['error' => 'Choose a supported date format.'], 422);
        $s = $pdo->prepare('UPDATE users SET theme = ?, date_format = ? WHERE id = ?'); $s->execute([$theme, $dateFormat, (int)$user['id']]);
        app_json(['ok' => true]);
    }
    if ($methodAction === 'change_password') {
        $currentPassword = (string)($body['current_password'] ?? ''); $newPassword = (string)($body['new_password'] ?? '');
        if (strlen($newPassword) < 12 || !hash_equals($newPassword, (string)($body['confirm_password'] ?? ''))) app_json(['error' => 'New passwords must match and contain at least 12 characters.'], 422);
        $check = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?'); $check->execute([(int)$user['id']]);
        if (!password_verify($currentPassword, (string)$check->fetchColumn())) app_json(['error' => 'Current password was not recognized.'], 401);
        $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?'); $update->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int)$user['id']]);
        session_regenerate_id(true);
        app_json(['ok' => true]);
    }
    if ($methodAction === 'list' || $methodAction === 'trash') {
        $module = api_module((string)($body['module'] ?? ''));
        $query = trim((string)($body['q'] ?? ''));
        $sql = 'SELECT * FROM records WHERE owner_id = ? AND module = ? AND deleted_at IS ' . ($methodAction === 'trash' ? 'NOT ' : '') . 'NULL ORDER BY updated_at DESC LIMIT 5001';
        $s = $pdo->prepare($sql); $s->execute([(int)$user['id'], $module]);
        $items = [];
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $payload = app_decrypt($row['payload']);
            if ($query !== '' && stripos(json_encode($payload, JSON_UNESCAPED_UNICODE), $query) === false) continue;
            $filter = trim((string)($body['filter'] ?? ''));
            $filterKey = ['contacts'=>'relationship','accounts'=>'account_type','journal'=>'category','intimacy'=>'event_type'][$module];
            if ($filter !== '' && strcasecmp((string)($payload[$filterKey] ?? ''), $filter) !== 0) continue;
            $dateValue = (string)($module === 'journal' ? ($payload['entry_date'] ?? '') : ($module === 'contacts' ? ($payload['date_of_birth'] ?? '') : ($module === 'accounts' ? ($row['created_at'] ?? '') : ($payload['event_date'] ?? ''))));
            $from = (string)($body['from'] ?? ''); $to = (string)($body['to'] ?? '');
            if ($from !== '' && substr($dateValue, 0, 10) < $from) continue;
            if ($to !== '' && substr($dateValue, 0, 10) > $to) continue;
            $rating = (string)($body['rating'] ?? '');
            if ($rating !== '') {
                $ratingValues = [];
                foreach ($payload as $key => $value) if (preg_match('/^(?:rating(?:_[a-z_]+)?|question_[4-8])$/', (string)$key) && $value !== '') $ratingValues[] = (string)$value;
                if (!in_array($rating, $ratingValues, true)) continue;
            }
            $tagFilter = trim((string)($body['tag'] ?? ''));
            if ($tagFilter !== '') {
                $tags = $payload['tags'] ?? [];
                $haystack = is_array($tags) ? implode(',', array_map('strval', $tags)) : (string)$tags;
                if (stripos($haystack, $tagFilter) === false) continue;
            }
            $items[] = ['id' => $row['id'], 'contact_id' => $row['contact_id'], 'data' => api_safe_data($module, $payload), 'created_at' => $row['created_at'], 'updated_at' => $row['updated_at'], 'deleted_at' => $row['deleted_at']];
        }
        $total = count($items); $offset = max(0, (int)($body['offset'] ?? 0)); $limit = min(100, max(1, (int)($body['limit'] ?? 30)));
        app_json(['items' => array_slice($items, $offset, $limit), 'total' => $total, 'truncated' => $total >= 5001]);
    }
    if ($methodAction === 'save') api_upload($pdo, $user, $body);
    if ($methodAction === 'delete' || $methodAction === 'restore') {
        $module = api_module((string)($body['module'] ?? '')); $id = (string)($body['id'] ?? '');
        if (!api_record($pdo, (int)$user['id'], $module, $id, true)) app_json(['error' => 'That record is unavailable.'], 404);
        if ($methodAction === 'delete') $sql = 'UPDATE records SET deleted_at = ?, updated_at = ? WHERE id = ? AND owner_id = ? AND module = ?';
        else $sql = 'UPDATE records SET deleted_at = NULL, updated_at = ? WHERE id = ? AND owner_id = ? AND module = ?';
        $s = $pdo->prepare($sql);
        if ($methodAction === 'delete') $s->execute([app_now(), app_now(), $id, (int)$user['id'], $module]);
        else $s->execute([app_now(), $id, (int)$user['id'], $module]);
        app_json(['ok' => true]);
    }
    if ($methodAction === 'reveal') {
        $module = 'accounts'; $id = (string)($body['id'] ?? ''); $field = (string)($body['field'] ?? '');
        $allowed = ['password','secret','routing_number','account_number','security_answers','recovery_codes'];
        if (!in_array($field, $allowed, true)) app_json(['error' => 'That field cannot be revealed.'], 422);
        $s = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?'); $s->execute([(int)$user['id']]);
        if (!password_verify((string)($body['password'] ?? ''), (string)$s->fetchColumn())) app_json(['error' => 'Password was not recognized.'], 401);
        $row = api_record($pdo, (int)$user['id'], $module, $id);
        if (!$row) app_json(['error' => 'That account is unavailable.'], 404);
        $payload = app_decrypt($row['payload']);
        app_json(['value' => $payload[$field] ?? '']);
    }
    if ($methodAction === 'backup') {
        $s = $pdo->prepare('SELECT id, module, contact_id, payload, created_at, updated_at, deleted_at FROM records WHERE owner_id = ? ORDER BY created_at');
        $s->execute([(int)$user['id']]);
        app_json(['format' => 'PersonVault backup v1', 'created_at' => app_now(), 'records' => $s->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($methodAction === 'restore_backup') {
        $rows = $body['backup']['records'] ?? null;
        if (!is_array($rows) || count($rows) > 10000) app_json(['error' => 'Backup file is invalid or too large.'], 422);
        $pdo->beginTransaction();
        try {
            $s = $pdo->prepare('INSERT INTO records(id, owner_id, module, contact_id, payload, created_at, updated_at, deleted_at) VALUES(?,?,?,?,?,?,?,?)');
            $inserted = 0;
            foreach ($rows as $r) {
                if (!is_array($r) || !in_array($r['module'] ?? '', APP_MODULES, true) || !is_string($r['id'] ?? null) || !is_string($r['payload'] ?? null)) continue;
                app_decrypt($r['payload']);
                $contactId = $r['contact_id'] ?? null;
                if ($contactId !== null && api_record($pdo, (int)$user['id'], 'contacts', (string)$contactId, true) === null) $contactId = null;
                try { $s->execute([$r['id'], (int)$user['id'], $r['module'], $contactId, $r['payload'], $r['created_at'] ?? app_now(), $r['updated_at'] ?? app_now(), $r['deleted_at'] ?? null]); $inserted++; } catch (PDOException) { /* Existing ID is already present. */ }
            }
            $pdo->commit(); app_json(['ok' => true, 'restored' => $inserted]);
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    if ($methodAction === 'users' || $methodAction === 'create_user') {
        if ($user['role'] !== 'admin') app_json(['error' => 'Administrator access is required.'], 403);
        if ($methodAction === 'users') {
            $list = $pdo->query('SELECT id, email, role, disabled, created_at FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
            app_json(['users' => $list]);
        }
        $email = strtolower(trim((string)($body['email'] ?? ''))); $password = (string)($body['password'] ?? '');
        $role = (string)($body['role'] ?? 'user');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || !in_array($role, ['admin','user'], true)) app_json(['error' => 'Enter a valid email, a password of at least 12 characters, and a valid role.'], 422);
        try { $s = $pdo->prepare('INSERT INTO users(email,password_hash,role,created_at) VALUES(?,?,?,?)'); $s->execute([$email,password_hash($password,PASSWORD_DEFAULT),$role,app_now()]); }
        catch (PDOException) { app_json(['error' => 'That email address is already registered.'], 409); }
        app_json(['ok' => true]);
    }
    if ($methodAction === 'set_user_status') {
        if ($user['role'] !== 'admin') app_json(['error' => 'Administrator access is required.'], 403);
        $target = (int)($body['user_id'] ?? 0); $disabled = !empty($body['disabled']) ? 1 : 0;
        if ($target < 1 || $target === (int)$user['id']) app_json(['error' => 'You cannot disable your current administrator account.'], 422);
        $exists = $pdo->prepare('SELECT role, disabled FROM users WHERE id = ?'); $exists->execute([$target]); $targetUser = $exists->fetch(PDO::FETCH_ASSOC);
        if (!$targetUser) app_json(['error' => 'That user is unavailable.'], 404);
        if ($disabled && $targetUser['role'] === 'admin' && (int)$targetUser['disabled'] === 0) {
            $adminCount = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ? AND disabled = 0'); $adminCount->execute(['admin']); $admins = (int)$adminCount->fetchColumn();
            if ($admins <= 1) app_json(['error' => 'At least one active administrator must remain.'], 422);
        }
        $update = $pdo->prepare('UPDATE users SET disabled = ? WHERE id = ?'); $update->execute([$disabled, $target]);
        app_json(['ok' => true]);
    }
    app_json(['error' => 'Unknown action.'], 404);
} catch (Throwable $e) {
    error_log('PersonVault request failed: ' . $e::class . ' ' . $e->getMessage());
    app_json(['error' => 'The request could not be completed. Check the server log for details.'], 500);
}
