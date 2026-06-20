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

		// Print the reliability script directly in the footer. We deliberately do NOT
		// enqueue with a dependency on wc-checkout: if that handle is missing (cached
		// page, deferred scripts) WordPress silently drops a dependent inline script,
		// which is why earlier versions of this watchdog never ran on the live site.
		add_action( 'wp_footer', array( __CLASS__, 'print_checkout_script' ), 99 );

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

		// Empty fragments object = no DOM replacements; WooCommerce JS unblocks the form.
		// Must be stdClass so it JSON-encodes as {} (object), not [] (array).
		wp_send_json( array(
			'result'    => 'success',
			'fragments' => new stdClass(),
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
	 * Print checkout reliability script directly in wp_footer.
	 *
	 * Deliberately NOT enqueued with wp_enqueue_scripts / wc-checkout dependency:
	 * if wc-checkout handle is absent (cached page, script optimiser) WordPress
	 * silently drops any inline script that depends on it — which is why the
	 * watchdog never ran on the live site in earlier versions.
	 *
	 * Targets the exact elements WooCommerce's blockUI overlays:
	 *   .woocommerce-checkout-review-order-table  (order summary spinner)
	 *   #payment / .woocommerce-checkout-payment  (pay via UPI spinner)
	 * NOT the outer <form> — that is NOT what WC blocks.
	 */
	public static function print_checkout_script() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}

		// Redirect URL for post-order recovery (if process_checkout times out).
		$redirect = '';
		if ( function_exists( 'WC' ) && WC()->session ) {
			$redirect = (string) WC()->session->get( self::SESSION_REDIRECT, '' );
		}
		$orders_url = function_exists( 'wc_get_account_endpoint_url' )
			? wc_get_account_endpoint_url( 'orders' )
			: home_url( '/my-account/orders/' );
		if ( ! $redirect ) {
			$redirect = $orders_url;
		}

		?>
<script id="jus-checkout-reliability">
(function () {
	'use strict';
	var $ = window.jQuery;
	if ( ! $ ) { return; }

	var redirect   = <?php echo wp_json_encode( esc_url_raw( $redirect ) ); ?>;
	var ordersUrl  = <?php echo wp_json_encode( esc_url_raw( $orders_url ) ); ?>;

	/* ── Force-unblock: targets the EXACT elements WC's blockUI overlays ── */
	function jusForceUnblock() {
		/* WC blocks these two child elements, NOT the outer form */
		var $tbl = $( '.woocommerce-checkout-review-order-table' );
		var $pay = $( '#payment, .woocommerce-checkout-payment' );
		var $btn = $( '#place_order' );

		if ( $tbl.length ) { $tbl.unblock(); }
		if ( $pay.length ) { $pay.unblock(); }
		if ( $btn.length ) { $btn.prop( 'disabled', false ).css( 'opacity', '' ); }

		/* Also unblock the outer form in case WC or a plugin blocked it too */
		var $form = $( 'form.woocommerce-checkout' );
		if ( $form.length ) {
			$form.removeClass( 'processing' );
			try { $form.unblock(); } catch(e) {}
		}
	}

	/* ── Rolling watchdog for update_order_review AJAX ── */
	/* WC fires update_checkout, blocks the table+payment, then fires update_order_review.
	   Our plugins_loaded fast-path responds in microseconds so updated_checkout fires
	   quickly. But if the AJAX still hangs (cached stale response, network hiccup)
	   this timer fires 2s after the last update_checkout and force-unblocks. */
	var _watchdogTimer = null;
	function armWatchdog() {
		clearTimeout( _watchdogTimer );
		_watchdogTimer = setTimeout( jusForceUnblock, 2000 );
	}
	$( document.body ).on( 'update_checkout', armWatchdog );
	/* When WC finishes successfully or with an error, cancel watchdog (WC unblocks itself). */
	$( document.body ).on( 'updated_checkout checkout_error', function () {
		clearTimeout( _watchdogTimer );
		_watchdogTimer = null;
	} );

	/* ── Hard safety net: 3 s after DOMContentLoaded, force-unblock unconditionally ── */
	/* Covers the case where update_checkout fires before our listener is registered,
	   or WC blocks on page load before the jQuery event system is ready. */
	$( document ).ready( function () {
		setTimeout( jusForceUnblock, 3000 );
	} );

	/* ── Extra unblock on checkout_error (failed process_checkout) ── */
	$( document.body ).on( 'checkout_error', function () {
		setTimeout( jusForceUnblock, 400 );
	} );

	/* ── Place Order timeout: if process_checkout AJAX hangs > 28 s, redirect ── */
	var _orderTimer = null;
	$( document.body ).on( 'checkout_place_order', function () {
		clearTimeout( _orderTimer );
		_orderTimer = setTimeout( function () {
			_orderTimer = null;
			/* Cart count > 0 means order wasn't created; go to orders page. */
			var cartBadge = $( '.cart-count-badge,.jwellery-cart-count' ).first().text();
			var cartCount = parseInt( cartBadge, 10 ) || 0;
			window.location.href = ( cartCount === 0 && redirect ) ? redirect : ordersUrl;
		}, 28000 );
	} );
	$( document.body ).on( 'checkout_error', function () {
		clearTimeout( _orderTimer );
		_orderTimer = null;
	} );

	/* ── Recovery: if order was placed but AJAX errored, redirect to thank-you ── */
	function maybeRecover() {
		if ( ! $( '.woocommerce-error' ).length ) { return; }
		var cartBadge = $( '.cart-count-badge,.jwellery-cart-count' ).first().text();
		if ( ( parseInt( cartBadge, 10 ) || 0 ) > 0 ) { return; }
		window.location.href = redirect || ordersUrl;
	}
	$( document.body ).on( 'checkout_error', function () { setTimeout( maybeRecover, 900 ); } );
}());
</script>
		<?php
	}
}
