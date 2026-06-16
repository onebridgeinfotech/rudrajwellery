<?php
/**
 * WooCommerce store settings: INR currency, coupons enabled.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Apply India / INR defaults (safe to run multiple times).
 */
function jwellery_apply_store_config() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	update_option( 'woocommerce_enable_coupons', 'yes' );
	update_option( 'woocommerce_calc_discounts_sequentially', 'no' );

	update_option( 'woocommerce_currency', 'INR' );
	update_option( 'woocommerce_currency_pos', 'left' );
	update_option( 'woocommerce_price_thousand_sep', ',' );
	update_option( 'woocommerce_price_decimal_sep', '.' );
	update_option( 'woocommerce_price_num_decimals', '0' );

	// India.
	update_option( 'woocommerce_default_country', 'IN' );
	update_option( 'woocommerce_allowed_countries', 'specific' );
	update_option( 'woocommerce_specific_allowed_countries', array( 'IN' ) );

	// Customer sign-up on My Account (login + register forms).
	update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
	update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );
	update_option( 'woocommerce_registration_generate_username', 'yes' );
	update_option( 'woocommerce_registration_generate_password', 'yes' );

	jwellery_ensure_classic_cart_page();
	jwellery_ensure_classic_checkout_page();
	jwellery_ensure_classic_myaccount_page();
}

/**
 * Switch Checkout page from WooCommerce Block to classic shortcode so UPI gateway always shows.
 *
 * @return bool True when the page was updated.
 */
function jwellery_ensure_classic_checkout_page() {
	return jwellery_ensure_classic_wc_page( 'checkout', '[woocommerce_checkout]' );
}

/**
 * Switch Cart page from WooCommerce Block to classic shortcode (promo code + UPI flow).
 *
 * @return bool True when the page was updated.
 */
function jwellery_ensure_classic_cart_page() {
	return jwellery_ensure_classic_wc_page( 'cart', '[woocommerce_cart]' );
}

/**
 * Does a WooCommerce page use block markup?
 *
 * @param int $page_id Page ID.
 * @return bool
 */
function jwellery_page_uses_wc_blocks( $page_id ) {
	if ( $page_id <= 0 ) {
		return false;
	}

	$content = (string) get_post_field( 'post_content', $page_id );
	if ( '' === $content ) {
		return false;
	}

	return (
		false !== strpos( $content, 'woocommerce/cart' )
		|| false !== strpos( $content, 'woocommerce/checkout' )
		|| false !== strpos( $content, 'woocommerce/customer-account' )
		|| false !== strpos( $content, 'woocommerce/my-account' )
		|| false !== strpos( $content, 'wp:woocommerce/cart' )
		|| false !== strpos( $content, 'wp:woocommerce/checkout' )
		|| false !== strpos( $content, 'wp:woocommerce/customer-account' )
	);
}

/**
 * Switch My Account page from WooCommerce Block to classic shortcode (theme templates).
 *
 * @return bool True when the page was updated.
 */
function jwellery_ensure_classic_myaccount_page() {
	return jwellery_ensure_classic_wc_page( 'myaccount', '[woocommerce_my_account]' );
}

/**
 * Replace WooCommerce block page with classic shortcode.
 *
 * @param string $page_key   WooCommerce page key (cart, checkout).
 * @param string $shortcode  Shortcode to use.
 * @return bool True when updated.
 */
function jwellery_ensure_classic_wc_page( $page_key, $shortcode ) {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return false;
	}

	$page_id = wc_get_page_id( $page_key );
	if ( $page_id <= 0 || ! jwellery_page_uses_wc_blocks( $page_id ) ) {
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
