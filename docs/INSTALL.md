# Phase 1–2: Hosting, WordPress, and WooCommerce

Complete these steps on your existing hosting (cPanel, Hostinger, GoDaddy, etc.).

## 1. Hosting prerequisites

| Requirement | How to check |
|-------------|--------------|
| PHP 8.1+ | cPanel → **Select PHP Version** or **MultiPHP Manager** |
| MySQL 8 / MariaDB 10.6+ | cPanel → **MySQL Databases** |
| HTTPS | cPanel → **SSL/TLS** → Let's Encrypt (free) |
| Memory 256MB+ | `php.ini` or MultiPHP → `memory_limit = 256M` |

### Create database

1. cPanel → **MySQL Databases**
2. Create database: e.g. `jewelry_wp`
3. Create user with strong password; add user to database with **ALL PRIVILEGES**
4. Note: **DB name**, **user**, **password**, **host** (usually `localhost`)

### Point domain

- Domain or subdomain **Document Root** → `public_html` (or subfolder if using subdomain)

---

## 2. Install WordPress

### Option A: One-click (recommended)

1. cPanel → **WordPress** / **Softaculous** / **Installatron**
2. Choose domain, directory (leave blank for root)
3. Site name: your jewelry brand
4. Admin username: **not** `admin` — use something unique
5. Strong admin password — save in password manager
6. Email: owner email (order notifications go here)

### Option B: Manual

1. Download from [wordpress.org/download](https://wordpress.org/download/)
2. Upload ZIP to `public_html`, extract
3. Visit `https://yourdomain.com` → run 5-minute install
4. Enter database credentials from step 1

### After install

| Setting | Location | Value |
|---------|----------|-------|
| Timezone | Settings → General | `Kolkata` |
| Permalinks | Settings → Permalinks | **Post name** |
| Site language | Settings → General | English (or your choice) |

---

## 3. Install WooCommerce

1. **Plugins → Add New** → search **WooCommerce** → Install → Activate
2. Run setup wizard:
   - Store address: India
   - Industry: **Fashion and apparel** or **Other**
   - Product types: **Physical products**
   - Business details: skip or fill as needed
3. **WooCommerce → Settings → General**
   - Selling location: **India**
   - Default customer location: **India**
   - Currency: **Indian rupee (₹)**

---

## 4. Install theme (correct method)

**Do not upload the whole project folder** — WordPress will show *"missing the style.css stylesheet"*. Use **`FOR-HOSTINGER-UPLOAD\1-THEME-UPLOAD-jwellery-jewelry-FLAT.zip`** (see [THEME-INSTALL-FIX.md](THEME-INSTALL-FIX.md)).

1. **Appearance → Themes → Add New** → search **Kadence** → Install → Activate  
2. Optional: upload **`jwellery-jewelry-child.zip`** (from `scripts\create-theme-zip.ps1`) — only works after Kadence is active  

Details: [THEME-INSTALL-FIX.md](THEME-INSTALL-FIX.md)

## 5. Install required plugins

| Plugin | Search term | Notes |
|--------|-------------|-------|
| **Jewelry UPI Store** | Upload ZIP from this repo | `jewelry-upi-store.zip` → **Plugins → Upload** (not Themes) |
| **WP Mail SMTP** | WP Mail SMTP | Required for order emails |
| **Really Simple SSL** | Really Simple SSL | If HTTPS not forced |
| **Kadence** (theme) | Kadence | Appearance → Themes → Install |
| **Kadence Starter Templates** | Kadence Starter Templates | Optional demo import |

Optional:

- **YITH WooCommerce Wishlist** — wishlist like reference site
- **Contact Form 7** — contact page form
- **Wordfence Security** — firewall

---

## 6. Upload Jewelry UPI Store plugin

1. On your PC: zip the folder `wordpress-plugin/jewelry-upi-store` (the folder must contain `jewelry-upi-store.php` at root of zip)
2. WordPress → **Plugins → Add New → Upload Plugin**
3. Choose zip → **Install Now** → **Activate**

---

## 7. SSL and security

1. Ensure site URL uses `https://` (Settings → General)
2. Activate **Really Simple SSL** if mixed content warnings appear
3. Change default admin URL only if comfortable with security plugins (optional)

---

## 8. Verify installation

- [ ] `https://yourdomain.com/wp-admin` loads
- [ ] WooCommerce → **Home** shows setup checklist (can dismiss after config)
- [ ] **Jewelry UPI Store** appears under Plugins as active
- [ ] Permalinks: visit a page — URL should be `/page-name/` not `?p=123`

**Next:** [THEME-SETUP.md](THEME-SETUP.md) and [UPI-SETTINGS.md](UPI-SETTINGS.md)
