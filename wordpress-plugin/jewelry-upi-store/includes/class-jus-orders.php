<?php
/**
 * Order status labels and admin list column for UTR.
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JUS_Orders
 */
class JUS_Orders {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'wc_order_statuses', array( __CLASS__, 'rename_pending_status' ) );
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_utr_column' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'render_utr_column' ), 20, 2 );
		add_filter( 'woocommerce_shop_order_list_table_columns', array( __CLASS__, 'add_utr_column' ), 20 );
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( __CLASS__, 'render_utr_column_hpos' ), 20, 2 );
	}

	/**
	 * Rename pending to "Pending payment" for clarity.
	 *
	 * @param array $statuses Statuses.
	 * @return array
	 */
	public static function rename_pending_status( $statuses ) {
		if ( isset( $statuses['wc-pending'] ) ) {
			$statuses['wc-pending'] = _x( 'Pending payment', 'Order status', 'jewelry-upi-store' );
		}
		if ( isset( $statuses['wc-on-hold'] ) ) {
			$statuses['wc-on-hold'] = _x( 'Awaiting UPI payment', 'Order status', 'jewelry-upi-store' );
		}
		return $statuses;
	}

	/**
	 * Add payment-tracking columns to orders list.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function add_tracking_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'order_status' === $key ) {
				$new['jus_claim'] = __( 'UPI paid?', 'jewelry-upi-store' );
				$new['jus_utr']   = __( 'UTR', 'jewelry-upi-store' );
			}
		}
		return $new;
	}

	/**
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function add_utr_column( $columns ) {
		return self::add_tracking_columns( $columns );
	}

	/**
	 * Render UTR column (legacy posts table).
	 *
	 * @param string $column Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function render_utr_column( $column, $post_id ) {
		if ( 'jus_utr' === $column ) {
			$order = wc_get_order( $post_id );
			if ( $order ) {
				$utr = $order->get_meta( '_billing_upi_utr' );
				echo $utr ? esc_html( $utr ) : '—';
			}
			return;
		}
		if ( 'jus_claim' === $column ) {
			$order = wc_get_order( $post_id );
			if ( $order && $order->get_meta( '_jus_payment_claimed' ) ) {
				echo '<span style="color:#1b5e20;font-weight:600;">' . esc_html__( 'Yes', 'jewelry-upi-store' ) . '</span>';
			} else {
				echo '—';
			}
		}
	}

	/**
	 * Render UTR column (HPOS list table).
	 *
	 * @param string   $column Column key.
	 * @param WC_Order $order Order.
	 */
	public static function render_utr_column_hpos( $column, $order ) {
		if ( 'jus_utr' === $column ) {
			$utr = $order->get_meta( '_billing_upi_utr' );
			echo $utr ? esc_html( $utr ) : '—';
			return;
		}
		if ( 'jus_claim' === $column ) {
			if ( $order->get_meta( '_jus_payment_claimed' ) ) {
				echo '<span style="color:#1b5e20;font-weight:600;">' . esc_html__( 'Yes', 'jewelry-upi-store' ) . '</span>';
			} else {
				echo '—';
			}
		}
	}
}
