<?php
/**
 * Mini cart — minimal safe output for AJAX fragments.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' );

if ( ! WC()->cart || WC()->cart->is_empty() ) {
	echo '<p class="woocommerce-mini-cart__empty-message">' . esc_html__( 'No products in the cart.', 'woocommerce' ) . '</p>';
	do_action( 'woocommerce_after_mini_cart' );
	return;
}
?>
<ul class="woocommerce-mini-cart cart_list product_list_widget">
	<?php
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $product instanceof WC_Product || ! $product->exists() ) {
			continue;
		}
		$name = $product->get_name();
		?>
		<li class="woocommerce-mini-cart-item mini_cart_item">
			<a role="button" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" class="remove remove_from_cart_button" data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>">&times;</a>
			<?php echo esc_html( $name ); ?>
			<span class="quantity"><?php echo esc_html( (string) (int) $cart_item['quantity'] ); ?> &times; <?php echo wp_kses_post( WC()->cart->get_product_price( $product ) ); ?></span>
		</li>
		<?php
	}
	?>
</ul>
<p class="woocommerce-mini-cart__total total">
	<strong><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?>:</strong>
	<?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?>
</p>
<p class="woocommerce-mini-cart__buttons buttons">
	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="button wc-forward"><?php esc_html_e( 'View cart', 'woocommerce' ); ?></a>
	<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button checkout wc-forward"><?php esc_html_e( 'Checkout', 'woocommerce' ); ?></a>
</p>
<?php
do_action( 'woocommerce_after_mini_cart' );
