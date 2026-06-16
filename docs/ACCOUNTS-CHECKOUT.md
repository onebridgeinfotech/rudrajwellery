# Phase 4–5: Login, cart, and UPI checkout

## Customer login (required)

The **Jewelry UPI Store** plugin enforces:

- Guest checkout **disabled**
- Registration **required** at checkout
- Guests visiting `/checkout/` are redirected to **My Account**

### WooCommerce settings (verify)

**WooCommerce → Settings → Accounts & Privacy**

| Setting | Value |
|---------|-------|
| Allow customers to create an account on "My account" | Checked |
| Allow customers to create an account during checkout | Checked |
| Allow customers to log in to an existing account during checkout | Checked |
| Allow customers to place orders without an account | **Unchecked** |

## Shopping flow

1. Customer browses **Shop** or category pages
2. **Add to cart** → **Cart** → **Proceed to checkout**
3. If not logged in → **My Account** (login or register)
4. Checkout: billing/shipping, **UPI Transaction ID (UTR)**, payment method **Pay via UPI** only
5. Place order → **Pending payment** status
6. Thank-you page: order #, amount, UPI ID, QR code

## Cart

Ensure WooCommerce pages exist under **Settings → Advanced**:

- Cart: `/cart/`
- Checkout: `/checkout/`
- My account: `/my-account/`

Theme should show cart icon in header (Kadence does by default when WooCommerce is active).

## Disable other payment methods

In **WooCommerce → Settings → Payments**, turn **off** every method except **Manual UPI (Jewelry Store)**.

The plugin hides other gateways on the frontend even if left enabled — disabling in admin avoids confusion.
