<?php
/**
 * Checkout reliability — defer slow emails and recover from false AJAX errors.
 *
 * On shared hosting, synchronous order emails can exceed checkout AJAX timeouts.
 * The order is often created successfully while the browser shows a generic error.
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JUS_Checkout_Reliability
 */
class JUS_Checkout_Reliability {

	const SESSION_REDIRECT = 'jus_checkout_redirect';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_defer_transactional_emails', '__return_true' );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'remember_redirect' ), 20, 3 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_checkout_script' ), 30 );

		// Fast-path via ?wc-ajax= endpoint (WC default on most servers).
		add_action( 'wc_ajax_update_order_review', array( __CLASS__, 'fast_update_order_review' ), 1 );

		// Fallback via admin-ajax.php (some Hostinger configs route here instead).
		add_action( 'wp_ajax_woocommerce_update_order_review', array( __CLASS__, 'fast_update_order_review' ), 1 );
		add_action( 'wp_ajax_nopriv_woocommerce_update_order_review', array( __CLASS__, 'fast_update_order_review' ), 1 );
	}

	/**
	 * Return a minimal update_order_review response instantly.
	 *
	 * Runs before WooCommerce's own handler (priority 1). Bypasses the slow
	 * shipping/gateway recalculation that causes checkout to hang on shared hosting.
	 * Empty fragments are safe: the checkout HTML is already rendered by PHP on page load.
	 */
	public static function fast_update_order_review() {
		// Validate nonce — same key WooCommerce uses.
		$nonce = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'update-order-review' ) ) {
			// Invalid nonce — fall through to WooCommerce's own handler.
			return;
		}

		// Return the stored cart hash from the session so WC JS does not trigger a reload.
		$cart_hash = '';
		if ( function_exists( 'WC' ) ) {
			if ( WC()->cart ) {
				$cart_hash = WC()->cart->get_cart_hash();
			} elseif ( WC()->session ) {
				$cart_hash = (string) WC()->session->get( 'cart_hash', '' );
			}
		}

		// Empty fragments = no DOM replacements; WooCommerce JS still unblocks the form.
		wp_send_json( array(
			'result'    => 'success',
			'fragments' => array(),
			'cart_hash' => $cart_hash,
		) );
	}

	/**
	 * Remember thank-you URL for the current checkout session.
	 *
	 * @param int      $order_id Order ID.
	 * @param array    $posted   Posted data.
	 * @param WC_Order $order    Order.
	 */
	public static function remember_redirect( $order_id, $posted, $order ) {
		unset( $posted );

		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order || ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		WC()->session->set( self::SESSION_REDIRECT, $order->get_checkout_order_received_url() );
	}

	/**
	 * Checkout fallback script when AJAX returns an error after order creation.
	 */
	public static function enqueue_checkout_script() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$redirect = WC()->session->get( self::SESSION_REDIRECT );
		if ( ! is_string( $redirect ) || '' === $redirect ) {
			$redirect = function_exists( 'wc_get_account_endpoint_url' )
				? wc_get_account_endpoint_url( 'orders' )
				: home_url( '/my-account/orders/' );
		}

		wp_register_script( 'jus-checkout-reliability', false, array( 'jquery', 'wc-checkout' ), JUS_VERSION, true );
		wp_enqueue_script( 'jus-checkout-reliability' );
		wp_add_inline_script(
			'jus-checkout-reliability',
			'(function($){' .

			// ── Recovery: redirect to orders page when order succeeded but AJAX returned error ──
			'var redirect=' . wp_json_encode( esc_url_raw( $redirect ) ) . ';' .
			'function cartCount(){var n=parseInt($(".jwellery-cart-count,.cart-count-badge,.cart-contents-count").first().text(),10);return isNaN(n)?0:n;}' .
			'function maybeRecover(){if(!redirect||!$(".woocommerce-error").length){return;}if(cartCount()>0){return;}window.location.href=redirect;}' .
			'$(document.body).on("checkout_error",function(){setTimeout(maybeRecover,900);});' .

			// ── Overlay watchdog ─────────────────────────────────────────────────────────────
			// WooCommerce blocks the checkout form while update_order_review AJAX is in-flight.
			// On shared hosting the request can stall. We keep a rolling timer that fires 3s
			// after the LAST update_checkout event. If the AJAX comes back first (updated_checkout
			// or checkout_error), we cancel the timer — WC will unblock the form itself.
			// This means repeated field changes are safe: each change resets the timer to 3s.
			'var _jusTimer=null;' .
			'function jusForceUnblock(){' .
			'_jusTimer=null;' .
			'var $form=$("form.woocommerce-checkout");' .
			'if(!$form.length){return;}' .
			'$form.removeClass("processing").unblock();' .
			'$form.find("#place_order").prop("disabled",false).css("opacity","");' .
			'}' .
			'function jusArmTimer(){' .
			'clearTimeout(_jusTimer);' .
			'_jusTimer=setTimeout(jusForceUnblock,3000);' .
			'}' .
			// Arm a 3s watchdog every time WC starts an update_checkout AJAX call.
			'$(document.body).on("update_checkout",jusArmTimer);' .
			// When WC completes the call (success or error), cancel our timer.
			'$(document.body).on("updated_checkout checkout_error",function(){' .
			'clearTimeout(_jusTimer);_jusTimer=null;' .
			'});' .
			// Extra unblock on checkout_error so a failed process_checkout also clears overlay.
			'$(document.body).on("checkout_error",function(){setTimeout(jusForceUnblock,500);});' .
			// Hard safety net: 8s after page ready, force-unblock unconditionally.
			'$(document).ready(function(){setTimeout(jusForceUnblock,8000);});' .

			'})(jQuery);'
		);
	}
}
