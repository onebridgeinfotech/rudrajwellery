# Fix: "Could not remove the old theme" (Hostinger)

You uploaded the **correct** file (`jwellery-jewelry.zip`). WordPress found an old broken copy and cannot delete it (file permissions on Hostinger).

## Fix — delete old theme manually (5 minutes)

### Step 1: Switch to a default theme

1. WordPress admin → **Appearance → Themes**
2. Activate **Twenty Twenty-Four** (or any default WordPress theme)
3. Do **not** delete Jwellery Jewelry from this screen yet

### Step 2: Delete theme folder in Hostinger File Manager

1. Log in to **Hostinger hPanel**
2. **Files → File Manager**
3. Open: `public_html/wp-content/themes/`
4. Find folder **`jwellery-jewelry`**
5. Right-click → **Delete** (or select and Delete)
6. If delete fails: select folder → **Permissions** → set to **755**, try Delete again

Also delete if present:

- `jwellery-jewelry-old`
- Duplicate folders from failed uploads

### Step 3: Upload theme again

1. **Appearance → Themes → Add New → Upload Theme**
2. Upload **`1-THEME-UPLOAD-jwellery-jewelry.zip`** (from `FOR-HOSTINGER-UPLOAD` folder)
3. **Install → Activate**

---

## Alternative: FTP / SSH

Path on server:

```
/home/u123456789/domains/yourdomain.com/public_html/wp-content/themes/jwellery-jewelry/
```

Delete the entire `jwellery-jewelry` folder, then upload the new zip.

---

## If File Manager still cannot delete

1. hPanel → **Advanced → Fix File Ownership** (if available)
2. Or open a **Hostinger live chat** and ask: *"Please delete wp-content/themes/jwellery-jewelry — WordPress theme update failed"*
3. After they delete it, upload the theme zip again

---

## Prevent this next time

- Do not upload theme zip multiple times in a row if the first install failed
- Always delete the broken folder in File Manager before re-uploading
- Use the latest zip from `FOR-HOSTINGER-UPLOAD` after running:

```powershell
cd "D:\jwellery ecommerce"
.\scripts\prepare-hostinger-upload.ps1
```

---

## After theme is active

1. Upload **`2-PLUGIN-UPLOAD-jewelry-upi-store.zip`** under **Plugins** (if not done)
2. Install **WooCommerce** if missing
3. Import products — see [HOSTINGER-INSTALL.md](HOSTINGER-INSTALL.md)
