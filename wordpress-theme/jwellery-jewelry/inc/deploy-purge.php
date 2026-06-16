<?php
/**
 * Deploy-time cache purge endpoint (admin-ajax).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * One-time deploy purge via admin-ajax (not page-cached).
 */
function jwellery_ajax_deploy_purge() {
	$key_file = WP_CONTENT_DIR . '/uploads/jwellery-deploy-purge.key';
	if ( ! file_exists( $key_file ) ) {
		wp_send_json_error( 'no key', 403 );
	}
	$expected = trim( (string) file_get_contents( $key_file ) );
	$given    = isset( $_REQUEST['key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ) : '';
	if ( ! $expected || ! hash_equals( $expected, $given ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}
	@unlink( $key_file );
	if ( function_exists( 'jwellery_purge_hosting_cache' ) ) {
		jwellery_purge_hosting_cache();
	}
	if ( defined( 'JWELLERY_THEME_VERSION' ) ) {
		update_option( 'jwellery_theme_deploy_version', JWELLERY_THEME_VERSION, false );
	}
	wp_send_json_success( 'purged' );
}
add_action( 'wp_ajax_nopriv_jwellery_deploy_purge', 'jwellery_ajax_deploy_purge' );
add_action( 'wp_ajax_jwellery_deploy_purge', 'jwellery_ajax_deploy_purge' );
