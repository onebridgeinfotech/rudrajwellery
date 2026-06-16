# Cannot activate Jewelry UPI Store plugin

## Fix 1 — Delete emergency recovery file (most common)

If the site crashed earlier, you may have uploaded an emergency file. **It blocks activation** until removed.

**Hostinger → File Manager →** `public_html/wp-content/mu-plugins/`

Delete **any** of these if present:

- `0-EMERGENCY-FIX-SITE-NOW.php`
- `0-EMERGENCY-DISABLE-BROKEN-UPI.php`

Then: **Plugins → Jewelry UPI Store → Activate**

---

## Fix 2 — WooCommerce must be active first

1. **Plugins → WooCommerce** → must show **Active**
2. If not installed: **Plugins → Add New** → search WooCommerce → Install → Activate
3. Then activate **Jewelry UPI Store**

---

## Fix 3 — Remove duplicate plugin folders

**File Manager →** `public_html/wp-content/plugins/`

Keep **only one** folder named `jewelry-upi-store`.

Delete or rename duplicates:

- `jewelry-upi-store-1`
- `jewelry-upi-store-2`
- `jewelry-upi-store-off`

Inside `jewelry-upi-store/` there must be `jewelry-upi-store.php` (not a nested subfolder).

---

## Fix 4 — Re-upload plugin v1.0.6

On your PC:

```powershell
cd "D:\jwellery ecommerce"
.\scripts\prepare-hostinger-upload.ps1
```

1. File Manager: delete folder `wp-content/plugins/jewelry-upi-store`
2. WordPress: **Plugins → Add New → Upload Plugin**
3. Choose `FOR-HOSTINGER-UPLOAD\2-PLUGIN-UPLOAD-jewelry-upi-store.zip`
4. **Install Now → Activate**
5. Confirm version **1.0.6** on Plugins page

---

## Fix 5 — Critical error on activate

1. File Manager: rename `jewelry-upi-store` → `jewelry-upi-store-off`
2. Site should load in wp-admin
3. Upload fresh v1.0.6 zip (Fix 4)
4. Delete emergency files from mu-plugins (Fix 1)
5. Activate again

---

## After successful activation

1. **WooCommerce → Settings → Payments → Manual UPI (Jewelry Store) → Manage**
2. Add **UPI ID** and **QR image URL** → Save
3. **LiteSpeed / Hostinger → Purge cache**
4. Test checkout: **Pay via UPI** + **UTR** field should appear

---

## Still stuck?

Note the **exact message** on screen (e.g. "Plugin could not be activated", white screen, or Activate link returns with no change) and check `wp-content/debug.log` if debug is enabled.
