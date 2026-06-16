# Phase 2–3: Theme, homepage, and catalog structure

Reference: [ramyanagendra.com](https://ramyanagendra.com/)

> **Do not upload `jwellery ecommerce.zip` as a theme.** That folder has no `style.css`. Use Kadence from WordPress.org, then optionally `jwellery-jewelry-child.zip`. See [THEME-INSTALL-FIX.md](THEME-INSTALL-FIX.md).

## 1. Install theme

1. **Appearance → Themes → Add New**
2. Search **Kadence** → Install → Activate (from WordPress directory — not your project ZIP)
3. **Plugins → Add New** → **Kadence Starter Templates** → Install → Activate
4. **Appearance → Kadence → Starter Templates** → choose **Fashion**, **Shop**, or **Beauty** demo
5. Import demo (pages + homepage layout)

Alternative: **Astra** + **Astra Starter Templates** → e-commerce template.

### Optional child theme (from this repo)

After Kadence is active:

1. Run `scripts\create-theme-zip.ps1` on your PC → creates `jwellery-jewelry-child.zip`
2. **Appearance → Themes → Upload** → activate **Jwellery Jewelry Child**

### Brand colors (Customizer)

**Appearance → Customize → Colors**

| Element | Suggested |
|---------|-----------|
| Primary | `#B8860B` (gold) or `#C9A227` |
| Accent | `#1a1a1a` (black) |
| Background | `#FFFDF8` (cream) |
| Buttons | Gold background, white text |

**Typography:** Playfair Display (headings) + Lato or Open Sans (body) — Google Fonts in Kadence.

---

## 2. Site identity

**Appearance → Customize → Site Identity**

- Logo: upload brand logo (PNG transparent)
- Site title: your store name
- Tagline: e.g. *Imitation Jewelry | Handmade Collection*

---

## 3. Menus (match reference)

**Appearance → Menus** → Create menu **Primary** → assign to **Primary Navigation**

| Menu item | URL / type |
|-----------|------------|
| Home | Front page |
| Shop | Parent → `#` or Shop page |
| ↳ Handmade Collection | Product category `handmade-collection` |
| ↳ Instagram Collection | Product category `instagram-collection` |
| ↳ Best Sellers | Shop with `?featured=1` or category |
| ↳ Latest Collection | Product category `latest-collection` |
| ↳ All Collections | `/product-category/` or custom page |
| ↳ All Products | Shop page `/shop/` |
| About | Page About |
| Contact | Page Contact |
| Track Order | Page with shortcode (see below) |

### Track Order page

1. **Pages → Add New** → Title: **Track Order**
2. Content block → Shortcode:

```
[woocommerce_order_tracking]
```

3. Publish → add to menu

---

## 4. Homepage sections

Edit front page with Kadence blocks or Elementor (if demo uses it).

### Section 1: Hero

- Full-width image (jewelry banner)
- Heading: *Timeless Elegance, Everyday Price*
- Button: **Shop Now** → link to `/shop/`

### Section 2: Best Sellers

- Block: **WooCommerce Featured Products** or shortcode:

```
[products limit="10" columns="5" orderby="popularity" visibility="featured"]
```

Mark products as **Featured** in product editor (star icon).

### Section 3: Top Categories

- Block: **Product Categories** — select:

  - Ear Rings (`ear-rings`)
  - Necklaces (`necklaces`)
  - Chockers (`chockers`)
  - Bangles (`bangles`)
  - Long Harams (`long-harams`)

Upload category thumbnail images (600×600) under **Products → Categories**.

### Section 4: Handmade Collection

```
[products category="handmade-collection" limit="10" columns="5"]
```

### Section 5: New Collection

```
[products category="latest-collection" limit="8" columns="4"]
```

### Section 6: Instagram Collection

```
[products category="instagram-collection" limit="4" columns="4"]
```

Add Instagram link in footer or section CTA.

### Section 7: Product of the Day (optional)

Single featured product block — change product weekly in admin.

---

## 5. Footer

**Appearance → Widgets** or **Customizer → Footer**

Links (create pages from [sample-data/pages](../sample-data/pages/)):

- Home
- Contact Information
- Privacy Policy
- Refund Policy
- Shipping Policy
- Terms of Service
- Track order

Social: Facebook, Instagram, YouTube URLs.

Copyright: `© 2026, Your Store Name`

---

## 6. WooCommerce pages

**WooCommerce → Settings → Advanced → Page setup**

Confirm pages exist: Shop, Cart, Checkout, My account, Terms.

---

## 7. Shop display

**Appearance → Customize → WooCommerce → Product Catalog**

- Products per row: 4 (desktop), 2 (mobile)
- Enable ratings if desired
- **Catalog images** 1:1 ratio in product settings

**Next:** Import [products-sample.csv](../sample-data/products-sample.csv) — see catalog section in [LAUNCH-CHECKLIST.md](LAUNCH-CHECKLIST.md)
