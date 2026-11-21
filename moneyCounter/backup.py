"""Create a consistent SQLite snapshot, or restore a snapshot to a fresh path."""

import argparse
import os
import sqlite3
from contextlib import closing
from pathlib import Path
from tempfile import TemporaryDirectory

import config

REQUIRED_TABLES = {"users", "tokens", "web_sessions", "roll_sizes", "drawers", "counts", "ledger", "audit", "requests"}


def read_only(path):
    return sqlite3.connect(Path(path).resolve().as_uri() + "?mode=ro", uri=True, timeout=10)


def check_snapshot(path):
    with closing(read_only(path)) as conn:
        if conn.execute("PRAGMA integrity_check").fetchall() != [("ok",)]:
            raise ValueError("The SQLite snapshot failed integrity checks.")
        if conn.execute("PRAGMA foreign_key_check").fetchone():
            raise ValueError("The SQLite snapshot contains invalid references.")
        tables = {row[0] for row in conn.execute("SELECT name FROM sqlite_master WHERE type='table'")}
        if not REQUIRED_TABLES <= tables:
            raise ValueError("The file is missing Money Counter tables.")


def snapshot(source, destination):
    """Copy committed state, including data in an active source WAL."""
    destination = Path(destination)
    with closing(read_only(source)) as src, closing(sqlite3.connect(destination)) as dest:
        src.backup(dest, pages=256, sleep=.05)
        dest.execute("PRAGMA journal_mode=DELETE")
    check_snapshot(destination)


def publish(source, destination):
    """Publish a complete backup atomically, failing if a destination already exists."""
    destination = Path(destination).resolve()
    if destination.exists():
        raise FileExistsError(f"Choose a new file path. {destination} already exists.")
    destination.parent.mkdir(parents=True, exist_ok=True)
    with TemporaryDirectory(prefix=".cash-backup-", dir=destination.parent) as directory:
        candidate = Path(directory) / "snapshot.sqlite3"
        snapshot(source, candidate)
        if os.name == "nt":
            candidate.rename(destination)
        else:
            os.link(candidate, destination)
    return destination


def main():
    config.load_environment()
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="action", required=True)
    backup = commands.add_parser("create")
    backup.add_argument("destination", type=Path)
    restore = commands.add_parser("restore")
    restore.add_argument("snapshot", type=Path)
    restore.add_argument("destination", type=Path)
    args = parser.parse_args()
    try:
        source = config.db_path() if args.action == "create" else args.snapshot
        print(publish(source, args.destination))
    except (ValueError, sqlite3.Error, OSError) as error:
        parser.exit(1, f"{error}\n")


if __name__ == "__main__":
    main()
