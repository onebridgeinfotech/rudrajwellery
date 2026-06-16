#!/usr/bin/env python3
"""Build WordPress-compatible theme ZIPs (forward slashes only)."""
from __future__ import annotations

import os
import sys
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEME_DIR = os.path.join(ROOT, "wordpress-theme", "jwellery-jewelry")
THEME_SLUG = "jwellery-jewelry"
NESTED_ZIP = os.path.join(ROOT, "jwellery-jewelry.zip")
FLAT_ZIP = os.path.join(ROOT, "jwellery-jewelry-flat.zip")


def iter_theme_files():
    for dirpath, _dirnames, filenames in os.walk(THEME_DIR):
        for name in filenames:
            full = os.path.join(dirpath, name)
            rel = os.path.relpath(full, THEME_DIR).replace("\\", "/")
            yield full, rel


def write_zip(path: str, prefix: str) -> None:
    if os.path.exists(path):
        os.remove(path)
    with zipfile.ZipFile(path, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
        for full, rel in iter_theme_files():
            arc = f"{prefix}{rel}" if prefix else rel
            if "\\" in arc:
                raise RuntimeError(f"Backslash in zip path: {arc}")
            zf.write(full, arc)
    with zipfile.ZipFile(path, "r") as zf:
        names = zf.namelist()
        if not any(n.endswith("style.css") for n in names):
            raise RuntimeError(f"style.css missing in {path}")


def main() -> int:
    style = os.path.join(THEME_DIR, "style.css")
    if not os.path.isfile(style):
        print(f"Theme not found: {style}", file=sys.stderr)
        return 1

    write_zip(NESTED_ZIP, f"{THEME_SLUG}/")
    write_zip(FLAT_ZIP, "")

    for path in (NESTED_ZIP, FLAT_ZIP):
        size_kb = os.path.getsize(path) // 1024
        print(f"Created: {path} ({size_kb} KB)")

    with zipfile.ZipFile(NESTED_ZIP, "r") as zf:
        entry = f"{THEME_SLUG}/style.css"
        if entry not in zf.namelist():
            print(f"Verify failed: {entry}", file=sys.stderr)
            return 1
    print(f"Verified: {THEME_SLUG}/style.css")
    return 0


if __name__ == "__main__":
    sys.exit(main())
