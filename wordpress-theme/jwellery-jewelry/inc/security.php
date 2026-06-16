<?php
/**
 * Security hardening — headers, auth limits, enumeration blocks.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Client IP for rate limits (respects common proxy headers).
 *
 * @return string
 */
function jwellery_security_client_ip() {
	$keys = array(
		'HTTP_CF_CONNECTING_IP',
		'HTTP_X_FORWARDED_FOR',
		'REMOTE_ADDR',
	);

	foreach ( $keys as $key ) {
		if ( empty( $_SERVER[ $key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			continue;
		}
		$raw = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( 'HTTP_X_FORWARDED_FOR' === $key && false !== strpos( $raw, ',' ) ) {
			$parts = explode( ',', $raw );
			$raw   = trim( $parts[0] );
		}
		if ( filter_var( $raw, FILTER_VALIDATE_IP ) ) {
			return $raw;
		}
	}

	return '0.0.0.0';
}

/**
 * HTTP security headers (front-end only).
 */
function jwellery_security_headers() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || headers_sent() ) {
		return;
	}

	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
	header( 'X-XSS-Protection: 1; mode=block' );
}
add_action( 'send_headers', 'jwellery_security_headers', 1 );

/**
 * Remove WordPress version fingerprint.
 */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/**
 * Disable XML-RPC (common brute-force / exploit vector).
 */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_xmlrpc_server_class', '__return_false' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );

/**
 * Block ?author= user ID enumeration.
 */
function jwellery_security_block_author_enum() {
	if ( is_admin() ) {
		return;
	}

	if ( isset( $_GET['author'] ) && is_numeric( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'jwellery_security_block_author_enum', 0 );

/**
 * Login rate limit — max 5 failures per IP+username per 15 minutes.
 *
 * @param WP_User|WP_Error|null $user     User or error.
 * @param string                $username Username.
 * @param string                $password Password.
 * @return WP_User|WP_Error|null
 */
function jwellery_security_login_rate_limit( $user, $username, $password ) {
	unset( $password );

	if ( empty( $username ) ) {
		return $user;
	}

	$key      = 'jwellery_login_fail_' . md5( jwellery_security_client_ip() . strtolower( $username ) );
	$attempts = (int) get_transient( $key );

	if ( $attempts >= 5 ) {
		return new WP_Error(
			'jwellery_too_many_logins',
			__( 'Too many failed login attempts. Please wait 15 minutes and try again.', 'jwellery-jewelry' )
		);
	}

	return $user;
}
add_filter( 'authenticate', 'jwellery_security_login_rate_limit', 30, 3 );

/**
 * Record failed login.
 *
 * @param string $username Username.
 */
function jwellery_security_login_failed( $username ) {
	if ( empty( $username ) ) {
		return;
	}

	$key      = 'jwellery_login_fail_' . md5( jwellery_security_client_ip() . strtolower( $username ) );
	$attempts = (int) get_transient( $key );
	set_transient( $key, $attempts + 1, 15 * MINUTE_IN_SECONDS );
}
add_action( 'wp_login_failed', 'jwellery_security_login_failed' );

/**
 * Clear login counter on success.
 *
 * @param string  $user_login Username.
 * @param WP_User $user       User.
 */
function jwellery_security_login_success( $user_login, $user ) {
	unset( $user );
	$key = 'jwellery_login_fail_' . md5( jwellery_security_client_ip() . strtolower( $user_login ) );
	delete_transient( $key );
}
add_action( 'wp_login', 'jwellery_security_login_success', 10, 2 );

/**
 * Restrict public REST user listing (username discovery).
 *
 * @param array $endpoints Endpoints.
 * @return array
 */
function jwellery_security_restrict_rest_users( $endpoints ) {
	if ( current_user_can( 'list_users' ) ) {
		return $endpoints;
	}

	unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	return $endpoints;
}
add_filter( 'rest_endpoints', 'jwellery_security_restrict_rest_users', 99 );

/**
 * Generic action rate limit helper.
 *
 * @param string $action Action key.
 * @param int    $max    Max attempts.
 * @param int    $window Window seconds.
 * @return bool True if allowed.
 */
function jwellery_security_rate_limit_allow( $action, $max = 5, $window = 900 ) {
	$key      = 'jwellery_rl_' . $action . '_' . md5( jwellery_security_client_ip() );
	$attempts = (int) get_transient( $key );

	if ( $attempts >= $max ) {
		return false;
	}

	set_transient( $key, $attempts + 1, $window );
	return true;
}

/**
 * Strip dangerous upload types from theme context (defense in depth).
 *
 * @param array $mimes Mime types.
 * @return array
 */
function jwellery_security_upload_mimes( $mimes ) {
	unset( $mimes['svg'], $mimes['svgz'] );
	return $mimes;
}
add_filter( 'upload_mimes', 'jwellery_security_upload_mimes', 99 );

/**
 * Admin notice: recommend wp-config hardening constants.
 */
function jwellery_security_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT && defined( 'WP_DEBUG' ) && ! WP_DEBUG ) {
		return;
	}

	echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Security tip', 'jwellery-jewelry' ) . ':</strong> ';
	echo esc_html__( 'Add DISALLOW_FILE_EDIT, disable WP_DEBUG on production, and use Wordfence or similar. See docs/SECURITY.md in your theme package.', 'jwellery-jewelry' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'jwellery_security_admin_notice' );
