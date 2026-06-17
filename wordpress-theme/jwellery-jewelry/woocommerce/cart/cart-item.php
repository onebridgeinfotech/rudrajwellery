<?php
/**
 * Cart line item — minimal safe template.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $cart_item, $cart_item_key ) ) {
	return;
}

$_product   = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
$product_id = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;

if ( ! $_product || ! is_a( $_product, 'WC_Product' ) || $cart_item['quantity'] <= 0 ) {
	return;
}

$product_name      = $_product->get_name();
$product_permalink = get_permalink( $product_id );
?>
<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
	<td class="product-remove">
		<a role="button" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" class="remove" aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ); ?>">&times;</a>
	</td>
	<td class="product-thumbnail">
		<?php if ( $product_permalink ) : ?>
			<a href="<?php echo esc_url( $product_permalink ); ?>"><span class="jwellery-cart-thumb-fallback" aria-hidden="true">&#9679;</span></a>
		<?php else : ?>
			<span class="jwellery-cart-thumb-fallback" aria-hidden="true">&#9679;</span>
		<?php endif; ?>
	</td>
	<td scope="row" role="rowheader" class="product-name" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
		<?php if ( $product_permalink ) : ?>
			<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo esc_html( $product_name ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $product_name ); ?>
		<?php endif; ?>
		<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</td>
	<td class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
		<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</td>
	<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
		<?php
		$product_quantity = woocommerce_quantity_input(
			array(
				'input_name'   => "cart[{$cart_item_key}][qty]",
				'input_value'  => $cart_item['quantity'],
				'max_value'    => $_product->get_max_purchase_quantity(),
				'min_value'    => $_product->is_sold_individually() ? 1 : 0,
				'product_name' => $product_name,
			),
			$_product,
			false
		);
		echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</td>
	<td class="product-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
		<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</td>
</tr>
