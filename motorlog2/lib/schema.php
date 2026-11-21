<?php
declare(strict_types=1);

const SCHEMA_VERSION = 3;

function tables(PDO $db): array {
    return $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
        ? $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN)
        : $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
}

function sql_columns(PDO $db, string $table): array {
    if (!in_array($table, ['trips', 'expenses'], true)) throw new LogicException('Migration table not allowed');
    return $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
        ? array_column($db->query("PRAGMA table_info($table)")->fetchAll(), 'name')
        : array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(), 'Field');
}

function schema_version(PDO $db): int {
    if (!in_array('schema_migrations', tables($db), true)) return 0;
    return (int)$db->query('SELECT MAX(version) FROM schema_migrations')->fetchColumn();
}

function migrate(PDO $db, int $target = SCHEMA_VERSION): void {
    $sqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $opts = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $create = static function (string $name, string $body) use ($db, $opts): void {
        $db->exec("CREATE TABLE IF NOT EXISTS $name ($body)$opts");
    };
    $create('schema_migrations', 'version INTEGER PRIMARY KEY, applied_at VARCHAR(30) NOT NULL');
    $current = schema_version($db);
    if ($current > SCHEMA_VERSION) throw new HttpFault(409, 'This database was upgraded by a newer Motorlog version.', 'schema_mismatch');
    for ($v = $current + 1; $v <= $target; $v++) {
        if ($sqlite) $db->beginTransaction();
        try {
            if ($v === 1) {
                $create('app_settings', "id INTEGER PRIMARY KEY, site_name VARCHAR(100) NOT NULL, currency VARCHAR(3) NOT NULL, unit VARCHAR(2) NOT NULL, github_repo VARCHAR(200) NOT NULL DEFAULT '', installed_at VARCHAR(30) NOT NULL");
                $create('users', "id $id, username VARCHAR(100) NOT NULL UNIQUE, display_name VARCHAR(120) NOT NULL, password_hash VARCHAR(255) NOT NULL, created_at VARCHAR(30) NOT NULL");
                $create('vehicles', "id $id, kind VARCHAR(15) NOT NULL, make VARCHAR(150) NOT NULL, model VARCHAR(150) NOT NULL, model_year INTEGER NULL, vin VARCHAR(70) NOT NULL, plate VARCHAR(50) NOT NULL, color VARCHAR(70) NOT NULL, notes TEXT NOT NULL, initial_odometer DECIMAL(13,3) NOT NULL, odometer_peak DECIMAL(13,3) NOT NULL, cover_id INTEGER NULL, created_at VARCHAR(30) NOT NULL");
                $create('engines', "id $id, vehicle_id INTEGER NOT NULL, installed_date VARCHAR(10) NULL, at_odometer DECIMAL(13,3) NOT NULL, at_engine DECIMAL(13,3) NOT NULL, serial_number VARCHAR(120) NOT NULL, reason VARCHAR(250) NOT NULL, notes TEXT NOT NULL, retired_date VARCHAR(10) NULL, retired_odometer DECIMAL(13,3) NULL, FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)");
                $create('trips', "id $id, vehicle_id INTEGER NOT NULL, trip_date VARCHAR(10) NOT NULL, start_odometer DECIMAL(13,3) NOT NULL, end_odometer DECIMAL(13,3) NOT NULL, purpose VARCHAR(200) NOT NULL, notes TEXT NOT NULL, created_at VARCHAR(30) NOT NULL, FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)");
                $create('expenses', "id $id, vehicle_id INTEGER NOT NULL, expense_date VARCHAR(10) NOT NULL, category VARCHAR(25) NOT NULL, vendor VARCHAR(180) NOT NULL, description TEXT NOT NULL, amount_minor BIGINT NOT NULL, created_at VARCHAR(30) NOT NULL, FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)");
            }
            if ($v === 2) {
                $create('drivers', "id $id, name VARCHAR(150) NOT NULL, phone VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, license_number VARCHAR(120) NOT NULL, jurisdiction VARCHAR(120) NOT NULL, expiration_date VARCHAR(10) NULL, emergency_contact VARCHAR(250) NOT NULL, notes TEXT NOT NULL, active INTEGER NOT NULL, created_at VARCHAR(30) NOT NULL");
                $create('driver_records', "id $id, driver_id INTEGER NOT NULL, record_date VARCHAR(10) NOT NULL, kind VARCHAR(30) NOT NULL, title VARCHAR(180) NOT NULL, notes TEXT NOT NULL, created_at VARCHAR(30) NOT NULL, FOREIGN KEY (driver_id) REFERENCES drivers(id)");
                $create('mechanics', "id $id, name VARCHAR(150) NOT NULL, phone VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, address TEXT NOT NULL, notes TEXT NOT NULL, created_at VARCHAR(30) NOT NULL");
                $create('invoices', "id $id, vehicle_id INTEGER NOT NULL, mechanic_id INTEGER NOT NULL, expense_id INTEGER NOT NULL UNIQUE, invoice_number VARCHAR(100) NOT NULL, invoice_date VARCHAR(10) NOT NULL, due_date VARCHAR(10) NULL, amount_minor BIGINT NOT NULL, description TEXT NOT NULL, created_at VARCHAR(30) NOT NULL, UNIQUE (mechanic_id, invoice_number), FOREIGN KEY (vehicle_id) REFERENCES vehicles(id), FOREIGN KEY (mechanic_id) REFERENCES mechanics(id), FOREIGN KEY (expense_id) REFERENCES expenses(id)");
                $create('payments', "id $id, invoice_id INTEGER NOT NULL, payment_date VARCHAR(10) NOT NULL, amount_minor BIGINT NOT NULL, method VARCHAR(100) NOT NULL, notes TEXT NOT NULL, created_at VARCHAR(30) NOT NULL, FOREIGN KEY (invoice_id) REFERENCES invoices(id)");
                $create('api_tokens', "id $id, user_id INTEGER NOT NULL, label VARCHAR(100) NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE, created_at VARCHAR(30) NOT NULL, expires_at VARCHAR(30) NOT NULL, revoked_at VARCHAR(30) NULL, FOREIGN KEY (user_id) REFERENCES users(id)");
                $create('idempotency', 'user_id INTEGER NOT NULL, request_key VARCHAR(100) NOT NULL, request_hash CHAR(64) NOT NULL, response_json TEXT NOT NULL, created_at VARCHAR(30) NOT NULL, PRIMARY KEY (user_id, request_key), FOREIGN KEY (user_id) REFERENCES users(id)');
                $create('login_limits', 'subject CHAR(64) PRIMARY KEY, attempts INTEGER NOT NULL, until_epoch INTEGER NOT NULL');
                if (!in_array('driver_id', sql_columns($db, 'trips'), true)) {
                    $db->exec($sqlite ? 'ALTER TABLE trips ADD COLUMN driver_id INTEGER NULL REFERENCES drivers(id)' : 'ALTER TABLE trips ADD COLUMN driver_id INTEGER NULL, ADD CONSTRAINT trips_driver FOREIGN KEY (driver_id) REFERENCES drivers(id)');
                }
                $existing = sql_columns($db, 'expenses');
                foreach (['odometer'=>'DECIMAL(13,3)', 'gallons'=>'DECIMAL(12,3)', 'price_per_gallon'=>'DECIMAL(13,4)', 'full_tank'=>'INTEGER'] as $name=>$type) {
                    if (!in_array($name, $existing, true)) $db->exec("ALTER TABLE expenses ADD COLUMN $name $type NULL");
                }
            }
            if ($v === 3) {
                $db->exec('UPDATE vehicles SET odometer_peak=(SELECT MAX(t.end_odometer) FROM trips t WHERE t.vehicle_id=vehicles.id AND t.end_odometer>=t.start_odometer AND t.start_odometer>=0) WHERE odometer_peak < (SELECT MAX(t.end_odometer) FROM trips t WHERE t.vehicle_id=vehicles.id AND t.end_odometer>=t.start_odometer AND t.start_odometer>=0)');
                $create('documents', "id $id, doc_type VARCHAR(15) NOT NULL, reference VARCHAR(45) NOT NULL UNIQUE, parent_id INTEGER NULL, status VARCHAR(14) NOT NULL, vehicle_id INTEGER NULL, driver_id INTEGER NULL, document_day VARCHAR(10) NULL, week_start VARCHAR(10) NULL, due_next VARCHAR(10) NULL, issue_count INTEGER NOT NULL, result_count INTEGER NOT NULL, payload MEDIUMTEXT NOT NULL, revision INTEGER NOT NULL, created_by INTEGER NOT NULL, created_at VARCHAR(30) NOT NULL, updated_at VARCHAR(30) NOT NULL, completed_at VARCHAR(30) NULL, FOREIGN KEY (parent_id) REFERENCES documents(id), FOREIGN KEY (vehicle_id) REFERENCES vehicles(id), FOREIGN KEY (driver_id) REFERENCES drivers(id), FOREIGN KEY (created_by) REFERENCES users(id)");
                $create('document_audit', "id $id, document_id INTEGER NOT NULL, action VARCHAR(30) NOT NULL, actor_id INTEGER NOT NULL, logged_at VARCHAR(30) NOT NULL, snapshot MEDIUMTEXT NOT NULL, FOREIGN KEY (document_id) REFERENCES documents(id), FOREIGN KEY (actor_id) REFERENCES users(id)");
                $create('inspection_issues', "id $id, document_id INTEGER NOT NULL, item_key VARCHAR(40) NOT NULL, status VARCHAR(20) NOT NULL, action_taken TEXT NOT NULL, responsible VARCHAR(150) NOT NULL, resolved_date VARCHAR(10) NULL, notes TEXT NOT NULL, updated_at VARCHAR(30) NOT NULL, UNIQUE (document_id, item_key), FOREIGN KEY (document_id) REFERENCES documents(id)");
                $create('photos', "id $id, owner_kind VARCHAR(15) NOT NULL, owner_id INTEGER NOT NULL, item_key VARCHAR(40) NOT NULL, storage_name VARCHAR(100) NOT NULL, mime VARCHAR(30) NOT NULL, caption VARCHAR(220) NOT NULL, view_label VARCHAR(30) NOT NULL, created_at VARCHAR(30) NOT NULL");
                $create('sale_templates', "id $id, name VARCHAR(150) NOT NULL, jurisdiction VARCHAR(150) NOT NULL, terms TEXT NOT NULL, witness INTEGER NOT NULL, notary INTEGER NOT NULL, created_at VARCHAR(30) NOT NULL");
            }
            $stmt = $db->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (?, ?)');
            $stmt->execute([$v, gmdate('Y-m-d\TH:i:s\Z')]);
            if ($sqlite) $db->commit();
        } catch (Throwable $e) {
            if ($sqlite && $db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }
}

function known_install_tables(): array {
    return ['schema_migrations','app_settings','users','vehicles','engines','trips','expenses','drivers','driver_records','mechanics','invoices','payments','api_tokens','idempotency','login_limits','documents','document_audit','inspection_issues','photos','sale_templates'];
}
