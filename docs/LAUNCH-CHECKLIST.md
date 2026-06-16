# Phase 8: Catalog, accounts, policies, and go-live

## Catalog import

1. **Products → Categories** — create (slugs must match CSV):

   - `ear-rings`, `necklaces`, `chockers`, `bangles`, `long-harams`
   - `handmade-collection`, `instagram-collection`, `latest-collection`

2. **Products → Import**
3. Choose `sample-data/products-sample.csv` from this repo
4. Map columns → Run importer
5. Upload product images: **Products → All Products** → edit each → set featured image

Adjust prices and stock before launch.

---

## Accounts and checkout (Phase 4–5)

**WooCommerce → Settings → Accounts & Privacy**

| Setting | Recommended |
|---------|-------------|
| Allow customers to place orders without an account | **Unchecked** (require login) |
| Allow customers to create an account during checkout | **Checked** |
| When creating an account, send password link | **Checked** |

**Settings → Advanced → Checkout**

- Enable guest checkout: **Off** if login required

Plugin sets UPI-only payment — verify under **Settings → Payments**.

---

## Policy pages

Copy content from `sample-data/pages/` into WordPress pages:

| Page | Slug | Source file |
|------|------|-------------|
| Privacy Policy | `privacy-policy` | `privacy-policy.html` |
| Refund Policy | `refund-policy` | `refund-policy.html` |
| Shipping Policy | `shipping-policy` | `shipping-policy.html` |
| Terms of Service | `terms-of-service` | `terms-of-service.html` |
| About | `about` | `about.html` |
| Contact | `contact` | `contact.html` |

Replace `YOUR_STORE_NAME`, `your@email.com`, `+91-XXXXXXXXXX`, `yourdomain.com` before publish.

**Settings → Privacy** → assign Privacy Policy page.

---

## Full end-to-end test

| # | Test | Pass |
|---|------|------|
| 1 | Register new customer at My Account | ☐ |
| 2 | Browse category, add to cart | ☐ |
| 3 | Checkout — only UPI visible | ☐ |
| 4 | Enter UTR, place order | ☐ |
| 5 | Thank-you page shows QR, UPI ID, order # | ☐ |
| 6 | Admin email with UTR | ☐ |
| 7 | Customer pending email | ☐ |
| 8 | Admin: Pending → Processing | ☐ |
| 9 | Customer processing email | ☐ |
| 10 | Track Order page works | ☐ |
| 11 | Mobile: cart + checkout + UPI readable | ☐ |

---

## Go-live

- [ ] Delete test orders and test products
- [ ] Real UPI ID and QR in plugin settings
- [ ] SMTP test email successful
- [ ] Shipping rates configured (WooCommerce → Settings → Shipping)
- [ ] GST note on products if applicable
- [ ] Backup plugin or host backups enabled

---

## Post-launch

- Check **Pending payment** orders 2–3 times daily
- Update Best Sellers featured products weekly
- Add New Collection products to `latest-collection` category
