#!/usr/bin/env python3
import re
from collections import Counter
from pathlib import Path

h = Path(r"d:\jwellery ecommerce\shop-audit.html").read_text(encoding="utf-8-sig", errors="ignore")
ids = re.findall(r'data-product-id="(\d+)"', h)
titles = re.findall(r'jwellery-product-title">([^<]+)', h)
print("product id mentions", len(ids), "unique", len(set(ids)))
print("duplicate id refs", [k for k, v in Counter(ids).items() if v > 1][:20])
print("unique titles", len(set(titles)))
print("dup titles", [t for t, c in Counter(titles).items() if c > 1])
