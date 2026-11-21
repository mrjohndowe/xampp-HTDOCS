"""Environment and local development configuration."""

import os
import re
from pathlib import Path
from urllib.parse import urlsplit

ROOT = Path(__file__).resolve().parent


def load_environment():
    file = ROOT / ".env"
    if file.is_file():
        for number, line in enumerate(file.read_text(encoding="utf-8-sig").splitlines(), 1):
            line = line.strip()
            if not line or line.startswith("#"):
                continue
            key, delimiter, value = line.partition("=")
            if not delimiter or not re.fullmatch(r"MC_[A-Z0-9_]+", key.strip()):
                raise ValueError(f"Invalid setting on .env line {number}.")
            value = value.strip()
            if len(value) >= 2 and value[0] == value[-1] and value[0] in "\"'":
                value = value[1:-1]
            os.environ.setdefault(key.strip(), value)


def enabled(name, default="0"):
    value = os.getenv(name, default)
    if value not in {"0", "1"}:
        raise ValueError(f"{name} must be 0 or 1.")
    return value == "1"


def origins(name):
    result = set()
    for item in os.getenv(name, "").split(","):
        item = item.strip().rstrip("/")
        if item:
            parsed = urlsplit(item)
            if (parsed.scheme not in {"https", "http"} or not parsed.hostname or parsed.username
                    or parsed.password or parsed.path or parsed.query or parsed.fragment
                    or any(character.isspace() or character in ";*'\\" for character in item)):
                raise ValueError(f"{name} must contain exact HTTP or HTTPS origins.")
            parsed.port  # Validate an explicit port.
            result.add(item)
    return result


def db_path():
    return (ROOT / os.getenv("MC_DB", "instance/cash.sqlite3")).resolve()
