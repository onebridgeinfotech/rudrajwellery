# Auto-deploy to Hostinger (rudrajewellery.in)

## Your FTP details (local)

Stored in **`.env.deploy`** (gitignored). Template: `deploy.env.example`.

| Setting | Value |
|---------|--------|
| Server | `82.180.143.137` |
| Username | `u956615329.rudrajewellery.in` |
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

## Daily workflow (fully automatic)

Every push to `main` triggers **Deploy to Hostinger** — no manual Run workflow needed.

```powershell
cd "d:\jwellery ecommerce"
.\scripts\ship.ps1 "Describe your change"
```

Or manually:

```powershell
git add .
git commit -m "Describe your change"
git push origin main
```

Then open **Actions** tab only to **watch** progress (optional):  
https://github.com/onebridgeinfotech/rudrajwellery/actions

**Required once:** GitHub secret `FTP_PASSWORD` = your Hostinger FTP password.  
Server/username/paths are in `.github/workflows/deploy.yml`.

---

## Security

- Do **not** put FTP password in code or docs.
- If the password was shared in chat or email, **change it** in hPanel → FTP Accounts → Change FTP password, then update `.env.deploy` and GitHub secret `FTP_PASSWORD`.
