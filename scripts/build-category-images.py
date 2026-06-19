#!/usr/bin/env python3
"""Copy representative WhatsApp/bundled product photos into category-images/."""
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DEMO = ROOT / "wordpress-theme/jwellery-jewelry/assets/demo-products"
OUT = ROOT / "wordpress-theme/jwellery-jewelry/assets/category-images"

# Recent WhatsApp uploads (prefer last-2-day batch; fallback SKUs from June 16 batch).
CATEGORY_COVERS = {
    "ear-rings": "WP-021.png",
    "studs": "WP-016.png",
    "necklaces": "WP-028.png",
    "chockers": "WP-028.png",
    "bangles": "WP-034.png",
    "rings": "WP-038.png",
    "long-harams": "WP-028.png",
    "handmade-collection": "WP-026.png",
    "instagram-collection": "WP-021.png",
    "latest-collection": "WP-039.png",
    "combo": "WP-028.png",
}


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    for slug, src_name in CATEGORY_COVERS.items():
        src = DEMO / src_name
        if not src.is_file():
            print(f"SKIP {slug}: missing {src_name}")
            continue
        ext = src.suffix.lower()
        dest = OUT / f"{slug}{ext}"
        shutil.copy2(src, dest)
        print(f"OK {slug} <- {src_name}")


if __name__ == "__main__":
    main()
