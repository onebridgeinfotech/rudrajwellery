<?php
/**
 * Customer account and checkout login requirements.
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JUS_Accounts
 */
class JUS_Accounts {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'require_login_at_checkout' ) );
		add_filter( 'woocommerce_checkout_registration_required', '__return_true' );
		add_filter( 'woocommerce_enable_guest_checkout', '__return_false' );
	}

	/**
	 * Redirect guests to My Account before checkout.
	 */
	public static function require_login_at_checkout() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		if ( is_user_logged_in() ) {
			return;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice(
				__( 'Please log in or register to place an order.', 'jewelry-upi-store' ),
				'notice'
			);
		}
		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
		wp_safe_redirect( $url );
		exit;
	}
}
