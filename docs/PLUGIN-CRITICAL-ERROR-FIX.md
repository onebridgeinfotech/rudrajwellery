# UPI plugin critical error — fix

## Cause

The plugin loaded the payment gateway **before** WooCommerce’s `WC_Payment_Gateway` class existed → PHP fatal error on activate.

**Fixed in plugin v1.0.6** (classic checkout only — no block checkout code)

Older fixes: v1.0.5, v1.0.1+

## Cannot activate plugin (Activate does nothing)

The emergency recovery file **blocks activation** on purpose. Delete it first:

**File Manager →** `public_html/wp-content/mu-plugins/`

Remove `0-EMERGENCY-FIX-SITE-NOW.php` and/or `0-EMERGENCY-DISABLE-BROKEN-UPI.php`

Also ensure **WooCommerce is active** before activating Jewelry UPI Store.

Full steps: [ACTIVATE-UPI-PLUGIN.md](ACTIVATE-UPI-PLUGIN.md)

---

## Site shows white screen / critical error right now

**Fastest fix (Hostinger File Manager):**

1. `public_html/wp-content/plugins/` → rename `jewelry-upi-store` to `jewelry-upi-store-off`
2. Site loads again

**Or** upload `0-EMERGENCY-DISABLE-BROKEN-UPI.php` into `public_html/wp-content/mu-plugins/` (create folder if missing), then reload the site.

After the site is back, upload the new **`2-PLUGIN-UPLOAD-jewelry-upi-store.zip`** (v1.0.5) and activate.

## Checkout shows “no payment methods available”

WooCommerce 8.3+ uses the **Checkout Block** by default. Custom UPI payment needs the **Jewelry UPI Store** plugin (v1.0.2+).

1. Upload **`2-PLUGIN-UPLOAD-jewelry-upi-store.zip`**
2. **Plugins → Activate** Jewelry UPI Store
3. **WooCommerce → Settings → Payments → Manual UPI (Jewelry Store) → Enable**
4. Add your **UPI ID** and **QR image URL** → Save

If the error remains after v1.0.3:

1. Upload **theme v3.3.5** (auto-switches checkout to classic `[woocommerce_checkout]`)
2. Upload **plugin v1.0.3** and deactivate → activate once
3. Or manually edit **Pages → Checkout** — replace the Checkout block with shortcode: `[woocommerce_checkout]`
4. Clear Hostinger / LiteSpeed cache and hard-refresh checkout on phone

---

## Step 1: Get site back online

**Hostinger → File Manager →** `public_html/wp-content/plugins/`

Rename:

`jewelry-upi-store` → `jewelry-upi-store-off`

Site should load again.

---

## Step 2: Upload fixed plugin

1. On PC: `D:\jwellery ecommerce\FOR-HOSTINGER-UPLOAD\2-PLUGIN-UPLOAD-jewelry-upi-store.zip`  
   (regenerate with `.\scripts\prepare-hostinger-upload.ps1` if needed)

2. **Plugins → Add New → Upload Plugin**

3. **Activate** Jewelry UPI Store

---

## Step 3: Enable UPI payment

**WooCommerce → Settings → Payments**

- Enable **Manual UPI (Jewelry Store)**
- Add **UPI ID** and **QR image URL**
- Save

---

## If error returns

Deactivate plugin via File Manager (rename folder), keep theme active. Contact support with `wp-content/debug.log` after adding to `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```
