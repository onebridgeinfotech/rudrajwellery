# Security guide — Jwellery Jewelry store

Best practices for protecting your WordPress + WooCommerce site against hacking, SQL injection, and abuse.

## Built into theme v4.3.0+

| Protection | What it does |
|------------|----------------|
| **Prepared SQL only** | Custom queries use `$wpdb->prepare()` — prevents SQL injection |
| **AJAX nonces** | Wishlist & quick-view require valid nonce tokens |
| **Security headers** | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` |
| **Login CAPTCHA** | Math challenge by default; optional Turnstile / reCAPTCHA (Customizer → Login CAPTCHA) |
| **Login rate limit** | 5 failed attempts per IP+username → 15 min lockout |
| **Author enumeration block** | Blocks `?author=1` username discovery |
| **REST user API** | Hides `/wp/v2/users` from public |
| **XML-RPC disabled** | Stops common brute-force vector |
| **Upload hardening** | SVG uploads blocked (prevents script injection) |
| **UPI payment claims** | Order key + nonce + rate limit required |
| **Admin-only fixes** | Cart/checkout page fixes run in wp-admin only |

## Add to `wp-config.php` (production)

Place **above** `/* That's all, stop editing! */`:

```php
/* Security — production */
define( 'DISALLOW_FILE_EDIT', true );   // Disable theme/plugin editor in admin
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'FORCE_SSL_ADMIN', true );      // When HTTPS is active
```

Optional (stricter):

```php
define( 'DISABLE_WP_CRON', false );       // Use real server cron if possible
```

## Hosting (Hostinger)

1. **SSL/HTTPS** — Enable free SSL; force HTTPS in WordPress Settings → General
2. **LiteSpeed Cache** — Keep cache; purge after theme updates
3. **File Manager** — Delete any root PHP scripts after use (`3-VISIT-ONCE-FIX-CHECKOUT.php`, etc.)
4. **Backups** — Enable Hostinger automatic backups

## Recommended plugins

| Plugin | Purpose |
|--------|---------|
| **Wordfence** or **Solid Security** | Firewall, malware scan, login protection |
| **Really Simple SSL** | HTTPS redirects |
| **UpdraftPlus** | Backups |

## Admin hygiene

- Use **strong unique passwords** for admin + hosting panel
- Enable **2FA** (Wordfence or Solid Security)
- Never use `admin` as username
- Limit admin accounts to people who need them
- Update WordPress, WooCommerce, theme, and plugins monthly
- Remove unused plugins and themes

## SQL injection — why WordPress is different

WordPress uses **prepared statements** via `$wpdb->prepare()`. Never write raw SQL with user input. This theme's only custom query already uses `prepare()`.

**Do not install** nulled plugins/themes — common malware source.

## After uploading theme/plugin

1. Upload `1-THEME-UPLOAD-jwellery-jewelry.zip` (v4.3.0+)
2. Upload `2-PLUGIN-UPLOAD-jewelry-upi-store.zip` (v1.1.1+)
3. Purge LiteSpeed cache
4. Delete one-time fix scripts from `public_html`
5. Confirm `wp-config.php` has `DISALLOW_FILE_EDIT`

## Incident response

If hacked: change all passwords, restore from backup, scan with Wordfence, check `wp-users` for unknown admins, review `wp-content/uploads` for `.php` files.
