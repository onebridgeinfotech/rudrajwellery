# Hostinger: Install jewelry store (WordPress + WooCommerce + UPI)

Complete guide for [Hostinger](https://www.hostinger.com/) hPanel. Reference design: [ramyanagendra.com](https://ramyanagendra.com/).

## Files to upload from this project

| ZIP file | Upload in WordPress |
|----------|---------------------|
| **`jwellery-jewelry.zip`** | Appearance → Themes → Upload |
| **`jewelry-upi-store.zip`** | Plugins → Add New → Upload |

Generate ZIPs on your PC:

```powershell
cd "d:\jwellery ecommerce"
.\scripts\create-theme-zip.ps1
.\scripts\create-plugin-zip.ps1
```

---

## Step 1: Install WordPress on Hostinger

1. Log in to **hPanel** → **Websites** → select your site
2. **Auto Installer** → **WordPress** → Install
3. Set:
   - **Site title:** your jewelry brand name
   - **Admin email:** your real email (order notifications)
   - **Username / password:** save securely
4. After install, open **https://yourdomain.com/wp-admin**

### WordPress settings

| Setting | Path | Value |
|---------|------|-------|
| Permalinks | Settings → Permalinks | **Post name** |
| Timezone | Settings → General | **Kolkata** |
| Language | Settings → General | English |

---

## Step 2: Install WooCommerce

1. **Plugins → Add New** → search **WooCommerce**
2. **Install Now → Activate**
3. Setup wizard:
   - Country: **India**
   - Currency: **Indian rupee (₹)**
   - Physical products
4. Skip paid extensions

---

## Step 3: Upload theme

1. **Appearance → Themes → Add New → Upload Theme**
2. Choose **`jwellery-jewelry.zip`**
3. **Install → Activate**

On activation the theme creates:

- Home, About, Contact, Track Order, policy pages
- Primary menu
- Static front page

---

## Step 4: Upload UPI plugin

1. **Plugins → Add New → Upload Plugin**
2. Choose **`jewelry-upi-store.zip`**
3. **Install → Activate**
4. **WooCommerce → Settings → Payments → Manual UPI (Jewelry Store) → Manage**
   - **UPI ID:** your UPI address
   - **QR Code Image URL:** upload QR in Media Library, paste URL
   - **Save**

Disable all other payment methods.

---

## Step 5: Import products

1. **Products → Categories** — create if missing:
   - `ear-rings`, `necklaces`, `chockers`, `bangles`, `long-harams`
   - `handmade-collection`, `latest-collection`, `instagram-collection`
2. **Products → Import** → upload `sample-data/products-sample.csv`
3. Add product images under **Products → All Products**

Mark bestsellers: edit product → check **Featured** (star).

---

## Step 6: Email (WP Mail SMTP)

1. **Plugins → Add New** → **WP Mail SMTP** → Install → Activate
2. Use Hostinger email: hPanel → **Emails** → create `orders@yourdomain.com`
3. Configure SMTP in WP Mail SMTP (see [EMAIL-SETUP.md](EMAIL-SETUP.md))
4. Send test email

---

## Step 7: Customize store

| Task | Where |
|------|-------|
| Logo | Appearance → Customize → Site Identity |
| Social links | Appearance → Customize → Social Links |
| Homepage | Automatic via theme (import products first) |
| Footer policies | Pages created by theme — edit text |
| Shipping | WooCommerce → Settings → Shipping |

---

## Step 8: Test order

1. Register test customer at **My Account**
2. Add product → Checkout → enter **UTR** → place order
3. Check **Pending payment** + thank-you UPI page
4. Admin email + customer email received
5. Orders → set **Processing**

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Theme: missing style.css | Upload **`jwellery-jewelry.zip`**, not the whole project folder |
| Blank homepage sections | Import products + create categories |
| No emails | Configure WP Mail SMTP |
| Checkout redirect to login | Expected — login required by UPI plugin |

See also [THEME-INSTALL-FIX.md](THEME-INSTALL-FIX.md).

---

## Hostinger checklist

- [ ] WordPress installed
- [ ] WooCommerce active, INR currency
- [ ] Theme **Jwellery Jewelry** active
- [ ] Plugin **Jewelry UPI Store** active, UPI configured
- [ ] Products imported
- [ ] SMTP test email OK
- [ ] Test order end-to-end
