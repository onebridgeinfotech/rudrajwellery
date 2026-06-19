<?php
/**
 * Cart line item — minimal safe row (classic cart page).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $cart_item, $cart_item_key ) ) {
	return;
}

$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
if ( ! $product instanceof WC_Product || ! $product->exists() || (int) $cart_item['quantity'] <= 0 ) {
	return;
}

$name = $product->get_name();
$url  = $product->is_visible() ? $product->get_permalink() : '';
?>
<tr class="woocommerce-cart-form__cart-item cart_item">
	<td class="product-remove">
		<a role="button" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" class="remove">&times;</a>
	</td>
	<td class="product-thumbnail">
		<span class="jwellery-cart-thumb-fallback" aria-hidden="true">&#9679;</span>
	</td>
	<td class="product-name" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
		<?php
		if ( $url ) {
			echo '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
		} else {
			echo esc_html( $name );
		}
		?>
	</td>
	<td class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
		<?php echo wp_kses_post( WC()->cart->get_product_price( $product ) ); ?>
	</td>
	<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
		<?php
		echo woocommerce_quantity_input(
			array(
				'input_name'   => "cart[{$cart_item_key}][qty]",
				'input_value'  => $cart_item['quantity'],
				'max_value'    => $product->get_max_purchase_quantity(),
				'min_value'    => $product->is_sold_individually() ? 1 : 0,
				'product_name' => $name,
			),
			$product,
			false
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</td>
	<td class="product-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
		<?php echo wp_kses_post( WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ) ); ?>
	</td>
</tr>
