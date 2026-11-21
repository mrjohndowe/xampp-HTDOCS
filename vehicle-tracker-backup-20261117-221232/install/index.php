<?php
// Vehicle Tracker - first-run onboarding wizard
// Browser-based setup: choose/test database, create administrator, set units,
// add first vehicle + odometer reading, initialize schema, write config + lock.

declare(strict_types = 1);

session_start();

$APP_DIR   = __DIR__ . '/..';
$LOCK_FILE = $APP_DIR . '/config.lock';
$CONFIG_FILE = $APP_DIR . '/config.php';
$SETUP_LOCK = $APP_DIR . '/data/.setup.lock';
$SCHEMA_FILE = $APP_DIR . '/schema.sql';
$DATA_DIR = $APP_DIR . '/data';
$README_URL = 'https://example.com/vehicle-tracker/README.md'; // replaced in README

// Helpers ---------------------------------------------------------------

function step(): int
{
    return (int)($_SESSION['vt_install_step'] ?? 1);
}

function set_step(int $n): void
{
    $_SESSION['vt_install_step'] = $n;
}

function flash(string $msg, string $type = 'error'): void
{
    $_SESSION['vt_install_flash'] = ['msg' => $msg, 'type' => $type];
}

function get_flash(): ?array
{
    if (isset($_SESSION['vt_install_flash'])) {
        $f = $_SESSION['vt_install_flash'];
        unset($_SESSION['vt_install_flash']);
        return $f;
    }
    return null;
}

function old(string $field)
{
    return htmlspecialchars($_SESSION['vt_install_old'][$field] ?? '', ENT_QUOTES);
}

function redirect_step(int $n): void
{
    set_step($n);
    header('Location: index.php');
    exit;
}

function test_connection(array $db): array
{
    try {
        if (($db['driver'] ?? '') === 'mysql') {
            $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4',
                $db['host'] ?? '127.0.0.1', (int)($db['port'] ?? 3306));
            $pdo = new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            if (!empty($db['dbname'])) {
                $pdo->exec('USE `' . str_replace('`', '``', $db['dbname']) . '`');
            }
        } else {
            $path = $db['database'] ?? __DIR__ . '/../data/vehicles.sqlite';
            if (!is_dir(dirname($path))) {
                @mkdir(dirname($path), 0777, true);
            }
            // Do not create the file here; a connection test must not leave
            // behind a zero-byte database that looks like an installation.
            if (!file_exists($path)) {
                if (!is_writable(dirname($path))) {
                    throw new RuntimeException('Database directory is not writable');
                }
                $pdo = new PDO('sqlite::memory:', null, null,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            } else {
                $pdo = new PDO('sqlite:' . $path, null, null,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo->exec('PRAGMA foreign_keys = ON');
            }
        }
        $pdo->query('SELECT 1');
        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function setup_database_connection(array $db): PDO
{
    if (($db['driver'] ?? 'sqlite') === 'mysql') {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $db['host'] ?? '127.0.0.1',
            (int)($db['port'] ?? 3306),
            $db['dbname'] ?? 'vehicle_tracker'
        );
        return new PDO(
            $dsn,
            $db['username'] ?? '',
            $db['password'] ?? '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))",
            ]
        );
    }

    $database = $db['database'] ?? __DIR__ . '/../data/vehicles.sqlite';
    $directory = dirname($database);
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create database directory.');
    }
    $pdo = new PDO('sqlite:' . $database, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

function installation_exists_without_lock(): bool
{
    global $CONFIG_FILE, $DATA_DIR;
    if (file_exists($CONFIG_FILE)) {
        return true;
    }
    $files = glob($DATA_DIR . '/*.{sqlite,sqlite3,db}', GLOB_BRACE);
    foreach ($files as $file) {
        // Only a real, non-empty, queryable database counts as an installation.
        if (@filesize($file) > 0) {
            try {
                $t = new PDO('sqlite:' . $file, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $t->query('SELECT 1 FROM sqlite_master LIMIT 1');
                return true;
            } catch (Throwable $e) {
                continue;
            }
        }
    }
    return false;
}

// Lock / guard checks --------------------------------------------------

// 1. Already installed? Block setup unconditionally (persists across restarts).
if (file_exists($LOCK_FILE)) {
    $lock = json_decode(file_get_contents($LOCK_FILE), true);
    $installedAt = $lock['installed_at'] ?? 'unknown';
    $driver = $lock['driver'] ?? '?';
    header('HTTP/1.1 403 Forbidden');
    ?>
    <!DOCTYPE html>
    <html lang="en"><head><meta charset="utf-8"><title>Setup blocked - Vehicle Tracker</title>
    <style>
      body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;margin:0}
      .card{background:#1e293b;border:1px solid #334155;padding:40px;border-radius:12px;max-width:480px;text-align:center}
      h1{margin:0 0 12px;font-size:22px}
      p{margin:0 0 20px;color:#94a3b8}
      .code{background:#0f172a;padding:12px;border-radius:6px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#a5f3fc;word-break:break-all}
      a{color:#38bdf8;text-decoration:none}
      a:hover{text-decoration:underline}
    </style></head><body>
    <div class="card">
      <h1>&#x1F6AB; Installation already complete</h1>
      <p>Vehicle Tracker was installed on <strong><?= htmlspecialchars($installedAt) ?></strong>
         using the <strong><?= htmlspecialchars($driver) ?></strong> database.</p>
      <p>A <code>config.lock</code> file exists, so the onboarding wizard is blocked.
         This persists across Apache restarts and cannot be bypassed from the browser.</p>
      <div class="code">config.lock</div>
      <p style="margin-top:24px"><a href="../">Go to the application</a></p>
    </div>
    </body></html>
    <?php
    exit;
}

// 2. Lock missing but data/config exists: existing installation without lock.
//    Block reinstallation to protect data; document recovery.
if (installation_exists_without_lock()) {
    header('HTTP/1.1 403 Forbidden');
    ?>
    <!DOCTYPE html>
    <html lang="en"><head><meta charset="utf-8"><title>Existing installation detected - Vehicle Tracker</title>
    <style>
      body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:#7f1d1d;color:#fee2e2;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;margin:0}
      .card{background:#991b1b;border:1px solid #7f1d1d;padding:40px;border-radius:12px;max-width:560px;text-align:left}
      h1{margin:0 0 12px;font-size:22px}
      p{margin:0 0 14px;color:#fecaca;line-height:1.6}
      ul{margin:8px 0 16px;padding-left:20px;color:#fecaca}
      code{background:#450a0a;padding:2px 6px;border-radius:4px;color:#fca5a5;font-family:ui-monospace,monospace}
      a{color:#fca5a5;text-decoration:underline}
    </style></head><body>
    <div class="card">
      <h1>&#x1F6AB; Existing installation detected without lock</h1>
      <p><code>config.lock</code> is missing, but an existing database or configuration was found.
         Reinstallation is blocked to prevent overwriting or losing your data.</p>
      <p><strong>Data-preserving recovery:</strong></p>
      <ul>
        <li>Restore <code>config.lock</code> from a backup if you have one (it contains no credentials).</li>
        <li>Otherwise, recreate <code>config.lock</code> manually next to <code>config.php</code>. Example:</li>
      </ul>
      <code>{"installed":true,"driver":"sqlite","database":"data/vehicles.sqlite","currency":"USD","distance_unit":"mi","installed_at":"2026-01-01","version":"1.0.0"}</code>
      <p>Do <em>not</em> delete the database file. Once the lock is restored, the wizard stays blocked and the app works normally.</p>
      <p>See the README ("Lock recovery") for full instructions.</p>
      <p style="margin-top:20px"><a href="../">Go to the application</a></p>
    </div>
    </body></html>
    <?php
    exit;
}

// 3. Fresh setup allowed. Serialize concurrent setup attempts with a flock.
if (!is_dir($DATA_DIR)) {
    @mkdir($DATA_DIR, 0777, true);
}
$setupLockFp = @fopen($SETUP_LOCK, 'c+');
if ($setupLockFp === false) {
    flash('Could not create setup lock file. Check data/ directory permissions.', 'error');
}
$setupLocked = $setupLockFp !== false && flock($setupLockFp, LOCK_EX | LOCK_NB);
if (!$setupLocked) {
    flash('Another setup is already in progress. Please wait, then retry.', 'error');
}

// Process form submissions ---------------------------------------------

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $setupLocked) {
    // Re-check lock right before writing (defense in depth).
    if (file_exists($LOCK_FILE)) {
        header('Location: index.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ---- Step 1: database choice + test ----
    if ($action === 'choose_db') {
        $db = [
            'driver'   => $_POST['driver'] ?? 'sqlite',
            'database' => trim($_POST['database'] ?? ($DATA_DIR . '/vehicles.sqlite')),
            'host'     => trim($_POST['host'] ?? '127.0.0.1'),
            'port'     => (int)($_POST['port'] ?? 3306),
            'dbname'   => trim($_POST['dbname'] ?? 'vehicle_tracker'),
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
        ];
        if (!in_array($db['driver'], ['sqlite', 'mysql'], true)) {
            flash('Choose SQLite or MySQL.');
            redirect_step(1);
        }
        if (!preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $db['database'])) {
            $db['database'] = $APP_DIR . '/' . $db['database'];
        }
        $result = test_connection($db);
        $_SESSION['vt_install_db'] = $db;
        $_SESSION['vt_install_old'] = $_POST;
        if ($result['ok']) {
            flash('Database connection tested successfully.', 'success');
            redirect_step(2);
        } else {
            flash('Connection test failed: ' . htmlspecialchars($result['error']), 'error');
            redirect_step(1);
        }
    }

    // ---- Step 2: administrator ----
    if ($action === 'create_admin') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';
        $_SESSION['vt_install_old'] = $_POST;
        if ($username === '') $errors[] = 'Username is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        if (empty($errors)) {
            $_SESSION['vt_install_admin'] = [
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ];
            flash('Administrator credentials validated.', 'success');
            redirect_step(3);
        }
        flash(implode('<br>', $errors), 'error');
        redirect_step(2);
    }

    // ---- Step 3: preferences ----
    if ($action === 'set_prefs') {
        $_SESSION['vt_install_prefs'] = [
            'site_name'       => trim($_POST['site_name'] ?? 'Vehicle Tracker'),
            'currency'        => $_POST['currency'] ?? 'USD',
            'distance_unit'   => $_POST['distance_unit'] ?? 'mi',
        ];
        $_SESSION['vt_install_old'] = $_POST;
        redirect_step(4);
    }

    // ---- Step 4: first vehicle + odometer + FINALIZE ----
    if ($action === 'finish') {
        $db   = $_SESSION['vt_install_db'] ?? null;
        $admin = $_SESSION['vt_install_admin'] ?? null;
        $prefs = $_SESSION['vt_install_prefs'] ?? null;
        if (!$db || !$admin || !$prefs) {
            flash('Setup state expired. Please start over.', 'error');
            redirect_step(1);
        }

        $vehicle = [
            'vin'          => trim($_POST['vin'] ?? ''),
            'make'         => trim($_POST['make'] ?? ''),
            'model'        => trim($_POST['model'] ?? ''),
            'year'         => (int)($_POST['year'] ?? date('Y')),
            'license_plate'=> trim($_POST['license_plate'] ?? ''),
            'color'        => trim($_POST['color'] ?? ''),
            'notes'        => trim($_POST['notes'] ?? ''),
        ];
        $initialOdo = (float)($_POST['initial_odometer'] ?? 0);
        $engineMiles= (float)($_POST['engine_miles_at_install'] ?? 0);
        $_SESSION['vt_install_old'] = $_POST;

        if ($vehicle['make'] === '' || $vehicle['model'] === '') {
            $errors[] = 'Make and model are required.';
        }
        if ($initialOdo < 0) $errors[] = 'Initial odometer cannot be negative.';
        if ($engineMiles < 0) $errors[] = 'Engine miles cannot be negative.';

        if (empty($errors)) {
            $pdo = null;
            try {
                $pdo = setup_database_connection($db);
                // Schema (idempotent)
                $schema = file_get_contents($SCHEMA_FILE);
                if ($schema === false) {
                    throw new RuntimeException('Could not read schema.sql');
                }
                // SQLite: execute whole script; MySQL: split not needed for our statements.
                if ($db['driver'] === 'mysql') {
                    $schema = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'INTEGER PRIMARY KEY AUTO_INCREMENT', $schema);
                    $schema = str_replace('username TEXT NOT NULL UNIQUE', 'username VARCHAR(191) NOT NULL UNIQUE', $schema);
                    $schema = str_replace('key TEXT PRIMARY KEY', '`key` VARCHAR(191) PRIMARY KEY', $schema);
                    $schema = str_replace('CREATE INDEX IF NOT EXISTS', 'CREATE INDEX', $schema);
                }
                $pdo->exec($schema);
                // MySQL DDL implicitly commits; begin the data transaction after schema.
                $pdo->beginTransaction();

                // Settings
                $now = date('Y-m-d H:i:s');
                $pdo->prepare("INSERT INTO settings (id, site_name, currency, distance_unit, installed_at, version)
                               VALUES (1, ?, ?, ?, ?, '1.0.0')")
                    ->execute([$prefs['site_name'], $prefs['currency'], $prefs['distance_unit'], $now]);

                // Administrator
                $pdo->prepare("INSERT INTO users (username, password_hash, role, created_at) VALUES (?, ?, 'admin', ?)")
                    ->execute([$admin['username'], $admin['password_hash'], $now]);
                $userId = (int)$pdo->lastInsertId();

                // API token for the administrator (desktop app can use this)
                $token = bin2hex(random_bytes(24));
                $pdo->prepare("INSERT INTO api_tokens (user_id, token_hash, name, created_at) VALUES (?, ?, 'Onboarding token', ?)")
                    ->execute([$userId, hash('sha256', $token), $now]);
                $_SESSION['vt_install_token'] = $token; // shown once on finish page

                // First vehicle
                $pdo->prepare("INSERT INTO vehicles (vin, make, model, year, license_plate, color, notes, created_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$vehicle['vin'], $vehicle['make'], $vehicle['model'], $vehicle['year'],
                               $vehicle['license_plate'], $vehicle['color'], $vehicle['notes'], $now]);
                $vehicleId = (int)$pdo->lastInsertId();

                // Baseline engine record so engine mileage is tracked from day one.
                $pdo->prepare("INSERT INTO engines (vehicle_id, serial_number, installed_date, engine_mileage_at_install,
                               vehicle_odometer_at_install, status, notes, created_at)
                               VALUES (?, 'Original', ?, ?, ?, 'active', 'Baseline from onboarding', ?)")
                    ->execute([$vehicleId, $now, $engineMiles, $initialOdo, $now]);

                $pdo->commit();

                // Write config.php (credentials live here, NOT in the lock).
                $config = ['driver' => $db['driver'], 'database' => $db['database']];
                if ($db['driver'] === 'mysql') {
                    $config['host'] = $db['host'];
                    $config['port'] = $db['port'];
                    $config['dbname'] = $db['dbname'];
                    $config['username'] = $db['username'];
                    $config['password'] = $db['password'];
                }
                $configPhp = "<?php\n// Vehicle Tracker configuration - generated by onboarding wizard.\n// DO NOT EDIT MANUALLY; re-run the installer to change.\nreturn " . var_export($config, true) . ";\n";
                if (@file_put_contents($CONFIG_FILE, $configPhp, LOCK_EX) === false) {
                    throw new RuntimeException('Could not write config.php');
                }

                // Write config.lock (NO credentials). Created ONLY after full success.
                $lock = [
                    'installed' => true,
                    'driver' => $db['driver'],
                    'database' => $db['database'],
                    'currency' => $prefs['currency'],
                    'distance_unit' => $prefs['distance_unit'],
                    'installed_at' => $now,
                    'version' => '1.0.0',
                ];
                if (@file_put_contents($LOCK_FILE, json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
                    throw new RuntimeException('Could not write config.lock');
                }

                // Clear setup session
                $_SESSION = [];
                session_regenerate_id(true);

                // Show success page with the token (displayed once)
                $showToken = $token;
                include __DIR__ . '/finish.php';
                exit;
            } catch (Throwable $e) {
                if ($pdo instanceof PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                flash('Setup failed: ' . htmlspecialchars($e->getMessage()) . '. Check configuration and database files before retrying.', 'error');
                redirect_step(1);
            }
        }
        flash(implode('<br>', $errors), 'error');
        redirect_step(1);
    }
}

// Render wizard --------------------------------------------------------

$flash = get_flash();
$db    = $_SESSION['vt_install_db'] ?? null;
$step  = step();
$title = ['Database', 'Administrator', 'Preferences', 'First vehicle'][$step - 1] . ' - Setup';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?></title>
<style>
  :root{--bg:#f8fafc;--card:#ffffff;--text:#0f172a;--muted:#64748b;--border:#e2e8f0;--primary:#2563eb;--primary-hover:#1d4ed8;--success:#16a34a;--error:#dc2626;--radius:10px}
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text);margin:0;line-height:1.5}
  .wrap{max-width:560px;margin:40px auto;padding:0 16px}
  header{display:flex;align-items:center;gap:12px;margin-bottom:8px}
  header h1{font-size:18px;margin:0}
  header .chip{font-size:12px;background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:999px}
  .card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:26px;margin-bottom:24px;box-shadow:0 1px 2px rgb(0 0 0 / 0.04)}
  .stepbar{display:flex;gap:8px;margin-bottom:24px}
  .step{flex:1;height:6px;border-radius:3px;background:var(--border)}
  .step.done{background:var(--primary)}
  .step.current{background:var(--primary);width:24px;border-radius:999px;flex:0 0 auto}
  .field{margin-bottom:16px}
  .field label{display:block;font-size:13px;color:var(--muted);margin-bottom:6px;font-weight:600}
  .field input,.field select{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;font-size:14px;background:#fff}
  .field input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgb(37 99 235 / 0.15)}
  .row{display:flex;gap:12px}
  .row .field{flex:1;margin-bottom:0}
  button{background:var(--primary);color:#fff;border:none;padding:10px 18px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}
  button:hover{background:var(--primary-hover)}
  button:disabled{opacity:.6;cursor:not-allowed}
  .actions{display:flex;gap:10px;margin-top:8px}
  .btn-ghost{background:#fff;border:1px solid var(--border);color:var(--text);padding:10px 18px;border-radius:8px;font-size:14px;cursor:pointer}
  .btn-ghost:hover{background:#f1f5f9}
  .flash{padding:12px 14px;border-radius:8px;margin-bottom:16px;font-size:14px}
  .flash.success{background:#ecfdf5;color:#14532d;border:1px solid #bbf7d0}
  .flash.error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
  .flash b{display:block;margin-bottom:6px}
  .test{display:flex;gap:8px;margin-top:8px}
  .test button{background:#f1f5f9;color:#334155;padding:10px 14px}
  .test button:hover{background:#e2e8f0}
  .hint{font-size:12.5px;color:var(--muted);margin-top:6px}
  table{width:100%;border-collapse:collapse;margin-top:12px}
  th,td{border-bottom:1px solid var(--border);padding:8px 6px;text-align:left;font-size:14px}
  .token-box{background:#0f172a;color:#a5f3fc;padding:14px;border-radius:8px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;word-break:break-all;margin-top:12px}
  .footer{color:var(--muted);font-size:13px;border-top:1px solid var(--border);padding-top:16px}
  .footer a{color:var(--primary)}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <h1>Vehicle Tracker Setup</h1>
    <span class="chip">Step <?= $step ?> of 4</span>
  </header>
  <div class="stepbar">
    <div class="step <?= $step > 1 ? 'done' : '' ?>"></div>
    <div class="step <?= $step > 2 ? 'done' : '' ?>"></div>
    <div class="step <?= $step > 3 ? 'done' : '' ?>"></div>
    <div class="step <?= $step === 4 ? 'current' : ($step > 4 ? 'done' : '') ?>"></div>
  </div>

  <?php if ($flash): ?>
    <div class="flash <?= $flash['type'] ?>"><b><?= htmlspecialchars(ucfirst($flash['type'])) ?>:</b> <?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <div class="card">
  <?php if ($step === 1): ?>
    <h2 style="margin-top:0">Choose and test database</h2>
    <form method="post" action="">
      <input type="hidden" name="action" value="choose_db">
      <div class="field">
        <label>Database driver</label>
        <select name="driver" id="driver" onchange="toggleDb()">
          <option value="sqlite" <?= old('driver') === 'mysql' ? '' : 'selected' ?>>SQLite (file-based, recommended)</option>
          <option value="mysql" <?= old('driver') === 'mysql' ? 'selected' : '' ?>>MySQL / MariaDB</option>
        </select>
      </div>
      <div id="db-mysql" style="display:none">
        <div class="row">
          <div class="field"><label>Host</label><input name="host" value="<?= old('host') ?: '127.0.0.1' ?>"></div>
          <div class="field"><label>Port</label><input name="port" type="number" value="<?= old('port') ?: 3306 ?>"></div>
        </div>
        <div class="row">
          <div class="field"><label>Database name</label><input name="dbname" value="<?= old('dbname') ?: 'vehicle_tracker' ?>"></div>
          <div class="field"><label>Username</label><input name="username" value="<?= old('username') ?>"></div>
        </div>
        <div class="field"><label>Password</label><input name="password" type="password"></div>
      </div>
      <div id="db-sqlite">
        <div class="field"><label>Database file path</label>
          <input name="database" value="<?= old('database') ?: ($DATA_DIR . '/vehicles.sqlite') ?>">
          <div class="hint">Relative to the app folder or absolute. The data/ folder is protected from web access.</div>
        </div>
      </div>
      <div class="test">
        <button type="submit">Test connection</button>
      </div>
    </form>
  <?php elseif ($step === 2): ?>
    <h2 style="margin-top:0">Create administrator</h2>
    <form method="post" action="">
      <input type="hidden" name="action" value="create_admin">
      <div class="field"><label>Username</label><input name="username" value="<?= old('username') ?>" required></div>
      <div class="field"><label>Password</label><input name="password" type="password" minlength="8" required></div>
      <div class="field"><label>Confirm password</label><input name="confirm" type="password" minlength="8" required></div>
      <div class="actions">
        <button type="submit">Continue</button>
      </div>
    </form>
  <?php elseif ($step === 3): ?>
    <h2 style="margin-top:0">Preferences</h2>
    <form method="post" action="">
      <input type="hidden" name="action" value="set_prefs">
      <div class="field"><label>Site name</label><input name="site_name" value="<?= old('site_name') ?: 'Vehicle Tracker' ?>"></div>
      <div class="row">
        <div class="field"><label>Currency</label>
          <select name="currency">
            <option value="USD" <?= old('currency') === 'EUR' || old('currency') === 'GBP' || old('currency') === 'CAD' || old('currency') === 'AUD' ? '' : 'selected' ?>>USD ($)</option>
            <option value="EUR" <?= old('currency') === 'EUR' ? 'selected' : '' ?>>EUR (â‚¬)</option>
            <option value="GBP" <?= old('currency') === 'GBP' ? 'selected' : '' ?>>GBP (&pound;)</option>
            <option value="CAD" <?= old('currency') === 'CAD' ? 'selected' : '' ?>>CAD</option>
            <option value="AUD" <?= old('currency') === 'AUD' ? 'selected' : '' ?>>AUD</option>
          </select>
        </div>
        <div class="field"><label>Distance unit</label>
          <select name="distance_unit">
            <option value="mi" <?= old('distance_unit') === 'km' ? '' : 'selected' ?>>Miles (mi)</option>
            <option value="km" <?= old('distance_unit') === 'km' ? 'selected' : '' ?>>Kilometers (km)</option>
          </select>
        </div>
      </div>
      <div class="actions"><button type="submit">Continue</button></div>
    </form>
  <?php else: ?>
    <h2 style="margin-top:0">Add first vehicle and odometer reading</h2>
    <form method="post" action="">
      <input type="hidden" name="action" value="finish">
      <div class="row">
        <div class="field"><label>Make</label><input name="make" value="<?= old('make') ?>" required></div>
        <div class="field"><label>Model</label><input name="model" value="<?= old('model') ?>" required></div>
      </div>
      <div class="row">
        <div class="field"><label>VIN</label><input name="vin" value="<?= old('vin') ?>"></div>
        <div class="field"><label>Year</label><input name="year" type="number" min="1900" max="2100" value="<?= old('year') ?: date('Y') ?>"></div>
      </div>
      <div class="row">
        <div class="field"><label>License plate</label><input name="license_plate" value="<?= old('license_plate') ?>"></div>
        <div class="field"><label>Color</label><input name="color" value="<?= old('color') ?>"></div>
      </div>
      <div class="field"><label>Notes</label><input name="notes" value="<?= old('notes') ?>"></div>
      <div class="row">
        <div class="field"><label>Initial odometer reading</label>
          <input name="initial_odometer" type="number" step="0.1" min="0" value="<?= old('initial_odometer') ?: '0' ?>" required>
          <div class="hint">Starting vehicle odometer (also sets the engine installation baseline).</div>
        </div>
        <div class="field"><label>Engine miles at installation</label>
          <input name="engine_miles_at_install" type="number" step="0.1" min="0" value="<?= old('engine_miles_at_install') ?: '0' ?>" required>
          <div class="hint">Engine's own recorded mileage when installed.</div>
        </div>
      </div>
      <div class="actions" style="margin-top:20px"><button type="submit">Finish setup</button></div>
    </form>
  <?php endif; ?>
  </div>

  <div class="footer">
    Vehicle Tracker &#8226; Onboarding wizard &middot;
    <a href="../">Skip to application</a>
  </div>
</div>

<script>
function toggleDb(){
  const d=document.getElementById('driver').value;
  document.getElementById('db-mysql').style.display = d==='mysql' ? '' : 'none';
  document.getElementById('db-sqlite').style.display = d==='sqlite' ? '' : 'none';
}
if (document.getElementById('driver')) toggleDb();
</script>
</body>
</html>
