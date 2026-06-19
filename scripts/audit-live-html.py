#!/usr/bin/env python3
import re
import sys
from collections import Counter
from pathlib import Path

path = Path(sys.argv[1] if len(sys.argv) > 1 else "shop-live-now.html")
html = path.read_text(encoding="utf-8", errors="ignore")

titles = re.findall(r"jwellery-product-title\">([^<]+)", html)
if not titles:
    titles = re.findall(r"woocommerce-loop-product__title[^>]*>([^<]+)", html)

imgs = []
for u in re.findall(r'src="(https://www\.rudrajewellery\.co\.in/wp-content/uploads/[^"?]+)', html):
    base = u.split("/")[-1]
    base = re.sub(r"-\d+x\d+(?=\.)", "", base)
    imgs.append(base)

prices = re.findall(r"Price-currencySymbol[^<]*</span>([\d,]+)", html)

print(f"file: {path.name}")
print(f"titles: {len(titles)} unique: {len(set(titles))}")
print(f"images: {len(imgs)} unique: {len(set(imgs))}")
dups = [k for k, v in Counter(imgs).items() if v > 1]
print(f"duplicate image files: {len(dups)}")
for d in sorted(dups, key=lambda x: -Counter(imgs)[x])[:15]:
    print(f"  {d} x{Counter(imgs)[d]}")
print(f"prices found: {len(prices)} sample: {prices[:12]}")
for t in titles[:20]:
    print(f"  - {t}")
