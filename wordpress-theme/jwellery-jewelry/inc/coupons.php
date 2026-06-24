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
 * Human-readable label for a coupon (e.g. FEST10 (10% off)).
 *
 * @param WC_Coupon $coupon Coupon.
 * @return string
 */
function jwellery_format_coupon_hint_label( $coupon ) {
	if ( ! $coupon instanceof WC_Coupon ) {
		return '';
	}

	$code   = $coupon->get_code();
	$type   = $coupon->get_discount_type();
	$amount = (float) $coupon->get_amount();

	if ( 'percent' === $type || 'percent_product' === $type ) {
		/* translators: 1: coupon code, 2: percent amount */
		return sprintf( __( '%1$s (%2$s%% off)', 'jwellery-jewelry' ), $code, wc_format_decimal( $amount, 0 ) );
	}

	if ( 'fixed_cart' === $type ) {
		/* translators: 1: coupon code, 2: discount amount */
		return sprintf( __( '%1$s (₹%2$s off)', 'jwellery-jewelry' ), $code, wc_format_decimal( $amount, 0 ) );
	}

	if ( 'fixed_product' === $type ) {
		/* translators: 1: coupon code, 2: discount amount */
		return sprintf( __( '%1$s (₹%2$s off per item)', 'jwellery-jewelry' ), $code, wc_format_decimal( $amount, 0 ) );
	}

	return $code;
}

/**
 * Published, non-expired coupons for cart/checkout hints.
 *
 * @return WC_Coupon[]
 */
function jwellery_get_active_public_coupons() {
	if ( ! class_exists( 'WC_Coupon' ) ) {
		return array();
	}

	$posts = get_posts(
		array(
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);

	$coupons = array();
	$now     = time();

	foreach ( $posts as $post_id ) {
		$coupon = new WC_Coupon( (int) $post_id );
		if ( ! $coupon->get_id() ) {
			continue;
		}

		$expires = $coupon->get_date_expires();
		if ( $expires && $expires->getTimestamp() < $now ) {
			continue;
		}

		$coupons[] = $coupon;
	}

	return $coupons;
}

/**
 * Promo hint text built from WooCommerce coupons in admin.
 *
 * @return string
 */
function jwellery_get_promo_hint_text() {
	$cache_key = 'jwellery_promo_hint_text';
	$cached    = get_transient( $cache_key );
	if ( is_string( $cached ) && '' !== $cached ) {
		return $cached;
	}

	$labels = array();
	foreach ( jwellery_get_active_public_coupons() as $coupon ) {
		$label = jwellery_format_coupon_hint_label( $coupon );
		if ( $label ) {
			$labels[] = $label;
		}
	}

	if ( empty( $labels ) ) {
		$text = __( 'Enter your promo code below.', 'jwellery-jewelry' );
	} else {
		/* translators: %s: comma-separated coupon labels */
		$text = sprintf( __( 'Try %s.', 'jwellery-jewelry' ), implode( ', ', $labels ) );
	}

	set_transient( $cache_key, $text, HOUR_IN_SECONDS );
	return $text;
}

/**
 * Clear promo hint cache when coupons change in admin.
 *
 * @param int $post_id Post ID.
 */
function jwellery_clear_promo_hint_cache( $post_id = 0 ) {
	if ( $post_id && 'shop_coupon' !== get_post_type( $post_id ) ) {
		return;
	}
	delete_transient( 'jwellery_promo_hint_text' );
}
add_action( 'save_post_shop_coupon', 'jwellery_clear_promo_hint_cache' );
add_action( 'deleted_post', 'jwellery_clear_promo_hint_cache' );
add_action( 'trashed_post', 'jwellery_clear_promo_hint_cache' );
add_action( 'untrashed_post', 'jwellery_clear_promo_hint_cache' );

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
	echo esc_html( jwellery_get_promo_hint_text() );
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
		"(function(){function showPromo(){var t=document.querySelector('.woocommerce-form-coupon-toggle');var f=document.querySelector('form.checkout_coupon');if(t){t.style.display='none';}if(f){f.style.display='flex';f.querySelectorAll('p.form-row-first,p.form-row-last').forEach(function(row){row.style.display='';});var input=f.querySelector('input[name=\"coupon_code\"]');if(input){input.style.display='';input.removeAttribute('hidden');}}}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',showPromo);}else{showPromo();}})();"
	);
}
add_action( 'wp_enqueue_scripts', 'jwellery_promo_checkout_script', 30 );
