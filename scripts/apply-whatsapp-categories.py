#!/usr/bin/env python3
"""Assign WooCommerce category slugs to each WP-* row in whatsapp-catalog.json."""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CATALOG = ROOT / "wordpress-theme/jwellery-jewelry/assets/demo-products/whatsapp-catalog.json"

# Mapped from product photos (SKU => category slug list).
SKU_CATEGORIES = {
    "WP-001": ["ear-rings"],
    "WP-002": ["ear-rings"],
    "WP-003": ["ear-rings"],
    "WP-004": ["combo"],
    "WP-005": ["combo"],
    "WP-006": ["ear-rings"],
    "WP-007": ["ear-rings"],
    "WP-008": ["studs"],
    "WP-009": ["ear-rings"],
    "WP-010": ["ear-rings"],
    "WP-011": ["ear-rings"],
    "WP-012": ["ear-rings"],
    "WP-013": ["ear-rings"],
    "WP-014": ["studs"],
    "WP-015": ["studs"],
    "WP-016": ["studs"],
    "WP-017": ["ear-rings"],
    "WP-018": ["ear-rings"],
    "WP-019": ["ear-rings"],
    "WP-020": ["ear-rings"],
    "WP-021": ["ear-rings"],
    "WP-022": ["ear-rings"],
    "WP-023": ["ear-rings"],
    "WP-024": ["ear-rings"],
    "WP-025": ["ear-rings"],
    "WP-026": ["ear-rings"],
    "WP-027": ["instagram-collection"],
    "WP-028": ["combo"],
    "WP-029": ["rings"],
    "WP-030": ["rings"],
    "WP-031": ["rings"],
    "WP-032": ["rings"],
    "WP-033": ["rings"],
    "WP-034": ["bangles"],
    "WP-035": ["bangles"],
    "WP-036": ["rings"],
    "WP-037": ["rings"],
    "WP-038": ["rings"],
    "WP-039": ["rings"],
}


def main():
    data = json.loads(CATALOG.read_text(encoding="utf-8"))
    updated = 0
    for item in data:
        sku = item.get("sku")
        if sku in SKU_CATEGORIES:
            item["categories"] = SKU_CATEGORIES[sku]
            updated += 1
    CATALOG.write_text(json.dumps(data, indent=2), encoding="utf-8")
    print(f"Updated categories for {updated} product(s) in {CATALOG}")


if __name__ == "__main__":
    main()
