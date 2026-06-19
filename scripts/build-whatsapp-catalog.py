#!/usr/bin/env python3
"""Build whatsapp-catalog.json + copy one image per unique stamp into demo-products."""
import json
import re
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ASSETS = Path(r"C:\Users\HP\.cursor\projects\d-jwellery-ecommerce\assets")
OUT_DIR = ROOT / "wordpress-theme/jwellery-jewelry/assets/demo-products"
MANIFEST = ROOT / "sample-data/whatsapp-product-images.json"

STAMP_RE = re.compile(
    r"WhatsApp_Image_(.+?)(?:-[a-f0-9-]{36})?\.(?:png|jpe?g)$",
    re.IGNORECASE,
)


def stamp_from_filename(name: str):
    match = STAMP_RE.search(name)
    return match.group(1) if match else None


def load_used_stamps():
    used = set()
    if MANIFEST.is_file():
        data = json.loads(MANIFEST.read_text(encoding="utf-8"))
        for entry in data.get("products", {}).values():
            for stamp in entry.get("images", []):
                used.add(stamp)
    return used


def collect_files_by_stamp():
    files_by_stamp = {}
    if not ASSETS.is_dir():
        return files_by_stamp
    for path in ASSETS.rglob("*"):
        if not path.is_file() or "WhatsApp_Image" not in path.name:
            continue
        match = STAMP_RE.match(path.name)
        if not match:
            stamp = stamp_from_filename(path.name)
            if not stamp:
                continue
        else:
            stamp = match.group(1)
        prev = files_by_stamp.get(stamp)
        if not prev or path.stat().st_size > prev.stat().st_size:
            files_by_stamp[stamp] = path
    return files_by_stamp


def main():
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    used_stamps = load_used_stamps()
    files_by_stamp = collect_files_by_stamp()
    extra = []
    n = 1

    for stamp in sorted(files_by_stamp.keys()):
        if stamp in used_stamps:
            continue

        sku = f"WP-{n:03d}"
        src = files_by_stamp[stamp]
        ext = src.suffix.lower()
        if ext == ".jpeg":
            ext = ".jpg"
        dest = OUT_DIR / f"{sku}{ext}"
        shutil.copy2(src, dest)

        extra.append(
            {
                "sku": sku,
                "name": f"Jewelry item {n}",
                "price": 399,
                "stock": 10,
                "featured": 0,
                "categories": ["latest-collection"],
                "description": "Imitation jewelry — update name and price in admin.",
                "stamp": stamp,
            }
        )
        used_stamps.add(stamp)
        n += 1

    catalog_path = OUT_DIR / "whatsapp-catalog.json"
    catalog_path.write_text(json.dumps(extra, indent=2), encoding="utf-8")
    print(f"Catalog: {len(extra)} new product(s) -> {catalog_path}")
    print(f"Skipped {len(files_by_stamp) - len(extra)} stamp(s) already mapped to existing SKUs.")


if __name__ == "__main__":
    main()
