#!/usr/bin/env python3
import re
import requests

BASE = "https://brown-llama-127224.hostingersite.com"
import os

USER = os.environ.get("JWELLERY_WP_USER", "")
PASSWORD = os.environ.get("JWELLERY_WP_PASSWORD", "")

s = requests.Session()
s.headers["User-Agent"] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"

s.get(f"{BASE}/wp-login.php", timeout=30)
s.post(
    f"{BASE}/wp-login.php",
    data={
        "log": USER,
        "pwd": PASSWORD,
        "wp-submit": "Log In",
        "redirect_to": f"{BASE}/wp-admin/",
        "testcookie": "1",
    },
    timeout=30,
)

page = s.get(f"{BASE}/wp-json/wp/v2/pages?slug=checkout", timeout=30).json()[0]
page_id = page["id"]
print("page_id", page_id)
print("before_block", "woocommerce/checkout" in page["content"]["rendered"])

edit = s.get(f"{BASE}/wp-admin/post.php?post={page_id}&action=edit", timeout=30)
nonce = re.search(r'name="_wpnonce" value="([^"]+)"', edit.text)
if not nonce:
    print("NO_EDIT_NONCE")
    raise SystemExit(1)

content = "<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->"
resp = s.post(
    f"{BASE}/wp-admin/post.php",
    data={
        "post_ID": str(page_id),
        "post_type": "page",
        "action": "editpost",
        "post_status": "publish",
        "content": content,
        "_wpnonce": nonce.group(1),
        "_wp_http_referer": f"/wp-admin/post.php?post={page_id}&action=edit",
        "post_title": "Checkout",
        "save": "Update",
    },
    timeout=60,
    allow_redirects=True,
)
print("update_status", resp.status_code, resp.url)

after = s.get(f"{BASE}/wp-json/wp/v2/pages?slug=checkout", timeout=30).json()[0]
rendered = after["content"]["rendered"]
print("after_block", "woocommerce/checkout" in rendered)
print("after_shortcode", "woocommerce_checkout" in rendered)

s.get(f"{BASE}/?add-to-cart=40", timeout=30)
checkout = s.get(f"{BASE}/checkout/", timeout=30)
text = checkout.text.lower()
print("frontend_classic", "woocommerce-checkout" in checkout.text)
print("frontend_block", "wc-block-checkout" in checkout.text)
print("frontend_upi", "pay via upi" in text)
print("frontend_error", "no payment methods" in text)
