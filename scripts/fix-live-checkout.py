#!/usr/bin/env python3
"""One-off fix for brown-llama live site checkout."""
import os
import re
import sys

import requests

BASE = "https://brown-llama-127224.hostingersite.com"
USER = os.environ.get("JWELLERY_WP_USER", "")
PASSWORD = os.environ.get("JWELLERY_WP_PASSWORD", "")


def login(session: requests.Session) -> bool:
    session.get(f"{BASE}/wp-login.php", timeout=30)
    resp = session.post(
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
    logged_in = any(c.name.startswith("wordpress_logged_in") for c in session.cookies)
    if not logged_in:
        print("LOGIN_FAILED", "incorrect" in resp.text.lower())
        return False
    print("LOGIN_OK")
    return True


def main() -> int:
    if not USER or not PASSWORD:
        print("Set JWELLERY_WP_USER and JWELLERY_WP_PASSWORD env vars")
        return 1
    s = requests.Session()
    s.headers.update({"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"})

    if not login(s):
        return 1

    setup = s.get(f"{BASE}/wp-admin/themes.php?page=jwellery-store-setup", timeout=30)
    nonce_match = re.search(r'name="_wpnonce" value="([^"]+)"', setup.text)
    if not nonce_match:
        print("NONCE_NOT_FOUND")
        return 1
    nonce = nonce_match.group(1)
    print("NONCE_OK")

    for action, name in (
        ("jwellery_update_pages", "update_pages"),
        ("jwellery_store_config", "store_config"),
    ):
        post = s.post(
            f"{BASE}/wp-admin/themes.php?page=jwellery-store-setup",
            data={
                "_wpnonce": nonce,
                "_wp_http_referer": "/wp-admin/themes.php?page=jwellery-store-setup",
                action: "",
            },
            timeout=60,
        )
        ok = "notice-success" in post.text or "updated" in post.text.lower()
        print(f"ACTION_{name}", "OK" if ok else "UNKNOWN")

    admin = s.get(f"{BASE}/wp-admin/", timeout=30)
    api_nonce = re.search(r'wp-api-fetch-nonce"\s+content="([^"]+)"', admin.text)
    if api_nonce:
        content = "<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->"
        upd = s.post(
            f"{BASE}/wp-json/wp/v2/pages/8",
            headers={
                "X-WP-Nonce": api_nonce.group(1),
                "Content-Type": "application/json",
            },
            json={"content": content},
            timeout=30,
        )
        print("REST_UPDATE", upd.status_code, upd.text[:200] if upd.status_code != 200 else "OK")
    else:
        print("REST_NONCE_MISSING")

    page = s.get(f"{BASE}/wp-json/wp/v2/pages?slug=checkout", timeout=30)
    if page.ok and page.json():
        raw = page.json()[0]["content"]["rendered"]
        print("CHECKOUT_BLOCK", "woocommerce/checkout" in raw)
        print("CHECKOUT_SHORTCODE", "woocommerce_checkout" in raw)

    s.get(f"{BASE}/?add-to-cart=40", timeout=30)
    checkout = s.get(f"{BASE}/checkout/", timeout=30)
    text = checkout.text
    lower = text.lower()
    print("CHECKOUT_URL", checkout.url)
    print("HAS_BLOCK", "wc-block-checkout" in text)
    print("HAS_CLASSIC", "woocommerce-checkout" in text)
    print("HAS_UPI", "pay via upi" in lower or "jus_manual_upi" in text)
    print("HAS_ERROR", "no payment methods" in lower)
    return 0


if __name__ == "__main__":
    sys.exit(main())
