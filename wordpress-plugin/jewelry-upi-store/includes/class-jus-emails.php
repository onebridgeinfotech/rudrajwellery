<?php
/**
 * Order emails and payment-received notifications (email only).
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JUS_Emails
 */
class JUS_Emails {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_email_order_meta', array( __CLASS__, 'email_order_meta' ), 10, 4 );
		add_filter( 'woocommerce_email_additional_content_customer_processing_order', array( __CLASS__, 'processing_payment_received' ), 10, 3 );
		add_filter( 'woocommerce_email_heading_customer_processing_order', array( __CLASS__, 'processing_email_heading' ), 10, 3 );
		add_filter( 'woocommerce_email_additional_content_customer_on_hold_order', array( __CLASS__, 'on_hold_pay_instructions' ), 10, 3 );
		add_filter( 'woocommerce_email_additional_content_new_order', array( __CLASS__, 'admin_new_order_summary' ), 10, 3 );
	}

	/**
	 * Is this a UPI order?
	 *
	 * @param WC_Order|null $order Order.
	 * @return bool
	 */
	private static function is_upi_order( $order ) {
		return is_a( $order, 'WC_Order' ) && 'jus_manual_upi' === $order->get_payment_method();
	}

	/**
	 * Gateway settings.
	 *
	 * @return array<string, string>
	 */
	private static function gateway_settings() {
		$settings = get_option( 'woocommerce_jus_manual_upi_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Output legacy UTR in order emails.
	 *
	 * @param WC_Order $order Order.
	 * @param bool     $sent_to_admin Admin.
	 * @param bool     $plain_text Plain.
	 * @param WC_Email $email Email.
	 */
	public static function email_order_meta( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
			return;
		}
		$utr = $order->get_meta( '_billing_upi_utr' );
		if ( ! $utr ) {
			return;
		}

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'UPI Transaction ID (UTR):', 'jewelry-upi-store' ) . ' ' . esc_html( $utr ) . "\n";
			return;
		}

		echo '<p><strong>' . esc_html__( 'UPI Transaction ID (UTR):', 'jewelry-upi-store' ) . '</strong> ' . esc_html( $utr ) . '</p>';
	}

	/**
	 * Customer: pay via UPI instructions when order is awaiting payment.
	 *
	 * @param string   $content Content.
	 * @param WC_Order $order   Order.
	 * @param WC_Email $email   Email.
	 * @return string
	 */
	public static function on_hold_pay_instructions( $content, $order, $email ) {
		if ( ! self::is_upi_order( $order ) ) {
			return $content;
		}

		$settings = self::gateway_settings();
		$upi_id   = isset( $settings['upi_id'] ) ? $settings['upi_id'] : '';

		$message = sprintf(
			/* translators: %s: order number */
			__( 'Please complete your UPI payment for order #%s. Put your order number in the UPI payment remarks. We will confirm within 24 hours after we verify your payment.', 'jewelry-upi-store' ),
			$order->get_order_number()
		);

		if ( $upi_id ) {
			$message .= ' ' . sprintf(
				/* translators: %s: UPI ID */
				__( 'Pay to UPI ID: %s', 'jewelry-upi-store' ),
				$upi_id
			);
		}

		$message .= ' ' . sprintf(
			/* translators: %s: formatted order total */
			__( 'Amount: %s', 'jewelry-upi-store' ),
			wp_strip_all_tags( $order->get_formatted_order_total() )
		);

		return trim( $content . "\n\n" . $message );
	}

	/**
	 * Customer: payment verified heading.
	 *
	 * @param string   $heading Heading.
	 * @param WC_Order $order   Order.
	 * @param WC_Email $email   Email.
	 * @return string
	 */
	public static function processing_email_heading( $heading, $order, $email ) {
		if ( ! self::is_upi_order( $order ) ) {
			return $heading;
		}

		return __( 'Your UPI payment has been received', 'jewelry-upi-store' );
	}

	/**
	 * Customer: payment verified message when admin marks Processing.
	 *
	 * @param string   $content Content.
	 * @param WC_Order $order   Order.
	 * @param WC_Email $email   Email.
	 * @return string
	 */
	public static function processing_payment_received( $content, $order, $email ) {
		if ( ! self::is_upi_order( $order ) ) {
			return $content;
		}

		$message = sprintf(
			/* translators: 1: order number, 2: order total */
			__( 'Good news! Your UPI payment for order #%1$s (%2$s) has been verified. We are preparing your shipment and will notify you when it is dispatched.', 'jewelry-upi-store' ),
			$order->get_order_number(),
			wp_strip_all_tags( $order->get_formatted_order_total() )
		);

		return trim( $content . "\n\n" . $message );
	}

	/**
	 * Admin: extra summary on new order email.
	 *
	 * @param string   $content Content.
	 * @param WC_Order $order   Order.
	 * @param WC_Email $email   Email.
	 * @return string
	 */
	public static function admin_new_order_summary( $content, $order, $email ) {
		if ( ! self::is_upi_order( $order ) ) {
			return $content;
		}

		$phone = $order->get_billing_phone();
		$note  = sprintf(
			/* translators: 1: order number, 2: customer name, 3: order total */
			__( 'UPI order #%1$s from %2$s — total %3$s. Verify payment in your UPI app (order number in remarks + amount, or UTR if provided). Mark Processing after confirm.', 'jewelry-upi-store' ),
			$order->get_order_number(),
			trim( $order->get_formatted_billing_full_name() ),
			wp_strip_all_tags( $order->get_formatted_order_total() )
		);

		if ( $phone ) {
			$note .= ' ' . sprintf(
				/* translators: %s: phone number */
				__( 'Customer phone: %s', 'jewelry-upi-store' ),
				$phone
			);
		}

		return trim( $content . "\n\n" . $note );
	}
}
