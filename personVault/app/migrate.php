<?php
declare(strict_types=1);

function app_migrate(PDO $pdo): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version INTEGER PRIMARY KEY, applied_at TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL DEFAULT "user", theme TEXT NOT NULL DEFAULT "system", date_format TEXT NOT NULL DEFAULT "Y-m-d", disabled INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS records (id TEXT PRIMARY KEY, owner_id INTEGER NOT NULL, module TEXT NOT NULL, contact_id TEXT NULL, payload TEXT NOT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL, deleted_at TEXT NULL, FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE CASCADE)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS records_owner_module ON records(owner_id, module, deleted_at, updated_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS records_owner_contact ON records(owner_id, contact_id)');
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version INT PRIMARY KEY, applied_at VARCHAR(32) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, email VARCHAR(254) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, role VARCHAR(16) NOT NULL DEFAULT "user", theme VARCHAR(16) NOT NULL DEFAULT "system", date_format VARCHAR(16) NOT NULL DEFAULT "Y-m-d", disabled TINYINT NOT NULL DEFAULT 0, created_at VARCHAR(32) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $pdo->exec('CREATE TABLE IF NOT EXISTS records (id CHAR(32) PRIMARY KEY, owner_id BIGINT UNSIGNED NOT NULL, module VARCHAR(24) NOT NULL, contact_id CHAR(32) NULL, payload LONGTEXT NOT NULL, created_at VARCHAR(32) NOT NULL, updated_at VARCHAR(32) NOT NULL, deleted_at VARCHAR(32) NULL, INDEX records_owner_module (owner_id, module, deleted_at, updated_at), INDEX records_owner_contact (owner_id, contact_id), CONSTRAINT records_owner_fk FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    $check = $pdo->query('SELECT version FROM schema_migrations WHERE version = 1');
    if (!$check->fetchColumn()) {
        $stmt = $pdo->prepare('INSERT INTO schema_migrations(version, applied_at) VALUES(1, ?)');
        $stmt->execute([app_now()]);
    }
}
