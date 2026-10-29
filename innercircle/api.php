<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
ic_headers();
header('Content-Type: application/json; charset=utf-8');
function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    exit;
}
function public_data(): array
{
    $installed = ic_installed();
    $meta = $installed ? ic_meta() : [];
    $auth = !$installed || ic_authorized($meta);
    $base = ['installed' => $installed, 'authenticated' => $auth, 'ownerName' => $auth ? ($meta['ownerName'] ?? '') : '', 'ownerEmail' => $auth ? ($meta['ownerEmail'] ?? '') : '', 'name' => $auth ? ($meta['name'] ?? 'My personal space') : 'Your private workspace', 'journalEnabled' => $meta['journalEnabled'] ?? true, 'autoLockMinutes' => $meta['autoLockMinutes'] ?? 15, 'engine' => 'SQLite', 'mysqlBackup' => !empty(ic_config()['mysql']), 'contacts' => [], 'accounts' => [], 'journals' => []];
    if (!$installed || !$auth) return $base;
    $base['contacts'] = ic_all('contact');
    $base['accounts'] = array_map(static function (array $a): array {
        unset($a['encryptedData']);
        return $a;
    }, ic_all('account'));
    $base['journals'] = array_map(static function (array $j): array {
        $secret = ic_decrypt($j['encryptedData']);
        unset($j['encryptedData']);
        return array_merge($j, $secret);
    }, ic_all('journal'));
    return $base;
}
try {
    ic_start_session();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') respond(public_data());
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['error' => 'Method not allowed.'], 405);
    if (($_SERVER['HTTP_X_INNERCIRCLE_REQUEST'] ?? '') !== '1') throw new DomainException('Request verification failed.', 403);
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $origin = parse_url($_SERVER['HTTP_ORIGIN']);
        $host = ($origin['host'] ?? '') . (isset($origin['port']) ? ':' . $origin['port'] : '');
        if (strcasecmp($host, $_SERVER['HTTP_HOST'] ?? '') !== 0) throw new DomainException('Cross-origin requests are not allowed.', 403);
    }
    $raw = file_get_contents('php://input', false, null, 0, 100001);
    if ($raw === false || strlen($raw) > 100000) throw new DomainException('This entry is too large.', 413);
    $body = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    if (!is_array($body)) throw new DomainException('Invalid request.');
    $action = ic_text($body['action'] ?? '');
    if ($action === 'setup') {
        if (ic_installed() || is_file(ic_private_dir() . '/install.lock')) throw new DomainException('Installation is locked. Your workspace is already set up.', 409);
        ic_requirements();
        ic_prepare_dir();
        $guard = fopen(ic_private_dir() . '/installation.mutex', 'c');
        if (!$guard || !flock($guard, LOCK_EX)) throw new RuntimeException('Could not lock the installation process.');
        try {
            if (ic_installed() || is_file(ic_private_dir() . '/install.lock')) throw new DomainException('Installation is already locked.', 409);
            $owner = ic_text($body['ownerName'] ?? '', 100);
            $email = ic_text($body['ownerEmail'] ?? '');
            $name = ic_text($body['name'] ?? '', 100);
            $password = is_string($body['password'] ?? null) ? $body['password'] : '';
            if ($owner === '' || $name === '' || $email === '') throw new DomainException('Your name, email, and workspace name are required.');
            ic_email($email);
            if (strlen($password) < 12 || strlen($password) > 256) throw new DomainException('Choose a password between 12 and 256 characters.');
            if (!empty($body['journalEnabled']) && ($body['isAdult'] ?? false) !== true) throw new DomainException('Confirm you are 18 or older to enable the intimacy journal.');
            $config = ['version' => 1, 'installedAt' => gmdate('c'), 'encryptionKey' => base64_encode(sodium_crypto_secretbox_keygen()), 'mysql' => null];
            if (!empty($body['mysqlBackup'])) {
                $host = ic_text($body['mysqlHost'] ?? '', 255);
                $database = ic_text($body['mysqlDatabase'] ?? '', 64);
                $port = filter_var($body['mysqlPort'] ?? 3306, FILTER_VALIDATE_INT);
                if (!preg_match('/^[a-zA-Z0-9.:-]+$/', $host) || !preg_match('/^[a-zA-Z0-9_]+$/', $database) || $port === false || $port < 1 || $port > 65535) throw new DomainException('Please check the MySQL host, database name, and port.');
                $config['mysql'] = ['host' => $host, 'port' => $port, 'database' => $database, 'username' => ic_text($body['mysqlUsername'] ?? '', 100), 'password' => (string)($body['mysqlPassword'] ?? '')];
                try {
                    ic_mysql($config);
                } catch (PDOException $error) {
                    throw new DomainException('Could not connect to the MySQL backup database. Verify the credentials and create the database first.');
                }
            }
            $settings = ['installed' => true, 'ownerName' => $owner, 'ownerEmail' => $email, 'name' => $name, 'passwordHash' => password_hash(ic_password_input($password), PASSWORD_DEFAULT), 'journalEnabled' => ($body['journalEnabled'] ?? false) === true, 'autoLockMinutes' => 15, 'authVersion' => bin2hex(random_bytes(16))];
            $db = ic_db();
            $db->beginTransaction();
            try {
                $db->exec('DELETE FROM ic_records');
                ic_set_meta($settings);
                if (($body['keepSample'] ?? false) === true) {
                    $seeds = json_decode(file_get_contents(__DIR__ . '/assets/seed.json'), true, 512, JSON_THROW_ON_ERROR);
                    foreach ($seeds as $person) ic_save('contact', $person);
                }
                $temp = ic_private_dir() . '/config-' . bin2hex(random_bytes(8)) . '.tmp';
                if (file_put_contents($temp, '<?php return ' . var_export($config, true) . ';', LOCK_EX) === false) throw new RuntimeException('Could not write the private configuration.');
                @chmod($temp, 0600);
                $db->commit();
                if (!rename($temp, ic_private_dir() . '/config.php')) throw new RuntimeException('Could not finalize the private configuration.');
                file_put_contents(ic_private_dir() . '/install.lock', 'Installation locked at ' . gmdate('c') . "\n", LOCK_EX);
                @chmod(ic_private_dir() . '/install.lock', 0600);
            } catch (Throwable $error) {
                if ($db->inTransaction()) $db->rollBack();
                if (isset($temp) && is_file($temp)) unlink($temp);
                throw $error;
            }
            ic_sign_in();
        } finally {
            flock($guard, LOCK_UN);
            fclose($guard);
        }
        respond(['ok' => true]);
    }
    if (!ic_installed()) throw new DomainException('Finish setup first to protect your private information.', 403);
    $meta = ic_meta();
    if ($action === 'login') {
        ic_login_attempt('check');
        $password = is_string($body['password'] ?? null) ? substr($body['password'], 0, 256) : '';
        if (!password_verify(ic_password_input($password), $meta['passwordHash'])) {
            ic_login_attempt('failed');
            throw new DomainException('That password doesn’t match. Please try again.', 401);
        }
        ic_login_attempt('reset');
        ic_sign_in();
        respond(['ok' => true]);
    }
    if (!ic_authorized($meta)) throw new DomainException('Your workspace is locked. Please sign in again.', 401);
    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        respond(['ok' => true]);
    }
    $id = ic_text($body['id'] ?? '', 80);
    if ($action === 'saveContact') {
        $name = ic_text($body['name'] ?? '', 100);
        $email = ic_text($body['email'] ?? '');
        $dob = ic_text($body['dob'] ?? '', 10);
        $group = ic_text($body['group'] ?? '');
        if ($name === '') throw new DomainException('A contact name is required.');
        ic_email($email);
        if ($dob !== '' && (!ic_valid_date($dob) || ic_age($dob) < 0 || ic_age($dob) > 125)) throw new DomainException('Please enter a valid past date of birth.');
        if (!in_array($group, ['Mother', 'Father', 'Brother', 'Sister', 'Son', 'Daughter', 'Friend', 'Best-Friend', 'Family', 'Work', 'Girl-Friend', 'Boy-Friend', 'Partner', 'Ex', 'Fling', 'Friends with Benefits', 'One Night Stand', 'Other'], true)) throw new DomainException('Choose a valid relationship.');
        $old = $id !== '' ? ic_record($id, 'contact') : null;
        if ($id !== '' && !$old) throw new DomainException('Contact not found.', 404);
        ic_save('contact', ['id' => $id ?: bin2hex(random_bytes(16)), 'name' => $name, 'email' => $email, 'dob' => $dob, 'group' => $group, 'title' => ic_text($body['title'] ?? '', 120), 'phone' => ic_text($body['phone'] ?? '', 60), 'address' => ic_text($body['address'] ?? '', 600), 'notes' => ic_text($body['notes'] ?? '', 10000), 'favorite' => ($body['favorite'] ?? false) === true, 'avatar' => $old['avatar'] ?? '']);
    } elseif ($action === 'toggleFavorite') {
        $person = ic_record($id, 'contact');
        if (!$person) throw new DomainException('Contact not found.', 404);
        $person['favorite'] = ($body['favorite'] ?? false) === true;
        ic_save('contact', $person);
    } elseif ($action === 'deleteContact') {
        $db = ic_db();
        $db->beginTransaction();
        try {
            foreach (['account', 'journal'] as $kind) foreach (ic_all($kind) as $record) if ($record['contactId'] === $id) ic_delete($record['id'], $kind);
            ic_delete($id, 'contact');
            $db->commit();
        } catch (Throwable $error) {
            $db->rollBack();
            throw $error;
        }
    } elseif ($action === 'saveAccount' || $action === 'saveJournal') {
        $contactId = ic_text($body['contactId'] ?? '', 80);
        $person = ic_record($contactId, 'contact');
        if (!$person) throw new DomainException('Please choose an existing contact.');
        if ($action === 'saveAccount') {
            if ($id !== '' && !ic_record($id, 'account')) throw new DomainException('Account not found.', 404);
            $title = ic_text($body['title'] ?? '', 120);
            $type = ic_text($body['type'] ?? '');
            $email = ic_text($body['email'] ?? '');
            ic_email($email);
            if ($title === '' || !in_array($type, ['Online account', 'Bank account', 'Other'], true)) throw new DomainException('Add an account name and choose its type.');
            $secret = ['password' => is_string($body['password'] ?? null) ? substr($body['password'], 0, 500) : '', 'routingNumber' => ic_text($body['routingNumber'] ?? '', 30), 'accountNumber' => ic_text($body['accountNumber'] ?? '', 40), 'notes' => ic_text($body['notes'] ?? '', 10000)];
            if ($type === 'Bank account' && (!preg_match('/^\d{9}$/', $secret['routingNumber']) || !preg_match('/^\d{4,34}$/', $secret['accountNumber']))) throw new DomainException('Enter a 9-digit US routing number and a 4–34 digit account number.');
            if ($type !== 'Bank account') {
                $secret['routingNumber'] = '';
                $secret['accountNumber'] = '';
            }
            ic_save('account', ['id' => $id ?: bin2hex(random_bytes(16)), 'contactId' => $contactId, 'title' => $title, 'type' => $type, 'username' => ic_text($body['username'] ?? ''), 'email' => $email, 'encryptedData' => ic_encrypt($secret)]);
        } else {
            if (empty($meta['journalEnabled'])) throw new DomainException('Enable your private journal in Settings first.');
            if ($id !== '' && !ic_record($id, 'journal')) throw new DomainException('Journal entry not found.', 404);
            $type = ic_text($body['type'] ?? '');
            $date = ic_text($body['date'] ?? '', 10);
            $mood = ic_text($body['mood'] ?? '', 40);
            if (!in_array($type, ['Reflection', 'Intimacy'], true) || !ic_valid_date($date) || $date > gmdate('Y-m-d')) throw new DomainException('Choose a valid entry type and a date that is not in the future.');
            if ($type === 'Intimacy' && (($body['adults'] ?? false) !== true || empty($person['dob']) || ic_age($person['dob'], $date) < 18)) throw new DomainException('Intimacy entries are only for adults 18+. Add the person’s date of birth and confirm both people were adults on this date.');
            $secret = ['notes' => ic_text($body['notes'] ?? '', 10000), 'consent' => ic_text($body['consent'] ?? ''), 'protection' => ic_text($body['protection'] ?? '', 1000), 'communication' => ic_text($body['communication'] ?? '', 1000), 'wellbeing' => ic_text($body['wellbeing'] ?? '', 1000), 'followUp' => ic_text($body['followUp'] ?? '', 1000), 'adults' => ($body['adults'] ?? false) === true];
            if ($secret['notes'] === '') throw new DomainException('Add a few words to your reflection.');
            if ($type === 'Intimacy' && $secret['consent'] === '') throw new DomainException('Please answer the consent question.');
            ic_save('journal', ['id' => $id ?: bin2hex(random_bytes(16)), 'contactId' => $contactId, 'date' => $date, 'type' => $type, 'mood' => $mood, 'encryptedData' => ic_encrypt($secret)]);
        }
    } elseif ($action === 'revealAccount') {
        $account = ic_record($id, 'account');
        if (!$account) throw new DomainException('Account not found.', 404);
        $secret = ic_decrypt($account['encryptedData']);
        unset($account['encryptedData']);
        respond(['account' => array_merge($account, $secret)]);
    } elseif ($action === 'deleteAccount' || $action === 'deleteJournal') {
        ic_delete($id, $action === 'deleteAccount' ? 'account' : 'journal');
    } elseif ($action === 'updateSettings') {
        $name = ic_text($body['name'] ?? '', 100);
        $owner = ic_text($body['ownerName'] ?? '', 100);
        $email = ic_text($body['ownerEmail'] ?? '');
        ic_email($email);
        if ($name === '' || $owner === '' || $email === '') throw new DomainException('Workspace name, your name, and email are required.');
        $minutes = (int)($body['autoLockMinutes'] ?? 0);
        if (!in_array($minutes, [5, 15, 30, 60], true)) throw new DomainException('Choose a valid auto-lock interval.');
        if (($body['journalEnabled'] ?? false) === true && empty($meta['journalEnabled']) && ($body['isAdult'] ?? false) !== true) throw new DomainException('Confirm you are 18 or older to enable the intimacy journal.');
        $meta = array_merge($meta, ['name' => $name, 'ownerName' => $owner, 'ownerEmail' => $email, 'autoLockMinutes' => $minutes, 'journalEnabled' => ($body['journalEnabled'] ?? false) === true]);
        ic_set_meta($meta);
    } elseif ($action === 'backup') {
        respond(['backup' => ic_backup()]);
    } elseif ($action === 'mysqlBackup') {
        try {
            $mysql = ic_mysql(ic_config());
            $snapshot = ic_backup();
            $stmt = $mysql->prepare('INSERT INTO innercircle_backups (id, created_at, encrypted_payload) VALUES (?, ?, ?)');
            $stmt->execute([bin2hex(random_bytes(16)), $snapshot['exportedAt'], $snapshot['encryptedPayload']]);
        } catch (PDOException $error) {
            error_log('innercircle MySQL backup: ' . $error->getMessage());
            throw new DomainException('MySQL backup could not be completed. Your SQLite data is unchanged. Check the backup database connection.');
        }
    } else throw new DomainException('Unknown workspace action.');
    respond(['ok' => true]);
} catch (DomainException $error) {
    $status = $error->getCode();
    respond(['error' => $error->getMessage()], $status >= 400 && $status < 600 ? $status : 400);
} catch (JsonException $error) {
    respond(['error' => 'Please check the submitted information.'], 400);
} catch (Throwable $error) {
    error_log('innercircle: ' . $error->getMessage());
    respond(['error' => !ic_installed() && $error instanceof RuntimeException && !$error instanceof PDOException ? $error->getMessage() : 'Your workspace could not complete this request. Please check your server configuration and try again.'], 500);
}
