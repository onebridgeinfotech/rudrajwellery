<?php
/**
 * Promo codes (WooCommerce coupons): create samples, labels, cart/checkout UI.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default promo codes for the jewelry store.
 *
 * @return array<int, array<string, mixed>>
 */
function jwellery_get_default_coupons() {
	return array(
		array(
			'code'        => 'WELCOME10',
			'type'        => 'percent',
			'amount'      => 10,
			'description' => '10% off your order (welcome offer)',
			'min_spend'   => 399,
		),
		array(
			'code'        => 'FLAT50',
			'type'        => 'fixed_cart',
			'amount'      => 50,
			'description' => 'Flat ₹50 off',
			'min_spend'   => 499,
		),
		array(
			'code'        => 'SALE15',
			'type'        => 'percent',
			'amount'      => 15,
			'description' => '15% off (festive sale)',
			'min_spend'   => 999,
		),
	);
}

/**
 * Create or update demo coupon by code.
 *
 * @param array<string, mixed> $data Coupon data.
 * @return int Coupon post ID.
 */
function jwellery_upsert_coupon( $data ) {
	if ( ! class_exists( 'WC_Coupon' ) ) {
		return 0;
	}

	$code = isset( $data['code'] ) ? wc_format_coupon_code( $data['code'] ) : '';
	if ( ! $code ) {
		return 0;
	}

	$existing_id = wc_get_coupon_id_by_code( $code );
	$coupon      = $existing_id ? new WC_Coupon( $existing_id ) : new WC_Coupon();

	$coupon->set_code( $code );
	$coupon->set_description( isset( $data['description'] ) ? $data['description'] : '' );
	$coupon->set_discount_type( isset( $data['type'] ) ? $data['type'] : 'percent' );
	$coupon->set_amount( isset( $data['amount'] ) ? (float) $data['amount'] : 0 );
	$coupon->set_individual_use( false );
	$coupon->set_usage_limit( 0 );
	$coupon->set_usage_limit_per_user( 0 );
	$coupon->set_free_shipping( false );

	if ( ! empty( $data['min_spend'] ) ) {
		$coupon->set_minimum_amount( (string) $data['min_spend'] );
	}

	$coupon->save();
	return (int) $coupon->get_id();
}

/**
 * Create all default promo codes.
 *
 * @return int Number created (new only).
 */
function jwellery_create_default_coupons() {
	$created = 0;
	foreach ( jwellery_get_default_coupons() as $row ) {
		$code = wc_format_coupon_code( $row['code'] );
		if ( wc_get_coupon_id_by_code( $code ) ) {
			jwellery_upsert_coupon( $row );
			continue;
		}
		if ( jwellery_upsert_coupon( $row ) ) {
			++$created;
		}
	}
	return $created;
}

/**
 * Ensure coupons stay enabled.
 *
 * @param bool $enabled Enabled.
 * @return bool
 */
function jwellery_coupons_enabled( $enabled ) {
	return true;
}
add_filter( 'woocommerce_coupons_enabled', 'jwellery_coupons_enabled' );

/**
 * Friendlier promo labels (classic cart/checkout).
 *
 * @param string $message Message.
 * @return string
 */
function jwellery_checkout_coupon_message( $message ) {
	return __( 'Have a promo code? Click here to enter it', 'jwellery-jewelry' );
}
add_filter( 'woocommerce_checkout_coupon_message', 'jwellery_checkout_coupon_message' );

/**
 * Block cart/checkout strings (WooCommerce Blocks).
 *
 * @param string $translated Translated.
 * @param string $text       Text.
 * @param string $domain     Domain.
 * @return string
 */
function jwellery_coupon_gettext( $translated, $text, $domain ) {
	if ( 'woocommerce' !== $domain ) {
		return $translated;
	}

	$map = array(
		'Add coupons'  => __( 'Apply promo code', 'jwellery-jewelry' ),
		'Enter code'   => __( 'Enter promo code', 'jwellery-jewelry' ),
		'Coupon code'  => __( 'Promo code', 'jwellery-jewelry' ),
		'Apply coupon' => __( 'Apply promo', 'jwellery-jewelry' ),
	);

	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}
add_filter( 'gettext', 'jwellery_coupon_gettext', 10, 3 );

/**
 * Ensure coupons enabled and demo codes exist (once per theme version).
 */
function jwellery_bootstrap_promo_codes() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	update_option( 'woocommerce_enable_coupons', 'yes' );

	$boot_version = get_option( 'jwellery_promo_bootstrapped', '' );
	if ( JWELLERY_THEME_VERSION === $boot_version ) {
		return;
	}

	jwellery_create_default_coupons();
	update_option( 'jwellery_promo_bootstrapped', JWELLERY_THEME_VERSION );
}
add_action( 'woocommerce_init', 'jwellery_bootstrap_promo_codes', 5 );

/**
 * Hint above coupon fields on cart and checkout.
 */
function jwellery_promo_code_hint() {
	if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
		return;
	}
	if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) {
		return;
	}

	echo '<div class="jwellery-promo-hint">';
	echo '<strong>' . esc_html__( 'Promo code', 'jwellery-jewelry' ) . '</strong> — ';
	echo esc_html__( 'Try WELCOME10 (10% off), FLAT50 (₹50 off), or SALE15 (15% off).', 'jwellery-jewelry' );
	echo '</div>';
}
add_action( 'woocommerce_before_cart', 'jwellery_promo_code_hint', 5 );
add_action( 'woocommerce_before_checkout_form', 'jwellery_promo_code_hint', 4 );

/**
 * Expand hidden checkout coupon form (classic checkout).
 */
function jwellery_promo_checkout_script() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}
	if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
		return;
	}

	wp_add_inline_script(
		'jwellery-theme',
		"(function(){function showPromo(){var t=document.querySelector('.woocommerce-form-coupon-toggle');var f=document.querySelector('form.checkout_coupon');if(t){t.style.display='none';}if(f){f.style.display='block';}}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',showPromo);}else{showPromo();}})();"
	);
}
add_action( 'wp_enqueue_scripts', 'jwellery_promo_checkout_script', 30 );
