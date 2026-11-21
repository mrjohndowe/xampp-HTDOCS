import csv
import hashlib
import io
import os
import re
import sqlite3
import unittest
from contextlib import closing
from datetime import UTC, datetime, timedelta
from pathlib import Path
from tempfile import TemporaryDirectory
from unittest.mock import patch

from openpyxl import load_workbook
from werkzeug.security import generate_password_hash

import app
import backup
import cash
import mysql_backup
from demo import TILL_ENDING, TILL_OPENING

PASSWORD = "Test password, 2026!"
PASSWORD_HASH = generate_password_hash(PASSWORD)
TEST_DAY = "2026-11-10"


class MoneyCounterTests(unittest.TestCase):
    def setUp(self):
        self.directory = TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        self.path = Path(self.directory.name) / "app.sqlite3"
        self.app = app.create_app({
            "DATABASE": self.path, "TESTING": True, "SECRET_KEY": "a temporary test secret",
            "CORS_ORIGINS": {"https://example.test"}, "FRAME_ORIGINS": {"https://example.test"},
        })
        self.client = self.app.test_client()
        self.tokens = {}
        with cash.database(self.path, write=True) as conn:
            for role in app.ROLES:
                user = conn.execute("INSERT INTO users(username,password_hash,role) VALUES (?,?,?)",
                                    (role, PASSWORD_HASH, role)).lastrowid
                token = role[0] * 43
                expires = (datetime.now(UTC) + timedelta(hours=1)).isoformat()
                conn.execute("INSERT INTO tokens VALUES (?,?,?,?,?)",
                             (hashlib.sha256(token.encode()).hexdigest(), user, role, cash.stamp(), expires))
                self.tokens[role] = token

    def call(self, method, route, payload=None, *, role="operator", key=None):
        headers = {"Authorization": "Bearer " + self.tokens[role]}
        if key:
            headers["Idempotency-Key"] = key
        return self.client.open("/api/v1" + route, method=method, json=payload, headers=headers)

    def open_drawer(self, count=None):
        response = self.call("POST", "/drawers",
                             {"name": "Front", "business_date": TEST_DAY,
                              "quantities": count if count is not None else TILL_OPENING})
        self.assertEqual(response.status_code, 201, response.get_data(as_text=True))
        return response.json

    def test_all_denominations_and_roll_defaults(self):
        quantities = cash.quantities({code: {"loose": 1, **({"rolls": 1} if size else {})}
                                     for code, _, _, size in cash.DENOMINATIONS}, cash.DEFAULT_ROLLS)
        self.assertEqual(cash.total(quantities, cash.DEFAULT_ROLLS), 24241)
        self.assertEqual(cash.total(cash.quantities(TILL_OPENING, cash.DEFAULT_ROLLS), cash.DEFAULT_ROLLS), 377030)
        self.assertEqual(cash.total(cash.quantities(TILL_ENDING, cash.DEFAULT_ROLLS), cash.DEFAULT_ROLLS), 372580)
        self.assertEqual(cash.money(-4450), "-$44.50")
        for value in [-1, 0.5, True, "10", cash.MAX_QUANTITY + 1]:
            with self.subTest(value=value), self.assertRaises(cash.Problem):
                cash.quantities({"quarter": {"loose": value}}, cash.DEFAULT_ROLLS)
        with self.assertRaises(cash.Problem):
            cash.quantities({"bill_1": {"rolls": 1}}, cash.DEFAULT_ROLLS)

    def test_drawer_reconciliation_deposit_validation_and_history(self):
        drawer = self.open_drawer()
        base = "/drawers/" + str(drawer["id"])
        self.assertEqual(drawer["actual_cents"], 377030)
        invalid = self.call("POST", base + "/deposits", {"version": 1, "quantities": {"bill_1": {"loose": 21}}})
        self.assertEqual(invalid.status_code, 409)
        for version, (kind, amount) in enumerate([
            ("sale", 7500), ("payout", 3811), ("withdrawal", 200), ("addition", 1000), ("adjustment", -39),
        ], 1):
            response = self.call("POST", base + "/transactions",
                                 {"version": version, "kind": kind, "amount_cents": amount, "note": "Test"})
            self.assertEqual(response.status_code, 201, response.get_data(as_text=True))
        stale = self.call("PATCH", base + "/count", {"version": 1, "quantities": TILL_ENDING})
        self.assertEqual(stale.status_code, 409)
        recount = self.call("PATCH", base + "/count", {"version": 6, "quantities": TILL_ENDING})
        self.assertEqual(recount.json["actual_cents"], 372580)
        payload = {"version": 7, "quantities": {"bill_20": {"loose": 2}}, "note": "Bag 2"}
        deposit = self.call("POST", base + "/deposits", payload, key="bank-1")
        self.assertEqual(deposit.status_code, 201)
        self.assertEqual(deposit.json["actual_cents"], 368580)
        self.assertEqual(self.call("POST", base + "/deposits", payload, key="bank-1").json, deposit.json)
        self.assertEqual(self.call("POST", base + "/deposits", {**payload, "note": "New"}, key="bank-1").status_code, 409)
        close = self.call("POST", base + "/close", {"version": 8, "quantities": deposit.json["quantities"]})
        self.assertEqual(close.status_code, 200)
        self.assertEqual(close.json["expected_cents"], 377480)
        self.assertEqual(close.json["variance_cents"], -8900)
        self.assertTrue(close.json["closed_at"])
        self.assertEqual(self.call("PATCH", base + "/count", {"version": 9, "quantities": {}}).status_code, 409)
        self.assertEqual([row["kind"] for row in self.call("GET", base + "/counts").json["items"]],
                         ["opening", "current", "after_deposit", "closing"])
        self.assertEqual(len(self.call("GET", base + "/transactions").json["items"]), 6)
        self.assertEqual(len(self.call("GET", "/audit", role="admin").json["items"]), 10)

    def test_shopping_trip_payout_is_recorded_once_across_drawers(self):
        first = self.open_drawer({"bill_20": {"loose": 2}})
        second = self.call("POST", "/drawers", {"name": "Back", "quantities": {}}).json
        payout = {"kind": "payout", "amount_cents": 1234, "version": 1, "shopping_trip": "42"}
        saved = self.call("POST", f"/drawers/{first['id']}/transactions", payout)
        self.assertEqual(saved.status_code, 201)
        self.assertEqual(saved.json["expected_cents"], 2766)
        duplicate = self.call("POST", f"/drawers/{second['id']}/transactions", payout)
        self.assertEqual(duplicate.status_code, 409)
        self.assertIn("already been recorded", duplicate.json["error"])
        page = self.client.get("/?shopping_trip=42&amount=12.34", follow_redirects=True)
        csrf = re.search(r'name="csrf" value="([^"]+)"', page.get_data(as_text=True))[1]
        signed_in = self.client.post("/login", data={"username": "operator", "password": PASSWORD, "csrf": csrf})
        self.assertIn("shopping_trip=42", signed_in.location)
        self.assertIn("amount=12.34", signed_in.location)

    def test_roll_settings_preserve_opening_configuration(self):
        first = self.open_drawer({"quarter": {"rolls": 1}})
        rolls = {**cash.DEFAULT_ROLLS, "quarter": 50}
        self.assertEqual(self.call("PATCH", "/settings", {"roll_sizes": rolls}).status_code, 403)
        self.assertEqual(self.call("PATCH", "/settings", {"roll_sizes": rolls}, role="admin").status_code, 200)
        response = self.call("POST", "/drawers", {"name": "Back", "quantities": {"quarter": {"rolls": 1}}})
        self.assertEqual(response.json["actual_cents"], 1250)
        unchanged = self.call("GET", "/drawers/" + str(first["id"]))
        self.assertEqual(unchanged.json["actual_cents"], 1000)
        self.assertEqual(self.call("POST", "/drawers", {"name": "front", "quantities": {}}).status_code, 409)

    def test_authentication_roles_csrf_cors_and_logout(self):
        self.assertEqual(self.client.get("/api/v1/drawers").status_code, 401)
        self.assertEqual(self.client.get("/").status_code, 302)
        self.assertEqual(self.call("POST", "/drawers", {}, role="viewer").status_code, 403)
        self.assertEqual(self.call("GET", "/audit", role="viewer").status_code, 403)
        preflight = self.client.options("/api/v1/drawers", headers={"Origin": "https://example.test"})
        self.assertEqual(preflight.status_code, 204)
        self.assertEqual(preflight.headers["Access-Control-Allow-Origin"], "https://example.test")
        preflight = self.client.options("/api/v1/drawers", headers={"Origin": "https://other.test"})
        self.assertNotIn("Access-Control-Allow-Origin", preflight.headers)
        page = self.client.get("/login")
        self.assertIn("frame-ancestors 'self' https://example.test", page.headers["Content-Security-Policy"])
        csrf = re.search(r'name="csrf" value="([^"]+)"', page.get_data(as_text=True))[1]
        response = self.client.post("/login", data={"username": "admin", "password": PASSWORD, "csrf": csrf},
                                    follow_redirects=True)
        self.assertIn(b"Cash overview", response.data)
        csrf = re.search(r'name="csrf-token" content="([^"]+)"', response.get_data(as_text=True))[1]
        self.assertEqual(self.client.post("/api/v1/drawers", json={}).status_code, 403)
        created = self.client.post("/api/v1/drawers", json={"name": "Web", "quantities": {}},
                                   headers={"X-CSRF-Token": csrf})
        self.assertEqual(created.status_code, 201)
        cookie = self.client.get_cookie("mc_session").value
        self.assertEqual(self.client.post("/logout", data={"csrf": csrf}).status_code, 303)
        self.client.set_cookie("mc_session", cookie)
        self.assertEqual(self.client.get("/api/v1/drawers").status_code, 401)

    def test_reports_and_formula_safe_exports(self):
        entry = self.open_drawer()
        base = "/drawers/" + str(entry["id"])
        self.call("POST", base + "/transactions",
                  {"kind": "sale", "amount_cents": 7500, "version": 1, "note": "=1+2"})
        query = "?period=day&end=" + TEST_DAY
        result = self.call("GET", "/reports" + query).json
        self.assertEqual(result["drawer_count"], 1)
        self.assertEqual(result["daily"][TEST_DAY]["sale"], 7500)
        self.assertEqual(self.call("GET", "/reports?period=week&end=" + TEST_DAY).json["start"], "2026-11-09")
        self.assertEqual(self.call("GET", "/reports?period=month&end=" + TEST_DAY).json["end"], "2026-11-30")
        self.assertEqual(self.call("GET", "/reports" + query + "&movement=deposit").json["transactions"], [])
        csv_result = self.call("GET", "/reports/export/csv" + query)
        rows = list(csv.reader(io.StringIO(csv_result.data.decode("utf-8-sig"))))
        self.assertIn(["1", "Front", TEST_DAY, "sale", "75.00", "'=1+2", "operator"], rows)
        xlsx_result = self.call("GET", "/reports/export/xlsx" + query)
        book = load_workbook(io.BytesIO(xlsx_result.data))
        self.assertEqual(book.sheetnames, ["Drawers", "Movements", "Denominations"])
        self.assertEqual(book["Movements"]["E2"].value, 75)
        self.assertEqual(book["Movements"]["F2"].value, "=1+2")
        self.assertEqual(book["Movements"]["F2"].data_type, "s")
        self.assertTrue(self.call("GET", "/reports/export/pdf" + query).data.startswith(b"%PDF"))
        self.assertIn(b"Print report", self.call("GET", "/reports/export/html" + query).data)

    def test_sqlite_backup_restore_and_live_wal(self):
        self.open_drawer()
        directory = self.path.parent
        with closing(sqlite3.connect(self.path)) as source:
            source.execute("PRAGMA wal_autocheckpoint=0")
            source.execute("UPDATE drawers SET name='WAL test'")
            source.commit()
            saved = backup.publish(self.path, directory / "backup.sqlite3")
            restored = backup.publish(saved, directory / "restored.sqlite3")
        with cash.database(restored) as conn:
            self.assertEqual(cash.snapshot(conn, 1)["name"], "WAL test")
        with self.assertRaises(FileExistsError):
            backup.publish(saved, restored)
        with self.assertRaises(sqlite3.Error):
            backup.publish(directory / "missing.sqlite3", directory / "should-not-exist.sqlite3")

    def test_configured_mount_path(self):
        with patch.dict(os.environ, {"MC_BASE_PATH": "/tools/cash"}):
            mounted = app.create_app({"DATABASE": self.path, "SECRET_KEY": "temporary"})
        client = mounted.test_client()
        page = client.get("/tools/cash/login")
        self.assertEqual(page.status_code, 200)
        self.assertIn(b"/tools/cash/static/app.css", page.data)
        self.assertIn("Path=/tools/cash", page.headers["Set-Cookie"])
        self.assertEqual(client.get("/login").status_code, 404)

    @unittest.skipUnless(os.getenv("MC_RUN_MYSQL_TEST") == "1", "requires a disposable MySQL database")
    def test_mysql_backup_and_recovery(self):
        self.open_drawer()
        options = mysql_backup.connection_options()
        slot = "test_" + os.urandom(8).hex()
        self.assertTrue(mysql_backup.synchronize(self.path, options, slot))
        self.assertFalse(mysql_backup.synchronize(self.path, options, slot))
        restored = mysql_backup.recover(options, slot, self.path.parent / "mysql-restored.sqlite3")
        with cash.database(restored) as conn:
            self.assertEqual(cash.snapshot(conn, 1)["opening_cents"], 377030)
        self.call("POST", "/drawers/1/transactions", {"kind": "sale", "amount_cents": 12, "version": 1})
        with patch.object(mysql_backup, "CHUNK_SIZE", object()), self.assertRaises(TypeError):
            mysql_backup.synchronize(self.path, options, slot)
        previous = mysql_backup.recover(options, slot, self.path.parent / "mysql-interrupted.sqlite3")
        with cash.database(previous) as conn:
            self.assertEqual(cash.snapshot(conn, 1)["expected_cents"], 377030)
        self.assertTrue(mysql_backup.synchronize(self.path, options, slot))
        second = mysql_backup.recover(options, slot, self.path.parent / "mysql-second.sqlite3")
        with cash.database(second) as conn:
            self.assertEqual(cash.snapshot(conn, 1)["expected_cents"], 377042)


if __name__ == "__main__":
    unittest.main()
