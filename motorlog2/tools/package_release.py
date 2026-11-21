#!/usr/bin/env python3
"""Create reproducible source-only XAMPP and Windows desktop release archives."""
import argparse
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED, ZipInfo
import re

APP=Path(__file__).resolve().parents[1]


def add(z, name, contents):
    info=ZipInfo(name, (2026, 11, 20, 0, 0, 0))
    info.external_attr=0o100644 << 16
    z.writestr(info, contents, compress_type=ZIP_DEFLATED, compresslevel=9)


def main():
    p=argparse.ArgumentParser(description=__doc__)
    p.add_argument('--source-zip', type=Path, default=APP.parent/'public'/'downloads'/'motorlog-xampp.zip')
    args=p.parse_args()
    desktop=APP/'desktop';version=(APP/'VERSION').read_bytes();entry=(desktop/'motorlog.py').read_bytes()
    if not re.fullmatch(rb'\d+\.\d+\.\d+\s*',version) or b"else '"+version.strip()+b"'" not in entry:
        p.error('Update the single-script fallback to match the authoritative VERSION before packaging')
    win=desktop/'motorlog-desktop.zip'
    with ZipFile(win, 'w') as z:
        add(z, 'motorlog.py', entry);add(z, 'VERSION',version)
        for name in ('requirements.txt','DESKTOP.md'):add(z, name, (desktop/name).read_bytes())
    target=args.source_zip.resolve()
    if APP in target.parents:p.error('Write the XAMPP source archive outside the XAMPP application directory.')
    target.parent.mkdir(parents=True,exist_ok=True)
    with ZipFile(target,'w') as z:
        for file in sorted(APP.rglob('*')):
            rel=file.relative_to(APP)
            if not file.is_file() or file.is_symlink() or any(t in ('__pycache__','.venv','config.lock','config.php','.env','backup','private','photos') for t in rel.parts):continue
            isdir=len(rel.parts)>1
            if isdir and rel.parts[0] not in ('assets','lib','catalog','tools','tests','desktop'):continue
            if not isdir and file.name not in ('index.php','router.php','.htaccess','VERSION','README.md'):continue
            if file.suffix not in ('.php','.py','.mjs','.js','.css','.jpg','.png','.woff2','.svg','.txt','.json','.md','.zip') and file.name not in ('VERSION','.htaccess'):continue
            add(z, 'motorlog/'+rel.as_posix(),file.read_bytes())
    print(f'Desktop: {win} ({win.stat().st_size:,} bytes)')
    print(f'XAMPP: {target} ({target.stat().st_size:,} bytes)')


if __name__=='__main__':main()
