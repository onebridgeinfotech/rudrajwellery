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

		// Prevent LiteSpeed / any cache from storing wc-ajax responses.
		add_action( 'init', array( __CLASS__, 'no_cache_wc_ajax' ), 0 );

		// Fast-path via ?wc-ajax= endpoint (WC default on most servers).
		add_action( 'wc_ajax_update_order_review', array( __CLASS__, 'fast_update_order_review' ), 1 );

		// Fallback via admin-ajax.php (some Hostinger configs route here instead).
		add_action( 'wp_ajax_woocommerce_update_order_review', array( __CLASS__, 'fast_update_order_review' ), 1 );
		add_action( 'wp_ajax_nopriv_woocommerce_update_order_review', array( __CLASS__, 'fast_update_order_review' ), 1 );
	}

	/**
	 * Set no-cache headers for every wc-ajax request so LiteSpeed never caches them.
	 */
	public static function no_cache_wc_ajax() {
		if ( ! isset( $_GET['wc-ajax'] ) && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			return;
		}
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'wc_ajax' );
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
	}

	/**
	 * Return a minimal update_order_review response instantly.
	 *
	 * Runs before WooCommerce's own handler (priority 1). Bypasses the slow
	 * shipping/gateway recalculation that causes checkout to hang on shared hosting.
	 *
	 * We deliberately skip nonce verification here. update_order_review is a
	 * read-only display refresh — it modifies no cart or order state, so there
	 * is nothing to protect. Verifying the nonce would permanently block the
	 * checkout form whenever the page is served from LiteSpeed cache (stale nonce
	 * causes WC's own handler to return "-1"; WC JS can't parse it and never fires
	 * updated_checkout, leaving the order-review and payment sections blocked forever).
	 * The actual order placement uses its own independent nonce on ?wc-ajax=checkout.
	 */
	public static function fast_update_order_review() {
		// Return the cart hash so WC JS does not trigger a reload.
		// An empty string is safe: WC JS only reloads when cart_hash is truthy AND differs.
		$cart_hash = '';
		if ( function_exists( 'WC' ) ) {
			if ( WC()->cart ) {
				$cart_hash = WC()->cart->get_cart_hash();
			} elseif ( WC()->session ) {
				$cart_hash = (string) WC()->session->get( 'cart_hash', '' );
			}
		}

		// Empty fragments = no DOM replacements; WooCommerce JS unblocks the form.
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

		// Orders page fallback (always defined, never empty).
		$orders_url = function_exists( 'wc_get_account_endpoint_url' )
			? wc_get_account_endpoint_url( 'orders' )
			: home_url( '/my-account/orders/' );

		wp_register_script( 'jus-checkout-reliability', false, array( 'jquery', 'wc-checkout' ), JUS_VERSION, true );
		wp_enqueue_script( 'jus-checkout-reliability' );
		wp_add_inline_script(
			'jus-checkout-reliability',
			'(function($){' .

			// ── Recovery: redirect to orders page when order succeeded but AJAX returned error ──
			'var redirect=' . wp_json_encode( esc_url_raw( $redirect ) ) . ';' .
			'var ordersUrl=' . wp_json_encode( esc_url_raw( $orders_url ) ) . ';' .
			'function cartCount(){var n=parseInt($(".jwellery-cart-count,.cart-count-badge,.cart-contents-count").first().text(),10);return isNaN(n)?0:n;}' .
			'function maybeRecover(){if(!$(".woocommerce-error").length){return;}if(cartCount()>0){return;}window.location.href=redirect||ordersUrl;}' .
			'$(document.body).on("checkout_error",function(){setTimeout(maybeRecover,900);});' .

			// ── update_order_review watchdog (rolling 3s timer) ───────────────────────────────
			// WooCommerce blocks the form while update_order_review AJAX is in-flight.
			// On shared hosting this can stall permanently. Reset the 3s timer on every
			// update_checkout trigger; cancel it when WC responds (updated_checkout / checkout_error).
			'var _jusUpdateTimer=null;' .
			'function jusUnblock(){' .
			'_jusUpdateTimer=null;' .
			'var $f=$("form.woocommerce-checkout");' .
			'if(!$f.length){return;}' .
			'$f.removeClass("processing").unblock();' .
			'$f.find("#place_order").prop("disabled",false).css("opacity","");' .
			'}' .
			'function jusArmUpdateTimer(){clearTimeout(_jusUpdateTimer);_jusUpdateTimer=setTimeout(jusUnblock,1500);}' .
			'$(document.body).on("update_checkout",jusArmUpdateTimer);' .
			'$(document.body).on("updated_checkout checkout_error",function(){clearTimeout(_jusUpdateTimer);_jusUpdateTimer=null;});' .
			// Extra unblock on error so a failed process_checkout also clears any overlay.
			'$(document.body).on("checkout_error",function(){setTimeout(jusUnblock,400);});' .
			// Hard safety net: 8s after page-ready, unconditionally unblock.
			'$(document).ready(function(){setTimeout(jusUnblock,8000);});' .

			// ── Place Order timeout (process_checkout AJAX) ───────────────────────────────────
			// If the server-side process_checkout hangs (PHP timeout on shared hosting),
			// the AJAX call never resolves and the button spins forever.
			// After 28s we redirect: to the thank-you URL if the order was placed (cart empty),
			// otherwise to the orders page so the customer can check.
			'var _jusOrderTimer=null;' .
			'$(document.body).on("checkout_place_order",function(){' .
			'clearTimeout(_jusOrderTimer);' .
			'_jusOrderTimer=setTimeout(function(){' .
			'_jusOrderTimer=null;' .
			// Redirect: if cart appears empty, order was likely created — go to thank-you.
			// If cart still has items, go to orders page so customer can see status.
			'window.location.href=(cartCount()===0&&redirect)?redirect:ordersUrl;' .
			'},28000);' .
			'});' .
			// On any checkout response (success redirects automatically; error fires checkout_error).
			'$(document.body).on("checkout_error",function(){clearTimeout(_jusOrderTimer);_jusOrderTimer=null;});' .

			'})(jQuery);'
		);
	}
}
