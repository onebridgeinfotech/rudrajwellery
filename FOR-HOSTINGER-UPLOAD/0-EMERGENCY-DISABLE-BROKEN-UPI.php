<?php
/**
 * Emergency: disable Jewelry UPI Store if it crashes the site.
 *
 * Hostinger File Manager:
 * 1. Open public_html/wp-content/
 * 2. Create folder mu-plugins (if missing)
 * 3. Upload THIS file into mu-plugins/
 * 4. Site should load again
 *
 * DELETE this file after you upload and activate plugin v1.0.6+.
 * While this file exists, WordPress cannot activate Jewelry UPI Store.
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return bool True when the UPI plugin should be stripped from active_plugins.
 */
function jus_emergency_should_block_upi_plugin() {
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
 * @param array $plugins Active plugins.
 * @return array
 */
function jus_emergency_filter_plugins( $plugins ) {
	if ( ! is_array( $plugins ) || ! jus_emergency_should_block_upi_plugin() ) {
		return $plugins;
	}

	return array_values(
		array_filter(
			$plugins,
			static function ( $plugin ) {
				return 'jewelry-upi-store/jewelry-upi-store.php' !== $plugin;
			}
		)
	);
}

add_filter( 'option_active_plugins', 'jus_emergency_filter_plugins' );
add_filter( 'pre_update_option_active_plugins', 'jus_emergency_filter_plugins' );
