# Feature checklist

Status legend: **Implemented** means present in source; **Verified** means covered by a successful check performed for this build. Requirements cut off in the supplied source text are not inferred.

| Area | Status | Notes |
|---|---|---|
| PHP 8.5, PDO SQLite/MySQL, no external runtime dependencies | Implemented; PHP CLI 8.5.10 and extensions inspected | XAMPP Apache was not independently exercised; live MySQL was not connected. |
| First-run installer, local-only install POST, external private config/key, lock | Implemented | Retry after a partially written install needs manual recovery; see `SECURITY.md`. |
| Versioned schema and migration runner | Implemented | Idempotent migration version 1 in `app/migrate.php`. |
| Multi-user login, roles, admin account creation and disable | Implemented | No public registration. Admin cannot read another user's records. |
| Contacts with names, contact details, relationship options, tags and dynamic age | Implemented | Search, type filter, sort, pagination and Trash/restore. |
| Separate managed account/password module | Implemented | Account fields encrypted; password reveal reauth; generation, masking and explicit clipboard copy. |
| Banking-specific account fields | Implemented | Fields shown for banking, card, and investment types; values encrypted and masked. |
| Personal journal | Implemented | Encrypted plain-text/Markdown-capable text, date/category/contact/tag/mood fields, search, date filter and pagination. HTML is never rendered. |
| Relationship/intimacy journal and 16 questions | Implemented | All questions optional; consent supports Yes, No, Unknown, Not recorded. One linked contact per event. |
| Intimacy date/type/rating/tag filters | Implemented | Filters search decrypted owner records; rating match checks Q4-Q8 and generic rating. |
| Search respecting encryption | Implemented | Decrypts only the current user's rows in application memory, up to 5,001 matching-module rows per request. |
| AES-256-GCM field encryption, unique IV, authenticated tamper rejection | Implemented; crypto tests included | Whole SQLite/MySQL database encryption is not included. Structural metadata and login email remain plaintext. |
| Secure headers, CSRF, session protections, prepared statements, owner checks | Implemented; core checks included | Must deploy with Apache serving `public/` and use HTTPS remotely. |
| Light/dark/system themes and responsive UI | Implemented in CSS and preference setting | Visual QA in XAMPP/browser remains to be performed. |
| No-refresh autosave | Implemented for record editor fields | First non-empty field creates the record. User-management forms submit explicitly. |
| Encrypted JSON export/import | Implemented | Per-user ciphertext merge; key is excluded. SQLite file copy/MySQL dump are documented manual operations. |
| Automated tests | Implemented | `php tests/run.php`; covers crypto, migrations, hashing, owner-scoped query behavior. |
| ZIP source package | Verified | `personVault-1.0.0.zip` contains all required source/docs and excludes live config, install lock, database, and key files. |
| Full XAMPP, browser, MySQL, and restore verification | Not verified | Environment-specific manual checks are listed in README. |
