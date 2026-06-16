<?php
/**
 * Auto-fix checkout payment (classic checkout + UPI gateway).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checkout page still using WooCommerce Block?
 *
 * @return bool
 */
function jwellery_checkout_uses_blocks() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return false;
	}

	return jwellery_page_uses_wc_blocks( wc_get_page_id( 'checkout' ) );
}

/**
 * Cart page still using WooCommerce Block?
 *
 * @return bool
 */
function jwellery_cart_uses_blocks() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return false;
	}

	return jwellery_page_uses_wc_blocks( wc_get_page_id( 'cart' ) );
}

/**
 * Is Manual UPI available at checkout?
 *
 * @return bool
 */
function jwellery_is_upi_checkout_ready() {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
		return false;
	}

	if ( ! class_exists( 'JUS_Gateway', false ) ) {
		return false;
	}

	$wc = WC();
	if ( ! $wc || ! isset( $wc->payment_gateways ) || ! is_object( $wc->payment_gateways ) ) {
		return false;
	}

	$gateways = $wc->payment_gateways()->get_available_payment_gateways();
	return is_array( $gateways ) && isset( $gateways['jus_manual_upi'] );
}

/**
 * Run all checkout payment fixes and return a report.
 *
 * @return array<string, string>
 */
function jwellery_run_checkout_payment_fix() {
	$report = array();

	if ( ! class_exists( 'WooCommerce' ) ) {
		$report['woocommerce'] = __( 'WooCommerce is not active.', 'jwellery-jewelry' );
		return $report;
	}

	if ( function_exists( 'jus_ensure_gateway_settings' ) ) {
		jus_ensure_gateway_settings();
		$report['upi_settings'] = __( 'UPI gateway settings ensured.', 'jwellery-jewelry' );
	} elseif ( ! class_exists( 'JUS_Gateway', false ) ) {
		$report['upi_plugin'] = __( 'Jewelry UPI Store plugin is NOT active — upload and activate it.', 'jwellery-jewelry' );
	} else {
		$settings = get_option( 'woocommerce_jus_manual_upi_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['enabled'] = 'yes';
		update_option( 'woocommerce_jus_manual_upi_settings', $settings );
		$report['upi_settings'] = __( 'UPI gateway enabled in database.', 'jwellery-jewelry' );
	}

	if ( function_exists( 'jwellery_ensure_classic_checkout_page' ) ) {
		$report['checkout_page'] = jwellery_ensure_classic_checkout_page()
			? __( 'Checkout switched to classic [woocommerce_checkout] shortcode.', 'jwellery-jewelry' )
			: __( 'Checkout already uses classic shortcode.', 'jwellery-jewelry' );
	}

	if ( function_exists( 'jwellery_ensure_classic_cart_page' ) ) {
		$report['cart_page'] = jwellery_ensure_classic_cart_page()
			? __( 'Cart switched to classic [woocommerce_cart] shortcode (promo codes visible).', 'jwellery-jewelry' )
			: __( 'Cart already uses classic shortcode.', 'jwellery-jewelry' );
	}

	$wc = function_exists( 'WC' ) ? WC() : null;
	if ( class_exists( 'JUS_Gateway', false ) && $wc && isset( $wc->payment_gateways ) && is_object( $wc->payment_gateways ) ) {
		$all = $wc->payment_gateways()->payment_gateways();
		if ( isset( $all['jus_manual_upi'] ) ) {
			$report['upi_gateway'] = ( 'yes' === $all['jus_manual_upi']->enabled )
				? __( 'Manual UPI gateway is enabled.', 'jwellery-jewelry' )
				: __( 'Manual UPI gateway exists but is disabled.', 'jwellery-jewelry' );
		}
	}

	$report['checkout_ready'] = jwellery_is_upi_checkout_ready()
		? __( 'OK — Pay via UPI should appear at checkout.', 'jwellery-jewelry' )
		: __( 'Still not ready — activate Jewelry UPI Store plugin and upload plugin v1.0.4.', 'jwellery-jewelry' );

	return $report;
}

/**
 * Auto-fix when an admin visits wp-admin (once per hour if still broken).
 */
function jwellery_auto_fix_checkout_payment() {
	if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	if ( jwellery_is_upi_checkout_ready() && ! jwellery_checkout_uses_blocks() && ! jwellery_cart_uses_blocks() ) {
		return;
	}

	if ( get_transient( 'jwellery_checkout_fix_lock' ) ) {
		return;
	}

	jwellery_run_checkout_payment_fix();
	set_transient( 'jwellery_checkout_fix_lock', 1, HOUR_IN_SECONDS );
}
add_action( 'admin_init', 'jwellery_auto_fix_checkout_payment', 5 );

/**
 * Fix cart/checkout blocks only for shop managers in wp-admin (never on public requests).
 */
function jwellery_auto_fix_checkout_admin_pages() {
	if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'jwellery_ensure_classic_checkout_page' ) ) {
		return;
	}

	if ( ! jwellery_checkout_uses_blocks() && ! jwellery_cart_uses_blocks() ) {
		return;
	}

	if ( get_transient( 'jwellery_checkout_block_fix_lock' ) ) {
		return;
	}

	if ( function_exists( 'jwellery_ensure_classic_myaccount_page' ) ) {
		jwellery_ensure_classic_myaccount_page();
	}
	if ( function_exists( 'jwellery_ensure_classic_cart_page' ) ) {
		jwellery_ensure_classic_cart_page();
	}
	jwellery_ensure_classic_checkout_page();
	set_transient( 'jwellery_checkout_block_fix_lock', 1, HOUR_IN_SECONDS );
}
add_action( 'admin_init', 'jwellery_auto_fix_checkout_admin_pages', 6 );
