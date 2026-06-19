#!/usr/bin/env python3
"""Build recovered-catalog-overrides.json from live HTML snapshots."""
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
HTML = ROOT / "live-shop-check.html"
OUT = ROOT / "wordpress-theme/jwellery-jewelry/assets/demo-products/recovered-catalog-overrides.json"

html = HTML.read_text(encoding="utf-8", errors="ignore")
blocks = re.split(r'<li class="product', html)[1:]
out: dict[str, dict] = {}

for block in blocks:
    title = re.search(r"jwellery-product-title\">([^<]+)", block)
    sku = re.search(r"/WP-(\d+)", block)
    price = re.search(
        r"woocommerce-Price-amount amount[^>]*>.*?(\d[\d,]*)</span>",
        block,
        re.S,
    )
    if not title or not sku:
        continue
    name = title.group(1).strip()
    if re.match(r"^Jewelry item \d+$", name, re.I):
        continue
    key = f"WP-{sku.group(1)}"
    row: dict[str, str] = {"name": name}
    if price:
        row["price"] = price.group(1).replace(",", "")
    out[key] = row

out["WP-036"] = {"name": "Padagam Ring", "price": "250"}

OUT.write_text(json.dumps(out, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
print(f"Wrote {len(out)} entries to {OUT}")
