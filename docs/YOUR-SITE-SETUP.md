# Your site: mediumseagreen-goose-608203.hostingersite.com

**Live URL:** https://mediumseagreen-goose-608203.hostingersite.com/  
**Reference:** https://ramyanagendra.com/

## What’s working now

- Announcement bar, search, Shop dropdown menu
- Top Categories carousel (5 jewelry types)
- Footer policies

## What’s missing (why it doesn’t look like the reference yet)

| Missing on your site | Cause |
|---------------------|--------|
| Best Sellers, Handmade, New, Instagram | **No products** in WooCommerce |
| Empty shop page | **No products** |
| Site title = Hostinger URL | WordPress **Settings → General** not changed |
| Product images | Run **Import product images** (theme v2.2.0 bundles photos from reference) |

---

## Promo codes at checkout

Cart already has **Apply promo code** — you need coupons in WooCommerce.

**Appearance → Store Setup → Enable INR + create promo codes**

Test codes: `WELCOME10` (10% off), `FLAT50` (₹50 off), `SALE15` (15% off). See [PROMO-CODES.md](PROMO-CODES.md).

---

## One-click fix (theme v2.3.0 — photos + INR + promo codes)

### 1. Upload latest theme zip

`FOR-HOSTINGER-UPLOAD\1-THEME-UPLOAD-jwellery-jewelry.zip`

### 2. In wp-admin

**Appearance → Store Setup →** click:

**Full setup (menu + categories + 19 products)**

This creates 19 products like the reference store (Changeable studs, Chandraharam, etc.) with ₹ prices **and attaches product photos** copied from ramyanagendra.com.

If products already exist but have no images: **Appearance → Store Setup → Import product images only**.

### 3. Change brand name

**Settings → General**

- **Site title:** `Ramya Nagendra Imitations` (or your brand)
- **Tagline:** `Imitation Jewelry`

Or **Appearance → Customize → Social Links → Store brand name**

### 4. WooCommerce

**Settings → General → Store visibility → Live**

**Settings → Payments → Manual UPI** (if plugin active)

### 5. Social links

**Appearance → Customize → Social Links** → Facebook, Instagram, YouTube

---

## After setup, homepage should show

1. Hero + Shop Now  
2. **Best Sellers** (10 products, carousel)  
3. **Top Categories**  
4. **Handmade Collection**  
5. **New Collection**  
6. **Product of the Day**  
7. **Long Harams** featured banner  
8. **Instagram Collection**  

---

## Quick links

| Page | URL |
|------|-----|
| Admin | https://mediumseagreen-goose-608203.hostingersite.com/wp-admin/ |
| Shop | https://mediumseagreen-goose-608203.hostingersite.com/shop/ |
| Store Setup | Appearance → Store Setup |
