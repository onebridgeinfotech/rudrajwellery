# Phase 5: UPI-only manual payment

Configure after WooCommerce and **Jewelry UPI Store** plugin are active.

## 1. Payment gateway

1. **WooCommerce → Settings → Payments**
2. Enable **Manual UPI (Jewelry Store)** only
3. Disable: Cash on delivery, Direct bank transfer, Stripe, PayPal, etc.
4. Click **Manage** on Manual UPI:

| Field | Example |
|-------|---------|
| Title | Pay via UPI |
| Description | Scan QR or pay to our UPI ID. Enter UTR after payment. |
| UPI ID | `yourstore@paytm` or `9876543210@ybl` |
| QR Code Image URL | Media Library → upload QR → copy image URL |
| Instructions | Pay exact order total. Put your **Order Number** in UPI remarks. |

### Generate UPI QR

1. Open PhonePe / Google Pay / Paytm as **business** account
2. Profile → **My QR Code** → download image
3. WordPress → **Media → Add New** → upload PNG
4. Click image → copy **File URL** into plugin settings

5. **Save changes**

The plugin automatically disables other payment gateways when active.

---

## 2. UTR checkout field

Handled by **Jewelry UPI Store** plugin (no extra plugin needed):

- Field label: **UPI Transaction ID (UTR)**
- Required when Manual UPI is selected
- Visible in admin order screen and emails

Backup: use [snippets/checkout-utr-required.php](../snippets/checkout-utr-required.php) in child theme only if plugin is deactivated.

---

## 3. Order status workflow

| Event | Order status |
|-------|----------------|
| Customer completes checkout | **Pending payment** |
| Admin verifies UTR in bank app | Change to **Processing** |
| Order shipped | **Completed** |
| Invalid/fake UTR | **Cancelled** + note to customer |

### Admin steps

1. **WooCommerce → Orders** → filter **Pending payment**
2. Open order → compare **UTR** field with bank statement
3. Confirm **amount** = order total
4. Order actions → **Processing** → Update

---

## 4. Thank-you page

After checkout, customer sees:

- Order number (for UPI remarks)
- Order total
- UPI ID and QR image
- Reminder to pay and that confirmation is within 24 hours (default text from plugin)

Customize text: **WooCommerce → Settings → Payments → Manual UPI → Instructions**

---

## 5. Test payment flow

1. Create a test product ₹1 or use coupon 100% off for admin-only test
2. Register a test customer account
3. Place order with fake UTR `TEST123456789`
4. Confirm:
   - [ ] Order status = Pending payment
   - [ ] Thank-you page shows UPI details
   - [ ] Admin email received
   - [ ] Customer pending email received
5. Mark Processing → customer receives processing email
6. Delete test orders before launch

**Next:** [EMAIL-SETUP.md](EMAIL-SETUP.md) | [ADMIN-WORKFLOW.md](ADMIN-WORKFLOW.md)
