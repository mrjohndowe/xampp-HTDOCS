# PersonVault

PersonVault is a self-hosted PHP application for private contacts, third-party credentials, personal journal entries, and relationship events. It is designed for PHP 8.5 on Windows/XAMPP and uses SQLite by default, with PDO MySQL as an alternative. No Composer packages, Node.js, Docker, or external services are needed.

## Install on Windows with XAMPP

1. Install XAMPP with PHP 8.5, Apache, PDO SQLite, OpenSSL, and (if needed) PDO MySQL. This build was checked with PHP CLI 8.5.10 and those extensions; an XAMPP Apache installation has not been independently checked here.
2. Extract this folder somewhere outside a public web directory if possible. Configure an Apache virtual host so its `DocumentRoot` points to this project's `public` folder. Keep Apache's PHP selection per site if other sites use different PHP versions.
3. Allow Apache to read the project and write `var/install.lock`. The installer creates a private folder outside the configured document root for the database, configuration, and key. Ensure the Apache account can create and protect that folder. On Windows, set restrictive NTFS permissions on the private folder so only the site account and administrator can read it.
4. Open the local site's `install.php` from the same computer. First-run POST requests are accepted only from localhost. The wizard checks PHP/OpenSSL/PDO, lets you select SQLite or MySQL, connects to the selected database, creates the schema, creates the first administrator, and writes the install lock.
5. Sign in with the administrator credentials. Use **User management** to create or disable accounts. An administrator can manage sign-in accounts but cannot read other users' private records.

For a local development-only server, run `php -S 127.0.0.1:8085 -t public` from the project folder and open `http://127.0.0.1:8085/install.php`. Do not use PHP's built-in server as a public production server.

### Requirements checked by the installer

- PHP 8.5 or later.
- `openssl`, PDO, and PDO SQLite for the default option.
- PDO MySQL when selecting MySQL.
- A writable private storage path outside `DOCUMENT_ROOT`.
- An empty database. For MySQL, create the database first and use a database user allowed to create tables and indexes.

The wizard accepts an IANA time zone. The app uses plain text for journal entries with safe text rendering and formatting; it does not interpret user text as HTML.

## Storage and database

The installer places the SQLite database under the private storage folder's `data` directory. MySQL settings are stored in a private PHP configuration file. The encryption key is a separate 32-byte file under `keys`; encrypted database values are stored under `data`. `config.sample.php` contains placeholders only and is not used as live configuration.

The schema is initialized idempotently by `app/migrate.php` and records migration version 1. Tables include users and owner-scoped records. All database reads and writes use PDO prepared statements. Switching from SQLite to MySQL is not automatic; export/import between backends is not provided.

## Use and privacy

- Contacts never become logins or managed accounts automatically.
- Records autosave while editing without a page reload. A record is created when its first non-empty field is entered. Theme, date display, and the signed-in user's password can be changed in Settings.
- All record fields are encrypted together as JSON with AES-256-GCM before database storage. Record payload search and filtering are performed after decrypting only the signed-in user's rows. The record list processes at most 5,001 rows per module per request.
- Accounts can link to one of the signed-in user's contacts. Banking fields appear for banking, credit/debit, and investment account types. Passwords and financial identifiers are masked in lists and require the account's password to reveal. Revealed values clear after 30 seconds or when hidden. Clipboard access is user initiated.
- Journal text is plain text, not arbitrary HTML. The intimacy questionnaire allows omitted, unknown, and not-recorded consent answers; a missing answer never implies consent.
- Records deleted in the UI move to Trash and can be restored. Permanent purge is not included; protect storage capacity and backups accordingly.

## Backup and restore

In **Settings & backup**, download a per-user JSON export. It contains the user's already encrypted record payloads and no encryption key. Restore merges records for the signed-in user; duplicate IDs are skipped. The backup is portable only with the matching encryption key and application format.

Back up these items separately and securely:

1. The complete private storage folder, including the database or MySQL configuration and the key file.
2. A separate protected copy of the encryption key, stored away from the database backup. Keep the key backup access limited; loss of the key makes private record payloads unrecoverable.
3. For SQLite file-level backup, stop writes and copy `data/personvault.sqlite` together with the matching key and configuration. Resume the app after the copy completes. A SQLite file copy is not encrypted by this application.
4. For MySQL, use your MySQL administrator's `mysqldump`/restore workflow over a protected channel, and protect the resulting dump as confidential data. The application does not automate server-level MySQL backups.

The JSON export is encrypted at the field layer, but timestamps, account/module types, user IDs, user login email addresses, and opaque linked-contact IDs are database metadata. A database reader can see those metadata fields. The SQLite file, MySQL storage, configuration file, and backup file do not receive whole-file encryption from this app; use Windows BitLocker or an appropriately protected encrypted volume/backup target for storage-level encryption.

## Run the test suite

From the project folder:

```powershell
php -d xdebug.mode=off tests/run.php
```

The tests use a temporary directory and SQLite database. They cover migration initialization, authenticated encryption/decryption and tamper rejection, password hashing, and owner-scoped record queries. They do not certify a running XAMPP Apache instance or a live MySQL server.

## Upgrade and recovery

- Keep a backup of the private storage folder and key before replacing application source.
- Apply later migration scripts only after reading their release notes and backing up the database.
- The app has no self-service key rotation or cross-backend migration utility.
- The installation lock is `var/install.lock` in the project and points to the external private configuration folder. The project root is denied by its Apache `.htaccess`; serve only `public` as the site document root.
- Do not delete the lock or private configuration to reinstall. To recover an interrupted first run, inspect the server log and private directory. If no administrator was created, remove only the incomplete private directory and retry with an empty database. If the database has a user but the lock/config write failed, preserve all files and get a verified backup before repair; do not initialize the same database again.
- After setup, first-run URLs redirect to the app. Do not delete `public/install.php`; it refuses remote setup and checks the protected lock, so keeping the source archive complete does not reopen setup.

## Project layout

- `app/` - configuration loading, crypto, sessions, database, migrations.
- `public/` - web entry points, API, CSS, and JavaScript.
- `tests/` - reproducible PHP test runner.
- `SECURITY.md` - security model, limitations, and recovery guidance.
- `FEATURES.md` - requirement-by-requirement implementation status.

## README source note

The supplied challenge text ends mid-sentence in its backup section. This README covers the requirements present in the supplied text and records known boundaries instead of inferring the missing tail.
