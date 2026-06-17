<?php
/**
 * Store notification email — orders, accounts, password resets.
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JUS_Notifications
 */
class JUS_Notifications {

	const DEFAULT_EMAIL = 'kalpanayadav503@gmail.com';
	const OPTION_EMAIL  = 'jus_store_notification_email';
	const OPTION_APPLIED = 'jus_notifications_applied_version';

	/**
	 * Former admin / store emails to replace on migrate.
	 *
	 * @return string[]
	 */
	private static function legacy_admin_emails() {
		return array(
			'udayach123@gmail.com',
		);
	}

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'pre_option_admin_email', array( __CLASS__, 'filter_admin_email' ) );
		add_action( 'init', array( __CLASS__, 'maybe_apply_settings' ), 5 );
		add_action( 'init', array( __CLASS__, 'maybe_migrate_legacy_email' ), 6 );

		add_filter( 'woocommerce_email_recipient_new_order', array( __CLASS__, 'admin_recipient' ), 20, 2 );
		add_filter( 'woocommerce_email_recipient_cancelled_order', array( __CLASS__, 'admin_recipient' ), 20, 2 );
		add_filter( 'woocommerce_email_recipient_failed_order', array( __CLASS__, 'admin_recipient' ), 20, 2 );

		add_filter( 'wp_new_user_notification_email_admin', array( __CLASS__, 'new_user_admin_email' ), 20, 3 );
		add_action( 'woocommerce_created_customer', array( __CLASS__, 'notify_new_customer' ), 20, 3 );
		add_action( 'retrieve_password_key', array( __CLASS__, 'notify_password_reset_requested' ), 20, 2 );
		add_action( 'after_password_reset', array( __CLASS__, 'notify_password_reset_done' ), 20, 2 );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'notify_order_status_change' ), 20, 4 );
	}

	/**
	 * Store owner / admin notification inbox.
	 *
	 * @return string
	 */
	public static function store_email() {
		$email = get_option( self::OPTION_EMAIL, self::DEFAULT_EMAIL );
		$email = is_string( $email ) ? sanitize_email( $email ) : '';
		if ( $email && is_email( $email ) ) {
			return $email;
		}
		return self::DEFAULT_EMAIL;
	}

	/**
	 * Force WordPress administration email to the store inbox.
	 *
	 * @param mixed $value Current option value.
	 * @return string
	 */
	public static function filter_admin_email( $value ) {
		unset( $value );
		return self::store_email();
	}

	/**
	 * Replace legacy admin email values stored in options/theme mods.
	 */
	public static function maybe_migrate_legacy_email() {
		if ( ! self::site_has_legacy_admin_email() ) {
			return;
		}
		self::apply_store_email_settings();
	}

	/**
	 * @return bool
	 */
	private static function site_has_legacy_admin_email() {
		if ( self::is_legacy_email( get_option( 'admin_email' ) ) ) {
			return true;
		}
		if ( self::is_legacy_email( get_theme_mod( 'jwellery_email' ) ) ) {
			return true;
		}
		if ( self::is_legacy_email( get_option( 'woocommerce_email_from_address' ) ) ) {
			return true;
		}

		foreach ( array( 'new_order', 'cancelled_order', 'failed_order' ) as $email_id ) {
			$settings = get_option( 'woocommerce_' . $email_id . '_settings', array() );
			if ( is_array( $settings ) && ! empty( $settings['recipient'] ) && self::is_legacy_email( $settings['recipient'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param mixed $email Email.
	 * @return bool
	 */
	private static function is_legacy_email( $email ) {
		$email = strtolower( trim( (string) $email ) );
		if ( ! $email ) {
			return false;
		}
		foreach ( self::legacy_admin_emails() as $legacy ) {
			if ( $email === strtolower( $legacy ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Apply email settings after deploy / version bump.
	 */
	public static function maybe_apply_settings() {
		if ( get_option( self::OPTION_APPLIED ) === JUS_VERSION ) {
			return;
		}
		self::apply_store_email_settings();
		update_option( self::OPTION_APPLIED, JUS_VERSION, false );
	}

	/**
	 * Set WordPress + WooCommerce notification recipients.
	 */
	public static function apply_store_email_settings() {
		$email = self::DEFAULT_EMAIL;

		update_option( 'admin_email', $email );
		update_option( self::OPTION_EMAIL, $email, false );

		$wc_admin_emails = array( 'new_order', 'cancelled_order', 'failed_order' );
		foreach ( $wc_admin_emails as $email_id ) {
			$key      = 'woocommerce_' . $email_id . '_settings';
			$settings = get_option( $key, array() );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}
			$settings['recipient'] = $email;
			$settings['enabled']   = 'yes';
			update_option( $key, $settings );
		}

		$theme_email = (string) get_theme_mod( 'jwellery_email', '' );
		if ( '' === $theme_email || self::is_legacy_email( $theme_email ) ) {
			set_theme_mod( 'jwellery_email', $email );
		}

		$info_email = (string) get_theme_mod( 'jwellery_info_email', '' );
		if ( '' === $info_email || ! is_email( $info_email ) ) {
			set_theme_mod( 'jwellery_info_email', 'info@rudrajwelelry.co.in' );
		}

		$from_address = class_exists( 'JUS_Mail' ) ? JUS_Mail::transactional_from_email() : self::DEFAULT_EMAIL;
		if ( self::is_legacy_email( $from_address ) || ! is_email( $from_address ) ) {
			update_option( 'woocommerce_email_from_address', class_exists( 'JUS_Mail' ) ? JUS_Mail::transactional_from_email() : 'noreply@' . wp_parse_url( home_url(), PHP_URL_HOST ) );
		} elseif ( self::is_legacy_email( (string) get_option( 'woocommerce_email_from_address', '' ) ) ) {
			update_option( 'woocommerce_email_from_address', $from_address );
		}

		$from_name = get_bloginfo( 'name' );
		if ( $from_name ) {
			update_option( 'woocommerce_email_from_name', $from_name );
		}
	}

	/**
	 * Route WooCommerce admin order emails to the store inbox.
	 *
	 * @param string   $recipient Recipient.
	 * @param WC_Order $order     Order.
	 * @return string
	 */
	public static function admin_recipient( $recipient, $order ) {
		unset( $order );
		return self::store_email();
	}

	/**
	 * New WP user — admin notification recipient.
	 *
	 * @param array   $email   Email args.
	 * @param WP_User $user    User.
	 * @param string  $blogname Site name.
	 * @return array
	 */
	public static function new_user_admin_email( $email, $user, $blogname ) {
		unset( $blogname );
		if ( ! is_array( $email ) ) {
			$email = array();
		}
		$email['to'] = self::store_email();
		if ( isset( $user->user_login ) ) {
			$email['subject'] = sprintf(
				/* translators: %s: username */
				__( '[%s] New customer account registered', 'jewelry-upi-store' ),
				$user->user_login
			);
		}
		return $email;
	}

	/**
	 * WooCommerce customer created.
	 *
	 * @param int    $customer_id Customer ID.
	 * @param array  $new_customer_data Data.
	 * @param string $password_generated Generated password flag.
	 */
	public static function notify_new_customer( $customer_id, $new_customer_data, $password_generated ) {
		unset( $new_customer_data, $password_generated );
		$user = get_userdata( $customer_id );
		if ( ! $user ) {
			return;
		}

		self::send(
			sprintf(
				/* translators: %s: customer email */
				__( '[New account] Customer registered — %s', 'jewelry-upi-store' ),
				$user->user_email
			),
			array(
				sprintf( __( 'Username: %s', 'jewelry-upi-store' ), $user->user_login ),
				sprintf( __( 'Email: %s', 'jewelry-upi-store' ), $user->user_email ),
				sprintf( __( 'User ID: %d', 'jewelry-upi-store' ), (int) $customer_id ),
			)
		);
	}

	/**
	 * Password reset requested.
	 *
	 * @param string $user_login Login.
	 * @param string $key        Reset key.
	 */
	public static function notify_password_reset_requested( $user_login, $key ) {
		unset( $key );
		$user = get_user_by( 'login', $user_login );
		if ( ! $user ) {
			return;
		}

		self::send(
			sprintf(
				/* translators: %s: username */
				__( '[Password reset requested] %s', 'jewelry-upi-store' ),
				$user->user_login
			),
			array(
				sprintf( __( 'A password reset was requested for account: %s', 'jewelry-upi-store' ), $user->user_login ),
				sprintf( __( 'Email: %s', 'jewelry-upi-store' ), $user->user_email ),
				__( 'The customer will receive the reset link at their email address.', 'jewelry-upi-store' ),
			)
		);
	}

	/**
	 * Password reset completed.
	 *
	 * @param WP_User $user     User.
	 * @param string  $new_pass New password.
	 */
	public static function notify_password_reset_done( $user, $new_pass ) {
		unset( $new_pass );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		self::send(
			sprintf(
				/* translators: %s: username */
				__( '[Password changed] %s', 'jewelry-upi-store' ),
				$user->user_login
			),
			array(
				sprintf( __( 'Password was reset for: %s (%s)', 'jewelry-upi-store' ), $user->user_login, $user->user_email ),
				gmdate( 'Y-m-d H:i:s' ) . ' UTC',
			)
		);
	}

	/**
	 * Copy store owner on important order status changes.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $old_status Old status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order Order.
	 */
	public static function notify_order_status_change( $order_id, $old_status, $new_status, $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// New-order email already covers the initial pending/on-hold placement.
		if ( in_array( $old_status, array( 'pending', 'checkout-draft', 'auto-draft' ), true ) && 'on-hold' === $new_status ) {
			return;
		}

		$notify_statuses = array( 'processing', 'completed', 'cancelled', 'refunded', 'failed' );
		if ( ! in_array( $new_status, $notify_statuses, true ) ) {
			return;
		}

		self::send(
			sprintf(
				/* translators: 1: order number, 2: status */
				__( '[Order #%1$s] Status updated to %2$s', 'jewelry-upi-store' ),
				$order->get_order_number(),
				$new_status
			),
			array(
				sprintf( __( 'Customer: %s', 'jewelry-upi-store' ), trim( $order->get_formatted_billing_full_name() ) ),
				sprintf( __( 'Email: %s', 'jewelry-upi-store' ), $order->get_billing_email() ),
				sprintf( __( 'Phone: %s', 'jewelry-upi-store' ), $order->get_billing_phone() ?: '—' ),
				sprintf( __( 'Total: %s', 'jewelry-upi-store' ), wp_strip_all_tags( $order->get_formatted_order_total() ) ),
				sprintf( __( 'Payment: %s', 'jewelry-upi-store' ), $order->get_payment_method_title() ),
				$order->get_edit_order_url(),
			)
		);
	}

	/**
	 * Send plain-text mail to store inbox.
	 *
	 * @param string $subject Subject.
	 * @param array  $lines   Body lines.
	 */
	private static function send( $subject, $lines ) {
		$body = implode( "\n", array_filter( array_map( 'wp_strip_all_tags', $lines ) ) );
		wp_mail( self::store_email(), $subject, $body );
	}
}
