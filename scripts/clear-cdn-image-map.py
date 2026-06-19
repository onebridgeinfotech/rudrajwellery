#!/usr/bin/env python3
"""Remove remote CDN URLs so only bundled WhatsApp images are used."""
import json
from pathlib import Path

MAP = Path(__file__).resolve().parents[1] / (
    "wordpress-theme/jwellery-jewelry/assets/demo-products/images-map.json"
)


def main():
    data = json.loads(MAP.read_text(encoding="utf-8-sig"))
    for sku in data:
        if isinstance(data[sku], dict):
            data[sku]["image"] = ""
    MAP.write_text(json.dumps(data, indent=4), encoding="utf-8")
    print(f"Cleared CDN URLs in {MAP} ({len(data)} SKUs)")


if __name__ == "__main__":
    main()
