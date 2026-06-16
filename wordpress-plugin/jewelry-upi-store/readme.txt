=== Jewelry UPI Store ===
Contributors: jewelry-ecommerce
Tags: woocommerce, upi, payment, jewelry, india
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later

Manual UPI payment gateway for WooCommerce jewelry stores.

== Description ==

* Manual UPI payment gateway (UPI ID + QR on thank-you page)
* Orders set to Pending payment until admin verifies
* UPI-only at checkout (other gateways hidden)
* Requires customer login to checkout

== Installation ==

1. Install and activate WooCommerce.
2. Upload `jewelry-upi-store` folder to `/wp-content/plugins/`.
3. Activate the plugin.
4. WooCommerce → Settings → Payments → Manual UPI (Jewelry Store) → configure UPI ID and QR URL.

== Changelog ==

= 1.1.0 =
* Combined payment tracking: order # in UPI remarks + optional UTR after pay + "I've completed payment" (emails admin).
* Admin orders list: UPI paid? and UTR columns.

= 1.0.9 =
* Removed WhatsApp order alerts — email notifications only.

= 1.0.8 =
* Customer email on new order (awaiting UPI payment) with pay instructions.
* Customer "Payment received" email when admin marks Processing.
* Admin new order email includes UPI verification summary.

= 1.0.7 =
* Removed UTR field at checkout (customers confused it with UPI ID).
* Verify payments by order number in UPI remarks + amount.

= 1.0.6 =
* Fix critical error: removed block checkout integration (classic checkout only).
* Gateway loads only when WooCommerce is fully ready.

= 1.0.5 =
* Fix critical error: load gateway/blocks only after WooCommerce is fully loaded.
* Safer admin notices and block checkout field registration.

= 1.0.0 =
* Initial release.
