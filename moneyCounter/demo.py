"""Start a disposable, loopback-only demo populated with sample drawer sessions."""

from datetime import datetime, timedelta
from pathlib import Path
from tempfile import TemporaryDirectory

from werkzeug.security import generate_password_hash

import cash

DEMO_USER = "demo"
DEMO_PASSWORD = "CashCounterDemo2026"
TILL_OPENING = {
    "penny": {"loose": 30}, "nickel": {"loose": 20}, "dime": {"loose": 20},
    "quarter": {"loose": 20}, "half": {"loose": 4},
    **{code: {"loose": 20} for code, _, _, size in cash.DENOMINATIONS if size is None},
}
TILL_ENDING = {**TILL_OPENING, "quarter": {"loose": 18}, "bill_1": {"loose": 15},
               "bill_2": {"loose": 18}, "bill_5": {"loose": 19},
               "bill_10": {"loose": 19}, "bill_20": {"loose": 19}}


def seed(path, today):
    with cash.database(path, write=True) as conn:
        user = conn.execute("INSERT INTO users(username,password_hash,role) VALUES (?,?,?)",
                            (DEMO_USER, generate_password_hash(DEMO_PASSWORD), "admin")).lastrowid
        cash.audit(conn, user, "demo_seed", {})
        for offset, sales, payout in [(3, 22400, 3400), (2, 38000, 2350), (1, 29400, 3811), (0, 28000, 3811)]:
            day = (today - timedelta(days=offset)).isoformat()
            count = cash.open_drawer(conn, user, {"name": "Main till", "business_date": day,
                                                   "quantities": TILL_OPENING}, day)
            drawer_id = count["id"]
            for kind, payload in [
                ("sale", {"amount_cents": sales, "note": "Cash receipts"}),
                ("payout", {"amount_cents": payout, "note": "Operating expense"}),
                ("current", {"quantities": {**TILL_OPENING, "bill_20": {"loose": 30},
                                              "quarter": {"loose": 20, "rolls": 2}}}),
                ("deposit", {"quantities": {"bill_20": {"loose": 15}}, "note": "Bank deposit"}),
            ]:
                count = cash.change_drawer(conn, user, drawer_id, kind, {"version": count["version"], **payload})
            if offset:
                cash.change_drawer(conn, user, drawer_id, "closing",
                                   {"version": count["version"], "quantities": TILL_ENDING})


def run():
    from app import create_app, run_server

    with TemporaryDirectory(prefix="moneycounter-demo-") as temp:
        path = Path(temp) / "demo.sqlite3"
        app = create_app({"DATABASE": path, "SESSION_COOKIE_SECURE": False, "SESSION_COOKIE_SAMESITE": "Lax"})
        seed(path, datetime.now(app.config["TIMEZONE"]).date())
        print(f"Disposable demo. Username {DEMO_USER} / Password {DEMO_PASSWORD}", flush=True)
        run_server(app, host="127.0.0.1", port=8001, development=True)
