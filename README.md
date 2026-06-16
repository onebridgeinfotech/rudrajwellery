# Jewelry E-commerce for Hostinger (WordPress + WooCommerce + UPI)

Full package to build a store like [ramyanagendra.com](https://ramyanagendra.com/): homepage collections, categories, login, cart, **manual UPI** payment, orders + emails.

## Install on Hostinger (2 ZIP files)

| File | WordPress location |
|------|-------------------|
| **`jwellery-jewelry.zip`** | **Appearance → Themes → Upload** |
| **`jewelry-upi-store.zip`** | **Plugins → Upload** |

**Step-by-step:** [docs/HOSTINGER-INSTALL.md](docs/HOSTINGER-INSTALL.md)

### Create ZIPs

```powershell
cd "d:\jwellery ecommerce"
.\scripts\create-theme-zip.ps1
.\scripts\create-plugin-zip.ps1
```

Do **not** upload the whole project folder as a theme.

## What’s included

### Standalone theme: `jwellery-jewelry`

- Valid `style.css` — installs on Hostinger without Kadence
- Homepage: Hero, Best Sellers, Top Categories, Handmade, New, Instagram sections
- WooCommerce shop layout, header cart + login
- Auto-creates pages (About, Contact, Track Order, policies) and menu on activation
- Social links in Customizer

Source: [wordpress-theme/jwellery-jewelry/](wordpress-theme/jwellery-jewelry/)

### Plugin: `jewelry-upi-store`

- Manual UPI gateway (QR + UPI ID on thank-you page)
- Required UTR field at checkout
- Orders → Pending payment until admin confirms
- Login required for checkout
- Emails to customer and owner with UTR

Source: [wordpress-plugin/jewelry-upi-store/](wordpress-plugin/jewelry-upi-store/)

### Sample data

- [sample-data/products-sample.csv](sample-data/products-sample.csv) — 19 products
- [sample-data/pages/](sample-data/pages/) — policy page HTML

## Documentation

| Doc | Topic |
|-----|-------|
| [HOSTINGER-INSTALL.md](docs/HOSTINGER-INSTALL.md) | **Start here** for Hostinger |
| [INSTALL.md](docs/INSTALL.md) | Generic hosting |
| [UPI-SETTINGS.md](docs/UPI-SETTINGS.md) | UPI + order workflow |
| [THEME-SETUP.md](docs/THEME-SETUP.md) | Extra customization |
| [EMAIL-SETUP.md](docs/EMAIL-SETUP.md) | SMTP |
| [LAUNCH-CHECKLIST.md](docs/LAUNCH-CHECKLIST.md) | Go-live tests |

## Order flow

1. Customer logs in → adds to cart → checkout with **UTR**
2. Order **Pending payment** + UPI instructions on thank-you page
3. Owner gets email → verifies UPI in bank app
4. Admin sets order to **Processing** → customer notified
