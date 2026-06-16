# Promo codes (coupons)

WooCommerce supports promo codes natively. **Cart and checkout use classic shortcodes** — customers see a **Promo code** field (try `WELCOME10`, `FLAT50`, `SALE15`).

## One-click setup (theme v2.3.0+)

**Appearance → Store Setup → Enable INR + create promo codes**

This sets:

- Currency: **INR (₹)**
- Country: **India**
- Creates three coupons:

| Code | Discount | Minimum order |
|------|----------|---------------|
| `WELCOME10` | 10% off | ₹399 |
| `FLAT50` | ₹50 off cart | ₹499 |
| `SALE15` | 15% off | ₹999 |

Full setup also runs INR + coupons automatically.

## Create your own promo codes

**WooCommerce → Marketing → Coupons → Add coupon**

| Field | Example |
|-------|---------|
| Code | `DIWALI20` |
| Discount type | Percentage / Fixed cart |
| Amount | `20` |
| Minimum spend | `999` |
| Usage limits | Optional |

## Test flow

1. Add product to cart
2. Cart → **Apply promo code** → enter `WELCOME10` → **Apply**
3. Total should drop by 10%
4. Proceed to checkout (login required) — discount carries through
5. Pay via UPI; order total includes discount

## Troubleshooting

| Issue | Fix |
|-------|-----|
| “Invalid coupon” | Run Store Setup to create codes, or create coupon in admin |
| No promo field on checkout | Upload theme **v3.3.7+** (classic cart/checkout + visible promo box), or run `3-VISIT-ONCE-FIX-CHECKOUT.php` |
| Still shows `$` | Run **Enable INR + create promo codes** or **WooCommerce → Settings → General → Currency → INR** |
