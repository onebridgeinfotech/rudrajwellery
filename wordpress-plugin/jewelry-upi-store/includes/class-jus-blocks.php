<?php
/**
 * WooCommerce Checkout Block support for Manual UPI gateway.
 *
 * @package JewelryUPIStore
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Registers Manual UPI for Cart/Checkout blocks.
 */
final class JUS_Blocks_Payment extends AbstractPaymentMethodType {

	/**
	 * Payment method name (must match gateway id and JS registration).
	 *
	 * @var string
	 */
	protected $name = 'jus_manual_upi';

	/**
	 * Gateway settings.
	 *
	 * @var array
	 */
	private $gateway_settings = array();

	/**
	 * Load settings.
	 */
	public function initialize() {
		$this->gateway_settings = get_option( 'woocommerce_jus_manual_upi_settings', array() );
		if ( ! is_array( $this->gateway_settings ) ) {
			$this->gateway_settings = array();
		}
	}

	/**
	 * Whether the gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_active() {
		$enabled = isset( $this->gateway_settings['enabled'] ) ? $this->gateway_settings['enabled'] : 'yes';
		return ( 'yes' === $enabled || '1' === $enabled || true === $enabled );
	}

	/**
	 * Script handles for checkout block.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_url = plugins_url( 'assets/js/jus-blocks-checkout.js', JUS_PLUGIN_FILE );

		wp_register_script(
			'jus-blocks-checkout',
			$script_url,
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wc-sanitize' ),
			JUS_VERSION,
			true
		);

		return array( 'jus-blocks-checkout' );
	}

	/**
	 * Script handles for block editor preview.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles_for_admin() {
		return $this->get_payment_method_script_handles();
	}

	/**
	 * Data exposed to the blocks checkout script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title', __( 'Pay via UPI', 'jewelry-upi-store' ) ),
			'description' => $this->get_setting( 'description', __( 'Scan the QR code or pay to our UPI ID. Enter your UTR after payment.', 'jewelry-upi-store' ) ),
			'upi_id'      => $this->get_setting( 'upi_id', '' ),
			'supports'    => array( 'products' ),
		);
	}

	/**
	 * Read a gateway setting with fallback.
	 *
	 * @param string $key Setting key.
	 * @param string $default Default value.
	 * @return string
	 */
	private function get_setting( $key, $default = '' ) {
		if ( isset( $this->gateway_settings[ $key ] ) && '' !== $this->gateway_settings[ $key ] ) {
			return $this->gateway_settings[ $key ];
		}
		return $default;
	}
}
