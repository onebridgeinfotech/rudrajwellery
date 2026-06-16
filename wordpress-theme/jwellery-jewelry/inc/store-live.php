<?php
/**
 * Disable WooCommerce "Coming soon" mode (safe, no internal WC classes).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Disable WooCommerce coming soon.
 */
function jwellery_force_store_live() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	update_option( 'woocommerce_coming_soon', 'no' );
	update_option( 'woocommerce_store_pages_only', 'no' );
}

/**
 * Run once after theme activation.
 */
function jwellery_maybe_force_store_live() {
	jwellery_force_store_live();
}
add_action( 'after_switch_theme', 'jwellery_maybe_force_store_live', 20 );
