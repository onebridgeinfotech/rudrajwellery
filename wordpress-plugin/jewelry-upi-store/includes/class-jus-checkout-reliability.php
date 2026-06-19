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
			'(function($){var redirect=' . wp_json_encode( esc_url_raw( $redirect ) ) . ';function cartCount(){var n=parseInt($(".jwellery-cart-count,.cart-count-badge,.cart-contents-count").first().text(),10);return isNaN(n)?0:n;}function maybeRecover(){if(!redirect||!$(".woocommerce-error").length){return;}if(cartCount()>0){return;}window.location.href=redirect;}$(document.body).on("checkout_error",function(){setTimeout(maybeRecover,900);});})(jQuery);'
		);
	}
}
