<?php
/**
 * WooCommerce theme support (safe if WooCommerce is not installed yet).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register WooCommerce theme support only when plugin is active.
 */
function jwellery_woocommerce_setup() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 400,
			'single_image_width'    => 600,
			'product_grid'          => array(
				'default_rows'    => 4,
				'min_rows'        => 1,
				'max_rows'        => 8,
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 5,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'jwellery_woocommerce_setup' );

/**
 * Register WooCommerce hooks after WooCommerce has loaded.
 */
function jwellery_register_wc_hooks() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_filter( 'loop_shop_columns', 'jwellery_loop_columns' );
	add_filter( 'loop_shop_per_page', 'jwellery_products_per_page' );
	add_action( 'woocommerce_before_main_content', 'jwellery_woocommerce_wrapper_start', 5 );
	add_action( 'woocommerce_after_main_content', 'jwellery_woocommerce_wrapper_end', 50 );
	add_action( 'wp', 'jwellery_remove_wc_sidebar' );
	add_filter( 'woocommerce_add_to_cart_fragments', 'jwellery_cart_fragments' );
	add_action( 'woocommerce_before_shop_loop', 'jwellery_shop_toolbar_open', 19 );
	add_action( 'woocommerce_before_shop_loop', 'jwellery_shop_toolbar_close', 31 );
	add_filter( 'pre_option_woocommerce_enable_myaccount_registration', 'jwellery_force_account_registration' );
	add_filter( 'pre_option_woocommerce_enable_signup_and_login_from_checkout', 'jwellery_force_account_registration' );
	add_filter( 'wc_get_template', 'jwellery_wc_get_template', 10, 5 );
}
add_action( 'woocommerce_init', 'jwellery_register_wc_hooks' );

/**
 * Force safe WooCommerce template overrides (cart / order line items).
 *
 * @param string $template       Path.
 * @param string $template_name  Name.
 * @param array  $args           Args.
 * @param string $template_path  Path.
 * @param string $default_path   Default path.
 * @return string
 */
function jwellery_wc_get_template( $template, $template_name, $args, $template_path, $default_path ) {
	$overrides = array(
		'cart/cart-item.php',
		'order/order-details-item.php',
	);
	if ( ! in_array( $template_name, $overrides, true ) ) {
		return $template;
	}
	$custom = JWELLERY_THEME_DIR . '/woocommerce/' . $template_name;
	return is_readable( $custom ) ? $custom : $template;
}

/**
 * Always allow customer registration on My Account page.
 *
 * @param mixed $value Option value.
 * @return string
 */
function jwellery_force_account_registration( $value ) {
	return 'yes';
}

/**
 * Shop toolbar wrapper start (sort + result count).
 */
function jwellery_shop_toolbar_open() {
	if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
		return;
	}
	if ( function_exists( 'jwellery_is_main_shop_catalog' ) && jwellery_is_main_shop_catalog() ) {
		return;
	}
	echo '<div class="jwellery-shop-toolbar">';
}

/**
 * Shop toolbar wrapper end.
 */
function jwellery_shop_toolbar_close() {
	if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
		return;
	}
	if ( function_exists( 'jwellery_is_main_shop_catalog' ) && jwellery_is_main_shop_catalog() ) {
		return;
	}
	echo '</div>';
}

/**
 * Products per row.
 *
 * @return int
 */
function jwellery_loop_columns() {
	if ( function_exists( 'wp_is_mobile' ) && wp_is_mobile() ) {
		return 2;
	}
	return 4;
}

/**
 * Products per page.
 *
 * @return int
 */
function jwellery_products_per_page() {
	return 48;
}

/**
 * Wrap WooCommerce content.
 */
function jwellery_woocommerce_wrapper_start() {
	if ( function_exists( 'jwellery_is_main_shop_catalog' ) && jwellery_is_main_shop_catalog() ) {
		echo '<div class="jwellery-shop-wrap jwellery-shop-wrap--catalog">';
		return;
	}
	echo '<div class="jwellery-shop-wrap container">';
}

/**
 * Wrap WooCommerce content end.
 */
function jwellery_woocommerce_wrapper_end() {
	echo '</div>';
}

/**
 * Remove default sidebar on shop.
 */
function jwellery_remove_wc_sidebar() {
	if ( ! function_exists( 'is_shop' ) ) {
		return;
	}
	if ( is_shop() || is_product_category() || is_product_tag() ) {
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	}
}

/**
 * Cart fragment for header count.
 *
 * @param array $fragments Fragments.
 * @return array
 */
function jwellery_cart_fragments( $fragments ) {
	if ( function_exists( 'jwellery_cart_toggle_button' ) && get_theme_mod( 'jwellery_enable_cart_drawer', true ) ) {
		ob_start();
		jwellery_cart_toggle_button();
		$fragments['button.jwellery-header-cart-toggle'] = ob_get_clean();
		if ( function_exists( 'jwellery_mobile_bar_cart_icon_wrap_html' ) ) {
			$fragments['.jwellery-mobile-bar-cart .jwellery-mobile-bar-icon-wrap'] = jwellery_mobile_bar_cart_icon_wrap_html();
		}
		return $fragments;
	}
	ob_start();
	jwellery_cart_link();
	$fragments['a.jwellery-cart-link'] = ob_get_clean();
	return $fragments;
}

/**
 * Safe shop page URL.
 *
 * @return string
 */
function jwellery_get_shop_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'shop' );
		if ( $url ) {
			return $url;
		}
	}
	return home_url( '/shop/' );
}

/**
 * Output cart link HTML.
 */
function jwellery_cart_link() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$count = WC()->cart->get_cart_contents_count();
	printf(
		'<a class="jwellery-cart-link" href="%s" title="%s"><span class="cart-label">%s</span> <span class="cart-count">(%d)</span></a>',
		esc_url( wc_get_cart_url() ),
		esc_attr__( 'View cart', 'jwellery-jewelry' ),
		esc_html__( 'Cart', 'jwellery-jewelry' ),
		(int) $count
	);
}
