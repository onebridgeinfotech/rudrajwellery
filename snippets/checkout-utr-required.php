<?php
/**
 * Backup: UPI UTR field on checkout (use only if Jewelry UPI Store plugin is inactive).
 *
 * Add to child theme functions.php:
 * require_once get_stylesheet_directory() . '/checkout-utr-required.php';
 *
 * @package JewelryEcommerce
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_checkout_fields', 'jewelry_add_utr_checkout_field' );

function jewelry_add_utr_checkout_field( $fields ) {
	$fields['order']['billing_upi_utr'] = array(
		'label'       => __( 'UPI Transaction ID (UTR)', 'jewelry-ecommerce' ),
		'placeholder' => __( '12-digit UTR from your UPI app', 'jewelry-ecommerce' ),
		'required'    => false,
		'class'       => array( 'form-row-wide' ),
		'priority'    => 120,
	);
	return $fields;
}

add_action( 'woocommerce_checkout_process', 'jewelry_validate_utr_field' );

function jewelry_validate_utr_field() {
	if ( empty( $_POST['billing_upi_utr'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wc_add_notice( __( 'Please enter your UPI Transaction ID (UTR).', 'jewelry-ecommerce' ), 'error' );
	}
}

add_action( 'woocommerce_checkout_update_order_meta', 'jewelry_save_utr_field' );

function jewelry_save_utr_field( $order_id ) {
	if ( ! empty( $_POST['billing_upi_utr'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $order_id, '_billing_upi_utr', sanitize_text_field( wp_unslash( $_POST['billing_upi_utr'] ) ) );
	}
}

add_action( 'woocommerce_admin_order_data_after_billing_address', 'jewelry_display_utr_admin' );

function jewelry_display_utr_admin( $order ) {
	$utr = $order->get_meta( '_billing_upi_utr' );
	if ( $utr ) {
		echo '<p><strong>' . esc_html__( 'UPI UTR:', 'jewelry-ecommerce' ) . '</strong> ' . esc_html( $utr ) . '</p>';
	}
}
