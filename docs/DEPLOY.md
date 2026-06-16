# Deploy to your hosting

**Hostinger users:** use [HOSTINGER-INSTALL.md](HOSTINGER-INSTALL.md) first.

## 1. Upload the theme (standalone)

Upload **`jwellery-jewelry.zip`** → **Appearance → Themes → Upload → Activate**

## 2. Upload the UPI plugin

### Windows (PowerShell)

```powershell
cd "d:\jwellery ecommerce"
Compress-Archive -Path "wordpress-plugin\jewelry-upi-store\*" -DestinationPath "jewelry-upi-store.zip" -Force
```

### Manual

1. Open `wordpress-plugin\jewelry-upi-store`
2. Select all files inside (including `jewelry-upi-store.php`)
3. Zip → name `jewelry-upi-store.zip`

### WordPress

1. **Plugins → Add New → Upload Plugin**
2. Choose `jewelry-upi-store.zip`
3. **Install Now → Activate**

## 3. Configure UPI

**WooCommerce → Settings → Payments → Manual UPI (Jewelry Store) → Manage**

Save UPI ID and QR image URL before taking live orders.

## 4. Import products

**Products → Import** → `sample-data/products-sample.csv`

Create categories first if importer asks (slugs in CSV).

## 5. Copy policy pages

Paste HTML from `sample-data/pages/*.html` into WordPress pages (Code editor mode).

## 6. Run launch checklist

See [LAUNCH-CHECKLIST.md](LAUNCH-CHECKLIST.md).
