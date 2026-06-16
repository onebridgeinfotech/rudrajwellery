<?php
/**
 * Emergency site recovery — upload to wp-content/mu-plugins/
 *
 * Fixes:
 * 1. Removes broken Jewelry UPI plugin from active list (v1.0.5 and older only)
 * 2. Stops WooCommerce from crashing when UPI gateway class is missing
 * 3. Disables theme checkout auto-fix hooks that can fatal without the plugin
 *
 * DELETE this file after the site loads and you activate plugin v1.0.6+.
 * If you cannot activate the plugin, this file is probably still present — delete it first.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block only broken UPI plugin versions. v1.0.6+ is allowed to stay active.
 *
 * @return bool True when the UPI plugin should be stripped from active_plugins.
 */
function jwellery_emergency_should_block_upi_plugin() {
	$plugin_file = WP_PLUGIN_DIR . '/jewelry-upi-store/jewelry-upi-store.php';

	if ( ! is_readable( $plugin_file ) ) {
		return true;
	}

	if ( ! function_exists( 'get_file_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$data = get_file_data(
		$plugin_file,
		array(
			'Version'     => 'Version',
			'Plugin Name' => 'Plugin Name',
		)
	);

	if ( empty( $data['Plugin Name'] ) ) {
		return true;
	}

	return version_compare( (string) $data['Version'], '1.0.6', '<' );
}

/**
 * Strip broken UPI plugin from active plugins list.
 *
 * @param array $plugins Active plugins.
 * @return array
 */
function jwellery_emergency_filter_plugins( $plugins ) {
	if ( ! is_array( $plugins ) || ! jwellery_emergency_should_block_upi_plugin() ) {
		return $plugins;
	}

	$remove = array(
		'jewelry-upi-store/jewelry-upi-store.php',
		'jewelry-upi-store-off/jewelry-upi-store.php',
	);

	return array_values(
		array_filter(
			$plugins,
			static function ( $plugin ) use ( $remove ) {
				return ! in_array( $plugin, $remove, true );
			}
		)
	);
}

add_filter( 'option_active_plugins', 'jwellery_emergency_filter_plugins' );
add_filter( 'pre_update_option_active_plugins', 'jwellery_emergency_filter_plugins' );

/**
 * Prevent WooCommerce from loading a missing UPI gateway class.
 *
 * @param array $gateways Gateway class names.
 * @return array
 */
function jwellery_emergency_safe_gateways( $gateways ) {
	if ( ! is_array( $gateways ) ) {
		return $gateways;
	}

	return array_values(
		array_filter(
			$gateways,
			static function ( $gateway ) {
				if ( 'JUS_Gateway' !== $gateway ) {
					return true;
				}
				return class_exists( 'JUS_Gateway', false );
			}
		)
	);
}
add_filter( 'woocommerce_payment_gateways', 'jwellery_emergency_safe_gateways', 999 );

/**
 * Remove theme checkout auto-fix that may call WC before ready.
 */
add_action(
	'after_setup_theme',
	static function () {
		remove_action( 'admin_init', 'jwellery_auto_fix_checkout_payment', 5 );
		remove_action( 'init', 'jwellery_auto_fix_checkout_frontend', 12 );
	},
	999
);
