<?php
/**
 * Plugin Name: Jewelry UPI Store
 * Description: Manual UPI payment gateway, pending order workflow, and order emails.
 * Version: 1.3.0
 * Author: Jewelry E-commerce
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 7.0
 * Text Domain: jewelry-upi-store
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

define( 'JUS_VERSION', '1.3.0' );
define( 'JUS_PLUGIN_FILE', __FILE__ );
define( 'JUS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * HPOS compatibility.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', JUS_PLUGIN_FILE, true );
		}
	}
);

/**
 * Default UPI gateway settings.
 *
 * @return array
 */
function jus_default_gateway_settings() {
	return array(
		'enabled'      => 'yes',
		'title'        => __( 'Pay via UPI', 'jewelry-upi-store' ),
		'description'  => __( 'Pay via UPI after you place the order. UPI details appear on the next page.', 'jewelry-upi-store' ),
		'upi_id'       => '',
		'qr_image_url' => '',
		'instructions' => __( 'Pay the exact order total. Put your Order Number in the UPI payment remarks/note. We will confirm within 24 hours after verifying your payment.', 'jewelry-upi-store' ),
	);
}

/**
 * Ensure UPI gateway settings exist and stay enabled.
 */
function jus_ensure_gateway_settings() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$settings = get_option( 'woocommerce_jus_manual_upi_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	$settings = wp_parse_args( $settings, jus_default_gateway_settings() );
	if ( empty( $settings['enabled'] ) || 'yes' !== $settings['enabled'] ) {
		$settings['enabled'] = 'yes';
	}

	update_option( 'woocommerce_jus_manual_upi_settings', $settings );
}

/**
 * Enable UPI gateway defaults on plugin activation.
 */
function jus_activate_plugin() {
	jus_ensure_gateway_settings();
	require_once JUS_PLUGIN_DIR . 'includes/class-jus-mail.php';
	require_once JUS_PLUGIN_DIR . 'includes/class-jus-notifications.php';
	JUS_Mail::apply_from_settings();
	JUS_Notifications::apply_store_email_settings();
	update_option( JUS_Notifications::OPTION_APPLIED, JUS_VERSION, false );
}
register_activation_hook( JUS_PLUGIN_FILE, 'jus_activate_plugin' );

/**
 * Admin notice if WooCommerce is missing.
 */
function jus_wc_missing_notice() {
	echo '<div class="error"><p><strong>Jewelry UPI Store</strong> requires <a href="' . esc_url( admin_url( 'plugin-install.php?s=woocommerce' ) ) . '">WooCommerce</a>.</p></div>';
}

/**
 * Load plugin after WooCommerce core is ready (classic checkout only).
 */
function jus_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Payment_Gateway' ) ) {
		add_action( 'admin_notices', 'jus_wc_missing_notice' );
		return;
	}

	jus_ensure_gateway_settings();

	require_once JUS_PLUGIN_DIR . 'includes/class-jus-mail.php';
		require_once JUS_PLUGIN_DIR . 'includes/class-jus-notifications.php';
		require_once JUS_PLUGIN_DIR . 'includes/class-jus-checkout.php';
		require_once JUS_PLUGIN_DIR . 'includes/class-jus-checkout-reliability.php';
		require_once JUS_PLUGIN_DIR . 'includes/class-jus-orders.php';
		require_once JUS_PLUGIN_DIR . 'includes/class-jus-emails.php';
		require_once JUS_PLUGIN_DIR . 'includes/class-jus-accounts.php';
		require_once JUS_PLUGIN_DIR . 'includes/class-jus-payment-tracking.php';

		JUS_Mail::init();
		JUS_Mail::apply_from_settings();
		JUS_Notifications::init();
		JUS_Checkout::init();
		JUS_Checkout_Reliability::init();
		JUS_Orders::init();
		JUS_Emails::init();
		JUS_Accounts::init();
		JUS_Payment_Tracking::init();

	add_filter( 'woocommerce_payment_gateways', 'jus_register_gateway' );
	add_filter( 'woocommerce_available_payment_gateways', 'jus_only_upi_gateway' );
}
add_action( 'woocommerce_loaded', 'jus_bootstrap', 20 );

/**
 * Permanently convert the WooCommerce checkout page from blocks to classic shortcode.
 * Runs once on init and saves the classic shortcode directly to the DB so blocks
 * rendering never interferes again. This is what WC admin "Switch to classic" does.
 */
function jus_convert_checkout_to_classic() {
	// Never run during AJAX — wp_update_post during AJAX breaks update_order_review.
	if ( wp_doing_ajax() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return;
	}
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return;
	}
	if ( get_option( 'jus_checkout_converted_v2' ) ) {
		return;
	}

	$page_id = wc_get_page_id( 'checkout' );
	if ( ! $page_id || $page_id < 0 ) {
		return;
	}

	$page = get_post( $page_id );
	if ( ! $page ) {
		return;
	}

	if ( strpos( $page->post_content, 'wp:woocommerce/checkout' ) !== false
		|| strpos( $page->post_content, 'wp:woocommerce/cart' ) !== false
		|| '' === trim( $page->post_content ) ) {

		wp_update_post( array(
			'ID'           => $page_id,
			'post_content' => '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
		) );
	}

	update_option( 'jus_checkout_converted_v2', true );
}
add_action( 'init', 'jus_convert_checkout_to_classic', 1 );

/**
 * Disable WooCommerce checkout blocks feature flag.
 */
add_filter( 'woocommerce_feature_cart_checkout_blocks_enabled', '__return_false' );

/**
 * Disable WooCommerce shipping requirement entirely.
 * The store handles delivery separately and collects payment via UPI.
 * Without this, WooCommerce's update_order_review AJAX hangs looking
 * for shipping methods that were never configured, keeping a spinner overlay
 * on the checkout page permanently.
 */
add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );

/**
 * Register gateway class name (loads gateway file only when WC asks for it).
 *
 * @param array $gateways Gateways.
 * @return array
 */
function jus_register_gateway( $gateways ) {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return $gateways;
	}

	if ( ! class_exists( 'JUS_Gateway', false ) ) {
		require_once JUS_PLUGIN_DIR . 'includes/class-jus-gateway.php';
	}

	if ( class_exists( 'JUS_Gateway', false ) ) {
		$gateways[] = 'JUS_Gateway';
	}

	return $gateways;
}

/**
 * UPI only on storefront checkout.
 *
 * @param array $gateways Gateways.
 * @return array
 */
function jus_only_upi_gateway( $gateways ) {
	if ( ! is_array( $gateways ) ) {
		return $gateways;
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $gateways;
	}
	if ( isset( $gateways['jus_manual_upi'] ) ) {
		return array( 'jus_manual_upi' => $gateways['jus_manual_upi'] );
	}
	return $gateways;
}

/**
 * Plugin action links.
 *
 * @param array $links Links.
 * @return array
 */
function jus_plugin_links( $links ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return $links;
	}
	$settings = array(
		'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=jus_manual_upi' ) ) . '">' . esc_html__( 'UPI Settings', 'jewelry-upi-store' ) . '</a>',
	);
	return array_merge( $settings, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( JUS_PLUGIN_FILE ), 'jus_plugin_links' );

/**
 * Admin notice when UPI gateway is missing or disabled.
 */
function jus_admin_gateway_notice() {
	if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	if ( ! function_exists( 'WC' ) ) {
		return;
	}

	$wc = WC();
	if ( ! $wc || ! isset( $wc->payment_gateways ) || ! is_object( $wc->payment_gateways ) ) {
		return;
	}

	$gateways = $wc->payment_gateways()->payment_gateways();
	if ( isset( $gateways['jus_manual_upi'] ) && 'yes' === $gateways['jus_manual_upi']->enabled ) {
		return;
	}

	$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=jus_manual_upi' );
	echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Jewelry UPI Store:', 'jewelry-upi-store' ) . '</strong> ';
	echo esc_html__( 'UPI payment is not enabled. Customers cannot checkout until you enable Manual UPI.', 'jewelry-upi-store' );
	echo ' <a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Open UPI settings', 'jewelry-upi-store' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'jus_admin_gateway_notice' );
