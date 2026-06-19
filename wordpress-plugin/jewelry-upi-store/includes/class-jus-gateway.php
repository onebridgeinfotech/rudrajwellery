<?php
/**
 * Manual UPI payment gateway.
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JUS_Gateway
 */
class JUS_Gateway extends WC_Payment_Gateway {

	/** @var string */
	public $upi_id = '';

	/** @var string */
	public $qr_image_url = '';

	/** @var string */
	public $instructions = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'jus_manual_upi';
		$this->icon               = '';
		$this->has_fields         = true;
		$this->method_title       = __( 'Manual UPI (Jewelry Store)', 'jewelry-upi-store' );
		$this->method_description = __( 'Customers pay via UPI QR or UPI ID after placing the order. Orders stay pending until you verify payment.', 'jewelry-upi-store' );
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->enabled      = $this->get_option( 'enabled', 'yes' );
		$this->title        = $this->get_option( 'title', __( 'Pay via UPI', 'jewelry-upi-store' ) );
		$this->description  = $this->get_option( 'description', __( 'Pay via UPI after you place the order. UPI details appear on the next page.', 'jewelry-upi-store' ) );
		$this->upi_id       = $this->get_option( 'upi_id' );
		$this->qr_image_url = $this->get_option( 'qr_image_url' );
		$this->instructions = $this->get_option( 'instructions' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 4 );
	}

	/**
	 * Admin settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable/Disable', 'jewelry-upi-store' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Manual UPI', 'jewelry-upi-store' ),
				'default' => 'yes',
			),
			'title'        => array(
				'title'       => __( 'Title', 'jewelry-upi-store' ),
				'type'        => 'text',
				'description' => __( 'Shown at checkout.', 'jewelry-upi-store' ),
				'default'     => __( 'Pay via UPI', 'jewelry-upi-store' ),
				'desc_tip'    => true,
			),
			'description'  => array(
				'title'       => __( 'Description', 'jewelry-upi-store' ),
				'type'        => 'textarea',
				'description' => __( 'Shown at checkout under payment method.', 'jewelry-upi-store' ),
				'default'     => __( 'Pay via UPI after placing your order. UPI ID and QR code will be shown on the confirmation page.', 'jewelry-upi-store' ),
			),
			'upi_id'       => array(
				'title'       => __( 'UPI ID', 'jewelry-upi-store' ),
				'type'        => 'text',
				'description' => __( 'e.g. yourstore@paytm or 9876543210@ybl', 'jewelry-upi-store' ),
				'default'     => '',
			),
			'qr_image_url' => array(
				'title'       => __( 'QR Code Image URL', 'jewelry-upi-store' ),
				'type'        => 'text',
				'description' => __( 'Upload QR to Media Library and paste the file URL here.', 'jewelry-upi-store' ),
				'default'     => '',
			),
			'instructions' => array(
				'title'       => __( 'Payment instructions', 'jewelry-upi-store' ),
				'type'        => 'textarea',
				'description' => __( 'Shown on thank-you page and emails.', 'jewelry-upi-store' ),
				'default'     => __( 'Pay the exact order total. Put your Order Number in the UPI payment remarks/note. We will confirm within 24 hours after verifying your payment.', 'jewelry-upi-store' ),
			),
		);
	}

	/**
	 * Gateway available when enabled (no country/currency blocks for UPI).
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}
		return parent::is_available();
	}

	/**
	 * Payment fields on checkout (UPI reminder).
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
		}
		if ( $this->upi_id ) {
			echo '<p><strong>' . esc_html__( 'UPI ID:', 'jewelry-upi-store' ) . '</strong> <code>' . esc_html( $this->upi_id ) . '</code></p>';
		}
		echo '<p class="jus-checkout-note">' . esc_html__( 'Place your order first — pay on the next page via QR code. Put your order number in UPI remarks.', 'jewelry-upi-store' ) . '</p>';
	}

	/**
	 * Process payment — order pending until admin confirms.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array(
				'result'   => 'failure',
				'redirect' => '',
			);
		}

		if ( ! $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
			$order->update_status(
				'on-hold',
				__( 'Awaiting UPI payment verification.', 'jewelry-upi-store' )
			);
		}

		if ( function_exists( 'wc_reduce_stock_levels' ) ) {
			wc_reduce_stock_levels( $order_id );
		} elseif ( method_exists( $order, 'reduce_order_stock' ) ) {
			$order->reduce_order_stock();
		}

		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->empty_cart();
		}

		$redirect = $this->get_return_url( $order );
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( 'jus_checkout_redirect', $redirect );
		}

		return array(
			'result'   => 'success',
			'redirect' => $redirect,
		);
	}

	/**
	 * Thank-you page: UPI QR, ID, amount, instructions.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$utr = $order->get_meta( '_billing_upi_utr' );

		echo '<div class="jus-thankyou-upi" style="margin:1.5em 0;padding:1.5em;border:1px solid #e0c080;background:#fffdf8;border-radius:8px;">';
		echo '<h2 style="margin-top:0;">' . esc_html__( 'Complete your UPI payment', 'jewelry-upi-store' ) . '</h2>';
		echo '<p style="margin:0 0 1em;padding:10px 12px;background:#fff3cd;border-radius:6px;"><strong>' . esc_html__( 'Important:', 'jewelry-upi-store' ) . '</strong> ';
		echo sprintf(
			/* translators: %s: order number */
			esc_html__( 'When paying, enter order number %s in the UPI remarks / note field.', 'jewelry-upi-store' ),
			'<code style="font-size:1.05em;">' . esc_html( $order->get_order_number() ) . '</code>'
		);
		echo '</p>';
		echo '<p><strong>' . esc_html__( 'Order number:', 'jewelry-upi-store' ) . '</strong> ' . esc_html( $order->get_order_number() ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Amount to pay:', 'jewelry-upi-store' ) . '</strong> ' . wp_kses_post( $order->get_formatted_order_total() ) . '</p>';

		if ( $this->upi_id ) {
			echo '<p><strong>' . esc_html__( 'UPI ID:', 'jewelry-upi-store' ) . '</strong> <code style="font-size:1.1em;">' . esc_html( $this->upi_id ) . '</code></p>';
		}

		if ( $this->qr_image_url ) {
			echo '<p><img src="' . esc_url( $this->qr_image_url ) . '" alt="' . esc_attr__( 'UPI QR Code', 'jewelry-upi-store' ) . '" style="max-width:220px;height:auto;border:1px solid #ddd;" /></p>';
		}

		if ( $utr ) {
			echo '<p><strong>' . esc_html__( 'Your UTR:', 'jewelry-upi-store' ) . '</strong> ' . esc_html( $utr ) . '</p>';
		}

		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}

		echo '</div>';
	}

	/**
	 * Add UPI details to customer emails.
	 *
	 * @param WC_Order $order Order.
	 * @param bool     $sent_to_admin Admin email.
	 * @param bool     $plain_text Plain text.
	 * @param WC_Email $email Email object.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}
		if ( $order->get_payment_method() !== $this->id ) {
			return;
		}

		$allowed_ids = array( 'customer_on_hold_order', 'customer_processing_order', 'customer_completed_order', 'new_order' );
		$email_id    = ( is_object( $email ) && isset( $email->id ) ) ? $email->id : '';
		if ( $email_id && ! in_array( $email_id, $allowed_ids, true ) ) {
			return;
		}

		$utr = $order->get_meta( '_billing_upi_utr' );

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Payment: UPI', 'jewelry-upi-store' ) . "\n";
			if ( $this->upi_id ) {
				echo esc_html__( 'UPI ID:', 'jewelry-upi-store' ) . ' ' . esc_html( $this->upi_id ) . "\n";
			}
			echo esc_html__( 'Order total:', 'jewelry-upi-store' ) . ' ' . esc_html( wp_strip_all_tags( $order->get_formatted_order_total() ) ) . "\n";
			if ( $utr ) {
				echo esc_html__( 'UTR:', 'jewelry-upi-store' ) . ' ' . esc_html( $utr ) . "\n";
			}
			if ( $this->instructions && ! $sent_to_admin ) {
				echo "\n" . esc_html( wp_strip_all_tags( $this->instructions ) ) . "\n";
			}
			return;
		}

		echo '<div style="margin:16px 0;padding:12px;background:#fffdf8;border:1px solid #e0c080;">';
		echo '<h3 style="margin:0 0 8px;">' . esc_html__( 'UPI payment details', 'jewelry-upi-store' ) . '</h3>';
		if ( $this->upi_id ) {
			echo '<p><strong>' . esc_html__( 'UPI ID:', 'jewelry-upi-store' ) . '</strong> ' . esc_html( $this->upi_id ) . '</p>';
		}
		echo '<p><strong>' . esc_html__( 'Amount:', 'jewelry-upi-store' ) . '</strong> ' . wp_kses_post( $order->get_formatted_order_total() ) . '</p>';
		if ( $utr ) {
			echo '<p><strong>' . esc_html__( 'UTR:', 'jewelry-upi-store' ) . '</strong> ' . esc_html( $utr ) . '</p>';
		}
		if ( $this->qr_image_url && ! $sent_to_admin ) {
			echo '<p><img src="' . esc_url( $this->qr_image_url ) . '" alt="UPI QR" style="max-width:180px;" /></p>';
		}
		echo '</div>';
	}
}
