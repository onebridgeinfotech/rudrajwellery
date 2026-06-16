<?php
/**
 * After payment: optional UTR + "I've paid" confirmation (email to admin).
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JUS_Payment_Tracking
 */
class JUS_Payment_Tracking {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_thankyou_jus_manual_upi', array( __CLASS__, 'render_thankyou_tracking_form' ), 25 );
		add_action( 'template_redirect', array( __CLASS__, 'handle_payment_claim' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'display_claim_admin' ), 15 );
	}

	/**
	 * Rate limit payment claims per IP.
	 *
	 * @return bool True when limited.
	 */
	private static function is_payment_claim_rate_limited() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$key = 'jus_claim_rl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 8 ) {
			return true;
		}
		set_transient( $key, $n + 1, 15 * MINUTE_IN_SECONDS );
		return false;
	}

	/**
	 * Can customer submit payment claim for this order?
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	private static function can_claim_payment( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return false;
		}
		if ( 'jus_manual_upi' !== $order->get_payment_method() ) {
			return false;
		}
		if ( $order->get_meta( '_jus_payment_claimed' ) ) {
			return false;
		}
		return in_array( $order->get_status(), array( 'on-hold', 'pending' ), true );
	}

	/**
	 * Thank-you page: pay steps + optional UTR + I've paid button.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function render_thankyou_tracking_form( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$claimed = (bool) $order->get_meta( '_jus_payment_claimed' );
		$utr     = $order->get_meta( '_billing_upi_utr' );

		echo '<div class="jus-payment-tracking" style="margin:1.5em 0;padding:1.5em;border:1px solid #c9a227;background:#fffef5;border-radius:8px;">';
		echo '<h3 style="margin-top:0;">' . esc_html__( 'After you pay via UPI', 'jewelry-upi-store' ) . '</h3>';
		echo '<ol style="margin:0 0 1em 1.2em;padding:0;line-height:1.6;">';
		echo '<li>' . esc_html__( 'Pay the exact order total shown above.', 'jewelry-upi-store' ) . '</li>';
		echo '<li>' . sprintf(
			/* translators: %s: order number */
			esc_html__( 'In your UPI app, put order number %s in the payment remarks / note (required).', 'jewelry-upi-store' ),
			'<strong>' . esc_html( $order->get_order_number() ) . '</strong>'
		) . '</li>';
		echo '<li>' . esc_html__( 'Optional: enter your 12-digit UTR / transaction reference below — helps us find your payment faster.', 'jewelry-upi-store' ) . '</li>';
		echo '</ol>';

		if ( $claimed ) {
			echo '<p class="jus-payment-claimed" style="margin:0;padding:12px;background:#e8f5e9;border-radius:6px;color:#1b5e20;">';
			echo esc_html__( 'Thank you — we received your payment confirmation. We will verify in our UPI app and email you within 24 hours.', 'jewelry-upi-store' );
			if ( $utr ) {
				echo ' <strong>' . esc_html__( 'UTR:', 'jewelry-upi-store' ) . '</strong> ' . esc_html( $utr );
			}
			echo '</p></div>';
			return;
		}

		if ( ! self::can_claim_payment( $order ) ) {
			echo '</div>';
			return;
		}

		$action = $order->get_checkout_order_received_url();
		echo '<form method="post" action="' . esc_url( $action ) . '" class="jus-payment-claim-form">';
		wp_nonce_field( 'jus_payment_claim_' . $order_id, 'jus_payment_claim_nonce' );
		echo '<input type="hidden" name="jus_payment_claim" value="1" />';
		echo '<input type="hidden" name="jus_order_id" value="' . esc_attr( (string) $order_id ) . '" />';
		echo '<input type="hidden" name="jus_order_key" value="' . esc_attr( $order->get_order_key() ) . '" />';
		echo '<p style="margin:0 0 8px;"><label for="jus_upi_utr"><strong>' . esc_html__( 'UPI reference / UTR (optional)', 'jewelry-upi-store' ) . '</strong></label></p>';
		echo '<p style="margin:0 0 12px;font-size:0.9em;color:#555;">' . esc_html__( 'From PhonePe / GPay / Paytm receipt — not your UPI ID.', 'jewelry-upi-store' ) . '</p>';
		echo '<p style="margin:0 0 16px;"><input type="text" name="jus_upi_utr" id="jus_upi_utr" placeholder="' . esc_attr__( 'e.g. 123456789012', 'jewelry-upi-store' ) . '" style="width:100%;max-width:320px;padding:10px;border:1px solid #ccc;border-radius:4px;" /></p>';
		echo '<button type="submit" class="button alt" style="background:#7b1e3a;color:#fff;border:none;padding:12px 24px;border-radius:4px;cursor:pointer;font-weight:600;">';
		echo esc_html__( "I've completed UPI payment", 'jewelry-upi-store' );
		echo '</button></form></div>';
	}

	/**
	 * Process payment claim form on thank-you page.
	 */
	public static function handle_payment_claim() {
		if ( empty( $_POST['jus_payment_claim'] ) || empty( $_POST['jus_order_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		if ( function_exists( 'is_wc_endpoint_url' ) && ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		$order_id = absint( $_POST['jus_order_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $order_id || ! isset( $_POST['jus_payment_claim_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jus_payment_claim_nonce'] ) ), 'jus_payment_claim_' . $order_id ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wc_add_notice( __( 'Security check failed. Please try again.', 'jewelry-upi-store' ), 'error' );
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! self::can_claim_payment( $order ) ) {
			return;
		}

		$posted_key = isset( $_POST['jus_order_key'] ) ? wc_clean( wp_unslash( $_POST['jus_order_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $posted_key || ! hash_equals( $order->get_order_key(), $posted_key ) ) {
			wc_add_notice( __( 'Invalid order session. Open the thank-you page from your order email link.', 'jewelry-upi-store' ), 'error' );
			return;
		}

		if ( self::is_payment_claim_rate_limited() ) {
			wc_add_notice( __( 'Too many attempts. Please wait a few minutes.', 'jewelry-upi-store' ), 'error' );
			return;
		}

		$utr = '';
		if ( ! empty( $_POST['jus_upi_utr'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$utr = sanitize_text_field( wp_unslash( $_POST['jus_upi_utr'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( strlen( $utr ) > 0 && strlen( $utr ) < 8 ) {
				wc_add_notice( __( 'UTR looks too short. Leave blank if unsure, or check your UPI app receipt.', 'jewelry-upi-store' ), 'error' );
				return;
			}
			if ( false !== strpos( $utr, '@' ) ) {
				wc_add_notice( __( 'That looks like a UPI ID, not a transaction reference. Enter the 12-digit UTR from your payment receipt, or leave blank.', 'jewelry-upi-store' ), 'error' );
				return;
			}
		}

		if ( $utr ) {
			$order->update_meta_data( '_billing_upi_utr', $utr );
		}

		$order->update_meta_data( '_jus_payment_claimed', current_time( 'mysql' ) );
		$order->save();

		$note = __( 'Customer confirmed UPI payment on thank-you page.', 'jewelry-upi-store' );
		if ( $utr ) {
			$note .= ' ' . sprintf(
				/* translators: %s: UTR */
				__( 'UTR: %s', 'jewelry-upi-store' ),
				$utr
			);
		}
		$order->add_order_note( $note, false, true );

		self::email_admin_payment_claimed( $order, $utr );

		wc_add_notice( __( 'Thank you! We will verify your UPI payment and confirm by email.', 'jewelry-upi-store' ), 'success' );

		wp_safe_redirect( $order->get_checkout_order_received_url() );
		exit;
	}

	/**
	 * Email store owner when customer claims payment.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $utr   Optional UTR.
	 */
	private static function email_admin_payment_claimed( $order, $utr = '' ) {
		$admin_email = get_option( 'admin_email' );
		if ( ! $admin_email ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: order number */
			__( '[%s] Customer says UPI payment done — verify now', 'jewelry-upi-store' ),
			$order->get_order_number()
		);

		$lines = array(
			sprintf(
				/* translators: 1: order number, 2: customer name, 3: total */
				__( 'Order #%1$s from %2$s — total %3$s.', 'jewelry-upi-store' ),
				$order->get_order_number(),
				trim( $order->get_formatted_billing_full_name() ),
				wp_strip_all_tags( $order->get_formatted_order_total() )
			),
			__( 'Customer clicked "I\'ve completed UPI payment".', 'jewelry-upi-store' ),
		);

		if ( $utr ) {
			$lines[] = sprintf(
				/* translators: %s: UTR */
				__( 'UTR provided: %s — search this in your UPI app.', 'jewelry-upi-store' ),
				$utr
			);
		} else {
			$lines[] = sprintf(
				/* translators: %s: order number */
				__( 'No UTR — match by order number %s in UPI remarks + exact amount.', 'jewelry-upi-store' ),
				$order->get_order_number()
			);
		}

		$lines[] = $order->get_edit_order_url();

		wp_mail( $admin_email, $subject, implode( "\n\n", $lines ) );
	}

	/**
	 * Show payment claim status in admin order screen.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function display_claim_admin( $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
			return;
		}
		if ( 'jus_manual_upi' !== $order->get_payment_method() ) {
			return;
		}

		$claimed = $order->get_meta( '_jus_payment_claimed' );
		if ( ! $claimed ) {
			echo '<p class="form-field form-field-wide"><strong>' . esc_html__( 'UPI payment claim:', 'jewelry-upi-store' ) . '</strong> ';
			echo esc_html__( 'Not yet confirmed by customer', 'jewelry-upi-store' ) . '</p>';
			return;
		}

		echo '<p class="form-field form-field-wide" style="color:#1b5e20;"><strong>' . esc_html__( 'UPI payment claim:', 'jewelry-upi-store' ) . '</strong> ';
		echo esc_html__( 'Customer confirmed payment on', 'jewelry-upi-store' ) . ' ' . esc_html( $claimed );
		echo ' — ' . esc_html__( 'verify in UPI app, then mark Processing.', 'jewelry-upi-store' ) . '</p>';
	}
}
