# Auto-deploy to Hostinger (rudrajewellery.co.in)

## Your FTP details (local)

Stored in **`.env.deploy`** (gitignored). Template: `deploy.env.example`.

| Setting | Value |
|---------|--------|
| Server | `82.180.143.137` |
| Username | `u956615329.rudrajewellery.co.in` |
| Port | `21` |
| Theme path | `/public_html/wp-content/themes/jwellery-jewelry/` |
| Plugin path | `/public_html/wp-content/plugins/jewelry-upi-store/` |

Password is only in `.env.deploy` — never commit it.

---

## Option A — Deploy from your PC (manual)

```powershell
cd "d:\jwellery ecommerce"
.\scripts\deploy-hostinger.ps1
```

Requires **WinSCP** (`winscp.com` in PATH) for fastest sync, or falls back to `curl`.

After deploy: **LiteSpeed Cache → Purge All** in wp-admin.

---

## Option B — Auto deploy on git push (recommended)

1. Initialize git and push to GitHub (if not done):

```powershell
cd "d:\jwellery ecommerce"
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/YOUR_USER/jwellery-ecommerce.git
git push -u origin main
```

2. In GitHub: **Settings → Secrets and variables → Actions → New repository secret**

| Secret name | Value |
|-------------|--------|
| `FTP_PASSWORD` | your FTP password (only secret required) |

Server, username, and paths are set in `.github/workflows/deploy.yml`.

3. Push theme/plugin changes to `main` — workflow `.github/workflows/deploy.yml` runs automatically.

---

## Daily workflow (local first, then live)

### 1. One-time: configure local WordPress

Copy `local.env.example` to `local.env` and set paths to your **local** WordPress install (Local WP, XAMPP, Laragon, etc.):

```powershell
copy local.env.example local.env
notepad local.env
```

Example (`local.env`):

```
LOCAL_SITE_URL=http://localhost:10008
LOCAL_WP_THEME_PATH=C:\Users\HP\Local Sites\rudra\app\public\wp-content\themes\jwellery-jewelry
LOCAL_WP_PLUGIN_PATH=C:\Users\HP\Local Sites\rudra\app\public\wp-content\plugins\jewelry-upi-store
```

Install WordPress + WooCommerce locally once, activate the theme and UPI plugin there.

### 2. Ship when ready (preview -> confirm -> deploy)

```powershell
cd "d:\jwellery ecommerce"
.\scripts\ship.ps1 "Describe your change"
```

What happens:

1. **Sync** theme/plugin to local WordPress and open browser
2. **You review** locally — type `y` only if it looks correct
3. **Commit + push** to GitHub
4. **GitHub Actions** deploys to Hostinger automatically (no manual Run workflow)

Skip local preview (already tested):

```powershell
.\scripts\ship.ps1 "Hotfix" -SkipLocal
```

Preview locally anytime without deploying:

```powershell
.\scripts\sync-local.ps1
```

### GitHub secret (once)

`FTP_PASSWORD` = your Hostinger FTP/SFTP password (same password for both).

**hPanel:** Advanced → **Remote Access** → turn **SSH/SFTP ON** (required for GitHub deploy).

**Port:** Hostinger shared hosting uses **SFTP port `65002`**, not FTP port 21. GitHub cannot reach port 21 (timeout).

---

## Security

- Do **not** put FTP password in code or docs.
- If the password was shared in chat or email, **change it** in hPanel → FTP Accounts → Change FTP password, then update `.env.deploy` and GitHub secret `FTP_PASSWORD`.
