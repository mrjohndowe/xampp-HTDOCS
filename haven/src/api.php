<?php
declare(strict_types=1);

function haven_owner_row(PDO $pdo, string $table, string $id, string $owner): ?array {
    $st = $pdo->prepare("SELECT * FROM {$table} WHERE id = ? AND owner_id = ?");
    $st->execute([$id, $owner]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function haven_handle_api(): void {
    $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $action = $input['action'] ?? ($_GET['action'] ?? '');

    if ($action === 'status') {
        haven_json(['ok' => true, 'installed' => haven_installed(), 'env' => haven_env_report(), 'appName' => haven_config()['app_name'] ?? 'Haven']);
    }

    if ($action === 'install') {
        if (haven_installed() && empty($input['force'])) {
            haven_json(['ok' => false, 'error' => 'Installation is locked.'], 403);
        }
        $step = $input['step'] ?? '';
        if ($step === 'write-config') {
            $cfg = haven_config();
            $cfg['driver'] = $input['driver'] === 'mysql' ? 'mysql' : 'sqlite';
            $cfg['app_name'] = substr((string)($input['appName'] ?? 'Haven'), 0, 80);
            $cfg['timezone'] = $input['timezone'] ?? 'UTC';
            $cfg['date_format'] = $input['dateFormat'] ?? 'YYYY-MM-DD';
            $cfg['registration_enabled'] = !empty($input['registrationEnabled']);
            if ($cfg['driver'] === 'mysql') {
                $cfg['mysql'] = [
                    'host' => $input['mysqlHost'] ?? '127.0.0.1',
                    'database' => $input['mysqlDatabase'] ?? 'haven',
                    'user' => $input['mysqlUser'] ?? 'root',
                    'password' => $input['mysqlPassword'] ?? '',
                    'charset' => 'utf8mb4',
                ];
            }
            haven_write_config($cfg);
            haven_schema();
            haven_json(['ok' => true]);
        }
        if ($step === 'secret') {
            $hex = haven_generate_secret();
            haven_json(['ok' => true, 'preview' => substr($hex, 0, 8) . '…' . substr($hex, -8)]);
        }
        if ($step === 'admin') {
            haven_schema();
            $created = haven_create_user($input, 'admin');
            $pdo = haven_pdo();
            $params = [
                haven_config()['app_name'] ?? 'Haven',
                haven_config()['timezone'] ?? 'UTC',
                haven_config()['date_format'] ?? 'YYYY-MM-DD',
                !empty(haven_config()['registration_enabled']) ? 1 : 0,
                haven_now(),
            ];
            $exists = $pdo->query("SELECT id FROM install_state WHERE id='default'")->fetch();
            if ($exists) {
                $pdo->prepare("UPDATE install_state SET installed=1, app_name=?, timezone=?, date_format=?, registration_enabled=?, installed_at=? WHERE id='default'")->execute($params);
            } else {
                $pdo->prepare("INSERT INTO install_state (id, installed, app_name, timezone, date_format, registration_enabled, installed_at) VALUES ('default',1,?,?,?,?,?)")->execute($params);
            }
            haven_lock_install();
            unset($created['dek']);
            haven_json(['ok' => true, 'user' => $created]);
        }
        haven_json(['ok' => false, 'error' => 'Unknown install step'], 400);
    }

    if ($action === 'login') {
        $pdo = haven_pdo();
        $st = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([strtolower(trim((string)($input['email'] ?? '')))]);
        $user = $st->fetch(PDO::FETCH_ASSOC);
        if (!$user || !(int)$user['is_active'] || !password_verify((string)($input['password'] ?? ''), $user['password_hash'])) {
            haven_json(['ok' => false, 'error' => 'Invalid email or password.'], 401);
        }
        $kek = hash_pbkdf2('sha256', (string)$input['password'], base64_decode($user['kek_salt']), 120000, 32, true);
        $dek = base64_decode(haven_decrypt($user['wrapped_dek'], $kek), true);
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $exp = gmdate('c', time() + 12 * 3600);
        $pdo->prepare('INSERT INTO sessions (id,user_id,token_hash,dek_enc,expires_at) VALUES (?,?,?,?,?)')
            ->execute([haven_id(), $user['id'], hash('sha256', $token), haven_encrypt(base64_encode($dek), haven_app_secret()), $exp]);
        setcookie('haven_sid', $token, ['expires' => time() + 12 * 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        haven_json(['ok' => true, 'user' => ['id' => $user['id'], 'email' => $user['email'], 'displayName' => $user['display_name'], 'role' => $user['role'], 'theme' => $user['theme']]]);
    }

    if ($action === 'logout') {
        setcookie('haven_sid', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true]);
        haven_json(['ok' => true]);
    }

    $auth = haven_require();
    $user = $auth['user'];
    $dek = $auth['dek'];
    $owner = $user['user_id'] ?? $user['id'];
    if (isset($user['user_id'])) $owner = $user['user_id'];
    $pdo = haven_pdo();

    if ($action === 'me') {
        haven_json(['ok' => true, 'user' => [
            'id' => $owner,
            'email' => $user['email'],
            'displayName' => $user['display_name'],
            'role' => $user['role'],
            'theme' => $user['theme'],
        ]]);
    }

    if ($action === 'reauth') {
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id=?');
        $st->execute([$owner]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify((string)($input['password'] ?? ''), $row['password_hash'])) {
            haven_json(['ok' => false, 'error' => 'Password verification failed.'], 401);
        }
        $pdo->prepare('UPDATE sessions SET reauth_until=? WHERE token_hash=?')->execute([gmdate('c', time() + 300), hash('sha256', $_COOKIE['haven_sid'] ?? '')]);
        haven_json(['ok' => true]);
    }

    $resource = $input['resource'] ?? '';
    $op = $input['op'] ?? 'list';
    $id = $input['id'] ?? '';
    $data = $input['data'] ?? [];

    $enc = fn($v) => ($v === '' || $v === null) ? null : haven_encrypt((string)$v, $dek);
    $dec = function ($v) use ($dek) {
        if (!$v) return '';
        try { return haven_decrypt($v, $dek); } catch (Throwable) { return ''; }
    };

    if ($resource === 'contacts') {
        if ($op === 'list') {
            $st = $pdo->prepare("SELECT * FROM contacts WHERE owner_id=? AND deleted_at IS NULL ORDER BY updated_at DESC");
            $st->execute([$owner]);
            $items = array_map(function ($row) use ($dec) {
                $name = trim($row['display_name']) ?: trim($row['first_name'] . ' ' . $row['last_name']) ?: 'Untitled';
                return $row + ['name' => $name, 'age' => haven_age($row['date_of_birth']), 'tags' => json_decode($row['tags'] ?: '[]', true), 'notes' => $dec($row['notes_enc'])];
            }, $st->fetchAll(PDO::FETCH_ASSOC));
            haven_json(['ok' => true, 'items' => $items]);
        }
        if ($op === 'create') {
            $id = haven_id();
            $pdo->prepare('INSERT INTO contacts (id,owner_id,display_name,created_at,updated_at) VALUES (?,?,?,?,?)')
                ->execute([$id, $owner, 'New contact', haven_now(), haven_now()]);
            haven_json(['ok' => true, 'id' => $id]);
        }
        if ($op === 'update') {
            $row = haven_owner_row($pdo, 'contacts', $id, $owner);
            if (!$row) haven_json(['ok' => false, 'error' => 'Not found'], 404);
            $pdo->prepare('UPDATE contacts SET first_name=?, middle_name=?, last_name=?, display_name=?, date_of_birth=?, email=?, phone=?, phone_secondary=?, street=?, city=?, state=?, zip=?, relationship=?, company=?, job_title=?, notes_enc=?, tags=?, updated_at=? WHERE id=? AND owner_id=?')
                ->execute([
                    $data['first_name'] ?? $row['first_name'],
                    $data['middle_name'] ?? $row['middle_name'],
                    $data['last_name'] ?? $row['last_name'],
                    $data['display_name'] ?? $row['display_name'],
                    $data['date_of_birth'] ?? $row['date_of_birth'],
                    $data['email'] ?? $row['email'],
                    $data['phone'] ?? $row['phone'],
                    $data['phone_secondary'] ?? $row['phone_secondary'],
                    $data['street'] ?? $row['street'],
                    $data['city'] ?? $row['city'],
                    $data['state'] ?? $row['state'],
                    $data['zip'] ?? $row['zip'],
                    $data['relationship'] ?? $row['relationship'],
                    $data['company'] ?? $row['company'],
                    $data['job_title'] ?? $row['job_title'],
                    array_key_exists('notes', $data) ? $enc($data['notes']) : $row['notes_enc'],
                    json_encode($data['tags'] ?? json_decode($row['tags'] ?: '[]', true)),
                    haven_now(), $id, $owner,
                ]);
            haven_json(['ok' => true]);
        }
        if ($op === 'delete') {
            $pdo->prepare('UPDATE contacts SET deleted_at=?, updated_at=? WHERE id=? AND owner_id=?')->execute([haven_now(), haven_now(), $id, $owner]);
            haven_json(['ok' => true]);
        }
    }

    if ($resource === 'accounts') {
        if ($op === 'list') {
            $st = $pdo->prepare('SELECT * FROM accounts WHERE owner_id=? AND deleted_at IS NULL ORDER BY updated_at DESC');
            $st->execute([$owner]);
            $items = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $items[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'username' => $row['username'],
                    'url' => $row['url'],
                    'hasPassword' => (bool)$row['password_enc'],
                    'passwordMasked' => $row['password_enc'] ? '••••••••' : '',
                    'show_banking_fields' => (int)$row['show_banking_fields'],
                    'bank_name' => $row['bank_name'],
                    'hasAccountNumber' => (bool)$row['account_number_enc'],
                    'hasRoutingNumber' => (bool)$row['routing_number_enc'],
                ];
            }
            haven_json(['ok' => true, 'items' => $items]);
        }
        if ($op === 'create') {
            $id = haven_id();
            $pdo->prepare('INSERT INTO accounts (id,owner_id,name,created_at,updated_at) VALUES (?,?,?,?,?)')->execute([$id, $owner, 'New account', haven_now(), haven_now()]);
            haven_json(['ok' => true, 'id' => $id]);
        }
        if ($op === 'get') {
            $row = haven_owner_row($pdo, 'accounts', $id, $owner);
            if (!$row) haven_json(['ok' => false, 'error' => 'Not found'], 404);
            $secrets = !empty($input['reveal']) && $auth['reauth'];
            $row['password'] = $secrets ? $dec($row['password_enc']) : null;
            $row['routing_number'] = $secrets ? $dec($row['routing_number_enc']) : null;
            $row['account_number'] = $secrets ? $dec($row['account_number_enc']) : null;
            $row['notes'] = $dec($row['notes_enc']);
            $row['needsReauth'] = !empty($input['reveal']) && !$auth['reauth'];
            $row['tags'] = json_decode($row['tags'] ?: '[]', true);
            haven_json(['ok' => true, 'item' => $row]);
        }
        if ($op === 'update') {
            $row = haven_owner_row($pdo, 'accounts', $id, $owner);
            if (!$row) haven_json(['ok' => false, 'error' => 'Not found'], 404);
            $pdo->prepare('UPDATE accounts SET name=?, type=?, url=?, username=?, login_email=?, recovery_email=?, password_enc=?, notes_enc=?, show_banking_fields=?, bank_name=?, account_holder=?, bank_account_type=?, routing_number_enc=?, account_number_enc=?, banking_notes_enc=?, recovery_codes_enc=?, updated_at=? WHERE id=? AND owner_id=?')
                ->execute([
                    $data['name'] ?? $row['name'],
                    $data['type'] ?? $row['type'],
                    $data['url'] ?? $row['url'],
                    $data['username'] ?? $row['username'],
                    $data['login_email'] ?? $row['login_email'],
                    $data['recovery_email'] ?? $row['recovery_email'],
                    array_key_exists('password', $data) ? $enc($data['password']) : $row['password_enc'],
                    array_key_exists('notes', $data) ? $enc($data['notes']) : $row['notes_enc'],
                    array_key_exists('show_banking_fields', $data) ? (!empty($data['show_banking_fields']) ? 1 : 0) : (int)$row['show_banking_fields'],
                    $data['bank_name'] ?? $row['bank_name'],
                    $data['account_holder'] ?? $row['account_holder'],
                    $data['bank_account_type'] ?? $row['bank_account_type'],
                    array_key_exists('routing_number', $data) ? $enc($data['routing_number']) : $row['routing_number_enc'],
                    array_key_exists('account_number', $data) ? $enc($data['account_number']) : $row['account_number_enc'],
                    array_key_exists('banking_notes', $data) ? $enc($data['banking_notes']) : $row['banking_notes_enc'],
                    array_key_exists('recovery_codes', $data) ? $enc($data['recovery_codes']) : $row['recovery_codes_enc'],
                    haven_now(), $id, $owner,
                ]);
            haven_json(['ok' => true]);
        }
        if ($op === 'delete') {
            $pdo->prepare('UPDATE accounts SET deleted_at=?, updated_at=? WHERE id=? AND owner_id=?')->execute([haven_now(), haven_now(), $id, $owner]);
            haven_json(['ok' => true]);
        }
    }

    if ($resource === 'journal') {
        if ($op === 'list') {
            $st = $pdo->prepare('SELECT * FROM journal_entries WHERE owner_id=? AND deleted_at IS NULL ORDER BY entry_at DESC');
            $st->execute([$owner]);
            $items = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $body = $dec($row['body_enc']);
                $items[] = $row + ['body' => $body, 'private_notes' => $dec($row['private_notes_enc']), 'tags' => json_decode($row['tags'] ?: '[]', true)];
            }
            haven_json(['ok' => true, 'items' => $items]);
        }
        if ($op === 'create') {
            $id = haven_id();
            $pdo->prepare('INSERT INTO journal_entries (id,owner_id,title,entry_at,created_at,updated_at) VALUES (?,?,?,?,?,?)')->execute([$id, $owner, 'New entry', haven_now(), haven_now(), haven_now()]);
            haven_json(['ok' => true, 'id' => $id]);
        }
        if ($op === 'update') {
            $row = haven_owner_row($pdo, 'journal_entries', $id, $owner);
            if (!$row) haven_json(['ok' => false, 'error' => 'Not found'], 404);
            $pdo->prepare('UPDATE journal_entries SET title=?, entry_at=?, category=?, contact_id=?, body_enc=?, private_notes_enc=?, mood=?, tags=?, updated_at=? WHERE id=? AND owner_id=?')
                ->execute([
                    $data['title'] ?? $row['title'],
                    $data['entry_at'] ?? $row['entry_at'],
                    $data['category'] ?? $row['category'],
                    $data['contact_id'] ?? $row['contact_id'],
                    array_key_exists('body', $data) ? $enc($data['body']) : $row['body_enc'],
                    array_key_exists('private_notes', $data) ? $enc($data['private_notes']) : $row['private_notes_enc'],
                    $data['mood'] ?? $row['mood'],
                    json_encode($data['tags'] ?? json_decode($row['tags'] ?: '[]', true)),
                    haven_now(), $id, $owner,
                ]);
            haven_json(['ok' => true]);
        }
        if ($op === 'delete') {
            $pdo->prepare('UPDATE journal_entries SET deleted_at=? WHERE id=? AND owner_id=?')->execute([haven_now(), $id, $owner]);
            haven_json(['ok' => true]);
        }
    }

    if ($resource === 'intimacy') {
        if ($op === 'list') {
            $st = $pdo->prepare('SELECT * FROM intimacy_events WHERE owner_id=? AND deleted_at IS NULL ORDER BY event_date DESC');
            $st->execute([$owner]);
            $items = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $items[] = $row + [
                    'description' => $dec($row['description_enc']),
                    'notes' => $dec($row['notes_enc']),
                    'location' => $dec($row['location_enc']),
                    'questionnaire' => json_decode($dec($row['questionnaire_enc']) ?: '{}', true),
                    'optional_details' => json_decode($dec($row['optional_details_enc']) ?: '{}', true),
                    'contact_ids' => json_decode($row['contact_ids'] ?: '[]', true),
                    'tags' => json_decode($row['tags'] ?: '[]', true),
                ];
            }
            haven_json(['ok' => true, 'items' => $items]);
        }
        if ($op === 'create') {
            $id = haven_id();
            $pdo->prepare('INSERT INTO intimacy_events (id,owner_id,event_date,created_at,updated_at) VALUES (?,?,?,?,?)')->execute([$id, $owner, gmdate('Y-m-d'), haven_now(), haven_now()]);
            haven_json(['ok' => true, 'id' => $id]);
        }
        if ($op === 'update') {
            $row = haven_owner_row($pdo, 'intimacy_events', $id, $owner);
            if (!$row) haven_json(['ok' => false, 'error' => 'Not found'], 404);
            $pdo->prepare('UPDATE intimacy_events SET event_date=?, start_time=?, end_time=?, duration_minutes=?, event_type=?, privacy_level=?, location_enc=?, description_enc=?, notes_enc=?, rating_overall=?, rating_enjoyment=?, rating_emotional=?, rating_communication=?, rating_comfort=?, rating_trust=?, rating_satisfaction=?, rating_after_feelings=?, questionnaire_enc=?, optional_details_enc=?, contact_ids=?, tags=?, updated_at=? WHERE id=? AND owner_id=?')
                ->execute([
                    $data['event_date'] ?? $row['event_date'],
                    $data['start_time'] ?? $row['start_time'],
                    $data['end_time'] ?? $row['end_time'],
                    array_key_exists('duration_minutes', $data) ? $data['duration_minutes'] : $row['duration_minutes'],
                    $data['event_type'] ?? $row['event_type'],
                    $data['privacy_level'] ?? $row['privacy_level'],
                    array_key_exists('location', $data) ? $enc($data['location']) : $row['location_enc'],
                    array_key_exists('description', $data) ? $enc($data['description']) : $row['description_enc'],
                    array_key_exists('notes', $data) ? $enc($data['notes']) : $row['notes_enc'],
                    $data['rating_overall'] ?? $row['rating_overall'],
                    $data['rating_enjoyment'] ?? $row['rating_enjoyment'],
                    $data['rating_emotional'] ?? $row['rating_emotional'],
                    $data['rating_communication'] ?? $row['rating_communication'],
                    $data['rating_comfort'] ?? $row['rating_comfort'],
                    $data['rating_trust'] ?? $row['rating_trust'],
                    $data['rating_satisfaction'] ?? $row['rating_satisfaction'],
                    $data['rating_after_feelings'] ?? $row['rating_after_feelings'],
                    array_key_exists('questionnaire', $data) ? $enc(json_encode($data['questionnaire'])) : $row['questionnaire_enc'],
                    array_key_exists('optional_details', $data) ? $enc(json_encode($data['optional_details'])) : $row['optional_details_enc'],
                    json_encode($data['contact_ids'] ?? json_decode($row['contact_ids'] ?: '[]', true)),
                    json_encode($data['tags'] ?? json_decode($row['tags'] ?: '[]', true)),
                    haven_now(), $id, $owner,
                ]);
            haven_json(['ok' => true]);
        }
        if ($op === 'delete') {
            $pdo->prepare('UPDATE intimacy_events SET deleted_at=? WHERE id=? AND owner_id=?')->execute([haven_now(), $id, $owner]);
            haven_json(['ok' => true]);
        }
    }

    if ($action === 'backup') {
        $out = ['kind' => 'haven-user-backup', 'version' => 1, 'exportedAt' => haven_now()];
        foreach (['contacts','accounts','journal_entries','intimacy_events','user_lists'] as $table) {
            $st = $pdo->prepare("SELECT * FROM {$table} WHERE owner_id=?");
            $st->execute([$owner]);
            $out[$table] = $st->fetchAll(PDO::FETCH_ASSOC);
        }
        haven_json($out);
    }

    if ($action === 'theme') {
        $theme = in_array($input['theme'] ?? '', ['light','dark','system'], true) ? $input['theme'] : 'system';
        $pdo->prepare('UPDATE users SET theme=? WHERE id=?')->execute([$theme, $owner]);
        haven_json(['ok' => true]);
    }

    haven_json(['ok' => false, 'error' => 'Unknown action'], 400);
}
