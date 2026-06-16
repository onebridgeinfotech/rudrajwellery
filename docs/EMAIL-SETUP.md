# Phase 6: Email notifications (customer + owner)

## 1. Admin email

**Settings → General → Administration Email Address**

Use the inbox you check for new orders (owner email).

---

## 2. WooCommerce emails

**WooCommerce → Settings → Emails**

Enable and customize:

| Email | Recipient | When |
|-------|-----------|------|
| **New order** | Admin (owner) | Every new order — includes UPI verify summary |
| **On hold order** | Customer | Right after checkout — pay via UPI instructions + UPI ID/amount |
| **Processing order** | Customer | After you confirm payment — **"Your UPI payment has been received"** |
| **Completed order** | Customer | When marked completed (shipped) |
| **Cancelled order** | Customer | If you cancel invalid payment |

Click each → **Manage** → ensure **Enable this email notification** is on (especially **On hold order** and **Processing order**).

---

## 3. WP Mail SMTP (required)

Shared hosting `mail()` often fails. Configure SMTP:

1. Install **WP Mail SMTP**
2. **WP Mail SMTP → Settings → General**
3. Choose mailer:

### Option A: Host email (cPanel)

| Field | Value |
|-------|-------|
| Mailer | Other SMTP |
| SMTP Host | `mail.yourdomain.com` |
| Encryption | SSL |
| SMTP Port | 465 |
| Authentication | On |
| Username | `orders@yourdomain.com` |
| Password | cPanel email account password |

Create `orders@yourdomain.com` in cPanel → **Email Accounts**.

### Option B: Gmail

| Field | Value |
|-------|-------|
| Mailer | Google / Gmail |
| Use App Password | [Google App Passwords](https://myaccount.google.com/apppasswords) |

### Option C: Brevo (Sendinblue) free tier

Sign up at brevo.com → SMTP keys → use in WP Mail SMTP.

4. **Email Test** tab → send test to your phone inbox
5. Confirm not in spam; add SPF/DKIM in cPanel if emails still fail

---

## 4. Email content tips

**New order (admin)** — ensure you see:

- Order number
- Customer name, phone, email
- Line items and total
- UPI verification reminder

**Processing (customer)** — subject: *Your UPI payment has been received*

Edit under WooCommerce → Settings → Emails → Processing order.

---

## 5. Test checklist

- [ ] Test email from WP Mail SMTP succeeds
- [ ] Place test order → admin receives New order
- [ ] Customer receives pending/on-hold email
- [ ] Mark Processing → customer receives processing email
- [ ] Check spam folder on Gmail test account

**Next:** [LAUNCH-CHECKLIST.md](LAUNCH-CHECKLIST.md)
