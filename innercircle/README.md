# innercircle — PHP / XAMPP edition

A single-owner, self-hosted contact book, encrypted account vault, and adult-only private intimacy journal. This package contains a prebuilt interface and a real PHP backend. Node.js, Next.js, PostgreSQL, Composer, and a build step are **not required** on your XAMPP server.

## Requirements

- PHP 8.2 or later; compatible with PHP 8.5. Development smoke tests run with PHP 8.2; verify your particular PHP 8.5/XAMPP configuration before production use.
- Apache (XAMPP) with PHP enabled. Do not upload PHP sources to a static-file host.
- PHP extensions: `PDO`, `pdo_sqlite`, and `sodium`.
- Optional `pdo_mysql` for MySQL or MariaDB backup.
- A current browser with JavaScript enabled.
- A writable private directory **outside Apache's document root**.

## 1. Install on XAMPP

1. Extract the `innercircle` folder into `C:\xampp\htdocs\` (Windows) or your equivalent `htdocs` directory.
2. In XAMPP's `php.ini`, enable `extension=pdo_sqlite`, `extension=sodium`, and optionally `extension=pdo_mysql`. XAMPP often already enables PDO/MySQL. Restart Apache after changes.
3. The app automatically creates a directory beside `htdocs`, named `innercircle-private-<installation-hash>`. For example, `C:\xampp\innercircle-private-...`. The Apache service user must be allowed to create/write this directory.
4. Alternatively set the Apache environment variable `INNERCIRCLE_DATA_DIR` to an absolute, writable directory outside `htdocs`. Example virtual-host directive: `SetEnv INNERCIRCLE_DATA_DIR "C:/xampp/innercircle-private"`. Restart Apache.
5. Visit `http://localhost/innercircle/`. The installer opens automatically.
6. Answer the setup questions: your name, owner email, workspace name, a 12-character-or-longer password, journal preference and adult confirmation, optional sample contacts, and optional MySQL backup credentials.
7. After setup, a private `config.php` and `install.lock` are created. Both the installer page and setup API reject reinstallation. **Delete or rename the `install` folder for additional hardening.** Deleting the folder is safe after setup; deleting only the lock does not bypass the config guard.

The owner email is a local profile field, not a password-reset service. Keep your password in a trusted password manager. Do not expose an unfinished installer to the public internet.

## 2. Your contacts

- Create, edit, search, filter, favorite, and delete contacts.
- Store name, date of birth, email, phone, optional address, circle, and notes.
- Age is calculated from date of birth, rather than stored as a stale number.
- Birthdays are sorted by the next occurrence. Grid and list layouts are available.
- CSV export includes the currently filtered contacts, never account secrets or journal entries. Spreadsheet formula prefixes are escaped.
- Deleting a contact also deletes their associated accounts and journals after confirmation.

## 3. Account vault

Each account belongs to a contact and includes an account name, username, email, password, and notes. Bank accounts additionally require a 9-digit US routing number and 4–34 digit account number.

Account passwords, bank numbers, and account notes are encrypted with authenticated Sodium secretbox encryption. Reveal controls hide values again after 30 seconds. Only store credentials you have permission to hold. Clipboard contents are not automatically erased by browsers; clear your clipboard after copying secrets.

## 4. Private journal

Each entry belongs to a person. Choose a general reflection or an intimacy check-in. Intimacy requires a date of birth confirming the person was at least 18 on the entry date and confirmation that both people were adults. Questions cover consent, boundaries, communication, protection/sexual health, wellbeing, and aftercare. Notes and question answers are encrypted. Consent concerns may be recorded honestly; the form does not force an affirmative answer.

The journal can be disabled in Settings without deleting existing entries. This app is not a substitute for health advice or professional support.

## 5. SQLite and MySQL backup

SQLite is always the primary database. MySQL is an **on-demand backup**, not automatic failover or continuous replication.

To enable backup during installation:

1. Start MySQL in XAMPP or prepare a trusted MySQL/MariaDB server.
2. Create an empty database (for example `innercircle_backup`) and a dedicated database user.
3. Grant that user SELECT, INSERT, and CREATE on the backup database. Use a strong password; never expose MySQL directly to the internet.
4. Enable the MySQL option in the installer and enter host, port, existing database name, username, and password. Setup tests the connection.
5. In Settings → Your data, your home, click **Back up to MySQL**. The app writes an authenticated, encrypted snapshot to `innercircle_backups`. SQLite remains untouched if backup fails. Snapshots are retained until you remove them administratively.

**Export encrypted backup** downloads the same full, encrypted snapshot as JSON. It includes the owner profile/password hash and all records, but never the encryption key or active sessions.

Backups are useless without the original encryption key in private `config.php`. Copy that file to separate, secure offline storage. Never place it under `htdocs`, email it, or put it into source control. Preserve the private directory when moving or upgrading the app.

### Restore a JSON backup

1. Take the workspace offline while restoring.
2. Keep the original private `config.php` and its encryption key.
3. Set `INNERCIRCLE_DATA_DIR` in the terminal to the same private directory used by Apache. On Windows Command Prompt use `set INNERCIRCLE_DATA_DIR=C:\xampp\innercircle-private-...`; on PowerShell use `$env:INNERCIRCLE_DATA_DIR='C:\xampp\innercircle-private-...'`.
4. From the innercircle directory run `C:\xampp\php\php.exe restore.php path\to\backup.json --replace` (or `php restore.php backup.json --replace` on Linux/macOS).
5. To restore the latest MySQL snapshot, run `php restore.php --mysql --replace` instead.
6. The utility verifies decryption before writing, creates an encrypted safety backup in the private directory, and replaces the records inside a SQLite transaction. The install lock is retained.
7. All existing owner sessions are automatically invalidated during restoration. Unlock using the owner password from the restored snapshot.

Restore is CLI-only and denied by Apache. The live Next.js preview uses PostgreSQL and a separate encryption format; its backups cannot be loaded by this PHP utility. Contacts can be exported as CSV for external use, but there is no CSV import tool in this version.

## Security notes

- Use HTTPS for any access beyond your own local machine. PHP marks session cookies Secure automatically when Apache reports HTTPS. If using a reverse proxy, configure Apache's HTTPS indicator correctly.
- Owner sessions use HttpOnly/SameSite=Strict cookies, sliding server-side inactivity expiry, session ID regeneration, and a configurable browser auto-lock.
- Passwords use salted PHP password hashes over a fixed-length SHA-384 prehash, avoiding bcrypt's 72-byte truncation. Login attempts are throttled across sessions.
- Mutations require a custom request header and same-origin checks. All database queries use prepared statements.
- All assets, including fonts and demo portraits, are bundled locally. The app makes no analytics or third-party asset requests.
- Contact details, account labels/usernames/emails, and journal metadata are normal SQLite records. Sensitive account fields and journal content are encrypted. Exported snapshots encrypt the entire dataset.
- Anyone who controls the server and its encryption key can decrypt private data. This is server-side encryption at rest, not zero-knowledge encryption. Use disk encryption, restricted file permissions, and secure server backups.
- Store information about other people only with their permission.
- This is a personal single-owner app, not a multi-tenant service. Do not share an installation password.

## Troubleshooting

- **Blank screen:** confirm JavaScript is enabled and `assets/app.js` and `assets/app.css` are present. Check PHP's error log.
- **Missing SQLite/Sodium:** enable the extension in the PHP configuration actually used by Apache, then restart Apache.
- **Database is read-only:** the private directory and its SQLite database/WAL files must be writable by the Apache service user.
- **Installation is locked:** expected after setup. Use `index.php`. Never delete the private configuration to fix a sign-in problem.
- **MySQL connection failed:** verify `pdo_mysql`, server/port, database existence, permissions, and credentials. SQLite-only operation does not need MySQL.
- **Move to a new server or folder:** preserve private `config.php`, `install.lock`, and a consistent SQLite backup. Set `INNERCIRCLE_DATA_DIR` explicitly so the application uses the original private directory rather than creating a new hash-based location.

Demo identities are fictional. Portraits were sourced from Unsplash. Typography is DM Sans and DM Serif Display (SIL Open Font License).
