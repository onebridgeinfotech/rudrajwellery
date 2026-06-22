# Rudra Jewellery — WordPress Store Operations Guide

Complete work instructions for day-to-day store management on **https://www.rudrajewellery.co.in**

**Admin login:** `https://www.rudrajewellery.co.in/wp-admin/`

---

## Table of contents

1. [Important: what saves where](#1-important-what-saves-where)
2. [Image size requirements](#2-image-size-requirements)
3. [Add a new product](#3-add-a-new-product)
4. [Update homepage banner slides](#4-update-homepage-banner-slides)
5. [Product of the Day](#5-product-of-the-day)
6. [Verify UPI orders (admin)](#6-verify-upi-orders-admin)
7. [Update order status & shipping](#7-update-order-status--shipping)
8. [Track orders (customer & admin)](#8-track-orders-customer--admin)
9. [Other common tasks](#9-other-common-tasks)
10. [Troubleshooting](#10-troubleshooting)
11. [Daily checklist](#11-daily-checklist)

---

## 1. Important: what saves where

| You change this in WordPress… | Stored in | Updated by FTP deploy? |
|------------------------------|-----------|-------------------------|
| Products, prices, stock | Database | **No** |
| Banner slides (Customizer) | Database | **No** |
| Media Library uploads | Server uploads folder | **No** |
| Theme code (PHP/CSS) | Theme files on server | **Yes** (deploy script) |

**Rule:** The deploy script (`scripts/deploy-hostinger.ps1`) only uploads **theme and plugin files**. It does **not** copy your products, banners, or media. Those live in WordPress on Hostinger.

After you change banners or products, click **Publish / Update** in wp-admin. If the live site looks old, purge cache (see [§10](#10-troubleshooting)).

---

## 2. Image size requirements

### Homepage hero banner slides (slider)

| Setting | Value |
|--------|--------|
| **Recommended size** | **1920 × 720 px** (widescreen) |
| **Also good** | 1920 × 800 px |
| **Minimum** | 1600 × 600 px |
| **Aspect ratio** | ~8:3 (wide landscape) |
| **Format** | JPG (80–85% quality) or WebP |
| **File size** | Under **400 KB** per slide when possible |
| **Count** | Up to **5 slides** |
| **Safe zone** | Keep jewellery in the **center 70%** — text overlays the middle |

**Portrait product photos** (e.g. 1080 × 1350) also work — desktop shows the full piece with a soft blurred fill. For the cleanest look, crop to **1920 × 720** in Canva before upload.

**Where to upload:** Appearance → Customize → **Homepage Hero** → Hero slide 1–5

---

### Product images (shop & product page)

| Image type | Recommended size | Notes |
|------------|------------------|--------|
| **Main product image** | **800 × 800 px** minimum; **1000 × 1000 px** ideal | Square works best |
| **Gallery images** | Same as main | Up to 4 extra angles |
| **Format** | JPG or WebP | Clear background, good lighting |
| **File size** | Under **300 KB** per image |

**Critical:** Products **without a main image do not appear** on the shop or homepage grids. Always set **Product image** before publishing.

| Page | Display size (theme) |
|------|----------------------|
| Shop grid thumbnail | ~400 px wide |
| Product page main image | ~600 px wide |
| Product of the Day spotlight | Thumbnail size |

---

### Logo & owner photo (optional)

| Asset | Recommended size |
|-------|------------------|
| **Header logo** | PNG with transparent background, max height ~**120 px**, width up to **280 px** |
| **Owner / founder photo** | **400 × 400 px** square (Appearance → Customize → Owner section) |

---

### Category images

Category cards use theme defaults. Custom category thumbnails (if added later): **600 × 600 px** square.

---

## 3. Add a new product

### Step-by-step

1. Log in to **wp-admin**
2. Go to **Products → Add New**
3. Fill in the fields below
4. Click **Publish**

### Required fields

| Field | Where | What to enter |
|-------|--------|----------------|
| **Product name** | Top title box | e.g. `Panchaloham 7 stone studs` |
| **Regular price** | Product data → **General** | Price in ₹ e.g. `299` |
| **Product image** | Right sidebar → **Product image** → Set image | Upload square photo (see sizes above) |
| **Categories** | Right sidebar → **Product categories** | Tick one or more (Studs, Necklaces, etc.) |
| **Stock** | Product data → **Inventory** | Enable stock management; set quantity |

### Recommended fields

| Field | Where | Notes |
|-------|--------|------|
| **SKU** | Product data → **Inventory** | Unique code e.g. `WP-050` or your own |
| **Short description** | Below title | 1–2 lines for product page (avoid placeholder text) |
| **Description** | Main editor | Full details (optional) |
| **Gallery** | Product data → **Product gallery** | Extra photos |
| **Material / Occasion / Care** | Product data → **Attributes** (if available) | Otherwise theme shows sensible defaults |

### Product categories on this store

- Ear Rings, Studs, Necklaces, Chockers, Bangles, Long Harams  
- Handmade Collection, Instagram Collection, Latest Collection  
- Combo, Rings (and others as created in **Products → Categories**)

### Make product visible on shop

Checklist before publishing:

- [ ] Status = **Published**
- [ ] **Product image** is set (not placeholder)
- [ ] **In stock** (or stock quantity > 0)
- [ ] At least **one category** selected
- [ ] **Price** is set

### Preview

- Click **Preview** (top right) or open the product from **Products → All Products**
- Confirm image, price, Add to cart, and mobile view

### Edit an existing product

**Products → All Products** → click product name → change fields → **Update**

---

## 4. Update homepage banner slides

1. **Appearance → Customize**
2. Open **Homepage Hero**
3. For each slide (**Hero slide 1** … **Hero slide 5**):
   - Click **Select image** or **Change image**
   - Upload from computer or pick from **Media Library**
   - Use **1920 × 720 px** widescreen images (see [§2](#2-image-size-requirements))
4. Optional: set **Hero “from” price (₹)** — leave **0** to auto-use your lowest in-stock product price
5. Click **Publish** (top of Customizer — required!)
6. Open homepage in a **private/incognito** window or hard refresh (**Ctrl+F5**)
7. If still cached: **LiteSpeed Cache → Purge All** (top admin bar)

**Note:** Theme deploys no longer reset your banner slides (fixed v4.6.50+). Your Customizer choices stay after code updates.

---

## 5. Product of the Day

The **Product of the Day** block on the homepage highlights **one** product.

### How the site picks it

1. **First choice:** Latest **Featured** product that is **in stock** and has an image  
2. **If none:** The **newest** in-stock product with an image

There is no separate “pick this SKU” setting — you control it with the **Featured** flag.

### Set today’s product

**Option A — when editing a product**

1. **Products → All Products** → open the product  
2. In **Product data**, check **Featured** (or use the star in the products list)  
3. **Update**  
4. To rotate tomorrow: uncheck Featured on the old product, check it on the new one

**Option B — quick star**

1. **Products → All Products**  
2. Click the **star** icon on the row for the product you want featured  
3. Only one featured product is needed for Product of the Day (latest featured wins)

### Show or hide the section

**Appearance → Customize → Homepage Sections** → toggle **Product of the Day** → **Publish**

---

## 6. Verify UPI orders (admin)

Customers pay via **UPI after** placing the order. New orders start as **Pending payment**.

### Morning routine

1. **WooCommerce → Orders**
2. Filter: **Pending payment**
3. Process oldest orders first

### Per-order verification

| Step | Action |
|------|--------|
| 1 | Open the order — note **Order #** and total amount |
| 2 | Check if customer clicked **“I've completed UPI payment”** on thank-you page (order note / UTR column if provided) |
| 3 | Open your **PhonePe / GPay / Paytm** business app |
| 4 | Find payment by **order number in remarks**, **amount**, **date**, or **UTR** |
| 5 | Confirm amount **exactly matches** order total |
| 6 | If valid → change status to **Processing** → **Update** |
| 7 | If invalid / no payment → add **Order note** → contact customer on WhatsApp → **Cancelled** if unresolved |

### Order list columns

The orders table may show **UTR** and payment-claim info for faster verification.

### Common payment issues

| Issue | What to do |
|-------|------------|
| Wrong amount paid | WhatsApp customer for balance or partial refund |
| No order # in UPI remarks | Match by amount + time + customer name |
| Duplicate UTR on two orders | Investigate before marking Processing |
| Customer never paid | Leave Pending or Cancel after follow-up |

---

## 7. Update order status & shipping

### Status workflow

| Status | Meaning | When to use |
|--------|---------|-------------|
| **Pending payment** | Order placed; UPI not verified | Default after checkout |
| **Processing** | Payment confirmed; preparing shipment | After UPI verified |
| **Completed** | Delivered / closed | After courier delivery (or hand delivery) |
| **Cancelled** | Order voided | Invalid payment or customer request |
| **On hold** | Awaiting info | Optional — use if you need customer action |

### Ship an order

1. Open order in **Processing** status  
2. Pack items — print packing slip: order → **Print** (or your packing plugin)  
3. After handover to courier, add an **Order note** (customer-visible if you tick “Note to customer”):

   ```
   Shipped via Delhivery. Tracking: 1234567890. Expected delivery 5–7 business days.
   ```

4. Change status to **Completed** → **Update** (customer gets completed email if enabled)

### Bulk actions

Select multiple verified orders → **Bulk actions** → **Change status to processing**

---

## 8. Track orders (customer & admin)

### Customer — Track Order page

URL: **https://www.rudrajewellery.co.in/track-order/**

Customer enters:

- **Order ID** (order number from email or thank-you page)  
- **Billing email** used at checkout  

They see WooCommerce order status (Pending payment → Processing → Completed).

### Customer — My Account

1. **My Account → Orders**  
2. Click order number for details and status  

Login required (guest checkout is disabled on this store).

### Customer — email updates

Ensure emails are on: **WooCommerce → Settings → Emails**

| Email | When customer receives it |
|-------|---------------------------|
| On hold / Pending | After order — pay via UPI instructions |
| Processing | After you verify payment |
| Completed | When you mark order completed / shipped |

### Admin — find an order

- **WooCommerce → Orders** → search by order #, name, email, or phone  
- Or filter by status / date  

### Delivery timeline (shown to customers)

1. **UPI verified** — within 24 hours of payment  
2. **Packed & shipped** — tracking in email when available  
3. **Delivered** — typically **5–10 business days** across India  

---

## 9. Other common tasks

### Store contact details (phone, WhatsApp, email)

**Appearance → Customize → Store UI**

- Store phone, emails, WhatsApp number, address  
- Click **Publish**

### Promo codes

**WooCommerce → Marketing → Coupons → Add coupon**

Customers apply codes on the **Cart** page.  
See also: [PROMO-CODES.md](PROMO-CODES.md)

### Hide a product without deleting

- Set **Stock** to 0 and **Out of stock**, or  
- Change status to **Draft**

### Best Sellers on shop

Products marked **Featured** appear in Best Sellers filters and mega menu.

### Purge website cache

After banner or major content changes:

- Top admin bar → **LiteSpeed Cache → Purge All**  
- Or use **Purge this page** when viewing the homepage  

### UPI payment settings

**WooCommerce → Settings → Payments → Manual UPI (Jewelry Store) → Manage**

- UPI ID, QR code image URL, payment instructions  
See: [UPI-SETTINGS.md](UPI-SETTINGS.md)

### Email delivery (SMTP)

If customers don’t receive emails, configure **WP Mail SMTP**.  
See: [EMAIL-SETUP.md](EMAIL-SETUP.md)

---

## 10. Troubleshooting

| Problem | Fix |
|---------|-----|
| Banner changes not visible | Customizer → **Publish**; Purge LiteSpeed cache; Ctrl+F5 |
| New product not on shop | Add **product image**; check **Published** + **In stock** + category |
| Product page error | Contact developer — check theme version in page source footer comment |
| Order stuck on Pending | Verify UPI in app; check customer used correct order # in remarks |
| Images look cropped on desktop banner | Use **1920×720** widescreen images; or re-upload after theme v4.6.51+ |
| Changes lost after deploy | Only **theme files** deploy — products/banners are in WordPress DB (safe) |
| Deploy doesn’t update banners | **Expected** — banners are not in deploy; update via Customizer |

---

## 11. Daily checklist

**Morning (15–30 min)**

- [ ] **WooCommerce → Orders** → process all **Pending payment**  
- [ ] Verify UPI in app → move to **Processing**  
- [ ] Reply to WhatsApp / contact form messages  

**When adding new jewellery**

- [ ] Upload square product image (800×800+)  
- [ ] Set price, stock, category  
- [ ] Publish and check on shop + mobile  
- [ ] Optional: mark **Featured** for Product of the Day or Best Sellers  

**When changing homepage**

- [ ] Update hero slides in **Customizer → Homepage Hero**  
- [ ] Publish Customizer  
- [ ] Purge cache and verify homepage  

**Weekly**

- [ ] Review low-stock products  
- [ ] Rotate **Featured** product for Product of the Day  
- [ ] Check **WooCommerce → Analytics → Orders** for revenue  

---

## Quick links

| Task | Path in wp-admin |
|------|------------------|
| Add product | Products → Add New |
| All products | Products → All Products |
| Orders | WooCommerce → Orders |
| Banner slides | Appearance → Customize → Homepage Hero |
| Product of the Day | Mark product **Featured** + Customize → Homepage Sections |
| Coupons | WooCommerce → Marketing → Coupons |
| UPI settings | WooCommerce → Settings → Payments |
| Media uploads | Media → Add New |

---

## Related technical docs (developers)

- [ADMIN-WORKFLOW.md](ADMIN-WORKFLOW.md) — short admin order routine  
- [UPI-SETTINGS.md](UPI-SETTINGS.md) — payment gateway setup  
- [DEPLOY.md](DEPLOY.md) — theme deploy (does not sync WordPress content)  
- [EMAIL-SETUP.md](EMAIL-SETUP.md) — SMTP configuration  

---

*Last updated: June 2026 — Rudra Jewellery theme v4.6.51*
