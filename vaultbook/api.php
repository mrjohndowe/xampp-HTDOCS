<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function respond(mixed $value, int $status = 200): never {
    http_response_code($status);
    echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function request_data(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        respond(['error' => 'Request body must be valid JSON.'], 400);
    }
    return $data;
}

function camel_row(array $row): array {
    foreach ($row as $key => $value) {
        $camel = preg_replace_callback('/_([a-z])/', static fn(array $m): string => strtoupper($m[1]), $key);
        if (in_array($key, ['is_favorite', 'is_primary', 'is_intimate', 'consent_confirmed', 'sti_tested_recent', 'is_private'], true)) {
            $value = (bool)$value;
        }
        $row[$camel] = $value;
        if ($camel !== $key) {
            unset($row[$key]);
        }
    }
    return $row;
}

function string_value(array $data, string $key, string $default = ''): string {
    $value = $data[$key] ?? $default;
    return is_scalar($value) ? trim((string)$value) : $default;
}

function bool_value(array $data, string $key, bool $default = false): int {
    return filter_var($data[$key] ?? $default, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
}

function table_columns(string $table, ?PDO $connection = null): array {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    $pdo = $connection ?? pdo();
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $rows = $pdo->query("PRAGMA table_info(`$table`)")->fetchAll(PDO::FETCH_ASSOC);
        $cache[$table] = array_column($rows, 'name');
    } else {
        $rows = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
        $cache[$table] = array_column($rows, 'Field');
    }
    return $cache[$table];
}

function save_record(string $table, array $data, array $mapping, ?int $id = null, ?PDO $connection = null): array {
    $pdo = $connection ?? pdo();
    $values = [];
    foreach ($mapping as $field => $column) {
        if (array_key_exists($field, $data)) {
            $values[$column] = $data[$field];
        }
    }
    $available = table_columns($table, $pdo);
    $values = array_filter($values, static fn($value, $column): bool => in_array($column, $available, true), ARRAY_FILTER_USE_BOTH);
    if ($id === null) {
        $columns = array_keys($values);
        $quoted = array_map(static fn(string $column): string => "`$column`", $columns);
        $sql = 'INSERT INTO `' . $table . '` (' . implode(',', $quoted) . ') VALUES (' .
            implode(',', array_fill(0, count($columns), '?')) . ')';
        $pdo->prepare($sql)->execute(array_values($values));
        $id = (int)$pdo->lastInsertId();
    } else {
        if (!$values) {
            respond(['error' => 'No editable fields were provided.'], 400);
        }
        $sets = array_map(static fn(string $column): string => "`$column` = ?", array_keys($values));
        $pdo->prepare('UPDATE `' . $table . '` SET ' . implode(',', $sets) . ' WHERE id = ?')
            ->execute([...array_values($values), $id]);
    }
    $statement = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
    $statement->execute([$id]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        respond(['error' => 'Record not found.'], 404);
    }
    return camel_row($row);
}

$action = (string)($_GET['action'] ?? '');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$data = in_array($method, ['POST', 'PUT', 'PATCH'], true) ? request_data() : [];

try {
    if ($action !== 'install-status' && !is_installed()) {
        respond(['error' => 'Phone Book is not installed. Open the installer first.'], 403);
    }

    $pdo = pdo();
    $contactMap = [
        'firstName' => 'first_name', 'lastName' => 'last_name', 'nickname' => 'nickname', 'dob' => 'dob',
        'email' => 'email', 'phone' => 'phone', 'phone2' => 'phone2', 'address' => 'address',
        'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'country' => 'country',
        'relationship' => 'relationship', 'gender' => 'gender', 'pronouns' => 'pronouns',
        'company' => 'company', 'jobTitle' => 'job_title', 'tags' => 'tags', 'notes' => 'notes',
        'isFavorite' => 'is_favorite', 'avatarColor' => 'avatar_color', 'howWeMet' => 'how_we_met',
        'anniversary' => 'anniversary',
    ];
    $accountMap = [
        'contactId' => 'contact_id', 'label' => 'label', 'type' => 'type', 'username' => 'username',
        'email' => 'email', 'password' => 'password', 'website' => 'website', 'bankName' => 'bank_name',
        'routingNumber' => 'routing_number', 'accountNumber' => 'account_number', 'accountType' => 'account_type',
        'notes' => 'notes', 'isPrimary' => 'is_primary',
    ];
    $journalMap = [
        'contactId' => 'contact_id', 'entryDate' => 'entry_date', 'title' => 'title', 'entryType' => 'entry_type',
        'isIntimate' => 'is_intimate', 'mood' => 'mood', 'location' => 'location', 'notes' => 'notes',
        'rating' => 'rating', 'intimacyType' => 'intimacy_type', 'protectionUsed' => 'protection_used',
        'contraceptionNotes' => 'contraception_notes', 'consentConfirmed' => 'consent_confirmed',
        'durationMinutes' => 'duration_minutes', 'satisfaction' => 'satisfaction',
        'partnerSatisfaction' => 'partner_satisfaction', 'emotionalCloseness' => 'emotional_closeness',
        'communicationRating' => 'communication_rating', 'activities' => 'activities',
        'positionNotes' => 'position_notes', 'orgasm' => 'orgasm', 'aftercare' => 'aftercare',
        'moodBefore' => 'mood_before', 'moodAfter' => 'mood_after', 'healthConcerns' => 'health_concerns',
        'stiTestedRecent' => 'sti_tested_recent', 'wouldRepeat' => 'would_repeat',
        'whatWentWell' => 'what_went_well', 'whatToImprove' => 'what_to_improve', 'isPrivate' => 'is_private',
    ];

    if ($action === 'install-status') {
        respond(['installed' => is_installed()]);
    }

    if ($action === 'settings' && $method === 'GET') {
        $rows = $pdo->query('SELECT `key`, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        $rows['dbDriver'] = DB_DRIVER;
        $database = DB_DRIVER === 'mysql'
            ? ['driver' => 'mysql', 'host' => MYSQL_HOST, 'port' => MYSQL_PORT, 'name' => MYSQL_DB, 'user' => MYSQL_USER]
            : ['driver' => 'sqlite', 'file' => basename(SQLITE_PATH)];
        respond(['installed' => ($rows['installed'] ?? '') === 'true', 'settings' => $rows, 'database' => $database, 'lockFileExists' => is_file(INSTALL_LOCK)]);
    }

    if ($action === 'settings' && $method === 'POST') {
        $theme = $data['theme'] ?? null;
        if (!in_array($theme, ['light', 'dark'], true)) {
            respond(['error' => 'Theme must be light or dark.'], 400);
        }
        $statement = $pdo->prepare('SELECT `value` FROM settings WHERE `key` = ?');
        $statement->execute(['theme']);
        if ($statement->fetchColumn() !== false) {
            $update = $pdo->prepare('UPDATE settings SET `value` = ?, updated_at = CURRENT_TIMESTAMP WHERE `key` = ?');
            $update->execute([$theme, 'theme']);
        } else {
            $insert = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?)');
            $insert->execute(['theme', $theme]);
        }
        respond(['ok' => true, 'theme' => $theme]);
    }

    if ($action === 'import' && $method === 'POST') {
        $backup = $data['backup'] ?? null;
        $mode = $data['mode'] ?? 'merge';
        if (!is_array($backup) || !in_array($mode, ['merge', 'replace'], true)) {
            respond(['error' => 'Choose merge or replace and provide a valid JSON backup.'], 400);
        }
        $backupContacts = $backup['contacts'] ?? null;
        $backupAccounts = $backup['accounts'] ?? [];
        $backupJournal = $backup['journalEntries'] ?? $backup['journal_entries'] ?? [];
        if (!is_array($backupContacts) || !array_is_list($backupContacts) ||
            !is_array($backupAccounts) || !array_is_list($backupAccounts) ||
            !is_array($backupJournal) || !array_is_list($backupJournal) ||
            count($backupContacts) > 10000 || count($backupAccounts) > 50000 || count($backupJournal) > 50000) {
            respond(['error' => 'Backup must contain contacts, accounts, and journal entry arrays within the supported size limits.'], 400);
        }

        $pdo->beginTransaction();
        try {
            if ($mode === 'replace') {
                $pdo->exec('DELETE FROM journal_entries');
                $pdo->exec('DELETE FROM accounts');
                $pdo->exec('DELETE FROM contacts');
            }

            $contactIds = [];
            foreach ($backupContacts as $contactRow) {
                if (!is_array($contactRow)) {
                    throw new InvalidArgumentException('Each contact in the backup must be an object.');
                }
                $contact = camel_row($contactRow);
                $oldId = filter_var($contact['id'] ?? null, FILTER_VALIDATE_INT);
                $contact['isFavorite'] = bool_value($contact, 'isFavorite');
                $saved = save_record('contacts', $contact, $contactMap, null, $pdo);
                if ($oldId !== false && $oldId !== null) {
                    $contactIds[(string)$oldId] = (int)$saved['id'];
                }
            }

            foreach ($backupAccounts as $accountRow) {
                if (!is_array($accountRow)) {
                    throw new InvalidArgumentException('Each account in the backup must be an object.');
                }
                $account = camel_row($accountRow);
                $sourceContactId = filter_var($account['contactId'] ?? null, FILTER_VALIDATE_INT);
                if ($sourceContactId === false || !isset($contactIds[(string)$sourceContactId])) {
                    throw new InvalidArgumentException('An account refers to a contact that is not included in this backup.');
                }
                $account['contactId'] = $contactIds[(string)$sourceContactId];
                $account['isPrimary'] = bool_value($account, 'isPrimary');
                save_record('accounts', $account, $accountMap, null, $pdo);
            }

            foreach ($backupJournal as $journalRow) {
                if (!is_array($journalRow)) {
                    throw new InvalidArgumentException('Each journal entry in the backup must be an object.');
                }
                $entry = camel_row($journalRow);
                $sourceContactId = filter_var($entry['contactId'] ?? null, FILTER_VALIDATE_INT);
                if ($sourceContactId === false || !isset($contactIds[(string)$sourceContactId])) {
                    throw new InvalidArgumentException('A journal entry refers to a contact that is not included in this backup.');
                }
                $entry['contactId'] = $contactIds[(string)$sourceContactId];
                foreach (['isIntimate', 'consentConfirmed', 'stiTestedRecent', 'isPrivate'] as $field) {
                    $entry[$field] = bool_value($entry, $field, $field === 'isPrivate');
                }
                save_record('journal_entries', $entry, $journalMap, null, $pdo);
            }

            $backupSettings = $backup['settings'] ?? [];
            if (is_array($backupSettings)) {
                $allowedSettings = ['appName', 'ownerName', 'theme'];
                $settingsToImport = [];
                if (array_is_list($backupSettings)) {
                    foreach ($backupSettings as $settingRow) {
                        if (is_array($settingRow) && isset($settingRow['key'], $settingRow['value'])) {
                            $settingsToImport[(string)$settingRow['key']] = (string)$settingRow['value'];
                        }
                    }
                } else {
                    $settingsToImport = $backupSettings;
                }
                foreach ($allowedSettings as $settingKey) {
                    if (!isset($settingsToImport[$settingKey])) {
                        continue;
                    }
                    $settingValue = (string)$settingsToImport[$settingKey];
                    if ($settingKey === 'theme' && !in_array($settingValue, ['light', 'dark'], true)) {
                        continue;
                    }
                    $statement = $pdo->prepare('SELECT `value` FROM settings WHERE `key` = ?');
                    $statement->execute([$settingKey]);
                    if ($statement->fetchColumn() !== false) {
                        $update = $pdo->prepare('UPDATE settings SET `value` = ?, updated_at = CURRENT_TIMESTAMP WHERE `key` = ?');
                        $update->execute([$settingValue, $settingKey]);
                    } else {
                        $insert = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?)');
                        $insert->execute([$settingKey, $settingValue]);
                    }
                }
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        respond([
            'ok' => true,
            'mode' => $mode,
            'contacts' => count($backupContacts),
            'accounts' => count($backupAccounts),
            'journalEntries' => count($backupJournal),
        ]);
    }

    if ($action === 'stats' && $method === 'GET') {
        $counts = [];
        foreach ([
            'contacts' => 'SELECT COUNT(*) FROM contacts',
            'favorites' => 'SELECT COUNT(*) FROM contacts WHERE is_favorite = 1',
            'accounts' => 'SELECT COUNT(*) FROM accounts',
            'bankAccounts' => "SELECT COUNT(*) FROM accounts WHERE type = 'Bank'",
            'journal' => 'SELECT COUNT(*) FROM journal_entries',
            'intimate' => 'SELECT COUNT(*) FROM journal_entries WHERE is_intimate = 1',
        ] as $key => $sql) {
            $counts[$key] = (int)$pdo->query($sql)->fetchColumn();
        }
        respond($counts);
    }

    if ($action === 'contacts' && $method === 'GET') {
        $query = trim((string)($_GET['q'] ?? ''));
        if ($query !== '') {
            $like = '%' . $query . '%';
            $statement = $pdo->prepare('SELECT c.*, (SELECT COUNT(*) FROM accounts a WHERE a.contact_id=c.id) AS accounts_count, (SELECT COUNT(*) FROM journal_entries j WHERE j.contact_id=c.id) AS journal_count, (SELECT COUNT(*) FROM journal_entries j WHERE j.contact_id=c.id AND j.is_intimate=1) AS intimate_count FROM contacts c WHERE c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.tags LIKE ? ORDER BY c.is_favorite DESC, c.first_name');
            $statement->execute([$like, $like, $like, $like, $like]);
        } else {
            $statement = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM accounts a WHERE a.contact_id=c.id) AS accounts_count, (SELECT COUNT(*) FROM journal_entries j WHERE j.contact_id=c.id) AS journal_count, (SELECT COUNT(*) FROM journal_entries j WHERE j.contact_id=c.id AND j.is_intimate=1) AS intimate_count FROM contacts c ORDER BY c.is_favorite DESC, c.first_name');
        }
        respond(array_map('camel_row', $statement->fetchAll(PDO::FETCH_ASSOC)));
    }
    if ($action === 'contacts' && $method === 'POST') {
        $firstName = string_value($data, 'firstName');
        if ($firstName === '') {
            respond(['error' => 'First name is required.'], 400);
        }
        $data['firstName'] = $firstName;
        $data['isFavorite'] = bool_value($data, 'isFavorite');
        respond(save_record('contacts', $data, $contactMap), 201);
    }
    if ($action === 'contacts' && $id && $method === 'GET') {
        $statement = $pdo->prepare('SELECT * FROM contacts WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        respond($row ? camel_row($row) : ['error' => 'Contact not found.'], $row ? 200 : 404);
    }
    if ($action === 'contacts' && $id && $method === 'PUT') {
        if (string_value($data, 'firstName') === '') {
            respond(['error' => 'First name is required.'], 400);
        }
        $data['isFavorite'] = bool_value($data, 'isFavorite');
        respond(save_record('contacts', $data, $contactMap, $id));
    }

    if ($action === 'accounts' && $method === 'GET') {
        $contactId = filter_var($_GET['contactId'] ?? null, FILTER_VALIDATE_INT);
        if (!$contactId) {
            respond([]);
        }
        $statement = $pdo->prepare('SELECT * FROM accounts WHERE contact_id = ? ORDER BY is_primary DESC, label');
        $statement->execute([$contactId]);
        respond(array_map('camel_row', $statement->fetchAll(PDO::FETCH_ASSOC)));
    }
    if ($action === 'accounts' && $method === 'POST') {
        $contactId = filter_var($data['contactId'] ?? null, FILTER_VALIDATE_INT);
        $label = string_value($data, 'label');
        if (!$contactId || $label === '') {
            respond(['error' => 'A contact and account label are required.'], 400);
        }
        $data['contactId'] = $contactId;
        $data['label'] = $label;
        $data['isPrimary'] = bool_value($data, 'isPrimary');
        if (($data['type'] ?? '') !== 'Bank') {
            foreach (['bankName', 'routingNumber', 'accountNumber', 'accountType'] as $field) {
                $data[$field] = '';
            }
        }
        respond(save_record('accounts', $data, $accountMap), 201);
    }
    if ($action === 'accounts' && $id && $method === 'PUT') {
        if (string_value($data, 'label') === '') {
            respond(['error' => 'Account label is required.'], 400);
        }
        $data['isPrimary'] = bool_value($data, 'isPrimary');
        if (($data['type'] ?? '') !== 'Bank') {
            foreach (['bankName', 'routingNumber', 'accountNumber', 'accountType'] as $field) {
                $data[$field] = '';
            }
        }
        respond(save_record('accounts', $data, $accountMap, $id));
    }

    if ($action === 'journal' && $method === 'GET') {
        $contactId = filter_var($_GET['contactId'] ?? null, FILTER_VALIDATE_INT);
        if (!$contactId) {
            respond([]);
        }
        $statement = $pdo->prepare('SELECT * FROM journal_entries WHERE contact_id = ? ORDER BY entry_date DESC, id DESC');
        $statement->execute([$contactId]);
        respond(array_map('camel_row', $statement->fetchAll(PDO::FETCH_ASSOC)));
    }
    if ($action === 'journal' && $method === 'POST') {
        $contactId = filter_var($data['contactId'] ?? null, FILTER_VALIDATE_INT);
        $title = string_value($data, 'title');
        $entryDate = string_value($data, 'entryDate');
        if (!$contactId || $title === '' || $entryDate === '') {
            respond(['error' => 'A contact, title, and date are required.'], 400);
        }
        $data['contactId'] = $contactId;
        $data['title'] = $title;
        $data['entryDate'] = $entryDate;
        $data['isIntimate'] = bool_value($data, 'isIntimate');
        $data['consentConfirmed'] = bool_value($data, 'consentConfirmed');
        $data['stiTestedRecent'] = bool_value($data, 'stiTestedRecent');
        $data['isPrivate'] = bool_value($data, 'isPrivate', true);
        respond(save_record('journal_entries', $data, $journalMap), 201);
    }
    if ($action === 'journal' && $id && $method === 'PUT') {
        if (string_value($data, 'title') === '' || string_value($data, 'entryDate') === '') {
            respond(['error' => 'Entry title and date are required.'], 400);
        }
        foreach (['isIntimate', 'consentConfirmed', 'stiTestedRecent', 'isPrivate'] as $field) {
            $data[$field] = bool_value($data, $field, $field === 'isPrivate');
        }
        respond(save_record('journal_entries', $data, $journalMap, $id));
    }

    if ($id && $method === 'DELETE' && in_array($action, ['contacts', 'accounts', 'journal'], true)) {
        $table = ['contacts' => 'contacts', 'accounts' => 'accounts', 'journal' => 'journal_entries'][$action];
        $statement = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
        $statement->execute([$id]);
        respond(['ok' => true]);
    }

    if ($action === 'export' && $method === 'GET') {
        $type = (string)($_GET['type'] ?? 'json');
        if ($type === 'json') {
            $export = [];
            foreach (['settings', 'contacts', 'accounts', 'journal_entries'] as $table) {
                $export[$table] = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            }
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename=phonebook-backup.json');
            echo json_encode(['exportedAt' => date(DATE_ATOM), ...$export], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $filename = $type === 'mysql' ? 'phonebook-mysql-backup.sql' : 'phonebook-sqlite.sql';
        $path = __DIR__ . '/database/' . ($type === 'mysql' ? 'mysql.sql' : 'sqlite.sql');
        if (!is_file($path)) {
            respond(['error' => 'Requested export file is unavailable.'], 404);
        }
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        readfile($path);
        exit;
    }

    respond(['error' => 'Unknown API action or method.'], 404);
} catch (PDOException $error) {
    error_log('Phone Book API database error: ' . $error->getMessage());
    respond(['error' => 'Database request failed. Check the PHP error log for details.'], 500);
} catch (InvalidArgumentException $error) {
    respond(['error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('Phone Book API request error: ' . $error->getMessage());
    respond(['error' => 'The request could not be completed. Check the PHP error log for details.'], 500);
}
