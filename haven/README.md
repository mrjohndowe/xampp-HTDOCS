# Haven PHP portable edition

This directory is a complete PHP 8 application for **XAMPP on Windows** (or any PHP 8.1+ with PDO, OpenSSL, and session support). PHP 8.5 is the target; the code uses only core extensions.

It does **not** require Docker, Node.js, or a cloud database.

## Features

Same product as the Next.js app: multi-user contacts, separate password manager, personal journal, intimacy journal, installer lock, AES-256-GCM field encryption, light/dark theme, autosave.

## Install on XAMPP

1. Extract `haven-xampp.zip` into `C:\xampp\htdocs`; the app will be at `C:\xampp\htdocs\haven`.
2. Confirm PHP extensions: `pdo_sqlite` (required for the default), `openssl`, and `pdo_mysql` only if you choose MySQL.
3. Make `storage/` writable (XAMPP’s Apache user must be able to create files there).
4. Visit `http://localhost/haven/public/` (or configure the document root to `public/`).
5. Complete the installation wizard.
6. Keep `storage/` outside the public document root. The included `.htaccess` denies web access if storage is accidentally placed under `public`.

Project maintainers can rebuild the downloadable archive from the project root with `npm run package:xampp`. It writes `public/downloads/haven-xampp.zip`.

### SQLite (default)

The installer creates `storage/haven.sqlite`. No extra database server is required.

**Limitation:** SQLite files are not SQLCipher-encrypted in this distribution. Field-level AES-256-GCM still applies. Anyone who can open the file will see metadata and ciphertext, not plaintext secrets.

### MySQL alternative

1. Create an empty database in phpMyAdmin.
2. In the installer, choose MySQL and enter host, database, user, and password.
3. PDO uses prepared statements only.

Switching engines is **not** a one-click migration. Export JSON from Backup while you can still log in, install the other engine, then restore into the same user vault.

## Installation lock

Successful setup writes `storage/installed.lock` **and** an `install_state` row. The lock file is not in the web root. Deleting a public HTML file will not unlock the installer. An administrator can unlock setup from Settings for 30 minutes.

## Tests

From a shell with PHP:

```bash
php tests/test.php
```

## Keys

`storage/app.secret` is generated during install. Back it up separately from `haven.sqlite` / MySQL dumps. If it is lost, session wrapping fails; user password-wrapped DEKs may still unlock vaults. If a **user password** is lost, that user’s encrypted fields cannot be recovered.
