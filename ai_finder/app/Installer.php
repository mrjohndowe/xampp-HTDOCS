<?php
declare(strict_types=1);

namespace App;

/**
 * First-run installer + installation lock.
 *
 * Locking rules enforced here (server-side, not in JavaScript):
 *  - installed.lock lives in storage/ which Apache denies over HTTP.
 *  - The lock file contains no credentials and no API keys.
 *  - Every installer page and setup endpoint calls isInstalled()/state() first.
 *  - Setup is serialised with an exclusive flock so two concurrent requests
 *    cannot both initialise the database or create duplicate admins.
 */
final class Installer
{
    public function lockPath(): string
    {
        return APP_ROOT . '/storage/installed.lock';
    }

    private function mutexPath(): string
    {
        return APP_ROOT . '/storage/setup.mutex';
    }

    public function configPath(): string
    {
        return APP_ROOT . '/config/config.php';
    }

    /** @return array{lock:bool,db_record:bool,installed:bool,recovery:bool,record:array<string,mixed>|null} */
    public function state(): array
    {
        $lock = is_file($this->lockPath());
        $record = null;
        if (is_file($this->configPath())) {
            $cfg = require $this->configPath();
            if (is_array($cfg)) {
                try {
                    $db = new Database($cfg['db'] ?? []);
                    if ($db->tableExists('install_meta')) {
                        $record = $db->one('SELECT * FROM install_meta ORDER BY installed_at DESC LIMIT 1');
                    }
                } catch (\Throwable $e) {
                    $record = null;
                }
            }
        }
        $dbRecord = $record !== null;
        return [
            'lock' => $lock,
            'db_record' => $dbRecord,
            'installed' => $lock || $dbRecord,
            'recovery' => $dbRecord && !$lock,
            'record' => $record,
        ];
    }

    public function isInstalled(): bool
    {
        return $this->state()['installed'];
    }

    public function recoveryInstructions(): array
    {
        return [
            'This installation is recorded in the database, but storage/installed.lock is missing.',
            'Restore the lock file without touching accounts, leads or settings with one of these:',
            'Windows PowerShell:  Set-Content -Path "B:\\xampp\\htdocs\\ai_finder\\storage\\installed.lock" -Value "installed" ',
            'Windows Command Prompt:  echo installed > B:\\xampp\\htdocs\\ai_finder\\storage\\installed.lock',
            'Or copy storage/installed.lock.sample to storage/installed.lock',
            'Nothing else is required — the database, admin account, leads and settings stay exactly as they are.',
        ];
    }

    /* -------------------------------------------------------------- *
     * Requirements
     * -------------------------------------------------------------- */

    /** @return array<int,array{label:string,ok:bool,hint:string,required:bool}> */
    public function requirements(string $driver = 'sqlite'): array
    {
        $checks = [];
        $checks[] = [
            'label' => 'PHP 8.2 or newer (running ' . PHP_VERSION . ')',
            'ok' => PHP_VERSION_ID >= 80200,
            'hint' => 'In XAMPP Control Panel click "Config" next to Apache → php.ini, or upgrade XAMPP to 8.2+.',
            'required' => true,
        ];
        foreach (['pdo', 'json', 'session', 'openssl', 'mbstring', 'filter'] as $ext) {
            $ok = extension_loaded($ext);
            $checks[] = [
                'label' => 'PHP extension: ' . $ext,
                'ok' => $ok,
                'hint' => 'Edit B:\\xampp\\php\\php.ini and remove the leading semicolon from extension=' . $ext . ', then restart Apache.',
                'required' => true,
            ];
        }
        $phpIni = php_ini_loaded_file() ?: 'the php.ini used by Apache';
        $checks[] = [
            'label' => 'PHP extension: curl',
            'ok' => extension_loaded('curl') && function_exists('curl_init'),
            'hint' => 'Check extension=curl in ' . $phpIni . ' and restart Apache. If it is already enabled, check the Apache error log for PHP Startup warnings about php_curl.dll and its dependencies.',
            'required' => true,
        ];
        if ($driver === 'mysql') {
            $ok = extension_loaded('pdo_mysql');
            $checks[] = [
                'label' => 'PHP extension: pdo_mysql (chosen MySQL/MariaDB driver)',
                'ok' => $ok,
                'hint' => 'Remove the semicolon before extension=pdo_mysql in B:\\xampp\\php\\php.ini and restart Apache.',
                'required' => true,
            ];
        } else {
            $ok = extension_loaded('pdo_sqlite');
            $checks[] = [
                'label' => 'PHP extension: pdo_sqlite (chosen SQLite driver)',
                'ok' => $ok,
                'hint' => 'Remove the semicolon before extension=pdo_sqlite in B:\\xampp\\php\\php.ini and restart Apache. SQLite needs no server.',
                'required' => true,
            ];
        }
        $writablePaths = [
            APP_ROOT . '/storage' => 'storage/ (database, logs, sessions, lock file)',
            APP_ROOT . '/storage/logs' => 'storage/logs/',
            APP_ROOT . '/config' => 'config/ (generated config.php)',
        ];
        foreach ($writablePaths as $path => $label) {
            if (!is_dir($path)) {
                @mkdir($path, 0775, true);
            }
            $ok = is_dir($path) && is_writable($path);
            $checks[] = [
                'label' => 'Writable: ' . $label,
                'ok' => $ok,
                'hint' => 'Right-click the folder in Windows Explorer → Properties → uncheck Read-only, or run: icacls "' . $path . '" /grant "Users:(OI)(CI)M"',
                'required' => true,
            ];
        }
        $remote = function_exists('curl_init') || (bool) ini_get('allow_url_fopen');
        $checks[] = [
            'label' => 'Outbound HTTP available (cURL or allow_url_fopen)',
            'ok' => $remote,
            'hint' => 'Live discovery and audits need outbound HTTP. Demo Mode works without it. Enable extension=curl in php.ini.',
            'required' => false,
        ];
        return $checks;
    }

    /** @return string[] */
    public function requirementFailures(string $driver = 'sqlite'): array
    {
        $out = [];
        foreach ($this->requirements($driver) as $check) {
            if ($check['required'] && !$check['ok']) {
                $out[] = $check['label'] . ' — ' . $check['hint'];
            }
        }
        return $out;
    }

    /* -------------------------------------------------------------- *
     * Setup execution
     * -------------------------------------------------------------- */

    /**
     * @param array<string,mixed> $in
     * @return array{ok:bool,errors:array<int,string>,log:array<int,string>,redirect?:string}
     */
    public function run(array $in): array
    {
        $log = [];
        $errors = [];

        $storageDir = APP_ROOT . '/storage';
        foreach ([$storageDir, $storageDir . '/logs', APP_ROOT . '/config'] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }

        // --- Serialise setup so concurrent requests cannot double-initialise.
        $mutex = fopen($this->mutexPath(), 'c');
        if ($mutex === false) {
            return ['ok' => false, 'errors' => ['Could not create storage/setup.mutex. Check that storage/ is writable.'], 'log' => []];
        }
        $lockStart = microtime(true);
        $acquired = false;
        while (microtime(true) - $lockStart < 20) {
            if (flock($mutex, LOCK_EX | LOCK_NB)) {
                $acquired = true;
                break;
            }
            usleep(250000);
        }
        if (!$acquired) {
            flock($mutex, LOCK_EX); // blocking fallback
            $acquired = true;
        }

        try {
            // Re-check inside the lock: another request may have finished setup.
            if ($this->isInstalled()) {
                return [
                    'ok' => false,
                    'errors' => ['LocalProof is already installed. The installer is permanently disabled by storage/installed.lock. Sign in at /login.'],
                    'log' => [],
                    'redirect' => 'login',
                ];
            }

            $mode = ($in['mode'] ?? 'demo') === 'live' ? 'live' : 'demo';
            $driver = ($in['driver'] ?? 'sqlite') === 'mysql' ? 'mysql' : 'sqlite';
            $failures = $this->requirementFailures($driver);
            if ($failures !== []) {
                return ['ok' => false, 'errors' => $failures, 'log' => []];
            }

            $cfg = $this->buildConfig($in, $driver);
            $db = new Database($cfg['db']);
            $test = $db->testConnection();
            if (!$test['ok']) {
                return ['ok' => false, 'errors' => [$test['message']], 'log' => $log];
            }
            if ($driver === 'mysql') {
                $log[] = 'Connected to MySQL/MariaDB.';
            } else {
                $log[] = 'SQLite database ready at ' . $cfg['db']['sqlite_path'] . '.';
            }

            $migrations = $db->migrate();
            $log[] = count($migrations) . ' schema statement(s) applied.';

            // No existing admin may be duplicated by a retried setup.
            $auth = new Auth($db);
            $userCount = $auth->countUsers();
            if ($userCount > 0) {
                return [
                    'ok' => false,
                    'errors' => ['An administrator already exists in this database, so setup cannot create another. Nothing was changed — sign in with the existing account, or point the installer at an empty database.'],
                    'log' => $log,
                ];
            }

            $admin = $auth->createAdmin(
                (string) ($in['admin_name'] ?? ''),
                (string) ($in['admin_email'] ?? ''),
                (string) ($in['admin_password'] ?? '')
            );
            if (!$admin['ok']) {
                return ['ok' => false, 'errors' => $admin['errors'], 'log' => $log];
            }
            $log[] = 'Administrator account created (password stored with a bcrypt hash).';

            // Write config.php (credentials) — denied over HTTP by .htaccess.
            $written = $this->writeConfig($cfg);
            if (!$written) {
                return ['ok' => false, 'errors' => ['Could not write config/config.php. Make sure the config/ folder is writable, then retry setup.'], 'log' => $log];
            }
            $log[] = 'config/config.php written (credentials live outside the web-visible area and are blocked by Apache).';

            $settings = new Settings($db);
            $settings->setMany([
                'mode' => $mode,
                'installation_id' => bin2hex(random_bytes(8)),
            ]);

            if ($mode === 'demo') {
                $seeded = Discovery::seedDemo($db, (int) $admin['id']);
                $log[] = 'Demo Mode: seeded ' . $seeded['leads'] . ' leads, ' . $seeded['audits'] . ' audits, ' . $seeded['pitches'] . ' pitches and ' . $seeded['events'] . ' outreach events.';
            } else {
                $settings->setMany([
                    'google_places_api_key' => (string) ($in['google_places_api_key'] ?? ''),
                    'ai_api_key' => (string) ($in['ai_api_key'] ?? ''),
                ]);
                $log[] = 'Live Mode: keys stored in the database (masked in the UI). No key is written to installed.lock.';
            }

            // Record installation in the database *before* creating the lock.
            $db->insert('install_meta', [
                'installed_at' => Support::now(),
                'version' => Database::VERSION,
                'mode' => $mode,
                'lock_path' => 'storage/installed.lock',
                'notes' => 'Created by the LocalProof first-run installer.',
            ]);
            $log[] = 'Installation recorded in the install_meta table.';

            // Finally: create the lock. Contains no secrets.
            $lockBody = "# LocalProof installation lock. Delete nothing else to recover.\n"
                . "installed_at=" . Support::now() . "\nmode=" . $mode . "\nversion=" . Database::VERSION . "\n";
            if (@file_put_contents($this->lockPath(), $lockBody) === false) {
                return [
                    'ok' => false,
                    'errors' => ['Database initialised and admin created, but storage/installed.lock could not be written. Make storage/ writable and re-run setup — the existing admin is detected and protected, so you will be told to sign in instead of duplicating it.'],
                    'log' => $log,
                ];
            }
            $log[] = 'storage/installed.lock created — the installer is now permanently closed.';

            $_SESSION['uid'] = (int) $admin['id'];
            session_regenerate_id(true);

            return ['ok' => true, 'errors' => [], 'log' => $log, 'redirect' => 'onboarding'];
        } catch (\Throwable $e) {
            Support::log('installer', $e->getMessage());
            return [
                'ok' => false,
                'errors' => ['Setup failed: ' . $e->getMessage() . ' — Nothing was committed that would block a retry. Fix the issue above and run setup again.'],
                'log' => $log,
            ];
        } finally {
            if (isset($acquired) && $acquired) {
                @flock($mutex, LOCK_UN);
            }
            @fclose($mutex);
        }
    }

    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    private function buildConfig(array $in, string $driver): array
    {
        $db = ['driver' => $driver];
        if ($driver === 'mysql') {
            $db['mysql_host'] = trim((string) ($in['mysql_host'] ?? '127.0.0.1')) ?: '127.0.0.1';
            $db['mysql_port'] = trim((string) ($in['mysql_port'] ?? '3306')) ?: '3306';
            $db['mysql_database'] = trim((string) ($in['mysql_database'] ?? 'localproof')) ?: 'localproof';
            $db['mysql_username'] = (string) ($in['mysql_username'] ?? 'root');
            $db['mysql_password'] = (string) ($in['mysql_password'] ?? '');
            $db['mysql_socket'] = (string) ($in['mysql_socket'] ?? '');
            $db['sqlite_path'] = 'storage/localproof.sqlite';
        } else {
            $db['sqlite_path'] = 'storage/localproof.sqlite';
            $db['mysql_host'] = '127.0.0.1';
            $db['mysql_port'] = '3306';
            $db['mysql_database'] = 'localproof';
            $db['mysql_username'] = 'root';
            $db['mysql_password'] = '';
            $db['mysql_socket'] = '';
        }

        return [
            'app' => [
                'base_url' => BASE_URL,
                'debug' => false,
                'timezone' => 'America/Denver',
            ],
            'db' => $db,
            'http' => [
                'connect_timeout' => 5,
                'timeout' => 12,
                'verify_ssl' => true,
                'user_agent' => 'Mozilla/5.0 (compatible; LocalProof/1.0)',
            ],
        ];
    }

    /** @param array<string,mixed> $cfg */
    private function writeConfig(array $cfg): bool
    {
        $export = "<?php\n/**\n * Generated by the LocalProof installer. Keep this file out of version control.\n */\ndeclare(strict_types=1);\n\nreturn " . $this->prettyExport($cfg) . ";\n";
        return @file_put_contents($this->configPath(), $export, LOCK_EX) !== false;
    }

    /** @param mixed $value */
    private function prettyExport($value, int $indent = 1): string
    {
        if (!is_array($value)) {
            return var_export($value, true);
        }
        $pad = str_repeat('    ', $indent);
        $out = "[\n";
        foreach ($value as $key => $item) {
            $out .= $pad . var_export((string) $key, true) . ' => ' . $this->prettyExport($item, $indent + 1) . ",\n";
        }
        $out .= str_repeat('    ', max(0, $indent - 1)) . ']';
        return $out;
    }
}
