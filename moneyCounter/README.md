# Money Counter

Money Counter is a local-first USD cash drawer workspace. It counts 13 denominations, tracks loose coins and sealed rolls, records cash movements, and preserves opening, interim, and closing counts. Python handles every saved calculation in integer cents. SQLite is the authoritative database. An optional worker copies consistent, recoverable snapshots to MySQL.

## Try the demo

Run these commands from the project directory with Python 3.12 or later. The demo uses a temporary SQLite database and listens on loopback.

```powershell
python -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements.txt
.\.venv\Scripts\python.exe app.py demo

```

Open [the demo](http://127.0.0.1:8001/) and sign in with **demo** / **CashCounterDemo2026**. Demo records are disposable. The opening and ending sample quantities reproduce $3,770.30 and $3,725.80 from the published Till worksheet. Demo movements are sample transactions. [Taxi Logbook · Till worksheet](https://docs.google.com/spreadsheets/d/e/2PACX-1vR8XB88MJ_OI7Wea1m3XVeYDtBJ7KG4jftq6NHX0NtklZqhXEC-qk15Kn0_YiAV9a-2QuKXzGQdkfnH/pubhtml#gid=1421896098)

## Create a workspace

```powershell
Copy-Item .env.example .env
.\.venv\Scripts\python.exe app.py create-user admin --role admin
.\.venv\Scripts\python.exe app.py

```

The user command prompts for a password. Open [the workspace](http://127.0.0.1:8000/). First launch creates the configured SQLite file and tables. The default location is `instance/cash.sqlite3`. Optional `app.py init-db` initializes explicitly. On macOS and Linux use `.venv/bin/python` in place of the Windows virtual environment executable.

| Role | Capabilities |
| --- | --- |
| Viewer | Read drawer sessions, saved counts, cash movement history, and reports |
| Operator | Viewer access, plus open drawers, save counts, record movements and deposits, and close drawers |
| Admin | Operator access, plus configure roll sizes and query audit history |

User provisioning and API token management require local command access. Password hashes use scrypt. Browser sessions expire after eight hours, logout revokes the session, and writes require a CSRF token. Cash writes, settings, and local account and token operations have audit records. Use an account with the minimum role needed for an integration.

## Count and reconcile

1. Open a drawer and enter its starting till by denomination. Choose the business date and give the drawer a name.
2. Record sales, payouts, withdrawals, additions, or signed adjustments in **Overview**. For a company take, record a payout or withdrawal with a descriptive note, according to your accounting practice.
3. Use **Cash counter → Current count** to save a fresh physical count. Cash movements update the expected balance. A fresh count updates the physical balance.
4. Use **Bank deposit** to enter sealed rolls, loose coins, and notes being removed. A deposit is limited to the quantities in the latest saved count. Recount after sales, payouts, or breaking rolls if that count is stale. A deposit records both the bank movement and the remaining denomination count.
5. Use **Closing count** to verify the remaining cash and close the session. Closed sessions are read-only.

Expected cash equals opening cash plus sales, additions, and signed adjustments, less payouts, withdrawals, and deposits. Actual cash is the latest saved count. Actual minus expected is the overage or shortage. An open drawer can show a provisional variance until it is recounted.

Roll value follows its quantity and denomination. Defaults are 50 pennies, 40 nickels, 50 dimes, 40 quarters, 20 half dollars, and 25 dollar coins. An administrator can change those sizes in **Settings**. Each drawer keeps the sizes it opened with.

## Record a Shopping Manager payout

In the Shopping Manager Option B web app, open **History** and choose **Record cash payout** on a completed cash purchase. Money Counter opens with the trip total and reference filled in. Sign in as an operator or administrator, select an open drawer, review the cash amount, and record the payout. For a split payment, enter only the cash portion. Money Counter accepts one payout per trip and subtracts it from the drawer's expected cash. Recount the drawer to update its physical balance.

Shopping Manager opens Money Counter at `http://127.0.0.1:8000/` by default. Set `NEXT_PUBLIC_MONEY_COUNTER_URL` in Shopping Manager's environment if you use a different address, such as the demo at `http://127.0.0.1:8001/`. Restart the Shopping Manager development server or rebuild its production app after changing the setting.

## Reports and exports

Use **Drawer activity** to expand the denomination breakdown for saved counts and deposits. **Reports** shows daily cash flow, movement history, and drawer reconciliation. It groups sessions and their movements by the drawer's business date. Weekly reports use Monday through Sunday. Choose a custom range for up to 366 days. The movement filter narrows the movement history and the movement export table. Summary totals remain totals for the full date range.

Download CSV with sections, Excel with separate worksheets, PDF, or printable HTML. The exported denomination section includes the nonzero denominations for every saved count and deposit in range.

## Host and integrate

See [API and embedding](API.md) for endpoint examples and integration settings. The app serves with Waitress by default. For production, put it behind your existing HTTPS reverse proxy, turn on secure cookies, preserve the configured base path, and restrict access to database and backup files. Bind the Python process to loopback when the proxy runs on the same computer. Configure `MC_TIMEZONE` for business dates and displayed timestamps.

`MC_BASE_PATH=/cash` mounts both the interface and API beneath `/cash`. An existing website can link to that path, reverse-proxy the path, or embed the app using the configured framing policy. The standard web server does not need a MySQL connection.

Store SQLite on a local disk with filesystem locking. The default session signing key is generated in a separate local SQLite file. Set a shared, random `MC_SECRET_KEY` through your environment if serving the same database from multiple worker processes that do not share the key file. Protect both the data and session key files.

## Backup and restore

Choose a new destination for each backup and each restoration. Keep independent dated backups. The backup uses SQLite's snapshot API, so committed changes in the write-ahead log are included while the application is running.

```powershell
.\.venv\Scripts\python.exe backup.py create .\backups\cash-2026-11-10.sqlite3
.\.venv\Scripts\python.exe backup.py restore .\backups\cash-2026-11-10.sqlite3 .\recovery\cash.sqlite3

```

Validate the restored copy, stop the application and backup worker, then set `MC_DB` to the recovered file and restart. The restore command creates a fresh file and refuses to overwrite an existing database. Use a local filesystem. On Linux and macOS, the destination filesystem must support hard links. Backups contain user password hashes, API token hashes, and cash records. Restrict their storage and transport. Use a fresh session key on recovery and revoke or reissue any API tokens whose previous state should not be restored.

### Optional MySQL backup

1. Install `requirements-mysql.txt` in the same virtual environment.
2. Create a dedicated MySQL database and service account. The account needs CREATE, SELECT, INSERT, UPDATE, and DELETE on that schema. Allow foreign-key creation if your server requires separate REFERENCES permission.
3. Set the `MC_MYSQL_*` variables in the sample configuration. For a remote server, specify the CA used to verify its TLS certificate and host identity. Give each independent SQLite database its own backup slot.
4. Run the worker in a separate terminal or service.

```powershell
.\.venv\Scripts\python.exe -m pip install -r requirements-mysql.txt
.\.venv\Scripts\python.exe mysql_backup.py once
.\.venv\Scripts\python.exe mysql_backup.py watch --interval 60

```

Each run takes a consistent snapshot and updates the slot in one InnoDB transaction. The previous complete snapshot remains recoverable until a new snapshot commits. A named MySQL lock serializes uploads and recoveries for the slot. The worker retries with a bounded backoff and logs failures. On reconnection, it copies the latest committed SQLite state, including changes made during the outage. Each slot keeps one snapshot. Keep dated SQLite backups for point-in-time recovery.

```powershell
.\.venv\Scripts\python.exe mysql_backup.py restore .\recovery\cash-from-mysql.sqlite3

```

Restore verifies chunk sequence, file size, SHA-256, and SQLite integrity before publishing the restored file. Activate it through `MC_DB` as described above.

## Test and package

```powershell
.\.venv\Scripts\python.exe -m unittest discover -s tests -v
.\.venv\Scripts\python.exe package.py

```

The optional MySQL integration test runs when `MC_RUN_MYSQL_TEST=1` and the backup settings point to a disposable database. It writes test snapshots. The source ZIP is written to `dist/money-counter.zip` and excludes runtime data and credentials.
