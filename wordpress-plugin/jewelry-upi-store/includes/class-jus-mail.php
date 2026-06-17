<?php
/**
 * Reliable transactional email (password reset, wp_mail defaults).
 *
 * Hostinger PHP mail() often fails when From is a Gmail address. Use a
 * domain-based noreply@ address and optional SMTP constants in wp-config.php.
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JUS_Mail
 */
class JUS_Mail {

	const OPTION_FROM = 'jus_transactional_from_email';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'wp_mail_from', array( __CLASS__, 'filter_from_address' ), 100 );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'filter_from_name' ), 100 );
		add_filter( 'retrieve_password_notification_email', array( __CLASS__, 'password_reset_email' ), 20, 4 );
		add_filter( 'retrieve_password_title', array( __CLASS__, 'password_reset_title' ), 20, 3 );
		add_filter( 'retrieve_password_message', array( __CLASS__, 'password_reset_message_legacy' ), 20, 4 );
		add_action( 'phpmailer_init', array( __CLASS__, 'configure_smtp' ), 20 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'log_mail_failure' ), 10, 1 );
		add_action( 'admin_notices', array( __CLASS__, 'smtp_admin_notice' ) );
	}

	/**
	 * Domain-based sender (not Gmail) for better deliverability on shared hosting.
	 *
	 * @return string
	 */
	public static function transactional_from_email() {
		$saved = get_option( self::OPTION_FROM, '' );
		if ( is_string( $saved ) && is_email( $saved ) ) {
			return $saved;
		}

		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$host = is_string( $host ) ? strtolower( $host ) : '';
		$host = preg_replace( '/^www\./', '', $host );

		if ( $host && false === strpos( $host, 'localhost' ) ) {
			return 'noreply@' . $host;
		}

		return 'noreply@example.com';
	}

	/**
	 * Persist domain sender after deploy.
	 */
	public static function apply_from_settings() {
		update_option( self::OPTION_FROM, self::transactional_from_email(), false );
	}

	/**
	 * @param string $email From email.
	 * @return string
	 */
	public static function filter_from_address( $email ) {
		unset( $email );
		return self::transactional_from_email();
	}

	/**
	 * @param string $name From name.
	 * @return string
	 */
	public static function filter_from_name( $name ) {
		unset( $name );
		$site = get_bloginfo( 'name' );
		return $site ? $site : 'Rudra Jewellery';
	}

	/**
	 * Password reset link on WooCommerce My Account when available.
	 *
	 * @param WP_User $user User.
	 * @param string  $key  Reset key.
	 * @return string
	 */
	public static function password_reset_url( $user, $key ) {
		if ( function_exists( 'wc_get_endpoint_url' ) && function_exists( 'wc_get_page_permalink' ) ) {
			$base = wc_get_page_permalink( 'myaccount' );
			if ( $base ) {
				return add_query_arg(
					array(
						'key'   => $key,
						'id'    => $user->ID,
						'login' => rawurlencode( $user->user_login ),
					),
					wc_get_endpoint_url( 'lost-password', '', $base )
				);
			}
		}

		return network_site_url(
			'wp-login.php?action=rp&key=' . rawurlencode( $key ) . '&login=' . rawurlencode( $user->user_login ),
			'login'
		);
	}

	/**
	 * WP 6.0+ password reset email array.
	 *
	 * @param array   $defaults    Email args.
	 * @param string  $key         Key.
	 * @param string  $user_login  Login.
	 * @param WP_User $user_data   User.
	 * @return array
	 */
	public static function password_reset_email( $defaults, $key, $user_login, $user_data ) {
		unset( $user_login );

		if ( ! $user_data instanceof WP_User ) {
			return $defaults;
		}

		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$reset_url = self::password_reset_url( $user_data, $key );

		if ( ! is_array( $defaults ) ) {
			$defaults = array();
		}

		$defaults['subject'] = sprintf(
			/* translators: %s: site name */
			__( '[%s] Reset your password', 'jewelry-upi-store' ),
			$site_name
		);

		$defaults['message'] = sprintf(
			/* translators: 1: site name, 2: username, 3: reset URL */
			__(
				"Hello,\n\nSomeone requested a password reset for your account on %1\$s.\n\nUsername: %2\$s\n\nIf this was you, open this link to choose a new password:\n%3\$s\n\nIf you did not request this, you can ignore this email.\n\n— %1\$s",
				'jewelry-upi-store'
			),
			$site_name,
			$user_data->user_login,
			$reset_url
		);

		$defaults['headers'] = array( 'Content-Type: text/plain; charset=UTF-8' );

		return $defaults;
	}

	/**
	 * Legacy password reset title (older WP / some plugins).
	 *
	 * @param string  $title      Title.
	 * @param string  $user_login Login.
	 * @param WP_User $user_data  User.
	 * @return string
	 */
	public static function password_reset_title( $title, $user_login, $user_data ) {
		unset( $user_login, $user_data );
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		return sprintf(
			/* translators: %s: site name */
			__( '[%s] Reset your password', 'jewelry-upi-store' ),
			$site_name
		);
	}

	/**
	 * Legacy password reset message filter.
	 *
	 * @param string  $message    Message.
	 * @param string  $key        Key.
	 * @param string  $user_login Login.
	 * @param WP_User $user_data  User.
	 * @return string
	 */
	public static function password_reset_message_legacy( $message, $key, $user_login, $user_data ) {
		unset( $user_login );

		if ( ! $user_data instanceof WP_User ) {
			return $message;
		}

		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$reset_url = self::password_reset_url( $user_data, $key );

		return sprintf(
			/* translators: 1: site name, 2: username, 3: reset URL */
			__(
				"Hello,\n\nSomeone requested a password reset for your account on %1\$s.\n\nUsername: %2\$s\n\nIf this was you, open this link to choose a new password:\n%3\$s\n\nIf you did not request this, you can ignore this email.\n\n— %1\$s",
				'jewelry-upi-store'
			),
			$site_name,
			$user_data->user_login,
			$reset_url
		);
	}

	/**
	 * Optional SMTP via wp-config.php constants (no plugin required).
	 *
	 * define( 'JUS_SMTP_HOST', 'smtp.hostinger.com' );
	 * define( 'JUS_SMTP_PORT', 465 );
	 * define( 'JUS_SMTP_USER', 'noreply@yourdomain.com' );
	 * define( 'JUS_SMTP_PASS', 'your-mailbox-password' );
	 * define( 'JUS_SMTP_SECURE', 'ssl' ); // ssl or tls
	 *
	 * @param PHPMailer $phpmailer Mailer.
	 */
	public static function configure_smtp( $phpmailer ) {
		if ( ! defined( 'JUS_SMTP_HOST' ) || ! JUS_SMTP_HOST ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = JUS_SMTP_HOST;
		$phpmailer->Port       = defined( 'JUS_SMTP_PORT' ) ? (int) JUS_SMTP_PORT : 465;
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Username   = defined( 'JUS_SMTP_USER' ) ? JUS_SMTP_USER : self::transactional_from_email();
		$phpmailer->Password   = defined( 'JUS_SMTP_PASS' ) ? JUS_SMTP_PASS : '';
		$phpmailer->SMTPSecure = defined( 'JUS_SMTP_SECURE' ) ? JUS_SMTP_SECURE : 'ssl';
		$phpmailer->From       = self::transactional_from_email();
		$phpmailer->FromName   = self::filter_from_name( '' );
	}

	/**
	 * @param WP_Error $error Error.
	 */
	public static function log_mail_failure( $error ) {
		if ( ! is_wp_error( $error ) ) {
			return;
		}
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( 'JUS wp_mail failed: ' . $error->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Recommend SMTP when WP Mail SMTP is not active.
	 */
	public static function smtp_admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( defined( 'JUS_SMTP_HOST' ) && JUS_SMTP_HOST ) {
			return;
		}
		if ( class_exists( 'WPMailSMTP\Core' ) || function_exists( 'wp_mail_smtp' ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Jewelry UPI Store — email delivery', 'jewelry-upi-store' ) . '</strong> ';
		echo esc_html__( 'Password reset and order emails need SMTP on Hostinger. Install WP Mail SMTP and connect your Hostinger mailbox (e.g. noreply@rudrajewellery.co.in), or add JUS_SMTP_* constants in wp-config.php.', 'jewelry-upi-store' );
		echo '</p></div>';
	}
}
