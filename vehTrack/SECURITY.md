# Security and deployment notes

## Implemented boundaries

- Application configuration, installation lock, SQLite file, private images, update cache and authentication-rate counters are outside the Apache document root by default. Use NTFS ACLs on Windows. Protect backups the same way.
- `.htaccess` files deny access to implementation/data directories except the public catalog JSON and disable directory listings. Use Apache 2.4 with overrides enabled. PHP's development server does not enforce these files.
- Browsers authenticate using HttpOnly SameSite=Strict session cookies, marked Secure on direct HTTPS. Mutations require a session CSRF header. API clients use revocable SHA-256-hashed bearer tokens with explicit expiry. Tokens are shown once at creation. No plaintext API token is stored in the server database.
- Passwords use PHP `password_hash(PASSWORD_DEFAULT)` and `password_verify`. Login is rate-limited per source IP. Application errors avoid exposing raw database connection failures to API clients; administrator-readable PHP logs have diagnostic messages.
- All SQL data values use PDO prepared statements. Dynamic schema/table names are application constants, not user inputs. MySQL uses InnoDB; SQLite uses its own driver-appropriate configuration.
- All authenticated users in this release are administrators. There is a single initial administrator workflow, not a fine-grained organization/role/tenant system. Do not share tokens with drivers just to view their own records; an API token provides full administrative access.
- Mutations serialize validation/write on the application host and commit records, audit entries and idempotency markers in the same database transaction. Record versions detect competing edits. Do not deploy multiple independent application hosts sharing one MySQL database without replacing the host-local mutex with an appropriate database-distributed lock.
- Inspection/agreement snapshots are immutable through ordinary edits. Revisions preserve originals. Paper signing requires a separate explicit action and is not an electronic signature process. Follow-up preserves the original finding, and evidence is not overwritten by later general vehicle photo changes.
- Images require authentication for both upload and display. PHP checks dimensions/type, decodes JPEG/PNG with GD, strips ancillary content by re-encoding, writes a random filename outside htdocs and serves it with a server-determined image MIME type and nosniff. Limits: 8 MB / 12 megapixels. No general attachment/file execution is supported.
- Browser text output is escaped; CSV quote escaping is applied. Browser CSV prefixes potential formula-leading text to reduce spreadsheet formula injection risk. Desktop CSV is a direct data export: open untrusted-text CSVs as data, not executable spreadsheet formulas.
- Update requests go only to public GitHub release APIs. There are no GitHub credentials/private-repository features. Accepting a download opens a GitHub download URL, never an automatic installer. Releases are not cryptographically verified by the application; inspect releases and validate checksums/signatures provided by the publisher before installing manually.

## Operator responsibilities / unsupported protections

Use HTTPS for non-local connections. Directly exposed XAMPP installations need normal operating-system/firewall hardening and current Apache/PHP/database security patches. A reverse proxy must forward secure-request information correctly if HTTPS terminates there; review cookie security and do not trust arbitrary forwarded headers. The app does not automatically provision certificates, MFA, encryption-at-rest, a WAF, antivirus or backup rotation.

Personal driver information, agreement parties, photos and signed paper scans are sensitive. Only administrators should have access to the source-host filesystem and database. The GUI's Windows settings use DPAPI; local document/photo previews are still sensitive files. No claim is made that these records automatically satisfy commercial-fleet compliance, legal retention or title-transfer requirements.

There is no public admin password-reset endpoint, installation unlock endpoint or remote migration trigger. Use protected backups and local administrative recovery. Never remove installation guards to perform an upgrade.

Inspect these URLs after configuring Apache: `/app/`, `/data/catalog-cache/`, `/desktop/vehicle_tracker.py`, `/tools/`, `/schemas/`, and `/migrations/` must not expose files. `/data/catalog.json` is deliberately public because it contains only licensed catalog data needed by onboarding. Requests for guessed private-storage paths under `htdocs` must not reach actual private files; actual storage must be outside that tree.

For a suspected vulnerability, stop exposing the instance, preserve a protected backup and logs, revoke affected API tokens, patch/review and test on a disposable copy before returning to service. This source package does not configure a public issue-reporting repository on the user's behalf.
