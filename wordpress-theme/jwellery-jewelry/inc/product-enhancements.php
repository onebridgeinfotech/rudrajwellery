<?php
/**
 * Product page — details, care, similar items, checkout polish.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default jewellery product details when attributes are empty.
 *
 * @return array<string, string>
 */
function jwellery_default_product_details() {
	return array(
		'material'  => __( 'Premium imitation / fashion jewellery with gold-tone finish', 'jwellery-jewelry' ),
		'occasion'  => __( 'Weddings, festivals, parties & daily traditional wear', 'jwellery-jewelry' ),
		'care'      => __( 'Avoid water, perfume & sweat directly on the piece. Store in a dry pouch.', 'jwellery-jewelry' ),
	);
}

/**
 * Read attribute or meta value for display.
 *
 * @param WC_Product $product Product.
 * @param string     $key     material|occasion|care.
 * @return string
 */
function jwellery_product_detail_value( $product, $key ) {
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return '';
	}

	$attr_map = array(
		'material' => array( 'pa_material', 'material' ),
		'occasion' => array( 'pa_occasion', 'occasion' ),
		'care'     => array( 'pa_care', 'care' ),
	);

	if ( isset( $attr_map[ $key ] ) ) {
		foreach ( $attr_map[ $key ] as $attr ) {
			$val = $product->get_attribute( $attr );
			if ( $val ) {
				return wp_strip_all_tags( $val );
			}
		}
	}

	$meta = get_post_meta( $product->get_id(), '_jwellery_' . $key, true );
	if ( $meta ) {
		return wp_strip_all_tags( (string) $meta );
	}

	$defaults = jwellery_default_product_details();
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
}

/**
 * Product detail list on single product.
 */
function jwellery_single_product_details() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$rows = array(
		'material' => __( 'Material', 'jwellery-jewelry' ),
		'occasion' => __( 'Occasion', 'jwellery-jewelry' ),
		'care'     => __( 'Care', 'jwellery-jewelry' ),
	);
	?>
	<div class="jwellery-product-details">
		<h3 class="jwellery-product-details-title"><?php esc_html_e( 'Product details', 'jwellery-jewelry' ); ?></h3>
		<ul class="jwellery-product-details-list">
			<?php foreach ( $rows as $key => $label ) : ?>
				<li>
					<strong><?php echo esc_html( $label ); ?></strong>
					<span><?php echo esc_html( jwellery_product_detail_value( $product, $key ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="jwellery-product-details-note">
			<?php esc_html_e( 'Imitation jewellery — not real gold. Colours stay long-lasting with proper care.', 'jwellery-jewelry' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'jwellery_single_product_details', 26 );

/**
 * Similar products (same category).
 */
function jwellery_single_product_similar() {
	if ( ! function_exists( 'is_product' ) || ! is_product() || ! function_exists( 'wc_get_products' ) ) {
		return;
	}
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return;
	}

	$related = wc_get_products(
		array(
			'limit'        => 4,
			'status'       => 'publish',
			'stock_status' => 'instock',
			'category'     => $terms,
			'exclude'      => array( $product->get_id() ),
			'orderby'      => 'rand',
		)
	);

	if ( empty( $related ) ) {
		return;
	}
	?>
	<section class="jwellery-similar-products">
		<h2 class="jwellery-similar-title"><?php esc_html_e( 'You may also like', 'jwellery-jewelry' ); ?></h2>
		<ul class="products jwellery-product-grid jwellery-product-grid--static">
			<?php
			foreach ( $related as $item ) {
				if ( function_exists( 'jwellery_render_product_card' ) ) {
					jwellery_render_product_card( $item );
				}
			}
			?>
		</ul>
	</section>
	<?php
}
add_action( 'woocommerce_after_single_product_summary', 'jwellery_single_product_similar', 15 );

/**
 * Checkout step indicator.
 */
function jwellery_checkout_steps() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}
	$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
	?>
	<nav class="jwellery-checkout-steps" aria-label="<?php esc_attr_e( 'Checkout progress', 'jwellery-jewelry' ); ?>">
		<ol>
			<li class="is-done"><a href="<?php echo esc_url( $cart_url ); ?>"><?php esc_html_e( 'Cart', 'jwellery-jewelry' ); ?></a></li>
			<li class="is-active" aria-current="step"><?php esc_html_e( 'Details', 'jwellery-jewelry' ); ?></li>
			<li><?php esc_html_e( 'Pay via UPI', 'jwellery-jewelry' ); ?></li>
			<li><?php esc_html_e( 'Done', 'jwellery-jewelry' ); ?></li>
		</ol>
	</nav>
	<?php
}
add_action( 'woocommerce_before_checkout_form', 'jwellery_checkout_steps', 5 );

/**
 * Thank-you page step + UPI helper note.
 */
function jwellery_thankyou_steps() {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}
	?>
	<nav class="jwellery-checkout-steps jwellery-checkout-steps--thankyou" aria-label="<?php esc_attr_e( 'Checkout progress', 'jwellery-jewelry' ); ?>">
		<ol>
			<li class="is-done"><?php esc_html_e( 'Cart', 'jwellery-jewelry' ); ?></li>
			<li class="is-done"><?php esc_html_e( 'Details', 'jwellery-jewelry' ); ?></li>
			<li class="is-active" aria-current="step"><?php esc_html_e( 'Pay via UPI', 'jwellery-jewelry' ); ?></li>
			<li><?php esc_html_e( 'Done', 'jwellery-jewelry' ); ?></li>
		</ol>
	</nav>
	<p class="jwellery-upi-helper"><?php esc_html_e( 'Scan the QR or pay to the UPI ID below. Always mention your order number in the payment note.', 'jwellery-jewelry' ); ?></p>
	<?php
}
add_action( 'woocommerce_before_thankyou', 'jwellery_thankyou_steps', 5 );

/**
 * Copy-to-clipboard for UPI ID on thank-you page.
 */
function jwellery_upi_copy_script() {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}
	?>
	<script>
	(function () {
		var box = document.querySelector('.jus-thankyou-upi');
		if (!box) return;
		var code = box.querySelector('code');
		if (!code || !code.textContent) return;
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'jwellery-btn jwellery-btn-primary jwellery-copy-upi';
		btn.textContent = '<?php echo esc_js( __( 'Copy UPI ID', 'jwellery-jewelry' ) ); ?>';
		code.parentNode.appendChild(btn);
		btn.addEventListener('click', function () {
			var text = code.textContent.trim();
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					btn.textContent = '<?php echo esc_js( __( 'Copied!', 'jwellery-jewelry' ) ); ?>';
				});
			}
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'jwellery_upi_copy_script', 50 );

/**
 * Register optional product attributes on store setup.
 */
function jwellery_register_product_attributes() {
	if ( ! function_exists( 'wc_create_attribute' ) ) {
		return;
	}

	$attrs = array(
		'material' => __( 'Material', 'jwellery-jewelry' ),
		'occasion' => __( 'Occasion', 'jwellery-jewelry' ),
		'care'     => __( 'Care', 'jwellery-jewelry' ),
	);

	foreach ( $attrs as $slug => $label ) {
		$tax = 'pa_' . $slug;
		if ( taxonomy_exists( $tax ) ) {
			continue;
		}
		wc_create_attribute(
			array(
				'name'         => $label,
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
	}
}
