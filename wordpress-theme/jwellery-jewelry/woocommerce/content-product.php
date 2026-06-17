<?php
/**
 * Product card in shop / category / upsell loops.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

if ( function_exists( 'jwellery_render_product_card' ) ) {
	jwellery_render_product_card( $product );
	return;
}

?>
<li <?php wc_product_class( '', $product ); ?>>
	<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
		<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h2 class="woocommerce-loop-product__title"><?php echo esc_html( $product->get_name() ); ?></h2>
		<?php echo wp_kses_post( $product->get_price_html() ); ?>
	</a>
	<?php woocommerce_template_loop_add_to_cart(); ?>
</li>
