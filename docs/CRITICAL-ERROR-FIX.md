# Fix: "There has been a critical error on this website"

Usually caused by the theme calling WooCommerce functions **before WooCommerce is installed**.

## Quick fix (get site back online)

### Option A: Rename theme folder (Hostinger File Manager)

1. hPanel → **File Manager**
2. Go to `public_html/wp-content/themes/`
3. Rename `jwellery-jewelry` → `jwellery-jewelry-off`
4. Site will load with default WordPress theme
5. Log in to **wp-admin**

### Option B: WordPress recovery email

If you received a recovery link email from WordPress, open it and switch to **Twenty Twenty-Four**.

---

## Install the fixed theme (v2.0.2)

1. On your PC run:

```powershell
cd "D:\jwellery ecommerce"
.\scripts\prepare-hostinger-upload.ps1
```

2. Delete old theme folder: `wp-content/themes/jwellery-jewelry` (or `jwellery-jewelry-off`)
3. Upload **`1-THEME-UPLOAD-jwellery-jewelry.zip`** (version 1.0.2)
4. **Activate** theme

---

## Required plugins (install in this order)

| Order | Plugin | How |
|-------|--------|-----|
| 1 | **WooCommerce** | Plugins → Add New → search → Install |
| 2 | **Jewelry UPI Store** | Upload `2-PLUGIN-UPLOAD-jewelry-upi-store.zip` |
| 3 | **WP Mail SMTP** | Search install (for emails) |

**WooCommerce must be active before** the jewelry theme works fully.

---

## If error continues

1. **Deactivate Jewelry UPI Store** temporarily (Plugins page)
2. Enable **WP_DEBUG** in `wp-config.php` (see below) and check `wp-content/debug.log`

Add above `/* That's all, stop editing! */` in `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

3. Hostinger → **PHP Configuration** → PHP **8.1** or **8.2**
4. Increase memory: `define( 'WP_MEMORY_LIMIT', '256M' );`

---

## What we fixed in v2.0.2

- Theme no longer calls WooCommerce functions before WC is loaded
- Fixed menu setup crash (invalid parent menu ID)
- Removed heavy auto-setup on every admin page load
- Safer store-live / coming-soon handling
- Plugin registers UPI gateway only when WooCommerce is active
