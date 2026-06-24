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
	 * Fast update_order_review with real HTML fragments (runs before WC default).
	 *
	 * Mirrors WC_AJAX::update_order_review but skips nonce verification so a
	 * LiteSpeed-cached checkout page never gets "-1" and stuck blockUI overlays.
	 * Shipping is already disabled store-wide; calculate_shipping() is cheap here.
	 */
	public static function fast_update_order_review() {
		if ( ! function_exists( 'WC' ) ) {
			wp_send_json( array( 'result' => 'failure' ) );
		}

		if ( null === WC()->cart ) {
			wc_load_cart();
		}

		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		if ( WC()->cart->is_empty() && ! is_customize_preview() && apply_filters( 'woocommerce_checkout_update_order_review_expired', true ) ) {
			wp_send_json(
				array(
					'fragments' => apply_filters(
						'woocommerce_update_order_review_fragments',
						array(
							'form.woocommerce-checkout' => wc_print_notice(
								esc_html__( 'Sorry, your session has expired.', 'woocommerce' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="wc-backward">' . esc_html__( 'Return to shop', 'woocommerce' ) . '</a>',
								'error',
								array(),
								true
							),
						)
					),
				)
			);
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		do_action( 'woocommerce_checkout_update_order_review', isset( $_POST['post_data'] ) ? wp_unslash( $_POST['post_data'] ) : '' );

		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
		$posted_shipping_methods = isset( $_POST['shipping_method'] ) ? wc_clean( wp_unslash( $_POST['shipping_method'] ) ) : array();

		if ( is_array( $posted_shipping_methods ) ) {
			foreach ( $posted_shipping_methods as $i => $value ) {
				if ( ! is_string( $value ) ) {
					continue;
				}
				$chosen_shipping_methods[ $i ] = $value;
			}
		}

		WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
		WC()->session->set( 'chosen_payment_method', empty( $_POST['payment_method'] ) ? '' : wc_clean( wp_unslash( $_POST['payment_method'] ) ) );
		WC()->customer->set_props(
			array(
				'billing_country'   => isset( $_POST['country'] ) ? wc_clean( wp_unslash( $_POST['country'] ) ) : null,
				'billing_state'     => isset( $_POST['state'] ) ? wc_clean( wp_unslash( $_POST['state'] ) ) : null,
				'billing_postcode'  => isset( $_POST['postcode'] ) ? wc_clean( wp_unslash( $_POST['postcode'] ) ) : null,
				'billing_city'      => isset( $_POST['city'] ) ? wc_clean( wp_unslash( $_POST['city'] ) ) : null,
				'billing_address_1' => isset( $_POST['address'] ) ? wc_clean( wp_unslash( $_POST['address'] ) ) : null,
				'billing_address_2' => isset( $_POST['address_2'] ) ? wc_clean( wp_unslash( $_POST['address_2'] ) ) : null,
			)
		);

		if ( wc_ship_to_billing_address_only() ) {
			WC()->customer->set_props(
				array(
					'shipping_country'   => isset( $_POST['country'] ) ? wc_clean( wp_unslash( $_POST['country'] ) ) : null,
					'shipping_state'     => isset( $_POST['state'] ) ? wc_clean( wp_unslash( $_POST['state'] ) ) : null,
					'shipping_postcode'  => isset( $_POST['postcode'] ) ? wc_clean( wp_unslash( $_POST['postcode'] ) ) : null,
					'shipping_city'      => isset( $_POST['city'] ) ? wc_clean( wp_unslash( $_POST['city'] ) ) : null,
					'shipping_address_1' => isset( $_POST['address'] ) ? wc_clean( wp_unslash( $_POST['address'] ) ) : null,
					'shipping_address_2' => isset( $_POST['address_2'] ) ? wc_clean( wp_unslash( $_POST['address_2'] ) ) : null,
				)
			);
		} else {
			WC()->customer->set_props(
				array(
					'shipping_country'   => isset( $_POST['s_country'] ) ? wc_clean( wp_unslash( $_POST['s_country'] ) ) : null,
					'shipping_state'     => isset( $_POST['s_state'] ) ? wc_clean( wp_unslash( $_POST['s_state'] ) ) : null,
					'shipping_postcode'  => isset( $_POST['s_postcode'] ) ? wc_clean( wp_unslash( $_POST['s_postcode'] ) ) : null,
					'shipping_city'      => isset( $_POST['s_city'] ) ? wc_clean( wp_unslash( $_POST['s_city'] ) ) : null,
					'shipping_address_1' => isset( $_POST['s_address'] ) ? wc_clean( wp_unslash( $_POST['s_address'] ) ) : null,
					'shipping_address_2' => isset( $_POST['s_address_2'] ) ? wc_clean( wp_unslash( $_POST['s_address_2'] ) ) : null,
				)
			);
		}

		if ( isset( $_POST['has_full_address'] ) && wc_string_to_bool( wc_clean( wp_unslash( $_POST['has_full_address'] ) ) ) ) {
			WC()->customer->set_calculated_shipping( true );
		} else {
			WC()->customer->set_calculated_shipping( false );
		}

		WC()->customer->save();
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();

		ob_start();
		woocommerce_order_review();
		$woocommerce_order_review = ob_get_clean();

		ob_start();
		woocommerce_checkout_payment();
		$woocommerce_checkout_payment = ob_get_clean();

		$reload_checkout = isset( WC()->session->reload_checkout );
		if ( ! $reload_checkout ) {
			$messages = wc_print_notices( true );
		} else {
			$messages = '';
		}

		unset( WC()->session->refresh_totals, WC()->session->reload_checkout );

		wp_send_json(
			array(
				'result'    => empty( $messages ) ? 'success' : 'failure',
				'messages'  => $messages,
				'reload'    => $reload_checkout,
				'fragments' => apply_filters(
					'woocommerce_update_order_review_fragments',
					array(
						'.woocommerce-checkout-review-order-table' => $woocommerce_order_review,
						'.woocommerce-checkout-payment'            => $woocommerce_checkout_payment,
					)
				),
				'cart_hash' => WC()->cart->get_cart_hash(),
			)
		);
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

		[ $tbl, $pay ].forEach( function ( $el ) {
			if ( ! $el.length ) { return; }
			try { $el.unblock(); } catch ( e ) {}
			$el.removeClass( 'blockUI' );
			$el.find( '.blockUI' ).remove();
		} );

		if ( $btn.length ) {
			$btn.prop( 'disabled', false ).css( { opacity: '', visibility: '', display: '' } );
		}

		/* Also unblock the outer form in case WC or a plugin blocked it too */
		var $form = $( 'form.woocommerce-checkout' );
		if ( $form.length ) {
			$form.removeClass( 'processing' );
			try { $form.unblock(); } catch ( e ) {}
			$form.find( '.blockUI' ).remove();
		}
	}

	/* ── Rolling watchdog for update_order_review AJAX ── */
	var _watchdogTimer = null;
	function armWatchdog() {
		clearTimeout( _watchdogTimer );
		_watchdogTimer = setTimeout( jusForceUnblock, 2000 );
	}
	$( document.body ).on( 'update_checkout', armWatchdog );
	/* Always force-unblock after WC finishes — empty/cached fragments can leave spinners. */
	$( document.body ).on( 'updated_checkout checkout_error', function () {
		clearTimeout( _watchdogTimer );
		_watchdogTimer = null;
		setTimeout( jusForceUnblock, 0 );
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
