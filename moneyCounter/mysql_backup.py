"""Optional MySQL snapshot worker. SQLite remains the source of truth."""

import argparse
import hashlib
import logging
import os
import re
import time
from contextlib import contextmanager
from pathlib import Path
from tempfile import TemporaryDirectory

import backup
import cash
import config

CHUNK_SIZE = 256 * 1024
LOG = logging.getLogger(__name__)


def connection_options():
    host = os.getenv("MC_MYSQL_HOST", "127.0.0.1")
    ca = os.getenv("MC_MYSQL_CA")
    if host not in {"localhost", "127.0.0.1", "::1"} and not ca:
        raise ValueError("Set MC_MYSQL_CA for a remote MySQL host.")
    options = {
        "host": host, "port": int(os.getenv("MC_MYSQL_PORT", "3306")),
        "database": os.getenv("MC_MYSQL_DATABASE"), "user": os.getenv("MC_MYSQL_USER"),
        "password": os.getenv("MC_MYSQL_PASSWORD", ""), "connect_timeout": 5,
        "read_timeout": 15, "write_timeout": 15, "autocommit": False,
    }
    if not options["database"] or not options["user"]:
        raise ValueError("Set MC_MYSQL_DATABASE and MC_MYSQL_USER to enable backups.")
    if ca:
        options.update(ssl_ca=ca, ssl_verify_cert=True, ssl_verify_identity=True)
    return options


def slot_name():
    slot = os.getenv("MC_MYSQL_SLOT", "main")
    if not re.fullmatch(r"[a-z0-9_-]{1,48}", slot):
        raise ValueError("MC_MYSQL_SLOT must contain 1–48 lowercase letters, digits, underscores or hyphens.")
    return slot


def checksum(path):
    with path.open("rb") as stream:
        return hashlib.file_digest(stream, "sha256").hexdigest()


def create_tables(cursor):
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS mc_backup_snapshots (
            slot VARCHAR(48) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
            sha256 CHAR(64) CHARACTER SET ascii NOT NULL,
            byte_count BIGINT UNSIGNED NOT NULL,
            saved_at VARCHAR(40) CHARACTER SET ascii NOT NULL
        ) ENGINE=InnoDB
    """)
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS mc_backup_chunks (
            slot VARCHAR(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            chunk_no INT UNSIGNED NOT NULL,
            content MEDIUMBLOB NOT NULL,
            PRIMARY KEY(slot, chunk_no),
            FOREIGN KEY(slot) REFERENCES mc_backup_snapshots(slot)
        ) ENGINE=InnoDB
    """)


@contextmanager
def locked_connection(options, slot):
    import pymysql

    lock = hashlib.sha256((options["database"] + ":" + slot).encode()).hexdigest()
    with pymysql.connect(**options) as conn:
        with conn.cursor() as cursor:
            cursor.execute("SELECT GET_LOCK(%s, 5)", (lock,))
            if cursor.fetchone()[0] != 1:
                raise RuntimeError("Could not acquire the backup slot lock.")
            # Closing the connection releases the advisory lock, including after a failure.
            yield conn, cursor


def synchronize(source, options, slot):
    with locked_connection(options, slot) as (conn, cursor):
        create_tables(cursor)
        with TemporaryDirectory(prefix="moneycounter-sync-") as directory:
            path = Path(directory) / "snapshot.sqlite3"
            backup.snapshot(source, path)
            signature = checksum(path)
            cursor.execute("SELECT sha256 FROM mc_backup_snapshots WHERE slot=%s", (slot,))
            existing = cursor.fetchone()
            if existing and existing[0] == signature:
                return False
            metadata = (slot, signature, path.stat().st_size, cash.stamp())
            cursor.execute(
                "INSERT INTO mc_backup_snapshots(slot, sha256, byte_count, saved_at) VALUES (%s,%s,%s,%s)"
                " ON DUPLICATE KEY UPDATE sha256=%s, byte_count=%s, saved_at=%s",
                (*metadata, *metadata[1:]))
            cursor.execute("DELETE FROM mc_backup_chunks WHERE slot=%s", (slot,))
            with path.open("rb") as stream:
                for number, chunk in enumerate(iter(lambda: stream.read(CHUNK_SIZE), b"")):
                    cursor.execute("INSERT INTO mc_backup_chunks VALUES (%s,%s,%s)", (slot, number, chunk))
            conn.commit()
            return True


def recover(options, slot, destination):
    import pymysql

    with TemporaryDirectory(prefix="moneycounter-recovery-") as directory:
        path = Path(directory) / "snapshot.sqlite3"
        with locked_connection(options, slot) as (conn, cursor):
            cursor.execute("SELECT sha256, byte_count FROM mc_backup_snapshots WHERE slot=%s", (slot,))
            metadata = cursor.fetchone()
            if not metadata:
                raise ValueError("No snapshot is stored in that slot.")
            signature, size = metadata
            with conn.cursor(pymysql.cursors.SSCursor) as chunks, path.open("wb") as stream:
                chunks.execute("SELECT chunk_no, content FROM mc_backup_chunks WHERE slot=%s ORDER BY chunk_no",
                               (slot,))
                for expected, (number, content) in enumerate(chunks):
                    if number != expected:
                        raise ValueError("MySQL snapshot has a missing chunk.")
                    stream.write(content)
        if path.stat().st_size != size or checksum(path) != signature:
            raise ValueError("MySQL snapshot failed its checksum.")
        backup.check_snapshot(path)
        return backup.publish(path, destination)


def main():
    config.load_environment()
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
    parser = argparse.ArgumentParser(description=__doc__)
    actions = parser.add_subparsers(dest="action", required=True)
    actions.add_parser("once")
    watch = actions.add_parser("watch")
    watch.add_argument("--interval", type=int, default=60)
    restore = actions.add_parser("restore")
    restore.add_argument("destination", type=Path)
    args = parser.parse_args()
    try:
        import pymysql  # Validate the optional dependency before entering the retry loop.
        options, slot = connection_options(), slot_name()
        if args.action == "restore":
            print(recover(options, slot, args.destination))
        elif args.action == "once":
            LOG.info("Snapshot uploaded." if synchronize(config.db_path(), options, slot)
                     else "Snapshot is up to date.")
        else:
            if not 5 <= args.interval <= 3600:
                raise ValueError("The backup interval must be 5–3600 seconds.")
            delay = 5
            while True:
                try:
                    if synchronize(config.db_path(), options, slot):
                        LOG.info("Snapshot uploaded.")
                    delay = 5
                    time.sleep(args.interval)
                except Exception:
                    LOG.exception("Backup failed. Retrying in %s seconds.", delay)
                    time.sleep(delay)
                    delay = min(delay * 2, 300)
    except KeyboardInterrupt:
        return
    except Exception as error:
        LOG.error("Backup command failed: %s", error)
        raise SystemExit(1) from None


if __name__ == "__main__":
    main()
