# LocalProof — find local businesses, prove what's broken, pitch it

LocalProof is a self-contained PHP application for freelancers and agencies. It finds local
businesses, verifies what their web presence actually is, audits the site for observable
problems, scores the opportunity, and drafts an editable pitch you send from **your own**
texting app (SMS, WhatsApp, Hiatti Phone, or any other app).

Runs on a plain XAMPP stack: **Apache + PHP 8.2+**, **SQLite by default (or MySQL/MariaDB)**.
No Composer, no npm, no Docker, no application server. **PostgreSQL is not used or supported
anywhere in this project.**

---

## 1. Quick start (XAMPP on Windows)

1. Install XAMPP with Apache + PHP **8.2 or newer** (XAMPP 8.2.x is ideal).
2. Extract the submission ZIP into `B:\xampp\htdocs\`. You should end up with:
   `B:\xampp\htdocs\ai_finder\index.php`
3. Open the **XAMPP Control Panel** and click **Start** next to **Apache**.
   *You do not need MySQL for the default SQLite setup.*
4. Browse to **http://localhost/ai_finder/**
5. The first-run installer starts automatically. Complete:
   * **Requirements** — everything is checked for you, with the exact fix for each failure.
   * **Database** — leave **SQLite** selected and press *Test connection*.
   * **Mode** — choose **Demo Mode** (no API keys, seeded data, offline audits) or **Live Mode**.
   * **Administrator** — name, email, password (8+ characters, stored as a bcrypt hash).
   * **Install** — creates the tables, seeds demo data if requested, writes
     `config/config.php`, records the installation in the database and creates
     `storage/installed.lock`.
6. You are signed in and taken to **onboarding**. Fill in your business name, sender details,
   services, default search location/radius, tone, language and preferred texting channel, then
   press **Find your first lead** — that search uses exactly the preferences you just saved.

> The submission ZIP ships **without** `installed.lock` and without an initialised database, so
> the installer always starts on a fresh extract.

### Paths

Everything works under the Apache sub-directory `/ai_finder/`. The app computes its own
base path, so assets, form posts, redirects and API endpoints all stay inside the sub-directory.
If you rename the folder, update `RewriteBase` in `.htaccess` to match.

---

## 2. Server requirements

| Requirement | Notes |
| --- | --- |
| PHP 8.2+ | `pdo`, `pdo_sqlite` (or `pdo_mysql`), `curl`, `mbstring`, `openssl`, `json`, `session`, `filter` |
| Writable | `storage/`, `storage/logs/`, `config/` |
| Outbound HTTP | optional — only needed for Live Mode discovery and live audits. Demo Mode is fully offline. |

Enable an extension by removing the leading semicolon in `B:\xampp\php\php.ini`
(for example `extension=curl`) and restarting Apache. All of these are enabled in a default
XAMPP install.

---

## 3. Database

### SQLite (default — nothing to start)
The database is a single file at `storage/localproof.sqlite`. It is created by the installer.
Apache denies HTTP access to `storage/` (`.htaccess` + `<FilesMatch>` on `*.sqlite`).

### MySQL / MariaDB (optional)
1. In the XAMPP Control Panel, click **Start** next to **MySQL**.
2. Create an empty database — phpMyAdmin (http://localhost/phpmyadmin) → *New* → name it
   `localproof`, collation `utf8mb4_unicode_ci`. Or from a shell:
   `B:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE localproof CHARACTER SET utf8mb4;"`
3. In the installer choose **MySQL / MariaDB**, enter `127.0.0.1`, `3306`, `localproof`, `root`
   (XAMPP's default root user has an empty password) and press **Test connection**.

Either driver is initialised by the same migration code; the installer records which one you
used in `config/config.php`.

---

## 4. Modes and API configuration

### Demo Mode (no keys required)
* A labelled dataset of Pueblo, Colorado businesses seeded into your database.
* Local demo websites served by your own Apache (`/demo-sites/…`) so audits are **real HTTP
  crawls** that work with no internet connection.
* Fixtures for: duplicate listings, a blocked audit (HTTP 403 to the auditor), a missing phone
  number, a provider timeout, and a provider rate limit (HTTP 429).
* Pitches are written by the built-in evidence template engine.

### Live Mode (guided)
Add keys later in **Settings** (never required to finish installing):

| Key | Used for |
| --- | --- |
| Google Places API (New) | live local business discovery, `places:searchText` |
| OpenStreetMap Nominatim | city/ZIP geocoding (no key) |
| OpenStreetMap Overpass | cross-checking a missing website field against a second permitted source (no key) |
| OpenAI-compatible API | optional rewrite/translation of the generated pitch |

Keys are stored in the `settings` table and are **never rendered back to the browser** — the
settings screen only shows a masked preview (`••••1234`), and no key is ever written to
`installed.lock` or `config.php`.

A configuration template with placeholders and no real secrets is at
`config/config.example.php`.

---

## 5. Installation lock

* `storage/installed.lock` is created **last**, after the database is initialised, the admin
  exists and the installation is recorded in the `install_meta` table.
* Apache cannot serve it: `storage/` is denied by `.htaccess`, and the root `.htaccess` blocks
  `*.lock` with `<FilesMatch>` + `<Files>`.
* The lock is enforced **server-side** on every installer page and setup endpoint
  (`/install`, `/setup/run`, `/setup/test-db`, `/setup/requirements`, `/setup/status`), for both
  GET and POST, including direct URL entry, AJAX requests and form submissions. After
  installation they return **HTTP 403** and change nothing.
* Setup is serialised with an exclusive `flock()` on `storage/setup.mutex`, so two concurrent
  requests cannot initialise the database twice or create two administrators. If setup fails
  halfway, a retried setup detects the existing administrator and refuses to duplicate it.
* The lock file contains no credentials — only the install timestamp, mode and schema version.

### Lock recovery
If the lock file is deleted but the database still has the `install_meta` row, the installer
stays blocked (this is deliberate) and shows the recovery instructions. Restore the lock **without
touching accounts, leads or settings** with any of these:

```powershell
Set-Content -Path "B:\xampp\htdocs\ai_finder\storage\installed.lock" -Value "installed"
```
```bat
echo installed > B:\xampp\htdocs\ai_finder\storage\installed.lock
```
Or copy `storage/installed.lock.sample` to `storage/installed.lock`.

Nothing else is required. To start completely from scratch, delete the `ai_finder` folder
and re-extract the ZIP.

---

## 6. Required demo script

Extract into a fresh XAMPP, start Apache, then:

1. **Install** — open http://localhost/ai_finder/ → SQLite → **Demo Mode** → create the
   administrator → **Install**. Finish onboarding (business name, sender details, services,
   default search `Pueblo, CO` / 10 mi / Beauty salon, tone, language, channel).
2. **Preferences applied** — press *Find your first lead*. The search form is pre-filled with the
   saved location, radius and industry, and the first pitch you draft uses the saved sender,
   tone and language.
3. **Installer is closed** — visit http://localhost/ai_finder/install (HTTP 403) and POST
   directly to http://localhost/ai_finder/setup/run (HTTP 403). Sign back in and confirm
   your leads, notes and settings are all still there.
   Quick command-line check:
   `curl -i -X POST -d "csrf=x" http://localhost/ai_finder/setup/run`
4. **Search salons within 10 miles of Pueblo, Colorado** — location `Pueblo, CO`, radius `10`,
   industry `Beauty salon`. You get both cases the brief asks for:
   * **No website found** — *The Rustic Rose Salon* or *Sunny Side Beauty Bar* (the latter also
     demonstrates a missing phone number).
   * **Working site with an observable issue** — *Bella Vista Hair Studio* (no viewport tag,
     HTTP-only, 1.2s response, ~350KB document, no visible phone, broken Contact and Booking
     links).
5. **Audit evidence** — open the lead, press **Run audit**, read each finding's affected URL,
   observed result, suggested fix, confidence and raw measurement. Expand *Show raw measurement*
   to see the HTTP status, byte count, response time and headers.
6. **Pitch** — press **Generate draft**, edit the text, and check the `evidence #N` badge: it
   jumps to the finding that produced the claim.
7. **Texting handoff** — press **Send pitch**, confirm the number, edit if needed, choose the
   channel (SMS / WhatsApp / Hiatti Phone / Other), then use a judge-controlled test number on a
   compatible device. Hiatti Phone uses the copy-and-paste flow documented below.
8. **Persistence** — save both leads, press F5, and confirm the records, audit history, pitch
   versions and outreach events are all still present.
9. **Edge cases** — from the dashboard:
   * *Duplicate handling* — search without an industry filter: *Bella Vista Hair Studio /
     Bella Vista Hair Salon* and *Rivera Nail Lounge / Rivera Nails Lounge & Spa* merge into one
     row each with a "merged N listings" badge; distinct addresses stay separate.
   * *Blocked audit* — open *Steel City Cuts & Barbershop* and run the audit: HTTP 403 to the
     declared audit user-agent, with every dependent check marked **Unavailable**.
   * *Missing phone number* — *Sunny Side Beauty Bar* shows **no phone**, and the send screen
     falls back to **Copy message**.
   * *Provider timeout / rate limit* — press **Simulate provider timeout** or
     **Simulate rate limit (HTTP 429)** in the search form (Demo Mode only, clearly labelled).

---

## 7. Texting handoff methods

| Channel | Method | Behaviour |
| --- | --- | --- |
| SMS / default texting app | `sms:+<E.164>?body=…` | Opens the device composer with recipient and draft prefilled where the OS supports it. If the body parameter is ignored, use **Copy message**. |
| WhatsApp | `https://wa.me/<digits>?text=…` | Opens the conversation (app or WhatsApp Web) with the draft prefilled for review before sending. |
| Hiatti Phone | **Copy number** + **Copy message** | No public, documented URI scheme for Hiatti Phone could be verified, so LocalProof does not guess one. It offers copy-and-paste and records the handoff event. Replace with a launch method as soon as one is verified. |
| Other texting app | `navigator.share()` where available, plus copy buttons | Uses the device share sheet on supporting browsers; copy buttons everywhere. |

`Copy message` is always available — including when a number is missing or invalid. Three
separate events are recorded: **pitch_copied**, **texting_handoff_started** and
**pitch_marked_sent** (which also stores the selected channel and moves the lead to
*Contacted*). Leads flagged **Do not contact** have every outreach action disabled, including
the send screen and the mark-as-sent endpoint.

---

## 8. Security notes

* Passwords: `password_hash()` / `password_verify()` (bcrypt).
* CSRF token on every state-changing POST.
* `config/config.php` (database credentials) and `storage/` are blocked from HTTP by Apache and
  are never echoed by the app.
* API keys live in the database, are masked in the UI, and never appear in `installed.lock`.
* The audit crawler identifies itself: `LocalProofAuditor/1.0` with a contact note, and caps
  links checked and request timeouts (both configurable in Settings).

---

## 9. Project layout

```
ai_finder/
├── index.php                  front controller / router
├── .htaccess                  rewrites, HTTP-denied paths, lock protection
├── README.md                  this file
├── FEATURE-CHECKLIST.md       live vs demo features and handoff methods
├── app/                       PHP classes (no Composer, plain requires)
│   ├── bootstrap.php  Support.php  Database.php  Settings.php  Auth.php
│   ├── Installer.php  Discovery.php  Audit.php  Scoring.php
│   ├── Pitch.php  Messaging.php  Leads.php  Controllers.php
├── assets/                    app.css, app.js (built, dependency-free)
├── config/
│   ├── config.example.php     configuration template (placeholders only)
│   └── .htaccess              deny all
├── database/                  schema notes
├── demo-sites/                labelled local fixtures for repeatable audits
├── views/                     server-rendered templates
└── storage/                   SQLite file, logs, sessions, installed.lock (HTTP-denied)
```

---

## 10. Troubleshooting

| Symptom | Fix |
| --- | --- |
| Blank page / 500 | Check `storage/logs/php-error.log`; set `'debug' => true` in `config/config.php`. |
| 404 on every route | `mod_rewrite` disabled — enable it in `httpd.conf` (`LoadModule rewrite_module`), or confirm the folder is named `ai_finder`. |
| "Could not open the SQLite database" | Make `storage/` writable: `icacls "B:\xampp\htdocs\ai_finder\storage" /grant "Users:(OI)(CI)M"`. |
| MySQL connection refused | Start MySQL in the XAMPP Control Panel and create the `localproof` database. |
| Audits all "unreachable" in Live Mode | No outbound internet from the machine, or the target blocks the auditor user-agent. |
| Session warnings | Ensure `storage/sessions` is writable (it is created automatically). |

---

## 11. Licence / attribution

Demo data is fictional and modelled on the shape of public local-business listings.
Live Mode data comes from the Google Places API and OpenStreetMap (ODbL) when you configure it;
neither provider endorses LocalProof.
