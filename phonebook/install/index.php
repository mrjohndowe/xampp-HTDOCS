<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config.php';

if (is_installed()) {
    http_response_code(403);
    echo '<h1>🔒 Installer locked</h1><p>Remove install.lock to reinstall.</p>';
    exit;
}

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $appName = trim((string)($_POST['appName'] ?? ''));
        $ownerName = trim((string)($_POST['ownerName'] ?? ''));
        $theme = ($_POST['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
        $driver = ($_POST['dbDriver'] ?? 'sqlite') === 'mysql' ? 'mysql' : 'sqlite';
        $configuration = [
            'dbDriver' => $driver,
            'sqliteFile' => trim((string)($_POST['sqliteFile'] ?? 'phonebook.sqlite')),
            'mysqlHost' => trim((string)($_POST['mysqlHost'] ?? '127.0.0.1')),
            'mysqlPort' => trim((string)($_POST['mysqlPort'] ?? '3306')),
            'mysqlDb' => trim((string)($_POST['mysqlDb'] ?? 'phonebook')),
            'mysqlUser' => trim((string)($_POST['mysqlUser'] ?? 'root')),
            'mysqlPass' => (string)($_POST['mysqlPass'] ?? ''),
        ];

        if ($appName === '' || $ownerName === '') {
            throw new InvalidArgumentException('App name and owner name are required.');
        }

        if ($driver === 'sqlite') {
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.sqlite$/i', $configuration['sqliteFile'])) {
                throw new InvalidArgumentException('SQLite filename must be a simple .sqlite filename.');
            }
            $configuration['sqlitePath'] = dirname(__DIR__) . '/database/' . $configuration['sqliteFile'];
            $pdo = pdo($configuration);
            $schema = file_get_contents(dirname(__DIR__) . '/database/sqlite.sql');
            if ($schema === false) {
                throw new RuntimeException('Could not load the SQLite schema.');
            }
            $pdo->exec($schema);
        } else {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $configuration['mysqlDb'])) {
                throw new InvalidArgumentException('MySQL database name may contain only letters, numbers, and underscores.');
            }
            if ($configuration['mysqlHost'] === '' || $configuration['mysqlUser'] === '' ||
                !ctype_digit($configuration['mysqlPort']) || (int)$configuration['mysqlPort'] < 1 ||
                (int)$configuration['mysqlPort'] > 65535) {
                throw new InvalidArgumentException('Enter a valid MySQL host, port, and username.');
            }
            $server = pdo(array_merge($configuration, ['mysqlDb' => '']));
            $server->exec('CREATE DATABASE IF NOT EXISTS `' . $configuration['mysqlDb'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo = pdo($configuration);
            $schema = file_get_contents(dirname(__DIR__) . '/database/mysql.sql');
            if ($schema === false) {
                throw new RuntimeException('Could not load the MySQL schema.');
            }
            $lines = preg_split('/\R/', $schema);
            array_splice($lines, 0, 2);
            $statements = preg_split('/;\s*(?:\R|$)/', implode("\n", $lines));
            foreach ($statements as $statement) {
                if (trim($statement) !== '') {
                    $pdo->exec($statement);
                }
            }
        }

        $localConfiguration = $configuration;
        unset($localConfiguration['sqlitePath']);
        $localContents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($localConfiguration, true) . ";\n";
        if (file_put_contents(dirname(__DIR__) . '/config.local.php', $localContents, LOCK_EX) === false) {
            throw new RuntimeException('Could not save database configuration. Check write permissions for the package folder.');
        }

        $upsert = $pdo->prepare('SELECT `value` FROM settings WHERE `key` = ?');
        foreach ([
            'installed' => 'true',
            'appName' => $appName,
            'ownerName' => $ownerName,
            'dbDriver' => $driver,
            'phpVersion' => PHP_VERSION,
            'theme' => $theme,
            'installedAt' => date(DATE_ATOM),
        ] as $key => $value) {
            $upsert->execute([$key]);
            if ($upsert->fetchColumn() !== false) {
                $update = $pdo->prepare('UPDATE settings SET `value` = ?, updated_at = CURRENT_TIMESTAMP WHERE `key` = ?');
                $update->execute([$value, $key]);
            } else {
                $insert = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?)');
                $insert->execute([$key, $value]);
            }
        }

        if (file_put_contents(INSTALL_LOCK, json_encode(['installedAt' => date(DATE_ATOM), 'app' => $appName], JSON_PRETTY_PRINT), LOCK_EX) === false) {
            throw new RuntimeException('Installation completed, but the install lock could not be written. Check folder permissions.');
        }
        $safeName = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $safeDriver = htmlspecialchars(strtoupper($driver), ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Phone Book installed</title><link rel="stylesheet" href="../assets/css/globals.css"></head><body data-theme="' . $theme . '"><main class="container install-result"><h1>✅ Your Phone Book is ready</h1><p>' . $safeName . ' has been installed with the ' . $safeDriver . ' database.</p><p><strong>For security, delete or rename the install/ folder after setup.</strong></p><a class="button button-primary" href="../">Open Phone Book</a></main></body></html>';
        exit;
    } catch (Throwable $exception) {
        $errorMessage = $exception instanceof PDOException
            ? 'Database connection or setup failed. Verify the database details, ensure PDO SQLite/MySQL is enabled, and check PHP error logs for details.'
            : $exception->getMessage();
    }
}

$postedDriver = ($_POST['dbDriver'] ?? 'sqlite') === 'mysql' ? 'mysql' : 'sqlite';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Set up Phone Book</title>
  <link rel="stylesheet" href="../assets/css/globals.css">
</head>
<body>
  <main class="container install-container">
    <div class="install-heading">
      <span class="brand-icon">📖</span>
      <h1>Set up your Phone Book</h1>
      <p>Personalize your workspace and connect its database.</p>
    </div>
    <?php if ($errorMessage !== ''): ?>
      <div class="install-error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post" class="install-form">
      <section class="install-section">
        <h2>About you</h2>
        <p class="muted">Choose how your private phone book appears.</p>
        <div class="form-grid">
          <label class="field"><span>App name</span><input name="appName" value="<?= htmlspecialchars((string)($_POST['appName'] ?? 'My Phone Book'), ENT_QUOTES, 'UTF-8') ?>" required></label>
          <label class="field"><span>Owner name</span><input name="ownerName" value="<?= htmlspecialchars((string)($_POST['ownerName'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></label>
        </div>
        <label class="field install-theme"><span>Appearance</span>
          <select name="theme">
            <option value="light" <?= ($_POST['theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>☀️ Light mode</option>
            <option value="dark" <?= ($_POST['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>🌙 Dark mode</option>
          </select>
        </label>
      </section>
      <section class="install-section">
        <h2>Database connection</h2>
        <p class="muted">Choose a local SQLite file or connect to a MySQL/MariaDB server.</p>
        <label class="field"><span>Database type</span>
          <select name="dbDriver" id="db-driver">
            <option value="sqlite" <?= $postedDriver === 'sqlite' ? 'selected' : '' ?>>SQLite — local database file</option>
            <option value="mysql" <?= $postedDriver === 'mysql' ? 'selected' : '' ?>>MySQL / MariaDB</option>
          </select>
        </label>
        <div id="sqlite-fields" class="database-fields <?= $postedDriver === 'mysql' ? 'hidden' : '' ?>">
          <label class="field"><span>SQLite database filename</span>
            <input name="sqliteFile" value="<?= htmlspecialchars((string)($_POST['sqliteFile'] ?? 'phonebook.sqlite'), ENT_QUOTES, 'UTF-8') ?>" required pattern="[A-Za-z0-9][A-Za-z0-9._-]*\.sqlite">
            <small>Created in the app's database folder.</small>
          </label>
        </div>
        <div id="mysql-fields" class="database-fields <?= $postedDriver === 'mysql' ? '' : 'hidden' ?>">
          <div class="form-grid">
            <label class="field"><span>Database host</span><input name="mysqlHost" value="<?= htmlspecialchars((string)($_POST['mysqlHost'] ?? '127.0.0.1'), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span>Port</span><input name="mysqlPort" type="number" min="1" max="65535" value="<?= htmlspecialchars((string)($_POST['mysqlPort'] ?? '3306'), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span>Database name</span><input name="mysqlDb" value="<?= htmlspecialchars((string)($_POST['mysqlDb'] ?? 'phonebook'), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span>Database username</span><input name="mysqlUser" autocomplete="username" value="<?= htmlspecialchars((string)($_POST['mysqlUser'] ?? 'root'), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="field"><span>Database password</span><input name="mysqlPass" type="password" autocomplete="new-password"></label>
          </div>
          <small>The installer creates the selected database and app tables. The database user needs permission to create a database.</small>
        </div>
      </section>
      <button class="button button-primary install-submit">Install Phone Book</button>
    </form>
  </main>
  <script>
    const driver = document.getElementById('db-driver');
    const sqliteFields = document.getElementById('sqlite-fields');
    const mysqlFields = document.getElementById('mysql-fields');
    function updateDatabaseFields() {
      const mysql = driver.value === 'mysql';
      mysqlFields.classList.toggle('hidden', !mysql);
      sqliteFields.classList.toggle('hidden', mysql);
      sqliteFields.querySelector('input').required = !mysql;
      mysqlFields.querySelectorAll('input').forEach(input => {
        input.required = mysql && input.name !== 'mysqlPass';
      });
    }
    driver.addEventListener('change', updateDatabaseFields);
    updateDatabaseFields();
  </script>
</body>
</html>
