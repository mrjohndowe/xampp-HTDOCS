"""USD denomination arithmetic and transactional SQLite drawer operations."""

import json
import re
import sqlite3
from contextlib import closing, contextmanager
from datetime import UTC, date, datetime
from pathlib import Path

DENOMINATIONS = (
    ("penny", "Penny", 1, 50),
    ("nickel", "Nickel", 5, 40),
    ("dime", "Dime", 10, 50),
    ("quarter", "Quarter", 25, 40),
    ("half", "Half dollar", 50, 20),
    ("dollar_coin", "Dollar coin", 100, 25),
    ("bill_1", "$1 bill", 100, None),
    ("bill_2", "$2 bill", 200, None),
    ("bill_5", "$5 bill", 500, None),
    ("bill_10", "$10 bill", 1000, None),
    ("bill_20", "$20 bill", 2000, None),
    ("bill_50", "$50 bill", 5000, None),
    ("bill_100", "$100 bill", 10000, None),
)
DEFAULT_ROLLS = {code: size for code, _, _, size in DENOMINATIONS if size}
MOVEMENTS = {"sale": 1, "payout": -1, "withdrawal": -1, "addition": 1, "adjustment": 1}
MAX_QUANTITY = 999_999
MAX_AMOUNT = 99_999_999_999


class Problem(Exception):
    def __init__(self, message, status=400):
        super().__init__(message)
        self.status = status


def stamp():
    return datetime.now(UTC).isoformat(timespec="microseconds")


def encode(value):
    return json.dumps(value, sort_keys=True, separators=(",", ":"))


def money(cents):
    sign = "-" if cents < 0 else ""
    dollars, pennies = divmod(abs(cents), 100)
    return f"{sign}${dollars:,}.{pennies:02d}"


def text_value(value, label, limit, *, required=False):
    if not isinstance(value, str) or len(value.strip()) > limit or (required and not value.strip()):
        raise Problem(f"{label} must be {'1' if required else '0'} to {limit} characters.")
    if any(ord(character) < 32 for character in value.strip()):
        raise Problem(f"{label} must be a single line.")
    return value.strip()


def integer(value, low, high, label):
    if type(value) is not int or not low <= value <= high:
        raise Problem(f"{label} must be a whole number between {low:,} and {high:,}.")
    return value


def parse_date(value):
    try:
        if not isinstance(value, str) or date.fromisoformat(value).isoformat() != value:
            raise ValueError
    except ValueError:
        raise Problem("Use YYYY-MM-DD for dates.") from None
    return value


def quantities(raw, rolls):
    if not isinstance(raw, dict) or set(raw) - {row[0] for row in DENOMINATIONS}:
        raise Problem("Provide quantities keyed by supported denomination codes.")
    result = {}
    for code, label, _, _ in DENOMINATIONS:
        entry = raw.get(code, {})
        allowed = {"loose", "rolls"} if code in rolls else {"loose"}
        if not isinstance(entry, dict) or entry.keys() - allowed:
            raise Problem(f"Invalid quantity fields for {label}.")
        result[code] = {field: integer(entry.get(field, 0), 0, MAX_QUANTITY, label)
                        for field in sorted(allowed)}
    return result


def total(count, rolls):
    return sum(value * (count[code]["loose"] + count[code].get("rolls", 0) * rolls.get(code, 0))
               for code, _, value, _ in DENOMINATIONS)


@contextmanager
def database(path, *, write=False):
    connection = sqlite3.connect(Path(path).resolve().as_uri() + "?mode=rw",
                                 uri=True, timeout=10, isolation_level=None)
    connection.row_factory = sqlite3.Row
    try:
        connection.execute("PRAGMA foreign_keys=ON")
        connection.execute("BEGIN IMMEDIATE" if write else "BEGIN")
        yield connection
        connection.commit()
    except BaseException:
        connection.rollback()
        raise
    finally:
        connection.close()


def initialize(path):
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    with closing(sqlite3.connect(path, timeout=10)) as conn, conn:
        conn.execute("PRAGMA journal_mode=WAL")
        schema = Path(__file__).with_name("schema.sql").read_text(encoding="utf-8")
        conn.executescript("BEGIN IMMEDIATE;\n" + schema)
        conn.executemany("INSERT OR IGNORE INTO roll_sizes VALUES (?, ?)", DEFAULT_ROLLS.items())


def settings(conn):
    return dict(conn.execute("SELECT code, quantity FROM roll_sizes").fetchall())


def audit(conn, user, action, detail, drawer=None):
    conn.execute("INSERT INTO audit(user_id, drawer_id, action, detail, created_at) VALUES (?, ?, ?, ?, ?)",
                 (user, drawer, action, encode(detail), stamp()))


def save_settings(conn, user, payload):
    rolls = payload.get("roll_sizes")
    if not isinstance(rolls, dict) or rolls.keys() != DEFAULT_ROLLS.keys():
        raise Problem("Provide sizes for all six coin rolls.")
    rolls = {code: integer(size, 1, 1000, code) for code, size in rolls.items()}
    previous = settings(conn)
    conn.executemany("UPDATE roll_sizes SET quantity=? WHERE code=?",
                     [(size, code) for code, size in rolls.items()])
    audit(conn, user, "roll_settings", {"before": previous, "after": rolls})
    return {"roll_sizes": rolls}


def drawer(conn, drawer_id):
    if not 1 <= drawer_id < 2**63:
        raise Problem("Drawer not found.", 404)
    result = conn.execute("SELECT * FROM drawers WHERE id=?", (drawer_id,)).fetchone()
    if result is None:
        raise Problem("Drawer not found.", 404)
    return dict(result)


def save_count(conn, drawer_id, kind, count, rolls, user):
    cursor = conn.execute(
        "INSERT INTO counts(drawer_id, kind, quantities, total_cents, user_id, created_at)"
        " VALUES (?, ?, ?, ?, ?, ?)", (drawer_id, kind, encode(count), total(count, rolls), user, stamp()))
    audit(conn, user, "count_" + kind, {"count_id": cursor.lastrowid}, drawer_id)


def open_drawer(conn, user, payload, today):
    name = text_value(payload.get("name"), "Drawer name", 80, required=True)
    business_date = parse_date(payload.get("business_date", today))
    rolls = settings(conn)
    count = quantities(payload.get("quantities"), rolls)
    if conn.execute("SELECT 1 FROM drawers WHERE lower(name)=lower(?) AND closed_at IS NULL",
                    (name,)).fetchone():
        raise Problem("That drawer already has an open session.", 409)
    cursor = conn.execute(
        "INSERT INTO drawers(name, business_date, opened_at, roll_sizes) VALUES (?, ?, ?, ?)",
        (name, business_date, stamp(), encode(rolls)))
    save_count(conn, cursor.lastrowid, "opening", count, rolls, user)
    return snapshot(conn, cursor.lastrowid)


def records(conn, table, drawer_id):
    if table not in {"counts", "ledger"}:
        raise ValueError("Unsupported history table.")
    rows = conn.execute(
        f"SELECT r.*, u.username FROM {table} r JOIN users u ON u.id=r.user_id"
        " WHERE r.drawer_id=? ORDER BY r.id", (drawer_id,))
    return [dict(row, quantities=json.loads(row["quantities"]) if row["quantities"] else None)
            for row in rows]


def snapshot(conn, drawer_id, *, history=False):
    result = drawer(conn, drawer_id)
    counts = conn.execute(
        "SELECT * FROM counts WHERE drawer_id=? ORDER BY id LIMIT 1", (drawer_id,)).fetchone()
    latest = conn.execute(
        "SELECT * FROM counts WHERE drawer_id=? ORDER BY id DESC LIMIT 1", (drawer_id,)).fetchone()
    by_kind = dict.fromkeys([*MOVEMENTS, "deposit"], 0)
    by_kind.update(dict(conn.execute(
        "SELECT kind, SUM(amount_cents) FROM ledger WHERE drawer_id=? GROUP BY kind", (drawer_id,))))
    expected = counts["total_cents"] + sum(by_kind[kind] * sign for kind, sign in MOVEMENTS.items())
    expected -= by_kind["deposit"]
    actual = latest["total_cents"]
    result.update(roll_sizes=json.loads(result["roll_sizes"]), opening_cents=counts["total_cents"],
                  expected_cents=expected, actual_cents=actual, variance_cents=actual - expected,
                  totals=by_kind, quantities=json.loads(latest["quantities"]),
                  counted_at=latest["created_at"], count_kind=latest["kind"])
    if history:
        result.update(counts=records(conn, "counts", drawer_id),
                      transactions=records(conn, "ledger", drawer_id))
    return result


def change_drawer(conn, user, drawer_id, action, payload):
    reference = payload.get("shopping_trip")
    if reference is not None:
        if action != "payout" or not isinstance(reference, str) or not re.fullmatch(r"[1-9][0-9]{0,19}", reference):
            raise Problem("Invalid Shopping Manager trip reference.")
        if conn.execute("SELECT 1 FROM shopping_payouts WHERE trip_reference=?", (reference,)).fetchone():
            raise Problem("A payout has already been recorded for this Shopping Manager trip.", 409)
    current = snapshot(conn, drawer_id)
    if current["closed_at"]:
        raise Problem("This drawer is closed.", 409)
    version = integer(payload.get("version"), 1, 2**53 - 1, "Drawer version")
    if version != current["version"]:
        raise Problem("The drawer changed. Refresh before saving.", 409)
    rolls = current["roll_sizes"]
    if action in {"current", "closing"}:
        count = quantities(payload.get("quantities"), rolls)
        save_count(conn, drawer_id, action, count, rolls, user)
        if action == "closing":
            conn.execute("UPDATE drawers SET closed_at=? WHERE id=?", (stamp(), drawer_id))
    else:
        note = text_value(payload.get("note", ""), "Note", 200, required=action == "adjustment")
        count = None
        if action == "deposit":
            count = quantities(payload.get("quantities"), rolls)
            amount = total(count, rolls)
            if not amount:
                raise Problem("Enter at least one denomination for the deposit.")
            remaining = current["quantities"]
            labels = {code: label for code, label, _, _ in DENOMINATIONS}
            for code, fields in count.items():
                for field, quantity in fields.items():
                    if quantity > remaining[code][field]:
                        unit = "rolls" if field == "rolls" else "loose units"
                        raise Problem(f"{labels[code]} deposit exceeds the available {unit}.", 409)
                    remaining[code][field] -= quantity
            save_count(conn, drawer_id, "after_deposit", remaining, rolls, user)
        elif action in MOVEMENTS:
            low = -MAX_AMOUNT if action == "adjustment" else 1
            amount = integer(payload.get("amount_cents"), low, MAX_AMOUNT, "Amount in cents")
            if amount == 0:
                raise Problem("Enter a nonzero adjustment.")
        else:
            raise Problem("Unsupported transaction type.")
        cursor = conn.execute(
            "INSERT INTO ledger(drawer_id, kind, amount_cents, quantities, note, user_id, created_at)"
            " VALUES (?, ?, ?, ?, ?, ?, ?)",
            (drawer_id, action, amount, encode(count) if count else None, note, user, stamp()))
        if reference is not None:
            conn.execute("INSERT INTO shopping_payouts VALUES (?, ?)", (reference, cursor.lastrowid))
        audit(conn, user, action, {"transaction_id": cursor.lastrowid}, drawer_id)
    conn.execute("UPDATE drawers SET version=version+1 WHERE id=?", (drawer_id,))
    return snapshot(conn, drawer_id)
