# Security and privacy

## Encryption model

Each record payload is JSON encoded and encrypted with PHP OpenSSL AES-256-GCM. Every write generates a fresh 12-byte nonce with `random_bytes`; OpenSSL produces a 16-byte authentication tag. The algorithm name, version marker, IV, tag, ciphertext, and fixed context string are used for decryption. Authentication failure rejects the record. The whole user record payload is encrypted, which keeps names, contact details, notes, journal text, tags, questionnaires, account credentials, and banking identifiers out of human-readable database columns.

The installer generates a random 32-byte key. It stores the key in a file under the private storage folder's `keys` directory; the database is kept in a separate `data` directory. The config is outside the web document root. Restrict access to this folder with NTFS ACLs and include it in a controlled recovery plan. PHP `chmod` is attempted but Windows ACLs are the effective control on Windows.

SQLite itself is not encrypted. The database also has plaintext structural metadata: owner IDs, record IDs, module names, linked opaque record IDs, timestamps, plus user login email addresses and password hashes. The application does not claim whole-file encryption. Use BitLocker or a protected encrypted volume for the private storage and encrypted removable/offsite backup media.

## Authentication and authorization

- Application login passwords are stored with PHP `password_hash` and verified with `password_verify`; managed third-party credentials are encrypted and recoverable, never password-hashed.
- Sessions use strict mode, HTTP-only, SameSite Strict cookies, session ID rotation at sign-in, and CSRF tokens for state-changing API requests. Use HTTPS for any non-local deployment so the session cookie is secure in transit.
- Every record operation is scoped by both the current user ID and record ID. Linked contacts must belong to the same user. Administrators can create/list/disable application accounts but never access another user's records.
- First installation is accepted only from a loopback address. After setup, `var/install.lock` points to the private config and direct installer access redirects to the application. Apache should serve `public/` only.
- Responses use output text nodes and CSP/security headers; errors returned to users are generic. Server logs receive exception class/message; they must remain private and should be reviewed for accidental environment details.

## Revealing managed secrets

Account list responses omit passwords, secrets, bank identifiers, recovery codes, and security answers. Reveal requests require the current user's application password and a record owned by that user. The UI clears revealed values when hidden and after 30 seconds. Clipboard copy is explicit. Avoid screen sharing while revealing sensitive data and clear copied values from the operating system clipboard when done.

## Backups and recovery

The app's JSON export contains encrypted per-user record payloads and omits the master key. It can only be restored into an installation with the matching key. Keep an additional encrypted and access-controlled copy of the key outside the database backup. Keep a separate secure record of the administrator's sign-in recovery path. If the key is lost, record contents cannot be recovered; replacing the key does not decrypt old values.

SQLite file copies should be made while the app is stopped or otherwise quiescent. Protect SQLite files, MySQL dumps, JSON exports, config files, and key backups as confidential data. For MySQL, use the database operator's protected dump/restore tools; the app does not invoke them. Restore to a test copy first when possible.

There is no built-in key rotation, hard-delete/purge workflow, or SQLite/MySQL migration utility. Do not manually edit ciphertext or change the key path without a verified backup.

## Deployment checklist

- Point the Apache virtual host to the `public` folder; do not expose the project root or private folder.
- Restrict NTFS ACLs on private storage, database files, the key, backups, and Apache logs.
- Use HTTPS for access beyond localhost; keep PHP, XAMPP, Windows, and OpenSSL maintained.
- Use strong unique application passwords; disable accounts when access should end.
- Back up the database, config, and key under separate access controls and periodically verify a restore.
- Keep MySQL networking limited to the required host and grant the app user only the permissions it needs.
- Do not put real credentials, the encryption key, a live config, or private database into source control or the downloadable source ZIP.

## Operational limitations

The supplied challenge text was truncated in its final backup requirement. The implementation supports encrypted per-user export/import, plus documented SQLite file copy and MySQL dump guidance. Whole-database encryption and automated database-level backup/restore are not included.
