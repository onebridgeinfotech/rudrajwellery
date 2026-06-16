<?php
/**
 * One-time store fix: checkout, cart, promo codes, UPI.
 *
 * HOW TO USE (Hostinger File Manager):
 * 1. Upload this file to public_html (same folder as wp-config.php)
 * 2. Log in to WordPress admin in another tab
 * 3. Open: https://YOUR-SITE.hostingersite.com/3-VISIT-ONCE-FIX-CHECKOUT.php
 * 4. DELETE this file from public_html immediately after you see "Done"
 *
 * @package JwelleryJewelry
 */

$wp_load = __DIR__ . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
	header( 'Content-Type: text/plain; charset=utf-8' );
	die( "wp-load.php not found. Upload this file to public_html (WordPress root).\n" );
}

require $wp_load;

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	header( 'Content-Type: text/html; charset=utf-8' );
	echo '<p>Access denied. Log in to WordPress admin as an administrator, then open this URL again.</p>';
	exit;
}

/**
 * Switch WC page to classic shortcode if it uses blocks.
 *
 * @param string $page_key  cart|checkout.
 * @param string $shortcode Shortcode.
 * @return bool True if updated.
 */
function jwellery_fix_wc_page_shortcode( $page_key, $shortcode ) {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return false;
	}

	$page_id = wc_get_page_id( $page_key );
	if ( $page_id <= 0 ) {
		return false;
	}

	$content = (string) get_post_field( 'post_content', $page_id );
	$uses_blocks = (
		false !== strpos( $content, 'woocommerce/cart' )
		|| false !== strpos( $content, 'woocommerce/checkout' )
		|| false !== strpos( $content, 'wp:woocommerce/cart' )
		|| false !== strpos( $content, 'wp:woocommerce/checkout' )
	);

	if ( ! $uses_blocks ) {
		return false;
	}

	wp_update_post(
		array(
			'ID'           => $page_id,
			'post_content' => '<!-- wp:shortcode -->' . $shortcode . '<!-- /wp:shortcode -->',
		)
	);

	return true;
}

header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Store Fix — Checkout + Promo</title>
	<style>
		body { font-family: system-ui, sans-serif; max-width: 680px; margin: 40px auto; padding: 0 20px; line-height: 1.5; }
		h1 { color: #7b1e3a; }
		.ok { color: #0a7; }
		.err { color: #c00; }
		li { margin: 8px 0; }
		.box { background: #fffdf8; border: 1px solid #e0c080; padding: 16px; border-radius: 8px; margin-top: 20px; }
		code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
	</style>
</head>
<body>
	<h1>Store fix — Checkout, Cart, Promo codes, UPI</h1>
	<ul>
		<?php
		$all_ok = true;

		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<li class="err"><strong>WooCommerce:</strong> NOT active</li>';
			$all_ok = false;
		} else {
			echo '<li class="ok"><strong>WooCommerce:</strong> Active</li>';
		}

		update_option( 'woocommerce_enable_coupons', 'yes' );
		echo '<li class="ok"><strong>Promo codes:</strong> Enabled in WooCommerce settings</li>';

		if ( function_exists( 'jwellery_create_default_coupons' ) ) {
			$n = jwellery_create_default_coupons();
			echo '<li class="ok"><strong>Demo coupons:</strong> WELCOME10, FLAT50, SALE15 ready (' . (int) $n . ' new)</li>';
		} elseif ( class_exists( 'WC_Coupon' ) ) {
			echo '<li class="err"><strong>Demo coupons:</strong> Upload theme v3.3.7+ for auto coupon creation</li>';
		}

		if ( ! class_exists( 'JUS_Gateway', false ) ) {
			echo '<li class="err"><strong>UPI Plugin:</strong> NOT active — activate <em>Jewelry UPI Store</em> v1.0.6</li>';
			$all_ok = false;
		} else {
			echo '<li class="ok"><strong>UPI Plugin:</strong> Active</li>';
			if ( function_exists( 'jus_ensure_gateway_settings' ) ) {
				jus_ensure_gateway_settings();
			}
			echo '<li class="ok"><strong>UPI gateway:</strong> Enabled</li>';
		}

		if ( function_exists( 'wc_get_page_id' ) ) {
			if ( jwellery_fix_wc_page_shortcode( 'cart', '[woocommerce_cart]' ) ) {
				echo '<li class="ok"><strong>Cart page:</strong> Switched to classic shortcode (promo field visible)</li>';
			} else {
				echo '<li class="ok"><strong>Cart page:</strong> Already classic shortcode</li>';
			}

			if ( jwellery_fix_wc_page_shortcode( 'checkout', '[woocommerce_checkout]' ) ) {
				echo '<li class="ok"><strong>Checkout page:</strong> Switched to classic shortcode</li>';
			} else {
				echo '<li class="ok"><strong>Checkout page:</strong> Already classic shortcode</li>';
			}
		}

		if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
			WC()->payment_gateways()->init();
			$available = WC()->payment_gateways()->get_available_payment_gateways();
			if ( isset( $available['jus_manual_upi'] ) ) {
				echo '<li class="ok"><strong>Payment:</strong> Pay via UPI available at checkout</li>';
			} else {
				echo '<li class="err"><strong>Payment:</strong> UPI not available — enable Manual UPI in WooCommerce → Payments</li>';
				$all_ok = false;
			}
		}

		$products = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'limit' => 1, 'status' => 'publish' ) ) : array();
		if ( empty( $products ) ) {
			echo '<li class="err"><strong>Products:</strong> No products — run Appearance → Store Setup → Full setup</li>';
		} else {
			$test = $products[0];
			echo '<li class="ok"><strong>Products:</strong> Found — test product: ' . esc_html( $test->get_name() ) . ' (' . wp_strip_all_tags( $test->get_price_html() ) . ')</li>';
		}
		?>
	</ul>

	<div class="box">
		<?php if ( $all_ok ) : ?>
			<p class="ok"><strong>Done.</strong> Test flow:</p>
			<ol>
				<li>Open <a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>">Shop</a> → Add to cart</li>
				<li><a href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>">Cart</a> → enter promo <code>WELCOME10</code> → Apply</li>
				<li><a href="<?php echo esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ) ); ?>">Checkout</a> → log in → Pay via UPI → place order → pay on thank-you page</li>
			</ol>
		<?php else : ?>
			<p class="err"><strong>Action needed:</strong> Fix the red items above, then refresh this page.</p>
		<?php endif; ?>
		<p><strong>Security:</strong> Delete <code>3-VISIT-ONCE-FIX-CHECKOUT.php</code> from public_html now.</p>
	</div>
</body>
</html>
