# Build site like ramyanagendra.com

Reference layout (from [ramyanagendra.com](https://ramyanagendra.com/) when live):

- Announcement bar, search, social links
- Menu: Home, **Shop** (6 sub-items), About, Contact, Track Order
- Log in + Cart
- Homepage: Best Sellers → Top Categories → Handmade → New → Product of the Day → Instagram
- Product carousels with "1 / 10" style counter
- Sold out badges, Add to cart
- Footer policies + social

## Theme v2.0.0

Upload **`1-THEME-UPLOAD-jwellery-jewelry.zip`** from `FOR-HOSTINGER-UPLOAD`.

## After upload — 5 steps in wp-admin

### 1. Run store setup
**Appearance → Store Setup → Run setup now**

Creates categories, menu, pages.

### 2. Import products
**Products → Import →** `sample-data/products-sample.csv`

### 3. Mark featured products
**Products → All Products** → star icon on bestsellers (for Best Sellers section)

### 4. Social + logo
**Appearance → Customize**
- Site Identity → logo
- Social Links → Facebook, Instagram, YouTube URLs

### 5. UPI plugin
**WooCommerce → Settings → Payments → Manual UPI**

## Optional plugins (closer to reference)

| Plugin | For |
|--------|-----|
| Built-in theme wishlist (`inc/wishlist.php`) | Heart icon, My Account tab — no extra plugin |
| Contact Form 7 | Contact page form |

## Paste your Hostinger URL

Share your live URL (e.g. `yoursite.hostingersite.com`) so we can review the homepage in the browser and fine-tune.

## Reference site note

The Shopify store at ramyanagendra.com may show "store does not exist" if the subscription ended. The WordPress theme copies the **layout and sections** from the original design.
