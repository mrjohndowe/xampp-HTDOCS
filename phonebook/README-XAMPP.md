# PhoneBook — XAMPP / PHP 8.5 Ready

## Quick Install (XAMPP)
1. Download `phonebook-php.zip` from Settings → XAMPP / PHP, or build it with `npm run build:php`.
2. Extract the ZIP contents into a new `phonebook` folder under `C:\xampp\htdocs\` (Windows) or `/opt/lampp/htdocs/`. The archive contains the full app at its root (`index.php`, `api.php`, `assets/`, `install/`, and `database/`); don't extract only the PHP sample page.
3. Start Apache in XAMPP Control Panel. PHP 8.1 or later is required.
4. Visit `http://localhost/phonebook/` and enter the app name, owner, theme, and SQLite or MySQL/MariaDB connection information.
5. The installer creates the selected database schema, saves its local connection settings, and creates `install.lock`. SQLite is the default; no database server is needed.
6. **After onboarding, delete or rename the `install/` folder.** The lock file also prevents installer access. To reinstall, remove `install.lock` and restore the installer folder.
7. For MySQL/MariaDB, enter its host, port, database name, username, and password in the installer. The account must have permission to create the requested database and tables.

The downloadable ZIP is a native PHP implementation of the preview's interactive app: contacts, relationship filters, account and bank records, the private journal, search, stats, settings, theme selection, JSON backup import, and exports. JSON import offers merge or replace; replace removes current contacts and their linked data. The installer stores database credentials in a generated `config.local.php`; do not share that file. Rebuild the ZIP after changing files under `php-xampp/` with `npm run build:php`.

## Next.js version (this repo)
The preview uses MySQL via Drizzle. SQLite/MySQL schemas and JSON backups are available from Settings → Backup.

## Security
The app does not provide user authentication. Run it only on a trusted, access-controlled host; do not expose it directly to the public internet. Account passwords and bank information are stored in the database, and JSON exports contain private information. Restrict filesystem and database access and store backups securely.
