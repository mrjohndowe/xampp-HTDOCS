#!/usr/bin/env python3
"""
Vehicle Tracker - Python desktop app.

A modern Tkinter desktop client for the Vehicle Tracker PHP website.
All reads/writes go through the server's authenticated JSON API, so the
website and the desktop app always share the same records.

Requirements:
    Python 3 (tkinter comes with Python on Windows)
    No third-party packages required (uses only the standard library).

Launch:
    python vehicle_tracker.py

First launch:
    The app opens the connection settings. Enter your website URL (e.g.
    http://localhost/vehicle-tracker) and an API token from the website's
    Settings page, then test the connection.
"""

from __future__ import annotations

import json
import tkinter as tk
from tkinter import ttk, messagebox
import urllib.request
import urllib.error
import threading
import queue
import uuid
from datetime import date, datetime
from typing import Any, Optional


# ---------------------------------------------------------------------------
# Themes
# ---------------------------------------------------------------------------

THEMES = {
    "light": {
        "name": "light",
        "bg": "#f8fafc",
        "panel": "#ffffff",
        "text": "#0f172a",
        "muted": "#64748b",
        "border": "#e2e8f0",
        "primary": "#2563eb",
        "primary_hover": "#1d4ed8",
        "success": "#16a34a",
        "error": "#dc2626",
        "warn": "#ca8a04",
        "entry_bg": "#ffffff",
        "entry_fg": "#0f172a",
        "select_bg": "#dbeafe",
        "select_fg": "#0f172a",
        "toast_bg": "#2563eb",
    },
    "dark": {
        "name": "dark",
        "bg": "#0f172a",
        "panel": "#1e293b",
        "text": "#f1f5f9",
        "muted": "#94a3b8",
        "border": "#334155",
        "primary": "#3b82f6",
        "primary_hover": "#60a5fa",
        "success": "#4ade80",
        "error": "#f87171",
        "warn": "#fcd34d",
        "entry_bg": "#0f172a",
        "entry_fg": "#f1f5f9",
        "select_bg": "#1e3a8a",
        "select_fg": "#f1f5f9",
        "toast_bg": "#3b82f6",
    },
}


def hex_rgb(color: str) -> tuple[int, int, int]:
    color = color.lstrip("#")
    return tuple(int(color[i : i + 2], 16) for i in (0, 2, 4))


def blend(c1: str, c2: str, f: float) -> str:
    r1, g1, b1 = hex_rgb(c1)
    r2, g2, b2 = hex_rgb(c2)
    return "#{:02x}{:02x}{:02x}".format(
        int(r1 + (r2 - r1) * f), int(g1 + (g2 - g1) * f), int(b1 + (b2 - b1) * f)
    )


# ---------------------------------------------------------------------------
# API client
# ---------------------------------------------------------------------------

class ApiError(Exception):
    def __init__(self, status, message=None):
        self.status = status if message is not None else 0
        super().__init__(str(message if message is not None else status))


class ApiClient:
    """Thin client over the PHP JSON API. All requests are blocking; callers
    must run them on a background thread."""

    def __init__(self, base_url: str, token: str = ""):
        self.base_url = base_url.rstrip("/")
        self.token = token

    def _headers(self, extra: Optional[dict] = None) -> dict:
        h = {
            "Accept": "application/json",
            "User-Agent": "VehicleTrackerDesktop/1.0",
        }
        if self.token:
            h["Authorization"] = "Bearer " + self.token
        if extra:
            h.update(extra)
        return h

    def request(
        self,
        method: str,
        path: str,
        body: Optional[dict] = None,
        idempotency_key: Optional[str] = None,
        timeout: float = 20.0,
    ) -> tuple[int, dict]:
        url = self.base_url + "/api" + path
        data = None
        headers = self._headers()
        if body is not None:
            data = json.dumps(body).encode("utf-8")
            headers["Content-Type"] = "application/json"
        if idempotency_key:
            headers["Idempotency-Key"] = idempotency_key

        req = urllib.request.Request(url, data=data, headers=headers, method=method)
        try:
            with urllib.request.urlopen(req, timeout=timeout) as resp:
                raw = resp.read().decode("utf-8")
                return resp.status, json.loads(raw) if raw.strip() else {}
        except urllib.error.HTTPError as e:
            raw = e.read().decode("utf-8", errors="replace")
            try:
                payload = json.loads(raw)
            except Exception:
                payload = {"error": raw or e.reason}
            raise ApiError(e.code, payload.get("error", f"HTTP {e.code}"))
        except urllib.error.URLError as e:
            raise ApiError(0, f"Connection error: {e.reason}")
        except Exception as e:  # pragma: no cover - defensive
            raise ApiError(0, f"Unexpected error: {e}")

    # -- resources --

    def status(self) -> tuple[int, dict]:
        try:
            return self.request("GET", "/status")
        except ApiError as exc:
            if exc.status == 409:
                return 409, {"error": str(exc)}
            raise

    def login(self, username: str, password: str) -> dict:
        _, data = self.request("POST", "/login", {"username": username, "password": password})
        return data

    def list_vehicles(self) -> list:
        _, data = self.request("GET", "/vehicles")
        return data.get("vehicles", [])

    def create_vehicle(self, body: dict, key: str) -> dict:
        _, data = self.request("POST", "/vehicles", body, idempotency_key=key)
        return data

    def update_vehicle(self, vid: int, body: dict, key: str) -> dict:
        _, data = self.request("PATCH", f"/vehicles/{vid}", body, idempotency_key=key)
        return data

    def delete_vehicle(self, vid: int, key: str) -> dict:
        _, data = self.request("DELETE", f"/vehicles/{vid}", idempotency_key=key)
        return data

    def list_trips(self, vehicle_id: Optional[int] = None) -> list:
        path = "/trips"
        if vehicle_id:
            path += f"?vehicle_id={vehicle_id}"
        _, data = self.request("GET", path)
        return data.get("trips", [])

    def create_trip(self, body: dict, key: str) -> dict:
        _, data = self.request("POST", "/trips", body, idempotency_key=key)
        return data

    def update_trip(self, tid: int, body: dict, key: str) -> dict:
        _, data = self.request("PATCH", f"/trips/{tid}", body, idempotency_key=key)
        return data

    def delete_trip(self, tid: int, key: str) -> dict:
        _, data = self.request("DELETE", f"/trips/{tid}", idempotency_key=key)
        return data

    def list_engines(self) -> list:
        _, data = self.request("GET", "/engines")
        return data.get("engines", [])

    def create_engine(self, body: dict, key: str) -> dict:
        _, data = self.request("POST", "/engines", body, idempotency_key=key)
        return data

    def update_engine(self, eid: int, body: dict, key: str) -> dict:
        _, data = self.request("PATCH", f"/engines/{eid}", body, idempotency_key=key)
        return data

    def delete_engine(self, eid: int, key: str) -> dict:
        _, data = self.request("DELETE", f"/engines/{eid}", idempotency_key=key)
        return data

    def list_expenses(self) -> list:
        _, data = self.request("GET", "/expenses")
        return data.get("expenses", [])

    def create_expense(self, body: dict, key: str) -> dict:
        _, data = self.request("POST", "/expenses", body, idempotency_key=key)
        return data

    def update_expense(self, eid: int, body: dict, key: str) -> dict:
        _, data = self.request("PATCH", f"/expenses/{eid}", body, idempotency_key=key)
        return data

    def delete_expense(self, eid: int, key: str) -> dict:
        _, data = self.request("DELETE", f"/expenses/{eid}", idempotency_key=key)
        return data

    def list_mechanics(self) -> list:
        _, data = self.request("GET", "/mechanics")
        return data.get("mechanics", [])

    def create_mechanic(self, body: dict, key: str) -> dict:
        _, data = self.request("POST", "/mechanics", body, idempotency_key=key)
        return data

    def list_invoices(self) -> list:
        _, data = self.request("GET", "/invoices")
        return data.get("invoices", [])

    def create_invoice(self, body: dict, key: str) -> dict:
        _, data = self.request("POST", "/invoices", body, idempotency_key=key)
        return data

    def update_invoice(self, iid: int, body: dict, key: str) -> dict:
        _, data = self.request("PATCH", f"/invoices/{iid}", body, idempotency_key=key)
        return data

    def list_payments(self, invoice_id: int) -> list:
        _, data = self.request("GET", f"/invoices/{invoice_id}/payments")
        return data.get("payments", [])

    def create_payment(self, invoice_id: int, body: dict, key: str) -> dict:
        _, data = self.request("POST", f"/invoices/{invoice_id}/payments", body, idempotency_key=key)
        return data

    def dashboard(self) -> dict:
        _, data = self.request("GET", "/dashboard")
        return data


# ---------------------------------------------------------------------------
# Settings persistence
# ---------------------------------------------------------------------------

SETTINGS_FILE_NAME = "vehicle_tracker_settings.json"


def settings_path() -> str:
    import os

    # Store next to the script when writable, otherwise in the user home.
    here = os.path.dirname(os.path.abspath(__file__))
    candidate = os.path.join(here, SETTINGS_FILE_NAME)
    try:
        if os.access(here, os.W_OK) or not os.path.exists(candidate):
            return candidate
    except Exception:
        pass
    return os.path.join(os.path.expanduser("~"), SETTINGS_FILE_NAME)


def load_settings() -> dict:
    import os

    path = settings_path()
    try:
        with open(path, "r", encoding="utf-8") as f:
            return json.load(f)
    except Exception:
        return {"theme": "light", "base_url": "", "token": ""}


def save_settings(settings: dict) -> None:
    import os

    path = settings_path()
    try:
        with open(path, "w", encoding="utf-8") as f:
            json.dump(settings, f, indent=2)
    except Exception:
        pass


# ---------------------------------------------------------------------------
# UI helpers
# ---------------------------------------------------------------------------

class App(tk.Tk):
    def __init__(self) -> None:
        super().__init__()
        self.title("Vehicle Tracker")
        self.geometry("1100x700")
        self.minsize(800, 500)

        self.settings = load_settings()
        self.theme_name = self.settings.get("theme", "light")
        self.theme = THEMES[self.theme_name]

        self.client: Optional[ApiClient] = None
        self.connected = False

        # idempotency keys pending retry: (kind, ref) -> key
        self.pending_keys: dict = {}

        self._build_styles()
        self._build_shell()
        self._show_connection()

    # ---------- styling ----------

    def _build_styles(self) -> None:
        t = self.theme
        self.configure(bg=t["bg"])
        style = ttk.Style(self)
        try:
            style.theme_use("clam")
        except Exception:
            pass
        style.configure("TFrame", background=t["bg"])
        style.configure("Panel.TFrame", background=t["panel"])
        style.configure("TLabel", background=t["bg"], foreground=t["text"], font=("Segoe UI", 10))
        style.configure("Muted.TLabel", background=t["bg"], foreground=t["muted"], font=("Segoe UI", 9))
        style.configure(
            "Title.TLabel",
            background=t["bg"],
            foreground=t["text"],
            font=("Segoe UI", 15, "bold"),
        )
        style.configure("Panel.Title.TLabel", background=t["panel"], foreground=t["text"], font=("Segoe UI", 15, "bold"))
        style.configure(
            "Heading.TLabel",
            background=t["panel"],
            foreground=t["muted"],
            font=("Segoe UI", 10, "bold"),
        )
        style.configure("TButton", font=("Segoe UI", 10), padding=6)
        style.map(
            "TButton",
            background=[("active", t["primary_hover"])],
            foreground=[("active", "#ffffff")],
        )
        style.configure("Treeview",
                        background=t["panel"],
                        fieldbackground=t["panel"],
                        foreground=t["text"],
                        bordercolor=t["border"],
                        lightcolor=t["border"],
                        darkcolor=t["border"],
                        rowheight=26,
                        font=("Segoe UI", 10))
        style.configure("Treeview.Heading",
                        background=blend(t["border"], t["panel"], 0.5),
                        foreground=t["text"],
                        font=("Segoe UI", 10, "bold"))
        style.map("Treeview", background=[("selected", t["select_bg"])], foreground=[("selected", t["select_fg"])])
        style.configure("Vertical.TScrollbar", background=t["panel"], troughcolor=t["bg"], bordercolor=t["border"])
        style.configure("TEntry", fieldbackground=t["entry_bg"], foreground=t["entry_fg"], bordercolor=t["border"])
        style.configure("TCombobox", fieldbackground=t["entry_bg"], foreground=t["entry_fg"], bordercolor=t["border"])

    def apply_theme(self, name: str) -> None:
        self.theme_name = name
        self.theme = THEMES[name]
        self.settings["theme"] = name
        save_settings(self.settings)
        self._build_styles()
        # Refresh current view colors by rebuilding shell content
        for child in self.winfo_children():
            child.destroy()
        self._build_shell()
        self._refresh_current()

    # ---------- shell ----------

    def _build_shell(self) -> None:
        t = self.theme
        self.container = ttk.Frame(self)
        self.container.pack(fill="both", expand=True)

        self.nav = ttk.Frame(self.container, style="Panel.TFrame")
        self.nav.pack(side="left", fill="y", padx=0, pady=0)
        self.nav.configure(width=210)

        ttk.Label(self.nav, text="Vehicle Tracker", style="Panel.Title.TLabel").pack(pady=(18, 10), padx=14)

        self.nav_buttons: dict[str, ttk.Button] = {}
        for label, view in [
            ("Dashboard", "dashboard"),
            ("Vehicles", "vehicles"),
            ("Trips", "trips"),
            ("Engines", "engines"),
            ("Expenses", "expenses"),
            ("Invoices", "invoices"),
            ("Settings", "settings"),
        ]:
            b = ttk.Button(self.nav, text=label, command=lambda v=view: self.switch_view(v))
            b.pack(fill="x", padx=14, pady=3)
            self.nav_buttons[view] = b

        ttk.Separator(self.nav, orient="horizontal").pack(fill="x", padx=14, pady=12)
        self.conn_label = ttk.Label(self.nav, text="Not connected", style="Muted.TLabel")
        self.conn_label.pack(padx=14, pady=(0, 6))
        ttk.Button(self.nav, text="Connection", command=self._show_connection).pack(fill="x", padx=14, pady=2)
        ttk.Button(self.nav, text="Theme", command=self._cycle_theme).pack(fill="x", padx=14, pady=2)
        ttk.Button(self.nav, text="Refresh", command=self._refresh_current).pack(fill="x", padx=14, pady=2)

        self.content = ttk.Frame(self.container)
        self.content.pack(side="left", fill="both", expand=True)

        self.status_var = tk.StringVar(value="Ready")
        self.status_bar = ttk.Label(self, textvariable=self.status_var, style="Muted.TLabel")
        self.status_bar.pack(side="bottom", fill="x")

        self._toast_id = None
        self.toast = tk.Label(self, bg=t["toast_bg"], fg="#ffffff", font=("Segoe UI", 10), padx=12, pady=6)
        self.toast.place_forget()

    def _cycle_theme(self) -> None:
        next_name = "dark" if self.theme_name == "light" else "light"
        self.apply_theme(next_name)

    def _show_toast(self, message: str, kind: str = "info") -> None:
        t = self.theme
        color = {"info": t["primary"], "success": t["success"], "error": t["error"]}[kind]
        self.toast.configure(bg=color, text=message)
        self.toast.lift()
        self.toast.place(relx=1.0, rely=1.0, x=-18, y=-18, anchor="se")
        self.toast.after(2500, self.toast.place_forget)

    def set_status(self, message: str) -> None:
        self.status_var.set(message)
        self.after(4000, lambda: self.status_var.set("Ready"))

    # ---------- navigation ----------

    def _set_nav(self, label: str) -> None:
        self.current_view = label.strip().lower()
        for name, button in self.nav_buttons.items():
            button.state(["!pressed", "!selected"])
            if name == self.current_view:
                button.state(["pressed"])

    def switch_view(self, view: str) -> None:
        self.current_view = view
        for v, b in self.nav_buttons.items():
            b.state(["!pressed", "!selected"])
        b = self.nav_buttons.get(view)
        if b:
            b.state(["pressed"])
        self._refresh_current()

    def _refresh_current(self) -> None:
        for child in self.content.winfo_children():
            child.destroy()
        view = getattr(self, "current_view", "dashboard")
        if view == "connection":
            self._show_connection()
            return
        if not self.connected:
            self._show_placeholder("Connect to the website to view data.")
            return
        getattr(self, f"_view_{view}")()

    def _show_placeholder(self, message: str) -> None:
        t = self.theme
        frame = ttk.Frame(self.content, style="Panel.TFrame")
        frame.pack(fill="both", expand=True, padx=24, pady=24)
        ttk.Label(frame, text=message, style="Heading.TLabel").pack(expand=True)

    # ---------- connection view ----------

    def _show_connection(self) -> None:
        self.current_view = "connection"
        for b in self.nav_buttons.values():
            b.state(["!pressed", "!selected"])
        for child in self.content.winfo_children():
            child.destroy()

        t = self.theme
        frame = ttk.Frame(self.content, style="Panel.TFrame")
        frame.pack(fill="both", expand=True, padx=40, pady=40)

        card = ttk.Frame(frame, style="Panel.TFrame")
        card.pack(expand=True)
        ttk.Label(card, text="Connect to Vehicle Tracker", style="Panel.Title.TLabel").pack(pady=(0, 16))

        self.ent_url = tk.Entry(card, width=48, bg=t["entry_bg"], fg=t["entry_fg"],
                                insertbackground=t["text"], relief="flat", highlightthickness=1,
                                highlightbackground=t["border"], highlightcolor=t["primary"], font=("Segoe UI", 11))
        self.ent_url.insert(0, self.settings.get("base_url", ""))
        self.ent_token = tk.Entry(card, width=48, show="•", bg=t["entry_bg"], fg=t["entry_fg"],
                                  insertbackground=t["text"], relief="flat", highlightthickness=1,
                                  highlightbackground=t["border"], highlightcolor=t["primary"], font=("Segoe UI", 11))
        self.ent_token.insert(0, self.settings.get("token", ""))

        def row(label_text, widget, note=""):
            box = ttk.Frame(card, style="Panel.TFrame")
            box.pack(fill="x", pady=(0, 10))
            ttk.Label(box, text=label_text, style="Heading.TLabel").pack(anchor="w")
            widget.pack(fill="x", pady=(4, 0))
            if note:
                ttk.Label(box, text=note, style="Muted.TLabel").pack(anchor="w", pady=(2, 0))

        row("Website URL", self.ent_url, "e.g. http://localhost/vehicle-tracker")
        row("API token", self.ent_token, "From the website Settings > API tokens")

        btns = ttk.Frame(card, style="Panel.TFrame")
        btns.pack(pady=(18, 0))
        ttk.Button(btns, text="Test connection", command=self._test_connection).pack(side="left", padx=5)
        ttk.Button(btns, text="Connect", command=self._connect).pack(side="left", padx=5)

        self.conn_msg = ttk.Label(card, text="", style="Muted.TLabel")
        self.conn_msg.pack(pady=(12, 0))

    def _set_conn_msg(self, text: str, error: bool = False) -> None:
        self.conn_msg.configure(text=text, foreground=self.theme["error"] if error else self.theme["success"])

    def _test_connection(self) -> None:
        url = self.ent_url.get().strip()
        token = self.ent_token.get().strip()
        if not url:
            self._set_conn_msg("Enter a website URL.", error=True)
            return

        def work():
            try:
                c = ApiClient(url, token)
                status, data = c.status()
                if status == 409:
                    return ("needs_onboarding", data.get("error", "Onboarding not complete"))
                return ("ok", data)
            except ApiError as e:
                return ("error", str(e))

        def done(result):
            kind, payload = result
            if kind == "needs_onboarding":
                self._set_conn_msg("Website needs onboarding: " + payload, error=True)
                messagebox.showwarning(
                    "Onboarding required",
                    "The website has not completed its first-run setup. "
                    "Open the website in a browser and finish the installer first.\n\n" + payload,
                )
            elif kind == "ok":
                self._set_conn_msg("Connection OK (installed %s)" % payload.get("installed_at", ""))
                messagebox.showinfo("Connection OK", "Reached the Vehicle Tracker API successfully.")
            else:
                self._set_conn_msg("Test failed: " + payload, error=True)
                messagebox.showerror("Connection failed", payload)

        self.set_status("Testing connection...")
        self._run_threaded(work, done)

    def _connect(self) -> None:
        url = self.ent_url.get().strip()
        token = self.ent_token.get().strip()
        if not url:
            self._set_conn_msg("Enter a website URL.", error=True)
            return

        def work():
            c = ApiClient(url, token)
            status, data = c.status()
            if status == 409:
                raise ApiError(409, data.get("error", "Onboarding not complete"))
            c.list_vehicles()
            return data

        def done(result):
            if isinstance(result, tuple) and result[0] == "error":
                self._set_conn_msg("Connect failed: " + result[1], error=True)
                messagebox.showerror("Connect failed", result[1])
                return
            self.client = ApiClient(url, token)
            self.connected = True
            self.settings["base_url"] = url
            self.settings["token"] = token
            save_settings(self.settings)
            self.conn_label.configure(text="Connected: " + url)
            self.switch_view("dashboard")

        self.set_status("Connecting...")
        self._run_threaded(work, done, error_handler=lambda e: self._set_conn_msg("Connect failed: " + e, error=True))

    def _run_threaded(self, work, done, error_handler=None) -> None:
        # Only the UI thread touches Tk; worker exceptions are captured before
        # Python clears the exception variable at the end of the except block.
        results = queue.Queue()
        def target():
            try:
                results.put((True, work()))
            except Exception as exc:
                results.put((False, str(exc)))
        def poll():
            try:
                ok, result = results.get_nowait()
            except queue.Empty:
                self.after(30, poll)
                return
            if ok:
                done(result)
            else:
                (error_handler or self._default_error)(result)
        threading.Thread(target=target, daemon=True).start()
        self.after(30, poll)

    def _default_error(self, message: str) -> None:
        messagebox.showerror("Error", message)
        self.set_status("Error: " + message)

    # ---------- generic list builder ----------

    def _tree(self, parent, columns, headings, width_map=None):
        t = self.theme
        tree = ttk.Treeview(parent, columns=columns, show="headings", selectmode="browse")
        for col, head in zip(columns, headings):
            tree.heading(col, text=head)
            tree.column(col, width=(width_map or {}).get(col, 120), anchor="center" if col in ("#0",) else "w")
        vsb = ttk.Scrollbar(parent, orient="vertical", command=tree.yview)
        tree.configure(yscrollcommand=vsb.set)
        tree.pack(side="left", fill="both", expand=True)
        vsb.pack(side="right", fill="y")
        return tree

    def _clear_tree(self, tree):
        for item in tree.get_children():
            tree.delete(item)

    # ---------- views ----------

    def _view_dashboard(self) -> None:
        t = self.theme
        self._set_nav("Dashboard")

        top = ttk.Frame(self.content, style="Panel.TFrame")
        top.pack(fill="x", padx=20, pady=(16, 10))
        ttk.Label(top, text="Dashboard", style="Panel.Title.TLabel").pack(side="left")

        self.dash_stats = ttk.Frame(self.content, style="Panel.TFrame")
        self.dash_stats.pack(fill="x", padx=20, pady=(0, 16))

        grid = ttk.Frame(self.dash_stats, style="Panel.TFrame")
        grid.pack()
        self.stat_labels = {}
        for i, (key, label, fmt) in enumerate([
            ("vehicles", "Vehicles", "{}"),
            ("trip_miles", "Trip miles", "{} mi"),
            ("expense_total", "Expenses", "{}{}"),
            ("outstanding_debt", "Outstanding debt", "{}{}"),
            ("engines", "Active engines", "{}"),
        ]):
            card = ttk.Frame(grid, style="Panel.TFrame")
            card.grid(row=0, column=i, padx=8, pady=6)
            ttk.Label(card, text=label, style="Heading.TLabel").pack()
            var = tk.StringVar(value="–")
            ttk.Label(card, textvariable=var, font=("Segoe UI", 16, "bold")).pack()
            self.stat_labels[key] = (var, fmt)

        recent = ttk.Frame(self.content, style="Panel.TFrame")
        recent.pack(fill="both", expand=True, padx=20, pady=(0, 20))
        ttk.Label(recent, text="Recent trips", style="Heading.TLabel").pack(anchor="w", pady=(0, 6))
        self.tree_recent = self._tree(
            recent,
            ("date", "vehicle", "start", "end", "distance", "purpose"),
            ("Date", "Vehicle", "Start", "End", "Distance", "Purpose"),
            {"date": 110, "vehicle": 180, "start": 90, "end": 90, "distance": 90, "purpose": 200},
        )

        self._load_dashboard()

    def _load_dashboard(self) -> None:
        def work():
            return self.client.dashboard()

        def done(data):
            for key, (var, fmt) in self.stat_labels.items():
                if key == "vehicles":
                    var.set(fmt.format(len(data.get("vehicles", []))))
                elif key == "trip_miles":
                    total = sum(float(v.get("total_miles") or 0) for v in data.get("vehicles", []))
                    var.set(fmt.format(f"{total:,.1f}"))
                elif key == "expense_total":
                    cur = "$"
                    var.set(fmt.format(cur, f"{float(data.get('expense_total', 0) or 0):,.2f}"))
                elif key == "outstanding_debt":
                    var.set(fmt.format("$", f"{float(data.get('outstanding_debt', 0) or 0):,.2f}"))
                elif key == "engines":
                    var.set(fmt.format(len(data.get("engines", []))))
            self._clear_tree(self.tree_recent)
            for t in data.get("recent_trips", []):
                self.tree_recent.insert("", "end", values=(
                    t.get("trip_date", ""),
                    f"{t.get('make','')} {t.get('model','')}".strip(),
                    f"{float(t.get('start_odometer') or 0):,.1f}",
                    f"{float(t.get('end_odometer') or 0):,.1f}",
                    f"{float(t.get('distance') or 0):,.1f}",
                    t.get("purpose", ""),
                ))
            self.set_status("Dashboard refreshed")

        self.set_status("Loading dashboard...")
        self._run_threaded(work, done)

    # ---- vehicles ----

    def _view_vehicles(self) -> None:
        t = self.theme
        self._set_nav("Vehicles")

        bar = ttk.Frame(self.content, style="Panel.TFrame")
        bar.pack(fill="x", padx=20, pady=(16, 6))
        ttk.Label(bar, text="Vehicles", style="Panel.Title.TLabel").pack(side="left")
        ttk.Button(bar, text="Add vehicle", command=self._dlg_vehicle).pack(side="right")

        tree = self._tree(
            self.content,
            ("id", "make", "model", "year", "vin", "plate", "trips", "miles", "odometer"),
            ("ID", "Make", "Model", "Year", "VIN", "Plate", "Trips", "Miles", "Odometer"),
            {"id": 40, "make": 120, "model": 120, "year": 60, "vin": 120, "plate": 100, "trips": 60, "miles": 90, "odometer": 100},
        )
        tree.pack(fill="both", expand=True, padx=20, pady=(0, 10))
        self.tree_vehicles = tree

        btns = ttk.Frame(self.content, style="Panel.TFrame")
        btns.pack(fill="x", padx=20, pady=(0, 16))
        ttk.Button(btns, text="Edit", command=self._edit_vehicle).pack(side="left", padx=3)
        ttk.Button(btns, text="Delete", command=self._delete_vehicle).pack(side="left", padx=3)
        ttk.Button(btns, text="Refresh", command=self._refresh_current).pack(side="left", padx=3)

        self._load_vehicles()

    def _load_vehicles(self) -> None:
        def work():
            return self.client.list_vehicles()

        def done(data):
            self._clear_tree(self.tree_vehicles)
            for v in data:
                self.tree_vehicles.insert("", "end", values=(
                    v.get("id"), v.get("make", ""), v.get("model", ""), v.get("year", ""),
                    v.get("vin", ""), v.get("license_plate", ""),
                    v.get("trip_count", 0), f"{float(v.get('total_miles') or 0):,.1f}",
                    f"{float(v.get('current_odometer') or 0):,.1f}" if v.get("current_odometer") is not None else "–",
                ))
            self.set_status(f"{len(data)} vehicle(s)")

        self.set_status("Loading vehicles...")
        self._run_threaded(work, done)

    def _dlg_vehicle(self, vehicle: Optional[dict] = None) -> None:
        t = self.theme
        editing = vehicle is not None
        dlg = tk.Toplevel(self)
        dlg.title("Edit vehicle" if editing else "Add vehicle")
        dlg.configure(bg=t["panel"])
        dlg.transient(self)
        dlg.grab_set()

        fields = {}
        def add_row(label, key, width=40, default=""):
            box = ttk.Frame(dlg, style="Panel.TFrame")
            box.pack(fill="x", padx=16, pady=6)
            ttk.Label(box, text=label, style="Heading.TFrame" if False else "Heading.TLabel").pack(anchor="w")
            ent = tk.Entry(box, width=width, bg=t["entry_bg"], fg=t["entry_fg"],
                           insertbackground=t["text"], relief="flat", highlightthickness=1,
                           highlightbackground=t["border"], highlightcolor=t["primary"])
            ent.insert(0, str(vehicle.get(key, default)) if editing else default)
            ent.pack(fill="x", pady=(4, 0))
            fields[key] = ent

        add_row("Make", "make")
        add_row("Model", "model")
        add_row("Year", "year", default=str(date.today().year))
        add_row("VIN", "vin")
        add_row("License plate", "license_plate")
        add_row("Color", "color")
        add_row("Notes", "notes")

        btns = ttk.Frame(dlg, style="Panel.TFrame")
        btns.pack(fill="x", padx=16, pady=(10, 16))

        def submit():
            body = {k: fields[k].get() for k in fields}
            if not body["make"] or not body["model"]:
                messagebox.showwarning("Validation", "Make and model are required.", parent=dlg)
                return
            body["year"] = int(body["year"]) if body["year"] else None
            key = self.pending_keys.get(("vehicle", vehicle.get("id") if editing else "new"))
            if key is None:
                key = str(uuid.uuid4())
                self.pending_keys[("vehicle", vehicle.get("id") if editing else "new")] = key

            def work():
                if editing:
                    return self.client.update_vehicle(int(vehicle["id"]), body, key)
                return self.client.create_vehicle(body, key)

            def done(result):
                self.pending_keys.pop(("vehicle", vehicle.get("id") if editing else "new"), None)
                dlg.destroy()
                self._show_toast("Vehicle saved.")
                self._refresh_current()

            def err(e):
                # keep the key so a retry reuses it (server dedupes)
                messagebox.showerror("Save failed", e, parent=dlg)

            self.set_status("Saving vehicle...")
            self._run_threaded(work, done, err)

        ttk.Button(btns, text="Cancel", command=dlg.destroy).pack(side="right", padx=5)
        ttk.Button(btns, text="Save", command=submit).pack(side="right")

    def _selected_id(self, tree) -> Optional[int]:
        sel = tree.selection()
        if not sel:
            messagebox.showwarning("Select", "Select a row first.")
            return None
        return int(tree.item(sel[0])["values"][0])

    def _edit_vehicle(self) -> None:
        vid = self._selected_id(self.tree_vehicles)
        if vid is None:
            return
        vehicle = next((v for v in self.client.list_vehicles() if v["id"] == vid), None)
        if vehicle:
            self._dlg_vehicle(vehicle)

    def _delete_vehicle(self) -> None:
        vid = self._selected_id(self.tree_vehicles)
        if vid is None:
            return
        if not messagebox.askyesno("Delete", "Delete this vehicle? Its trips and expenses will be removed too."):
            return
        key = str(uuid.uuid4())

        def work():
            return self.client.delete_vehicle(vid, key)

        def done(result):
            self._show_toast("Vehicle deleted.")
            self._refresh_current()

        self.set_status("Deleting vehicle...")
        self._run_threaded(work, done)

    # ---- trips ----

    def _view_trips(self) -> None:
        t = self.theme
        self._set_nav("Trips")

        bar = ttk.Frame(self.content, style="Panel.TFrame")
        bar.pack(fill="x", padx=20, pady=(16, 6))
        ttk.Label(bar, text="Trips", style="Panel.Title.TLabel").pack(side="left")
        ttk.Button(bar, text="Log trip", command=self._dlg_trip).pack(side="right")

        tree = self._tree(
            self.content,
            ("id", "date", "vehicle", "start", "end", "distance", "purpose"),
            ("ID", "Date", "Vehicle", "Start odo", "End odo", "Distance", "Purpose"),
            {"id": 40, "date": 100, "vehicle": 160, "start": 90, "end": 90, "distance": 80, "purpose": 220},
        )
        tree.pack(fill="both", expand=True, padx=20, pady=(0, 10))
        self.tree_trips = tree

        btns = ttk.Frame(self.content, style="Panel.TFrame")
        btns.pack(fill="x", padx=20, pady=(0, 16))
        ttk.Button(btns, text="Edit", command=self._edit_trip).pack(side="left", padx=3)
        ttk.Button(btns, text="Delete", command=self._delete_trip).pack(side="left", padx=3)
        ttk.Button(btns, text="Refresh", command=self._refresh_current).pack(side="left", padx=3)

        self._load_trips()

    def _load_trips(self) -> None:
        def work():
            return self.client.list_trips()

        def done(data):
            self._clear_tree(self.tree_trips)
            for t in data:
                self.tree_trips.insert("", "end", values=(
                    t.get("id"), t.get("trip_date", ""),
                    f"{t.get('make','')} {t.get('model','')}".strip(),
                    f"{float(t.get('start_odometer') or 0):,.1f}",
                    f"{float(t.get('end_odometer') or 0):,.1f}",
                    f"{float(t.get('distance') or 0):,.1f}",
                    t.get("purpose", ""),
                ))
            self.set_status(f"{len(data)} trip(s)")

        self.set_status("Loading trips...")
        self._run_threaded(work, done)

    def _dlg_trip(self, trip: Optional[dict] = None) -> None:
        t = self.theme
        editing = trip is not None
        vehicles = self.client.list_vehicles()
        dlg = tk.Toplevel(self)
        dlg.title("Edit trip" if editing else "Log trip")
        dlg.configure(bg=t["panel"])
        dlg.transient(self)
        dlg.grab_set()

        fields = {}
        def add_row(label, key, widget, default=""):
            box = ttk.Frame(dlg, style="Panel.TFrame")
            box.pack(fill="x", padx=16, pady=6)
            ttk.Label(box, text=label, style="Heading.TLabel").pack(anchor="w")
            widget.pack(fill="x", pady=(4, 0))
            if default:
                try:
                    widget.delete(0, "end")
                    widget.insert(0, default)
                except Exception:
                    pass
            fields[key] = widget

        vbox = ttk.Frame(dlg, style="Panel.TFrame")
        ttk.Label(vbox, text="Vehicle", style="Heading.TLabel").pack(anchor="w")
        vcombo = ttk.Combobox(vbox, values=[f"{v['id']} | {v.get('make','')} {v.get('model','')}" for v in vehicles],
                              state="readonly")
        if editing:
            vcombo.set(next((f"{v['id']} | {v.get('make','')} {v.get('model','')}" for v in vehicles if v["id"] == trip["vehicle_id"]), ""))
        add_row("Vehicle", "vehicle", vcombo)

        dbox = ttk.Frame(dlg, style="Panel.TFrame")
        dbox.pack(fill="x", padx=16, pady=6)
        ttk.Label(dbox, text="Date (YYYY-MM-DD)", style="Heading.TLabel").pack(anchor="w")
        dent = tk.Entry(dbox, bg=t["entry_bg"], fg=t["entry_fg"], insertbackground=t["text"],
                        relief="flat", highlightthickness=1, highlightbackground=t["border"],
                        highlightcolor=t["primary"])
        dent.pack(fill="x", pady=(4, 0))
        dent.insert(0, trip.get("trip_date", date.today().isoformat()) if editing else date.today().isoformat())
        fields["trip_date"] = dent

        for key, label, default in [
            ("start_odometer", "Start odometer", str(trip.get("start_odometer", "")) if editing else ""),
            ("end_odometer", "End odometer", str(trip.get("end_odometer", "")) if editing else ""),
            ("purpose", "Purpose", trip.get("purpose", "") if editing else ""),
            ("notes", "Notes", trip.get("notes", "") if editing else ""),
        ]:
            box = ttk.Frame(dlg, style="Panel.TFrame")
            box.pack(fill="x", padx=16, pady=6)
            ttk.Label(box, text=label, style="Heading.TLabel").pack(anchor="w")
            ent = tk.Entry(box, bg=t["entry_bg"], fg=t["entry_fg"], insertbackground=t["text"],
                           relief="flat", highlightthickness=1, highlightbackground=t["border"],
                           highlightcolor=t["primary"])
            ent.insert(0, default)
            ent.pack(fill="x", pady=(4, 0))
            fields[key] = ent

        btns = ttk.Frame(dlg, style="Panel.TFrame")
        btns.pack(fill="x", padx=16, pady=(10, 16))

        def submit():
            try:
                start = float(fields["start_odometer"].get())
                end = float(fields["end_odometer"].get())
            except ValueError:
                messagebox.showwarning("Validation", "Enter numeric odometer readings.", parent=dlg)
                return
            if end < start:
                messagebox.showwarning("Validation", "End odometer must be >= start odometer.", parent=dlg)
                return
            sel = fields["vehicle"].get()
            vid = int(sel.split(" | ")[0]) if sel else None
            if not vid:
                messagebox.showwarning("Validation", "Select a vehicle.", parent=dlg)
                return
            body = {
                "vehicle_id": vid,
                "trip_date": fields["trip_date"].get(),
                "start_odometer": start,
                "end_odometer": end,
                "purpose": fields["purpose"].get(),
                "notes": fields["notes"].get(),
            }
            key = self.pending_keys.get(("trip", trip.get("id") if editing else "new"))
            if key is None:
                key = str(uuid.uuid4())
                self.pending_keys[("trip", trip.get("id") if editing else "new")] = key

            def work():
                if editing:
                    return self.client.update_trip(int(trip["id"]), body, key)
                return self.client.create_trip(body, key)

            def done(result):
                self.pending_keys.pop(("trip", trip.get("id") if editing else "new"), None)
                dlg.destroy()
                self._show_toast("Trip saved.")
                self._refresh_current()

            def err(e):
                messagebox.showerror("Save failed", e, parent=dlg)

            self.set_status("Saving trip...")
            self._run_threaded(work, done, err)

        ttk.Button(btns, text="Cancel", command=dlg.destroy).pack(side="right", padx=5)
        ttk.Button(btns, text="Save", command=submit).pack(side="right")

    def _edit_trip(self) -> None:
        tid = self._selected_id(self.tree_trips)
        if tid is None:
            return
        trip = next((t for t in self.client.list_trips() if t["id"] == tid), None)
        if trip:
            self._dlg_trip(trip)

    def _delete_trip(self) -> None:
        tid = self._selected_id(self.tree_trips)
        if tid is None:
            return
        if not messagebox.askyesno("Delete", "Delete this trip?"):
            return
        key = str(uuid.uuid4())

        def work():
            return self.client.delete_trip(tid, key)

        def done(result):
            self._show_toast("Trip deleted.")
            self._refresh_current()

        self.set_status("Deleting trip...")
        self._run_threaded(work, done)

    # ---- engines ----

    def _view_engines(self) -> None:
        t = self.theme
        self._set_nav("Engines")

        bar = ttk.Frame(self.content, style="Panel.TFrame")
        bar.pack(fill="x", padx=20, pady=(16, 6))
        ttk.Label(bar, text="Engines", style="Panel.Title.TLabel").pack(side="left")
        ttk.Button(bar, text="Install engine", command=self._dlg_engine).pack(side="right")

        tree = self._tree(
            self.content,
            ("id", "vehicle", "serial", "installed", "engine_miles_install", "vehicle_odo_install", "current_miles", "status"),
            ("ID", "Vehicle", "Serial", "Installed", "Miles at install", "Vehicle odo at install", "Current engine miles", "Status"),
            {"id": 40, "vehicle": 150, "serial": 120, "installed": 100, "engine_miles_install": 100, "vehicle_odo_install": 110, "current_miles": 110, "status": 90},
        )
        tree.pack(fill="both", expand=True, padx=20, pady=(0, 10))
        self.tree_engines = tree

        btns = ttk.Frame(self.content, style="Panel.TFrame")
        btns.pack(fill="x", padx=20, pady=(0, 16))
        ttk.Button(btns, text="Edit", command=self._edit_engine).pack(side="left", padx=3)
        ttk.Button(btns, text="Delete", command=self._delete_engine).pack(side="left", padx=3)
        ttk.Button(btns, text="Refresh", command=self._refresh_current).pack(side="left", padx=3)

        self._load_engines()

    def _load_engines(self) -> None:
        def work():
            return self.client.list_engines()

        def done(data):
            self._clear_tree(self.tree_engines)
            for e in data:
                miles = e.get("engine_miles")
                self.tree_engines.insert("", "end", values=(
                    e.get("id"),
                    f"{e.get('make','')} {e.get('model','')}".strip(),
                    e.get("serial_number", ""),
                    e.get("installed_date", ""),
                    f"{float(e.get('engine_mileage_at_install') or 0):,.1f}",
                    f"{float(e.get('vehicle_odometer_at_install') or 0):,.1f}",
                    f"{float(miles):,.1f}" if miles is not None else "–",
                    "Conflict" if e.get("conflict") else e.get("status", ""),
                ))
            self.set_status(f"{len(data)} engine(s)")

        self.set_status("Loading engines...")
        self._run_threaded(work, done)

    def _dlg_engine(self, engine: Optional[dict] = None) -> None:
        t = self.theme
        editing = engine is not None
        vehicles = self.client.list_vehicles()
        dlg = tk.Toplevel(self)
        dlg.title("Edit engine" if editing else "Install engine")
        dlg.configure(bg=t["panel"])
        dlg.transient(self)
        dlg.grab_set()

        fields = {}
        def add_row(label, key, widget, default=""):
            box = ttk.Frame(dlg, style="Panel.TFrame")
            box.pack(fill="x", padx=16, pady=6)
            ttk.Label(box, text=label, style="Heading.TLabel").pack(anchor="w")
            widget.pack(fill="x", pady=(4, 0))
            if default not in (None, ""):
                try:
                    widget.delete(0, "end")
                    widget.insert(0, default)
                except Exception:
                    pass
            fields[key] = widget

        vbox = ttk.Frame(dlg, style="Panel.TFrame")
        ttk.Label(vbox, text="Vehicle", style="Heading.TLabel").pack(anchor="w")
        vcombo = ttk.Combobox(vbox, values=[f"{v['id']} | {v.get('make','')} {v.get('model','')}" for v in vehicles],
                              state="readonly")
        if editing:
            vcombo.set(next((f"{v['id']} | {v.get('make','')} {v.get('model','')}" for v in vehicles if v["id"] == engine["vehicle_id"]), ""))
        add_row("Vehicle", "vehicle", vcombo)

        add_row("Serial number", "serial_number", tk.Entry(dlg), engine.get("serial_number", "") if editing else "")
        add_row("Installation date", "installed_date", tk.Entry(dlg), engine.get("installed_date", date.today().isoformat()) if editing else date.today().isoformat())
        add_row("Engine miles at installation", "engine_mileage_at_install", tk.Entry(dlg),
                str(engine.get("engine_mileage_at_install", 0)) if editing else "0")
        add_row("Vehicle odometer at installation", "vehicle_odometer_at_install", tk.Entry(dlg),
                str(engine.get("vehicle_odometer_at_install", 0)) if editing else "0")
        add_row("Replacement reason", "replacement_reason", tk.Entry(dlg), engine.get("replacement_reason", "") if editing else "")
        add_row("Notes", "notes", tk.Entry(dlg), engine.get("notes", "") if editing else "")

        status_box = ttk.Frame(dlg, style="Panel.TFrame")
        status_box.pack(fill="x", padx=16, pady=6)
        ttk.Label(status_box, text="Status", style="Heading.TLabel").pack(anchor="w")
        status_var = tk.StringVar(value=engine.get("status", "active") if editing else "active")
        ttk.Combobox(status_box, textvariable=status_var, values=["active", "replaced"], state="readonly").pack(fill="x", pady=(4, 0))
        fields["status"] = status_var

        btns = ttk.Frame(dlg, style="Panel.TFrame")
        btns.pack(fill="x", padx=16, pady=(10, 16))

        def submit():
            try:
                emi = float(fields["engine_mileage_at_install"].get())
                voi = float(fields["vehicle_odometer_at_install"].get())
            except ValueError:
                messagebox.showwarning("Validation", "Enter numeric mileage readings.", parent=dlg)
                return
            if emi < 0 or voi < 0:
                messagebox.showwarning("Validation", "Readings cannot be negative.", parent=dlg)
                return
            sel = fields["vehicle"].get()
            vid = int(sel.split(" | ")[0]) if sel else None
            if not vid:
                messagebox.showwarning("Validation", "Select a vehicle.", parent=dlg)
                return
            body = {
                "vehicle_id": vid,
                "serial_number": fields["serial_number"].get(),
                "installed_date": fields["installed_date"].get(),
                "engine_mileage_at_install": emi,
                "vehicle_odometer_at_install": voi,
                "status": fields["status"].get(),
                "replacement_reason": fields["replacement_reason"].get(),
                "notes": fields["notes"].get(),
            }
            key = self.pending_keys.get(("engine", engine.get("id") if editing else "new"))
            if key is None:
                key = str(uuid.uuid4())
                self.pending_keys[("engine", engine.get("id") if editing else "new")] = key

            def work():
                if editing:
                    return self.client.update_engine(int(engine["id"]), body, key)
                return self.client.create_engine(body, key)

            def done(result):
                self.pending_keys.pop(("engine", engine.get("id") if editing else "new"), None)
                dlg.destroy()
                self._show_toast("Engine saved.")
                self._refresh_current()

            def err(e):
                messagebox.showerror("Save failed", e, parent=dlg)

            self.set_status("Saving engine...")
            self._run_threaded(work, done, err)

        ttk.Button(btns, text="Cancel", command=dlg.destroy).pack(side="right", padx=5)
        ttk.Button(btns, text="Save", command=submit).pack(side="right")

    def _edit_engine(self) -> None:
        eid = self._selected_id(self.tree_engines)
        if eid is None:
            return
        engine = next((e for e in self.client.list_engines() if e["id"] == eid), None)
        if engine:
            self._dlg_engine(engine)

    def _delete_engine(self) -> None:
        eid = self._selected_id(self.tree_engines)
        if eid is None:
            return
        if not messagebox.askyesno("Delete", "Delete this engine record? (Active engines cannot be deleted)"):
            return
        key = str(uuid.uuid4())

        def work():
            return self.client.delete_engine(eid, key)

        def done(result):
            self._show_toast("Engine record deleted.")
            self._refresh_current()

        self.set_status("Deleting engine...")
        self._run_threaded(work, done)

    # ---- expenses ----

    def _view_expenses(self) -> None:
        t = self.theme
        self._set_nav("Expenses")

        bar = ttk.Frame(self.content, style="Panel.TFrame")
        bar.pack(fill="x", padx=20, pady=(16, 6))
        ttk.Label(bar, text="Expenses", style="Panel.Title.TLabel").pack(side="left")
        ttk.Button(bar, text="Add expense", command=self._dlg_expense).pack(side="right")

        tree = self._tree(
            self.content,
            ("id", "date", "vehicle", "category", "vendor", "amount", "description"),
            ("ID", "Date", "Vehicle", "Category", "Vendor", "Amount", "Description"),
            {"id": 40, "date": 100, "vehicle": 160, "category": 100, "vendor": 140, "amount": 90, "description": 260},
        )
        tree.pack(fill="both", expand=True, padx=20, pady=(0, 10))
        self.tree_expenses = tree

        self.exp_total_var = tk.StringVar(value="Total: –")
        btns = ttk.Frame(self.content, style="Panel.TFrame")
        btns.pack(fill="x", padx=20, pady=(0, 16))
        ttk.Label(btns, textvariable=self.exp_total_var, font=("Segoe UI", 10, "bold")).pack(side="left")
        ttk.Button(btns, text="Edit", command=self._edit_expense).pack(side="right", padx=3)
        ttk.Button(btns, text="Delete", command=self._delete_expense).pack(side="right", padx=3)
        ttk.Button(btns, text="Refresh", command=self._refresh_current).pack(side="right", padx=3)

        self._load_expenses()

    def _load_expenses(self) -> None:
        def work():
            return self.client.list_expenses()

        def done(data):
            self._clear_tree(self.tree_expenses)
            total = 0.0
            for x in data:
                amt = float(x.get("amount") or 0)
                total += amt
                self.tree_expenses.insert("", "end", values=(
                    x.get("id"), x.get("expense_date", ""),
                    f"{x.get('make','')} {x.get('model','')}".strip(),
                    x.get("category", ""), x.get("vendor", ""),
                    f"{amt:,.2f}", x.get("description", ""),
                ))
            self.exp_total_var.set(f"Total: ${total:,.2f}")
            self.set_status(f"{len(data)} expense(s)")

        self.set_status("Loading expenses...")
        self._run_threaded(work, done)

    def _dlg_expense(self, expense: Optional[dict] = None) -> None:
        t = self.theme
        editing = expense is not None
        vehicles = self.client.list_vehicles()
        dlg = tk.Toplevel(self)
        dlg.title("Edit expense" if editing else "Record expense")
        dlg.configure(bg=t["panel"])
        dlg.transient(self)
        dlg.grab_set()

        fields = {}
        def add_row(label, key, widget, default=""):
            box = ttk.Frame(dlg, style="Panel.TFrame")
            box.pack(fill="x", padx=16, pady=6)
            ttk.Label(box, text=label, style="Heading.TLabel").pack(anchor="w")
            widget.pack(fill="x", pady=(4, 0))
            if default not in (None, ""):
                try:
                    widget.delete(0, "end")
                    widget.insert(0, default)
                except Exception:
                    pass
            fields[key] = widget

        vbox = ttk.Frame(dlg, style="Panel.TFrame")
        ttk.Label(vbox, text="Vehicle", style="Heading.TLabel").pack(anchor="w")
        vcombo = ttk.Combobox(vbox, values=[f"{v['id']} | {v.get('make','')} {v.get('model','')}" for v in vehicles],
                              state="readonly")
        if editing:
            vcombo.set(next((f"{v['id']} | {v.get('make','')} {v.get('model','')}" for v in vehicles if v["id"] == expense["vehicle_id"]), ""))
        add_row("Vehicle", "vehicle", vcombo)

        add_row("Date (YYYY-MM-DD)", "expense_date", tk.Entry(dlg), expense.get("expense_date", date.today().isoformat()) if editing else date.today().isoformat())

        cat_box = ttk.Frame(dlg, style="Panel.TFrame")
        ttk.Label(cat_box, text="Category", style="Heading.TLabel").pack(anchor="w")
        cat_var = tk.StringVar(value=expense.get("category", "other") if editing else "other")
        ttk.Combobox(cat_box, textvariable=cat_var, values=["fuel", "repair", "maintenance", "parts", "other"], state="readonly").pack(fill="x", pady=(4, 0))
        cat_box.pack(fill="x", padx=16, pady=6)
        fields["category"] = cat_var

        add_row("Vendor", "vendor", tk.Entry(dlg), expense.get("vendor", "") if editing else "")
        add_row("Amount", "amount", tk.Entry(dlg), str(expense.get("amount", "")) if editing else "")
        add_row("Description", "description", tk.Entry(dlg), expense.get("description", "") if editing else "")

        inv_box = ttk.Frame(dlg, style="Panel.TFrame")
        ttk.Label(inv_box, text="Related invoice (optional)", style="Heading.TLabel").pack(anchor="w")
        inv_var = tk.StringVar(value="")
        inv_combo = ttk.Combobox(inv_box, textvariable=inv_var, state="readonly")
        inv_combo.pack(fill="x", pady=(4, 0))
        inv_box.pack(fill="x", padx=16, pady=6)
        fields["invoice_id"] = inv_var

        try:
            invoices = self.client.list_invoices()
            inv_combo["values"] = [f"{i['id']} | #{i['invoice_number']} (balance ${float(i.get('balance', 0) or 0):.2f})" for i in invoices]
            if editing and expense.get("invoice_id"):
                inv_combo.set(next((f"{i['id']} | #{i['invoice_number']} ..." for i in invoices if i["id"] == expense["invoice_id"]), ""))
        except Exception:
            pass

        btns = ttk.Frame(dlg, style="Panel.TFrame")
        btns.pack(fill="x", padx=16, pady=(10, 16))

        def submit():
            try:
                amt = float(fields["amount"].get())
            except ValueError:
                messagebox.showwarning("Validation", "Enter a numeric amount.", parent=dlg)
                return
            if amt < 0:
                messagebox.showwarning("Validation", "Amount cannot be negative.", parent=dlg)
                return
            sel = fields["vehicle"].get()
            vid = int(sel.split(" | ")[0]) if sel else None
            if not vid:
                messagebox.showwarning("Validation", "Select a vehicle.", parent=dlg)
                return
            inv_sel = fields["invoice_id"].get()
            inv_id = int(inv_sel.split(" | ")[0]) if inv_sel else None
            body = {
                "vehicle_id": vid,
                "expense_date": fields["expense_date"].get(),
                "category": fields["category"].get(),
                "vendor": fields["vendor"].get(),
                "amount": amt,
                "description": fields["description"].get(),
                "invoice_id": inv_id,
            }
            key = self.pending_keys.get(("expense", expense.get("id") if editing else "new"))
            if key is None:
                key = str(uuid.uuid4())
                self.pending_keys[("expense", expense.get("id") if editing else "new")] = key

            def work():
                if editing:
                    return self.client.update_expense(int(expense["id"]), body, key)
                return self.client.create_expense(body, key)

            def done(result):
                self.pending_keys.pop(("expense", expense.get("id") if editing else "new"), None)
                dlg.destroy()
                self._show_toast("Expense saved.")
                self._refresh_current()

            def err(e):
                messagebox.showerror("Save failed", e, parent=dlg)

            self.set_status("Saving expense...")
            self._run_threaded(work, done, err)

        ttk.Button(btns, text="Cancel", command=dlg.destroy).pack(side="right", padx=5)
        ttk.Button(btns, text="Save", command=submit).pack(side="right")

    def _edit_expense(self) -> None:
        eid = self._selected_id(self.tree_expenses)
        if eid is None:
            return
        expense = next((e for e in self.client.list_expenses() if e["id"] == eid), None)
        if expense:
            self._dlg_expense(expense)

    def _delete_expense(self) -> None:
        eid = self._selected_id(self.tree_expenses)
        if eid is None:
            return
        if not messagebox.askyesno("Delete", "Delete this expense?"):
            return
        key = str(uuid.uuid4())

        def work():
            return self.client.delete_expense(eid, key)

        def done(result):
            self._show_toast("Expense deleted.")
            self._refresh_current()

        self.set_status("Deleting expense...")
        self._run_threaded(work, done)

    # ---- invoices ----

    def _view_invoices(self) -> None:
        t = self.theme
        self._set_nav("Invoices")

        bar = ttk.Frame(self.content, style="Panel.TFrame")
        bar.pack(fill="x", padx=20, pady=(16, 6))
        ttk.Label(bar, text="Invoices", style="Panel.Title.TLabel").pack(side="left")
        ttk.Button(bar, text="New invoice", command=self._dlg_invoice).pack(side="right")

        tree = self._tree(
            self.content,
            ("id", "date", "number", "mechanic", "amount", "paid", "balance", "due", "status"),
            ("ID", "Date", "Number", "Mechanic", "Amount", "Paid", "Balance", "Due", "Status"),
            {"id": 40, "date": 100, "number": 110, "mechanic": 140, "amount": 90, "paid": 90, "balance": 90, "due": 100, "status": 90},
        )
        tree.pack(fill="both", expand=True, padx=20, pady=(0, 10))
        self.tree_invoices = tree

        btns = ttk.Frame(self.content, style="Panel.TFrame")
        btns.pack(fill="x", padx=20, pady=(0, 16))
        ttk.Button(btns, text="Edit", command=self._edit_invoice).pack(side="left", padx=3)
        ttk.Button(btns, text="Record payment", command=self._dlg_payment).pack(side="left", padx=3)
        ttk.Button(btns, text="View payments", command=self._view_payments_for_selected).pack(side="left", padx=3)
        ttk.Button(btns, text="Refresh", command=self._refresh_current).pack(side="left", padx=3)

        self._load_invoices()

    def _load_invoices(self) -> None:
        def work():
            return self.client.list_invoices()

        def done(data):
            self._clear_tree(self.tree_invoices)
            for inv in data:
                bal = float(inv.get("balance", 0) or 0)
                status = "Overdue" if inv.get("overdue") else ("Paid" if bal <= 0 else inv.get("status", "open"))
                self.tree_invoices.insert("", "end", values=(
                    inv.get("id"), inv.get("invoice_date", ""), inv.get("invoice_number", ""),
                    inv.get("mechanic_name", ""),
                    f"{float(inv.get('amount', 0) or 0):,.2f}",
                    f"{float(inv.get('paid', 0) or 0):,.2f}",
                    f"{bal:,.2f}",
                    inv.get("due_date", ""),
                    status,
                ))
            self.set_status(f"{len(data)} invoice(s)")

        self.set_status("Loading invoices...")
        self._run_threaded(work, done)

    def _dlg_invoice(self, invoice: Optional[dict] = None) -> None:
        t = self.theme
        editing = invoice is not None
        mechanics = self.client.list_mechanics()
        dlg = tk.Toplevel(self)
        dlg.title("Edit invoice" if editing else "New invoice")
        dlg.configure(bg=t["panel"])
        dlg.transient(self)
        dlg.grab_set()

        fields = {}
        def add_row(label, key, widget, default=""):
            box = ttk.Frame(dlg, style="Panel.TFrame")
            box.pack(fill="x", padx=16, pady=6)
            ttk.Label(box, text=label, style="Heading.TLabel").pack(anchor="w")
            widget.pack(fill="x", pady=(4, 0))
            if default not in (None, ""):
                try:
                    widget.delete(0, "end")
                    widget.insert(0, default)
                except Exception:
                    pass
            fields[key] = widget

        mbox = ttk.Frame(dlg, style="Panel.TFrame")
        ttk.Label(mbox, text="Mechanic", style="Heading.TLabel").pack(anchor="w")
        mcombo = ttk.Combobox(mbox, values=[f"{m['id']} | {m.get('name','')}" for m in mechanics], state="readonly")
        if editing:
            mcombo.set(next((f"{m['id']} | {m.get('name','')}" for m in mechanics if m["id"] == invoice["mechanic_id"]), ""))
        add_row("Mechanic", "mechanic", mcombo)
        add_row("Invoice number", "invoice_number", tk.Entry(dlg), invoice.get("invoice_number", "") if editing else "")
        add_row("Invoice date", "invoice_date", tk.Entry(dlg), invoice.get("invoice_date", date.today().isoformat()) if editing else date.today().isoformat())
        add_row("Due date", "due_date", tk.Entry(dlg), invoice.get("due_date", "") if editing else "")
        add_row("Amount", "amount", tk.Entry(dlg), str(invoice.get("amount", "")) if editing else "")
        add_row("Notes", "notes", tk.Entry(dlg), invoice.get("notes", "") if editing else "")

        status_box = ttk.Frame(dlg, style="Panel.TFrame")
        ttk.Label(status_box, text="Status", style="Heading.TLabel").pack(anchor="w")
        status_var = tk.StringVar(value=invoice.get("status", "open") if editing else "open")
        ttk.Combobox(status_box, textvariable=status_var, values=["open", "paid", "overdue", "void"], state="readonly").pack(fill="x", pady=(4, 0))
        status_box.pack(fill="x", padx=16, pady=6)
        fields["status"] = status_var

        btns = ttk.Frame(dlg, style="Panel.TFrame")
        btns.pack(fill="x", padx=16, pady=(10, 16))

        def submit():
            try:
                amt = float(fields["amount"].get())
            except ValueError:
                messagebox.showwarning("Validation", "Enter a numeric amount.", parent=dlg)
                return
            if amt < 0:
                messagebox.showwarning("Validation", "Amount cannot be negative.", parent=dlg)
                return
            sel = fields["mechanic"].get()
            mid = int(sel.split(" | ")[0]) if sel else None
            if not mid:
                messagebox.showwarning("Validation", "Select a mechanic.", parent=dlg)
                return
            body = {
                "mechanic_id": mid,
                "invoice_number": fields["invoice_number"].get(),
                "invoice_date": fields["invoice_date"].get(),
                "due_date": fields["due_date"].get(),
                "amount": amt,
                "status": fields["status"].get(),
                "notes": fields["notes"].get(),
            }
            key = self.pending_keys.get(("invoice", invoice.get("id") if editing else "new"))
            if key is None:
                key = str(uuid.uuid4())
                self.pending_keys[("invoice", invoice.get("id") if editing else "new")] = key

            def work():
                if editing:
                    return self.client.update_invoice(int(invoice["id"]), body, key)
                return self.client.create_invoice(body, key)

            def done(result):
                self.pending_keys.pop(("invoice", invoice.get("id") if editing else "new"), None)
                dlg.destroy()
                self._show_toast("Invoice saved.")
                self._refresh_current()

            def err(e):
                messagebox.showerror("Save failed", e, parent=dlg)

            self.set_status("Saving invoice...")
            self._run_threaded(work, done, err)

        ttk.Button(btns, text="Cancel", command=dlg.destroy).pack(side="right", padx=5)
        ttk.Button(btns, text="Save", command=submit).pack(side="right")

    def _edit_invoice(self) -> None:
        iid = self._selected_id(self.tree_invoices)
        if iid is None:
            return
        invoice = next((i for i in self.client.list_invoices() if i["id"] == iid), None)
        if invoice:
            self._dlg_invoice(invoice)

    def _dlg_payment(self) -> None:
        iid = self._selected_id(self.tree_invoices)
        if iid is None:
            return
        invoice = next((i for i in self.client.list_invoices() if i["id"] == iid), None)
        if not invoice:
            return
        bal = float(invoice.get("balance", 0) or 0)
        if bal <= 0:
            messagebox.showinfo("Paid", "This invoice has no outstanding balance.")
            return

        t = self.theme
        dlg = tk.Toplevel(self)
        dlg.title("Record payment")
        dlg.configure(bg=t["panel"])
        dlg.transient(self)
        dlg.grab_set()

        fields = {}
        def add_row(label, key, widget, default=""):
            box = ttk.Frame(dlg, style="Panel.TFrame")
            box.pack(fill="x", padx=16, pady=6)
            ttk.Label(box, text=label, style="Heading.TLabel").pack(anchor="w")
            widget.pack(fill="x", pady=(4, 0))
            if default not in (None, ""):
                try:
                    widget.delete(0, "end")
                    widget.insert(0, default)
                except Exception:
                    pass
            fields[key] = widget

        ttk.Label(dlg, text=f"Invoice #{invoice['id']} — outstanding balance: ${bal:,.2f}",
                  style="Heading.TLabel").pack(pady=(16, 6), padx=16, anchor="w")
        add_row("Payment date", "payment_date", tk.Entry(dlg), date.today().isoformat())
        add_row("Amount", "amount", tk.Entry(dlg), f"{bal:,.2f}")
        add_row("Method", "method", ttk.Combobox(dlg, values=["Cash", "Card", "Transfer", "Check"], state="readonly"), "Cash")
        add_row("Notes", "notes", tk.Entry(dlg), "")

        btns = ttk.Frame(dlg, style="Panel.TFrame")
        btns.pack(fill="x", padx=16, pady=(10, 16))

        def submit():
            try:
                amt = float(fields["amount"].get())
            except ValueError:
                messagebox.showwarning("Validation", "Enter a numeric amount.", parent=dlg)
                return
            if amt <= 0:
                messagebox.showwarning("Validation", "Amount must be greater than zero.", parent=dlg)
                return
            if amt > bal:
                messagebox.showwarning("Validation", "Payment exceeds outstanding balance.", parent=dlg)
                return
            body = {
                "payment_date": fields["payment_date"].get(),
                "amount": amt,
                "method": fields["method"].get(),
                "notes": fields["notes"].get(),
            }
            key = str(uuid.uuid4())

            def work():
                return self.client.create_payment(iid, body, key)

            def done(result):
                dlg.destroy()
                self._show_toast("Payment recorded.")
                self._refresh_current()

            def err(e):
                messagebox.showerror("Save failed", e, parent=dlg)

            self.set_status("Recording payment...")
            self._run_threaded(work, done, err)

        ttk.Button(btns, text="Cancel", command=dlg.destroy).pack(side="right", padx=5)
        ttk.Button(btns, text="Record", command=submit).pack(side="right")

    def _view_payments_for_selected(self) -> None:
        iid = self._selected_id(self.tree_invoices)
        if iid is None:
            return
        self._payments_window(iid)

    def _payments_window(self, invoice_id: int) -> None:
        t = self.theme
        win = tk.Toplevel(self)
        win.title("Payments")
        win.configure(bg=t["panel"])
        win.transient(self)
        win.grab_set()

        tree = self._tree(
            win,
            ("id", "date", "amount", "method", "notes"),
            ("ID", "Date", "Amount", "Method", "Notes"),
            {"id": 40, "date": 100, "amount": 90, "method": 90, "notes": 260},
        )
        tree.pack(fill="both", expand=True, padx=16, pady=16)

        def load():
            def work():
                return self.client.list_payments(invoice_id)

            def done(data):
                self._clear_tree(tree)
                for p in data:
                    tree.insert("", "end", values=(
                        p.get("id"), p.get("payment_date", ""),
                        f"{float(p.get('amount', 0) or 0):,.2f}",
                        p.get("method", ""), p.get("notes", ""),
                    ))

            self._run_threaded(work, done)

        load()

    # ---------- settings ----------

    def _view_settings(self) -> None:
        t = self.theme
        self._set_nav("Settings")

        frame = ttk.Frame(self.content, style="Panel.TFrame")
        frame.pack(fill="both", expand=True, padx=20, pady=16)

        ttk.Label(frame, text="Connection", style="Panel.Title.TLabel").pack(anchor="w", pady=(0, 8))

        card = ttk.Frame(frame, style="Panel.TFrame")
        card.pack(fill="x", pady=(0, 16))

        self.cfg_url = tk.Entry(card, bg=t["entry_bg"], fg=t["entry_fg"], insertbackground=t["text"],
                                relief="flat", highlightthickness=1, highlightbackground=t["border"],
                                highlightcolor=t["primary"])
        self.cfg_token = tk.Entry(card, show="•", bg=t["entry_bg"], fg=t["entry_fg"], insertbackground=t["text"],
                                  relief="flat", highlightthickness=1, highlightbackground=t["border"],
                                  highlightcolor=t["primary"])
        self.cfg_url.insert(0, self.settings.get("base_url", ""))
        self.cfg_token.insert(0, self.settings.get("token", ""))

        ttk.Label(card, text="Website URL", style="Heading.TLabel").pack(anchor="w", padx=14, pady=(14, 2))
        self.cfg_url.pack(fill="x", padx=14, pady=(0, 10))
        ttk.Label(card, text="API token", style="Heading.TLabel").pack(anchor="w", padx=14, pady=(0, 2))
        self.cfg_token.pack(fill="x", padx=14, pady=(0, 14))

        ttk.Button(card, text="Save connection", command=self._save_connection).pack(anchor="e", padx=14, pady=(0, 14))

        ttk.Label(frame, text="Theme", style="Panel.Title.TLabel").pack(anchor="w", pady=(0, 8))
        ttk.Button(frame, text="Switch light/dark theme", command=self._cycle_theme).pack(anchor="w", pady=(0, 4))
        ttk.Label(frame, text="Your theme choice is saved on this computer and persists when you reopen the app.",
                  style="Muted.TLabel").pack(anchor="w", pady=(0, 16))

        ttk.Label(frame, text="About", style="Panel.Title.TLabel").pack(anchor="w")
        ttk.Label(frame, text="Vehicle Tracker desktop app v1.0\n"
                              "Reads and writes through the website's authenticated JSON API.\n"
                              "No local database; the PHP server owns all records and calculations.",
                  style="Muted.TLabel", justify="left").pack(anchor="w")

    def _save_connection(self) -> None:
        url = self.cfg_url.get().strip()
        token = self.cfg_token.get().strip()
        self.settings["base_url"] = url
        self.settings["token"] = token
        save_settings(self.settings)
        self.client = ApiClient(url, token) if url else None
        self.connected = bool(url and token)
        self.conn_label.configure(text=("Connected: " + url) if self.connected else "Not connected")
        self._show_toast("Connection settings saved.")
        self._refresh_current()


def main() -> None:
    app = App()
    app.mainloop()


if __name__ == "__main__":
    main()
