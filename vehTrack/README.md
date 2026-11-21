# Vehicle Tracker 1.0.0

A runnable PHP/XAMPP website and standard-library Python/tkinter desktop GUI sharing **one authenticated PHP JSON API** and one SQLite or MySQL/MariaDB database. No frontend build step, CDN, external font, Composer package or desktop database is required.

## Important delivery limits — read first

This is working source, not a mockup. It is **not represented as a fully compliant competition submission**:

- The referenced November 18 catalog snapshot was not attached. Live vPIC retrieval was blocked after 94 passenger-car make responses. The bundled offline **fallback has 379 distinct makes and 7,543 make/model records**: 1,146 Passenger Car records from type-filtered vPIC responses and 6,397 Motorcycle records from a pinned VehiclesDB snapshot. It includes Dodge → Dart and make-specific Ford models, but **does not meet the requested complete vPIC catalog coverage**. See [CATALOG.md](CATALOG.md), including source dates and limitations. No placeholder or generated fake model names are used.
- PDF export is **browser Print → Save as PDF / Microsoft Print to PDF**, not a headless server PDF renderer. Both clients create readable printable documents and handwritten-signature lines. The desktop exports a self-contained local HTML document, then opens it in the browser. It does not automate your browser's print dialog.
- Signing means **recording actual handwritten paper execution**, with explicit confirmation and actual buyer/seller signing dates. No electronic signature generation, signature drawing, digital certificate or e-signature compliance claim is provided. Attach JPEG/PNG scans to the agreement if desired.
- The built-in agreement is a general blank, editable template. The Sale templates section supports user-created jurisdiction-specific wording. Official state title-transfer forms and legally reviewed state-specific boilerplate are **not bundled**. Do not treat a jurisdiction label as legal certification.
- Migrations support this application's documented schema versions 1 → 2. An unrelated preexisting application's schema is not automatically guessed or imported.
- Windows XAMPP and a physical Android device were not available for testing. PHP was tested on Linux with both PDO SQLite and MariaDB; Chromium responsive UI checks and a real tkinter GUI smoke test under Xvfb were run. See [TEST_REPORT.md](TEST_REPORT.md).

## 1. Fresh Windows XAMPP installation

1. Install XAMPP with Apache, PHP **8.1+** (8.2+ recommended) and optionally MySQL/MariaDB. Start Apache.
2. Extract this folder as:
   ```text
   C:\xampp\htdocs\vehicle-tracker\
       index.php
       public\
       app\
       data\catalog.json
       desktop\vehicle_tracker.py
       tools\
       ...
   ```
   Do not place a second nested `vehicle-tracker` folder inside it.
3. In `C:\xampp\php\php.ini`, enable the database driver you will use and GD:
   ```ini
   extension=pdo_sqlite
   extension=sqlite3
   extension=pdo_mysql
   extension=gd
   upload_max_filesize=8M
   post_max_size=12M
   memory_limit=256M
   ```
   Keep `allow_url_fopen=On` for optional GitHub checks. Restart Apache after PHP configuration changes. Required database extensions need not all be enabled if using only one engine; GD is required for photo uploads.
4. Ensure Apache allows `.htaccess` overrides and `Require` directives (XAMPP's usual Apache 2.4 configuration does). Do not deploy through a server that ignores the included `.htaccess` files. The public PHP API does not need URL rewriting.
5. Open **http://localhost/vehicle-tracker/**. This redirects to the onboarding form. Choose the database, test the connection, create your administrator (12+ character password), select site name, currency and miles/kilometers, and add the first vehicle with its baseline date, vehicle odometer and engine mileage **at that baseline**.
6. Submit **Create secure installation**. Setup creates the tables, user and first vehicle, writes the private `config.php`, and creates the secret-free `config.lock` **last**. Sign in using the account you chose.

### Private storage and permissions

By default, PHP derives a stable private-directory name from the project path, **one directory above Apache's document root**. For standard XAMPP this is:

```text
C:\xampp\vehicle-tracker-private-<12-character-path-hash>\
    config.php
    config.lock
    vehicles.sqlite             # SQLite installations only
    uploads\                    # decoded/re-encoded private images
    install.mutex
    write.mutex
    update-cache.json
```

This is outside `htdocs`. PHP/Apache must be allowed to create/write this directory. Do not give anonymous network users write access to it. On Windows use NTFS permissions for the Apache service account and administrators only; Unix permission calls are additional protection, not a replacement for Windows ACLs. Do not copy backups into `htdocs`.

For a predictable custom location, set Apache's environment before the first installation, e.g. add to Apache's virtual-host/directory configuration and restart:

```apache
SetEnv VT_PRIVATE "C:/xampp/vehicle-tracker-private"
```

That directory must also be outside `htdocs` and writable only by Apache/admins. Keep it stable across upgrades. Use the **same exact directory** for CLI maintenance by setting `VT_PRIVATE` in Command Prompt. `tools/maintenance.php info` prints its detected path without printing credentials.

**Never put a real `config.php` in the project root, public folder, or source control.** `app/config.example.php` is documentation only; setup does not require it.

### SQLite

Select SQLite. No server, database creation or schema import is necessary. The SQLite file is created in private storage. Use local disk; do not put SQLite on a network share. Transactions, a host-local writer mutex and SQLite busy timeout protect writes.

### MySQL / MariaDB

Start XAMPP MySQL. Create an **empty** UTF-8 database in phpMyAdmin. Create a dedicated application user with rights on that database (CREATE, SELECT, INSERT, UPDATE, DELETE, ALTER and INDEX for future migrations). Onboarding does not need server-global administrative rights. Enter the host (normally `localhost`), database name and that user's credentials. Click **Test connection only** before installing. Table creation is automatic and explicitly uses InnoDB. The reference schemas in `schemas/` are for inspection; **do not import them before onboarding**, because setup deliberately refuses an existing application schema.

SQLite and MySQL use driver-specific identity columns and PDO options. MySQL does not execute SQLite pragmas or SQLite conflict syntax.

## 2. First-run lock, concurrency and failure recovery

Every installer GET and POST acquires `install.mutex`, then checks **both** `config.lock` and `config.php`. Either existing file blocks setup. The selected database is also checked for existing application tables **before** creating any table. A missing lock therefore cannot be used to reinstall over existing users/data, even after Apache restarts. Application tables are not queried before schema creation on a fresh database.

Input and connection validation occur before schema writes. New-installation data writes are transactional. Recoverable failures before commit roll back and remove the newly created empty schema; setup never deletes a preexisting schema. A filesystem failure after database commit leaves the configuration or `.pending` file for administrative recovery rather than retrying installation and duplicating users. MySQL DDL itself is not transactional; an interrupted DDL/connection failure can leave empty tables, which the existing-schema guard protects rather than guessing they are disposable.

### Data-preserving lock recovery

1. Stop ordinary use; back up the **entire private directory and database** first.
2. Confirm you are using the original application directory and `VT_PRIVATE`. Moving the project changes its default private-directory hash; explicitly point `VT_PRIVATE` at the original private directory rather than running setup again.
3. With the original private `config.php` present, open Command Prompt:
   ```bat
   cd C:\xampp\htdocs\vehicle-tracker
   set "VT_PRIVATE=C:\xampp\vehicle-tracker-private-EXACT-EXISTING-NAME"
   C:\xampp\php\php.exe tools\maintenance.php info
   C:\xampp\php\php.exe tools\maintenance.php recover-lock
   ```
   The command verifies the existing installation marker and administrator before restoring a timestamp-only lock. **It does not recreate users, replace the database, or erase records.** It refuses an already locked installation.
4. If installation committed but publishing `config.php` failed and `config.php.pending` exists:
   ```bat
   C:\xampp\php\php.exe tools\maintenance.php recover-pending-config
   C:\xampp\php\php.exe tools\maintenance.php recover-lock
   ```
   This verifies the pending file points to a committed initialized database. It never treats an arbitrary database as empty.
5. If configuration is missing and there is no pending file, restore **the original config from backup**. If no backup exists, an administrator must reconstruct it privately using the example and correct database credentials, then verify the installation marker and recover the lock. Do not rerun setup against the database.
6. For an interrupted **never-successful** installation with only empty tables, inspect and back up first. Only after establishing that all application tables contain no users/records may an administrator remove those empty new-install tables and the `.pending` file and retry. Do not delete tables from an existing installation. An ambiguous or unrelated schema requires manual investigation/import mapping.

The lock contains no passwords, API tokens or connection strings. No HTTP recovery/unlock endpoint exists.

## 3. Python Windows desktop app

### Download and run

Sign in to the website. Use **Download Python App/Script** in the sidebar or Settings. It serves the maintained `desktop/vehicle_tracker.py` through an authenticated endpoint.

Install Python 3.10+ from python.org. Enable **tcl/tk and IDLE** in the Windows installer. No pip packages or companion files are required for the desktop application:

```bat
py --version
py -m tkinter
cd %USERPROFILE%\Downloads
py vehicle_tracker.py
```

The first launch asks for the website URL (e.g. `http://localhost/vehicle-tracker`) and an API token. In the website's **Settings & updates**, create a device-labeled token, choose its lifetime (1–365 days), and copy the one-time value. The GUI tests server availability and authentication before connecting. Invalid/revoked/expired tokens and incomplete website onboarding produce errors without clearing the connection form.

Use **Connection settings** to change the URL/token; no source editing is necessary. On Windows the saved token is protected with DPAPI for the current Windows user under `%APPDATA%\VehicleTracker\settings.json`. Other platforms use a permission-restricted plaintext settings file; protect the OS account. Revoke a lost token in the website, and rotate it if exposed. Never put a token in a URL. Use HTTPS outside localhost; HTTP sends tokens unencrypted.

Network work runs on daemon worker threads; completion callbacks update tkinter widgets on the GUI thread via a queue. The GUI remains responsive during failed requests. Forms retain entered values for retries. There is **no unsynchronized vehicle database** on the desktop. Draft autosave requires a connection; unsaved data remains in the open form, not an offline local vehicle database. Do not close a form with unsaved changes unless you intend to discard them.

Local previews/exports contain private information. The app uses `%APPDATA%\VehicleTracker\photo-preview.*` for explicitly requested photo previews; remove these after viewing on a shared computer. Printed/exported HTML is written only to your chosen path. Share or delete local exports deliberately.

## 4. Everyday use and calculations

### Profiles and catalog

Use Vehicles for profiles, custom/manual make/model entries, photos, notes and cover selection. Both catalog controls are editable searchable dropdowns. Changing make clears model immediately; all filtering is local, so stale network selection responses cannot override it. Old saved make/model strings remain editable even if absent from the catalog. See CATALOG.md for the exact fallback's coverage and refresh commands. Catalog lookup needs no external network on a fresh install.

The first profile baseline anchors original-engine calculations. Use **Engine history** for replacements rather than rewriting the initial baseline. Each replacement records date, vehicle odometer, existing engine mileage, serial/reason/notes. Current engine mileage equals installation engine mileage plus current vehicle odometer minus installation odometer. Retired-engine mileage is capped at the next installation reading and shown in the vehicle's engine history.

### Trips and odometer observations

Trip distance is end minus start. A 120,000 → 120,300 trip is 300 distance units. If the engine baseline was 25,000 at vehicle 120,000, current engine mileage becomes **25,300**. The current vehicle reading is the maximum valid baseline/trip/fuel/inspection/engine-installation observation, never merely the last inserted row. The server rejects negative, non-finite, reversed, dated decreasing readings and overlapping same-day trip ranges with an explanation identifying the conflicting record.

Within one calendar day the order of point observations is their odometer, since trips/fuel record dates rather than exact times; inspection time is preserved but does not invent trip times. A same-day fuel point inside a trip's range is allowed. Correcting/deleting the highest observation intentionally recomputes the current maximum from remaining observations; there is not an independent immutable odometer high-water ledger. Review the remaining readings when correcting history. Baselines and engine replacements are protected from deletion through the generic delete action.

Trip filters include vehicle, driver, date range and search. Filtered CSV includes assigned driver; unassigned trips remain unassigned. Drivers can be deactivated without losing history. Driver records store training, qualifications, renewals, incidents, violations and notes. Expired licenses are highlighted. All signed-in users in this single-administrator edition have full administrative access; there is no per-driver login or fine-grained fleet role system.

### Expenses, fuel and mechanic debt

- Fuel reveals date, odometer, US gallons, unit price, full-tank choice and notes. Total is recalculated on the server, rounded to two decimal currency precision, regardless of the caller's submitted amount. Current release supports currencies with **two decimal places**.
- The first full fill establishes the baseline. Gallons of intervening partial fills **and** the closing full fill form the next interval's denominator. First full/partial rows do not invent MPG. Missing legacy fuel details break the interval until a new valid full baseline exists. Edits/backdating/deletions recompute intervals from sorted history on every refresh.
- At full 120,300, partial +5 gal 120,450, full +5 gal 120,600, the final result is **30 US MPG**. Five gallons at $3.40 costs **$17.00**. Kilometer distances are divided by 1.609344 before US MPG calculation.
- Each repair invoice automatically creates/updates **one linked Repairs expense**. Edit the invoice, not its protected linked expense. A $600 invoice contributes $600 to expense lists/totals/CSV. A $200 payment changes debt to $400 and creates **no extra expense**. Amount reductions below payments and overpayments are rejected.
- Payments and record mutations use idempotency keys and optimistic record versions. Retrying the same failed/lost-response request cannot duplicate the committed write. Financial writes are serialized on the PHP host around validation and commit. This is a single-host XAMPP design, not a clustered/multi-host deployment.

### Weekly inspections

Open Weekly inspections and create a record. Select vehicle/optional driver and enter inspector identification. Vehicle identification and current odometer prefill for review; no inspection result defaults to Pass. Checklist groups are in the same form on the website and the dedicated checklist tab on desktop. Each item has Pass / Needs attention / Fail / N/A, notes and an item key for optional evidence photos.

- Choose **Save draft** to allow incomplete sheets. Changed drafts autosave every five seconds and show save/error status. Reopen the shared draft from either client after refresh.
- Progress/completion and findings counts update immediately. Needs attention/Fail require explanatory notes at submission. Submission also requires vehicle, inspector, valid date/time/odometer, matching site unit, every result, and explicit acknowledgment.
- `Completed` submits a unique referenced immutable sheet, creates an independent open follow-up issue for every finding, records the explicit acknowledgment/completion timestamp and computes Monday week plus next Monday due date.
- Upload photos after the first draft save, choosing an item key for an individual finding. Inspection evidence is not linked to a mutable general gallery photo and survives vehicle cover/gallery changes.
- Use Inspection follow-up for responsible person, action, status, resolution date and notes. Original finding/result cannot be overwritten. Resolved issues stay in history and have an audit trail. Resolving an issue never changes the original sheet to Pass.
- Submitted corrections use **Create revision** with a reason; the original stays available. Revisions are drafts with renewed explicit acknowledgment required. Historical submitted sheets remain listed; the latest draft does not delete earlier sheets or findings.
- Dashboard statuses distinguish missing/draft/completed findings/no findings; unresolved issues are counted separately. Current-week status does not certify safety. Future weeks and business-day grace rules are not inferred.
- Use Preview, Print / Save PDF, or Blank printable / PDF. Print CSS excludes navigation/edit forms/dark backgrounds. Both blank and completed output include signature lines, readable wrapping and page-break rules.

### Photos

JPEG/PNG only, maximum 8 MB and 12 megapixels. PHP validates actual image headers and **decodes/re-encodes with GD** (removing extraneous metadata) into random names. Images live outside htdocs and are served only by the authenticated API. Gallery captions and view labels, previews and vehicle cover selection work in both clients. Failed uploads retain form input. Inspection photos/agreements can have evidence added without changing finalized text; originals are not replaced through the UI. No public permanent photo URLs are issued. There is no photo-delete UI in this release.

### Bill of Sale and templates

Create a draft in Bill of Sale, use an existing vehicle or enter details manually, and review every prefill. Fill parties, addresses, sale date/time/location, odometer disclosure, price/deposit, currency, payment terms and status, condition, defects, accessories, title/liens, delivery, warranty and additional terms. Remaining balance is server-calculated (5,000 − 500 = 4,500). No repair expense or mechanic debt is generated.

The built-in template is blank/general and deliberately assumes no lien-free title, waiver, as-is clause, party, amount or signature. In **Sale templates**, save your own jurisdiction-specific reviewed wording and instructions. **Apply selected template for review** is explicit and replaces only editable terms, not parties or signatures. Edit applied terms before finalizing. Obtain qualified jurisdiction-specific advice and official title documents as appropriate.

Incomplete drafts autosave and synchronize. Preview/print works before finalization. `Finalized` requires all material disclosures/terms and explicit review; the server snapshots the entered data and locks further edits. Later vehicle-profile edits do not alter the snapshot. Corrections create a reason-linked draft revision. Each original stays in history, including paper-signed originals.

To distinguish actual handwritten execution, use **Record paper signing** on a finalized snapshot, provide the actual buyer/seller signing dates, and explicitly confirm both parties signed the printed document. This changes execution metadata only, records an audit revision, and labels it **Signed**. It never inserts a signature or claims an electronic-signature process. Attach optional JPEG/PNG signed scans in the private gallery. Paper signing can only be recorded through that explicit action, not by setting a generic API status.

## 5. Optional GitHub release checks and manual upgrades

No repository URL is invented or preconfigured. Enter public GitHub owner and repository in website Settings; the desktop uses those same server settings. Only published, non-draft, non-prerelease strict `vMAJOR.MINOR.PATCH` / `MAJOR.MINOR.PATCH` tags qualify. Semantic comparison treats 1.10.0 as newer than 1.9.0. The implementation inspects the first 100 published releases and chooses the highest valid stable semantic version in that set.

Both clients check in the background after login/connection. Checks are cached server-side for one hour per configured repository; **Check for updates** manually refreshes. Missing settings, inaccessible/no releases, rate limits and offline errors are nonfatal. Private repositories/tokens for GitHub are **not supported**; app API tokens are never sent to GitHub or browser scripts as GitHub credentials.

For a newer release, the prompt shows installed/latest and asks **Would you like to download the latest version?** `Later / No` downloads nothing. Download opens the release's ZIP asset if present; otherwise it opens the explicitly labeled tagged source ZIP. If several ZIPs exist, the first ZIP asset is chosen, so publish only one application ZIP or order/name assets appropriately. Downloading does not run code or install anything. Dismissal is persisted locally by version; manual recheck can prompt again, and later versions can prompt.

### Backup before any upgrade

1. Stop Apache/desktop writers. Keep backups **outside htdocs** on protected storage.
2. Copy the whole private directory (configuration, lock, photos, and SQLite file if applicable), project source, VERSION and catalog together.
3. For MySQL, also use phpMyAdmin Export → SQL for the complete application database, or `mariadb-dump`/`mysqldump` with `--single-transaction` and an interactive `-p` prompt. Never put the password on a command line or inside a web-accessible backup. Test restoring into a separate disposable database.
4. Download only after accepting. Inspect/review source and release notes. Replace **code files only**, preserving the private directory. Do not run setup, delete the lock, copy a sample config over the original, overwrite uploads, or create a new database.
5. If the project directory changes, set `VT_PRIVATE` to the existing private directory.
6. Run versioned migration with Apache stopped and backup confirmed:
   ```bat
   cd C:\xampp\htdocs\vehicle-tracker
   set "VT_PRIVATE=C:\xampp\vehicle-tracker-private-EXACT-EXISTING-NAME"
   C:\xampp\php\php.exe tools\maintenance.php migrate --backup-confirmed
   ```
   Migration 002 preserves all records, adds explicit unassigned driver attribution where missing, flags incomplete historical fuel details, and audits each normalized payload. It is transactional and refuses unknown/newer schema versions. Re-running at schema 2 is a no-op. The lock remains unchanged.
7. Restart Apache, refresh both clients, verify record counts, balances, photos and drafts. Roll back **code and the matched database/private backup together** if necessary; never run old code against a newer incompatible schema.

No update flow commits/pushes to GitHub, unlocks setup, deletes user data, or automatically runs downloaded code.

## 6. Tests and development

Application dependencies: Apache/PHP with PDO database driver and GD; Windows Python 3.10+ with tkinter. Catalog refresh uses Python's standard library. Test-only dependencies: `requests`; optionally Playwright/Chromium for UI testing and a display/Xvfb for tkinter. No forbidden database service/dependency is used.

```bat
C:\xampp\php\php.exe tests\unit.php
py -m pip install -r tests\requirements.txt
set "PATH=C:\xampp\php;%PATH%"
py tests\integration.py
```

Integration tests start their own temporary PHP server/private directory with an ephemeral port, create their own random temporary administrator, and remove the SQLite database at teardown. **Do not point tests at a real installation.** For MySQL use a new empty disposable database and test user:

```bat
set VT_TEST_DATABASE=mysql
set VT_TEST_MYSQL_HOST=127.0.0.1
set VT_TEST_MYSQL_NAME=vt_test
set VT_TEST_MYSQL_USER=vt_test
set VT_TEST_MYSQL_PASSWORD=your_disposable_test_password
py tests\integration.py
```

The MySQL test database is intentionally not dropped automatically; inspect it and drop/recreate it yourself between runs. The README test credentials above are instructions, not shipped working credentials.

The optional Linux development server is not the deployment target:

```text
VT_PRIVATE=/absolute/private/path php -S 0.0.0.0:8080 -t /path/to/vehicle-tracker
```

PHP's development server ignores `.htaccess`; do not expose it publicly or treat it as production Apache security testing. XAMPP installation is the supported deployment layout.

## Source layout

| Path | Responsibility |
|---|---|
| `public/setup.php` | Locked, serialized onboarding |
| `public/api.php` | Session/token auth, CSRF, mutations, protected images/download |
| `app/core.php` | PDO schemas, validation, calculations, transactions/audit helpers |
| `app/updates.php` | Stable release selection/cache |
| `public/app.js`, `style.css`, `index.php` | Responsive browser GUI / print sheets |
| `desktop/vehicle_tracker.py` | Maintained self-contained Python GUI download |
| `data/forms.json` | Shared form contract for both clients |
| `data/checklist.json` | Shared inspection checklist |
| `data/catalog.json` | Actual populated local fallback catalog |
| `schemas/` | SQLite/MySQL reference schemas |
| `migrations/` | Versioned record-preserving migration |
| `tools/maintenance.php` | CLI-only migration and lock recovery |
| `tools/refresh_catalog.py` | Atomic full vPIC refresh (retains local data on failure) |
| `tools/import_catalog.py` | Reproducible pinned fallback construction |
| `tests/` | Unit/HTTP integration checks |

See SECURITY.md for threat-model/deployment notes. Application code is MIT licensed; catalog data has separate attribution/license terms in CATALOG.md and the bundled upstream notices.
