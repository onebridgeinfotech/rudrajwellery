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

/**
 * Purge LiteSpeed / Hostinger page cache when theme version changes after deploy.
 */
function jwellery_purge_hosting_cache() {
	if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
		LiteSpeed_Cache_API::purge_all();
		if ( method_exists( 'LiteSpeed_Cache_API', 'purge' ) ) {
			LiteSpeed_Cache_API::purge( home_url( '/' ) );
			LiteSpeed_Cache_API::purge( home_url( '/shop/' ) );
		}
	}
	if ( function_exists( 'litespeed_purge_all' ) ) {
		litespeed_purge_all();
	}
	do_action( 'litespeed_purge_all' );
	if ( function_exists( 'litespeed_purge_url' ) ) {
		litespeed_purge_url( home_url( '/' ) );
	}
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
}

/**
 * Auto-purge cache once per theme version so FTP deploys show on the live site.
 */
function jwellery_maybe_purge_cache_on_version_bump() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	$stored = get_option( 'jwellery_theme_deploy_version', '' );
	if ( $stored === JWELLERY_THEME_VERSION ) {
		return;
	}
	jwellery_purge_hosting_cache();
	update_option( 'jwellery_theme_deploy_version', JWELLERY_THEME_VERSION, false );
}
add_action( 'init', 'jwellery_maybe_purge_cache_on_version_bump', 1 );
