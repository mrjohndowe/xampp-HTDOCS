"""Create the complete source ZIP without credentials, runtime data or local caches."""

from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

ROOT = Path(__file__).resolve().parent
FILES = (".env.example", ".gitignore", "README.md", "API.md", "app.py", "backup.py", "cash.py",
         "config.py", "demo.py", "mysql_backup.py", "package.py", "reporting.py", "requirements.txt",
         "requirements-mysql.txt", "schema.sql")


def main():
    target = ROOT / "dist" / "money-counter.zip"
    target.parent.mkdir(exist_ok=True)
    source = [ROOT / name for name in FILES]
    for directory in ("static", "templates", "tests"):
        source.extend(path for path in (ROOT / directory).rglob("*")
                      if path.is_file() and path.suffix in {".js", ".css", ".html", ".py"})
    with ZipFile(target, "w", compression=ZIP_DEFLATED) as archive:
        for path in sorted(source):
            archive.write(path, "money-counter/" + path.relative_to(ROOT).as_posix())
    print(target)


if __name__ == "__main__":
    main()
