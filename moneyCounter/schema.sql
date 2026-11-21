CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin', 'operator', 'viewer'))
);
CREATE TABLE IF NOT EXISTS tokens (
    digest TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    label TEXT NOT NULL,
    created_at TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    UNIQUE(user_id, label)
);
CREATE TABLE IF NOT EXISTS login_limits (
    address TEXT PRIMARY KEY,
    started_at INTEGER NOT NULL,
    attempts INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS web_sessions (
    digest TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    expires_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS session_expiry_idx ON web_sessions(expires_at);
CREATE TABLE IF NOT EXISTS roll_sizes (
    code TEXT PRIMARY KEY,
    quantity INTEGER NOT NULL CHECK (quantity BETWEEN 1 AND 1000)
);
CREATE TABLE IF NOT EXISTS drawers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    business_date TEXT NOT NULL,
    opened_at TEXT NOT NULL,
    closed_at TEXT,
    roll_sizes TEXT NOT NULL,
    version INTEGER NOT NULL DEFAULT 1
);
CREATE INDEX IF NOT EXISTS drawer_date_idx ON drawers(business_date, id);
CREATE UNIQUE INDEX IF NOT EXISTS one_open_drawer_per_name
    ON drawers(lower(name)) WHERE closed_at IS NULL;
CREATE TABLE IF NOT EXISTS counts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    drawer_id INTEGER NOT NULL REFERENCES drawers(id),
    kind TEXT NOT NULL CHECK (kind IN ('opening','current','after_deposit','closing')),
    quantities TEXT NOT NULL,
    total_cents INTEGER NOT NULL CHECK (total_cents >= 0),
    user_id INTEGER NOT NULL REFERENCES users(id),
    created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS counts_drawer_idx ON counts(drawer_id, id);
CREATE TABLE IF NOT EXISTS ledger (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    drawer_id INTEGER NOT NULL REFERENCES drawers(id),
    kind TEXT NOT NULL CHECK (kind IN ('deposit','sale','payout','withdrawal','addition','adjustment')),
    amount_cents INTEGER NOT NULL CHECK (amount_cents != 0),
    quantities TEXT,
    note TEXT NOT NULL,
    user_id INTEGER NOT NULL REFERENCES users(id),
    created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS shopping_payouts (
    trip_reference TEXT PRIMARY KEY NOT NULL,
    ledger_id INTEGER NOT NULL UNIQUE REFERENCES ledger(id)
);
CREATE INDEX IF NOT EXISTS ledger_drawer_idx ON ledger(drawer_id, id);
CREATE TABLE IF NOT EXISTS audit (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id),
    drawer_id INTEGER REFERENCES drawers(id),
    action TEXT NOT NULL,
    detail TEXT NOT NULL,
    created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS requests (
    user_id INTEGER NOT NULL REFERENCES users(id),
    request_key TEXT NOT NULL,
    fingerprint TEXT NOT NULL,
    response TEXT NOT NULL,
    PRIMARY KEY(user_id, request_key)
);
