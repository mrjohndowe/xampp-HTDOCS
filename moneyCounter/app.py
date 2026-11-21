"""Flask entry point, web sessions, and the versioned JSON API."""

import argparse
import getpass
import hashlib
import hmac
import json
import logging
import os
import re
import secrets
import sqlite3
import time
from datetime import UTC, datetime, timedelta
from pathlib import Path
from zoneinfo import ZoneInfo

from flask import (Flask, abort, g, jsonify, redirect, render_template, request,
                   session, url_for)
from werkzeug.exceptions import HTTPException
from werkzeug.middleware.dispatcher import DispatcherMiddleware
from werkzeug.security import check_password_hash, generate_password_hash
from werkzeug.wrappers import Response

import cash
import config
import reporting

ROLES = ("admin", "operator", "viewer")
DUMMY_HASH = generate_password_hash(secrets.token_hex(32))


def digest(value):
    return hashlib.sha256(value.encode()).hexdigest()


def session_key(path):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.touch(exist_ok=True, mode=0o600)
    # Serialize first launch across workers, including workers on Windows.
    with cash.database(path, write=True) as conn:
        conn.execute("CREATE TABLE IF NOT EXISTS server_secret (value TEXT NOT NULL)")
        row = conn.execute("SELECT value FROM server_secret").fetchone()
        if row:
            return row[0]
        key = secrets.token_hex(32)
        conn.execute("INSERT INTO server_secret VALUES (?)", (key,))
        return key


def create_app(overrides=None):
    config.load_environment()
    app = Flask(__name__)
    base = os.getenv("MC_BASE_PATH", "").rstrip("/")
    if base and not re.fullmatch(r"(?:/[a-zA-Z0-9_-]+)+", base):
        raise ValueError("MC_BASE_PATH must be a URL path such as /cash.")
    secure = config.enabled("MC_SECURE_COOKIES")
    cross_site = config.enabled("MC_CROSS_SITE_COOKIE")
    frames = config.origins("MC_FRAME_ORIGINS")
    if cross_site and (not secure or not frames):
        raise ValueError("Cross-site cookies require HTTPS and explicit frame origins.")
    app.config.update(
        DATABASE=config.db_path(), TIMEZONE=ZoneInfo(os.getenv("MC_TIMEZONE", "America/Denver")),
        CORS_ORIGINS=config.origins("MC_CORS_ORIGINS"), FRAME_ORIGINS=frames,
        ENABLE_API_TOKENS=config.enabled("MC_ENABLE_API_TOKENS", "1"),
        MAX_CONTENT_LENGTH=64 * 1024, SESSION_COOKIE_NAME="mc_session",
        SESSION_COOKIE_HTTPONLY=True, SESSION_COOKIE_SECURE=secure,
        SESSION_COOKIE_SAMESITE="None" if cross_site else "Lax",
        SESSION_COOKIE_PATH=base or "/", PERMANENT_SESSION_LIFETIME=timedelta(hours=8),
        SESSION_REFRESH_EACH_REQUEST=False,
    )
    app.config.update(overrides or {})
    cash.initialize(app.config["DATABASE"])
    if not app.secret_key:
        app.secret_key = os.getenv("MC_SECRET_KEY") or session_key(
            Path(app.config["DATABASE"]).with_suffix(".sessions.sqlite3"))
    app.jinja_env.filters["money"] = cash.money
    app.jinja_env.filters["localtime"] = lambda value: datetime.fromisoformat(value).astimezone(
        app.config["TIMEZONE"]).strftime("%b %d, %Y · %H:%M %Z")
    if base:
        def outside(environ, start_response):
            if environ["PATH_INFO"] in {"/", base}:
                return Response(status=302, headers={"Location": base + "/"})(environ, start_response)
            return Response("Not found", status=404)(environ, start_response)
        app.wsgi_app = DispatcherMiddleware(outside, {base: app.wsgi_app})

    def db(write=False):
        return cash.database(app.config["DATABASE"], write=write)

    def today():
        return datetime.now(app.config["TIMEZONE"]).date().isoformat()

    def api():
        return request.path.startswith("/api/v1/")

    def token():
        if "csrf" not in session:
            session["csrf"] = secrets.token_urlsafe(32)
        return session["csrf"]

    @app.context_processor
    def context():
        return {"csrf": token, "user": g.get("user")}

    @app.before_request
    def authenticate():
        g.user = None
        g.bearer = False
        if api() and request.method == "OPTIONS":
            return "", 204
        if request.endpoint == "static":
            return None
        header = request.headers.get("Authorization")
        if api() and header is not None:
            value = header.removeprefix("Bearer ")
            if (not app.config["ENABLE_API_TOKENS"] or value == header
                    or not re.fullmatch(r"[A-Za-z0-9_-]{43}", value)):
                raise cash.Problem("Invalid API token.", 401)
            with db() as conn:
                g.user = conn.execute(
                    "SELECT u.* FROM users u JOIN tokens t ON t.user_id=u.id"
                    " WHERE t.digest=? AND t.expires_at>?", (digest(value), cash.stamp())).fetchone()
            if g.user is None:
                raise cash.Problem("Invalid or expired API token.", 401)
            g.bearer = True
        elif isinstance(session.get("session_id"), str):
            with db() as conn:
                g.user = conn.execute(
                    "SELECT u.* FROM users u JOIN web_sessions s ON s.user_id=u.id"
                    " WHERE s.digest=? AND s.expires_at>?",
                    (digest(session["session_id"]), cash.stamp())).fetchone()
        if request.endpoint not in {None, "login"} and not g.user:
            if api():
                raise cash.Problem("Sign in or provide a valid API token.", 401)
            trip = request.args.get("shopping_trip", "")
            amount = request.args.get("amount", "")
            if (request.endpoint == "workspace" and request.view_args == {"view": "overview"}
                    and re.fullmatch(r"[1-9][0-9]{0,19}", trip)
                    and re.fullmatch(r"[0-9]{1,9}\.[0-9]{2}", amount)):
                session["shopping_payout"] = {"shopping_trip": trip, "amount": amount}
            return redirect(url_for("login"))
        if request.method not in {"GET", "HEAD", "OPTIONS"} and not g.bearer:
            supplied = request.headers.get("X-CSRF-Token") if api() else request.form.get("csrf")
            expected = session.get("csrf")
            if not supplied or not expected or not hmac.compare_digest(supplied.encode(), expected.encode()):
                raise cash.Problem("Your session token is missing or expired. Reload the page.", 403)
        return None

    @app.after_request
    def headers(response):
        ancestors = sorted(app.config["FRAME_ORIGINS"])
        response.headers["Content-Security-Policy"] = (
            "default-src 'self'; base-uri 'none'; object-src 'none'; form-action 'self';"
            " frame-ancestors 'self'" + (" " + " ".join(ancestors) if ancestors else ""))
        if not ancestors:
            response.headers["X-Frame-Options"] = "SAMEORIGIN"
        response.headers["X-Content-Type-Options"] = "nosniff"
        response.headers["Referrer-Policy"] = "same-origin"
        if request.endpoint != "static":
            response.headers["Cache-Control"] = "no-store"
        if api():
            response.vary.add("Origin")
            origin = request.headers.get("Origin")
            if origin and origin in app.config["CORS_ORIGINS"]:
                response.headers["Access-Control-Allow-Origin"] = origin
                response.headers["Access-Control-Allow-Methods"] = "GET, POST, PATCH, OPTIONS"
                response.headers["Access-Control-Allow-Headers"] = "Authorization, Content-Type, Idempotency-Key"
                response.headers["Access-Control-Expose-Headers"] = "Content-Disposition"
        return response

    @app.errorhandler(cash.Problem)
    @app.errorhandler(HTTPException)
    def known_error(error):
        status = error.status if isinstance(error, cash.Problem) else error.code
        message = str(error) if isinstance(error, cash.Problem) else error.description
        return (jsonify(error=message) if api() else render_template("error.html", message=message)), status

    @app.errorhandler(sqlite3.OperationalError)
    def sqlite_error(error):
        app.logger.exception("SQLite operation failed")
        busy = getattr(error, "sqlite_errorcode", None) in {sqlite3.SQLITE_BUSY, sqlite3.SQLITE_LOCKED}
        return known_error(cash.Problem("The database is temporarily busy. Retry shortly." if busy
                                       else "The database operation failed.", 503 if busy else 500))

    @app.route("/login", methods=["GET", "POST"])
    def login():
        if g.user:
            return redirect(url_for("workspace"))
        if request.method == "GET":
            return render_template("login.html")
        username = request.form.get("username", "").strip().lower()
        password = request.form.get("password", "")
        if len(username) > 64 or len(password) > 256:
            return render_template("login.html", error="Invalid username or password."), 401
        now = int(time.time())
        address = request.remote_addr or "unknown"
        with db(write=True) as conn:
            conn.execute("DELETE FROM login_limits WHERE started_at<=?", (now - 300,))
            row = conn.execute("SELECT attempts FROM login_limits WHERE address=?", (address,)).fetchone()
            if row and row["attempts"] >= 20:
                raise cash.Problem("Too many sign-in attempts. Try again in five minutes.", 429)
            conn.execute(
                "INSERT INTO login_limits VALUES (?, ?, 1)"
                " ON CONFLICT(address) DO UPDATE SET attempts=attempts+1", (address, now))
            user = conn.execute("SELECT * FROM users WHERE username=?", (username,)).fetchone()
        valid = check_password_hash(user["password_hash"] if user else DUMMY_HASH, password)
        if not valid or user is None:
            app.logger.info("Sign-in rejected from %s", address)
            return render_template("login.html", error="Invalid username or password."), 401
        with db(write=True) as conn:
            conn.execute("DELETE FROM login_limits WHERE address=?", (address,))
            conn.execute("DELETE FROM web_sessions WHERE expires_at<=?", (cash.stamp(),))
            session_id = secrets.token_urlsafe(32)
            expiry = (datetime.now(UTC) + app.permanent_session_lifetime).isoformat(timespec="microseconds")
            conn.execute("INSERT INTO web_sessions VALUES (?, ?, ?)", (digest(session_id), user["id"], expiry))
        shopping_payout = session.get("shopping_payout", {})
        session.clear()
        session["session_id"] = session_id
        session.permanent = True
        return redirect(url_for("workspace", **shopping_payout), code=303)

    @app.post("/logout")
    def logout():
        with db(write=True) as conn:
            conn.execute("DELETE FROM web_sessions WHERE digest=?", (digest(session["session_id"]),))
        session.clear()
        return redirect(url_for("login"), code=303)

    @app.route("/", defaults={"view": "overview"})
    @app.get("/workspace/<view>")
    def workspace(view):
        if view not in {"overview", "count", "activity", "settings"}:
            abort(404)
        if view == "settings" and g.user["role"] != "admin":
            abort(403)
        return render_template("workspace.html", view=view, today=today(),
                               denominations=cash.DENOMINATIONS)

    @app.get("/reports")
    def reports_page():
        with db() as conn:
            report = reporting.build(conn, request.args, today())
        return render_template("reports.html", report=report)

    def allowed_write(admin=False):
        if g.user["role"] == "viewer" or (admin and g.user["role"] != "admin"):
            raise cash.Problem("Your role cannot perform this action.", 403)

    def mutate(handler, status=200, *, admin=False):
        allowed_write(admin)
        if not request.is_json:
            raise cash.Problem("Send an application/json request.", 415)
        payload = request.get_json()
        if not isinstance(payload, dict):
            raise cash.Problem("Send a JSON object.")
        key = request.headers.get("Idempotency-Key")
        if key and not re.fullmatch(r"[a-zA-Z0-9._-]{1,80}", key):
            raise cash.Problem("Invalid Idempotency-Key.")
        user_id = g.user["id"]
        fingerprint = digest(cash.encode([request.method, request.path, payload]))
        with db(write=True) as conn:
            if key:
                prior = conn.execute(
                    "SELECT fingerprint, response FROM requests WHERE user_id=? AND request_key=?",
                    (user_id, key)).fetchone()
                if prior:
                    if prior["fingerprint"] != fingerprint:
                        raise cash.Problem("That idempotency key belongs to a different request.", 409)
                    return jsonify(json.loads(prior["response"])), status
            result = handler(conn, user_id, payload)
            if key:
                conn.execute("INSERT INTO requests VALUES (?, ?, ?, ?)",
                             (user_id, key, fingerprint, cash.encode(result)))
        return jsonify(result), status

    @app.get("/api/v1/settings")
    def get_settings():
        with db() as conn:
            return jsonify(roll_sizes=cash.settings(conn))

    @app.get("/api/v1/audit")
    def audit_history():
        if g.user["role"] != "admin":
            abort(403)
        before = cursor_value()
        with db() as conn:
            rows = conn.execute(
                "SELECT a.*, u.username FROM audit a JOIN users u ON u.id=a.user_id"
                " WHERE a.id<? ORDER BY a.id DESC LIMIT 51", (before,)).fetchall()
        return jsonify(items=[dict(row, detail=json.loads(row["detail"])) for row in rows[:50]],
                       next_before=rows[49]["id"] if len(rows) > 50 else None)

    @app.patch("/api/v1/settings")
    def update_settings():
        return mutate(cash.save_settings, admin=True)

    def cursor_value():
        before = request.args.get("before", str(2**63 - 1))
        if not re.fullmatch(r"[0-9]{1,19}", before) or not 1 <= int(before) <= 2**63 - 1:
            raise cash.Problem("Invalid pagination cursor.")
        return int(before)

    @app.get("/api/v1/drawers")
    def drawers():
        before = cursor_value()
        status = request.args.get("status", "all")
        filters = {"open": " AND closed_at IS NULL", "closed": " AND closed_at IS NOT NULL", "all": ""}
        if status not in filters:
            raise cash.Problem("Use open, closed, or all for status.")
        with db() as conn:
            rows = conn.execute("SELECT id, name, business_date, closed_at FROM drawers WHERE id<?"
                                + filters[status] + " ORDER BY id DESC LIMIT 51", (before,)).fetchall()
        return jsonify(drawers=[dict(row) for row in rows[:50]],
                       next_before=rows[49]["id"] if len(rows) > 50 else None)

    @app.post("/api/v1/drawers")
    def add_drawer():
        return mutate(lambda conn, user, payload: cash.open_drawer(conn, user, payload, today()), 201)

    @app.get("/api/v1/drawers/<int:drawer_id>")
    def get_drawer(drawer_id):
        with db() as conn:
            return jsonify(cash.snapshot(conn, drawer_id))

    @app.get("/api/v1/drawers/<int:drawer_id>/counts")
    @app.get("/api/v1/drawers/<int:drawer_id>/transactions")
    def history(drawer_id):
        table = "counts" if request.path.endswith("/counts") else "ledger"
        with db() as conn:
            cash.drawer(conn, drawer_id)
            return jsonify(items=cash.records(conn, table, drawer_id))

    @app.patch("/api/v1/drawers/<int:drawer_id>/count")
    @app.post("/api/v1/drawers/<int:drawer_id>/<any(deposits,transactions,close):operation>")
    def change(drawer_id, operation="count"):
        def apply(conn, user, payload):
            action = {"count": "current", "close": "closing", "deposits": "deposit"}.get(operation)
            if operation == "transactions":
                action = payload.get("kind")
                if not isinstance(action, str) or action not in cash.MOVEMENTS:
                    raise cash.Problem("Select a supported cash movement.")
            return cash.change_drawer(conn, user, drawer_id, action, payload)
        return mutate(apply, 201 if operation in {"deposits", "transactions"} else 200)

    @app.get("/api/v1/reports")
    def api_reports():
        with db() as conn:
            return jsonify(reporting.build(conn, request.args, today()))

    @app.get("/api/v1/reports/export/<any(csv,xlsx,pdf,html):kind>")
    @app.get("/reports/export/<any(csv,xlsx,pdf,html):kind>")
    def export(kind):
        with db() as conn:
            report = reporting.build(conn, request.args, today())
        if kind == "html":
            return render_template("print_report.html", report=report, tables=reporting.tables(report))
        return reporting.download(report, kind)

    return app


def user_command(conn, args):
    username = args.username.strip().lower()
    if not re.fullmatch(r"[a-z0-9][a-z0-9_.-]{0,63}", username):
        raise ValueError("Use 1–64 lowercase letters, digits, dots, underscores or hyphens.")
    if args.command == "create-user":
        cursor = conn.execute("INSERT INTO users(username, password_hash, role) VALUES (?, ?, ?)",
                              (username, args.password_hash, args.role))
        cash.audit(conn, cursor.lastrowid, "local_create_user", {"username": username, "role": args.role})
        print(f"Created {username} ({args.role}).")
        return
    user = conn.execute("SELECT * FROM users WHERE username=?", (username,)).fetchone()
    if not user:
        raise ValueError("User does not exist.")
    label = cash.text_value(args.label, "Token label", 64, required=True)
    if args.command == "revoke-token":
        deleted = conn.execute("DELETE FROM tokens WHERE user_id=? AND label=?", (user["id"], label))
        if deleted.rowcount != 1:
            raise ValueError("Token label does not exist.")
        cash.audit(conn, user["id"], "local_revoke_token", {"label": label})
        print("Token revoked.")
    else:
        if not 1 <= args.days <= 365:
            raise ValueError("Token lifetime must be 1–365 days.")
        value = secrets.token_urlsafe(32)
        expires = (datetime.now(UTC) + timedelta(days=args.days)).isoformat(timespec="microseconds")
        conn.execute("INSERT INTO tokens VALUES (?, ?, ?, ?, ?)",
                     (digest(value), user["id"], label, cash.stamp(), expires))
        cash.audit(conn, user["id"], "local_issue_token", {"label": label, "expires_at": expires})
        return value
    return None


def main():
    config.load_environment()
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command")
    commands.add_parser("init-db")
    commands.add_parser("demo")
    user = commands.add_parser("create-user")
    user.add_argument("username")
    user.add_argument("--role", choices=ROLES, default="operator")
    for command in ("issue-token", "revoke-token"):
        manage = commands.add_parser(command)
        manage.add_argument("username")
        manage.add_argument("label")
        if command == "issue-token":
            manage.add_argument("--days", type=int, default=30)
    args = parser.parse_args()
    try:
        if args.command == "demo":
            from demo import run
            run()
            return
        if args.command is None:
            run_server(create_app())
            return
        path = config.db_path()
        cash.initialize(path)
        if args.command == "init-db":
            print(f"SQLite initialized at {path}")
            return
        if args.command == "create-user":
            password = getpass.getpass("Password (at least 12 characters): ")
            if not 12 <= len(password) <= 256 or password != getpass.getpass("Confirm password: "):
                raise ValueError("Passwords must match and be 12–256 characters.")
            args.password_hash = generate_password_hash(password)
        with cash.database(path, write=True) as conn:
            issued = user_command(conn, args)
        if issued:
            print(issued)
    except (ValueError, cash.Problem, sqlite3.IntegrityError) as error:
        parser.exit(1, f"{error}\n")


def run_server(app, *, host=None, port=None, development=False):
    host = host or os.getenv("MC_HOST", "127.0.0.1")
    port = port or int(os.getenv("MC_PORT", "8000"))
    if development or config.enabled("MC_DEV_SERVER"):
        if host not in {"127.0.0.1", "::1", "localhost"}:
            raise ValueError("The development server is limited to loopback.")
        app.run(host=host, port=port)
        return
    from waitress import serve
    serve(app, host=host, port=port, threads=8, max_request_body_size=64 * 1024)


if __name__ == "__main__":
    main()
