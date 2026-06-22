<?php
/**
 * Checkout helpers (UTR field removed — customers verify payment after order via UPI remarks).
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JUS_Checkout
 */
class JUS_Checkout {

	/**
	 * Init hooks.
	 */
	public static function init() {
		// Legacy orders may still have UTR meta — show in admin/account only.
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'display_utr_admin' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'display_utr_customer' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'display_upi_payment_reminder' ), 8 );
	}

	/**
	 * Show UTR in admin order screen (legacy orders only).
	 *
	 * @param WC_Order $order Order.
	 */
	public static function display_utr_admin( $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
			return;
		}
		$utr = $order->get_meta( '_billing_upi_utr' );
		if ( $utr ) {
			echo '<p class="form-field form-field-wide"><strong>' . esc_html__( 'UPI Transaction ID (UTR):', 'jewelry-upi-store' ) . '</strong> <code style="font-size:14px;">' . esc_html( $utr ) . '</code></p>';
		}
	}

	/**
	 * Show UTR on customer order view (legacy orders only).
	 *
	 * @param WC_Order $order Order.
	 */
	public static function display_utr_customer( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			if ( is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( (int) $order );
			}
		}
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}
		$utr = $order->get_meta( '_billing_upi_utr' );
		if ( $utr ) {
			echo '<p><strong>' . esc_html__( 'UPI UTR:', 'jewelry-upi-store' ) . '</strong> ' . esc_html( $utr ) . '</p>';
		}
	}

	/**
	 * UPI payment reminder on My Account order view.
	 *
	 * @param WC_Order|int $order Order or ID.
	 */
	public static function display_upi_payment_reminder( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			if ( is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( (int) $order );
			}
		}
		if ( ! is_a( $order, 'WC_Order' ) || 'jus_manual_upi' !== $order->get_payment_method() ) {
			return;
		}
		if ( ! in_array( $order->get_status(), array( 'on-hold', 'pending' ), true ) ) {
			return;
		}

		$settings = get_option( 'woocommerce_jus_manual_upi_settings', array() );
		$upi_id   = isset( $settings['upi_id'] ) ? $settings['upi_id'] : '';
		$qr_url   = isset( $settings['qr_image_url'] ) ? $settings['qr_image_url'] : '';

		echo '<div class="jus-thankyou-upi jus-view-order-upi" style="margin:1.5em 0;padding:1.5em;border:1px solid #e0c080;background:#fffdf8;border-radius:8px;">';
		echo '<h3 style="margin-top:0;">' . esc_html__( 'Pay through UPI', 'jewelry-upi-store' ) . '</h3>';
		echo '<p><strong>' . esc_html__( 'Amount:', 'jewelry-upi-store' ) . '</strong> ' . wp_kses_post( $order->get_formatted_order_total() ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Order number (UPI remarks):', 'jewelry-upi-store' ) . '</strong> <code>' . esc_html( $order->get_order_number() ) . '</code></p>';
		if ( $upi_id ) {
			echo '<p><strong>' . esc_html__( 'UPI ID:', 'jewelry-upi-store' ) . '</strong> <code>' . esc_html( $upi_id ) . '</code></p>';
		}
		if ( $qr_url ) {
			echo '<p><img src="' . esc_url( $qr_url ) . '" alt="' . esc_attr__( 'UPI QR Code', 'jewelry-upi-store' ) . '" style="max-width:200px;height:auto;border:1px solid #ddd;" /></p>';
		}
		echo '<p><a class="button" href="' . esc_url( $order->get_checkout_order_received_url() ) . '">' . esc_html__( 'Open payment page', 'jewelry-upi-store' ) . '</a></p>';
		echo '</div>';
	}
}
