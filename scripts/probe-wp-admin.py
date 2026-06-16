import os
import re
import requests

BASE = "https://brown-llama-127224.hostingersite.com"
USER = os.environ.get("JWELLERY_WP_USER", "udayach123@gmail.com")
PASSWORD = os.environ.get("JWELLERY_WP_PASSWORD", "Samyuka123$")

s = requests.Session()
s.headers["User-Agent"] = "Mozilla/5.0"
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
logged_in = any(c.name.startswith("wordpress_logged_in") for c in s.cookies)
print("logged_in", logged_in)

for path in ["/wp-admin/", "/wp-admin/plugins.php", "/wp-admin/themes.php"]:
    r = s.get(BASE + path, timeout=30)
    print(path, r.status_code, "critical" in r.text.lower())
    if r.status_code == 200 and "critical" not in r.text.lower():
        print("  title", re.search(r"<title>([^<]+)", r.text, re.I))
        break
