<?php
/**
 * Order line item — safe render (avoids is_visible() fatals on some hosts).
 *
 * @package JwelleryJewelry
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
	return;
}

$product = $item->get_product();
?>
<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'woocommerce-table__line-item order_item', $item, $order ) ); ?>">
	<td class="woocommerce-table__product-name product-name">
		<?php
		$name = $item->get_name();
		if ( $product && $product->get_permalink() ) {
			echo '<a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $name ) . '</a>';
		} else {
			echo esc_html( $name );
		}

		$qty          = $item->get_quantity();
		$refunded_qty = $order->get_qty_refunded_for_item( $item->get_id() );
		if ( $refunded_qty ) {
			$qty_display = '<del>' . esc_html( (string) $qty ) . '</del> <ins>' . esc_html( (string) ( $qty - ( $refunded_qty * -1 ) ) ) . '</ins>';
		} else {
			$qty_display = esc_html( (string) $qty );
		}
		echo wp_kses_post(
			apply_filters(
				'woocommerce_order_item_quantity_html',
				' <strong class="product-quantity">&times;&nbsp;' . $qty_display . '</strong>',
				$item
			)
		);

		do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );
		wc_display_item_meta( $item );
		do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
		?>
	</td>
	<td class="woocommerce-table__product-total product-total">
		<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
	</td>
</tr>
