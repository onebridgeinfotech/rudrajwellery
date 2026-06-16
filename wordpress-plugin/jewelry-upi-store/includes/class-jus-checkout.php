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
		$utr = $order->get_meta( '_billing_upi_utr' );
		if ( $utr ) {
			echo '<p><strong>' . esc_html__( 'UPI UTR:', 'jewelry-upi-store' ) . '</strong> ' . esc_html( $utr ) . '</p>';
		}
	}
}
